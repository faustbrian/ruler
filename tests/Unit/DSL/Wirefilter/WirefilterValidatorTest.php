<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\Wirefilter\ValidationResult;
use Cline\Ruler\DSL\Wirefilter\WirefilterValidator;

describe('WirefilterValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for valid expression', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('age >= 18'))->toBeTrue();
        });

        test('validate returns true for complex valid expression', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('age >= 18 and country == "US"'))->toBeTrue();
        });

        test('validate returns true for mathematical expression', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('price + shipping > 100'))->toBeTrue();
        });

        test('validate returns true for expression with arrays', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('country in ["US", "CA", "UK"]'))->toBeTrue();
        });

        test('validateWithErrors returns success for valid expression', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('age >= 18');

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for complex expression', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('(age >= 18 and country == "US") or verified == true');

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for invalid syntax', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('age >= '))->toBeFalse();
        });

        test('validate returns false for unclosed parenthesis', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('(age >= 18'))->toBeFalse();
        });

        test('validate returns false for invalid operator', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate('age >>> 18'))->toBeFalse();
        });

        test('validateWithErrors returns failure for invalid expression', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('age >= ');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors provides error message', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('age >= ');

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors getErrorMessages returns array of messages', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('invalid ><> syntax');

            expect($result->isValid())->toBeFalse();

            $messages = $result->getErrorMessages();
            expect($messages)->toBeArray()
                ->and($messages)->not->toBeEmpty();
        });

        test('validateWithErrors getFirstError returns first error message', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('age >= ');

            expect($result->isValid())->toBeFalse();

            $firstError = $result->getFirstError();
            expect($firstError)->toBeString()
                ->and($firstError)->not->toBeEmpty();
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty string', function (): void {
            $validator = new WirefilterValidator();

            expect($validator->validate(''))->toBeFalse();
        });

        test('validateWithErrors empty string returns failure', function (): void {
            $validator = new WirefilterValidator();
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
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

        test('validateWithErrors handles non-SyntaxError exceptions', function (): void {
            // Create a validator with invalid input that triggers non-SyntaxError exception
            $validator = new WirefilterValidator();

            // Very long invalid expression might trigger different error types
            $result = $validator->validateWithErrors('this is not valid wirefilter syntax at all');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeEmpty();
        });

        test('validateWithErrors provides context for syntax errors', function (): void {
            $validator = new WirefilterValidator();

            // Expression with error in the middle
            $result = $validator->validateWithErrors('age >= 18 and country === invalid operator here');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();

            // Check if error details exist
            $firstError = $result->getErrors()[0];
            expect($firstError)->toHaveKey('message');
        });

        test('validateWithErrors handles errors without position info', function (): void {
            $validator = new WirefilterValidator();

            // Some errors might not have position information
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });
    });
});
