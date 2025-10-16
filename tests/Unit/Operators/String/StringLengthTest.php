<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\String\StringLength;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('StringLength', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', 'test');

            $op = new StringLength($var);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('string length calculation', function (): void {
            $var = new Variable('a', 'hello');
            $context = new Context();

            $op = new StringLength($var);
            expect($op->prepareValue($context)->getValue())->toBe(5);

            $context['a'] = '';
            expect($op->prepareValue($context)->getValue())->toBe(0);

            $context['a'] = 'hello world';
            expect($op->prepareValue($context)->getValue())->toBe(11);
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('StringLength: value must be a string');

            $var = new Variable('a', 123);
            $context = new Context();

            $op = new StringLength($var);
            $op->prepareValue($context);
        });
    });
});
