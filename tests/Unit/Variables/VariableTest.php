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

describe('Variable', function (): void {
    describe('Happy Paths', function (): void {
        test('constructor', function (): void {
            $name = 'evil';
            $var = new Variable($name);
            expect($var->getName())->toEqual($name);
            expect($var->getValue())->toBeNull();
        });

        test('get set value', function (): void {
            $values = explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

            $variable = new Variable('technologic');

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

            $varA = new Variable('four', 'qux');
            expect($varA->prepareValue($context))->toBeInstanceOf(Value::class);
            expect($varA->prepareValue($context)->getValue())->toEqual('qux', "Variables should return the default value if it's missing from the context.");

            $varB = new Variable('one', 'FAIL');
            expect($varB->prepareValue($context)->getValue())->toEqual('Foo');

            $varC = new Variable('three', 'FAIL');
            expect($varC->prepareValue($context)->getValue())->toEqual('baz');

            $varD = new Variable(null, 'qux');
            expect($varD->prepareValue($context))->toBeInstanceOf(Value::class);
            expect($varD->prepareValue($context)->getValue())->toEqual('qux', "Anonymous variables don't require a name to prepare value");
        });
    });
});
