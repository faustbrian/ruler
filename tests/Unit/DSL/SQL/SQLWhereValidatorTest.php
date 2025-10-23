<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\SQL\SQLWhereValidator;
use Cline\Ruler\DSL\Wirefilter\ValidationResult;

describe('SQLWhereValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for valid simple comparison', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('age > 18'))->toBeTrue();
        });

        test('validate returns true for valid AND expression', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("age >= 18 AND country = 'US'"))->toBeTrue();
        });

        test('validate returns true for valid OR expression', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("age >= 21 OR status = 'verified'"))->toBeTrue();
        });

        test('validate returns true for valid NOT expression', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('NOT (age < 18)'))->toBeTrue();
        });

        test('validate returns true for valid IN operator', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("country IN ('US', 'CA', 'UK')"))->toBeTrue();
        });

        test('validate returns true for valid NOT IN operator', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("country NOT IN ('FR', 'DE')"))->toBeTrue();
        });

        test('validate returns true for valid BETWEEN operator', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('age BETWEEN 18 AND 65'))->toBeTrue();
        });

        test('validate returns true for valid LIKE operator', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("email LIKE '%@example.com'"))->toBeTrue();
        });

        test('validate returns true for valid NOT LIKE operator', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("email NOT LIKE '%@spam.com'"))->toBeTrue();
        });

        test('validate returns true for valid IS NULL', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('middle_name IS NULL'))->toBeTrue();
        });

        test('validate returns true for valid IS NOT NULL', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('email IS NOT NULL'))->toBeTrue();
        });

        test('validate returns true for complex nested expression', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate(
                "(age >= 18 AND country = 'US') OR (age >= 21 AND country IN ('CA', 'UK'))",
            ))->toBeTrue();
        });

        test('validateWithErrors returns success for valid expression', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('age > 18');

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for invalid syntax', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('age > >'))->toBeFalse();
        });

        test('validate returns false for incomplete expression', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('age >'))->toBeFalse();
        });

        test('validate returns false for missing operand', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('AND age > 18'))->toBeFalse();
        });

        test('validate returns false for unmatched parentheses', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('(age > 18'))->toBeFalse();
        });

        test('validate returns false for extra closing parenthesis', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('age > 18)'))->toBeFalse();
        });

        test('validate returns false for invalid IN syntax', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate("country IN 'US'"))->toBeFalse();
        });

        test('validate returns false for incomplete BETWEEN', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('age BETWEEN 18'))->toBeFalse();
        });

        test('validateWithErrors returns failure with error message for invalid syntax', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('age > >');

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->toBeString();
        });

        test('validateWithErrors captures error details for incomplete expression', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('age >');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->toHaveCount(1)
                ->and($result->getErrors()[0])->toHaveKey('message');
        });

        test('validateWithErrors captures error for unmatched parentheses', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('(age > 18');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrorMessages())->not->toBeEmpty();
        });

        test('validateWithErrors returns error for missing AND operand', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('age > 18 AND');

            expect($result->isValid())->toBeFalse()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors returns error for invalid field name', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('123invalid > 18');

            expect($result->isValid())->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty string returns false', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate(''))->toBeFalse();
        });

        test('validate whitespace only returns false', function (): void {
            $validator = new SQLWhereValidator();

            expect($validator->validate('   '))->toBeFalse();
        });

        test('validateWithErrors handles empty string', function (): void {
            $validator = new SQLWhereValidator();
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });
    });
});
