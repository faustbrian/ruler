<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\Type\ArrayCount;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;

describe('ArrayCount', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $var = new Variable('a', [1, 2, 3]);

            $op = new ArrayCount($var);
            expect($op)->toBeInstanceOf(VariableOperand::class);
        });

        test('array count calculation', function (): void {
            $var = new Variable('a', [1, 2, 3]);
            $context = new Context();

            $op = new ArrayCount($var);
            expect($op->prepareValue($context)->getValue())->toBe(3);

            $context['a'] = [];
            expect($op->prepareValue($context)->getValue())->toBe(0);

            $context['a'] = ['key1' => 'value1', 'key2' => 'value2'];
            expect($op->prepareValue($context)->getValue())->toBe(2);
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('ArrayCount: value must be an array or countable');

            $var = new Variable('a', 'string');
            $context = new Context();

            $op = new ArrayCount($var);
            $op->prepareValue($context);
        });
    });
});
