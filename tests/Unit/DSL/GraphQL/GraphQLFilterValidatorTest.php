<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\GraphQL\GraphQLFilterValidator;
use Cline\Ruler\DSL\Wirefilter\ValidationResult;

describe('GraphQLFilterValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for valid expression', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate(['age' => ['gte' => 18]]))->toBeTrue();
        });

        test('validate returns true for complex valid expression', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate([
                'age' => ['gte' => 18],
                'country' => 'US',
            ]))->toBeTrue();
        });

        test('validate returns true for expression with arrays', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate(['country' => ['in' => ['US', 'CA', 'UK']]]))->toBeTrue();
        });

        test('validate returns true for logical operators', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate([
                'OR' => [
                    ['age' => ['gte' => 21]],
                    ['country' => 'US'],
                ],
            ]))->toBeTrue();
        });

        test('validate returns true for JSON string', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate('{"age": {"gte": 18}}'))->toBeTrue();
        });

        test('validateWithErrors returns success for valid expression', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors(['age' => ['gte' => 18]]);

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for complex expression', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors([
                'OR' => [
                    [
                        'AND' => [
                            ['age' => ['gte' => 18]],
                            ['country' => 'US'],
                        ],
                    ],
                    ['verified' => true],
                ],
            ]);

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for invalid JSON', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate('{"age": invalid}'))->toBeFalse();
        });

        test('validateWithErrors returns failure for invalid JSON', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors('{"invalid": }');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors provides error message', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors('not valid json');

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors getErrorMessages returns array of messages', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors('{"malformed"}');

            expect($result->isValid())->toBeFalse();

            $messages = $result->getErrorMessages();
            expect($messages)->toBeArray()
                ->and($messages)->not->toBeEmpty();
        });

        test('validateWithErrors getFirstError returns first error message', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors('{"incomplete": ');

            expect($result->isValid())->toBeFalse();

            $firstError = $result->getFirstError();
            expect($firstError)->toBeString()
                ->and($firstError)->not->toBeEmpty();
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty array', function (): void {
            $validator = new GraphQLFilterValidator();

            // Empty array is technically valid (no filters)
            expect($validator->validate([]))->toBeTrue();
        });

        test('validate empty string', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate(''))->toBeFalse();
        });

        test('validateWithErrors empty string returns failure', function (): void {
            $validator = new GraphQLFilterValidator();
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });

        test('validate deeply nested structure', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate([
                'OR' => [
                    [
                        'AND' => [
                            ['age' => ['gte' => 18]],
                            [
                                'OR' => [
                                    ['country' => 'US'],
                                    ['country' => 'CA'],
                                ],
                            ],
                        ],
                    ],
                    ['verified' => true],
                ],
            ]))->toBeTrue();
        });

        test('ValidationResult success factory', function (): void {
            $result = ValidationResult::success();

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty()
                ->and($result->getFirstError())->toBeNull();
        });

        test('ValidationResult failure factory', function (): void {
            $errors = [
                ['message' => 'Test error', 'context' => 'test context'],
            ];

            $result = ValidationResult::failure($errors);

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->toHaveCount(1)
                ->and($result->getFirstError())->toBe('Test error');
        });

        test('validate handles null values correctly', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate(['field' => null]))->toBeTrue();
        });

        test('validate handles boolean values', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate(['active' => true]))->toBeTrue()
                ->and($validator->validate(['deleted' => false]))->toBeTrue();
        });

        test('validate handles numeric values', function (): void {
            $validator = new GraphQLFilterValidator();

            expect($validator->validate(['count' => 42]))->toBeTrue()
                ->and($validator->validate(['price' => 99.99]))->toBeTrue();
        });
    });
});
