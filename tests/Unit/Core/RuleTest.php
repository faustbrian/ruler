<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Core\Rule;
use Tests\Fixtures\CallbackProposition;
use Tests\Fixtures\TrueProposition;

describe('Rule', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $rule = new Rule(
                new TrueProposition(),
            );
            expect($rule)->toBeInstanceOf(Proposition::class);
        });

        test('constructor evaluation and execution', function (): void {
            $test = $this;
            $context = new Context();
            $executed = false;
            $actionExecuted = false;

            $ruleOne = new Rule(
                new CallbackProposition(function ($c) use ($test, $context, &$executed): false {
                    $test->assertSame($c, $context);
                    $executed = true;

                    return false;
                }),
                function () use (&$actionExecuted): void {
                    $actionExecuted = true;
                },
            );

            expect($ruleOne->evaluate($context))->toBeFalse();
            expect($executed)->toBeTrue();

            $ruleOne->execute($context);
            expect($actionExecuted)->toBeFalse();

            $executed = false;
            $actionExecuted = false;

            $ruleTwo = new Rule(
                new CallbackProposition(function ($c) use ($test, $context, &$executed): true {
                    $test->assertSame($c, $context);
                    $executed = true;

                    return true;
                }),
                function () use (&$actionExecuted): void {
                    $actionExecuted = true;
                },
            );

            expect($ruleTwo->evaluate($context))->toBeTrue();
            expect($executed)->toBeTrue();

            $ruleTwo->execute($context);
            expect($actionExecuted)->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
        test('non callable actions will throw an exception', function (): void {
            $this->expectException(LogicException::class);
            $context = new Context();
            $rule = new Rule(
                new TrueProposition(),
                'this is not callable',
            );
            $rule->execute($context);
        });
    });
});
