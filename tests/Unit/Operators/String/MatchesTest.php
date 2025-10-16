<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Variables\Variable;

describe('Matches', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', 'test');
            $varB = new Variable('b', '/test/');

            $op = new Matches($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation', function (): void {
            $varA = new Variable('a', 'hello world');
            $varB = new Variable('b', '/world/');
            $context = new Context();

            $op = new Matches($varA, $varB);
            expect($op->evaluate($context))->toBeTrue();

            $context['a'] = 'goodbye';
            expect($op->evaluate($context))->toBeFalse();

            $context['a'] = 'test@example.com';
            $context['b'] = '/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i';
            expect($op->evaluate($context))->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Matches: value must be a string');

            $varA = new Variable('a', 123);
            $varB = new Variable('b', '/test/');
            $context = new Context();

            $op = new Matches($varA, $varB);
            $op->evaluate($context);
        });

        test('invalid pattern type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Matches: pattern must be a string');

            $varA = new Variable('a', 'test');
            $varB = new Variable('b', 123);
            $context = new Context();

            $op = new Matches($varA, $varB);
            $op->evaluate($context);
        });
    });
});
