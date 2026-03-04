<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Rule;
use Cline\Ruler\Core\RuleCompilationResult;
use Cline\Ruler\Core\RuleCompiler;
use Cline\Ruler\Exceptions\InvalidRuleStructureException;

describe('RuleCompilationResult', function (): void {
    test('returns rule on success', function (): void {
        $rule = RuleCompiler::compileFromArray([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ])->getRule();

        $result = RuleCompilationResult::success($rule);

        expect($result->isSuccess())->toBeTrue();
        expect($result->getRule())->toBe($rule);
        expect($result->getError())->toBeNull();
    });

    test('returns error on failure', function (): void {
        $error = InvalidRuleStructureException::forReason('Broken rule');

        $result = RuleCompilationResult::failure($error);

        expect($result->isSuccess())->toBeFalse();
        expect($result->getError())->toBe($error);
    });

    test('throws when reading rule from failure result', function (): void {
        $error = InvalidRuleStructureException::forReason('Broken rule');
        $result = RuleCompilationResult::failure($error);

        expect(fn (): Rule => $result->getRule())
            ->toThrow(LogicException::class, 'Compilation failed; rule is unavailable.');
    });
});
