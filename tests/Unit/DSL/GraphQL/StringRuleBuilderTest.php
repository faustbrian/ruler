<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\GraphQL\GraphQLFilterRuleBuilder;

test('parse simple comparison expression', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['age' => ['gt' => 18]]);

    $context = new Context(['age' => 25]);

    expect($rule)->toBeInstanceOf(Rule::class)
        ->and($rule->evaluate($context))->toBeTrue();
});

test('parse comparison with field that fails', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['age' => ['gt' => 18]]);

    $context = new Context(['age' => 15]);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse equality operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['status' => ['eq' => 'active']]);

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse implicit equality', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['status' => 'active']);

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse logical and expression', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'AND' => [
            ['age' => ['gte' => 18]],
            ['country' => 'US'],
        ],
    ]);

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse implicit and with multiple fields', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'age' => ['gte' => 18],
        'country' => 'US',
    ]);

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse logical or expression', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
        'OR' => [
            ['age' => ['gte' => 21]],
            ['country' => 'US'],
        ],
    ]);

    $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
    $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse expression with nested logic', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse([
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

    $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not expression', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['NOT' => ['age' => ['lt' => 18]]]);

    $trueContext = new Context(['age' => 25]);
    $falseContext = new Context(['age' => 15]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse in operator with array', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['country' => ['in' => ['US', 'CA', 'UK']]]);

    $trueContext = new Context(['country' => 'US']);
    $falseContext = new Context(['country' => 'FR']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse matches operator with regex', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['phone' => ['match' => '^\\d{3}-\\d{4}$']]);

    $trueContext = new Context(['phone' => '123-4567']);
    $falseContext = new Context(['phone' => '12-34567']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse less than or equal operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['age' => ['lte' => 65]]);

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse inequality operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['status' => ['ne' => 'inactive']]);

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse notIn operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['role' => ['notIn' => ['banned', 'suspended']]]);

    $trueContext = new Context(['role' => 'active']);
    $falseContext = new Context(['role' => 'banned']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse contains operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['email' => ['contains' => '@example.com']]);

    $trueContext = new Context(['email' => 'user@example.com']);
    $falseContext = new Context(['email' => 'user@test.com']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse startsWith operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['name' => ['startsWith' => 'John']]);

    $trueContext = new Context(['name' => 'John Doe']);
    $falseContext = new Context(['name' => 'Jane Smith']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse endsWith operator', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['filename' => ['endsWith' => '.pdf']]);

    $trueContext = new Context(['filename' => 'document.pdf']);
    $falseContext = new Context(['filename' => 'document.docx']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse range query with multiple operators', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['age' => ['gte' => 18, 'lte' => 65]]);

    $trueContext = new Context(['age' => 30]);
    $falseContext1 = new Context(['age' => 15]);
    $falseContext2 = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});
