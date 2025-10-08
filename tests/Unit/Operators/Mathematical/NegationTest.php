<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Mathematical\Negation;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Negation', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);

            $op = new Negation($varA);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('subtract', function ($a, $result): void {
            $varA = new Variable('a', $a);
            $context = new Context();

            $op = new Negation($varA);
            expect($result)->toEqual($op->prepareValue($context)->getValue());
        })->with('negateData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Arithmetic: values must be numeric');
            $varA = new Variable('a', 'string');
            $context = new Context();

            $op = new Negation($varA);
            $op->prepareValue($context);
        });
    });
});

dataset('negateData', function () {
    return [
        [1, -1],
        [0.0, 0.0],
        ['0', 0],
        [-62_834, 62_834],
    ];
});
