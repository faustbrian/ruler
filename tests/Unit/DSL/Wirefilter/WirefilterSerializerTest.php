<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\DSL\Wirefilter\WirefilterParser;
use Cline\Ruler\DSL\Wirefilter\WirefilterSerializer;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Variables\Variable;

describe('WirefilterSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age > 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age > 18');
        });

        test('serialize equality comparison', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('status == "active"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status == "active"');
        });

        test('serialize logical and', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age >= 18 and country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 18 and country == "US"');
        });

        test('serialize logical or', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age >= 21 or country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 21 or country == "US"');
        });

        test('serialize not operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('not (age < 18)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('not (age < 18)');
        });

        test('serialize mathematical addition', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('price + shipping > 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price + shipping > 100');
        });

        test('serialize mathematical subtraction', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('total - discount < 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('total - discount < 100');
        });

        test('serialize mathematical multiplication', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('quantity * price > 1000');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('quantity * price > 1000');
        });

        test('serialize mathematical division', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('total / count == 10');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('total / count == 10');
        });

        test('serialize modulo operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('value % 2 == 0');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('value % 2 == 0');
        });

        test('serialize exponentiation operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('base ** power > 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('base ** power > 100');
        });

        test('serialize negation operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('-value < 0');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('-value < 0');
        });

        test('serialize in operator with array', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('country in ["US", "CA", "UK"]');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('country in ["US", "CA", "UK"]');
        });

        test('serialize not in operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('role not in ["banned", "suspended"]');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('role not in ["banned", "suspended"]');
        });

        test('serialize matches operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('phone matches "/^\\\\d{3}-\\\\d{4}$/"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('phone matches "/^\\\\d{3}-\\\\d{4}$/"');
        });

        test('serialize strict equality', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age === 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age === 18');
        });

        test('serialize strict inequality', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('verified !== false');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('verified !== false');
        });

        test('serialize inequality operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('status != "inactive"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status != "inactive"');
        });

        test('serialize less than or equal', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age <= 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age <= 65');
        });

        test('serialize greater than or equal', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age >= 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 18');
        });

        test('serialize less than', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('quantity < 10');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('quantity < 10');
        });

        test('serialize complex nested expression', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('(age >= 18 and country == "US") or age >= 21');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(age >= 18 and country == "US") or age >= 21');
        });

        test('serialize boolean true value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('verified == true');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('verified == true');
        });

        test('serialize boolean false value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('deleted == false');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('deleted == false');
        });

        test('serialize null value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('deleted_at == null');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('deleted_at == null');
        });

        test('serialize numeric values', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('price > 99.99');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price > 99.99');
        });

        test('serialize nested proposition in parentheses', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('(age >= 18 and country == "US") or age >= 21');
            $result = $serializer->serialize($rule);

            // Should preserve parentheses for AND inside OR
            expect($result)->toContain('(');
        });

        test('serialize same as operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('value === 1');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('===');
        });

        test('serialize not same as operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('value !== 1');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('!==');
        });

        test('serialize generic operator via registry lookup', function (): void {
            // Test serializeGenericOperator path by using an operator not in explicit match
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            // Create a rule using StartsWith which isn't in the explicit match cases
            $variable = new Variable('name');
            $value = new Variable(null, 'John');
            $operator = new StartsWith($variable, $value);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('startsWith');
        });

        test('serialize operator with getOperands method fallback', function (): void {
            // Test getOperands fallback path with an operator that might use method instead of property
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            $variable = new Variable('text');
            $value = new Variable(null, 'test');
            $operator = new StringContains($variable, $value);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            // StringContains maps to contains() in wirefilter DSL
            expect($result)->toContain('contains');
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'age > 18';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);
            $reparsed = $parser->parse($serialized);

            expect($serialized)->toBe($original);
        });

        test('round trip complex expression', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'age >= 18 and country == "US"';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip mathematical expression', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'price + shipping > 100';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip with arrays', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'country in ["US", "CA", "UK"]';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });
    });
});
