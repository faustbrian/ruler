<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Tests\Fixtures\FalseProposition;
use Tests\Fixtures\TrueProposition;

describe('LogicalNot', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $true = new TrueProposition();

            $op = new LogicalNot([$true]);
            expect($op)->toBeInstanceOf(Proposition::class);
        });

        test('constructor', function (): void {
            $op = new LogicalNot([new FalseProposition()]);
            expect($op->evaluate(
                new Context(),
            ))->toBeTrue();
        });

        test('add proposition and evaluate', function (): void {
            $op = new LogicalNot();

            $op->addProposition(
                new TrueProposition(),
            );
            expect($op->evaluate(
                new Context(),
            ))->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('executing alogical not without propositions throws an exception', function (): void {
            $this->expectException(LogicException::class);
            $op = new LogicalNot();
            $op->evaluate(
                new Context(),
            );
        });

        test('instantiating alogical not with too many arguments throws an exception', function (): void {
            $this->expectException(LogicException::class);
            $op = new LogicalNot([new TrueProposition(), new FalseProposition()]);
        });

        test('adding asecond proposition to logical not throws an exception', function (): void {
            $this->expectException(LogicException::class);
            $op = new LogicalNot();
            $op->addProposition(
                new TrueProposition(),
            );
            $op->addProposition(
                new TrueProposition(),
            );
        });
    });
});
