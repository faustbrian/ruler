<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\RuleEvaluator;
use Cline\Ruler\Core\RuleEvaluatorCompilationResult;
use Cline\Ruler\Exceptions\RuleEvaluatorException;

describe('RuleEvaluatorCompilationResult', function (): void {
    test('returns evaluator on success', function (): void {
        $evaluator = RuleEvaluator::createFromArray([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ]);

        $result = RuleEvaluatorCompilationResult::success($evaluator);

        expect($result->isSuccess())->toBeTrue();
        expect($result->getEvaluator())->toBe($evaluator);
        expect($result->getError())->toBeNull();
    });

    test('returns error on failure', function (): void {
        $error = RuleEvaluatorException::invalidRuleStructure('Broken rule');

        $result = RuleEvaluatorCompilationResult::failure($error);

        expect($result->isSuccess())->toBeFalse();
        expect($result->getError())->toBe($error);
    });

    test('throws when reading evaluator from failure result', function (): void {
        $error = RuleEvaluatorException::invalidRuleStructure('Broken rule');
        $result = RuleEvaluatorCompilationResult::failure($error);

        expect(fn (): RuleEvaluator => $result->getEvaluator())
            ->toThrow(LogicException::class, 'Compilation failed; evaluator is unavailable.');
    });
});
