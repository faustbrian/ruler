<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Date\Before;
use Cline\Ruler\Variables\Variable;
use Illuminate\Support\Facades\Date;

describe('Before', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $varA = new Variable('a', '2024-01-01');
            $varB = new Variable('b', '2024-12-31');
            $op = new Before($varA, $varB);
            expect($op)->toBeInstanceOf(Proposition::class);
        });
        test('constructor and evaluation with strings', function (): void {
            $varA = new Variable('a', '2024-01-01');
            $varB = new Variable('b', '2024-12-31');
            $context = new Context();
            $op = new Before($varA, $varB);
            expect($op->evaluate($context))->toBeTrue();
            $context['a'] = '2025-01-01';
            expect($op->evaluate($context))->toBeFalse();
        });
        test('constructor and evaluation with Carbon', function (): void {
            $varA = new Variable('a', Date::parse('2024-01-01'));
            $varB = new Variable('b', Date::parse('2024-12-31'));
            $context = new Context();
            $op = new Before($varA, $varB);
            expect($op->evaluate($context))->toBeTrue();
            $context['a'] = Date::parse('2025-01-01');
            expect($op->evaluate($context))->toBeFalse();
        });
        test('constructor and evaluation with timestamps', function (): void {
            $varA = new Variable('a', 1_704_067_200); // 2024-01-01
            $varB = new Variable('b', 1_735_603_200); // 2024-12-31
            $context = new Context();
            $op = new Before($varA, $varB);
            expect($op->evaluate($context))->toBeTrue();
        });
        test('constructor and evaluation with DateTime objects', function (): void {
            $varA = new Variable('a', Date::parse('2024-01-10'));
            $varB = new Variable('b', Date::parse('2024-01-15'));
            $context = new Context();
            $op = new Before($varA, $varB);
            expect($op->evaluate($context))->toBeTrue();
            $context['a'] = Date::parse('2024-01-20');
            expect($op->evaluate($context))->toBeFalse();
        });
    });
    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Before: values must be valid date/time representations');
            $varA = new Variable('a', []);
            $varB = new Variable('b', '2024-01-01');
            $context = new Context();
            $op = new Before($varA, $varB);
            $op->evaluate($context);
        });
    });
});
