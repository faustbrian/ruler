<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableProperty;
use Tests\Fixtures\ArrayAccessObject;
use Tests\Fixtures\ObjectWithMethods;
use Tests\Fixtures\ObjectWithProperties;

describe('VariableProperty', function (): void {
    describe('Happy Paths', function (): void {
        test('constructor', function (): void {
            $name = 'evil';
            $prop = new VariableProperty(
                new Variable(),
                $name,
            );
            expect($prop->getName())->toEqual($name);
            expect($prop->getValue())->toBeNull();
        });

        test('get set value', function (): void {
            $values = explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

            $prop = new VariableProperty(
                new Variable(),
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

            $var = new Variable('root');

            $propA = new VariableProperty($var, 'undefined', 'default');
            expect($propA->prepareValue($context))->toBeInstanceOf(Value::class);
            expect($propA->prepareValue($context)->getValue())->toEqual('default', "VariableProperties should return the default value if it's missing from the context.");

            $propB = new VariableProperty($var, 'one', 'FAIL');
            expect($propB->prepareValue($context)->getValue())->toEqual('Foo');
        });

        test('accesses object method and returns result', function (): void {
            // Arrange
            $object = new ObjectWithMethods(firstName: 'Jane', lastName: 'Smith');
            $context = new Context(['user' => $object]);
            $var = new Variable('user');

            // Act
            $prop = new VariableProperty($var, 'getFullName', 'default');
            $result = $prop->prepareValue($context);

            // Assert
            expect($result)->toBeInstanceOf(Value::class);
            expect($result->getValue())->toBe('Jane Smith');
        });

        test('accesses object public property and returns value', function (): void {
            // Arrange
            $object = new ObjectWithProperties(name: 'Alice Johnson', email: 'alice@example.com');
            $context = new Context(['profile' => $object]);
            $var = new Variable('profile');

            // Act
            $propName = new VariableProperty($var, 'name', 'default');
            $propEmail = new VariableProperty($var, 'email', 'default');

            // Assert
            expect($propName->prepareValue($context)->getValue())->toBe('Alice Johnson');
            expect($propEmail->prepareValue($context)->getValue())->toBe('alice@example.com');
        });

        test('accesses ArrayAccess object offset and returns value', function (): void {
            // Arrange
            $arrayAccessObj = new ArrayAccessObject(['key1' => 'value1', 'key2' => 'value2', 'count' => 99]);
            $context = new Context(['data' => $arrayAccessObj]);
            $var = new Variable('data');

            // Act
            $prop1 = new VariableProperty($var, 'key1', 'default');
            $prop2 = new VariableProperty($var, 'key2', 'default');
            $propCount = new VariableProperty($var, 'count', 'default');

            // Assert
            expect($prop1->prepareValue($context)->getValue())->toBe('value1');
            expect($prop2->prepareValue($context)->getValue())->toBe('value2');
            expect($propCount->prepareValue($context)->getValue())->toBe(99);
        });

        test('accesses object method returning different types', function (): void {
            // Arrange
            $object = new ObjectWithMethods();
            $context = new Context(['obj' => $object]);
            $var = new Variable('obj');

            // Act & Assert - string return type
            $propString = new VariableProperty($var, 'getFirstName', 'default');
            expect($propString->prepareValue($context)->getValue())->toBe('John');

            // Act & Assert - boolean return type
            $propBool = new VariableProperty($var, 'isActive', false);
            expect($propBool->prepareValue($context)->getValue())->toBeTrue();

            // Act & Assert - integer return type
            $propInt = new VariableProperty($var, 'getCount', 0);
            expect($propInt->prepareValue($context)->getValue())->toBe(42);
        });

        test('accesses object properties with different types', function (): void {
            // Arrange
            $object = new ObjectWithProperties(name: 'Bob', email: 'bob@test.com', age: 25, active: false);
            $context = new Context(['user' => $object]);
            $var = new Variable('user');

            // Act & Assert - string property
            $propName = new VariableProperty($var, 'name', 'default');
            expect($propName->prepareValue($context)->getValue())->toBe('Bob');

            // Act & Assert - integer property
            $propAge = new VariableProperty($var, 'age', 0);
            expect($propAge->prepareValue($context)->getValue())->toBe(25);

            // Act & Assert - boolean property
            $propActive = new VariableProperty($var, 'active', true);
            expect($propActive->prepareValue($context)->getValue())->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('returns default value when object method does not exist', function (): void {
            // Arrange
            $object = new ObjectWithMethods();
            $context = new Context(['obj' => $object]);
            $var = new Variable('obj');

            // Act
            $prop = new VariableProperty($var, 'nonExistentMethod', 'default_value');
            $result = $prop->prepareValue($context);

            // Assert
            expect($result->getValue())->toBe('default_value');
        });

        test('returns default value when object property does not exist', function (): void {
            // Arrange
            $object = new ObjectWithProperties();
            $context = new Context(['obj' => $object]);
            $var = new Variable('obj');

            // Act
            $prop = new VariableProperty($var, 'nonExistentProperty', 'fallback');
            $result = $prop->prepareValue($context);

            // Assert
            expect($result->getValue())->toBe('fallback');
        });

        test('returns default value when ArrayAccess offset does not exist', function (): void {
            // Arrange
            $arrayAccessObj = new ArrayAccessObject(['existing' => 'value']);
            $context = new Context(['data' => $arrayAccessObj]);
            $var = new Variable('data');

            // Act
            $prop = new VariableProperty($var, 'missing', 'default');
            $result = $prop->prepareValue($context);

            // Assert
            expect($result->getValue())->toBe('default');
        });
    });

    describe('Edge Cases', function (): void {
        test('prioritizes method call over property when both exist with same name', function (): void {
            // Arrange
            $object = new class()
            {
                public string $name = 'property_value';

                public function name(): string
                {
                    return 'method_value';
                }
            };
            $context = new Context(['obj' => $object]);
            $var = new Variable('obj');

            // Act
            $prop = new VariableProperty($var, 'name', 'default');
            $result = $prop->prepareValue($context);

            // Assert - method should be called, not property accessed
            expect($result->getValue())->toBe('method_value');
        });

        test('prioritizes property over ArrayAccess when object implements both', function (): void {
            // Arrange
            $object = new class() implements ArrayAccess
            {
                public string $key = 'property_value';

                private array $data = ['key' => 'array_access_value'];

                public function offsetExists(mixed $offset): bool
                {
                    return array_key_exists($offset, $this->data);
                }

                public function offsetGet(mixed $offset): mixed
                {
                    return $this->data[$offset];
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
            $var = new Variable('obj');

            // Act
            $prop = new VariableProperty($var, 'key', 'default');
            $result = $prop->prepareValue($context);

            // Assert - property should be accessed, not ArrayAccess offsetGet
            expect($result->getValue())->toBe('property_value');
        });

        test('handles empty ArrayAccess object', function (): void {
            // Arrange
            $emptyArrayAccess = new ArrayAccessObject([]);
            $context = new Context(['empty' => $emptyArrayAccess]);
            $var = new Variable('empty');

            // Act
            $prop = new VariableProperty($var, 'any_key', 'default');
            $result = $prop->prepareValue($context);

            // Assert
            expect($result->getValue())->toBe('default');
        });

        test('handles null default value when property not found', function (): void {
            // Arrange
            $object = new ObjectWithProperties();
            $context = new Context(['obj' => $object]);
            $var = new Variable('obj');

            // Act
            $prop = new VariableProperty($var, 'missing', null);
            $result = $prop->prepareValue($context);

            // Assert
            expect($result->getValue())->toBeNull();
        });

        test('does not call private methods on objects', function (): void {
            // Arrange
            $object = new class()
            {
                private static function privateMethod(): string
                {
                    return 'private_value';
                }
            };
            $context = new Context(['obj' => $object]);
            $var = new Variable('obj');

            // Act
            $prop = new VariableProperty($var, 'privateMethod', 'default');
            $result = $prop->prepareValue($context);

            // Assert - private method should not be accessible, return default
            expect($result->getValue())->toBe('default');
        });

        test('accesses ArrayAccess with numeric keys', function (): void {
            // Arrange
            $arrayAccessObj = new ArrayAccessObject([0 => 'first', 1 => 'second', 2 => 'third']);
            $context = new Context(['list' => $arrayAccessObj]);
            $var = new Variable('list');

            // Act
            $prop0 = new VariableProperty($var, '0', 'default');
            $prop1 = new VariableProperty($var, '1', 'default');

            // Assert
            expect($prop0->prepareValue($context)->getValue())->toBe('first');
            expect($prop1->prepareValue($context)->getValue())->toBe('second');
        });
    });
});
