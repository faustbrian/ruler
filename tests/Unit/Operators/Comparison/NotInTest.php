<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Comparison\NotIn;
use Cline\Ruler\Variables\Variable;

describe('NotIn', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $value = new Variable('value', 1);
            $array = new Variable('array', [1, 2, 3]);

            $op = new NotIn($value, $array);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $value = new Variable('value', 5);
            $array = new Variable('array', [1, 2, 3]);
            $context = new Context();

            $op = new NotIn($value, $array);
            expect($op->evaluate($context))->toBeTrue();

            $context['value'] = 2;
            expect($op->evaluate($context))->toBeFalse();

            $context['value'] = 'other';
            $context['array'] = ['test', 'foo', 'bar'];
            expect($op->evaluate($context))->toBeTrue();

            $context['value'] = '2';
            $context['array'] = [1, 2, 3];
            expect($op->evaluate($context))->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid array type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('NotIn: second operand must be an array');

            $value = new Variable('value', 1);
            $array = new Variable('array', 'not an array');
            $context = new Context();

            $op = new NotIn($value, $array);
            $op->evaluate($context);
        });
    });
});
