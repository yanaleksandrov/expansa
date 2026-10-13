<?php

declare(strict_types=1);

namespace App\Api\Extensions;

use App\Models\Option;
use Expansa\Facades\Extensions;
use Expansa\Http\Enums\Notice;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Response;

/**
 * Turns plugins on and off: calls their activate() or deactivate() and keeps the ids
 * in the "extensions.active" option, which bootstrap.php loads on every request.
 */
final class ExtensionsService
{
    /**
     * Activates an installed plugin; a quarantined one is released first, so activating retries it.
     *
     * @param string   $id       Plugin id like "plugins/seo".
     * @param Response $response Response to add the notice and the reload to.
     * @return Response
     * @throws HttpError When the plugin is not installed or its activate() fails.
     */
    public function activate(string $id, Response $response): Response
    {
        Extensions::forget($id);

        $plugin = Extensions::getInstalled('plugin')[$id] ?? throw new HttpError(404, t('The plugin is not installed.'));
        if (! Extensions::activate($id)) {
            throw new HttpError(500, t('The plugin :name could not be activated, see the log.', $plugin->name));
        }

        Option::update('extensions.active', array_values(array_unique([...$this->active(), $id])));

        // the menu and the pages of the plugin appear only in the next request
        return $response->notify(t('The plugin :name is activated.', $plugin->name), Notice::Success)->reload(600);
    }

    /**
     * Deactivates a plugin; it leaves the active ones even when its deactivate() fails or it is quarantined.
     *
     * @param string   $id       Plugin id like "plugins/seo".
     * @param Response $response Response to add the notice and the reload to.
     * @return Response
     * @throws HttpError When the plugin is not active.
     */
    public function deactivate(string $id, Response $response): Response
    {
        $active = $this->active();
        if (! in_array($id, $active, true)) {
            throw new HttpError(404, t('The plugin is not active.'));
        }

        // code of a quarantined plugin is not run again
        $done = isset(Extensions::getQuarantined()[$id]) || Extensions::deactivate($id);

        Option::update('extensions.active', array_values(array_diff($active, [$id])));

        $message = $done
            ? $response->notify(t('The plugin is deactivated.'), Notice::Success)
            : $response->notify(t('The plugin is deactivated, but its cleanup failed, see the log.'), Notice::Warning);

        return $message->reload(600);
    }

    /**
     * Ids of the active extensions.
     *
     * @return string[]
     */
    private function active(): array
    {
        return array_filter((array) Option::get('extensions.active', []), is_string(...));
    }
}
