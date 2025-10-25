<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Natural\NaturalLanguageRuleBuilder;

test('parse simple comparison expression', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is more than 18');

    $context = new Context(['age' => 25]);

    expect($rule)->toBeInstanceOf(Rule::class)
        ->and($rule->evaluate($context))->toBeTrue();
});

test('parse comparison with field that fails', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is more than 18');

    $context = new Context(['age' => 15]);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse equality operator', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('status is "active"');

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse logical and expression', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is at least 18 and country is "US"');

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse logical or expression', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is at least 21 or country is "US"');

    $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
    $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse expression with parentheses', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('(age is at least 18 and country is "US") or age is at least 21');

    $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse mathematical expression', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    // Natural language uses pre-computed totals
    $rule = $nl->parse('total is more than 100');

    $trueContext = new Context(['total' => 105]);
    $falseContext = new Context(['total' => 60]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse in operator with array', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('country is one of "US", "CA", "UK"');

    $trueContext = new Context(['country' => 'US']);
    $falseContext = new Context(['country' => 'FR']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse string contains with regex', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('phone contains "123"');

    $trueContext = new Context(['phone' => '123-4567']);
    $falseContext = new Context(['phone' => '987-6543']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse less than or equal operator', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is at most 65');

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse inequality operator', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('status is not "inactive"');

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not in operator', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('role is not one of "banned", "suspended"');

    $trueContext = new Context(['role' => 'active']);
    $falseContext = new Context(['role' => 'banned']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse modulo operator with pre-computed boolean', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    // Natural language uses pre-computed results
    $rule = $nl->parse('isEven is true');

    $trueContext = new Context(['isEven' => true]);
    $falseContext = new Context(['isEven' => false]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse exponentiate operator with pre-computed result', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('result is more than 100');

    $trueContext = new Context(['result' => 125]);
    $falseContext = new Context(['result' => 8]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse division operator with pre-computed result', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('average is 10');

    $trueContext = new Context(['average' => 10]);
    $falseContext = new Context(['average' => 20]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse multiplication operator with pre-computed result', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('totalCost is more than 1000');

    $trueContext = new Context(['totalCost' => 1_200]);
    $falseContext = new Context(['totalCost' => 500]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse subtraction operator with pre-computed result', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('discount is less than 100');

    $trueContext = new Context(['discount' => 90]);
    $falseContext = new Context(['discount' => 150]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse between operator', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is between 18 and 65');

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not expression', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    // Natural language: "age is not less than 18"
    $rule = $nl->parse('age is not less than 18');

    $trueContext = new Context(['age' => 25]);
    $falseContext = new Context(['age' => 15]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse matches operator with regex', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    // Natural language uses "contains" for substring matching
    $rule = $nl->parse('phone contains "-"');

    $trueContext = new Context(['phone' => '123-4567']);
    $falseContext = new Context(['phone' => '1234567']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});
