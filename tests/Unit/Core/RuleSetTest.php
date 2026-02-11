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
    });
});
