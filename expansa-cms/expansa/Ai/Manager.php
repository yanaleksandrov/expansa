<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Exception;
use Expansa\Ai\Contracts\Context;
use Expansa\Ai\Contracts\Generator;
use Expansa\Ai\Contracts\Provider;
use Expansa\Ai\Contracts\TokenCounter;
use Expansa\Ai\Contracts\Tool;
use Expansa\Ai\Contracts\ToolRegistry;
use Expansa\Ai\Contracts\Validator;
use Expansa\Ai\Exceptions\BudgetExceeded;
use Expansa\Ai\Exceptions\ClarificationUnavailable;
use Expansa\Ai\Exceptions\EmptyRequest;
use Expansa\Ai\Exceptions\InputTooLong;
use Expansa\Ai\Exceptions\InvalidResponse;
use Expansa\Ai\Generators\Ai as AiGenerator;
use Expansa\Ai\Internal\Protocol;
use Expansa\Ai\Internal\Trace;
use Expansa\Ai\TokenCounters\Characters;
use Expansa\Ai\Tools\Registry;
use Expansa\Ai\Validators\Chain;
use Expansa\Ai\Validators\Extensions;
use Expansa\Ai\Validators\Paths;
use Expansa\Ai\Validators\Php;
use Expansa\Ai\Validators\Policy;

/**
 * Runs bounded AI extension generation and user clarification rounds.
 * Keeps no state between calls: each result carries a `Session` for the next round,
 * so one manager serves any number of users and requests.
 */
final class Manager
{
    /**
     * Generates the extension's source and test files.
     * Validation feedback is passed back for bounded repair attempts.
     */
    public readonly Generator $generator;

    /**
     * Checks generated output and reports repairable errors.
     * Defaults to paths, PHP syntax, forbidden calls, and extensions of `platform`.
     */
    public readonly Validator $validator;

    /**
     * Coordinates context retrieval, AI calls, tools, generation, and validation.
     * The constructor order follows the data flow from provider to bounded context.
     */
    public function __construct(

        /**
         * Provider used for specification and refinement requests.
         * The default generator uses it for code generation as well.
         */
        public readonly Provider $provider,

        /**
         * Source of relevant CMS APIs, hooks, and other reference material.
         * It receives the request so it can return only matching information.
         */
        public readonly Context $context,

        /**
         * Code generator; null uses `Generators\Ai` with `provider`.
         */
        ?Generator $generator = null,

        /**
         * Output validator; null checks paths, PHP syntax, forbidden calls, and extensions.
         */
        ?Validator $validator = null,

        /**
         * Registry of capabilities the AI may call during specification.
         * Only registered capabilities can be dispatched.
         */
        public readonly ToolRegistry $tools = new Registry(),

        /**
         * Hard limits for input, context, tool calls, output, repair, and the session.
         * Defaults suit an MVP and can be tuned to the selected provider.
         */
        public readonly Limits $limits = new Limits(),

        /**
         * Counts and truncates text using the model's tokenizer.
         * The fallback counts Unicode characters when no model tokenizer is supplied.
         */
        public readonly TokenCounter $tokenizer = new Characters(),

        /**
         * PHP version and extensions the generated code may use.
         * The model receives them and reports extensions the task needs but the platform lacks.
         */
        public readonly Platform $platform = new Platform(),
    ) {
        $this->generator = $generator ?? new AiGenerator($provider);
        $this->validator = $validator ?? new Chain([new Paths(), new Php(), new Policy(), new Extensions($platform)]);
    }

    /**
     * Starts a session for the supplied user request.
     * Returns questions in the result when required details are missing.
     *
     * @param string $input User's natural language request for the extension
     * @return Draft Generated result or questions for the user
     * @throws EmptyRequest When the supplied input is empty
     * @throws InputTooLong When input exceeds the configured size
     * @throws InvalidResponse When a provider returns malformed protocol data
     * @throws BudgetExceeded When the session token budget runs out before a required call
     */
    public function create(string $input): Draft
    {
        if (trim($input) === '') {
            throw new EmptyRequest('The plugin request cannot be empty.');
        }

        return $this->run(new Session($input));
    }

    /**
     * Adds one answer and resumes the session.
     * The passed session is not changed, so it can be retried after an exception.
     *
     * @param Session $session Session of the result that asked the questions
     * @param string $answer User's response to the pending questions
     * @return Draft Updated result or another clarification request
     * @throws EmptyRequest When the answer is empty
     * @throws ClarificationUnavailable When no question is pending or the limit is reached
     * @throws InputTooLong When combined input exceeds the configured size
     * @throws InvalidResponse When a provider returns malformed protocol data
     * @throws BudgetExceeded When the session token budget runs out before a required call
     */
    public function clarify(Session $session, string $answer): Draft
    {
        $answer = trim($answer);
        if ($answer === '') {
            throw new EmptyRequest('The clarification answer cannot be empty.');
        }
        if ($session->questions === [] || $session->clarifications >= $this->limits->clarifications) {
            throw new ClarificationUnavailable('No clarification answer can be accepted for this request.');
        }

        return $this->run($session->withAnswer($answer));
    }

    /**
     * Runs one round: context lookup, specification, and generation unless questions remain.
     *
     * @param Session $session Session including the latest answer
     * @return Draft Generated result or questions for the user
     */
    private function run(Session $session): Draft
    {
        $trace = new Trace($session->spent);
        $input = $this->input($session);
        $context = $this->context($input, $trace);
        $left = $this->limits->clarifications - $session->clarifications;
        $plan = $this->specify($input, $context, $left, $trace);

        $missing = array_values(array_diff(
            array_unique(array_map(strtolower(...), $plan['missing_extensions'])),
            $this->platform->extensions,
        ));
        if ($missing !== []) {
            $session = $session->withRound([], $trace->summary($session->clarifications));

            return new Draft(
                $plan['specification'],
                [],
                $session,
                errors: ['The task requires PHP extensions that are not available: ' . implode(', ', $missing) . '.'],
                missingExtensions: $missing,
                metadata: $this->metadata($session, $trace),
            );
        }

        if ($plan['questions'] !== [] && $left > 0) {
            $session = $session->withRound($plan['questions'], $trace->summary($session->clarifications));

            return new Draft($plan['specification'], [], $session, metadata: $this->metadata($session, $trace));
        }

        $trace->questionsIgnored = count($plan['questions']);
        [$files, $errors] = $this->generate($input, $context, $plan['specification'], $trace);
        $session = $session->withRound([], $trace->summary($session->clarifications));

        return new Draft($plan['specification'], $files, $session, $errors, metadata: $this->metadata($session, $trace));
    }

    /**
     * Combines the request with clarification answers and checks its token budget.
     *
     * @param Session $session Current session
     * @return string Request text sent to context and generation stages
     * @throws InputTooLong When input exceeds the configured token limit
     */
    private function input(Session $session): string
    {
        $input = $session->input;
        if ($session->answers !== []) {
            $input .= "\n\nUser clarifications:\n" . implode("\n", $session->answers);
        }

        if ($this->tokenizer->count($input) > $this->limits->inputTokens) {
            throw new InputTooLong('The plugin request exceeds the configured input token limit.');
        }

        return $input;
    }

    /**
     * Retrieves and bounds reference context while recording its size and duration.
     *
     * @param string $input Request text including clarifications
     * @param Trace $trace Records of this round
     * @return string Context text used by the specification and generation stages
     */
    private function context(string $input, Trace $trace): string
    {
        $started = hrtime(true);
        $context = $this->context->get($input, $this->limits->contextTokens);
        $tokens = $this->tokenizer->count($context);
        $truncated = $tokens > $this->limits->contextTokens;
        if ($truncated) {
            $context = $this->tokenizer->truncate($context, $this->limits->contextTokens);
        }

        $trace->contextTokens = min($tokens, $this->limits->contextTokens);
        $trace->add('context', $started, ['context_tokens' => $trace->contextTokens, 'truncated' => $truncated]);

        return $context;
    }

    /**
     * Gets a specification, runs requested tools, and refines it with their results.
     *
     * @param string $input Request text including clarifications
     * @param string $context Bounded reference material
     * @param int $left Clarification rounds the user still has
     * @param Trace $trace Records of this round
     * @return array{
     *     specification: string,
     *     questions: string[],
     *     missing_extensions: string[],
     *     tool_calls: array<int, array{name: string, arguments: array<string, mixed>}>,
     * }
     * @throws InvalidResponse When a provider returns malformed protocol data
     */
    private function specify(string $input, string $context, int $left, Trace $trace): array
    {
        $tools = $this->limits->toolCalls > 0 ? $this->tools->all() : [];
        $data = [
            'input'               => $input,
            'context'             => $context,
            'platform'            => Protocol::platform($this->platform),
            'clarifications_left' => $left,
        ];
        if ($tools !== []) {
            $data['tools'] = array_map(fn (Tool $tool): array => [
                'name'        => $tool->name,
                'description' => $tool->description,
                'parameters'  => $tool->parameters,
            ], $tools);
            $data['limits'] = ['tool_calls' => $this->limits->toolCalls];
        }

        $plan = Protocol::plan($this->complete(Protocol::ANALYSIS, $data, 'analysis', $trace)->text);
        $calls = $tools === [] ? [] : $plan['tool_calls'];
        $trace->toolCallsLimited = count($calls) > $this->limits->toolCalls;

        $results = $this->tools($calls, $trace);
        if ($results === []) {
            return $plan;
        }

        return Protocol::plan($this->complete(Protocol::REFINEMENT, [
            'input'               => $input,
            'draft'               => $plan['specification'],
            'tool_results'        => $results,
            'platform'            => Protocol::platform($this->platform),
            'clarifications_left' => $left,
        ], 'refinement', $trace)->text);
    }

    /**
     * Runs the allowed tool calls and bounds each result; failures go back to the model as errors.
     *
     * @param array<int, array{name: string, arguments: array<string, mixed>}> $calls Requested tool calls
     * @param Trace $trace Records of this round
     * @return array<int, array{name: string, result?: string, error?: string}> Bounded tool responses
     */
    private function tools(array $calls, Trace $trace): array
    {
        $limit = $this->limits->toolResultTokens;
        $results = [];
        foreach (array_slice($calls, 0, $this->limits->toolCalls) as $call) {
            $started = hrtime(true);
            try {
                $key = 'result';
                $text = $this->tools->handle($call['name'], $call['arguments']);
            } catch (Exception $error) {
                $key = 'error';
                $text = $error->getMessage();
            }

            $tokens = $this->tokenizer->count($text);
            $results[] = ['name' => $call['name'], $key => $tokens > $limit ? $this->tokenizer->truncate($text, $limit) : $text];
            $trace->add('tool', $started, [
                'name'          => $call['name'],
                'failed'        => $key === 'error',
                'result_tokens' => min($tokens, $limit),
                'truncated'     => $tokens > $limit,
            ]);
        }

        return $results;
    }

    /**
     * Generates files, validates them, and retries while repairs and the token budget remain.
     * A malformed generator response counts as a failed attempt, not as a fatal error.
     *
     * @param string $input Request text including clarifications
     * @param string $context Bounded reference material
     * @param string $specification Final specification
     * @param Trace $trace Records of this round
     * @return array{array<string, string>, string[]} Last files and their remaining errors
     * @throws BudgetExceeded When the budget is spent before the first attempt
     */
    private function generate(string $input, string $context, string $specification, Trace $trace): array
    {
        $files = [];
        $errors = [];
        for ($attempt = 0; $attempt <= $this->limits->repairs; $attempt++) {
            if ($trace->total >= $this->limits->totalTokens) {
                if ($attempt === 0) {
                    throw new BudgetExceeded('The session has spent its total token budget before generation.');
                }

                $trace->budgetExceeded = true;
                break;
            }

            $trace->repairs = $attempt;
            $started = hrtime(true);
            try {
                $generation = $this->generator->generate(
                    new Brief($input, $context, $specification, $errors, $files, $this->platform),
                    $this->limits->outputTokens,
                );
            } catch (InvalidResponse $error) {
                $errors = [$error->getMessage()];
                $trace->add('generation', $started, ['error' => $error->getMessage()]);
                continue;
            }

            $files = $generation->files;
            $trace->add(
                'generation',
                $started,
                ['details' => $generation->metadata],
                $generation->inputTokens,
                $generation->outputTokens,
                $generation->providerCalls,
            );

            $started = hrtime(true);
            $errors = $this->validator->validate($files);
            $trace->add('validation', $started, ['errors' => count($errors)]);
            if ($errors === []) {
                break;
            }
        }

        return [$files, $errors];
    }

    /**
     * Sends one prompt within the session budget and records the provider's usage.
     *
     * @param string $system Stage instructions
     * @param array<string, mixed> $data Request data
     * @param string $step Step name for the trace
     * @param Trace $trace Records of this round
     * @throws BudgetExceeded When the session token budget is spent
     */
    private function complete(string $system, array $data, string $step, Trace $trace): Completion
    {
        if ($trace->total >= $this->limits->totalTokens) {
            throw new BudgetExceeded("The session has spent its total token budget before {$step}.");
        }

        $started = hrtime(true);
        $completion = $this->provider->complete(
            new Prompt($system, Protocol::encode($data), Protocol::SPECIFICATION_SCHEMA),
            $this->limits->outputTokens,
        );
        $trace->add(
            $step,
            $started,
            ['provider' => $completion->metadata],
            $completion->inputTokens,
            $completion->outputTokens,
            1,
        );

        return $completion;
    }

    /**
     * Builds result metadata: this round, its steps, the limits, and session totals.
     *
     * @param Session $session Session with this round's summary appended
     * @param Trace $trace Records of this round
     * @return array<string, mixed> Current metrics and clarification history
     */
    private function metadata(Session $session, Trace $trace): array
    {
        $history = $session->history;
        $sum = fn (string $key): int|float => array_sum(array_column($history, $key));

        return [
            ...$history[array_key_last($history)],
            'steps'          => $trace->steps,
            'limits'         => [
                'input_tokens'       => $this->limits->inputTokens,
                'context_tokens'     => $this->limits->contextTokens,
                'output_tokens'      => $this->limits->outputTokens,
                'tool_calls'         => $this->limits->toolCalls,
                'tool_result_tokens' => $this->limits->toolResultTokens,
                'clarifications'     => $this->limits->clarifications,
                'repairs'            => $this->limits->repairs,
                'total_tokens'       => $this->limits->totalTokens,
            ],
            'session_totals' => [
                'input_tokens'   => $sum('input_tokens'),
                'output_tokens'  => $sum('output_tokens'),
                'total_tokens'   => $sum('total_tokens'),
                'provider_calls' => $sum('provider_calls'),
                'tool_calls'     => $sum('tool_calls'),
                'repairs'        => $sum('repairs'),
                'clarifications' => $session->clarifications,
                'duration_ms'    => round($sum('duration_ms'), 3),
            ],
            'history'        => $history,
        ];
    }
}
