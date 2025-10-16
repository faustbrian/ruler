<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Variables\Variable;

describe('Between', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $value = new Variable('value', 5);
            $min = new Variable('min', 1);
            $max = new Variable('max', 10);

            $op = new Between($value, $min, $max);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $value = new Variable('value', 5);
            $min = new Variable('min', 1);
            $max = new Variable('max', 10);
            $context = new Context();

            $op = new Between($value, $min, $max);
            expect($op->evaluate($context))->toBeTrue();

            $context['value'] = 0;
            expect($op->evaluate($context))->toBeFalse();

            $context['value'] = 11;
            expect($op->evaluate($context))->toBeFalse();

            $context['value'] = 1;
            expect($op->evaluate($context))->toBeTrue();

            $context['value'] = 10;
            expect($op->evaluate($context))->toBeTrue();

            $context['value'] = 5.5;
            expect($op->evaluate($context))->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Between: all values must be numeric');

            $value = new Variable('value', 'string');
            $min = new Variable('min', 1);
            $max = new Variable('max', 10);
            $context = new Context();

            $op = new Between($value, $min, $max);
            $op->evaluate($context);
        });
    });
});
