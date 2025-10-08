<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Logical\LogicalNand;
use Cline\Ruler\Variables\Variable;

describe('LogicalNand', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', 1);
            $propA = new EqualTo($varA, $varB);

            $op = new LogicalNand([$propA]);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('logical nand evaluation', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', 2);
            $varC = new Variable('c', 3);
            $context = new Context();

            $propA = new EqualTo($varA, $varB);
            $propB = new EqualTo($varB, $varC);

            $op = new LogicalNand([$propA, $propB]);
            expect($op->evaluate($context))->toBeTrue();

            $context['b'] = 1;
            expect($op->evaluate($context))->toBeTrue();

            $context['c'] = 1;
            expect($op->evaluate($context))->toBeFalse();

            $context['b'] = 2;
            expect($op->evaluate($context))->toBeTrue();
        });
    });
});
