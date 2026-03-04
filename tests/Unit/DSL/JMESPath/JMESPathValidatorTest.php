<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\JMESPath\JMESPathValidator;
use Cline\Ruler\DSL\Wirefilter\ValidationResult;

describe('JMESPathValidator', function (): void {
    describe('Happy Paths', function (): void {
        test('validate returns true for valid expression', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('age >= `18`'))->toBeTrue();
        });

        test('validate returns true for complex valid expression', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('age >= `18` && country == `"US"`'))->toBeTrue();
        });

        test('validate returns true for nested property expression', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('user.age >= `18`'))->toBeTrue();
        });

        test('validate returns true for expression with length function', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('length(@) > `5`'))->toBeTrue();
        });

        test('validate returns true for array filter expression', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('users[?age > `18`]'))->toBeTrue();
        });

        test('validate returns true for projection expression', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('users[*].age'))->toBeTrue();
        });

        test('validateWithErrors returns success for valid expression', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('age >= `18`');

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for complex expression', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('(age >= `18` && country == `"US"`) || verified == `true`');

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });

        test('validateWithErrors returns success for type function', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('type(@) == `"array"`');

            expect($result->isValid())->toBeTrue()
                ->and($result->getErrors())->toBeEmpty();
        });
    });

    describe('Sad Paths', function (): void {
        test('validate returns false for invalid syntax', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('age >= '))->toBeFalse();
        });

        test('validate returns false for unclosed bracket', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('users[?age > `18`'))->toBeFalse();
        });

        test('validate returns false for invalid operator', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('age >>> `18`'))->toBeFalse();
        });

        test('validate returns false for invalid function', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate('invalid_function(age)'))->toBeFalse();
        });

        test('validateWithErrors returns failure for invalid expression', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('age >= ');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty()
                ->and($result->getFirstError())->not->toBeNull();
        });

        test('validateWithErrors provides error message', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('age >= ');

            expect($result->isValid())->toBeFalse();

            $errors = $result->getErrors();
            expect($errors)->toHaveCount(1)
                ->and($errors[0])->toHaveKey('message');
        });

        test('validateWithErrors getErrorMessages returns array of messages', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('invalid >>> syntax');

            expect($result->isValid())->toBeFalse();

            $messages = $result->getErrorMessages();
            expect($messages)->toBeArray()
                ->and($messages)->not->toBeEmpty();
        });

        test('validateWithErrors getFirstError returns first error message', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('age >= ');

            expect($result->isValid())->toBeFalse();

            $firstError = $result->getFirstError();
            expect($firstError)->toBeString()
                ->and($firstError)->not->toBeEmpty();
        });

        test('validateWithErrors returns failure for unclosed bracket', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('users[?age > `18`');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });
    });

    describe('Edge Cases', function (): void {
        test('validate empty string', function (): void {
            $validator = new JMESPathValidator();

            expect($validator->validate(''))->toBeFalse();
        });

        test('validateWithErrors empty string returns failure', function (): void {
            $validator = new JMESPathValidator();
            $result = $validator->validateWithErrors('');

            expect($result->isValid())->toBeFalse()
                ->and($result->getErrors())->not->toBeEmpty();
        });

        test('validate whitespace only string', function (): void {
            $validator = new JMESPathValidator();

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
