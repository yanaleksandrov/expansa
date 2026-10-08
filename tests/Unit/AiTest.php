<?php

declare(strict_types=1);

namespace Tests\Unit;

use Expansa\Ai\Brief;
use Expansa\Ai\Completion;
use Expansa\Ai\Contracts\Context;
use Expansa\Ai\Contracts\Generator;
use Expansa\Ai\Contracts\Provider;
use Expansa\Ai\Contracts\Tool;
use Expansa\Ai\Generation;
use Expansa\Ai\Limits;
use Expansa\Ai\Manager;
use Expansa\Ai\Prompt;
use Expansa\Ai\Tools\Registry;
use Expansa\Ai\Validators\Php;
use PHPUnit\Framework\TestCase;

final class AiTest extends TestCase
{
    public function testGeneratesAndValidatesADraft(): void
    {
        $extension = new Manager(
            $this->provider(['{"specification":"Use the welcome hook."}']),
            $this->context(),
            $this->generator([['Plugin.php' => '<?php declare(strict_types=1);']]),
            new Php(),
        )->create('Add a welcome message');

        self::assertTrue($extension->valid);
        self::assertSame(['Plugin.php' => '<?php declare(strict_types=1);'], $extension->files);
        self::assertSame('Use the welcome hook.', $extension->specification);
    }

    public function testRetriesGenerationWithValidationErrors(): void
    {
        $generator = $this->generator([['Plugin.php' => '<?php function {'], ['Plugin.php' => '<?php return true;']]);

        $extension = new Manager(
            $this->provider(['{"specification":"A valid extension."}']),
            $this->context(),
            $generator,
            new Php(),
        )->create('Add a feature');

        self::assertTrue($extension->valid);
        self::assertCount(2, $generator->briefs);
        self::assertSame(['Plugin.php' => '<?php function {'], $generator->briefs[1]->files);
    }

    public function testRunsRequestedToolsDuringAnalysis(): void
    {
        $tool = new class implements Tool {
            public string $name = 'hooks';
            public string $description = 'Lists CMS hooks';
            public array $parameters = ['type' => 'object'];

            public function handle(array $arguments): string
            {
                return 'welcome';
            }
        };

        $extension = new Manager(
            $this->provider([
                '{"specification":"Draft","tool_calls":[{"name":"hooks","arguments":{}}]}',
                '{"specification":"Final specification"}',
            ]),
            $this->context(),
            $this->generator([]),
            new Php(),
            new Registry([$tool]),
        )->create('Use the right hook');

        self::assertSame('Final specification', $extension->specification);
        self::assertSame(1, $extension->metadata['tool_calls']);
    }

    public function testClarifiesMissingDetailsWithinConfiguredLimits(): void
    {
        $context = new class implements Context {
            /** @var array<int, array{string, int}> */
            public array $inputs = [];

            public function get(string $input, int $maxTokens): string
            {
                $this->inputs[] = [$input, $maxTokens];

                return str_repeat('x', $maxTokens + 8);
            }
        };
        $manager = new Manager(
            $this->provider([
                '{"specification":"Draft","questions":["Which hook should be used?"]}',
                '{"specification":"Use the welcome hook."}',
            ]),
            $context,
            $this->generator([]),
            new Php(),
            new Registry(),
            new Limits(contextTokens: 32, outputTokens: 50, clarifications: 1, repairs: 0),
        );

        $pending = $manager->create('Add a welcome extension');
        self::assertSame(['Which hook should be used?'], $pending->questions);
        self::assertFalse($pending->valid);

        $extension = $manager->clarify($pending->session, 'Use the welcome hook');

        self::assertTrue($extension->valid);
        self::assertStringContainsString('Use the welcome hook', $context->inputs[1][0]);
        self::assertSame(32, $extension->metadata['history'][1]['context_tokens']);
        self::assertSame(1, $extension->metadata['clarifications']);
        self::assertSame(27, $extension->metadata['session_totals']['input_tokens']);
        self::assertSame(13, $extension->metadata['session_totals']['output_tokens']);
        self::assertCount(2, $extension->metadata['history']);
    }

    /**
     * Creates a provider that returns the responses in order.
     *
     * @param string[] $responses Response texts
     */
    private function provider(array $responses): Provider
    {
        return new class ($responses) implements Provider {
            /**
             * @param string[] $responses Response texts
             */
            public function __construct(private array $responses) {}

            public function complete(Prompt $prompt, int $maxOutputTokens): Completion
            {
                return new Completion(array_shift($this->responses) ?? '{}', 10, 5);
            }
        };
    }

    /**
     * Creates a context source with a fixed hook list.
     */
    private function context(): Context
    {
        return new class implements Context {
            public function get(string $input, int $maxTokens): string
            {
                return 'Hooks: welcome';
            }
        };
    }

    /**
     * Creates a generator that returns the file maps in order and records briefs.
     *
     * @param array<int, array<string, string>> $results File maps
     */
    private function generator(array $results): Generator
    {
        return new class ($results) implements Generator {
            /** @var Brief[] */
            public array $briefs = [];

            /**
             * @param array<int, array<string, string>> $results File maps
             */
            public function __construct(private array $results) {}

            public function generate(Brief $brief, int $maxOutputTokens): Generation
            {
                $this->briefs[] = $brief;

                return new Generation(array_shift($this->results) ?? ['Plugin.php' => '<?php return true;'], 7, 3, 1);
            }
        };
    }
}
