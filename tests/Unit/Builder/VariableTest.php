<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Builder\VariableProperty;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Operators\Mathematical\Ceil;
use Cline\Ruler\Operators\Mathematical\Division;
use Cline\Ruler\Operators\Mathematical\Exponentiate;
use Cline\Ruler\Operators\Mathematical\Floor;
use Cline\Ruler\Operators\Mathematical\Modulo;
use Cline\Ruler\Operators\Mathematical\Multiplication;
use Cline\Ruler\Operators\Mathematical\Negation;
use Cline\Ruler\Operators\Mathematical\Subtraction;
use Cline\Ruler\Operators\Set\SetContains;
use Cline\Ruler\Operators\Set\SetDoesNotContain;
use Cline\Ruler\Values\Value;

describe('Variable', function (): void {
    describe('Happy Paths', function (): void {
        test('constructor', function (): void {
            $name = 'evil';
            $var = new Variable(
                new RuleBuilder(),
                $name,
            );
            expect($var->getName())->toEqual($name);
            expect($var->getValue())->toBeNull();
        });

        test('get set value', function (): void {
            $values = explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

            $variable = new Variable(
                new RuleBuilder(),
                'technologic',
            );

            foreach ($values as $valueString) {
                $variable->setValue($valueString);
                expect($variable->getValue())->toEqual($valueString);
            }
        });

        test('prepare value', function (): void {
            $values = [
                'one' => 'Foo',
                'two' => 'BAR',
                'three' => function () {
                    return 'baz';
                },
            ];

            $context = new Context($values);

            $rb = new RuleBuilder();
            $varA = new Variable($rb, 'four', 'qux');
            expect($varA->prepareValue($context))->toBeInstanceOf(Value::class);
            expect($varA->prepareValue($context)->getValue())->toEqual('qux', "Variables should return the default value if it's missing from the context.");

            $varB = new Variable($rb, 'one', 'FAIL');
            expect($varB->prepareValue($context)->getValue())->toEqual('Foo');

            $varC = new Variable($rb, 'three', 'FAIL');
            expect($varC->prepareValue($context)->getValue())->toEqual('baz');

            $varD = new Variable($rb, null, 'qux');
            expect($varD->prepareValue($context))->toBeInstanceOf(Value::class);
            expect($varD->prepareValue($context)->getValue())->toEqual('qux', "Anonymous variables don't require a name to prepare value");
        });

        test('fluent interface helpers and anonymous variables', function (): void {
            $rb = new RuleBuilder();
            $context = new Context([
                'a' => 1,
                'b' => 2,
                'c' => [1, 4],
                'd' => [
                    'foo' => 1,
                    'bar' => 2,
                    'baz' => [
                        'qux' => 3,
                    ],
                ],
                'e' => 1.5,
            ]);

            $varA = new Variable($rb, 'a');
            $varB = new Variable($rb, 'b');
            $varC = new Variable($rb, 'c');
            $varD = new Variable($rb, 'd');
            $varE = new Variable($rb, 'e');

            expect($varA->greaterThan(0))->toBeInstanceOf(GreaterThan::class);
            expect($varA->greaterThan(0)->evaluate($context))->toBeTrue();
            expect($varA->greaterThan(2)->evaluate($context))->toBeFalse();

            expect($varA->greaterThanOrEqualTo(0))->toBeInstanceOf(GreaterThanOrEqualTo::class);
            expect($varA->greaterThanOrEqualTo(0)->evaluate($context))->toBeTrue();
            expect($varA->greaterThanOrEqualTo(1)->evaluate($context))->toBeTrue();
            expect($varA->greaterThanOrEqualTo(2)->evaluate($context))->toBeFalse();

            expect($varA->lessThan(0))->toBeInstanceOf(LessThan::class);
            expect($varA->lessThan(2)->evaluate($context))->toBeTrue();
            expect($varA->lessThan(0)->evaluate($context))->toBeFalse();

            expect($varA->lessThanOrEqualTo(0))->toBeInstanceOf(LessThanOrEqualTo::class);
            expect($varA->lessThanOrEqualTo(1)->evaluate($context))->toBeTrue();
            expect($varA->lessThanOrEqualTo(2)->evaluate($context))->toBeTrue();
            expect($varA->lessThanOrEqualTo(0)->evaluate($context))->toBeFalse();

            expect($varA->equalTo(0))->toBeInstanceOf(EqualTo::class);
            expect($varA->equalTo(1)->evaluate($context))->toBeTrue();
            expect($varA->equalTo(0)->evaluate($context))->toBeFalse();
            expect($varA->equalTo(2)->evaluate($context))->toBeFalse();

            expect($varA->notEqualTo(0))->toBeInstanceOf(NotEqualTo::class);
            expect($varA->notEqualTo(1)->evaluate($context))->toBeFalse();
            expect($varA->notEqualTo(0)->evaluate($context))->toBeTrue();
            expect($varA->notEqualTo(2)->evaluate($context))->toBeTrue();

            $this->assertInstanceof(Variable::class, $varA->add(3));
            $this->assertInstanceof(Addition::class, $varA->add(3)->getValue());
            $this->assertInstanceof(Value::class, $varA->add(3)->prepareValue($context));
            expect($varA->add(3)->prepareValue($context)->getValue())->toEqual(4);
            expect($varA->add(-1)->prepareValue($context)->getValue())->toEqual(0);

            $this->assertInstanceof(Variable::class, $varE->ceil());
            $this->assertInstanceof(Ceil::class, $varE->ceil()->getValue());
            expect($varE->ceil()->prepareValue($context)->getValue())->toEqual(2);

            $this->assertInstanceof(Variable::class, $varB->divide(3));
            $this->assertInstanceof(Division::class, $varB->divide(3)->getValue());
            expect($varB->divide(2)->prepareValue($context)->getValue())->toEqual(1);
            expect($varB->divide(-1)->prepareValue($context)->getValue())->toEqual(-2);

            $this->assertInstanceof(Variable::class, $varE->floor());
            $this->assertInstanceof(Floor::class, $varE->floor()->getValue());
            expect($varE->floor()->prepareValue($context)->getValue())->toEqual(1);

            $this->assertInstanceof(Variable::class, $varA->modulo(3));
            $this->assertInstanceof(Modulo::class, $varA->modulo(3)->getValue());
            expect($varA->modulo(3)->prepareValue($context)->getValue())->toEqual(1);
            expect($varB->modulo(2)->prepareValue($context)->getValue())->toEqual(0);

            $this->assertInstanceof(Variable::class, $varA->multiply(3));
            $this->assertInstanceof(Multiplication::class, $varA->multiply(3)->getValue());
            expect($varB->multiply(3)->prepareValue($context)->getValue())->toEqual(6);
            expect($varB->multiply(-1)->prepareValue($context)->getValue())->toEqual(-2);

            $this->assertInstanceof(Variable::class, $varA->negate());
            $this->assertInstanceof(Negation::class, $varA->negate()->getValue());
            expect($varA->negate()->prepareValue($context)->getValue())->toEqual(-1);
            expect($varB->negate()->prepareValue($context)->getValue())->toEqual(-2);

            $this->assertInstanceof(Variable::class, $varA->subtract(3));
            $this->assertInstanceof(Subtraction::class, $varA->subtract(3)->getValue());
            expect($varA->subtract(3)->prepareValue($context)->getValue())->toEqual(-2);
            expect($varA->subtract(-1)->prepareValue($context)->getValue())->toEqual(2);

            $this->assertInstanceof(Variable::class, $varA->exponentiate(3));
            $this->assertInstanceof(Exponentiate::class, $varA->exponentiate(3)->getValue());
            expect($varA->exponentiate(3)->prepareValue($context)->getValue())->toEqual(1);
            expect($varA->exponentiate(-1)->prepareValue($context)->getValue())->toEqual(1);
            expect($varB->exponentiate(3)->prepareValue($context)->getValue())->toEqual(8);
            expect($varB->exponentiate(-1)->prepareValue($context)->getValue())->toEqual(0.5);

            expect($varA->greaterThan($varB)->evaluate($context))->toBeFalse();
            expect($varA->lessThan($varB)->evaluate($context))->toBeTrue();

            expect($varC->setContains(1))->toBeInstanceOf(SetContains::class);
            expect($varC->setContains($varA)->evaluate($context))->toBeTrue();

            expect($varC->setDoesNotContain(1))->toBeInstanceOf(SetDoesNotContain::class);
            expect($varC->setDoesNotContain($varB)->evaluate($context))->toBeTrue();

            expect($varD['bar'])->toBeInstanceOf(VariableProperty::class);
            expect('foo')->toEqual($varD['foo']->getName());
            expect($varD['foo']->equalTo(1)->evaluate($context))->toBeTrue();

            expect($varD['foo'])->toBeInstanceOf(VariableProperty::class);
            expect('bar')->toEqual($varD['bar']->getName());
            expect($varD['bar']->equalTo(2)->evaluate($context))->toBeTrue();

            expect($varD['baz']['qux'])->toBeInstanceOf(VariableProperty::class);
            expect('qux')->toEqual($varD['baz']['qux']->getName());
            expect($varD['baz']['qux']->equalTo(3)->evaluate($context))->toBeTrue();
        });

        test('array access', function (): void {
            $var = new Variable(
                new RuleBuilder(),
            );
            expect($var)->toBeInstanceOf(ArrayAccess::class);

            $foo = $var['foo'];
            $bar = $var['bar'];
            expect($foo)->toBeInstanceOf(VariableProperty::class);
            expect($bar)->toBeInstanceOf(VariableProperty::class);

            expect($foo)->toBe($var['foo']);
            expect($bar)->toBe($var['bar']);
            $this->assertNotSame($foo, $bar);

            expect($var->offsetExists('foo'))->toBeTrue();
            expect($var->offsetExists('bar'))->toBeTrue();

            expect($var->offsetExists('baz'))->toBeFalse();
            expect($var->offsetExists('qux'))->toBeFalse();

            $baz = $var->getProperty('baz');
            expect($var->offsetExists('baz'))->toBeTrue();

            $qux = $var['qux'];
            expect($var->offsetExists('qux'))->toBeTrue();

            unset($var['foo'], $var['bar'], $var['baz']);

            expect($var->offsetExists('foo'))->toBeFalse();
            expect($var->offsetExists('bar'))->toBeFalse();
            expect($var->offsetExists('baz'))->toBeFalse();
            expect($var->offsetExists('qux'))->toBeTrue();
        });
    });
});
