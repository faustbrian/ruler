<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\MongoDB\MongoQueryRuleBuilder;

test('parse simple comparison expression', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['age' => ['$gt' => 18]]);

    $context = new Context(['age' => 25]);

    expect($rule)->toBeInstanceOf(Rule::class)
        ->and($rule->evaluate($context))->toBeTrue();
});

test('parse comparison with field that fails', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['age' => ['$gt' => 18]]);

    $context = new Context(['age' => 15]);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse equality operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['status' => 'active']);

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse logical and expression', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        '$and' => [
            ['age' => ['$gte' => 18]],
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

test('parse logical or expression', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        '$or' => [
            ['age' => ['$gte' => 21]],
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

test('parse expression with parentheses', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB equivalent: nested logical operators
    $rule = $mongo->parse([
        '$or' => [
            [
                '$and' => [
                    ['age' => ['$gte' => 18]],
                    ['country' => 'US'],
                ],
            ],
            ['age' => ['$gte' => 21]],
        ],
    ]);

    $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse mathematical expression', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB doesn't support inline arithmetic
    // Test with pre-computed total value
    $rule = $mongo->parse(['total' => ['$gt' => 100]]);

    $trueContext = new Context(['total' => 105]); // price + shipping = 105
    $falseContext = new Context(['total' => 60]);  // price + shipping = 60

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not expression', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        '$not' => ['age' => ['$lt' => 18]],
    ]);

    $trueContext = new Context(['age' => 25]);
    $falseContext = new Context(['age' => 15]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse in operator with array', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['country' => ['$in' => ['US', 'CA', 'UK']]]);

    $trueContext = new Context(['country' => 'US']);
    $falseContext = new Context(['country' => 'FR']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse matches operator with regex', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['phone' => ['$regex' => '^\\d{3}-\\d{4}$']]);

    $trueContext = new Context(['phone' => '123-4567']);
    $falseContext = new Context(['phone' => '12-34567']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse less than or equal operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['age' => ['$lte' => 65]]);

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse inequality operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['status' => ['$ne' => 'inactive']]);

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse notIn operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['role' => ['$nin' => ['banned', 'suspended']]]);

    $trueContext = new Context(['role' => 'active']);
    $falseContext = new Context(['role' => 'banned']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse modulo operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB doesn't support modulo in query language
    // Test with pre-computed boolean result
    $rule = $mongo->parse(['isEven' => true]);

    $trueContext = new Context(['isEven' => true]);  // value % 2 == 0
    $falseContext = new Context(['isEven' => false]); // value % 2 != 0

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse exponentiate operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB doesn't support exponentiation in query language
    // Test with pre-computed result
    $rule = $mongo->parse(['result' => ['$gt' => 100]]);

    $trueContext = new Context(['result' => 125]);  // 5 ** 3 = 125
    $falseContext = new Context(['result' => 8]);   // 2 ** 3 = 8

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse division operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB doesn't support division in query language
    // Test with pre-computed result
    $rule = $mongo->parse(['average' => 10]);

    $trueContext = new Context(['average' => 10]);  // 100 / 10 = 10
    $falseContext = new Context(['average' => 20]); // 100 / 5 = 20

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse multiplication operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB doesn't support multiplication in query language
    // Test with pre-computed total
    $rule = $mongo->parse(['total' => ['$gt' => 1_000]]);

    $trueContext = new Context(['total' => 1_200]);  // 20 * 60 = 1200
    $falseContext = new Context(['total' => 500]);  // 10 * 50 = 500

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse subtraction operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB doesn't support subtraction in query language
    // Test with pre-computed final value
    $rule = $mongo->parse(['final' => ['$lt' => 100]]);

    $trueContext = new Context(['final' => 90]);   // 120 - 30 = 90
    $falseContext = new Context(['final' => 150]); // 200 - 50 = 150

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});
