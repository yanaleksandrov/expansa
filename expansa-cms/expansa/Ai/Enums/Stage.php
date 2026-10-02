<?php

declare(strict_types=1);

namespace Expansa\Ai\Enums;

/**
 * Stage of a manager round, in execution order; the value names the step in metadata.
 */
enum Stage: string
{
    /**
     * Reference material is retrieved for the request.
     */
    case Context = 'context';

    /**
     * The model writes the first specification and may ask questions or request tools.
     */
    case Analysis = 'analysis';

    /**
     * One tool requested by the model runs.
     */
    case Tool = 'tool';

    /**
     * The model revises the specification with the tool results.
     */
    case Refinement = 'refinement';

    /**
     * The generator writes the files or repairs the previous attempt.
     */
    case Generation = 'generation';

    /**
     * The validator checks the generated files.
     */
    case Validation = 'validation';
}
