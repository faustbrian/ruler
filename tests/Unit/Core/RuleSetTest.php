<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Core\RuleSet;
use Cline\Ruler\Core\RuleSetExecutionReport;
use Cline\Ruler\Enums\ConflictResolutionStrategy;
use Tests\Fixtures\TrueProposition;

describe('RuleSet', function (): void {
    describe('Happy Paths', function (): void {
        test('ruleset creation update and execution', function (): void {
            $context = new Context();
            $true = new TrueProposition();

            $executedActionA = false;
            $ruleA = new Rule($true, function () use (&$executedActionA): void {
                $executedActionA = true;
            });

            $executedActionB = false;
            $ruleB = new Rule($true, function () use (&$executedActionB): void {
                $executedActionB = true;
            });

            $executedActionC = false;
            $ruleC = new Rule($true, function () use (&$executedActionC): void {
                $executedActionC = true;
            });

            $ruleset = new RuleSet([$ruleA]);

            $ruleset->executeRules($context);

            expect($executedActionA)->toBeTrue();
            expect($executedActionB)->toBeFalse();
            expect($executedActionC)->toBeFalse();

            $ruleset->addRule($ruleC);
            $ruleset->executeRules($context);

            expect($executedActionA)->toBeTrue();
            expect($executedActionB)->toBeFalse();
            expect($executedActionC)->toBeTrue();
        });

        test('executes rules in priority order when configured', function (): void {
            $context = new Context();
            $true = new TrueProposition();
            $executionOrder = [];

            $lowPriority = new Rule(
                $true,
                function () use (&$executionOrder): void {
                    $executionOrder[] = 'low';
                },
                'low',
                'Low priority',
                10,
            );

            $highPriority = new Rule(
                $true,
                function () use (&$executionOrder): void {
                    $executionOrder[] = 'high';
                },
                'high',
                'High priority',
                100,
            );

            $ruleset = new RuleSet(
                [$lowPriority, $highPriority],
                ConflictResolutionStrategy::PriorityHighFirst,
            );

            $ruleset->executeRules($context);

            expect($executionOrder)->toBe(['high', 'low']);
        });

        test('uses insertion order when priorities tie', function (): void {
            $context = new Context();
            $true = new TrueProposition();
            $executionOrder = [];

            $first = new Rule(
                $true,
                function () use (&$executionOrder): void {
                    $executionOrder[] = 'first';
                },
                'first',
                'First',
                5,
            );

            $second = new Rule(
                $true,
                function () use (&$executionOrder): void {
                    $executionOrder[] = 'second';
                },
                'second',
                'Second',
                5,
            );

            $ruleset = new RuleSet(
                [$first, $second],
                ConflictResolutionStrategy::PriorityHighFirst,
            );

            $ruleset->executeRules($context);

            expect($executionOrder)->toBe(['first', 'second']);
        });

        test('can switch conflict strategy at runtime', function (): void {
            $context = new Context();
            $true = new TrueProposition();
            $executionOrder = [];

            $high = new Rule(
                $true,
                function () use (&$executionOrder): void {
                    $executionOrder[] = 'high';
                },
                'high',
                'High',
                100,
            );

            $low = new Rule(
                $true,
                function () use (&$executionOrder): void {
                    $executionOrder[] = 'low';
                },
                'low',
                'Low',
                1,
            );

            $ruleset = new RuleSet([$high, $low]);
            $ruleset
                ->setConflictResolutionStrategy(ConflictResolutionStrategy::PriorityLowFirst)
                ->executeRules($context);

            expect($ruleset->getConflictResolutionStrategy())
                ->toBe(ConflictResolutionStrategy::PriorityLowFirst);
            expect($executionOrder)->toBe(['low', 'high']);
        });

        test('forward chaining activates downstream rules in later cycles', function (): void {
            $context = new Context([
                'seeded' => false,
                'eligible' => false,
                'approved' => false,
            ]);

            $rb = new \Cline\Ruler\Builder\RuleBuilder();
            $executionOrder = [];

            $approve = new Rule(
                $rb['eligible']->sameAs(true),
                function () use ($context, &$executionOrder): void {
                    $executionOrder[] = 'approve';
                    $context['approved'] = true;
                },
                'approve',
                'Approval Rule',
                20,
            );

            $seed = new Rule(
                $rb['seeded']->sameAs(false),
                function () use ($context, &$executionOrder): void {
                    $executionOrder[] = 'seed';
                    $context['seeded'] = true;
                    $context['eligible'] = true;
                },
                'seed',
                'Seed Rule',
                10,
            );

            // Approval is evaluated first and fails in cycle 1.
            $ruleset = new RuleSet([$approve, $seed]);

            expect($ruleset->executeForwardChaining($context))->toBe(2);
            expect($context['approved'])->toBeTrue();
            expect($executionOrder)->toBe(['seed', 'approve']);
        });

        test('forward chaining does not re-fire rules by default', function (): void {
            $context = new Context(['counter' => 0]);
            $rb = new \Cline\Ruler\Builder\RuleBuilder();

            $rule = new Rule(
                $rb['counter']->greaterThanOrEqualTo(0),
                function () use ($context): void {
                    $context['counter'] = $context['counter'] + 1;
                },
                'counter',
                'Counter Rule',
            );

            $ruleset = new RuleSet([$rule]);

            expect($ruleset->executeForwardChaining($context))->toBe(1);
            expect($context['counter'])->toBe(1);
        });

        test('executeRulesWithReport returns matched and action counts', function (): void {
            $context = new Context(['flag' => true, 'disabledFlag' => true]);
            $rb = new \Cline\Ruler\Builder\RuleBuilder();
            $executed = [];

            $ruleA = new Rule(
                $rb['flag']->sameAs(true),
                function () use (&$executed): void {
                    $executed[] = 'A';
                },
                'A',
                'Rule A',
                10,
            );

            $ruleB = new Rule(
                $rb['flag']->sameAs(false),
                function () use (&$executed): void {
                    $executed[] = 'B';
                },
                'B',
                'Rule B',
                5,
            );

            $ruleDisabled = new Rule(
                $rb['disabledFlag']->sameAs(true),
                function () use (&$executed): void {
                    $executed[] = 'DISABLED';
                },
                'D',
                'Disabled Rule',
                100,
                false,
            );

            $report = (new RuleSet([$ruleA, $ruleB, $ruleDisabled]))
                ->executeRulesWithReport($context);

            expect($report)->toBeInstanceOf(RuleSetExecutionReport::class);
            expect($report->getMatchedCount())->toBe(1);
            expect($report->getActionExecutionCount())->toBe(1);
            expect($executed)->toBe(['A']);
            expect($report->getResults())->toHaveCount(3);
        });
    });

    describe('Sad Paths', function (): void {
        test('forward chaining throws when max cycles is exceeded', function (): void {
            $context = new Context();
            $true = new TrueProposition();

            $rule = new Rule(
                $true,
                static function (): void {},
                'loop',
                'Loop Rule',
            );

            $ruleset = new RuleSet([$rule]);

            expect(fn (): int => $ruleset->executeForwardChaining($context, 2, true))
                ->toThrow(RuntimeException::class);
        });
    });
});
