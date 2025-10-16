<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Type\IsNumeric;
use Cline\Ruler\Variables\Variable;

describe('IsNumeric', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', 123);

            $op = new IsNumeric($var);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $var = new Variable('a', 123);
            $context = new Context();

            $op = new IsNumeric($var);
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 45.67;
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = '123';
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = '45.67';
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 'not numeric';
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = [];
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = true;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = null;
            expect($op->evaluate($context))->toBeFalse();
        });
    });
});
