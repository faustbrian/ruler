<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;

test('parse simple comparison expression', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('age > 18');

    $context = new Context(['age' => 25]);

    expect($rule)->toBeInstanceOf(Rule::class)
        ->and($rule->evaluate($context))->toBeTrue();
});

test('parse comparison with field that fails', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('age > 18');

    $context = new Context(['age' => 15]);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse equality operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('status == "active"');

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse logical and expression', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('age >= 18 and country == "US"');

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse logical or expression', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('age >= 21 or country == "US"');

    $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
    $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse expression with parentheses', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('(age >= 18 and country == "US") or age >= 21');

    $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse mathematical expression', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('price + shipping > 100');

    $trueContext = new Context(['price' => 80, 'shipping' => 25]);
    $falseContext = new Context(['price' => 50, 'shipping' => 10]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not expression', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('not (age < 18)');

    $trueContext = new Context(['age' => 25]);
    $falseContext = new Context(['age' => 15]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parseWithAction executes callback when true', function (): void {
    $srb = new StringRuleBuilder();
    $executed = false;
    $rule = $srb->parseWithAction('age >= 18', function () use (&$executed): void {
        $executed = true;
    });

    $context = new Context(['age' => 25]);
    $rule->execute($context);

    expect($executed)->toBeTrue();
});

test('parseWithAction does not execute callback when false', function (): void {
    $srb = new StringRuleBuilder();
    $executed = false;
    $rule = $srb->parseWithAction('age >= 18', function () use (&$executed): void {
        $executed = true;
    });

    $context = new Context(['age' => 15]);
    $rule->execute($context);

    expect($executed)->toBeFalse();
});

test('parse in operator with array', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('country in ["US", "CA", "UK"]');

    $trueContext = new Context(['country' => 'US']);
    $falseContext = new Context(['country' => 'FR']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse matches operator with regex', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('phone matches "/^\\\\d{3}-\\\\d{4}$/"');

    $trueContext = new Context(['phone' => '123-4567']);
    $falseContext = new Context(['phone' => '12-34567']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse less than or equal operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('age <= 65');

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse inequality operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('status != "inactive"');

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse notIn operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('role not in ["banned", "suspended"]');

    $trueContext = new Context(['role' => 'active']);
    $falseContext = new Context(['role' => 'banned']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse modulo operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('value % 2 == 0');

    $trueContext = new Context(['value' => 10]);
    $falseContext = new Context(['value' => 11]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse exponentiate operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('base ** power > 100');

    $trueContext = new Context(['base' => 5, 'power' => 3]);
    $falseContext = new Context(['base' => 2, 'power' => 3]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse negate operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('-value < 0');

    $trueContext = new Context(['value' => 10]);
    $falseContext = new Context(['value' => -10]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse division operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('total / count == 10');

    $trueContext = new Context(['total' => 100, 'count' => 10]);
    $falseContext = new Context(['total' => 100, 'count' => 5]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse multiplication operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('quantity * price > 1000');

    $trueContext = new Context(['quantity' => 20, 'price' => 60]);
    $falseContext = new Context(['quantity' => 10, 'price' => 50]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse subtraction operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('total - discount < 100');

    $trueContext = new Context(['total' => 120, 'discount' => 30]);
    $falseContext = new Context(['total' => 200, 'discount' => 50]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});
