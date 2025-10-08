<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Mathematical\Min;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Min', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', [5, 2, 9]);

            $op = new Min($var);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('min', function ($a, $result): void {
            $var = new Variable('a', $a);
            $context = new Context();

            $op = new Min($var);
            expect($op->prepareValue($context)->getValue())->toEqual($result);
        })->with('minData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function ($datum): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('min: all values must be numeric');
            $var = new Variable('a', $datum);
            $context = new Context();

            $op = new Min($var);
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
dataset('minData', function () {
    return [
        [5, 5],
        [[], null],
        [[5], 5],
        [[-2, -5, -242], -242],
        [[2, 5, 242], 2],
    ];
});
