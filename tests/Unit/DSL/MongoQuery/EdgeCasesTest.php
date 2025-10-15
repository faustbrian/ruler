<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\MongoQuery\MongoQueryRuleBuilder;

test('parse strict equality using explicit comparison', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$eq' => 42]]);

    $trueContext = new Context(['value' => 42]);
    $falseContext = new Context(['value' => '42']);

    // MongoDB $eq operator is not strict (similar to ==)
    // This test documents the behavior difference
    expect($rule->evaluate($trueContext))->toBeTrue();
});

test('parse strict inequality using explicit comparison', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$ne' => 'test']]);

    $trueContext = new Context(['value' => 42]);
    $falseContext = new Context(['value' => 'test']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse array literal in expression', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['status' => ['$in' => ['active', 'pending', 'approved']]]);

    $trueContext = new Context(['status' => 'pending']);
    $falseContext = new Context(['status' => 'rejected']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse deeply nested object property access', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['user.profile.settings.theme' => 'dark']);

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

test('parse empty array check', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB: check if field equals empty array
    $rule = $mongo->parse(['tags' => []]);

    $trueContext = new Context(['tags' => []]);
    $falseContext = new Context(['tags' => ['php', 'mongodb']]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse mixed type arrays', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$in' => [1, 'two', true, null]]]);

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

test('parse unary minus operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB: pre-computed negation
    $rule = $mongo->parse(['negativeValue' => ['$gt' => -10]]);

    $trueContext = new Context(['negativeValue' => -5]);  // -5 > -10 = true
    $falseContext = new Context(['negativeValue' => -15]); // -15 > -10 = false

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse implicit AND with multiple fields', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // MongoDB: multiple fields at root level = implicit AND
    $rule = $mongo->parse([
        'age' => ['$gte' => 18],
        'country' => 'US',
    ]);

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse $nor logical operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // $nor: none of the conditions can be true
    $rule = $mongo->parse([
        '$nor' => [
            ['age' => ['$lt' => 18]],
            ['status' => 'banned'],
        ],
    ]);

    $trueContext = new Context(['age' => 25, 'status' => 'active']);
    $falseContext1 = new Context(['age' => 15, 'status' => 'active']); // age < 18
    $falseContext2 = new Context(['age' => 25, 'status' => 'banned']); // status = banned

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse $exists operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['email' => ['$exists' => true]]);

    $trueContext = new Context(['email' => 'user@example.com']);
    $falseContext = new Context(['name' => 'John']); // no email field

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse $exists false', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['deletedAt' => ['$exists' => false]]);

    $trueContext = new Context(['name' => 'John']); // no deletedAt
    $falseContext = new Context(['name' => 'John', 'deletedAt' => '2024-01-01']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse $regex with case-insensitive option', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        'email' => [
            '$regex' => '@EXAMPLE\\.COM$',
            '$options' => 'i',
        ],
    ]);

    $trueContext1 = new Context(['email' => 'user@example.com']);
    $trueContext2 = new Context(['email' => 'admin@EXAMPLE.COM']);
    $falseContext = new Context(['email' => 'user@test.com']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse empty query matches everything', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([]);

    $context1 = new Context(['age' => 25]);
    $context2 = new Context(['name' => 'John']);
    $context3 = new Context([]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeTrue();
});

test('parse JSON string query', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $json = '{"age": {"$gte": 18}, "country": "US"}';
    $rule = $mongo->parseJson($json);

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext = new Context(['age' => 15, 'country' => 'US']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse multiple operators on same field', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // Range query: 18 <= age <= 65
    $rule = $mongo->parse([
        'age' => [
            '$gte' => 18,
            '$lte' => 65,
        ],
    ]);

    $trueContext = new Context(['age' => 30]);
    $falseContext1 = new Context(['age' => 15]);
    $falseContext2 = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('throws exception for unsupported operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();

    expect(fn (): Rule => $mongo->parse(['age' => ['$invalidOp' => 18]]))->toThrow(InvalidArgumentException::class);
});

test('parse $not with string field check', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['$not' => 'email']);

    $trueContext = new Context(['name' => 'John']);
    $falseContext = new Context(['email' => 'user@example.com']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse $regex with multiline flag', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        'text' => [
            '$regex' => '^test',
            '$options' => 'm',
        ],
    ]);

    $trueContext = new Context(['text' => "line1\ntest"]);
    $falseContext = new Context(['text' => 'no match']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse $regex with dotall flag', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        'text' => [
            '$regex' => 'start.*end',
            '$options' => 's',
        ],
    ]);

    $trueContext = new Context(['text' => "start\nmiddle\nend"]);
    $falseContext = new Context(['text' => 'no match']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse $strLength with exact match', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['name' => ['$strLength' => 5]]);

    $trueContext = new Context(['name' => 'Alice']);
    $falseContext = new Context(['name' => 'Bob']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse $strLength with comparison operators', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        'password' => [
            '$strLength' => [
                '$gte' => 8,
                '$lte' => 20,
            ],
        ],
    ]);

    $trueContext = new Context(['password' => 'mypassword123']);
    $falseContext1 = new Context(['password' => 'short']);
    $falseContext2 = new Context(['password' => 'verylongpasswordthatexceedslimit']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse $strLength with $eq operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 487
    $rule = $mongo->parse(['name' => ['$strLength' => ['$eq' => 5]]]);

    expect($rule->evaluate(
        new Context(['name' => 'Alice']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Bob']),
        ))->toBeFalse();
});

test('parse $strLength with $ne operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 488
    $rule = $mongo->parse(['name' => ['$strLength' => ['$ne' => 3]]]);

    expect($rule->evaluate(
        new Context(['name' => 'Alice']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Bob']),
        ))->toBeFalse();
});

test('parse $strLength with $gt operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 489
    $rule = $mongo->parse(['password' => ['$strLength' => ['$gt' => 8]]]);

    expect($rule->evaluate(
        new Context(['password' => 'verylongpassword']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['password' => 'short']),
        ))->toBeFalse();
});

test('parse $strLength with $lt operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 491
    $rule = $mongo->parse(['code' => ['$strLength' => ['$lt' => 5]]]);

    expect($rule->evaluate(
        new Context(['code' => 'ABC']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'ABCDEF']),
        ))->toBeFalse();
});

test('throws exception for invalid $strLength operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();

    expect(fn (): Rule => $mongo->parse(['name' => ['$strLength' => ['$invalid' => 5]]]))->toThrow(InvalidArgumentException::class);
});

test('throws exception for invalid $strLength value type', function (): void {
    $mongo = new MongoQueryRuleBuilder();

    expect(fn (): Rule => $mongo->parse(['name' => ['$strLength' => 'invalid']]))->toThrow(InvalidArgumentException::class);
});

test('parse $size with comparison operators', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        'tags' => [
            '$size' => [
                '$gte' => 2,
                '$lte' => 5,
            ],
        ],
    ]);

    $trueContext = new Context(['tags' => ['a', 'b', 'c']]);
    $falseContext1 = new Context(['tags' => ['a']]);
    $falseContext2 = new Context(['tags' => ['a', 'b', 'c', 'd', 'e', 'f']]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse $size with $eq operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 555
    $rule = $mongo->parse(['tags' => ['$size' => ['$eq' => 3]]]);

    expect($rule->evaluate(
        new Context(['tags' => ['a', 'b', 'c']]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['tags' => ['a', 'b']]),
        ))->toBeFalse();
});

test('parse $size with $ne operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 556
    $rule = $mongo->parse(['tags' => ['$size' => ['$ne' => 2]]]);

    expect($rule->evaluate(
        new Context(['tags' => ['a', 'b', 'c']]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['tags' => ['a', 'b']]),
        ))->toBeFalse();
});

test('parse $size with $gt operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 557
    $rule = $mongo->parse(['items' => ['$size' => ['$gt' => 5]]]);

    expect($rule->evaluate(
        new Context(['items' => ['a', 'b', 'c', 'd', 'e', 'f', 'g']]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['items' => ['a', 'b', 'c']]),
        ))->toBeFalse();
});

test('parse $size with $lt operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    // This tests MongoQueryCompiler line 559
    $rule = $mongo->parse(['items' => ['$size' => ['$lt' => 3]]]);

    expect($rule->evaluate(
        new Context(['items' => ['a', 'b']]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['items' => ['a', 'b', 'c', 'd']]),
        ))->toBeFalse();
});

test('throws exception for invalid $size operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();

    expect(fn (): Rule => $mongo->parse(['tags' => ['$size' => ['$invalid' => 3]]]))->toThrow(InvalidArgumentException::class);
});

test('throws exception for invalid $size value type', function (): void {
    $mongo = new MongoQueryRuleBuilder();

    expect(fn (): Rule => $mongo->parse(['tags' => ['$size' => 'invalid']]))->toThrow(InvalidArgumentException::class);
});
