<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\SqlWhere\SqlWhereRuleBuilder;

test('parse simple comparison expression', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('age > 18');

    $context = new Context(['age' => 25]);

    expect($rule)->toBeInstanceOf(Rule::class)
        ->and($rule->evaluate($context))->toBeTrue();
});

test('parse comparison with field that fails', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('age > 18');

    $context = new Context(['age' => 15]);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse equality operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("status = 'active'");

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse logical and expression', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("age >= 18 AND country = 'US'");

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse logical or expression', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("age >= 21 OR country = 'US'");

    $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
    $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse expression with parentheses', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("(age >= 18 AND country = 'US') OR age >= 21");

    $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not expression', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('NOT (age < 18)');

    $trueContext = new Context(['age' => 25]);
    $falseContext = new Context(['age' => 15]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parseWithAction executes callback when true', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $executed = false;
    $rule = $srb->parseWithAction('age >= 18', function () use (&$executed): void {
        $executed = true;
    });

    $context = new Context(['age' => 25]);
    $rule->execute($context);

    expect($executed)->toBeTrue();
});

test('parseWithAction does not execute callback when false', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $executed = false;
    $rule = $srb->parseWithAction('age >= 18', function () use (&$executed): void {
        $executed = true;
    });

    $context = new Context(['age' => 15]);
    $rule->execute($context);

    expect($executed)->toBeFalse();
});

test('parse in operator with values', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("country IN ('US', 'CA', 'UK')");

    $trueContext = new Context(['country' => 'US']);
    $falseContext = new Context(['country' => 'FR']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse less than or equal operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('age <= 65');

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse inequality operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("status != 'inactive'");

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not in operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("role NOT IN ('banned', 'suspended')");

    $trueContext = new Context(['role' => 'active']);
    $falseContext = new Context(['role' => 'banned']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse like operator with percent wildcard', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("email LIKE '%@example.com'");

    $trueContext = new Context(['email' => 'john@example.com']);
    $falseContext = new Context(['email' => 'john@test.com']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not like operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("email NOT LIKE '%@test.com'");

    $trueContext = new Context(['email' => 'john@example.com']);
    $falseContext = new Context(['email' => 'john@test.com']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse between operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('age BETWEEN 18 AND 65');

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse is null operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('deleted_at IS NULL');

    $trueContext = new Context(['deleted_at' => null]);
    $falseContext = new Context(['deleted_at' => '2024-01-01']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse is not null operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('email IS NOT NULL');

    $trueContext = new Context(['email' => 'test@example.com']);
    $falseContext = new Context(['email' => null]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse alternative not equal operator', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("status <> 'inactive'");

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse dot notation for nested fields', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('user.profile.age >= 18');

    $context = new Context([
        'user' => ['profile' => ['age' => 25]],
    ]);

    expect($rule->evaluate($context))->toBeTrue();
});
