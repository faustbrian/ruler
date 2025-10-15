<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\Mathematical\Division;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Division', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', [2]);

            $op = new Division($varA, $varB);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('division', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new Division($varA, $varB);
            expect($result)->toEqual($op->prepareValue($context)->getValue());
        })->with('divisionData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Arithmetic: values must be numeric');
            $varA = new Variable('a', 'string');
            $varB = new Variable('b', 'blah');
            $context = new Context();

            $op = new Division($varA, $varB);
            $op->prepareValue($context);
        });
    });

    describe('Edge Cases', function (): void {
        test('divide by zero', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Division by zero');
            $varA = new Variable('a', random_int(1, 100));
            $varB = new Variable('b', 0);
            $context = new Context();

            $op = new Division($varA, $varB);
            $op->prepareValue($context);
        });
    });
});

dataset('divisionData', fn (): array => [
    [6, 2, 3],
    [7.5, 2.5, 3.0],
]);
