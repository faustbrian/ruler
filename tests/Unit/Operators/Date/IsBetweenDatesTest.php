<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Carbon\CarbonImmutable;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Date\IsBetweenDates;
use Cline\Ruler\Variables\Variable;
use Illuminate\Support\Facades\Date;

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
            $date = new Variable('date', Date::parse('2024-06-15'));
            $start = new Variable('start', Date::parse('2024-01-01'));
            $end = new Variable('end', Date::parse('2024-12-31'));
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = Date::parse('2023-12-31');
            expect($op->evaluate($context))->toBeFalse();
        });

        test('constructor and evaluation with DateTime objects', function (): void {
            $date = new Variable('date', Date::parse('2024-01-12'));
            $start = new Variable('start', Date::parse('2024-01-10'));
            $end = new Variable('end', Date::parse('2024-01-15'));
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = Date::parse('2024-01-09');
            expect($op->evaluate($context))->toBeFalse();

            $context['date'] = Date::parse('2024-01-16');
            expect($op->evaluate($context))->toBeFalse();
        });

        test('handles DateTimeImmutable objects', function (): void {
            $date = new Variable('date', CarbonImmutable::parse('2024-06-15'));
            $start = new Variable('start', CarbonImmutable::parse('2024-01-01'));
            $end = new Variable('end', CarbonImmutable::parse('2024-12-31'));
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = CarbonImmutable::parse('2023-12-31');
            expect($op->evaluate($context))->toBeFalse();
        });

        test('handles mixed date formats', function (): void {
            $date = new Variable('date', Date::parse('2024-06-15'));
            $start = new Variable('start', '2024-01-01');
            $end = new Variable('end', Date::parse('2024-12-31'));
            $context = new Context();

            $op = new IsBetweenDates($date, $start, $end);
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = CarbonImmutable::parse('2023-12-31');
            expect($op->evaluate($context))->toBeFalse();

            $context['date'] = '2024-07-01';
            expect($op->evaluate($context))->toBeTrue();

            $context['date'] = Date::parse('2024-08-15');
            expect($op->evaluate($context))->toBeTrue();
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
