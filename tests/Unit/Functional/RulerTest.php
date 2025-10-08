<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Context;

describe('Ruler Functional Tests', function (): void {
    describe('Happy Paths', function (): void {
        test('applies de morgan law for logical or with negations', function ($p, $q): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q'));
            expect($rb->create(
                $rb->logicalOr(
                    $rb->logicalNot(
                        $rb['p']->equalTo(true),
                    ),
                    $rb->logicalNot(
                        $rb['q']->equalTo(true),
                    ),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalNot(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableTwo');

        test('applies de morgan law for logical and with negations', function ($p, $q): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q'));
            expect($rb->create(
                $rb->logicalAnd(
                    $rb->logicalNot(
                        $rb['p']->equalTo(true),
                    ),
                    $rb->logicalNot(
                        $rb['q']->equalTo(true),
                    ),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalNot(
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableTwo');

        test('applies commutative property for logical or', function ($p, $q): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q'));
            expect($rb->create(
                $rb->logicalOr(
                    $rb['q']->equalTo(true),
                    $rb['p']->equalTo(true),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb['q']->equalTo(true),
                ),
            )->evaluate($context));
        })->with('truthTableTwo');

        test('applies commutative property for logical and', function ($p, $q): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q'));
            expect($rb->create(
                $rb->logicalAnd(
                    $rb['q']->equalTo(true),
                    $rb['p']->equalTo(true),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb['q']->equalTo(true),
                ),
            )->evaluate($context));
        })->with('truthTableTwo');

        test('applies associative property for logical or', function ($p, $q, $r): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q', 'r'));
            expect($rb->create(
                $rb->logicalOr(
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true),
                    ),
                    $rb['r']->equalTo(true),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb->logicalOr(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableThree');

        test('applies associative property for logical and', function ($p, $q, $r): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q', 'r'));
            expect($rb->create(
                $rb->logicalAnd(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true),
                    ),
                    $rb['r']->equalTo(true),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb->logicalAnd(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableThree');

        test('applies distributive property for logical and over or', function ($p, $q, $r): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q', 'r'));
            expect($rb->create(
                $rb->logicalOr(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true),
                    ),
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['r']->equalTo(true),
                    ),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb->logicalOr(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableThree');

        test('applies distributive property for logical or over and', function ($p, $q, $r): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p', 'q', 'r'));
            expect($rb->create(
                $rb->logicalAnd(
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true),
                    ),
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['r']->equalTo(true),
                    ),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb->logicalAnd(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableThree');

        test('applies double negation law', function ($p): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p'));
            expect($rb->create(
                $rb->logicalNot(
                    $rb->logicalNot(
                        $rb['p']->equalTo(true),
                    ),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb['p']->equalTo(true),
            )->evaluate($context));
        })->with('truthTableOne');

        test('applies tautology law for logical or with same proposition', function ($p): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p'));
            expect($rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb['p']->equalTo(true),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb['p']->equalTo(true),
            )->evaluate($context));
        })->with('truthTableOne');

        test('applies tautology law for logical and with same proposition', function ($p): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p'));
            expect($rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb['p']->equalTo(true),
                ),
            )->evaluate($context))->toEqual($rb->create(
                $rb['p']->equalTo(true),
            )->evaluate($context));
        })->with('truthTableOne');

        test('validates excluded middle law', function ($p): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p'));
            expect(true)->toEqual($rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb->logicalNot(
                        $rb['p']->equalTo(true),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableOne');

        test('validates law of non contradiction', function ($p): void {
            $rb = new RuleBuilder();
            $context = new Context(compact('p'));
            expect(true)->toEqual($rb->create(
                $rb->logicalNot(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb->logicalNot(
                            $rb['p']->equalTo(true),
                        ),
                    ),
                ),
            )->evaluate($context));
        })->with('truthTableOne');
    });
});

dataset('truthTableOne', function () {
    return [
        [true],
        [false],
    ];
});

dataset('truthTableTwo', function () {
    return [
        [true,  true],
        [true,  false],
        [false, true],
        [false, false],
    ];
});

dataset('truthTableThree', function () {
    return [
        [true,  true,  true],
        [true,  true,  false],
        [true,  false, true],
        [true,  false, false],
        [false, true,  true],
        [false, true,  false],
        [false, false, true],
        [false, false, false],
    ];
});
