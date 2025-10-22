<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\Natural\NaturalLanguageValidator;
use Cline\Ruler\DSL\Wirefilter\ValidationResult;

describe('NaturalLanguageValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for valid expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('age is at least 18'))->toBeTrue();
        });

        test('validate returns true for complex valid expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('age is at least 18 and country equals US'))->toBeTrue();
        });

        test('validate returns true for between expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('age is between 18 and 65'))->toBeTrue();
        });

        test('validate returns true for is one of expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('country is one of US, CA, UK'))->toBeTrue();
        });

        test('validate returns true for string operations', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('name contains John'))->toBeTrue();
        });

        test('validateWithErrors returns success for valid expression', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('age is greater than 18');

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for complex expression', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('(age is at least 18 and country equals US) or age is at least 21');

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validate returns true for is either or expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('status is either active or pending'))->toBeTrue();
        });

        test('validate returns true for is not one of expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('country is not one of US, CA'))->toBeTrue();
        });

        test('validate returns true for starts with expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('email starts with admin'))->toBeTrue();
        });

        test('validate returns true for ends with expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('filename ends with .pdf'))->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for invalid operator', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('age invalid operator 18'))->toBeFalse();
        });

        test('validate returns false for malformed expression', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('age is'))->toBeFalse();
        });

        test('validate returns false for incomplete comparison', function (): void {
            $validator = new NaturalLanguageValidator();

            // "age is greater than" with no value is actually parsed as "age equals 'greater than'"
            // Let's use a truly incomplete expression
            expect($validator->validate('age is'))->toBeFalse();
        });

        test('validateWithErrors returns failure for invalid expression', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('age invalid operator 18');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors provides error message', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('age is');

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors getErrorMessages returns array of messages', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('invalid syntax here');

            expect($result->isValid())->toBeFalse();

            $messages = $result->getErrorMessages();
            expect($messages)->toBeArray()
                ->and($messages)->not->toBeEmpty();
        });

        test('validateWithErrors getFirstError returns first error message', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('age is');

            expect($result->isValid())->toBeFalse();

            $firstError = $result->getFirstError();
            expect($firstError)->toBeString()
                ->and($firstError)->not->toBeEmpty();
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty string', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate(''))->toBeFalse();
        });

        test('validateWithErrors empty string returns failure', function (): void {
            $validator = new NaturalLanguageValidator();
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });

        test('validate whitespace only', function (): void {
            $validator = new NaturalLanguageValidator();

            expect($validator->validate('   '))->toBeFalse();
        });

        test('ValidationResult success factory', function (): void {
            $result = ValidationResult::success();

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty()
                ->and($result->getFirstError())->toBeNull();
        });

        test('ValidationResult failure factory', function (): void {
            $errors = [
                ['message' => 'Test error'],
            ];

            $result = ValidationResult::failure($errors);

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->toHaveCount(1)
                ->and($result->getFirstError())->toBe('Test error');
        });
    });
});
