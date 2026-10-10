<?php

declare(strict_types=1);

use Expansa\Debug\Dumper;
use Expansa\Debug\Manager;
use Expansa\Debug\Metric;
use Expansa\Debug\Panel;
use Expansa\Log\Enums\Level;

// run: php tests/Debug.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Throws a TypeError from a call with a wrong argument.
 *
 * @param int $number
 * @return int
 */
function debugTyped(int $number): int
{
    return $number;
}

/**
 * Output of the callback.
 *
 * @param callable $callback
 * @return string
 */
function debugOutput(callable $callback): string
{
    ob_start();
    $callback();

    return (string) ob_get_clean();
}

// the variables of the error page template, read through a template that outputs them as JSON
$template = tempnam(sys_get_temp_dir(), 'view');
file_put_contents($template, '<?php echo json_encode(compact("title", "message", "id", "file", "line", "trace", "arguments", "previous", "request"));');

/**
 * Output of the error, rendered by a manager like the one bootstrap.php configures.
 *
 * @param Throwable            $e
 * @param bool                 $details
 * @param array<string, mixed> $request Context of the request.
 * @param string|null          $view    The JSON template by default; '' for text.
 * @return string
 */
function debugRender(Throwable $e, bool $details = true, array $request = [], ?string $view = null): string
{
    global $template;

    $manager = new Manager(console: false);
    $manager->configure(
        view: $view ?? $template,
        details: $details,
        context: fn () => $request,
        json: fn () => false,
        collapse: [EX_PATH . 'expansa'],
    );

    return debugOutput(fn () => $manager->send($e, 'abc123'));
}

$cause = new RuntimeException('database is down');
$error = new LogicException('<script>alert(1)</script>', 0, $cause);

// page data
$public = json_decode(debugRender($error, false), true);
check('without details the page shows no message', $public['message'] !== $error->getMessage() && $public['title'] === 'Server Error');
check('without details the page shows no code, file or trace', $public['file'] === '' && $public['trace'] === [] && $public['request'] === []);
check('without details the page shows the error id', $public['id'] === 'abc123');

$data = json_decode(debugRender($error, true, ['method' => 'POST', 'input' => ['password' => 'secret', 'name' => 'Ann']]), true);
check('details have the class and the message', $data['title'] === LogicException::class && $data['message'] === $error->getMessage());
check('the trace starts at the place of the throw', $data['trace'][0]['file'] === __FILE__ && $data['trace'][0]['line'] === $error->getLine());
check('every frame has the code around its line', $data['trace'][0]['start'] === max(1, $error->getLine() - 10) && str_contains($data['trace'][0]['code'], "new LogicException('<script>"));
check('previous exceptions are listed', count($data['previous']) === 1 && $data['previous'][0]['message'] === 'database is down');
check('the request hides secrets', $data['request']['input'] === '{"password":"********","name":"Ann"}' && $data['request']['method'] === 'POST');

$core = (function () {
    try {
        Expansa\Support\Arr::map([1], fn () => throw new Exception('core'));
    } catch (Exception $e) {
        return $e;
    }
})();
$frames = json_decode(debugRender($core), true)['trace'];
check('the place of the throw is never collapsed', $frames[0]['collapsed'] === false);
check('frames under a collapsed path are collapsed', $frames[1]['collapsed'] === true && str_contains($frames[1]['file'], 'Arr.php'));
check('other frames are not collapsed', end($frames)['collapsed'] === false);

$eval = (function () {
    try {
        eval('throw new Exception("eval");');
    } catch (Exception $e) {
        return $e;
    }
})();
check('code of a missing file is empty', json_decode(debugRender($eval), true)['trace'][0]['code'] === '');

try {
    debugTyped(...['not a number']);
} catch (TypeError $typeError) {
}
$arguments = json_decode(debugRender($typeError), true)['arguments'];
check('a TypeError lists the arguments when the trace keeps them', ini_get('zend.exception_ignore_args') ? $arguments === [] : $arguments[0] === ['position' => 1, 'type' => 'string', 'value' => "'not a number'"]);

$text = debugRender($error, true, [], '');
check('the text has the id, the error, the trace and the cause', str_starts_with($text, "Error ID: abc123" . PHP_EOL . 'LogicException: <script>') && str_contains($text, __FILE__) && str_contains($text, 'Caused by RuntimeException: database is down'));

// template
$html = debugRender($error, true, ['input' => ['password' => 'secret']], EX_PATH . 'dashboard/views/fallback/debug.php');
check('the page escapes the message', ! str_contains($html, '<script>alert') && str_contains($html, '&lt;script&gt;alert(1)'));
check('the page escapes the code', str_contains($html, 'new LogicException(&#039;&lt;script&gt;'));
check('the page shows the error id and the request', str_contains($html, 'abc123') && str_contains($html, '&quot;password&quot;:&quot;********&quot;'));

$html = debugRender($error, false, [], EX_PATH . 'dashboard/views/fallback/debug.php');
check('the public page has the id but no trace or code', str_contains($html, 'abc123') && ! str_contains($html, 'errors-source') && ! str_contains($html, __FILE__));
unlink($template);

// manager
$reported = [];
$manager  = new Manager(console: false);
$manager->configure(
    view: EX_PATH . 'dashboard/views/fallback/debug.php',
    report: function (Throwable $e, string $id, array $context) use (&$reported) {
        $reported = [$e, $id, $context];
    },
    context: fn () => ['url' => '/a', 'user' => fn () => throw new RuntimeException('no database'), 'token' => 'x'],
    json: fn () => false,
);

$id = $manager->report($error, ['controller' => 'Post']);
check('report() passes the error with a new id', $reported[0] === $error && $reported[1] === $id && preg_match('/^[0-9a-f]{8}$/', $id) === 1);
check('report() adds the request context, a failing value keeps the rest', $reported[2] === ['controller' => 'Post', 'url' => '/a', 'user' => 'failed: no database', 'token' => '********']);
check('every report has its own id', $manager->report($error) !== $id);

$manager->report($error, ['user' => ['api_key' => 1, 'login' => 'ann'], 'csrf' => 'x']);
check('the context hides nested secrets', $reported[2]['user'] === ['api_key' => '********', 'login' => 'ann'] && $reported[2]['csrf'] === '********');

$html = debugOutput(fn () => $manager->send($error, 'abc123'));
check('render() outputs the page', str_contains($html, '<html') && str_contains($html, 'abc123'));
check('render() hides the details by default', ! str_contains($html, '&lt;script&gt;alert'));

$manager->configure(json: fn () => true);
$json = json_decode(debugOutput(fn () => $manager->send($error, 'abc123')), true);
check('json without details has only the message and the id', $json === ['message' => 'Something went wrong. Please try again later.', 'id' => 'abc123']);

$manager->configure(details: true, json: fn () => true);
$json = json_decode(debugOutput(fn () => $manager->send($error, 'abc123')), true);
check('json with details has the message, the id, the class and the trace', $json['message'] === $error->getMessage() && $json['id'] === 'abc123' && $json['exception'] === LogicException::class && str_contains($json['trace'][0], __FILE__));

$manager->configure();
check('without a view render() outputs the id as text', debugOutput(fn () => $manager->send($error, 'abc123')) === 'Server Error. Error ID: abc123');

$broken = new Manager(console: false);
$broken->configure(report: fn () => throw new RuntimeException('log is down'));
$logged = ini_set('error_log', $file = tempnam(sys_get_temp_dir(), 'debug'));
check('a failing report still returns the id', preg_match('/^[0-9a-f]{8}$/', $broken->report($error)) === 1);
check('a failing report writes both errors to error_log()', str_contains(file_get_contents($file), 'log is down') && str_contains(file_get_contents($file), 'database is down'));
ini_set('error_log', (string) $logged);
unlink($file);

// error handler
$warnings = [];
$handler  = new Manager(console: false);
$handler->configure(warning: function (ErrorException $e) use (&$warnings) {
    $warnings[] = $e->getMessage();
});
$handler->register();

$undefined = [];
$value     = $undefined['missing'] ?? null;
echo @$undefined['silenced'];
trigger_error('custom warning', E_USER_WARNING);
check('a warning goes to the callback and the request goes on', $warnings === ['custom warning']);
check('a silenced error is ignored', ! in_array('Undefined array key "silenced"', $warnings, true));

$handler->configure(details: true, warning: function (ErrorException $e) use (&$warnings) {
    $warnings[] = $e->getMessage();
});
trigger_error('shown', E_USER_WARNING);
check('details alone do not stop the request on a warning', end($warnings) === 'shown' && $handler->hasDetails());

$handler->configure(strict: true, warning: function (ErrorException $e) use (&$warnings) {
    $warnings[] = $e->getMessage();
});
check('in strict mode a warning is thrown', throws(fn () => trigger_error('strict', E_USER_WARNING), ErrorException::class) && ! $handler->hasDetails());
trigger_error('old', E_USER_DEPRECATED);
check('a deprecation goes to the callback even in strict mode', end($warnings) === 'old');

$warnings = [];
for ($i = 0; $i < 3; $i++) {
    trigger_error("repeated $i", E_USER_DEPRECATED);
}
check('the same place is passed to the callback once per request', $warnings === ['repeated 0']);
restore_error_handler();
restore_exception_handler();

check('isFatal() is true for a fatal error from the shutdown function', Manager::isFatal(new ErrorException('out of memory', 0, E_ERROR)));
check('isFatal() is false for a warning and an exception', ! Manager::isFatal(new ErrorException('w', 0, E_WARNING)) && ! Manager::isFatal($error));

// output of a registered manager: the buffers started after register() are dropped
ob_start();
$buffers = new Manager(console: false);
$buffers->configure(view: EX_PATH . 'dashboard/views/fallback/debug.php');
$buffers->register();
ob_start();
echo '<main>half-rendered page';
$buffers->send($error, 'abc123');
$html = (string) ob_get_clean();
restore_error_handler();
restore_exception_handler();
check('render() drops the half-rendered page', ! str_contains($html, 'half-rendered') && str_contains($html, '<html'));

$_SERVER['REQUEST_METHOD'] = 'HEAD';
check('a HEAD response has no body', debugOutput(fn () => $buffers->send($error, 'abc123')) === '');
unset($_SERVER['REQUEST_METHOD']);

// a failing template
$view = tempnam(sys_get_temp_dir(), 'view');
file_put_contents($view, '<?php echo "<html>partial"; throw new RuntimeException("template broke");');
$failing = new Manager(console: false);
$failing->configure(view: $view);
$html = debugOutput(fn () => $failing->send($error, 'abc123'));
check('a failing template falls back to text with the id', $html === '<pre>Server Error. Error ID: abc123</pre>');
$failing->configure(view: $view, details: true);
$html = debugOutput(fn () => $failing->send($error, 'abc123'));
check('in details mode the fallback has the error and the template failure', str_contains($html, 'LogicException: &lt;script&gt;') && str_contains($html, 'template broke') && ! str_contains($html, 'partial'));
unlink($view);

// panel
$panel = new Panel()
    ->add('Queries', fn () => [['query' => 'SELECT <1>']])
    ->add('Metrics', fn () => ['time' => '1ms', 'count' => 3])
    ->add('Broken', fn () => throw new RuntimeException('no data'));
$sections = $panel->getSections();
check('a list of rows stays as it is', $sections['Queries'] === [['query' => 'SELECT <1>']]);
check('pairs become name and value rows', $sections['Metrics'] === [['name' => 'time', 'value' => '1ms'], ['name' => 'count', 'value' => '3']]);
check('a failing section shows its error', $sections['Broken'] === [['error' => 'no data']]);
check('the panel escapes values', str_contains($panel->render(), 'SELECT &lt;1&gt;'));
check('an empty panel renders nothing', new Panel()->render() === '');

$panel->add('Queries', fn () => [['query' => 'replaced']]);
check('add() keeps a section with the same title', $panel->getSections()['Queries'] === [['query' => 'SELECT <1>']]);
$panel->forget('Queries')->add('Queries', fn () => [['query' => 'replaced']]);
check('forget() lets another section take the title', $panel->getSections()['Queries'] === [['query' => 'replaced']]);

Expansa\Facades\Panel::add('Facade', fn () => ['shared' => true]);
check('the Panel facade keeps one panel for the request', isset(Expansa\Facades\Panel::getSections()['Facade']));

// dumper
$dumper = new Dumper(console: true);
check('scalars are dumped as code', $dumper->render(null) === 'null' && $dumper->render(true) === 'true' && $dumper->render(1.5) === '1.5');
check('strings have their length', $dumper->render("a\"b") === '"a\"b" (3)');
check('arrays are nested with keys', $dumper->render(['a' => [1]]) === "array(1) [" . PHP_EOL . "  'a' => array(1) [" . PHP_EOL . "    0 => 1" . PHP_EOL . "  ]" . PHP_EOL . "]");
check('enums are dumped by case', $dumper->render(Level::Debug) === Level::class . '::Debug');

$object = new class {
    public int $count = 1;
    protected string $name = 'x';
    private array $items = [];
    public ?object $self = null;
};
$object->self = $object;
$dump = $dumper->render($object);
check('properties are marked by visibility', str_contains($dump, '+count: 1') && str_contains($dump, '#name: "x" (1)') && str_contains($dump, '-items: array(0) []'));
check('recursion is cut', str_contains($dump, '+self: ' . get_debug_type($object) . ' #' . spl_object_id($object) . ' {recursion}'));
check('dump() escapes HTML in the browser', debugOutput(fn () => new Dumper(console: false)->dump('<b>')) === '<pre class="debug-dump">&quot;&lt;b&gt;&quot; (3)</pre>');

// metric
$metric = new Metric();
$metric->start();
check('time() is in milliseconds below a second', preg_match('/^\d+(\.\d)?ms$/', $metric->time()) === 1);
check('time(true) is seconds since start()', $metric->time(true) < 1);
check('memory() has a unit', preg_match('/^\d+(\.\d+)?(B|KB|MB|GB|TB)$/', $metric->memory()) === 1);
check('memory(true) is the peak in bytes', $metric->memory(true) === memory_get_peak_usage());

ini_set('memory_limit', '1G');
check('memoryPercent() reads a limit in gigabytes', $metric->memoryPercent() === round(memory_get_peak_usage(true) / 1024 ** 3 * 100, 2));
ini_set('memory_limit', '-1');
check('memoryPercent() is null without a limit', $metric->memoryPercent() === null);

exit($failures > 0 ? 1 : 0);
