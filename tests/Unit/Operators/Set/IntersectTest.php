<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\Set\Intersect;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('Intersect', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', [2]);

            $op = new Intersect($varA, $varB);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('intersect', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new Intersect($varA, $varB);
            expect($op->prepareValue($context)->getValue())->toEqual($result);
        })->with('intersectData');
    });

    describe('Sad Paths', function (): void {
        test('invalid data', function (): void {
            $varA = new Variable('a', 'string');
            $varB = new Variable('b', 'blah');
            $context = new Context();

            $op = new Intersect($varA, $varB);
            expect($op->prepareValue($context)->getValue())->toEqual([]);
        });
    });
});

dataset('intersectData', fn (): array => [
    [6, 2, []],
    [
        ['a', 'c'],
        'a',
        ['a'],
    ],
    [
        ['a', 'b', 'c'],
        [],
        [],
    ],
    [
        [],
        ['a', 'b', 'c'],
        [],
    ],
    [
        [],
        [],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['d', 'e', 'f'],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
    ],
    [
        ['a', 'b', 'c'],
        ['b', 'c'],
        ['b', 'c'],
    ],
    [
        ['b', 'c'],
        ['b', 'd'],
        ['b'],
    ],
]);
