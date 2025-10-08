<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Variables\Variable;

describe('StartsWith', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 'foo bar baz');
            $varB = new Variable('b', 'foo');

            $op = new StartsWith($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('starts with', function ($a, $b, $result): void {
            $varA = new Variable('a', $a);
            $varB = new Variable('b', $b);
            $context = new Context();

            $op = new StartsWith($varA, $varB);
            expect($result)->toEqual($op->evaluate($context));
        })->with('startsWithData');
    });
});

dataset('startsWithData', function () {
    return [
        ['supercalifragilistic', 'supercalifragilistic', true],
        ['supercalifragilistic', 'super', true],
        ['supercalifragilistic', 'SUPER', false],
        ['supercalifragilistic', 'stic', false],
        ['supercalifragilistic', '', false],
    ];
});
