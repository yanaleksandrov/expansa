<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Ai\Commands\Work;
use Expansa\Ai\Contexts\Files;
use Expansa\Ai\Limits;
use Expansa\Ai\Manager;
use Expansa\Ai\Providers\OpenAi;
use Expansa\Ai\Queue;
use Expansa\Ai\Stores\File;

/**
 * Builds the plugin generation queue from the AI tab of the settings (`ai` option: address, model,
 * encrypted key) and the server paths of EX_AI in env.php. Web requests only store tasks; the manager
 * and its provider are created in the `ai:work` worker.
 */
final class Ai
{
    /**
     * Address of the service until the settings name another; Google Gemini has a free tier.
     */
    public const string URL = 'https://generativelanguage.googleapis.com/v1beta/openai/';

    /**
     * Model of the default service.
     */
    public const string MODEL = 'gemini-flash-latest';

    /**
     * Queue of the current process, created on the first call.
     *
     * @var Queue|null
     */
    private static ?Queue $queue = null;

    /**
     * Returns the queue shared by the API controller and the `ai:work` command.
     */
    public static function queue(): Queue
    {
        return self::$queue ??= new Queue(
            store: new File(EX_STORAGE . 'ai'),
            manager: self::manager(...),
            start: Work::createLauncher(EX_PATH . 'artisan', self::config()['php']),
        );
    }

    /**
     * Whether a service key is set; local services such as Ollama need none, so a URL to localhost counts too.
     */
    public static function isConfigured(): bool
    {
        $config = self::config();

        $isLocal = str_contains($config['url'], '://localhost') || str_contains($config['url'], '://127.0.0.1');

        return $config['url'] !== '' && ($config['key'] !== '' || $isLocal);
    }

    /**
     * Model the tasks are generated with.
     *
     * @return string
     */
    public static function getModel(): string
    {
        return self::config()['model'];
    }

    /**
     * PHP CLI binary that runs the worker.
     *
     * @return string
     */
    public static function getPhp(): string
    {
        return self::config()['php'];
    }

    /**
     * Creates the manager for one worker run.
     */
    private static function manager(): Manager
    {
        $config = self::config();

        return new Manager(
            provider: new OpenAi(
                url: $config['url'],
                model: $config['model'],
                key: $config['key'],
                schemas: $config['schemas'],
                options: $config['options'],
            ),
            context: new Files($config['context'], always: ['Extensions.md', 'Hooks.md']),
            // whole plugins with tests need long responses; thinking models spend part of them on reasoning
            limits: new Limits(contextTokens: 12000, outputTokens: 16000, totalTokens: 200000),
        );
    }

    /**
     * Returns the service of the AI tab of the settings and the server paths of EX_AI.
     *
     * @return array{url: string, model: string, key: string, schemas: bool, options: array<string, mixed>, context: string, php: string}
     */
    private static function config(): array
    {
        $ai = (array) Option::get('ai', []);

        return [
            'url'     => trim((string) ($ai['url'] ?? self::URL)),
            'model'   => trim((string) ($ai['model'] ?? '')) ?: self::MODEL,
            'key'     => Secrets::decrypt((string) ($ai['key'] ?? '')),
            'schemas' => (bool) ($ai['schemas'] ?? true),
        ] + (defined('EX_AI') ? EX_AI : []) + [
            'options' => [],
            'context' => dirname(EX_PATH) . '/documentation',
            'php'     => PHP_BINARY,
        ];
    }
}
