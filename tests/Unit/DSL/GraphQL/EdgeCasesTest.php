<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\DSL\GraphQL\GraphQLFilterRuleBuilder;

test('parse deeply nested object property access', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'user' => [
            'profile' => [
                'settings' => [
                    'theme' => 'dark',
                ],
            ],
        ],
    ]);

    $trueContext = new Context([
        'user' => [
            'profile' => [
                'settings' => [
                    'theme' => 'dark',
                ],
            ],
        ],
    ]);

    $falseContext = new Context([
        'user' => [
            'profile' => [
                'settings' => [
                    'theme' => 'light',
                ],
            ],
        ],
    ]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse JSON string query', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $json = '{"age": {"gte": 18}, "country": "US"}';
    $rule = $gql->parseJson($json);

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext = new Context(['age' => 15, 'country' => 'US']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse containsInsensitive operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['email' => ['containsInsensitive' => '@EXAMPLE.COM']]);

    $trueContext1 = new Context(['email' => 'user@example.com']);
    $trueContext2 = new Context(['email' => 'admin@EXAMPLE.COM']);
    $falseContext = new Context(['email' => 'user@test.com']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse notContains operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['description' => ['notContains' => 'spam']]);

    $trueContext = new Context(['description' => 'This is a clean message']);
    $falseContext = new Context(['description' => 'This is spam']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse notContainsInsensitive operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['description' => ['notContainsInsensitive' => 'SPAM']]);

    $trueContext = new Context(['description' => 'This is a clean message']);
    $falseContext1 = new Context(['description' => 'This is spam']);
    $falseContext2 = new Context(['description' => 'This is SPAM']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse isNull operator - should be null', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['deletedAt' => ['isNull' => true]]);

    $trueContext = new Context(['deletedAt' => null]);
    $falseContext = new Context(['deletedAt' => '2024-01-01']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse isNull operator - should not be null', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['email' => ['isNull' => false]]);

    $trueContext = new Context(['email' => 'user@example.com']);
    $falseContext = new Context(['email' => null]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse isType operator with string', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['name' => ['isType' => 'string']]);

    $trueContext = new Context(['name' => 'John']);
    $falseContext = new Context(['name' => 123]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse isType operator with number', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['count' => ['isType' => 'number']]);

    $trueContext1 = new Context(['count' => 42]);
    $trueContext2 = new Context(['count' => 3.14]);
    $trueContext3 = new Context(['count' => '42']); // is_numeric accepts strings
    $falseContext = new Context(['count' => 'abc']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse isType operator with boolean', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['flag' => ['isType' => 'bool']]);

    $trueContext = new Context(['flag' => true]);
    $falseContext = new Context(['flag' => 1]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse isType operator with array', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['tags' => ['isType' => 'array']]);

    $trueContext = new Context(['tags' => [1, 2, 3]]);
    $falseContext = new Context(['tags' => 'not array']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse isType operator with null', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['value' => ['isType' => 'null']]);

    $trueContext = new Context(['value' => null]);
    $falseContext = new Context(['value' => '']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse complex e-commerce product filter', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'AND' => [
            ['price' => ['gte' => 10, 'lte' => 500]],
            ['category' => ['in' => ['electronics', 'books']]],
            ['inStock' => true],
            [
                'OR' => [
                    ['rating' => ['gte' => 4.0]],
                    ['featured' => true],
                ],
            ],
            ['NOT' => ['status' => 'clearance']],
        ],
    ]);

    $validProduct = new Context([
        'price' => 299,
        'category' => 'electronics',
        'inStock' => true,
        'rating' => 4.5,
        'featured' => false,
        'status' => 'active',
    ]);

    $invalidProduct = new Context([
        'price' => 299,
        'category' => 'electronics',
        'inStock' => true,
        'rating' => 3.5,
        'featured' => false,
        'status' => 'active',
    ]);

    expect($rule->evaluate($validProduct))->toBeTrue()
        ->and($rule->evaluate($invalidProduct))->toBeFalse();
});

test('parse nested object with operators', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'user' => [
            'profile' => [
                'age' => ['gte' => 18],
            ],
        ],
    ]);

    $trueContext = new Context([
        'user' => [
            'profile' => [
                'age' => 25,
            ],
        ],
    ]);

    $falseContext = new Context([
        'user' => [
            'profile' => [
                'age' => 15],
        ],
    ]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse triple nested OR conditions', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'OR' => [
            [
                'OR' => [
                    ['status' => 'active'],
                    ['status' => 'pending'],
                ],
            ],
            ['vip' => true],
        ],
    ]);

    $trueContext1 = new Context(['status' => 'active', 'vip' => false]);
    $trueContext2 = new Context(['status' => 'pending', 'vip' => false]);
    $trueContext3 = new Context(['status' => 'inactive', 'vip' => true]);
    $falseContext = new Context(['status' => 'inactive', 'vip' => false]);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse mixed type values in array', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['value' => ['in' => [1, 'two', true, null]]]);

    $trueContext1 = new Context(['value' => 1]);
    $trueContext2 = new Context(['value' => 'two']);
    $trueContext3 = new Context(['value' => true]);
    $trueContext4 = new Context(['value' => null]);
    $falseContext = new Context(['value' => 'four']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($trueContext4))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});
