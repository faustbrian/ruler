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
use Cline\Ruler\Operators\Set\SetContains;
use Cline\Ruler\Operators\Set\SetDoesNotContain;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\EndsWithInsensitive;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StartsWithInsensitive;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringContainsInsensitive;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Values\Value;
use Tests\Fixtures\ArrayAccessObject;
use Tests\Fixtures\ObjectWithMethods;
use Tests\Fixtures\ObjectWithProperties;

describe('VariableProperty', function (): void {
    describe('Happy Paths', function (): void {
        test('constructor', function (): void {
            $name = 'evil';
            $prop = new VariableProperty(
                new Variable(
                    new RuleBuilder(),
                ),
                $name,
            );
            expect($prop->getName())->toEqual($name);
            expect($prop->getValue())->toBeNull();
        });

        test('get set value', function (): void {
            $values = explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

            $prop = new VariableProperty(
                new Variable(
                    new RuleBuilder(),
                ),
                'technologic',
            );

            foreach ($values as $valueString) {
                $prop->setValue($valueString);
                expect($prop->getValue())->toEqual($valueString);
            }
        });

        test('prepare value', function (): void {
            $values = [
                'root' => [
                    'one' => 'Foo',
                    'two' => 'BAR',
                ],
            ];

            $context = new Context($values);

            $var = new Variable(
                new RuleBuilder(),
                'root',
            );

            $propA = new VariableProperty($var, 'undefined', 'default');
            expect($propA->prepareValue($context))->toBeInstanceOf(Value::class);
            expect($propA->prepareValue($context)->getValue())->toEqual('default', "VariableProperties should return the default value if it's missing from the context.");

            $propB = new VariableProperty($var, 'one', 'FAIL');
            expect($propB->prepareValue($context)->getValue())->toEqual('Foo');
        });

        test('fluent interface helpers and anonymous variables', function (): void {
            $context = new Context([
                'root' => [
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
                    'e' => 'string',
                    'f' => 'ring',
                    'g' => 'stuff',
                    'h' => 'STRING',
                ],
            ]);

            $root = new Variable(
                new RuleBuilder(),
                'root',
            );

            $varA = $root['a'];
            $varB = $root['b'];
            $varC = $root['c'];
            $varD = $root['d'];
            $varE = $root['e'];
            $varF = $root['f'];
            $varG = $root['g'];
            $varH = $root['h'];

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

            expect($varA->greaterThan($varB)->evaluate($context))->toBeFalse();
            expect($varA->lessThan($varB)->evaluate($context))->toBeTrue();

            expect($varE->stringContains('ring'))->toBeInstanceOf(StringContains::class);
            expect($varE->stringContains($varF)->evaluate($context))->toBeTrue();

            expect($varE->stringContainsInsensitive('STRING'))->toBeInstanceOf(StringContainsInsensitive::class);
            expect($varE->stringContainsInsensitive($varH)->evaluate($context))->toBeTrue();

            expect($varE->stringDoesNotContain('cheese'))->toBeInstanceOf(StringDoesNotContain::class);
            expect($varE->stringDoesNotContain($varG)->evaluate($context))->toBeTrue();

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

            expect($varE->endsWith('string'))->toBeInstanceOf(EndsWith::class);
            expect($varE->endsWith($varE)->evaluate($context))->toBeTrue();

            expect($varE->endsWithInsensitive('STRING'))->toBeInstanceOf(EndsWithInsensitive::class);
            expect($varE->endsWithInsensitive($varE)->evaluate($context))->toBeTrue();

            expect($varE->startsWith('string'))->toBeInstanceOf(StartsWith::class);
            expect($varE->startsWith($varE)->evaluate($context))->toBeTrue();

            expect($varE->startsWithInsensitive('STRING'))->toBeInstanceOf(StartsWithInsensitive::class);
            expect($varE->startsWithInsensitive($varE)->evaluate($context))->toBeTrue();
        });

        test('returns default value when property name is null', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'root');
            $context = new Context(['root' => ['key' => 'value']]);
            $defaultValue = 'default_value';

            // Act
            $property = new VariableProperty($parentVariable, null, $defaultValue);
            $result = $property->prepareValue($context);

            // Assert
            expect($result)->toBeInstanceOf(Value::class);
            expect($result->getValue())->toBe($defaultValue);
        });

        test('accesses object method through variable property', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'user');
            $objectWithMethods = new ObjectWithMethods('Jane', 'Smith');
            $context = new Context(['user' => $objectWithMethods]);

            // Act - test various method calls
            $fullNameProperty = new VariableProperty($parentVariable, 'getFullName');
            $firstNameProperty = new VariableProperty($parentVariable, 'getFirstName');
            $lastNameProperty = new VariableProperty($parentVariable, 'getLastName');
            $activeProperty = new VariableProperty($parentVariable, 'isActive');
            $countProperty = new VariableProperty($parentVariable, 'getCount');

            // Assert
            expect($fullNameProperty->prepareValue($context)->getValue())->toBe('Jane Smith');
            expect($firstNameProperty->prepareValue($context)->getValue())->toBe('Jane');
            expect($lastNameProperty->prepareValue($context)->getValue())->toBe('Smith');
            expect($activeProperty->prepareValue($context)->getValue())->toBeTrue();
            expect($countProperty->prepareValue($context)->getValue())->toBe(42);
        });

        test('accesses object property through variable property', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'profile');
            $objectWithProperties = new ObjectWithProperties(
                name: 'Alice Johnson',
                email: 'alice@example.com',
                age: 25,
                active: false,
            );
            $context = new Context(['profile' => $objectWithProperties]);

            // Act
            $nameProperty = new VariableProperty($parentVariable, 'name');
            $emailProperty = new VariableProperty($parentVariable, 'email');
            $ageProperty = new VariableProperty($parentVariable, 'age');
            $activeProperty = new VariableProperty($parentVariable, 'active');

            // Assert
            expect($nameProperty->prepareValue($context)->getValue())->toBe('Alice Johnson');
            expect($emailProperty->prepareValue($context)->getValue())->toBe('alice@example.com');
            expect($ageProperty->prepareValue($context)->getValue())->toBe(25);
            expect($activeProperty->prepareValue($context)->getValue())->toBeFalse();
        });

        test('accesses ArrayAccess offset through variable property', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'settings');
            $arrayAccessObject = new ArrayAccessObject([
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => true,
                'count' => 100,
            ]);
            $context = new Context(['settings' => $arrayAccessObject]);

            // Act
            $themeProperty = new VariableProperty($parentVariable, 'theme');
            $languageProperty = new VariableProperty($parentVariable, 'language');
            $notificationsProperty = new VariableProperty($parentVariable, 'notifications');
            $countProperty = new VariableProperty($parentVariable, 'count');

            // Assert
            expect($themeProperty->prepareValue($context)->getValue())->toBe('dark');
            expect($languageProperty->prepareValue($context)->getValue())->toBe('en');
            expect($notificationsProperty->prepareValue($context)->getValue())->toBeTrue();
            expect($countProperty->prepareValue($context)->getValue())->toBe(100);
        });
    });

    describe('Edge Cases', function (): void {
        test('returns default value when ArrayAccess object does not have offset', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'settings');
            $arrayAccessObject = new ArrayAccessObject(['existing' => 'value']);
            $context = new Context(['settings' => $arrayAccessObject]);
            $defaultValue = 'fallback';

            // Act
            $property = new VariableProperty($parentVariable, 'nonexistent', $defaultValue);
            $result = $property->prepareValue($context);

            // Assert
            expect($result->getValue())->toBe($defaultValue);
        });

        test('returns default value when object does not have method or property', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'user');
            $objectWithMethods = new ObjectWithMethods();
            $context = new Context(['user' => $objectWithMethods]);
            $defaultValue = 'not_found';

            // Act
            $property = new VariableProperty($parentVariable, 'nonexistentMethod', $defaultValue);
            $result = $property->prepareValue($context);

            // Assert
            expect($result->getValue())->toBe($defaultValue);
        });

        test('method access takes precedence over property access', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'obj');

            // Create an object with both a property and method with same name
            $object = new class()
            {
                public string $value = 'property_value';

                public function value(): string
                {
                    return 'method_value';
                }
            };

            $context = new Context(['obj' => $object]);

            // Act
            $property = new VariableProperty($parentVariable, 'value');
            $result = $property->prepareValue($context);

            // Assert - method should take precedence
            expect($result->getValue())->toBe('method_value');
        });

        test('property access takes precedence over ArrayAccess', function (): void {
            // Arrange
            $ruleBuilder = new RuleBuilder();
            $parentVariable = new Variable($ruleBuilder, 'obj');

            // Create an ArrayAccess object with a public property
            $object = new class() implements ArrayAccess
            {
                public string $name = 'property_value';

                private array $data = ['name' => 'array_value'];

                public function offsetExists(mixed $offset): bool
                {
                    return array_key_exists($offset, $this->data);
                }

                public function offsetGet(mixed $offset): mixed
                {
                    return $this->data[$offset] ?? null;
                }

                public function offsetSet(mixed $offset, mixed $value): void
                {
                    $this->data[$offset] = $value;
                }

                public function offsetUnset(mixed $offset): void
                {
                    unset($this->data[$offset]);
                }
            };

            $context = new Context(['obj' => $object]);

            // Act
            $property = new VariableProperty($parentVariable, 'name');
            $result = $property->prepareValue($context);

            // Assert - property should take precedence over ArrayAccess
            expect($result->getValue())->toBe('property_value');
        });
    });
});
