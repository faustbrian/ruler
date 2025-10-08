<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Mathematical\Ceil;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Ceil', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);

            $op = new Ceil($varA);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('ceiling', function ($a, $result): void {
            $varA = new Variable('a', $a);
            $context = new Context();

            $op = new Ceil($varA);
            expect($result)->toEqual($op->prepareValue($context)->getValue());
        })->with('ceilingData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Arithmetic: values must be numeric');
            $varA = new Variable('a', 'string');
            $context = new Context();

            $op = new Ceil($varA);
            $op->prepareValue($context);
        });
    });
});

dataset('ceilingData', function () {
    return [
        [1.2, 2],
        [1.0, 1],
        [1, 1],
        [-0.5, 0],
        [-1.5, -1],
    ];
});
