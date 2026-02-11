<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
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
            $ruleA = new Rule($true, function ($context) use (&$executedActionA): void {
                $executedActionA = true;
            }, 'rule-a');

            $executedActionB = false;
            $ruleB = new Rule($true, function ($context) use (&$executedActionB): void {
                $executedActionB = true;
            }, 'rule-b');

            $executedActionC = false;
            $ruleC = new Rule($true, function ($context) use (&$executedActionC): void {
                $executedActionC = true;
            }, 'rule-c');

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
                function ($context) use (&$executionOrder): void {
                    $executionOrder[] = 'low';
                },
                'low',
                'Low priority',
                10,
            );

            $highPriority = new Rule(
                $true,
                function ($context) use (&$executionOrder): void {
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
                function ($context) use (&$executionOrder): void {
                    $executionOrder[] = 'first';
                },
                'first',
                'First',
                5,
            );

            $second = new Rule(
                $true,
                function ($context) use (&$executionOrder): void {
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
                function ($context) use (&$executionOrder): void {
                    $executionOrder[] = 'high';
                },
                'high',
                'High',
                100,
            );

            $low = new Rule(
                $true,
                function ($context) use (&$executionOrder): void {
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

            $rb = new RuleBuilder();
            $executionOrder = [];

            $approve = new Rule(
                $rb['eligible']->sameAs(true),
                function (Context $context) use (&$executionOrder): void {
                    $executionOrder[] = 'approve';
                    $context['approved'] = true;
                },
                'approve',
                'Approval Rule',
                20,
            );

            $seed = new Rule(
                $rb['seeded']->sameAs(false),
                function (Context $context) use (&$executionOrder): void {
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

            $report = $ruleset->executeForwardChaining($context);

            expect($report)->toBeInstanceOf(RuleSetExecutionReport::class);
            expect($report->getActionExecutionCount())->toBe(2);
            expect($report->getCycleCount())->toBe(3);
            expect($context['approved'])->toBeTrue();
            expect($executionOrder)->toBe(['seed', 'approve']);
        });

        test('forward chaining does not re-fire rules by default', function (): void {
            $context = new Context(['counter' => 0]);
            $rb = new RuleBuilder();

            $rule = new Rule(
                $rb['counter']->greaterThanOrEqualTo(0),
                function (Context $context): void {
                    $counter = $context['counter'];
                    ++$counter;
                    $context['counter'] = $counter;
                },
                'counter',
                'Counter Rule',
            );

            $ruleset = new RuleSet([$rule]);

            $report = $ruleset->executeForwardChaining($context);

            expect($report->getActionExecutionCount())->toBe(1);
            expect($report->getCycleCount())->toBe(2);
            expect($context['counter'])->toBe(1);
        });

        test('executeRules returns matched and action counts', function (): void {
            $context = new Context(['flag' => true, 'disabledFlag' => true]);
            $rb = new RuleBuilder();
            $executed = [];

            $ruleA = new Rule(
                $rb['flag']->sameAs(true),
                function ($context) use (&$executed): void {
                    $executed[] = 'A';
                },
                'A',
                'Rule A',
                10,
            );

            $ruleB = new Rule(
                $rb['flag']->sameAs(false),
                function ($context) use (&$executed): void {
                    $executed[] = 'B';
                },
                'B',
                'Rule B',
                5,
            );

            $ruleDisabled = new Rule(
                $rb['disabledFlag']->sameAs(true),
                function ($context) use (&$executed): void {
                    $executed[] = 'DISABLED';
                },
                'D',
                'Disabled Rule',
                100,
                false,
            );

            $report = new RuleSet([$ruleA, $ruleB, $ruleDisabled])
                ->executeRules($context);

            expect($report)->toBeInstanceOf(RuleSetExecutionReport::class);
            expect($report->getMatchedCount())->toBe(1);
            expect($report->getActionExecutionCount())->toBe(1);
            expect($executed)->toBe(['A']);
            expect($report->getResults())->toHaveCount(3);
        });

        test('can disable and enable rule by id', function (): void {
            $context = new Context();
            $true = new TrueProposition();
            $executed = [];

            $ruleA = new Rule(
                $true,
                function ($context) use (&$executed): void {
                    $executed[] = 'A';
                },
                'rule-a',
                'A',
            );
            $ruleB = new Rule(
                $true,
                function ($context) use (&$executed): void {
                    $executed[] = 'B';
                },
                'rule-b',
                'B',
            );

            $ruleset = new RuleSet([$ruleA, $ruleB]);
            $ruleset->disableRule('rule-a');
            $ruleset->executeRules($context);

            expect($ruleset->isRuleEnabled('rule-a'))->toBeFalse();
            expect($executed)->toBe(['B']);

            $executed = [];
            $ruleset->enableRule('rule-a');
            $ruleset->executeRules($context);

            expect($ruleset->isRuleEnabled('rule-a'))->toBeTrue();
            expect($executed)->toBe(['A', 'B']);
        });

        test('removeRule and replaceRule update executable set', function (): void {
            $context = new Context();
            $true = new TrueProposition();
            $executed = [];

            $original = new Rule(
                $true,
                function ($context) use (&$executed): void {
                    $executed[] = 'original';
                },
                'original',
                'Original',
            );

            $replacement = new Rule(
                $true,
                function ($context) use (&$executed): void {
                    $executed[] = 'replacement';
                },
                'replacement',
                'Replacement',
            );

            $removeMe = new Rule(
                $true,
                function ($context) use (&$executed): void {
                    $executed[] = 'remove';
                },
                'remove',
                'Remove',
            );

            $ruleset = new RuleSet([$original, $removeMe]);
            $ruleset->replaceRule($original, $replacement);
            $ruleset->removeRule($removeMe);

            $ruleset->executeRules($context);

            expect($executed)->toBe(['replacement']);
            expect($ruleset->getRules())->toHaveCount(1);
            expect($ruleset->getRules()[0]->getId())->toBe('replacement');
        });

        test('clearRules removes all rules and resets lifecycle state', function (): void {
            $true = new TrueProposition();
            $ruleA = new Rule($true, null, 'a', 'A');
            $ruleB = new Rule($true, null, 'b', 'B');

            $ruleset = new RuleSet([$ruleA, $ruleB]);
            $ruleset->disableRule('a');
            $ruleset->clearRules();

            expect($ruleset->getRules())->toBe([]);
            expect($ruleset->isRuleEnabled('a'))->toBeFalse();
            expect($ruleset->isRuleEnabled('b'))->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('throws when adding rule without id', function (): void {
            $this->expectException(RuntimeException::class);

            $rule = new Rule(
                new TrueProposition(),
                static function ($context): void {},
            );

            new RuleSet([$rule]);
        });

        test('throws when duplicate rule ids are added', function (): void {
            $this->expectException(RuntimeException::class);

            $true = new TrueProposition();
            $first = new Rule($true, static function ($context): void {}, 'dupe');
            $second = new Rule($true, static function ($context): void {}, 'dupe');

            new RuleSet([$first, $second]);
        });

        test('forward chaining throws when max cycles is exceeded', function (): void {
            $context = new Context();
            $true = new TrueProposition();

            $rule = new Rule(
                $true,
                static function ($context): void {},
                'loop',
                'Loop Rule',
            );

            $ruleset = new RuleSet([$rule]);

            expect(fn (): RuleSetExecutionReport => $ruleset->executeForwardChaining($context, 2, true))
                ->toThrow(RuntimeException::class);
        });
    });
});
