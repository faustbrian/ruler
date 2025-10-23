<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\Mathematical\Abs;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Abs', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', -5);

            $op = new Abs($var);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('absolute value calculation', function (): void {
            $var = new Variable('a', -5);
            $context = new Context();

            $op = new Abs($var);
            expect($op->prepareValue($context)->getValue())->toBe(5);

            $context['a'] = 5;
            expect($op->prepareValue($context)->getValue())->toBe(5);

            $context['a'] = -10.5;
            expect($op->prepareValue($context)->getValue())->toBe(10.5);

            $context['a'] = 0;
            expect($op->prepareValue($context)->getValue())->toBe(0);
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Abs: value must be numeric');

            $var = new Variable('a', 'string');
            $context = new Context();

            $op = new Abs($var);
            $op->prepareValue($context);
        });
    });
});
