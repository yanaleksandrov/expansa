<?php

declare(strict_types=1);

use Expansa\Ai\Brief;
use Expansa\Ai\Completion;
use Expansa\Ai\Contracts\Context;
use Expansa\Ai\Contracts\Generator;
use Expansa\Ai\Contracts\Provider;
use Expansa\Ai\Contracts\Tool;
use Expansa\Ai\Exceptions\BudgetExceeded;
use Expansa\Ai\Exceptions\ClarificationUnavailable;
use Expansa\Ai\Exceptions\EmptyRequest;
use Expansa\Ai\Exceptions\InputTooLong;
use Expansa\Ai\Exceptions\InvalidConfiguration;
use Expansa\Ai\Exceptions\InvalidResponse;
use Expansa\Ai\Exceptions\RequestFailed;
use Expansa\Ai\Contexts\Files as FilesContext;
use Expansa\Ai\Providers\OpenAi as OpenAiProvider;
use Expansa\Ai\Generation;
use Expansa\Ai\Generators\Ai as AiGenerator;
use Expansa\Ai\Limits;
use Expansa\Ai\Manager;
use Expansa\Ai\Platform;
use Expansa\Ai\Queue;
use Expansa\Ai\Enums\Status;
use Expansa\Ai\Stores\File as FileStore;
use Expansa\Ai\Prompt;
use Expansa\Ai\Session;
use Expansa\Ai\Step;
use Expansa\Ai\TokenCounters\Characters;
use Expansa\Ai\Tools\Registry;
use Expansa\Ai\Validators\Chain;
use Expansa\Ai\Validators\Paths;
use Expansa\Ai\Validators\Php;
use Expansa\Ai\Validators\Policy;
use Expansa\Facades\Ai;

require_once __DIR__ . '/bootstrap.php';

/**
 * Provider that returns queued responses and records prompts.
 */
final class QueueProvider implements Provider
{
    /** @var Prompt[] */
    public array $prompts = [];

    /**
     * @param array<int, string|Closure> $responses Response texts or callbacks of the prompt
     */
    public function __construct(public array $responses, public int $inputTokens = 10, public int $outputTokens = 5) {}

    public function complete(Prompt $prompt, int $maxOutputTokens): Completion
    {
        $this->prompts[] = $prompt;
        $response = array_shift($this->responses) ?? '{}';

        return new Completion(
            $response instanceof Closure ? $response($prompt) : $response,
            $this->inputTokens,
            $this->outputTokens,
            ['model' => 'test'],
        );
    }
}

/**
 * Generator that returns queued file maps or throws queued exceptions.
 */
final class QueueGenerator implements Generator
{
    /** @var Brief[] */
    public array $briefs = [];

    /**
     * @param array<int, array<string, string>|Throwable> $results File maps or exceptions
     */
    public function __construct(public array $results) {}

    public function generate(Brief $brief, int $maxOutputTokens): Generation
    {
        $this->briefs[] = $brief;
        $result = array_shift($this->results) ?? ['Plugin.php' => '<?php return true;'];
        if ($result instanceof Throwable) {
            throw $result;
        }

        return new Generation($result, 7, 3, 1);
    }
}

$context = new class implements Context {
    /** @var array<int, array{string, int}> */
    public array $inputs = [];

    public function get(string $input, int $maxTokens): string
    {
        $this->inputs[] = [$input, $maxTokens];

        return 'Available hooks: welcome';
    }
};

$spec = fn (string $text, array $questions = [], array $calls = []): string => json_encode(
    ['specification' => $text, 'questions' => $questions, 'tool_calls' => $calls],
);
$lenient = new Chain([new Paths(requireTests: false), new Php(), new Policy()]);

// generation, repair, facade, metadata
$provider = new QueueProvider([$spec('Use the registered hook.')], 120, 24);
$generator = new QueueGenerator([['Extension.php' => '<?php function {'], ['Extension.php' => '<?php return true;']]);
Ai::swap(new Manager($provider, $context, $generator, $lenient));
$result = Ai::create('Add a welcome extension');

check('AI package repairs syntax errors and returns a valid extension', $result->valid && count($generator->briefs) === 2);
check(
    'AI repair attempt receives the previous files and their errors',
    $generator->briefs[1]->files === ['Extension.php' => '<?php function {']
        && str_starts_with($generator->briefs[1]->errors[0], 'Extension.php:1:'),
);
check(
    'AI package records token usage, repairs, and limits',
    $result->metadata['total_tokens'] === 164
        && $result->metadata['repairs'] === 1
        && $result->metadata['limits']['total_tokens'] === 60000
        && $result->session->questions === [],
);
check(
    'AI analysis prompt has a schema and skips the tool catalog without tools',
    $provider->prompts[0]->schema !== null && ! isset(json_decode($provider->prompts[0]->user, true)['tools']),
);

// clarification with a stateless manager
$provider = new QueueProvider([$spec('Draft', ['Which hook?']), $spec('Use the welcome hook.')]);
$manager = new Manager($provider, $context, new QueueGenerator([]), $lenient, limits: new Limits(clarifications: 1));
$pending = $manager->create('Add a welcome extension');
$stored = unserialize(serialize($pending->session));
$clarified = $manager->clarify($stored, 'Use the welcome hook');

check('AI package returns questions without files', $pending->questions === ['Which hook?'] && ! $pending->valid);
check(
    'AI session survives serialization and resumes on another manager call',
    $clarified->valid && str_contains(end($context->inputs)[0], 'Use the welcome hook'),
);
check(
    'AI session totals add up both rounds',
    count($clarified->metadata['history']) === 2
        && $clarified->metadata['session_totals']['input_tokens'] === 27
        && $clarified->metadata['session_totals']['clarifications'] === 1,
);
check('AI rejects a second answer after the limit', throws(fn () => $manager->clarify($clarified->session, 'more'), ClarificationUnavailable::class));
check('AI rejects an empty answer', throws(fn () => $manager->clarify($pending->session, '  '), EmptyRequest::class));
check('AI rejects an empty request', throws(fn () => $manager->create(' '), EmptyRequest::class));

// a failed round leaves the session usable
$provider = new QueueProvider([$spec('Draft', ['Which hook?']), 'not json', $spec('Done')]);
$manager = new Manager($provider, $context, new QueueGenerator([]), $lenient);
$pending = $manager->create('Add a welcome extension');
check('AI clarify fails on a malformed response', throws(fn () => $manager->clarify($pending->session, 'welcome'), InvalidResponse::class));
check(
    'AI session can be retried after a failed clarification',
    $manager->clarify($pending->session, 'welcome')->valid && $pending->session->answers === [],
);

// questions after the last clarification are dropped
$provider = new QueueProvider([$spec('Draft', ['Which hook?'])]);
$result = new Manager($provider, $context, new QueueGenerator([]), $lenient, limits: new Limits(clarifications: 0))
    ->create('Add a welcome extension');
check(
    'AI generates with defaults when no clarification rounds are left',
    $result->valid
        && $result->metadata['questions_ignored'] === 1
        && json_decode($provider->prompts[0]->user, true)['clarifications_left'] === 0,
);

// malformed generator output is repairable
$generator = new QueueGenerator([new InvalidResponse('bad map'), ['Plugin.php' => '<?php return true;']]);
$result = new Manager(new QueueProvider([$spec('Spec')]), $context, $generator, $lenient)->create('Add a feature');
check('AI treats a malformed generator response as a failed attempt', $result->valid && $generator->briefs[1]->errors === ['bad map']);

// tools: failures and unknown names go back to the model
$tool = new class implements Tool {
    public string $name = 'hooks';
    public string $description = 'Lists CMS hooks';
    public array $parameters = ['type' => 'object', 'properties' => ['area' => ['type' => 'string']]];

    public function handle(array $arguments): string
    {
        throw new RuntimeException('Hook index is offline');
    }
};
$provider = new QueueProvider([
    $spec('Draft', [], [['name' => 'hooks', 'arguments' => []], ['name' => 'missing', 'arguments' => []]]),
    $spec('Final specification'),
]);
$result = new Manager($provider, $context, new QueueGenerator([]), $lenient, tools: new Registry([$tool]))->create('Use the right hook');
$refinement = json_decode($provider->prompts[1]->user, true);
check(
    'AI reports tool failures to the model instead of stopping',
    $result->specification === 'Final specification'
        && $refinement['tool_results'][0]['error'] === 'Hook index is offline'
        && str_contains($refinement['tool_results'][1]['error'], 'missing'),
);
check(
    'AI sends tool parameters in the analysis prompt',
    json_decode($provider->prompts[0]->user, true)['tools'][0]['parameters']['properties']['area']['type'] === 'string',
);

// progress: each step is reported when it starts and when it finishes
$provider = new QueueProvider([
    json_encode(['message' => 'I will check the hooks.', 'specification' => 'Draft', 'tool_calls' => [['name' => 'hooks', 'arguments' => ['area' => 'home']]]]),
    json_encode(['message' => 'The hook index is offline.', 'specification' => 'Final']),
]);
$generator = new QueueGenerator([['Plugin.php' => '<?php function {'], ['Plugin.php' => '<?php return true;']]);
$steps = [];
new Manager($provider, $context, $generator, $lenient, tools: new Registry([$tool]))
    ->create('Use the right hook', function (Step $step) use (&$steps): void {
        $steps[] = $step;
    });
$order = array_map(fn (Step $step): string => $step->stage->value . ($step->isDone ? '.' : '…'), $steps);
check(
    'AI manager reports every step at its start and its end',
    $order === [
        'context…', 'context.', 'analysis…', 'analysis.', 'tool…', 'tool.', 'refinement…', 'refinement.',
        'generation…', 'generation.', 'validation…', 'validation.', 'generation…', 'generation.', 'validation…', 'validation.',
    ],
);
check(
    'AI steps carry the model messages and the specification',
    $steps[3]->data['message'] === 'I will check the hooks.' && $steps[3]->data['specification'] === 'Draft'
        && $steps[3]->data['tokens'] === 15 && $steps[7]->data['message'] === 'The hook index is offline.',
);
check(
    'AI tool steps report arguments at the start and the failure at the end',
    $steps[4]->data === ['name' => 'hooks', 'arguments' => ['area' => 'home']] && $steps[4]->duration === null
        && $steps[5]->data['error'] === 'Hook index is offline' && $steps[5]->duration >= 0,
);
check(
    'AI generation and validation steps report files, repairs, and errors',
    $steps[9]->data['files'] === ['Plugin.php' => 16] && $steps[11]->data['errors'] !== []
        && $steps[12]->data['repair'] === 1 && $steps[15]->data['errors'] === [] && $steps[0]->round === 0,
);
check(
    'AI generator passes the model message to the generation',
    new AiGenerator(new QueueProvider([json_encode(['message' => ' Added a shortcode. ', 'files' => [['path' => 'a.php', 'content' => '']]])]))
        ->generate(new Brief('', '', ''), 10)->message === 'Added a shortcode.',
);
check(
    'AI rejects a message that is not a string',
    throws(fn () => new Manager(new QueueProvider([json_encode(['specification' => 'x', 'message' => 1])]), $context)->create('x'), InvalidResponse::class),
);

// token budget
$generator = new QueueGenerator([['Plugin.php' => '<?php function {'], ['Plugin.php' => '<?php function {']]);
$result = new Manager(new QueueProvider([$spec('Spec')], 50, 40), $context, $generator, $lenient, limits: new Limits(totalTokens: 95))
    ->create('Add a feature');
check('AI stops repairs when the session budget is spent', ! $result->valid && $result->metadata['budget_exceeded'] && count($generator->briefs) === 1);
check(
    'AI throws when the budget is spent before a required call',
    throws(fn () => new Manager(new QueueProvider([$spec('Spec')], 50, 50), $context, new QueueGenerator([]), $lenient, limits: new Limits(totalTokens: 100))->create('x'), BudgetExceeded::class),
);

// token counter
$counter = new Characters();
check('AI character counter counts code points', $counter->count('Привет') === 6 && $counter->truncate('Привет', 3) === 'При');
check('AI character counter does not undercount invalid UTF-8', $counter->count("\xff\xfe abc") === 6);
check(
    'AI input limit applies to invalid UTF-8',
    throws(fn () => new Manager(new QueueProvider([]), $context, limits: new Limits(inputTokens: 3))->create("\xff\xff\xff\xff"), InputTooLong::class),
);

// built-in generator
$provider = new QueueProvider(["```json\n" . json_encode(['files' => [['path' => 'a.php', 'content' => '<?php return true;']]]) . "\n```"], 40, 12);
$generated = new AiGenerator($provider)->generate(new Brief('Add a feature', 'Hook: welcome', 'Use the hook', ['a.php:1: error'], ['a.php' => '<?php']), 64);
$data = json_decode($provider->prompts[0]->user, true);
check(
    'AI generator parses fenced JSON and records usage',
    $generated->files === ['a.php' => '<?php return true;'] && $generated->inputTokens === 40 && $generated->providerCalls === 1,
);
check('AI generator sends previous files with validation errors', $data['previous_files'] === ['a.php' => '<?php'] && $provider->prompts[0]->schema !== null);
check(
    'AI generator rejects duplicate paths',
    throws(fn () => new AiGenerator(new QueueProvider([json_encode(['files' => [['path' => 'a', 'content' => ''], ['path' => 'a', 'content' => '']]])]))->generate(new Brief('', '', ''), 10), InvalidResponse::class),
);

// validators
$paths = new Paths();
$unsafe = ['../outside.php', '/abs.php', 'C:/x.php', 'a\\b.php', 'a//b.php', 'a/./b.php', 'x.php:stream', 'nul.php', 'dir./a.php', "a\0.php", 'a b.php'];
check(
    'AI path validator rejects unsafe paths',
    count($paths->validate(array_fill_keys($unsafe, '') + ['tests/ATest.php' => ''])) === count($unsafe),
);
check('AI path validator accepts nested paths', $paths->validate(['src/Plugin.php' => '', 'tests/PluginTest.php' => '']) === []);
check('AI path validator requires tests and allowed types', count($paths->validate(['Plugin.php' => '', 'run.sh' => ''])) === 2);
check('AI path validator rejects paths that differ by case', count($paths->validate(['A.php' => '', 'a.php' => '', 'tests/T.php' => ''])) === 1);
check('AI PHP validator reports syntax errors', new Php()->validate(['a.php' => '<?php function {', 'b.md' => '{']) !== []);

$policy = new Policy();
$errors = $policy->validate(['a.php' => "<?php\neval('1');\n`ls`;\nshell_exec('ls');\n\\system('ls');\ninclude \$file;\n"]);
check('AI policy rejects eval, backticks, shell calls, and variable includes', count($errors) === 5 && str_starts_with($errors[0], 'a.php:2:'));
check(
    'AI policy allows methods, declarations, and strings with forbidden names',
    $policy->validate(['a.php' => "<?php\n\$db->exec('x');\nDb::system();\nfunction exec2() {}\n\$s = 'exec(1) `x`';\ninclude __DIR__ . '/a.php';\n"]) === [],
);

// platform and PHP extensions
$platform = new Platform('8.4.0', ['Core', 'mbstring', 'MBSTRING']);
check('AI platform normalizes extension names', $platform->extensions === ['core', 'mbstring'] && $platform->has('MbString'));
check('AI platform defaults to the loaded extensions', new Platform()->has('core') && new Platform()->version === PHP_VERSION);

$provider = new QueueProvider([json_encode(['specification' => 'Resize images', 'missing_extensions' => ['GD', 'mbstring']])]);
$generator = new QueueGenerator([]);
$result = new Manager($provider, $context, $generator, $lenient, platform: $platform)->create('Resize uploaded images');
$analysis = json_decode($provider->prompts[0]->user, true);
check('AI analysis prompt lists the platform extensions', $analysis['platform'] === ['php' => '8.4.0', 'extensions' => ['core', 'mbstring']]);
check(
    'AI reports missing extensions without generating files',
    $result->missingExtensions === ['gd'] && $result->files === [] && ! $result->valid
        && $generator->briefs === [] && str_contains($result->errors[0], 'gd'),
);

$generator = new QueueGenerator([]);
new Manager(new QueueProvider([json_encode(['specification' => 'Spec', 'missing_extensions' => ['mbstring']])]), $context, $generator, $lenient, platform: $platform)
    ->create('Count characters');
check('AI ignores reported extensions that are available', count($generator->briefs) === 1 && $generator->briefs[0]->platform === $platform);

$extensions = new Expansa\Ai\Validators\Extensions(new Platform(extensions: ['core', 'mbstring']));
$errors = $extensions->validate([
    'a.php' => "<?php\nnamespace App;\n\$img = imagecreatetruecolor(1, 1);\n\$zip = new \\ZipArchive();\ncurl_init();\ncurl_close(\$h);\n",
    'b.php' => "<?php\nmb_strlen('x');\n\$client->curl_exec();\nfunction sodium_helper() {}\nsodium_helper();\nclass Redis {}\nRedis::connect();\n",
]);
check(
    'AI extension validator reports functions and classes of unavailable extensions once per file',
    count($errors) === 4
        && str_starts_with($errors[0], 'a.php:3: imagecreatetruecolor needs the PHP extension "gd"')
        && str_contains($errors[1], "ZipArchive needs the PHP extension \"zip\"") && str_contains($errors[2], 'curl_init'),
);
check(
    'AI extension validator accepts code for available extensions',
    new Expansa\Ai\Validators\Extensions(new Platform(extensions: ['core', 'gd', 'zip', 'curl']))
        ->validate(['a.php' => "<?php\nimagecreatetruecolor(1, 1);\nnew ZipArchive();\ncurl_init();\n"]) === [],
);

$provider = new QueueProvider([json_encode(['files' => [['path' => 'a.php', 'content' => '<?php']]])]);
new AiGenerator($provider)->generate(new Brief('r', 'c', 's', platform: $platform), 10);
check('AI generator prompt lists the platform extensions', json_decode($provider->prompts[0]->user, true)['platform']['extensions'] === ['core', 'mbstring']);

// background queue
$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-ai-' . getmypid();
$store = new FileStore($directory);
$started = [];
$responses = [$spec('Draft', ['Which hook?']), $spec('Use the welcome hook.')];
$queue = new Queue(
    $store,
    function () use (&$responses, $context, $lenient): Manager {
        return new Manager(new QueueProvider($responses), $context, new QueueGenerator([]), $lenient);
    },
    function (string $id) use (&$started): void {
        $started[] = $id;
    },
);
$task = $queue->dispatch('Add a welcome extension', owner: '7');
check('AI queue stores a queued task and starts a worker', $task->status === Status::Queued && $started === [$task->id] && $store->get($task->id)?->input === 'Add a welcome extension');
check('AI queue rejects an empty request', throws(fn () => $queue->dispatch(' '), EmptyRequest::class));

$worked = $queue->work();
check('AI worker stops at questions', $worked->status === Status::Questions && $store->get($task->id)->draft->questions === ['Which hook?']);
check('AI queue rejects answers for tasks without questions', throws(fn () => $queue->clarify('missing', 'x'), ClarificationUnavailable::class));

array_shift($responses);
$queue->clarify($task->id, 'Use the welcome hook');
$done = $queue->work($task->id);
check(
    'AI worker resumes the task with the answer',
    $done->status === Status::Ready && $done->draft->valid && $done->answer === null && $store->get($task->id)->status === Status::Ready,
);
$rounds = array_map(fn (Step $step): string => $step->round . $step->stage->value, $done->steps);
check(
    'AI task keeps the finished steps of every round',
    $rounds === ['0context', '0analysis', '1context', '1analysis', '1generation', '1validation']
        && array_all($store->get($task->id)->steps, fn (Step $step): bool => $step->isDone),
);
check('AI queue does not cancel a finished task', ! $queue->cancel($task->id) && ! $queue->cancel('missing'));
check('AI worker returns null on an empty queue', $queue->work() === null);

// cancellation stops the worker at the next step
$cancelling = new class implements Context {
    public ?Closure $onGet = null;

    public function get(string $input, int $maxTokens): string
    {
        ($this->onGet)();

        return '';
    }
};
$provider = new QueueProvider([$spec('Spec')]);
$cancellable = new Queue($store, fn (): Manager => new Manager($provider, $cancelling, new QueueGenerator([]), $lenient));
$running = $cancellable->dispatch('Add a feature');
$cancelling->onGet = function () use ($cancellable, $running): void {
    $cancellable->cancel($running->id);
};
$stopped = $cancellable->work($running->id);
check(
    'AI worker stops a cancelled task without calling the provider',
    $stopped->status === Status::Cancelled && $provider->prompts === [] && $store->get($running->id)->status === Status::Cancelled,
);
check('AI worker does not take a cancelled task', $cancellable->work($running->id) === null);
check('AI store lists tasks of an owner', count($store->all('7')) === 1 && $store->all('8') === []);

$flaky = new Queue($store, fn (): Manager => throw new RuntimeException('Provider timeout'), attempts: 2);
$retry = $flaky->dispatch('Add a feature');
$first = $flaky->work($retry->id);
$second = $flaky->work($retry->id);
check('AI worker retries temporary failures and then fails', $first->status === Status::Queued && $second->status === Status::Failed && $second->error === 'Provider timeout');

$budget = new Queue($store, fn (): Manager => throw new BudgetExceeded('spent'));
check('AI worker fails at once on permanent failures', $budget->work($budget->dispatch('Add a feature')->id)->status === Status::Failed);

$dead = $flaky->dispatch('Add a feature');
$store->claim(900, $dead->id);
check('AI store does not give a running task to another worker', $store->claim(900, $dead->id) === null);
$stale = $store->get($dead->id);
$stale->updatedAt = time() - 1000;
$store->set($stale);
check('AI store reclaims a task whose worker died', $store->claim(900, $dead->id)?->attempts === 2);
check('AI store rejects ids that leave the directory', $store->get('../x') === null && ! $store->delete('../x'));
check('AI store deletes tasks', $store->delete($dead->id) && $store->get($dead->id) === null);

// the user renames, archives, or deletes a task while its worker runs
$renaming = new class implements Context {
    public ?Closure $onGet = null;

    public function get(string $input, int $maxTokens): string
    {
        ($this->onGet)();

        return '';
    }
};
$provider = new QueueProvider([$spec('Spec')]);
$editable = new Queue($store, fn (): Manager => new Manager($provider, $renaming, new QueueGenerator([]), $lenient));
$running = $editable->dispatch('Add a feature');
$renaming->onGet = function () use ($editable, $running): void {
    $editable->rename($running->id, ' Feedback form ');
    $editable->archive($running->id);
};
$finished = $editable->work($running->id);
check(
    'AI worker keeps the title and archive flag changed while it ran',
    $finished->status === Status::Ready && $store->get($running->id)->title === 'Feedback form' && $store->get($running->id)->isArchived,
);

$provider = new QueueProvider([$spec('Spec')]);
$deleted = $editable->dispatch('Add a feature');
$renaming->onGet = function () use ($editable, $deleted): void {
    $editable->delete($deleted->id);
};
$editable->work($deleted->id);
check('AI worker stops and does not restore a deleted task', $store->get($deleted->id) === null && $provider->prompts === []);
check('AI queue reports changes of missing tasks', ! $editable->rename('missing', 'x') && ! $editable->archive('missing'));

array_map(unlink(...), glob($directory . '/{,.}*[!.]', GLOB_BRACE) ?: []);
rmdir($directory);

// OpenAI-compatible provider against a local server
$port = 18000 + getmypid() % 1000;
$server = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/fixtures/ai-server.php'],
    array_fill(1, 2, ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'w']),
    $pipes,
);
for ($i = 0; $i < 50 && ! @fsockopen('127.0.0.1', $port); $i++) {
    usleep(100_000);
}

$prompt = new Prompt('System rules', '{"input":"x"}', ['type' => 'object']);
$completion = new OpenAiProvider("http://127.0.0.1:{$port}/v1", 'test-model', 'secret', options: ['temperature' => 0.2])->complete($prompt, 64);
$sent = json_decode($completion->text, true);
check(
    'AI OpenAI provider sends messages, the schema, the key, and options',
    $sent['path'] === '/v1/chat/completions' && $sent['authorization'] === 'Bearer secret'
        && $sent['request']['messages'][0] === ['role' => 'system', 'content' => 'System rules']
        && $sent['request']['response_format']['json_schema']['schema'] === ['type' => 'object']
        && $sent['request']['max_tokens'] === 64 && $sent['request']['temperature'] === 0.2,
);
check(
    'AI OpenAI provider reports usage and the finish reason',
    $completion->inputTokens === 11 && $completion->outputTokens === 4 && $completion->metadata['finish_reason'] === 'stop',
);
$sent = json_decode(new OpenAiProvider("http://127.0.0.1:{$port}/v1/", 'test-model', schemas: false)->complete($prompt, 64)->text, true);
check(
    'AI OpenAI provider puts the schema into the instructions without structured output',
    $sent['request']['response_format'] === ['type' => 'json_object'] && str_contains($sent['request']['messages'][0]['content'], 'JSON Schema')
        && $sent['authorization'] === '',
);
try {
    new OpenAiProvider("http://127.0.0.1:{$port}/v1/", 'limited')->complete($prompt, 64);
    $reason = '';
} catch (RequestFailed $error) {
    $reason = $error->getMessage();
}
check('AI OpenAI provider throws on an error status with the reason', str_contains($reason, '429') && str_contains($reason, 'Quota exceeded'));
proc_terminate($server);
proc_close($server);
check('AI OpenAI provider throws when the service is unreachable', throws(fn () => new OpenAiProvider('http://127.0.0.1:1/', 'm', timeout: 2)->complete($prompt, 8), RequestFailed::class));

// documentation context
$docs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-ai-docs-' . getmypid();
mkdir($docs);
file_put_contents("{$docs}/Hooks.md", 'Hooks: add and call');
file_put_contents("{$docs}/Mail.md", 'Mail sends email; mail templates and email queue');
file_put_contents("{$docs}/Cache.md", 'Cache stores values');
$found = new FilesContext($docs, always: ['Hooks.md'])->get('Send an email after the order', 1000);
check(
    'AI file context puts the always files first, then matching ones, and skips the rest',
    strpos($found, '## Hooks.md') === 0 && str_contains($found, '## Mail.md') && ! str_contains($found, 'Cache'),
);
array_map(unlink(...), glob("{$docs}/*.md"));
rmdir($docs);

// value objects
check('AI limits reject negative tool budgets', throws(fn () => new Limits(toolCalls: -1), InvalidConfiguration::class));
check('AI limits reject a zero session budget', throws(fn () => new Limits(totalTokens: 0), InvalidConfiguration::class));
check('AI completion rejects negative usage', throws(fn () => new Completion('', -1, 0), InvalidResponse::class));
check('AI generation rejects negative usage', throws(fn () => new Generation([], 0, 0, -1), InvalidResponse::class));
check('AI session copies do not change the original', new Session('x')->withAnswer('y')->answers === ['y']);
