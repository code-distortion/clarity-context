<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack;

use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Context;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionCaughtMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\SimulateControlPackage;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

/**
 * Test the CallStack class's generation of MetaGroup objects.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CallStackMetaGroupUnitTest extends LaravelTestCase
{
    /** @var callable|null A temporary storage spot for the callable that's passed to prime(). */
    private static $callable;

    /** @var Context|null A temporary storage spot for the context that execute() generates. */
    private static ?Context $resultingContext;



    /**
     * Test that a CallStack generates the desired MetaGroup objects.
     *
     * @test
     * @dataProvider buildMetaGroupsDataProvider
     *
     * @param callable        $callable The callable to run, that sets up the situation.
     * @param array<string[]> $expected The expected groupings of Meta objects.
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    #[Test]
    #[DataProvider('buildMetaGroupsDataProvider')]
    public static function test_the_building_of_meta_groups(
        callable $callable,
        array $expected,
    ): void {

        $callback = function (Context $context) use ($expected) {

            $callStackMetaGroups = $context->getCallStack()->getMetaGroups();
            $stackTraceMetaGroups = $context->getStackTrace()->getMetaGroups();
            self::assertIsArray($callStackMetaGroups);
            self::assertIsArray($stackTraceMetaGroups);

            $found = [];
            foreach ($callStackMetaGroups as $groupIndex => $metaGroup) {
                foreach ($metaGroup->getMeta() as $metaIndex => $meta) {
                    $found[$groupIndex][$metaIndex] = get_class($meta);
                }
            }
            if ($expected !== $found) {
                throw new Exception('The generated callstack meta-groups were not expected');
            }
            self::assertSame($expected, $found);

            $found = [];
            foreach ($stackTraceMetaGroups as $groupIndex => $metaGroup) {
                foreach ($metaGroup->getMeta() as $metaIndex => $meta) {
                    $found[$groupIndex][$metaIndex] = get_class($meta);
                }
            }
            $expectedReverse = array_reverse($expected);
            if ($expectedReverse !== $found) {
                throw new Exception('The generated stack trace meta-groups were not expected');
            }
            self::assertSame($expectedReverse, $found);
        };



        // reset state
        self::$callable = null;
        self::$resultingContext = null;

        $callable();

        /** @var Context $resultingContext $callable() will set it to be a Context object. */
        $resultingContext = self::$resultingContext;

        $callback($resultingContext);
    }

    /**
     * DataProvider for test_the_building_of_meta_groups().
     *
     * @return array<array<callable|array<string[]>>>
     */
    public static function buildMetaGroupsDataProvider(): array
    {
        $prime = function (callable $callable) {
            self::$callable = $callable;
        };

        $execute = function () {

            SimulateControlPackage::pushControlCallMetaHere(1, [], 1);

            /** @var callable $callable It is callable by this stage, because $prime has been run. */
            $callable = self::$callable;
            $exception = null;
            try {
                $callable();
            } catch (Throwable $e) {
                $exception = $e;
            }

            /** @var Exception $exception It is an Exception by this stage, the primed closures all throw an exception. */
            self::$resultingContext = SimulateControlPackage::buildContext(1, $exception);
        };



        $return = [];



        // exception thrown in an APPLICATION frame



        // no context - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                $prime(fn() => throw new Exception()); $execute(); // phpcs:ignore
//                Control::prime(fn() => throw new Exception())->callback($callback)->execute();
            },
            [
                [
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                    ExceptionThrownMeta::class
                ],
            ],
        ];

        // with context - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                $prime(fn() => throw new Exception()); $execute(); // phpcs:ignore

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)->execute();
            },
            [
                [
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                    ExceptionThrownMeta::class,
                ],
            ],
        ];

        // with 2 x context (same line) - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                // two contexts on the same line - the second one is the one that's picked up
                Clarity::context('hello'); Clarity::context(['a' => 'b']); // phpcs:ignore
                $prime(fn() => throw new Exception()); $execute(); // phpcs:ignore

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)->execute();
            },
            [
                [
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                    ExceptionThrownMeta::class,
                ],
            ],
        ];

        // with 2 x context - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                Clarity::context(['a' => 'b']);
                $prime(fn() => throw new Exception()); $execute(); // phpcs:ignore

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)->execute();
            },
            [
                [
                    ContextMeta::class,
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                    ExceptionThrownMeta::class,
                ],
            ],
        ];

        // with 2 x context (with gap) - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                // blank line on purpose
                Clarity::context(['a' => 'b']);
                $prime(fn() => throw new Exception()); $execute(); // phpcs:ignore

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)->execute();
            },
            [
                [ContextMeta::class],
                [
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                    ExceptionThrownMeta::class,
                ],
            ],
        ];







        // no context - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                $prime(fn() => throw new Exception());
                $execute();

//                Control::prime(fn() => throw new Exception())->callback($callback)
//                    ->execute();
            },
            [
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with context - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                $prime(fn() => throw new Exception());
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (same line) - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                // two contexts on the same line - the second one is the one that's picked up
                Clarity::context('hello'); Clarity::context(['a' => 'b']); // phpcs:ignore
                $prime(fn() => throw new Exception());
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                Clarity::context(['a' => 'b']);
                $prime(fn() => throw new Exception());
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class, ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (with gap) - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                // blank line on purpose
                Clarity::context(['a' => 'b']);
                $prime(fn() => throw new Exception());
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];







        // no context - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                $prime(fn() => throw new Exception());
                // blank line on purpose
                $execute();

//                Control::prime(fn() => throw new Exception())
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with context - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                $prime(fn() => throw new Exception());
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (same line) - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                // two contexts on the same line - the second one is the one that's picked up
                Clarity::context('hello'); Clarity::context(['a' => 'b']); // phpcs:ignore
                $prime(fn() => throw new Exception());
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                Clarity::context(['a' => 'b']);
                $prime(fn() => throw new Exception());
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class, ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (with gap) - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                // blank line on purpose
                Clarity::context(['a' => 'b']);
                $prime(fn() => throw new Exception());
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw new Exception())
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class, ExceptionThrownMeta::class],
            ],
        ];







        // exception thrown in a VENDOR frame



        // no context - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                $prime(fn() => app()->make(NonExistantClass::class)); $execute(); /** @phpstan-ignore-line */ // phpcs:ignore
// phpcs:ignore Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)->execute();
            },
            [
                [
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                ],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with context - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                $prime(fn() => app()->make(NonExistantClass::class)); $execute(); /** @phpstan-ignore-line */ // phpcs:ignore

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)->execute();
            },
            [
                [
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                ],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (same line) - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                // two contexts on the same line - the second one is the one that's picked up
                Clarity::context('hello'); Clarity::context(['a' => 'b']); // phpcs:ignore
                $prime(fn() => app()->make(NonExistantClass::class)); $execute(); /** @phpstan-ignore-line */ // phpcs:ignore

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)->execute();
            },
            [
                [
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                ],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                Clarity::context(['a' => 'b']);
                $prime(fn() => app()->make(NonExistantClass::class)); $execute(); /** @phpstan-ignore-line */ // phpcs:ignore

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)->execute();
            },
            [
                [
                    ContextMeta::class,
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                ],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (with gap) - simulate "Control" chaining on the same line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                // blank line on purpose
                Clarity::context(['a' => 'b']);
                $prime(fn() => app()->make(NonExistantClass::class)); $execute(); /** @phpstan-ignore-line */ // phpcs:ignore

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)->execute();
            },
            [
                [ContextMeta::class],
                [
                    ContextMeta::class,
                    CallMeta::class,
                    ExceptionCaughtMeta::class,
                    LastApplicationFrameMeta::class,
                ],
                [ExceptionThrownMeta::class],
            ],
        ];







        // no context - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                $execute();

// phpcs:ignore Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)
//                    ->execute();
            },
            [
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with context - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                $execute();

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (same line) - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                // two contexts on the same line - the second one is the one that's picked up
                Clarity::context('hello'); Clarity::context(['a' => 'b']); // phpcs:ignore
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                $execute();

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                Clarity::context(['a' => 'b']);
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                $execute();

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class, ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (with gap) - prime() then execute() on the next line
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                // blank line on purpose
                Clarity::context(['a' => 'b']);
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                $execute();

//                Clarity::context('hello');
// phpcs:ignore   Control::prime(fn() => throw app()->make(NonExistantClass::class))->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];







        // no context - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                // blank line on purpose
                $execute();

//                Control::prime(fn() => throw app()->make(NonExistantClass::class))
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with context - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw app()->make(NonExistantClass::class))
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (same line) - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                // two contexts on the same line - the second one is the one that's picked up
                Clarity::context('hello'); Clarity::context(['a' => 'b']); // phpcs:ignore
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw app()->make(NonExistantClass::class))
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                Clarity::context(['a' => 'b']);
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw app()->make(NonExistantClass::class))
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class, ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        // with 2 x context (with gap) - prime() then execute() (with gap)
        $return[] = [
            function () use ($prime, $execute) {
                Clarity::context('hello');
                // blank line on purpose
                Clarity::context(['a' => 'b']);
                $prime(fn() => app()->make(NonExistantClass::class)); /** @phpstan-ignore-line */
                // blank line on purpose
                $execute();

//                Clarity::context('hello');
//                Control::prime(fn() => throw app()->make(NonExistantClass::class))
//                    ->callback($callback)
//                    ->execute();
            },
            [
                [ContextMeta::class],
                [ContextMeta::class],
                [CallMeta::class, ExceptionCaughtMeta::class],
                [LastApplicationFrameMeta::class],
                [ExceptionThrownMeta::class],
            ],
        ];

        return $return;
    }
}
