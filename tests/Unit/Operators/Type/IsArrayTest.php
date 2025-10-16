<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Type\IsArray;
use Cline\Ruler\Variables\Variable;

describe('IsArray', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', []);

            $op = new IsArray($var);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $var = new Variable('a', [1, 2, 3]);
            $context = new Context();

            $op = new IsArray($var);
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = [];
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = ['key' => 'value'];
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 'string';
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 123;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = true;
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = null;
            expect($op->evaluate($context))->toBeFalse();
        });
    });
});
