<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\DSL\MongoDB\MongoQueryRuleBuilder;

// ============================================================================
// STRICT COMPARISON OPERATORS
// ============================================================================

test('$same operator - strict equality', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$same' => 42]]);

    $trueContext = new Context(['value' => 42]);
    $falseContext1 = new Context(['value' => '42']); // String '42' !== int 42
    $falseContext2 = new Context(['value' => 42.0]); // Float might differ

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse();
});

test('$nsame operator - strict inequality', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$nsame' => '42']]);

    $trueContext = new Context(['value' => 42]); // int !== string
    $falseContext = new Context(['value' => '42']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$between operator with numeric range', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['age' => ['$between' => [18, 65]]]);

    $trueContext = new Context(['age' => 30]);
    $falseContext1 = new Context(['age' => 15]);
    $falseContext2 = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

// ============================================================================
// STRING OPERATORS
// ============================================================================

test('$startsWith operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['name' => ['$startsWith' => 'John']]);

    $trueContext = new Context(['name' => 'John Doe']);
    $falseContext = new Context(['name' => 'jane smith']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$startsWithi operator - case insensitive', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['name' => ['$startsWithi' => 'john']]);

    $trueContext1 = new Context(['name' => 'John Doe']);
    $trueContext2 = new Context(['name' => 'JOHN SMITH']);
    $falseContext = new Context(['name' => 'jane smith']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$endsWith operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['email' => ['$endsWith' => '@example.com']]);

    $trueContext = new Context(['email' => 'user@example.com']);
    $falseContext = new Context(['email' => 'user@test.org']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$endsWithi operator - case insensitive', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['email' => ['$endsWithi' => '@EXAMPLE.COM']]);

    $trueContext1 = new Context(['email' => 'user@example.com']);
    $trueContext2 = new Context(['email' => 'admin@EXAMPLE.COM']);
    $falseContext = new Context(['email' => 'user@test.org']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$contains operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['description' => ['$contains' => 'important']]);

    $trueContext = new Context(['description' => 'This is important information']);
    $falseContext = new Context(['description' => 'This is trivial']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$containsi operator - case insensitive', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['description' => ['$containsi' => 'IMPORTANT']]);

    $trueContext1 = new Context(['description' => 'This is important information']);
    $trueContext2 = new Context(['description' => 'This is IMPORTANT']);
    $falseContext = new Context(['description' => 'This is trivial']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$notContains operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['description' => ['$notContains' => 'spam']]);

    $trueContext = new Context(['description' => 'This is a clean message']);
    $falseContext = new Context(['description' => 'This is spam']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$notContainsi operator - case insensitive', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['description' => ['$notContainsi' => 'SPAM']]);

    $trueContext = new Context(['description' => 'This is a clean message']);
    $falseContext1 = new Context(['description' => 'This is spam']);
    $falseContext2 = new Context(['description' => 'This is SPAM']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('$strLength with exact match', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['code' => ['$strLength' => 5]]);

    $trueContext = new Context(['code' => 'ABCDE']);
    $falseContext = new Context(['code' => 'ABC']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$strLength with comparison operators', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['password' => ['$strLength' => ['$gte' => 8]]]);

    $trueContext = new Context(['password' => 'securePassword']);
    $falseContext = new Context(['password' => 'short']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$notRegex operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['username' => ['$notRegex' => '/[^a-zA-Z0-9]/']]);

    $trueContext = new Context(['username' => 'user123']);
    $falseContext = new Context(['username' => 'user@123']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

// ============================================================================
// DATE OPERATORS
// ============================================================================

test('$after operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['createdAt' => ['$after' => '2024-01-01']]);

    $trueContext = new Context(['createdAt' => '2024-06-15']);
    $falseContext = new Context(['createdAt' => '2023-12-31']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$before operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['expiresAt' => ['$before' => '2024-12-31']]);

    $trueContext = new Context(['expiresAt' => '2024-06-15']);
    $falseContext = new Context(['expiresAt' => '2025-01-01']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$betweenDates operator', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['eventDate' => ['$betweenDates' => ['2024-01-01', '2024-12-31']]]);

    $trueContext = new Context(['eventDate' => '2024-06-15']);
    $falseContext1 = new Context(['eventDate' => '2023-12-31']);
    $falseContext2 = new Context(['eventDate' => '2025-01-01']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

// ============================================================================
// TYPE OPERATORS
// ============================================================================

test('$type operator with null', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$type' => 'null']]);

    $trueContext = new Context(['value' => null]);
    $falseContext = new Context(['value' => '']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$type operator with array', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['value' => ['$type' => 'array']]);

    $trueContext = new Context(['value' => [1, 2, 3]]);
    $falseContext = new Context(['value' => 'not array']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$type operator with boolean', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['flag' => ['$type' => 'bool']]);

    $trueContext = new Context(['flag' => true]);
    $falseContext = new Context(['flag' => 1]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$type operator with numeric', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['count' => ['$type' => 'number']]);

    $trueContext1 = new Context(['count' => 42]);
    $trueContext2 = new Context(['count' => 3.14]);
    $trueContext3 = new Context(['count' => '42']); // PHP's is_numeric() accepts numeric strings
    $falseContext = new Context(['count' => 'abc']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$type operator with string', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['name' => ['$type' => 'string']]);

    $trueContext = new Context(['name' => 'John']);
    $falseContext = new Context(['name' => 123]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$size operator with exact count', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['tags' => ['$size' => 3]]);

    $trueContext = new Context(['tags' => ['a', 'b', 'c']]);
    $falseContext = new Context(['tags' => ['a', 'b']]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$size operator with comparison operators', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['items' => ['$size' => ['$gte' => 5]]]);

    $trueContext = new Context(['items' => [1, 2, 3, 4, 5, 6]]);
    $falseContext = new Context(['items' => [1, 2, 3]]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$empty operator - should be empty', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['data' => ['$empty' => true]]);

    $trueContext1 = new Context(['data' => []]);
    $trueContext2 = new Context(['data' => '']);
    $trueContext3 = new Context(['data' => null]);
    $falseContext = new Context(['data' => [1, 2, 3]]);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('$empty operator - should not be empty', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse(['data' => ['$empty' => false]]);

    $trueContext = new Context(['data' => [1, 2, 3]]);
    $falseContext1 = new Context(['data' => []]);
    $falseContext2 = new Context(['data' => '']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

// ============================================================================
// LOGICAL OPERATORS
// ============================================================================

test('$xor operator - exactly one condition must be true', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        '$xor' => [
            ['age' => ['$gte' => 18]],
            ['country' => 'US'],
        ],
    ]);

    $trueContext1 = new Context(['age' => 25, 'country' => 'CA']); // Only age passes
    $trueContext2 = new Context(['age' => 15, 'country' => 'US']); // Only country passes
    $falseContext1 = new Context(['age' => 25, 'country' => 'US']); // Both pass
    $falseContext2 = new Context(['age' => 15, 'country' => 'CA']); // Neither pass

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('$nand operator - not all conditions can be true', function (): void {
    $mongo = new MongoQueryRuleBuilder();
    $rule = $mongo->parse([
        '$nand' => [
            ['age' => ['$gte' => 18]],
            ['country' => 'US'],
        ],
    ]);

    $trueContext1 = new Context(['age' => 15, 'country' => 'US']); // Not all true
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']); // Not all true
    $trueContext3 = new Context(['age' => 15, 'country' => 'CA']); // Neither true
    $falseContext = new Context(['age' => 25, 'country' => 'US']); // Both true

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});
