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
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Variables\Variable;

describe('GreaterThan', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', 2);

            $op = new GreaterThan($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', 2);
            $context = new Context();

            $op = new GreaterThan($varA, $varB);
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 2;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 3;
            $context['b'] = function () {
                return 0;
            };
            expect($op->evaluate($context))->toBeTrue();
        });
    });
});
