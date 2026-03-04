<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Type\IsBoolean;
use Cline\Ruler\Variables\Variable;

describe('IsBoolean', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', true);

            $op = new IsBoolean($var);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $var = new Variable('a', true);
            $context = new Context();

            $op = new IsBoolean($var);
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = false;
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 'true';
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 1;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 0;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = null;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = [];
            expect($op->evaluate($context))->toBeFalse();
        });
    });
});
