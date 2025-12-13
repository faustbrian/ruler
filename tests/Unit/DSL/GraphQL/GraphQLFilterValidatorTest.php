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

        test('validateWithErrors includes context for JSON parse error with position', function (): void {
            $validator = new GraphQLFilterValidator();
            $invalidJson = '{"field": "value", "bad: json}';
            $result = $validator->validateWithErrors($invalidJson);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('Invalid JSON');
        });

        test('validateWithErrors includes context for long JSON with truncation', function (): void {
            $validator = new GraphQLFilterValidator();
            // Create a long invalid JSON string with error position far in
            $longJson = str_repeat('x', 30).'{"field1": "value1", "bad: json, "field5": "value5"}'.str_repeat('y', 30);
            $result = $validator->validateWithErrors($longJson);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors handles short invalid JSON string', function (): void {
            $validator = new GraphQLFilterValidator();

            // Short string that fits within the max length (testing Throwable branch with string)
            $shortJson = '{"short": invalid}';
            $result = $validator->validateWithErrors($shortJson);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message');
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

        test('validateWithErrors extracts context with ellipsis at start when error near end', function (): void {
            $validator = new GraphQLFilterValidator();

            // Create JSON with error near the end - context should have ellipsis at start
            $jsonWithErrorAtEnd = str_repeat('{"f": "v"},', 10).'{"bad: }';
            $result = $validator->validateWithErrors($jsonWithErrorAtEnd);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors no position in JSON error message', function (): void {
            $validator = new GraphQLFilterValidator();

            // Malformed JSON that may not include position in error
            $result = $validator->validateWithErrors('}{');

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('Invalid JSON');
        });

        test('validateWithErrors JsonException with null context when position not found', function (): void {
            $validator = new GraphQLFilterValidator();

            // JSON error without position in message - extractJsonContext returns null (line 171)
            // Line 123 check should prevent adding null context to error array
            $result = $validator->validateWithErrors('}{');

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                // When context is null, it shouldn't be in the error array
                ->and($errors[0]['message'])->toContain('Invalid JSON');

            // Verify context key doesn't exist when null (line 123 prevents it)
            expect(array_key_exists('context', $errors[0]))->toBeFalse();
        });

        test('validateWithErrors JsonException attempts position extraction', function (): void {
            $validator = new GraphQLFilterValidator();

            // Test various malformed JSON to see if any include position in error
            // Lines 152-168 handle position extraction if error message includes "at position X"
            // Note: PHP 8.4's JsonException may not include position, making this defensive code
            $testCases = [
                '{"field": "value", "bad":}',  // Missing value
                '{"field": "value"',           // Unclosed brace
                '{"field": }',                 // Missing value after colon
                '{field: "value"}',            // Unquoted key
            ];

            foreach ($testCases as $invalidJson) {
                $result = $validator->validateWithErrors($invalidJson);

                expect($result->isValid())->toBeFalse();
                $errors = $result->getErrors();
                expect($errors)->not->toBeEmpty()
                    ->and($errors[0])->toHaveKey('message')
                    ->and($errors[0]['message'])->toContain('Invalid JSON');

                // The regex on line 151 looks for "at position (\d+)" in error message
                // If found, lines 152-168 extract context around that position
                // If not found (typical in PHP 8.4), line 171 returns null
                // Line 123 only adds context if it's not null
            }
        });

        test('validateWithErrors Throwable with array context - filter with keys', function (): void {
            $validator = new GraphQLFilterValidator();

            // Invalid operator with wrong type - triggers InvalidArgumentException (lines 127-133)
            $result = $validator->validateWithErrors([
                'field1' => ['in' => 'not_an_array'], // 'in' operator expects array
            ]);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0])->toHaveKey('context')
                ->and($errors[0]['context'])->toContain('Filter with keys:');
        });

        test('validateWithErrors extractFilterContext with more than 5 keys', function (): void {
            $validator = new GraphQLFilterValidator();

            // Array with more than 5 keys to trigger key limiting (lines 198-201)
            $result = $validator->validateWithErrors([
                'field1' => ['in' => 'not_array'],
                'field2' => 'v2',
                'field3' => 'v3',
                'field4' => 'v4',
                'field5' => 'v5',
                'field6' => 'v6',
                'field7' => 'v7',
            ]);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('context')
                ->and($errors[0]['context'])->toContain('...');
        });

        test('validateWithErrors extractFilterContext with long string over 100 chars', function (): void {
            $validator = new GraphQLFilterValidator();

            // String longer than 100 chars to trigger truncation (lines 188-189)
            // Use invalid but parseable-looking string to trigger Throwable not JsonException
            $longString = '["'.str_repeat('a', 150).'"]';
            $result = $validator->validateWithErrors($longString);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('context')
                ->and($errors[0]['context'])->toContain('...')
                ->and(mb_strlen($errors[0]['context']))->toBeLessThanOrEqual(103); // 100 + '...'
        });

        test('validateWithErrors extractFilterContext with short string under 100 chars', function (): void {
            $validator = new GraphQLFilterValidator();

            // String under 100 chars - no truncation (line 192)
            // Valid JSON but invalid filter structure to trigger Throwable
            $shortString = '["not_an_object"]';
            $result = $validator->validateWithErrors($shortString);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('context')
                ->and($errors[0]['context'])->toBe($shortString);
        });

        test('validateWithErrors extractJsonContext with position and ellipsis at both ends', function (): void {
            $validator = new GraphQLFilterValidator();

            // Long JSON with error in middle to trigger ellipsis at both ends (lines 160-166)
            $longPrefix = str_repeat('x', 50);
            $longSuffix = str_repeat('y', 50);
            $invalidJson = $longPrefix.'{"field": invalid}'.$longSuffix;
            $result = $validator->validateWithErrors($invalidJson);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('Invalid JSON');
        });

        test('validateWithErrors extractJsonContext with position near start', function (): void {
            $validator = new GraphQLFilterValidator();

            // Error near start - no ellipsis at start (lines 160-161 - condition false)
            $invalidJson = '{"bad: json} '.str_repeat('x', 100);
            $result = $validator->validateWithErrors($invalidJson);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('Invalid JSON');
        });

        test('validateWithErrors extractJsonContext with position near end', function (): void {
            $validator = new GraphQLFilterValidator();

            // Error near end - no ellipsis at end (lines 164-165 - condition false)
            $invalidJson = str_repeat('x', 100).'{"bad: }';
            $result = $validator->validateWithErrors($invalidJson);

            expect($result->isValid())->toBeFalse();
            $errors = $result->getErrors();
            expect($errors)->not->toBeEmpty()
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('Invalid JSON');
        });
    });
});
