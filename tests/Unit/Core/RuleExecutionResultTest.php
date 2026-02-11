<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\RuleExecutionResult;

describe('RuleExecutionResult', function (): void {
    describe('Happy Paths', function (): void {
        test('stores execution fields', function (): void {
            $result = new RuleExecutionResult(
                'rule-1',
                'Check age',
                10,
                true,
                true,
                true,
            );

            expect($result->ruleId)->toBe('rule-1');
            expect($result->ruleName)->toBe('Check age');
            expect($result->priority)->toBe(10);
            expect($result->enabled)->toBeTrue();
            expect($result->matched)->toBeTrue();
            expect($result->actionExecuted)->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('rejects null rule id', function (): void {
            $this->expectException(TypeError::class);

            new RuleExecutionResult(
                null,
                null,
                0,
                true,
                false,
                false,
            );
        });
    });
});
