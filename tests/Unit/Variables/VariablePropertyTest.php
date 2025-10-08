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
    });
});
