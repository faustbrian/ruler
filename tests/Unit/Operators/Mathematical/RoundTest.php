<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\Mathematical\Round;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Round', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $value = new Variable('value', 5.5);

            $op = new Round($value);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('round without precision', function (): void {
            $value = new Variable('value', 5.5);
            $context = new Context();

            $op = new Round($value);
            expect($op->prepareValue($context)->getValue())->toBe(6.0);

            $context['value'] = 5.4;
            expect($op->prepareValue($context)->getValue())->toBe(5.0);

            $context['value'] = -5.5;
            expect($op->prepareValue($context)->getValue())->toBe(-6.0);
        });

        test('round with precision', function (): void {
            $value = new Variable('value', 5.567);
            $precision = new Variable('precision', 2);
            $context = new Context();

            $op = new Round($value, $precision);
            expect($op->prepareValue($context)->getValue())->toBe(5.57);

            $context['precision'] = 1;
            expect($op->prepareValue($context)->getValue())->toBe(5.6);

            $context['precision'] = 0;
            expect($op->prepareValue($context)->getValue())->toBe(6.0);
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Round: value must be numeric');

            $value = new Variable('value', 'string');
            $context = new Context();

            $op = new Round($value);
            $op->prepareValue($context);
        });

        test('invalid precision type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Round: precision must be numeric');

            $value = new Variable('value', 5.5);
            $precision = new Variable('precision', 'string');
            $context = new Context();

            $op = new Round($value, $precision);
            $op->prepareValue($context);
        });
    });
});
