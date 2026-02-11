<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Context;

describe('Set', function (): void {
    describe('Happy Paths', function (): void {
        test('complicated', function (): void {
            $rb = new RuleBuilder();
            $context = new Context([
                'expected' => 'a',
                'foo' => ['a', 'z'],
                'bar' => ['z', 'b'],
                'baz' => ['a', 'z', 'b', 'q'],
                'bob' => ['a', 'd'],
            ]);

            expect($rb->create(
                $rb['foo']->intersect(
                    $rb['bar']->symmetricDifference($rb['baz']),
                )->setContains($rb['expected']),
                'set-complicated-1',
            )->evaluate($context))->toBeTrue();

            expect($rb->create(
                $rb['bar']->union(
                    $rb['bob'],
                )->containsSubset($rb['foo']),
                'set-complicated-2',
            )->evaluate($context))->toBeTrue();
        });

        test('union', function ($a, $b, $expected): void {
            $rb = new RuleBuilder();
            $context = new Context(['a' => $a, 'b' => $b, 'expected' => $expected]);
            expect($rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->union($rb['b']),
                ),
                'set-union',
            )->evaluate($context))->toBeTrue();
        })->with('setUnion');

        test('intersect', function ($a, $b, $expected): void {
            $rb = new RuleBuilder();
            $context = new Context(['a' => $a, 'b' => $b, 'expected' => $expected]);
            expect($rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->intersect($rb['b']),
                ),
                'set-intersect',
            )->evaluate($context))->toBeTrue();
        })->with('setIntersect');

        test('complement', function ($a, $b, $expected): void {
            $rb = new RuleBuilder();
            $context = new Context(['a' => $a, 'b' => $b, 'expected' => $expected]);
            expect($rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->complement($rb['b']),
                ),
                'set-complement',
            )->evaluate($context))->toBeTrue();
        })->with('setComplement');

        test('symmetric difference', function ($a, $b, $expected): void {
            $rb = new RuleBuilder();
            $context = new Context(['a' => $a, 'b' => $b, 'expected' => $expected]);
            expect($rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->symmetricDifference($rb['b']),
                ),
                'set-symmetric-difference',
            )->evaluate($context))->toBeTrue();
        })->with('setSymmetricDifference');
    });
});

dataset('setUnion', fn (): array => [
    [
        ['a', 'b', 'c'],
        [],
        ['a', 'b', 'c'],
    ],
    [
        [],
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
    ],
    [
        [],
        [],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['d', 'e', 'f'],
        ['a', 'b', 'c', 'd', 'e', 'f'],
    ],
    [
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
    ],
    [
        ['a', 'b', 'c'],
        ['b', 'c'],
        ['a', 'b', 'c'],
    ],
    [
        ['b', 'c'],
        ['b', 'd'],
        ['b', 'c', 'd'],
    ],
]);
dataset('setIntersect', fn (): array => [
    [
        ['a', 'b', 'c'],
        [],
        [],
    ],
    [
        [],
        ['a', 'b', 'c'],
        [],
    ],
    [
        [],
        [],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['d', 'e', 'f'],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
    ],
    [
        ['a', 'b', 'c'],
        ['b', 'c'],
        ['b', 'c'],
    ],
    [
        ['b', 'c'],
        ['b', 'd'],
        ['b'],
    ],
]);
dataset('setComplement', fn (): array => [
    [
        ['a', 'b', 'c'],
        [],
        ['a', 'b', 'c'],
    ],
    [
        [],
        ['a', 'b', 'c'],
        [],
    ],
    [
        [],
        [],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['d', 'e', 'f'],
        ['a', 'b', 'c'],
    ],
    [
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['b', 'c'],
        ['a'],
    ],
    [
        ['b', 'c'],
        ['b', 'd'],
        ['c'],
    ],
]);
dataset('setSymmetricDifference', fn (): array => [
    [
        ['a', 'b', 'c'],
        [],
        ['a', 'b', 'c'],
    ],
    [
        [],
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
    ],
    [
        [],
        [],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['d', 'e', 'f'],
        ['a', 'b', 'c', 'd', 'e', 'f'],
    ],
    [
        ['a', 'b', 'c'],
        ['a', 'b', 'c'],
        [],
    ],
    [
        ['a', 'b', 'c'],
        ['b', 'c'],
        ['a'],
    ],
    [
        ['b', 'c'],
        ['b', 'd'],
        ['c', 'd'],
    ],
]);
