<?php

declare(strict_types=1);

use Expansa\Facades\Log;
use Expansa\Log\Contracts\Handler;
use Expansa\Log\Exceptions\LogException;
use Expansa\Log\Formatters\Line;
use Expansa\Log\Formatters\Telegram;
use Expansa\Log\Handlers\AbstractHandler;
use Expansa\Log\Handlers\ErrorLog;
use Expansa\Log\Handlers\File;
use Expansa\Log\Handlers\RotatingFile;
use Expansa\Log\Level;
use Expansa\Log\Logger;
use Expansa\Log\LogRecord;
use Expansa\Log\Manager;

// run: php tests/Log.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

function throws(callable $callback, string $class = LogException::class): bool
{
    try {
        $callback();
    } catch (Throwable $e) {
        return $e instanceof $class;
    }

    return false;
}

// keeps the records in memory
class MemoryHandler extends AbstractHandler
{
    /**
     * @var LogRecord[]
     */
    public array $records = [];

    public function handle(LogRecord $record): bool
    {
        $this->records[] = $record;

        return true;
    }
}

class BrokenHandler extends AbstractHandler
{
    public function handle(LogRecord $record): bool
    {
        throw new RuntimeException('disk is full');
    }
}

function record(string $message, array $context = [], Level $level = Level::Info): LogRecord
{
    return new LogRecord(new DateTimeImmutable('2025-01-31 10:20:30'), 'app', $level, $message, $context);
}

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-log-' . getmypid();

// levels
check('levels resolve from cases, values, RFC 5424 codes and names', Level::of(Level::Error) === Level::Error && Level::of(400) === Level::Error && Level::of(3) === Level::Error && Level::of('ERROR') === Level::Error);
check('unknown levels throw', throws(fn () => Level::of('fatal')) && throws(fn () => Level::of(8)) && throws(fn () => new Logger()->log('verbose', 'x')));
check('level labels and order', Level::Warning->label() === 'WARNING' && Level::Warning->includes(Level::Error) && ! Level::Warning->includes(Level::Info));

// logger
$memory = new MemoryHandler('info');
$logger = new Logger('auth', [$memory]);
$logger->debug('skipped');
$logger->info('User {name} signed in from {ip}, admin: {admin}, {missing}', ['name' => 'Kevin', 'ip' => '127.0.0.1', 'admin' => false]);
$record = $memory->records[0] ?? null;
check('records below the handler level are skipped', count($memory->records) === 1);
check('placeholders are replaced from the context', $record?->message === 'User Kevin signed in from 127.0.0.1, admin: false, {missing}');
check('record gets the channel, level and context', $record?->channel === 'auth' && $record->level === Level::Info && $record->context['name'] === 'Kevin');
$logger->warning('{list} {error} {date}', ['list' => [1], 'error' => new LogicException('bad'), 'date' => new DateTimeImmutable('2025-01-01 00:00', new DateTimeZone('UTC'))]);
check('arrays, exceptions and dates in placeholders are readable', $memory->records[1]->message === '[array] LogicException: bad 2025-01-01T00:00:00+00:00');

$logger->withContext(['request' => 'r1', 'user' => 1]);
$logger->error('Failed', ['user' => 2]);
check('logger context is merged, the record context wins', end($memory->records)->context === ['request' => 'r1', 'user' => 2]);
check('withoutContext clears it', $logger->withoutContext()->getContext() === []);

$logger = new Logger('app', [new MemoryHandler('error'), new MemoryHandler('warning')]);
check('isHandling uses the lowest handler level', $logger->isHandling('warning') && ! $logger->isHandling('notice'));
$top = new MemoryHandler('debug');
$logger->pushHandler($top);
check('pushHandler puts the handler on top and lowers the level', $logger->getHandlers()[0] === $top && $logger->isHandling('debug'));
check('popHandler removes it and raises the level back', $logger->popHandler() === $top && ! $logger->isHandling('debug'));
check('popping an empty stack throws', throws(fn () => new Logger()->popHandler(), LogicException::class));
check('a logger without handlers handles nothing', ! new Logger()->isHandling('emergency'));

$errorLog = $tmp . DIRECTORY_SEPARATOR . 'php-error.log';
@mkdir($tmp, 0777, true);
ini_set('error_log', $errorLog);
$after  = new MemoryHandler();
$logger = new Logger('app', [new BrokenHandler(), $after]);
$logger->critical('Database is down');
check('a failing handler neither throws nor stops the others', count($after->records) === 1);
check('the failure goes to error_log with the record', str_contains((string) @file_get_contents($errorLog), 'BrokenHandler failed: disk is full; record: Database is down'));

new Logger('php', [new ErrorLog('info')])->info('to the error log');
check('ErrorLogHandler writes to error_log', str_contains((string) file_get_contents($errorLog), 'php.INFO: to the error log'));

// line formatter
$formatter = new Line();
check('line format without context', $formatter->format(record('Hello')) === "[2025-01-31 10:20:30] app.INFO: Hello\n");
check('context is JSON with unicode and slashes as is', $formatter->format(record('Hi', ['name' => 'Ян', 'url' => 'https://a.b/c', 'price' => 1.0])) === "[2025-01-31 10:20:30] app.INFO: Hi {\"name\":\"Ян\",\"url\":\"https://a.b/c\",\"price\":1.0}\n");

$line = $formatter->format(record('Failed', ['exception' => new RuntimeException('outer', 7, new LogicException('inner'))]));
check('exceptions are logged with class, message, code, location and previous', str_contains($line, '"class":"RuntimeException","message":"outer","code":7,"file":"' . addslashes(__FILE__) . ':') && str_contains($line, '"previous":{"class":"LogicException","message":"inner"'));

$line = $formatter->format(record('Data', [
    'object'  => new stdClass(),
    'enum'    => Level::Error,
    'invalid' => "\xB1\x31",
    'deep'    => [[[[[['x']]]]]],
    'date'    => new DateTimeImmutable('2025-01-01 00:00', new DateTimeZone('UTC')),
]));
check('objects, enums, invalid UTF-8, deep arrays and dates never break the line', str_contains($line, '"object":"[object stdClass]"') && str_contains($line, '"enum":"Expansa\\\\Log\\\\Level::Error"') && str_contains($line, '"invalid":"�1"') && str_contains($line, '"...') && str_contains($line, '"date":"2025-01-01T00:00:00+00:00"'));
check('custom date format', new Line('H:i')->format(record('x')) === "[10:20] app.INFO: x\n");

$source = record('shared', ['exception' => new RuntimeException('e')]);
$formatter->format($source);
check('formatting does not change the shared record', $source->context['exception'] instanceof RuntimeException);

// telegram formatter
$telegram = new Telegram()->format(record('<script> & "quotes"', ['id' => '<1>'], Level::Error));
check('telegram message escapes HTML', $telegram === "<b>ERROR</b> app\n&lt;script&gt; &amp; \"quotes\"\n<pre>{\"id\":\"&lt;1&gt;\"}</pre>");
$telegram = new Telegram()->format(record(str_repeat('я', 5000), ['a' => str_repeat('b', 5000)]));
check('telegram message fits the limit', mb_strlen(strip_tags(html_entity_decode($telegram))) <= Telegram::MAX_LENGTH);

// file handlers
$file    = $tmp . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'app.log';
$handler = new File($file, 'info');
$handler->handle(record('first'));
$handler->handle(record('second'));
check('FileHandler creates the directory and appends lines', file_get_contents($file) === "[2025-01-31 10:20:30] app.INFO: first\n[2025-01-31 10:20:30] app.INFO: second\n");
$handler->close();
$handler->handle(record('third'));
check('the file is opened again after close', substr_count(file_get_contents($file), "\n") === 3);
$handler->close();
check('an unwritable path throws', throws(fn () => new File($file . DIRECTORY_SEPARATOR . 'x' . DIRECTORY_SEPARATOR . 'a.log')->handle(record('x'))));

$daily = $tmp . DIRECTORY_SEPARATOR . 'daily';
mkdir($daily);
foreach (['2025-01-26', '2025-01-27', '2025-01-28', '2025-01-29', '2025-01-30'] as $date) {
    touch($daily . DIRECTORY_SEPARATOR . "app-$date.log");
}
touch($daily . DIRECTORY_SEPARATOR . 'app-backup.log');
$handler = new RotatingFile($daily . DIRECTORY_SEPARATOR . 'app.log', 3);
$handler->handle(record('today'));
$handler->close();
$left = array_map('basename', glob($daily . DIRECTORY_SEPARATOR . '*'));
sort($left);
check('RotatingFileHandler writes a file per day', file_get_contents($daily . DIRECTORY_SEPARATOR . 'app-2025-01-31.log') === "[2025-01-31 10:20:30] app.INFO: today\n");
check('only the latest maxFiles are kept, other files are not touched', $left === ['app-2025-01-29.log', 'app-2025-01-30.log', 'app-2025-01-31.log', 'app-backup.log']);
$handler->handle(new LogRecord(new DateTimeImmutable('2025-02-01 00:00:01'), 'app', Level::Info, 'tomorrow'));
$handler->close();
check('the day change switches the file', is_file($daily . DIRECTORY_SEPARATOR . 'app-2025-02-01.log') && ! is_file($daily . DIRECTORY_SEPARATOR . 'app-2025-01-29.log'));

// manager
$manager = new Manager();
check('an unconfigured manager writes to error_log', $manager->getDefaultChannel() === 'errorlog' && $manager->channel()->getHandlers()[0] instanceof ErrorLog);

$manager->configure([
    'file'  => ['driver' => 'single', 'path' => $tmp . DIRECTORY_SEPARATOR . 'single.log', 'level' => 'notice'],
    'daily' => ['driver' => 'daily', 'path' => $tmp . DIRECTORY_SEPARATOR . 'daily.log', 'days' => 3],
    'both'  => ['driver' => 'stack', 'channels' => ['file', 'daily']],
    'self'  => ['driver' => 'stack', 'channels' => ['self']],
    'mem'   => ['driver' => 'memory'],
    'bad'   => ['driver' => 'unknown'],
    'tg'    => ['driver' => 'telegram', 'token' => 't'],
]);
check('the first channel is the default one', $manager->getDefaultChannel() === 'file');
check('channels are created once', $manager->channel('file') === $manager->channel('file') && $manager->channel('file')->getName() === 'file');
check('channel drivers and levels come from the config', $manager->channel()->getHandlers()[0] instanceof File && $manager->channel()->getHandlers()[0]->getLevel() === Level::Notice);
check('stack channel gets the handlers of its channels', count($manager->channel('both')->getHandlers()) === 2);
check('a stack including itself throws', throws(fn () => $manager->channel('self')));
check('unknown channel, driver and missing options throw', throws(fn () => $manager->channel('none')) && throws(fn () => $manager->channel('bad')) && throws(fn () => $manager->channel('tg')));
check('unknown default channel throws', throws(fn () => new Manager()->configure(['a' => ['driver' => 'single']], 'b')));

$memory = new MemoryHandler();
$manager->extend('memory', fn (array $config, string $name) => $memory);
$manager->shareContext(['request' => 'abc']);
$manager->channel('mem')->info('custom');
check('custom drivers may return a handler, shared context reaches new channels', $memory->records[0]->channel === 'mem' && $memory->records[0]->context === ['request' => 'abc']);
check('shared context reaches existing channels', $manager->channel('file')->getContext() === ['request' => 'abc']);
check('on-demand stack', count($manager->stack(['file', 'mem'])->getHandlers()) === 2 && $manager->stack(['mem'])->getContext() === ['request' => 'abc']);
check('flushSharedContext forgets it', $manager->flushSharedContext()->sharedContext() === []);
check('forgetChannel drops a channel', $manager->channel('mem') !== $manager->forgetChannel('mem')->channel('mem'));

$manager->warning('to the default channel');
$manager->info('below its level');
$manager->log('error', 'by level name');
$single = (string) file_get_contents($tmp . DIRECTORY_SEPARATOR . 'single.log');
check('PSR-3 methods write to the default channel', str_contains($single, 'file.WARNING: to the default channel') && str_contains($single, 'file.ERROR: by level name') && ! str_contains($single, 'below its level'));
foreach ($manager->getChannels() as $channel) {
    foreach ($channel->getHandlers() as $handler) {
        if ($handler instanceof File) {
            $handler->close();
        }
    }
}

Log::configure(['mem' => ['driver' => 'memory']]);
Log::extend('memory', fn () => $memory);
Log::error('facade');
check('Log facade uses the manager', end($memory->records)->message === 'facade');

// cleanup
$remove = function (string $dir) use (&$remove) {
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $path) {
        is_dir($path) ? $remove($path) : unlink($path);
    }
    rmdir($dir);
};
$remove($tmp);

exit($failures > 0 ? 1 : 0);
