<?php

declare(strict_types=1);

use App\Facades\Dashboard;
use App\Http\Kernel;
use Expansa\Builders\Tree;
use Expansa\Extensions\Plugin;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Facades\Lifecycle;
use Expansa\Facades\Route;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Request;
use Expansa\Support\Is;
use Plugins\FileManager\Controller;
use Plugins\FileManager\Settings;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('File Manager')
            ->setVersion('2027.2')
            ->setAuthor('Expansa Team')
            ->setDescription(t('Edit, delete, upload, download, copy, and paste files and folders.'));
    }

    public function boot(): void
    {
        spl_autoload_register(static function (string $class): void {
            if (str_starts_with($class, 'Plugins\\FileManager\\')) {
                require __DIR__ . '/src/' . substr($class, 20) . '.php';
            }
        });

        if (Lifecycle::is('api')) {
            foreach (Controller::ENDPOINTS as $method) {
                $endpoint = strtolower((string) preg_replace('/[A-Z]/', '-$0', $method));

                Route::post("/api/file-manager/$endpoint", static fn () => Kernel::dispatch(Controller::class, $method));
            }
            foreach (Controller::DOWNLOADS as $method) {
                Route::get("/api/file-manager/$method", static fn () => Kernel::dispatch(Controller::class, $method));
            }
        }

        Hook::add('userEventLabel', static fn (string $label, string $event) => Controller::label($event) ?? $label);

        if (! Is::dashboard()) {
            return;
        }

        Dashboard::page('file-manager', view: __DIR__ . '/views/file-manager', can: 'manage_options');
        Dashboard::data('file-manager', $this->data(...));

        Tree::attach('dashboard-panel-menu', static fn (Tree $tree) => $tree->append(
            [
                [
                    'id'           => 'file-manager',
                    'url'          => 'file-manager',
                    'title'        => t('File Manager'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-folder-open',
                    'position'     => 800,
                ],
            ]
        ));
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }

    public function install(): void
    {
    }

    public function uninstall(): void
    {
    }

    /**
     * Template data of the file manager page: the settings and the listing of the folder from
     * `?path=`, the root when it is missing, so the page shows them without a request.
     * The assets are enqueued here to load only on this page.
     *
     * @param Request $request
     * @return array{root: string, settings: Settings, listing: array<string, mixed>}
     */
    private function data(Request $request): array
    {
        Asset::style('file-manager', '/plugins/file-manager/assets/css/main.css');
        Asset::script('file-manager', '/plugins/file-manager/assets/js/main.js');

        $settings = Settings::load();
        $explorer = $settings->createExplorer();

        try {
            $listing = $explorer->list((string) ($request->query['path'] ?? ''));
        } catch (HttpError) {
            $listing = $explorer->list('');
        }

        return [
            'root'     => basename($explorer->root),
            'settings' => $settings,
            'listing'  => $listing,
        ];
    }
};
