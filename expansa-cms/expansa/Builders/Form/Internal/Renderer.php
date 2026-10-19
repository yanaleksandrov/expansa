<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Internal;

use Expansa\Builders\Form;
use Expansa\Security\Sanitizer;

/**
 * Renders a fields array to HTML: each field goes to the template of its type with ready attributes
 * and visibility conditions. Templates are named by type (`input`, `layout-group`), the `view` callback
 * of Form::configure() finds their files.
 *
 * @internal
 * @package Expansa\Builders
 */
final class Renderer
{
    /**
     * Types that wrap nested fields and render through the `layout-<type>` template, tabs through `layout-tabs`.
     */
    private const array LAYOUT_TYPES = ['tab', 'step', 'group'];

    /**
     * HTML5 input types rendered by the shared `input` template.
     */
    private const array INPUT_TYPES = [
        'color', 'date', 'datetime-local', 'email', 'month',
        'range', 'search', 'tel', 'text', 'time', 'url', 'week',
    ];

    /**
     * Attributes of the youla.js widgets that fields of the type turn into.
     */
    private const array WIDGETS = [
        'select' => 'u-select',
    ];

    /**
     * Options of the `u-pickadate` datepicker by input type; the formats come from the dashboard locale.
     * `week` stays native: air-datepicker has no week view.
     */
    private const array DATE_PICKERS = [
        'date'           => '',
        'datetime-local' => '{"timepicker": true}',
        'time'           => '{"timepicker": true, "onlyTimepicker": true}',
        'month'          => '{"view": "months", "minView": "months", "dateFormat": "MMMM yyyy"}',
    ];

    /**
     * Render fields, nested tabs, steps and groups included.
     *
     * @param array $fields
     * @param int   $step   Number of the first step.
     * @return string
     */
    public function render(array $fields, int $step = 1): string
    {
        $html    = '';
        $hasTabs = false;

        foreach ($fields as $field) {
            $type = Sanitizer::id($field['type'] ?? '');

            // all tabs of a level render together in place of the first one: the menu, then the panels
            if ($type === 'tab') {
                if (! $hasTabs) {
                    $hasTabs = true;
                    $html   .= $this->renderTemplate($type, ['tabs' => $this->tabs($fields, $step)]);
                }
                continue;
            }

            if ($this->isLayout($type)) {
                $field = [
                    'content' => $this->render($field['fields'] ?? [], $step + 1),
                    'step'    => $step++,
                    ...$field,
                ];
            }

            $field['attributes'] = $this->attributes($type, $field);

            if (! empty($field['conditions'])) {
                $field['conditions'] = $this->visibility($field['conditions'], $fields);
            }

            $html .= $this->renderTemplate($type, $field);
        }

        return $html;
    }

    /**
     * Get the tabs of a level with their nested fields rendered, visibility conditions
     * and the one shown when the page opens marked.
     *
     * @param array $fields
     * @param int   $step   Number of the step that holds the tabs.
     * @return array
     */
    private function tabs(array $fields, int $step): array
    {
        $tabs = [];
        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'tab') {
                $tabs[] = [
                    ...$field,
                    'content'    => $this->render($field['fields'] ?? [], $step + 1),
                    'conditions' => empty($field['conditions']) ? [] : $this->visibility($field['conditions'], $fields),
                ];
            }
        }

        $active = $this->activeTab($tabs);
        foreach ($tabs as $i => $tab) {
            $tabs[$i]['active'] = Sanitizer::prop($tab['name'] ?? '') === $active;
        }

        return $tabs;
    }

    /**
     * Get the tab shown when the page opens: the one of the `tab` query parameter, else the first one;
     * a tab its conditions hide is skipped. The server renders it visible, so the page does not flicker.
     *
     * @param array $tabs
     * @return string Prop of the tab, empty when all tabs are hidden.
     */
    private function activeTab(array $tabs): string
    {
        $props = [];
        foreach ($tabs as $tab) {
            if (empty($tab['conditions']['hidden'])) {
                $props[] = Sanitizer::prop($tab['name'] ?? '');
            }
        }

        $query = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : '';

        return in_array($query, $props, true) ? $query : ($props[0] ?? '');
    }

    /**
     * Render the template of a field type and connect its CSS and JS.
     *
     * @param string $type
     * @param array  $field
     * @return string
     */
    private function renderTemplate(string $type, array $field): string
    {
        $template = match (true) {
            $type === 'tab'                          => 'layout-tabs',
            $this->isLayout($type)                   => "layout-$type",
            in_array($type, self::INPUT_TYPES, true) => 'input',
            default                                  => $type,
        };

        // the type is the asset uid: date, range and color share the input template, not its scripts
        Form::assets($template, $type);

        return Form::view($template, $field);
    }

    /**
     * Get the HTML attributes of a field: its own ones plus `type`, `name`,
     * the youla.js widget of the type (`false` in its attribute turns it off) and the error key.
     *
     * @param string $type
     * @param array  $field
     * @return array
     */
    private function attributes(string $type, array $field): array
    {
        $attributes = Sanitizer::array($field['attributes'] ?? []);
        $name       = Sanitizer::name($field['name'] ?? '');

        if ($type === 'submit') {
            $attributes['name'] ??= $name;
        } elseif (! $this->isLayout($type)) {
            $attributes = [
                'type' => $type,
                'name' => $name,
                ...$attributes,
            ];
        }

        if (isset(self::WIDGETS[$type])) {
            $widget = self::WIDGETS[$type];
            if (($attributes[$widget] ?? '') === false) {
                unset($attributes[$widget]);
            } else {
                $attributes[$widget] ??= '';
            }
        }

        // a select and a textarea have no type, the datepicker works on a text input
        if ($type === 'select' || $type === 'textarea') {
            unset($attributes['type']);
        } elseif (isset(self::DATE_PICKERS[$type])) {
            $attributes['type']          = 'text';
            $attributes['u-pickadate'] ??= self::DATE_PICKERS[$type];
        }

        if (! empty($field['invalid'])) {
            $attributes['aria-invalid'] = 'true';
        }

        // youla-ajax.js shows the response errors of this key at the field
        $error = (string) ($field['error'] ?? '');
        if ($error !== '') {
            $attributes['data-error'] = $error;
        }

        return $attributes;
    }

    /**
     * Get the `u-show` and `hidden` attributes of a field from its conditions on other fields:
     * the browser shows the field while all conditions are met, the server hides it if one is not.
     *
     * @param array $conditions Items with `field`, `operator` and `value`.
     * @param array $fields     Fields of the same level, the conditions check their values.
     * @return array
     */
    private function visibility(array $conditions, array $fields): array
    {
        $values      = $this->values($fields);
        $expressions = [];
        $visible     = true;

        foreach ($conditions as ['field' => $name, 'operator' => $operator, 'value' => $value]) {
            if (! $operator || ! isset($values[$name])) {
                continue;
            }

            $condition     = new Condition($name, $operator, $value);
            $expressions[] = $condition->expression();
            $visible       = $condition->matches($values[$name]) && $visible;
        }

        if ($expressions === []) {
            return [];
        }

        return [
            'u-show' => implode(' && ', $expressions),
            'hidden' => Sanitizer::bool(! $visible),
        ];
    }

    /**
     * Get the current values of fields by name, with the fields of nested groups and tabs;
     * each option of a checkbox is a separate value.
     *
     * @param array $fields
     * @return array<string, mixed>
     */
    private function values(array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            // a group or a tab is shown by a field of a sibling one, e.g. a radio choosing between them
            if (in_array($field['type'] ?? '', ['group', 'tab'], true)) {
                $values += $this->values($field['fields'] ?? []);
                continue;
            }

            $options = $field['options'] ?? [];
            if ($options && ($field['type'] ?? '') === 'checkbox') {
                $values += array_combine(array_keys($options), array_column($options, 'checked'));
            } else {
                $values[$field['name'] ?? ''] = $field['attributes']['value'] ?? null;
            }
        }

        return $values;
    }

    /**
     * Whether the type wraps nested fields: a tab, a step or a group.
     *
     * @param string $type
     * @return bool
     */
    private function isLayout(string $type): bool
    {
        return in_array($type, self::LAYOUT_TYPES, true);
    }
}
