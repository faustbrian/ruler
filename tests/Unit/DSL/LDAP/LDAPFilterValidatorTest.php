<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\LDAP\LDAPFilterValidator;
use Cline\Ruler\DSL\Wirefilter\ValidationResult;

describe('LDAPFilterValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for simple equality', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(country=US)'))->toBeTrue();
        });

        test('validate returns true for greater than or equal', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(age>=18)'))->toBeTrue();
        });

        test('validate returns true for AND operator', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(&(age>=18)(country=US))'))->toBeTrue();
        });

        test('validate returns true for OR operator', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(|(age>=21)(country=US))'))->toBeTrue();
        });

        test('validate returns true for NOT operator', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(!(status=inactive))'))->toBeTrue();
        });

        test('validate returns true for presence check', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(email=*)'))->toBeTrue();
        });

        test('validate returns true for wildcard pattern', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(name=John*)'))->toBeTrue();
        });

        test('validate returns true for approximate match', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(name~=john)'))->toBeTrue();
        });

        test('validate returns true for complex nested filter', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(&(age>=18)(|(country=US)(country=CA))(status=active))'))->toBeTrue();
        });

        test('validateWithErrors returns success for valid expression', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('(age>=18)');

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for complex expression', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('(&(age>=18)(|(country=US)(country=CA)))');

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validate returns true for all comparison operators', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(age=18)'))->toBeTrue()
                ->and($validator->validate('(age>=18)'))->toBeTrue()
                ->and($validator->validate('(age<=65)'))->toBeTrue()
                ->and($validator->validate('(age>18)'))->toBeTrue()
                ->and($validator->validate('(age<65)'))->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for missing opening parenthesis', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('age>=18)'))->toBeFalse();
        });

        test('validate returns false for missing closing parenthesis', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(age>=18'))->toBeFalse();
        });

        test('validate returns false for missing operator', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(age18)'))->toBeFalse();
        });

        test('validate returns false for missing attribute', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(=18)'))->toBeFalse();
        });

        test('validate returns false for empty AND operator', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(&)'))->toBeFalse();
        });

        test('validate returns false for empty OR operator', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(|)'))->toBeFalse();
        });

        test('validateWithErrors returns failure for invalid expression', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('(=18)');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors provides error message', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('(age>=18');

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors getErrorMessages returns array of messages', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('invalid syntax');

            expect($result->isValid())->toBeFalse();

            $messages = $result->getErrorMessages();
            expect($messages)->toBeArray()
                ->and($messages)->not->toBeEmpty();
        });

        test('validateWithErrors getFirstError returns first error message', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('(=18)');

            expect($result->isValid())->toBeFalse();

            $firstError = $result->getFirstError();
            expect($firstError)->toBeString()
                ->and($firstError)->not->toBeEmpty();
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty string', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate(''))->toBeFalse();
        });

        test('validateWithErrors empty string returns failure', function (): void {
            $validator = new LDAPFilterValidator();
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });

        test('validate single parenthesis', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('('))->toBeFalse();
        });

        test('validate nested parentheses without content', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(())'))->toBeFalse();
        });

        test('validate with spaces in filter', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(name=John Doe)'))->toBeTrue();
        });

        test('validate with special characters in value', function (): void {
            $validator = new LDAPFilterValidator();

            expect($validator->validate('(email=user@example.com)'))->toBeTrue();
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
