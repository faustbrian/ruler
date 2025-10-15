<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Variables\Variable;

describe('StringContains', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', [2]);

            $op = new StringContains($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('contains', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new StringContains($varA, $varB);
            expect($result)->toEqual($op->evaluate($context));
        })->with('containsData');

        test('does not contain', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new StringDoesNotContain($varA, $varB);
            $this->assertNotEquals($op->evaluate($context), $result);
        })->with('containsData');
    });
});

dataset('containsData', fn (): array => [
    ['supercalifragilistic', 'super', true],
    ['supercalifragilistic', 'fragil', true],
    ['supercalifragilistic', 'a', true],
    ['supercalifragilistic', 'stic', true],
    ['timmy', 'bob', false],
    ['tim', 'TIM', false],
]);
