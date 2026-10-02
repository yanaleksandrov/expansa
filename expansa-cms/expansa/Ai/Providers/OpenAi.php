<?php

declare(strict_types=1);

namespace Expansa\Ai\Providers;

use Expansa\Ai\Completion;
use Expansa\Ai\Contracts\Provider;
use Expansa\Ai\Exceptions\InvalidResponse;
use Expansa\Ai\Exceptions\RequestFailed;
use Expansa\Ai\Prompt;

/**
 * Provider for any service with the OpenAI Chat Completions API: OpenAI, Google Gemini, OpenRouter, Groq, Ollama.
 * The free Gemini tier works with `https://generativelanguage.googleapis.com/v1beta/openai/` and a Flash model,
 * OpenRouter with `https://openrouter.ai/api/v1/` and a model id ending in `:free`.
 */
final class OpenAi implements Provider
{
    /**
     * Stores the service address, credentials, and request options.
     */
    public function __construct(

        /**
         * API base URL with a trailing slash; `chat/completions` is appended.
         */
        public string $url {
            set => rtrim($value, '/') . '/';
        },

        /**
         * Model id of the service.
         */
        public readonly string $model,

        /**
         * API key sent as a bearer token; empty for local services such as Ollama.
         */
        #[\SensitiveParameter]
        private readonly string $key = '',

        /**
         * Whether the service supports `json_schema` structured output; otherwise the schema goes into
         * the instructions and the response is requested as `json_object`.
         */
        public readonly bool $schemas = true,

        /**
         * Seconds to wait for a response; code generation of a large plugin takes minutes.
         */
        public readonly int $timeout = 300,

        /**
         * Extra request fields, e.g. `['reasoning_effort' => 'low', 'temperature' => 0.2]`.
         *
         * @var array<string, mixed>
         */
        public readonly array $options = [],
    ) {}

    /**
     * Sends the prompt as system and user messages and returns the first choice.
     *
     * @param Prompt $prompt System instructions, request data, and response schema
     * @param int $maxOutputTokens Maximum requested response size
     * @return Completion Text, token usage, model, and finish reason
     * @throws RequestFailed When the service can not be reached or answers with an error
     * @throws InvalidResponse When the response has no message text
     */
    public function complete(Prompt $prompt, int $maxOutputTokens): Completion
    {
        $system = $prompt->system;
        $body = ['model' => $this->model, 'max_tokens' => $maxOutputTokens, ...$this->options];
        if ($prompt->schema !== null && $this->schemas) {
            $body['response_format'] = [
                'type'        => 'json_schema',
                'json_schema' => ['name' => 'response', 'schema' => $prompt->schema],
            ];
        } elseif ($prompt->schema !== null) {
            $system .= "\n\nJSON Schema of the response:\n" . json_encode($prompt->schema, JSON_UNESCAPED_SLASHES);
            $body['response_format'] = ['type' => 'json_object'];
        }

        $body['messages'] = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt->user],
        ];

        $data = $this->send($body);
        $choice = $data['choices'][0] ?? null;
        $text = $choice['message']['content'] ?? null;
        if (! is_string($text)) {
            throw new InvalidResponse('The AI service returned no message text.');
        }

        // thinking models such as Gemini leave reasoning out of completion_tokens but count it in total_tokens
        $input = (int) ($data['usage']['prompt_tokens'] ?? 0);
        $output = max((int) ($data['usage']['completion_tokens'] ?? 0), (int) ($data['usage']['total_tokens'] ?? 0) - $input);

        return new Completion(
            $text,
            $input,
            $output,
            [
                'model'         => $data['model'] ?? $this->model,
                'id'            => $data['id'] ?? '',
                'finish_reason' => $choice['finish_reason'] ?? '',
            ],
        );
    }

    /**
     * Posts the request body and decodes the JSON response.
     *
     * @param array<string, mixed> $body Request fields
     * @return array<mixed> Decoded response
     * @throws RequestFailed When the request fails or the status is not 2xx
     */
    private function send(array $body): array
    {
        $headers = ['Content-Type: application/json'];
        if ($this->key !== '') {
            $headers[] = "Authorization: Bearer {$this->key}";
        }

        $curl = curl_init($this->url . 'chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => $this->timeout,
            // the system certificate store, so Windows builds of PHP without curl.cainfo verify HTTPS too
            CURLOPT_SSL_OPTIONS    => CURLSSLOPT_NATIVE_CA,
        ]);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);

        if (! is_string($response)) {
            throw new RequestFailed("The AI service is unreachable: {$error}");
        }

        $data = json_decode($response, true);
        if ($status < 200 || $status >= 300 || ! is_array($data)) {
            // services put the reason into error.message, OpenRouter's upstream errors into a list
            $reason = $data['error']['message'] ?? $data[0]['error']['message'] ?? mb_substr($response, 0, 300);

            throw new RequestFailed("The AI service answered with status {$status}: {$reason}");
        }

        return $data;
    }
}
