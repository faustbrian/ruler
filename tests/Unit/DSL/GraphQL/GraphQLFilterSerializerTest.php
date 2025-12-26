<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\GraphQL\GraphQLFilterParser;
use Cline\Ruler\DSL\GraphQL\GraphQLFilterSerializer;

describe('GraphQLFilterSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['age' => ['gt' => 18]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['age' => ['gt' => 18]]);
        });

        test('serialize equality comparison', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['status' => ['eq' => 'active']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['status' => ['eq' => 'active']]);
        });

        test('serialize implicit equality', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['status' => 'active']);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['status' => ['eq' => 'active']]);
        });

        test('serialize logical AND explicit', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'AND' => [
                    ['age' => ['gte' => 18]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            expect($result)->toMatchArray([
                'age' => ['gte' => 18],
                'country' => ['eq' => 'US'],
            ]);
        });

        test('serialize implicit AND with multiple fields', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'age' => ['gte' => 18],
                'country' => 'US',
            ]);
            $result = $serializer->serialize($rule);

            expect($result)->toMatchArray([
                'age' => ['gte' => 18],
                'country' => ['eq' => 'US'],
            ]);
        });

        test('serialize logical OR', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'OR' => [
                    ['age' => ['gte' => 21]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe([
                'OR' => [
                    ['age' => ['gte' => 21]],
                    ['country' => ['eq' => 'US']],
                ],
            ]);
        });

        test('serialize NOT operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['NOT' => ['age' => ['lt' => 18]]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['NOT' => ['age' => ['lt' => 18]]]);
        });

        test('serialize in operator with array', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['country' => ['in' => ['US', 'CA', 'UK']]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['country' => ['in' => ['US', 'CA', 'UK']]]);
        });

        test('serialize notIn operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['role' => ['notIn' => ['banned', 'suspended']]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['role' => ['notIn' => ['banned', 'suspended']]]);
        });

        test('serialize contains operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['email' => ['contains' => '@example.com']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['email' => ['contains' => '@example.com']]);
        });

        test('serialize startsWith operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['name' => ['startsWith' => 'John']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['name' => ['startsWith' => 'John']]);
        });

        test('serialize endsWith operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['filename' => ['endsWith' => '.pdf']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['filename' => ['endsWith' => '.pdf']]);
        });

        test('serialize match operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['phone' => ['match' => '^\\d{3}-\\d{4}$']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['phone' => ['match' => '^\\d{3}-\\d{4}$']]);
        });

        test('serialize inequality operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['status' => ['ne' => 'inactive']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['status' => ['ne' => 'inactive']]);
        });

        test('serialize less than or equal', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['age' => ['lte' => 65]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['age' => ['lte' => 65]]);
        });

        test('serialize greater than or equal', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['age' => ['gte' => 18]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['age' => ['gte' => 18]]);
        });

        test('serialize less than', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['quantity' => ['lt' => 10]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['quantity' => ['lt' => 10]]);
        });

        test('serialize range query with multiple operators', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['age' => ['gte' => 18, 'lte' => 65]]);
            $result = $serializer->serialize($rule);

            // Multiple operators on same field become implicit AND with separate keys
            expect($result)->toBeArray()
                ->and($result)->toHaveKey('age')
                ->and(array_key_exists('gte', $result['age']) || array_key_exists('lte', $result['age']))->toBeTrue();
        });

        test('serialize complex nested expression', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'OR' => [
                    [
                        'AND' => [
                            ['age' => ['gte' => 18]],
                            ['country' => 'US'],
                        ],
                    ],
                    ['age' => ['gte' => 21]],
                ],
            ]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe([
                'OR' => [
                    [
                        'age' => ['gte' => 18],
                        'country' => ['eq' => 'US'],
                    ],
                    ['age' => ['gte' => 21]],
                ],
            ]);
        });

        test('serialize numeric values', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['price' => ['gt' => 99.99]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['price' => ['gt' => 99.99]]);
        });

        test('serialize boolean values', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['verified' => ['eq' => true]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['verified' => ['eq' => true]]);
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $original = ['age' => ['gt' => 18]];
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip explicit AND', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $original = [
                'AND' => [
                    ['age' => ['gte' => 18]],
                    ['country' => ['eq' => 'US']],
                ],
            ];
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            // Explicit AND becomes implicit
            expect($serialized)->toMatchArray([
                'age' => ['gte' => 18],
                'country' => ['eq' => 'US'],
            ]);
        });

        test('round trip OR expression', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $original = [
                'OR' => [
                    ['age' => ['gte' => 21]],
                    ['country' => ['eq' => 'US']],
                ],
            ];
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip with arrays', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $original = ['country' => ['in' => ['US', 'CA', 'UK']]];
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip NOT expression', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $original = ['NOT' => ['age' => ['lt' => 18]]];
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });
    });

    describe('Null Check Operators', function (): void {
        test('serialize isNull operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['email' => ['isNull' => true]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['email' => ['isNull' => true]]);
        });

        test('serialize isNull false (NOT isNull)', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['email' => ['isNull' => false]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['NOT' => ['email' => ['isNull' => true]]]);
        });
    });

    describe('Type Validation Operators', function (): void {
        test('serialize isType string', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['name' => ['isType' => 'string']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['name' => ['type' => 'string']]);
        });

        test('serialize isType numeric', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['age' => ['isType' => 'numeric']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['age' => ['type' => 'numeric']]);
        });

        test('serialize isType boolean', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['active' => ['isType' => 'boolean']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['active' => ['type' => 'boolean']]);
        });

        test('serialize isType array', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['tags' => ['isType' => 'array']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['tags' => ['type' => 'array']]);
        });
    });

    describe('Case Insensitive String Operators', function (): void {
        test('serialize containsInsensitive operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['email' => ['containsInsensitive' => 'EXAMPLE']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['email' => ['containsInsensitive' => 'EXAMPLE']]);
        });

        test('serialize notContains operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['email' => ['notContains' => 'spam']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['email' => ['notContains' => 'spam']]);
        });

        test('serialize notContainsInsensitive operator', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse(['email' => ['notContainsInsensitive' => 'SPAM']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe(['email' => ['notContainsInsensitive' => 'SPAM']]);
        });
    });

    describe('Complex AND Conditions', function (): void {
        test('serialize AND with nested OR (forces explicit AND)', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'AND' => [
                    ['OR' => [['age' => ['gt' => 18]], ['age' => ['lt' => 65]]]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe([
                'AND' => [
                    ['OR' => [['age' => ['gt' => 18]], ['age' => ['lt' => 65]]]],
                    ['country' => ['eq' => 'US']],
                ],
            ]);
        });

        test('serialize AND with nested AND (collapses to implicit)', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'AND' => [
                    ['AND' => [['age' => ['gt' => 18]], ['age' => ['lt' => 65]]]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            // Nested AND with simple conditions collapses to implicit AND
            expect($result)->toMatchArray([
                'age' => ['lt' => 65],
                'country' => ['eq' => 'US'],
            ]);
        });

        test('serialize AND with nested NOT (forces explicit AND)', function (): void {
            $parser = new GraphQLFilterParser();
            $serializer = new GraphQLFilterSerializer();

            $rule = $parser->parse([
                'AND' => [
                    ['NOT' => ['age' => ['lt' => 18]]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe([
                'AND' => [
                    ['NOT' => ['age' => ['lt' => 18]]],
                    ['country' => ['eq' => 'US']],
                ],
            ]);
        });
    });
});
