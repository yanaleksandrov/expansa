<?php

declare(strict_types=1);

namespace Expansa\Ai;

/**
 * A generated plugin draft for review: files, specification, and the status details needed by the caller.
 * The package never installs it; the application shows it to the user and installs it on approval.
 */
final class Draft
{
    /**
     * Whether files were generated and passed validation.
     *
     * @var bool
     */
    public bool $valid {
        get => $this->files !== [] && $this->errors === [];
    }

    /**
     * Questions that need user answers before code generation can continue.
     * Pass an answer with `session` to `Manager::clarify()` to resume.
     *
     * @var string[]
     */
    public array $questions {
        get => $this->session->questions;
    }

    /**
     * Stores the specification, generated files, and generation result.
     * Validation errors and usage details support later review.
     */
    public function __construct(

        /**
         * Finalized requirements used to generate the extension.
         * Keeps the model's specification alongside its generated files.
         */
        public readonly string $specification,

        /**
         * Generated extension files keyed by safe relative paths.
         * Values contain complete source contents.
         *
         * @var array<string, string>
         */
        public readonly array $files,

        /**
         * Request state to store and pass back to `Manager::clarify()`.
         */
        public readonly Session $session,

        /**
         * Validation errors remaining after repair attempts.
         * An empty list means no validation issues remain.
         *
         * @var string[]
         */
        public readonly array $errors = [],

        /**
         * PHP extensions the task needs but the platform lacks; no files are generated then.
         * Show them to the user: the extensions must be installed or the task changed.
         *
         * @var string[]
         */
        public readonly array $missingExtensions = [],

        /**
         * Token usage, timings, tool calls, limits, and provider details.
         * Includes per-round summaries when the user clarified the request.
         *
         * @var array<string, mixed>
         */
        public readonly array $metadata = [],
    ) {}
}
