<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Mathematical\Floor;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Floor', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);

            $op = new Floor($varA);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('ceiling', function ($a, $result): void {
            $varA = new Variable('a', $a);
            $context = new Context();

            $op = new Floor($varA);
            expect($result)->toEqual($op->prepareValue($context)->getValue());
        })->with('ceilingData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Arithmetic: values must be numeric');
            $varA = new Variable('a', 'string');
            $context = new Context();

            $op = new Floor($varA);
            $op->prepareValue($context);
        });
    });
});

dataset('ceilingData', function () {
    return [
        [1.2, 1],
        [1.0, 1],
        [1, 1],
        [-0.5, -1],
        [-1.5, -2],
    ];
});
