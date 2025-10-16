<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Set\ContainsSubset;
use Cline\Ruler\Operators\Set\DoesNotContainSubset;
use Cline\Ruler\Variables\Variable;

describe('ContainsSubset', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', [2]);

            $op = new ContainsSubset($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('contains', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new ContainsSubset($varA, $varB);
            expect($result)->toEqual($op->evaluate($context));
        })->with('containsData');

        test('does not contain', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new DoesNotContainSubset($varA, $varB);
            $this->assertNotEquals($op->evaluate($context), $result);
        })->with('containsData');
    });
});

dataset('containsData', fn (): array => [
    [[1], [1], true],
    [[1], 1, true],
    [[1, 2, 3], [1, 2], true],
    [[1, 2, 3], [2, 4], false],
    [['foo', 'bar', 'baz'], ['pow'], false],
    [['foo', 'bar', 'baz'], ['bar'], true],
    [['foo', 'bar', 'baz'], ['bar', 'baz'], true],
    [null, 'bar', false],
    [null, ['bar'], false],
    [null, ['bar', 'baz'], false],
    [null, null, true],
    [[], [], true],
    [[1, 2, 3], [2], true],
]);
