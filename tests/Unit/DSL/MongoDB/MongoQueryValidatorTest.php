<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\MongoDB\MongoQueryValidator;
use Cline\Ruler\DSL\Wirefilter\ValidationResult;

describe('MongoQueryValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for valid query array', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['age' => ['$gte' => 18]]))->toBeTrue();
        });

        test('validate returns true for implicit equality', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['status' => 'active']))->toBeTrue();
        });

        test('validate returns true for complex valid query', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate([
                '$and' => [
                    ['age' => ['$gte' => 18]],
                    ['country' => 'US'],
                ],
            ]))->toBeTrue();
        });

        test('validate returns true for logical operators', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate([
                '$or' => [
                    ['age' => ['$gte' => 21]],
                    ['country' => 'US'],
                ],
            ]))->toBeTrue();
        });

        test('validate returns true for query with arrays', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['country' => ['$in' => ['US', 'CA', 'UK']]]))->toBeTrue();
        });

        test('validateJson returns true for valid JSON query', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validateJson('{"age": {"$gte": 18}}'))->toBeTrue();
        });

        test('validateJson returns true for complex JSON query', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validateJson('{"$and": [{"age": {"$gte": 18}}, {"country": "US"}]}'))->toBeTrue();
        });

        test('validateWithErrors returns success for valid query', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors(['age' => ['$gte' => 18]]);

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for complex query', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors([
                '$or' => [
                    [
                        '$and' => [
                            ['age' => ['$gte' => 18]],
                            ['country' => 'US'],
                        ],
                    ],
                    ['verified' => true],
                ],
            ]);

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateJsonWithErrors returns success for valid JSON', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateJsonWithErrors('{"age": {"$gte": 18}}');

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for invalid operator', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['age' => ['$invalidOp' => 18]]))->toBeFalse();
        });

        test('validate returns false for malformed query', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['$and' => 'not-an-array']))->toBeFalse();
        });

        test('validateJson returns false for invalid JSON', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validateJson('{"age": invalid}'))->toBeFalse();
        });

        test('validateJson returns false for non-object JSON', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validateJson('"just a string"'))->toBeFalse();
        });

        test('validateJson returns false for JSON array at root', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validateJson('[{"age": 18}]'))->toBeFalse();
        });

        test('validateWithErrors returns failure for invalid query', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors(['age' => ['$invalidOp' => 18]]);

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors provides error message', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors(['age' => ['$invalidOp' => 18]]);

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('Unsupported operator');
        });

        test('validateWithErrors getErrorMessages returns array of messages', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors(['field' => ['$badOp' => 'value']]);

            expect($result->isValid())->toBeFalse();

            $messages = $result->getErrorMessages();
            expect($messages)->toBeArray()
                ->and($messages)->not->toBeEmpty();
        });

        test('validateWithErrors getFirstError returns first error message', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors(['age' => ['$invalid' => 18]]);

            expect($result->isValid())->toBeFalse();

            $firstError = $result->getFirstError();
            expect($firstError)->toBeString()
                ->and($firstError)->not->toBeEmpty();
        });

        test('validateJsonWithErrors returns failure for invalid JSON', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateJsonWithErrors('{"age": invalid}');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });

        test('validateJsonWithErrors provides JSON error message', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateJsonWithErrors('{"age": }');

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toHaveKey('message')
                ->and($errors[0]['message'])->toContain('JSON');
        });

        test('validateJsonWithErrors returns failure for non-object JSON', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateJsonWithErrors('"just a string"');

            expect($result->isValid())->toBeFalse()
                ->and($result->getFirstError())->toContain('must decode to an object/array');
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty query', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate([]))->toBeTrue();
        });

        test('validateJson empty object', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validateJson('{}'))->toBeTrue();
        });

        test('validateWithErrors empty query returns success', function (): void {
            $validator = new MongoQueryValidator();
            $result = $validator->validateWithErrors([]);

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validate query with null value', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['deleted_at' => null]))->toBeTrue();
        });

        test('validate query with boolean value', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['verified' => true]))->toBeTrue();
        });

        test('validate query with numeric value', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['price' => 99.99]))->toBeTrue();
        });

        test('validate query with string value', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate(['status' => 'active']))->toBeTrue();
        });

        test('validate deeply nested query', function (): void {
            $validator = new MongoQueryValidator();

            expect($validator->validate([
                '$or' => [
                    [
                        '$and' => [
                            ['age' => ['$gte' => 18]],
                            [
                                '$or' => [
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
                ['message' => 'Test error', 'position' => 5],
            ];

            $result = ValidationResult::failure($errors);

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->toHaveCount(1)
                ->and($result->getFirstError())->toBe('Test error');
        });
    });
});
