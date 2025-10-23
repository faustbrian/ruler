<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Exceptions\RuleEvaluatorException;

describe('RuleEvaluatorException', function (): void {
    describe('Happy Paths', function (): void {
        test('invalidRuleStructure creates exception with correct message', function (): void {
            // Act
            $exception = RuleEvaluatorException::invalidRuleStructure();

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Invalid rule structure');
        });

        test('invalidNotRule creates exception with correct message', function (): void {
            // Act
            $exception = RuleEvaluatorException::invalidNotRule();

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Logical NOT must have exactly one argument');
        });

        test('invalidCombinator creates exception with correct message', function (): void {
            // Arrange
            $invalidCombinator = 'xor';

            // Act
            $exception = RuleEvaluatorException::invalidCombinator($invalidCombinator);

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Invalid combinator: xor');
        });
    });

    describe('Edge Cases', function (): void {
        test('invalidCombinator handles empty string', function (): void {
            // Arrange
            $emptyCombinator = '';

            // Act
            $exception = RuleEvaluatorException::invalidCombinator($emptyCombinator);

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Invalid combinator: ');
        });

        test('invalidCombinator handles special characters', function (): void {
            // Arrange
            $specialCombinator = '&&||';

            // Act
            $exception = RuleEvaluatorException::invalidCombinator($specialCombinator);

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Invalid combinator: &&||');
        });
    });
});
