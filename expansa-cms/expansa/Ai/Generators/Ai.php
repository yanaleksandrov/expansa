<?php

declare(strict_types=1);

namespace Expansa\Ai\Generators;

use Expansa\Ai\Brief;
use Expansa\Ai\Contracts\Generator;
use Expansa\Ai\Contracts\Provider;
use Expansa\Ai\Exceptions\InvalidResponse;
use Expansa\Ai\Generation;
use Expansa\Ai\Internal\Protocol;
use Expansa\Ai\Prompt;

/**
 * Generates an extension file map through a configured AI provider.
 */
final class Ai implements Generator
{
    /**
     * Stores the provider used for code generation.
     */
    public function __construct(

        /**
         * AI service for code, which may be stronger than the specification provider.
         */
        public readonly Provider $provider,
    ) {}

    /**
     * Requests an extension file map from the provider.
     * Repair attempts send the previous files with their validation errors.
     *
     * @param Brief $brief Request, context, specification, and previous attempt
     * @param int $maxOutputTokens Maximum tokens requested from the provider
     * @return Generation Files and usage reported by the provider
     * @throws InvalidResponse When the provider response is not a file map
     */
    public function generate(Brief $brief, int $maxOutputTokens): Generation
    {
        $data = [
            'request'       => $brief->request,
            'cms_context'   => $brief->context,
            'specification' => $brief->specification,
            'platform'      => Protocol::platform($brief->platform),
        ];
        if ($brief->errors !== []) {
            $data['validation_errors'] = $brief->errors;
            $data['previous_files'] = $brief->files;
        }

        $completion = $this->provider->complete(
            new Prompt(Protocol::GENERATION, Protocol::encode($data), Protocol::FILES_SCHEMA),
            $maxOutputTokens,
        );

        return new Generation(
            Protocol::files($completion->text),
            $completion->inputTokens,
            $completion->outputTokens,
            1,
            $completion->metadata,
        );
    }
}
