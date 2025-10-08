<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operator;
use Cline\Ruler\Operators\Logical\LogicalXor;
use Tests\Fixtures\FalseProposition;
use Tests\Fixtures\TrueProposition;

describe('LogicalXor', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $true = new TrueProposition();

            $op = new LogicalXor([$true]);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor', function (): void {
            $true = new TrueProposition();
            $false = new FalseProposition();
            $context = new Context();

            $op = new LogicalXor([$true, $false]);
            expect($op->evaluate($context))->toBeTrue();
        });

        test('add proposition and evaluate', function (): void {
            $true = new TrueProposition();
            $false = new FalseProposition();
            $context = new Context();

            $op = new LogicalXor();

            $op->addProposition($false);
            expect($op->evaluate($context))->toBeFalse();

            $op->addOperand($false);
            expect($op->evaluate($context))->toBeFalse();

            $op->addProposition($true);
            expect($op->evaluate($context))->toBeTrue();

            $op->addOperand($true);
            expect($op->evaluate($context))->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('executing alogical xor without propositions throws an exception', function (): void {
            $this->expectException(LogicException::class);
            $op = new LogicalXor();
            $op->evaluate(
                new Context(),
            );
        });
    });
});
