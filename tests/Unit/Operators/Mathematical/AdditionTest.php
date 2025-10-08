<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Addition', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', [2]);

            $op = new Addition($varA, $varB);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('addition', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new Addition($varA, $varB);
            expect($result)->toEqual($op->prepareValue($context)->getValue());
        })->with('additionData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Arithmetic: values must be numeric');
            $varA = new Variable('a', 'string');
            $varB = new Variable('b', 'blah');
            $context = new Context();

            $op = new Addition($varA, $varB);
            $op->prepareValue($context);
        });
    });
});

dataset('additionData', function () {
    return [
        [1, 2, 3],
        [2.5, 3.8, 6.3],
    ];
});
