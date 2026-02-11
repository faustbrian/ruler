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
use Cline\Ruler\Core\RuleExecutionResult;
use Cline\Ruler\Core\RuleIds;
use Tests\Fixtures\CallbackProposition;
use Tests\Fixtures\TrueProposition;

describe('Rule', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $rule = new Rule(
                new TrueProposition(),
                null,
                RuleIds::fromString('rule-interface'),
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
                function ($context) use (&$actionExecuted): void {
                    $actionExecuted = true;
                },
                RuleIds::fromString('rule-one'),
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
                function ($context) use (&$actionExecuted): void {
                    $actionExecuted = true;
                },
                RuleIds::fromString('rule-two'),
            );

            expect($ruleTwo->evaluate($context))->toBeTrue();
            expect($executed)->toBeTrue();

            $ruleTwo->execute($context);
            expect($actionExecuted)->toBeTrue();
        });

        test('exposes rule metadata and salience fields', function (): void {
            $rule = new Rule(
                new TrueProposition(),
                null,
                RuleIds::fromString('rule-1'),
                'Adult Access',
                50,
                true,
                ['domain' => 'auth', 'tier' => 'gold'],
            );

            expect($rule->getRuleId())->toEqual(RuleIds::fromString('rule-1'));
            expect($rule->getName())->toBe('Adult Access');
            expect($rule->getPriority())->toBe(50);
            expect($rule->isEnabled())->toBeTrue();
            expect($rule->getMetadata())->toBe(['domain' => 'auth', 'tier' => 'gold']);
            expect($rule->getMetadataValue('domain'))->toBe('auth');
            expect($rule->getMetadataValue('unknown'))->toBeNull();
        });

        test('disabled rule does not evaluate true', function (): void {
            $rule = new Rule(
                new TrueProposition(),
                null,
                RuleIds::fromString('rule-disabled'),
                'Disabled Rule',
                1,
                false,
            );

            expect($rule->evaluate(
                new Context(),
            ))->toBeFalse();
        });

        test('execute returns structured execution details', function (): void {
            $context = new Context();
            $actionExecuted = false;

            $rule = new Rule(
                new TrueProposition(),
                function ($context) use (&$actionExecuted): void {
                    $actionExecuted = true;
                },
                RuleIds::fromString('r-100'),
                'Structured Rule',
                25,
            );

            $result = $rule->execute($context);

            expect($result)->toBeInstanceOf(RuleExecutionResult::class);
            expect($result->ruleId)->toBe('r-100');
            expect($result->ruleName)->toBe('Structured Rule');
            expect($result->priority)->toBe(25);
            expect($result->matched)->toBeTrue();
            expect($result->actionExecuted)->toBeTrue();
            expect($actionExecuted)->toBeTrue();
        });

        test('action can receive context when callback expects parameter', function (): void {
            $capturedContext = null;
            $context = new Context(['userId' => 99]);

            $rule = new Rule(
                new TrueProposition(),
                function (Context $ctx) use (&$capturedContext): void {
                    $capturedContext = $ctx;
                },
                RuleIds::fromString('rule-context'),
            );

            $result = $rule->execute($context);

            expect($result->matched)->toBeTrue();
            expect($result->actionExecuted)->toBeTrue();
            expect($capturedContext)->toBe($context);
        });

        test('rule keeps explicitly assigned identifier', function (): void {
            $manual = new Rule(
                new TrueProposition(),
                null,
                RuleIds::fromString('manual-id'),
            );

            expect($manual->getRuleId())->toEqual(RuleIds::fromString('manual-id'));
        });
    });

    describe('Sad Paths', function (): void {
        test('non-closure actions are rejected at construction time', function (): void {
            $this->expectException(TypeError::class);

            new Rule(
                new TrueProposition(),
                'this is not callable',
                RuleIds::fromString('bad-action'),
            );
        });

        test('missing rule id is rejected', function (): void {
            $this->expectException(TypeError::class);

            new Rule(
                new TrueProposition(),
                null,
            );
        });

        test('empty rule id is rejected', function (): void {
            $this->expectException(RuntimeException::class);

            new Rule(
                new TrueProposition(),
                null,
                RuleIds::fromString(''),
            );
        });
    });
});
