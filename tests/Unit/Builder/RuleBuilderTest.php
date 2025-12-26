<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\Logical\LogicalXor;
use Tests\Fixtures\FalseProposition;
use Tests\Fixtures\TrueProposition;

describe('RuleBuilder', function (): void {
    describe('Happy Paths', function (): void {
        test('interface', function (): void {
            $rb = new RuleBuilder();
            expect($rb)->toBeInstanceOf(RuleBuilder::class);
            expect($rb)->toBeInstanceOf(ArrayAccess::class);
        });

        test('manipulate variables via array access', function (): void {
            $name = 'alpha';
            $rb = new RuleBuilder();

            expect($rb->offsetExists($name))->toBeFalse();

            $var = $rb[$name];
            expect($rb->offsetExists($name))->toBeTrue();

            expect($var)->toBeInstanceOf(BuilderVariable::class);
            expect($var->getName())->toEqual($name);

            expect($rb[$name])->toBe($var);
            expect($var->getValue())->toBeNull();

            $rb[$name] = 'eeesh.';
            expect($var->getValue())->toEqual('eeesh.');

            unset($rb[$name]);
            expect($rb->offsetExists($name))->toBeFalse();
            $this->assertNotSame($var, $rb[$name]);
        });

        test('logical operator generation', function (): void {
            $rb = new RuleBuilder();
            $context = new Context();

            $true = new TrueProposition();
            $false = new FalseProposition();

            expect($rb->logicalAnd($true, $false))->toBeInstanceOf(LogicalAnd::class);
            expect($rb->logicalAnd($true, $false)->evaluate($context))->toBeFalse();

            expect($rb->logicalOr($true, $false))->toBeInstanceOf(LogicalOr::class);
            expect($rb->logicalOr($true, $false)->evaluate($context))->toBeTrue();

            expect($rb->logicalNot($true))->toBeInstanceOf(LogicalNot::class);
            expect($rb->logicalNot($true)->evaluate($context))->toBeFalse();

            expect($rb->logicalXor($true, $false))->toBeInstanceOf(LogicalXor::class);
            expect($rb->logicalXor($true, $false)->evaluate($context))->toBeTrue();
        });

        test('rule creation', function (): void {
            $rb = new RuleBuilder();
            $context = new Context();

            $true = new TrueProposition();
            $false = new FalseProposition();

            expect($rb->create($true))->toBeInstanceOf(Rule::class);
            expect($rb->create($true)->evaluate($context))->toBeTrue();
            expect($rb->create($false)->evaluate($context))->toBeFalse();

            $executed = false;
            $rule = $rb->create($true, function () use (&$executed): void {
                $executed = true;
            });

            expect($executed)->toBeFalse();
            $rule->execute($context);
            expect($executed)->toBeTrue();
        });

        test('not add equal to', function (): void {
            $rb = new RuleBuilder();
            $context = new Context([
                'A2' => 8,
                'A3' => 4,
                'B2' => 13,
            ]);

            $rule = $rb->logicalNot(
                $rb['A2']->equalTo($rb['B2']),
            );
            expect($rule->evaluate($context))->toBeTrue();

            $rule = $rb['A2']->add($rb['A3']);

            $rule = $rb->logicalNot(
                $rule->equalTo($rb['B2']),
            );
            expect($rule->evaluate($context))->toBeTrue();
        });

        test('external operators', function (): void {
            $rb = new RuleBuilder();
            $rb->registerOperatorNamespace('\Tests\Fixtures');

            $context = new Context(['a' => 100]);
            $varA = $rb['a'];

            expect($varA->aLotGreaterThan(1)->evaluate($context))->toBeTrue();

            $context['a'] = 9;
            expect($varA->aLotGreaterThan(1)->evaluate($context))->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('logic exception on unknown operator', function (): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Unknown operator: "aLotBiggerThan"');
            $rb = new RuleBuilder();
            $rb->registerOperatorNamespace('\Tests\Fixtures');

            $varA = $rb['a'];

            $varA->aLotBiggerThan(1);
        });
    });
});
