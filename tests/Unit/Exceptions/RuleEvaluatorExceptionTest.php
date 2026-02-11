<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Enums\RuleErrorCode;
use Cline\Ruler\Enums\RuleErrorPhase;
use Cline\Ruler\Exceptions\RuleEvaluatorException;

describe('RuleEvaluatorException', function (): void {
    describe('Happy Paths', function (): void {
        test('invalidRuleStructure creates exception with correct message', function (): void {
            // Act
            $exception = RuleEvaluatorException::invalidRuleStructure();

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Invalid rule structure');
            expect($exception->getErrorCode())->toBe(RuleErrorCode::CompileInvalidRuleStructure);
            expect($exception->getPhase())->toBe(RuleErrorPhase::Compile);
            expect($exception->getPath())->toBe([]);
        });

        test('invalidNotRule creates exception with correct message', function (): void {
            // Act
            $exception = RuleEvaluatorException::invalidNotRule();

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Logical NOT must have exactly one argument');
            expect($exception->getErrorCode())->toBe(RuleErrorCode::CompileInvalidNotArity);
            expect($exception->getPath())->toBe(['value']);
        });

        test('invalidCombinator creates exception with correct message', function (): void {
            // Arrange
            $invalidCombinator = 'xor';

            // Act
            $exception = RuleEvaluatorException::invalidCombinator($invalidCombinator);

            // Assert
            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Invalid combinator: xor');
            expect($exception->getErrorCode())->toBe(RuleErrorCode::CompileInvalidCombinator);
            expect($exception->getPath())->toBe(['combinator']);
        });

        test('invalidRuleCacheKey creates exception with previous context', function (): void {
            $previous = new RuntimeException('json encode failed');
            $exception = RuleEvaluatorException::invalidRuleCacheKey('json encode failed', $previous);

            expect($exception)->toBeInstanceOf(RuleEvaluatorException::class);
            expect($exception->getMessage())->toBe('Unable to generate rule cache key: json encode failed');
            expect($exception->getPrevious())->toBe($previous);
            expect($exception->getErrorCode())->toBe(RuleErrorCode::CompileCacheKeyGenerationFailed);
            expect($exception->getPath())->toBe(['rules']);
            expect($exception->getDetails())->toBe(['reason' => 'json encode failed']);
        });

        test('unknownOperator creates exception with field and operator details', function (): void {
            $previous = new InvalidArgumentException('unknown operator');
            $exception = RuleEvaluatorException::unknownOperator(
                'doesNotExist',
                'status',
                ['operator'],
                $previous,
            );

            expect($exception->getMessage())->toBe('Unknown operator: "doesNotExist"');
            expect($exception->getErrorCode())->toBe(RuleErrorCode::CompileUnknownOperator);
            expect($exception->getPhase())->toBe(RuleErrorPhase::Compile);
            expect($exception->getPath())->toBe(['operator']);
            expect($exception->getDetails())->toBe([
                'operator' => 'doesNotExist',
                'field' => 'status',
            ]);
            expect($exception->getPrevious())->toBe($previous);
        });

        test('runtimeEvaluationFailed creates runtime payload', function (): void {
            $previous = new RuntimeException('evaluation exploded');
            $exception = RuleEvaluatorException::runtimeEvaluationFailed(
                'Rule evaluation failed',
                ['rules', 0],
                ['values' => ['status' => 'active']],
                $previous,
            );

            expect($exception->getErrorCode())->toBe(RuleErrorCode::RuntimeEvaluationFailed);
            expect($exception->getPhase())->toBe(RuleErrorPhase::Runtime);
            expect($exception->getPath())->toBe(['rules', 0]);
            expect($exception->getDetails())->toBe([
                'values' => ['status' => 'active'],
            ]);
            expect($exception->getPrevious())->toBe($previous);
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

        test('toArray returns machine-readable payload', function (): void {
            $exception = RuleEvaluatorException::invalidRuleStructure(
                'Operator must be a string',
                ['value', 0, 'operator'],
                ['receivedType' => 'int'],
            );

            expect($exception->toArray())->toBe([
                'contractVersion' => RuleEvaluatorException::ERROR_CONTRACT_VERSION,
                'message' => 'Operator must be a string',
                'errorCode' => 'compile.invalid_rule_structure',
                'phase' => 'compile',
                'path' => ['value', 0, 'operator'],
                'details' => ['receivedType' => 'int'],
            ]);
        });
    });
});
