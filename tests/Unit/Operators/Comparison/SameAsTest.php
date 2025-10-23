<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Comparison\SameAs;
use Cline\Ruler\Variables\Variable;

describe('SameAs', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', 1);

            $op = new SameAs($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $varA = new Variable('a', 1);
            $varB = new Variable('b', 2);
            $context = new Context();

            $op = new SameAs($varA, $varB);
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 2;
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 3;
            $context['b'] = fn (): int => 3;
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 3;
            $context['b'] = '3';
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = new stdClass();
            $context['a']->attributes = 1;
            $context['b'] = new stdClass();
            $context['b']->attributes = 1;
            expect($op->evaluate($context))->toBeFalse();

            $context['b'] = $context['a'];
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 1;
            $context['b'] = true;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = null;
            $context['b'] = false;
            expect($op->evaluate($context))->toBeFalse();
        });
    });
});
