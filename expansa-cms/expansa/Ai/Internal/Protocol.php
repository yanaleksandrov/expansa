<?php

declare(strict_types=1);

namespace Expansa\Ai\Internal;

use Expansa\Ai\Exceptions\InvalidResponse;
use Expansa\Ai\Platform;

/**
 * Prompts, response schemas, and response parsing shared by the manager and the built-in generator.
 *
 * @internal
 */
final class Protocol
{
    /**
     * Flags for prompt data: invalid UTF-8 from users, context, or tools must not break encoding.
     */
    private const int ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE;

    /**
     * Instructions for the first specification call.
     */
    public const string ANALYSIS = 'You specify CMS extensions. Read the user input and CMS context, then return JSON '
        . 'matching the schema. "message" tells the user in one or two sentences, in the language of the input, '
        . 'what you understood and will do. "specification" states the behavior and acceptance criteria. '
        . 'Put a question into "questions" only when a missing detail blocks the specification and "clarifications_left" is above zero; '
        . 'otherwise choose a reasonable default and write it into the specification. Request "tool_calls" only for '
        . 'the listed tools, at most "limits.tool_calls", with arguments matching each tool\'s parameters. Generated '
        . 'code may use only the PHP version and extensions in "platform". When the task cannot be solved without an '
        . 'extension missing there, list it in "missing_extensions" and ask no questions.';

    /**
     * Instructions for the specification call that follows tool results.
     */
    public const string REFINEMENT = 'You finalize a CMS extension specification. Revise the draft using the tool '
        . 'results (an entry with "error" means the tool failed) and return JSON matching the schema with an empty '
        . '"tool_calls" and a "message" telling the user in one sentence, in the language of the input, what the '
        . 'results changed. Ask questions only under the same rule as before: a blocking detail and '
        . '"clarifications_left" above zero. Report extensions missing from "platform" in "missing_extensions".';

    /**
     * Instructions for code generation and repair.
     */
    public const string GENERATION = 'You write CMS extensions. Implement the specification as complete PHP source '
        . 'files with PHPUnit tests under tests/. Use only the PHP version and extensions in "platform". '
        . 'Use relative paths of letters, digits, ".", "_", "-" and "/". When "validation_errors" is present, '
        . 'fix them in "previous_files" and return the complete corrected set. "message" tells the user in one or '
        . 'two sentences, in the language of the request, what the files do or what you fixed. Return JSON matching '
        . 'the schema.';

    /**
     * Response schema of the analysis and refinement calls.
     *
     * @var array<string, mixed>
     */
    public const array SPECIFICATION_SCHEMA = [
        'type'       => 'object',
        'properties' => [
            'message'            => ['type' => 'string'],
            'specification'      => ['type' => 'string'],
            'questions'          => ['type' => 'array', 'items' => ['type' => 'string']],
            'missing_extensions' => ['type' => 'array', 'items' => ['type' => 'string']],
            'tool_calls'         => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => ['name' => ['type' => 'string'], 'arguments' => ['type' => 'object']],
                    'required'   => ['name', 'arguments'],
                ],
            ],
        ],
        'required'   => ['message', 'specification', 'questions', 'missing_extensions', 'tool_calls'],
    ];

    /**
     * Response schema of the generation call.
     *
     * @var array<string, mixed>
     */
    public const array FILES_SCHEMA = [
        'type'       => 'object',
        'properties' => [
            'message' => ['type' => 'string'],
            'files'   => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => ['path' => ['type' => 'string'], 'content' => ['type' => 'string']],
                    'required'   => ['path', 'content'],
                ],
            ],
        ],
        'required'   => ['message', 'files'],
    ];

    /**
     * Encodes prompt data as JSON.
     *
     * @param array<string, mixed> $data Prompt fields
     */
    public static function encode(array $data): string
    {
        return json_encode($data, self::ENCODE_FLAGS);
    }

    /**
     * Describes the platform for prompts.
     *
     * @param Platform $platform PHP version and available extensions
     * @return array{php: string, extensions: string[]}
     */
    public static function platform(Platform $platform): array
    {
        return ['php' => $platform->version, 'extensions' => $platform->extensions];
    }

    /**
     * Parses an analysis or refinement response.
     *
     * @param string $text Model response
     * @return array{
     *     message: string,
     *     specification: string,
     *     questions: string[],
     *     missing_extensions: string[],
     *     tool_calls: array<int, array{name: string, arguments: array<string, mixed>}>,
     * }
     * @throws InvalidResponse When fields do not match the protocol
     */
    public static function plan(string $text): array
    {
        $data = self::decode($text);
        if (! is_string($data['specification'] ?? null)) {
            throw new InvalidResponse('The AI provider must return a JSON specification string.');
        }

        $calls = $data['tool_calls'] ?? [];
        if (! is_array($calls)) {
            throw new InvalidResponse('The tool_calls field must be an array.');
        }

        $toolCalls = [];
        foreach ($calls as $call) {
            if (! is_array($call) || ! is_string($call['name'] ?? null) || ! is_array($call['arguments'] ?? null)) {
                throw new InvalidResponse('Each tool call must contain a name and arguments object.');
            }
            if (! array_all(array_keys($call['arguments']), fn (mixed $key): bool => is_string($key))) {
                throw new InvalidResponse('Tool argument names must be strings.');
            }

            $toolCalls[] = ['name' => $call['name'], 'arguments' => $call['arguments']];
        }

        return [
            'message'            => self::message($data),
            'specification'      => $data['specification'],
            'questions'          => self::strings($data, 'questions'),
            'missing_extensions' => self::strings($data, 'missing_extensions'),
            'tool_calls'         => $toolCalls,
        ];
    }

    /**
     * Reads an optional list of strings, trimmed and without empty entries.
     *
     * @param array<mixed> $data Decoded response
     * @param string $field Field name
     * @return string[]
     * @throws InvalidResponse When the field is not an array of strings
     */
    private static function strings(array $data, string $field): array
    {
        $values = $data[$field] ?? [];
        if (! is_array($values) || ! array_all($values, fn (mixed $value): bool => is_string($value))) {
            throw new InvalidResponse("The {$field} field must be an array of strings.");
        }

        return array_values(array_filter(array_map(trim(...), $values), strlen(...)));
    }

    /**
     * Reads the optional message for the user.
     *
     * @param array<mixed> $data Decoded response
     * @throws InvalidResponse When the message is not a string
     */
    private static function message(array $data): string
    {
        $message = $data['message'] ?? '';
        if (! is_string($message)) {
            throw new InvalidResponse('The message field must be a string.');
        }

        return trim($message);
    }

    /**
     * Parses a generation response into a file map and the message for the user.
     *
     * @param string $text Model response
     * @return array{files: array<string, string>, message: string} Relative paths mapped to source
     * @throws InvalidResponse When the response is not a non-empty file list
     */
    public static function generation(string $text): array
    {
        $data = self::decode($text);
        $entries = $data['files'] ?? null;
        if (! is_array($entries) || $entries === []) {
            throw new InvalidResponse('The AI provider must return a non-empty "files" list.');
        }

        $files = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['path'] ?? null) || ! is_string($entry['content'] ?? null)) {
                throw new InvalidResponse('Each generated file must contain string "path" and "content" fields.');
            }
            if (isset($files[$entry['path']])) {
                throw new InvalidResponse("The generated file {$entry['path']} is listed twice.");
            }

            $files[$entry['path']] = $entry['content'];
        }

        return ['files' => $files, 'message' => self::message($data)];
    }

    /**
     * Decodes a JSON object, accepting the Markdown code fence models often add.
     *
     * @return array<mixed>
     * @throws InvalidResponse When the text is not a JSON object
     */
    private static function decode(string $text): array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('~^```[A-Za-z]*\s*|\s*```$~', '', $text) ?? $text;
        }

        $data = json_decode($text, true);
        if (! is_array($data) || array_is_list($data) && $data !== []) {
            throw new InvalidResponse('The AI provider must return a JSON object.');
        }

        return $data;
    }
}
