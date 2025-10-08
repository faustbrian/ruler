<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Carbon\Carbon;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Date\IsBetweenDates;
use Cline\Ruler\Variables\Variable;

describe('IsBetweenDates', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $date = new Variable('date', '2024-06-15');
            $start = new Variable('start', '2024-01-01');
            $end = new Variable('end', '2024-12-31');

            $op = new IsBetweenDates($date, $start, $end);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor and evaluation with strings', function (): void {
            $date = new Variable('date', '2024-06-15');
            $start = new Variable('start', '2024-01-01');
            $end = new Variable('end', '2024-12-31');
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = '2023-12-31';
            expect($op->evaluate($context))->toBeFalse();

            $context['date'] = '2025-01-01';
            expect($op->evaluate($context))->toBeFalse();

            $context['date'] = '2024-01-01';
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = '2024-12-31';
            expect($op->evaluate($context))->toBeTrue();
        });

        test('constructor and evaluation with Carbon', function (): void {
            $date = new Variable('date', Carbon::parse('2024-06-15'));
            $start = new Variable('start', Carbon::parse('2024-01-01'));
            $end = new Variable('end', Carbon::parse('2024-12-31'));
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = Carbon::parse('2023-12-31');
            expect($op->evaluate($context))->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('invalid value type', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('IsBetweenDates: values must be valid date/time representations');

            $date = new Variable('date', []);
            $start = new Variable('start', '2024-01-01');
            $end = new Variable('end', '2024-12-31');
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            $op->evaluate($context);
        });
    });
});
