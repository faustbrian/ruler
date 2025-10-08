<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Mathematical\Max;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Max', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', [5, 2, 9]);

            $op = new Max($var);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('max', function ($a, $result): void {
            $var = new Variable('a', $a);
            $context = new Context();

            $op = new Max($var);
            expect($op->prepareValue($context)->getValue())->toEqual($result);
        })->with('maxData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function ($datum): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('max: all values must be numeric');
            $var = new Variable('a', $datum);
            $context = new Context();

            $op = new Max($var);
            $op->prepareValue($context);
        })->with('invalidData');
    });
});

dataset('invalidData', function () {
    return [
        ['string'],
        [['string']],
        [[1, 2, 3, 'string']],
        [['string', 1, 2, 3]],
    ];
});
dataset('maxData', function () {
    return [
        [5, 5],
        [[], null],
        [[5], 5],
        [[-2, -5, -242], -2],
        [[2, 5, 242], 242],
    ];
});
