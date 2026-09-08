<?php

declare(strict_types=1);

namespace App\Api\Apikey;

use App\Models\Field;
use App\Models\Post;
use DateTime;
use Expansa\Facades\Safe;
use Expansa\Http\Response;
use Expansa\Support\Str;

final class ApikeyService
{
    /**
     * The real create logic used to sit after a `var_dump(...); exit;` debug block
     * (plus an unrelated `\App\Models\Apikey::get(2)` test fetch immediately overwritten
     * below it) — restored to its working form, debug artifacts dropped.
     *
     * Returns a raw Response (not a plain array) to keep the exact wire shape the
     * frontend already expects (dashboard/forms/api-keys-manager.php reads `.post`
     * off the top-level response, not nested under an extra `data` key).
     */
    public function create(array $input): Response|array
    {
        $fields = Safe::data($input, [
            'limits'     => 'absint',
            'period'     => 'text',
            'start-date' => 'date',
            'end-date'   => 'date',
            'sites'      => 'trim|sitesList',
            'token'      => 'trim:' . Str::random(32),
        ])->extend('date', function ($value) {
            $date = DateTime::createFromFormat('j F, Y', $value);
            return $date instanceof DateTime ? $date->format('Y-m-d') : '';
        })->extend('sitesList', function ($value) {
            $sitesList = array_map('trim', explode(',', $value));
            return array_filter($sitesList, fn($url) => filter_var($url, FILTER_VALIDATE_URL));
        })->apply();

        $title  = Safe::text($input['title'] ?? '');
        $status = Safe::text($input['status'] ?? '');

        $post = Post::add('api-keys', compact('title', 'status', 'fields'));

        if (!$post instanceof Post) {
            return ['method' => 'POST create user'];
        }

        $postData = [];
        foreach ((array) $post as $key => $value) {
            if (!in_array($key, ['uuid', 'title', 'status', 'createdAt', 'updatedAt'], true)) {
                continue;
            }

            if (in_array($key, ['createdAt', 'updatedAt'], true)) {
                $value = (new DateTime($value))->format('j F, Y');
            }

            $postData[$key] = $value;
        }

        $keyFields = (new Field($post))->get();
        if ($keyFields) {
            foreach ($keyFields as $field => $values) {
                $key = Safe::camelcase($field);
                if (!isset($key, $values[0])) {
                    continue;
                }

                if (in_array($key, ['endDate', 'startDate'], true)) {
                    $date = DateTime::createFromFormat('Y-m-d', $values[0]);
                    if ($date instanceof DateTime) {
                        $values[0] = $date->format('j F, Y');
                    }
                }

                $postData[$key] = $values[0];
            }
        }

        return (new Response())->json([
            'post' => $postData,
            'data' => [
                ['target' => 'body', 'notify' => t('New API key is created')],
            ],
        ]);
    }
}
