<?php

declare(strict_types=1);

namespace Expansa\Log;

use Closure;
use Stringable;
use Expansa\Log\Contracts\Handler;
use Expansa\Log\Contracts\LoggerInterface;
use Expansa\Log\Exception\LogException;
use Expansa\Log\Handlers\ErrorLog;
use Expansa\Log\Handlers\File;
use Expansa\Log\Handlers\RotatingFile;
use Expansa\Log\Handlers\Telegram;

/**
 * Channels described by configuration and created on first use, the Log facade instance.
 * PSR-3 methods write to the default channel; without configuration it passes records to error_log().
 *
 * @package Expansa\Log
 */
class Manager implements LoggerInterface
{
    /**
     * Channel configs by name, each with a `driver` key.
     */
    protected array $config = [
        'errorlog' => ['driver' => 'errorlog'],
    ];

    protected string $default = 'errorlog';

    /**
     * Created channels by name.
     *
     * @var Logger[]
     */
    protected array $channels = [];

    /**
     * Custom drivers: get the channel config and name, return a Logger or a Handler.
     *
     * @var Closure[]
     */
    protected array $drivers = [];

    /**
     * Context added to every channel.
     */
    protected array $sharedContext = [];

    /**
     * Channels being created, to detect a stack that includes itself.
     */
    private array $resolving = [];

    /**
     * Set the channels, created channels are dropped.
     *
     * @param array  $channels Configs by name: `['daily' => ['driver' => 'daily', 'path' => '...', 'days' => 14]]`.
     * @param string $default  Channel of the PSR-3 methods, the first channel by default.
     * @return void
     * @throws LogException If the default channel is not configured.
     */
    public function configure(array $channels, string $default = ''): void
    {
        $default = $default !== '' ? $default : (string) array_key_first($channels);

        if (! isset($channels[$default])) {
            throw new LogException("Default logging channel [$default] is not configured.");
        }

        $this->config   = $channels;
        $this->default  = $default;
        $this->channels = [];
    }

    /**
     * Add a custom driver.
     *
     * @param string  $driver
     * @param Closure $factory Gets the channel config and name, returns a Logger or a Handler.
     * @return static
     */
    public function extend(string $driver, Closure $factory): static
    {
        $this->drivers[$driver] = $factory;

        return $this;
    }

    /**
     * Get a channel, the default one without a name.
     *
     * @param string|null $name
     * @return Logger
     * @throws LogException For an unknown channel or driver.
     */
    public function channel(?string $name = null): Logger
    {
        $name ??= $this->default;

        return $this->channels[$name] ?? $this->channels[$name] = $this->resolve($name);
    }

    /**
     * Create a channel writing to all handlers of the given channels, it is not cached.
     *
     * @param string[] $channels
     * @param string   $name
     * @return Logger
     */
    public function stack(array $channels, string $name = 'stack'): Logger
    {
        return $this->createStackDriver(['channels' => $channels], $name)->withContext($this->sharedContext);
    }

    /**
     * Get the channels created so far.
     *
     * @return Logger[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Drop a created channel, the next call creates it again.
     *
     * @param string|null $name The default channel by default.
     * @return static
     */
    public function forgetChannel(?string $name = null): static
    {
        unset($this->channels[$name ?? $this->default]);

        return $this;
    }

    public function getDefaultChannel(): string
    {
        return $this->default;
    }

    /**
     * Add context to every channel, including the ones created later.
     *
     * @param array $context
     * @return static
     */
    public function shareContext(array $context): static
    {
        $this->sharedContext = [...$this->sharedContext, ...$context];

        foreach ($this->channels as $channel) {
            $channel->withContext($context);
        }

        return $this;
    }

    public function sharedContext(): array
    {
        return $this->sharedContext;
    }

    /**
     * Forget the shared context, created channels keep their context.
     *
     * @return static
     */
    public function flushSharedContext(): static
    {
        $this->sharedContext = [];

        return $this;
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Emergency, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Alert, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Critical, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Error, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Warning, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Notice, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Info, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->channel()->log(Level::Debug, $message, $context);
    }

    public function log(Level|int|string $level, string|Stringable $message, array $context = []): void
    {
        $this->channel()->log($level, $message, $context);
    }

    /**
     * Create a channel from its config.
     *
     * @param string $name
     * @return Logger
     * @throws LogException
     */
    protected function resolve(string $name): Logger
    {
        $config = $this->config[$name] ?? null;
        if (! is_array($config) || ! isset($config['driver'])) {
            throw new LogException("Logging channel [$name] is not configured.");
        }

        if (isset($this->resolving[$name])) {
            throw new LogException("Logging channel [$name] includes itself.");
        }

        $this->resolving[$name] = true;

        try {
            $driver = $config['driver'];
            $method = 'create' . ucfirst($driver) . 'Driver';

            $logger = match (true) {
                isset($this->drivers[$driver]) => $this->createCustomDriver($config, $name),
                method_exists($this, $method)  => $this->{$method}($config, $name),
                default                        => throw new LogException("Logging driver [$driver] of [$name] is not supported."),
            };
        } finally {
            unset($this->resolving[$name]);
        }

        return $logger->withContext($this->sharedContext);
    }

    protected function createCustomDriver(array $config, string $name): Logger
    {
        $result = ($this->drivers[$config['driver']])($config, $name);

        return $result instanceof Handler ? new Logger($name, [$result]) : $result;
    }

    protected function createStackDriver(array $config, string $name): Logger
    {
        $handlers = [];
        foreach ((array) ($config['channels'] ?? []) as $channel) {
            array_push($handlers, ...$this->channel($channel)->getHandlers());
        }

        return new Logger($name, $handlers);
    }

    protected function createSingleDriver(array $config, string $name): Logger
    {
        return new Logger($name, [new File($this->required($config, 'path', $name), $config['level'] ?? Level::Debug)]);
    }

    protected function createDailyDriver(array $config, string $name): Logger
    {
        $path = $this->required($config, 'path', $name);
        $days = (int) ($config['days'] ?? 7);

        return new Logger($name, [new RotatingFile($path, $days, $config['level'] ?? Level::Debug)]);
    }

    protected function createTelegramDriver(array $config, string $name): Logger
    {
        $token  = $this->required($config, 'token', $name);
        $chatId = $this->required($config, 'chat_id', $name);

        return new Logger($name, [new Telegram($token, $chatId, $config['level'] ?? Level::Error)]);
    }

    protected function createErrorlogDriver(array $config, string $name): Logger
    {
        return new Logger($name, [new ErrorLog($config['level'] ?? Level::Debug)]);
    }

    private function required(array $config, string $key, string $name): mixed
    {
        return $config[$key] ?? throw new LogException("Logging channel [$name] requires the \"$key\" option.");
    }
}
