<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\DSL\JMESPath\JMESPathAdapter;
use Cline\Ruler\DSL\JMESPath\JMESPathProposition;
use Cline\Ruler\DSL\JMESPath\JMESPathRuleBuilder;

test('basic comparison works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('age >= `18`');

    expect($rule->evaluate(
        new Context(['age' => 20]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['age' => 16]),
    ))->toBeFalse();
});

test('equality comparison works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("country == 'US'");

    expect($rule->evaluate(
        new Context(['country' => 'US']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['country' => 'CA']),
    ))->toBeFalse();
});

test('logical AND works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("age >= `18` && country == 'US'");

    expect($rule->evaluate(
        new Context(['age' => 20, 'country' => 'US']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['age' => 20, 'country' => 'FR']),
    ))->toBeFalse();
    expect($rule->evaluate(
        new Context(['age' => 16, 'country' => 'US']),
    ))->toBeFalse();
});

test('logical OR works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("status == 'active' || status == 'pending'");

    expect($rule->evaluate(
        new Context(['status' => 'active']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['status' => 'pending']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['status' => 'deleted']),
    ))->toBeFalse();
});

test('logical NOT works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("!(status == 'banned')");

    expect($rule->evaluate(
        new Context(['status' => 'active']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['status' => 'banned']),
    ))->toBeFalse();
});

test('contains function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("contains(tags, 'premium')");

    expect($rule->evaluate(
        new Context(['tags' => ['premium', 'verified']]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['tags' => ['basic', 'verified']]),
    ))->toBeFalse();
});

test('starts_with function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("starts_with(email, 'admin')");

    expect($rule->evaluate(
        new Context(['email' => 'admin@example.com']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['email' => 'user@example.com']),
    ))->toBeFalse();
});

test('ends_with function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("ends_with(email, '@example.com')");

    expect($rule->evaluate(
        new Context(['email' => 'john@example.com']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['email' => 'john@test.com']),
    ))->toBeFalse();
});

test('array length works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('length(tags) > `3`');

    expect($rule->evaluate(
        new Context(['tags' => ['a', 'b', 'c', 'd']]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['tags' => ['a', 'b']]),
    ))->toBeFalse();
});

test('nested field access works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('user.profile.age >= `18`');

    $context = new Context([
        'user' => ['profile' => ['age' => 25]],
    ]);
    expect($rule->evaluate($context))->toBeTrue();

    $context = new Context([
        'user' => ['profile' => ['age' => 16]],
    ]);
    expect($rule->evaluate($context))->toBeFalse();
});

test('array filtering works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('length(orders[?total > `100`]) > `0`');

    $context = new Context([
        'orders' => [
            ['total' => 150],
            ['total' => 50],
            ['total' => 200],
        ],
    ]);
    expect($rule->evaluate($context))->toBeTrue();

    $context = new Context([
        'orders' => [
            ['total' => 50],
            ['total' => 75],
        ],
    ]);
    expect($rule->evaluate($context))->toBeFalse();
});

test('max function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('max(scores) > `90`');

    expect($rule->evaluate(
        new Context(['scores' => [75, 88, 95, 82]]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['scores' => [75, 88, 82]]),
    ))->toBeFalse();
});

test('min function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('min(prices) >= `10`');

    expect($rule->evaluate(
        new Context(['prices' => [10, 20, 30]]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['prices' => [5, 20, 30]]),
    ))->toBeFalse();
});

test('type checking works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("type(age) == 'number'");

    expect($rule->evaluate(
        new Context(['age' => 25]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['age' => '25']),
    ))->toBeFalse();
});

test('not_null function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('not_null(email)');

    expect($rule->evaluate(
        new Context(['email' => 'test@example.com']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['email' => null]),
    ))->toBeFalse();
});

test('complex nested expression works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse(
        "age >= `18` && age < `65` && contains(['US', 'CA', 'UK'], country) && emailVerified == `true`",
    );

    $valid = new Context([
        'age' => 30,
        'country' => 'US',
        'emailVerified' => true,
    ]);
    expect($rule->evaluate($valid))->toBeTrue();

    $invalid = new Context([
        'age' => 30,
        'country' => 'FR',
        'emailVerified' => true,
    ]);
    expect($rule->evaluate($invalid))->toBeFalse();
});

test('parentheses for grouping work', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('(age >= `18` && age < `65`) || vip == `true`');

    expect($rule->evaluate(
        new Context(['age' => 30, 'vip' => false]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['age' => 70, 'vip' => true]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['age' => 70, 'vip' => false]),
    ))->toBeFalse();
});

test('boolean literals work', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('verified == `true`');

    expect($rule->evaluate(
        new Context(['verified' => true]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['verified' => false]),
    ))->toBeFalse();
});

test('null comparison works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('deletedAt == `null`');

    expect($rule->evaluate(
        new Context(['deletedAt' => null]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['deletedAt' => '2024-01-01']),
    ))->toBeFalse();
});

test('array indexing works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("tags[0] == 'premium'");

    expect($rule->evaluate(
        new Context(['tags' => ['premium', 'verified']]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['tags' => ['basic', 'verified']]),
    ))->toBeFalse();
});

test('sum function works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('sum(values) > `100`');

    expect($rule->evaluate(
        new Context(['values' => [30, 40, 50]]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['values' => [10, 20, 30]]),
    ))->toBeFalse();
});

test('truthy conversion works for arrays', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('tags');

    expect($rule->evaluate(
        new Context(['tags' => ['a', 'b']]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['tags' => []]),
    ))->toBeFalse();
});

test('truthy conversion works for strings', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('name');

    expect($rule->evaluate(
        new Context(['name' => 'John']),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['name' => '']),
    ))->toBeFalse();
});

test('truthy conversion works for numbers', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('count');

    expect($rule->evaluate(
        new Context(['count' => 5]),
    ))->toBeTrue();
    expect($rule->evaluate(
        new Context(['count' => 0]),
    ))->toBeFalse();
});

test('validate returns true for valid expression', function (): void {
    $jmes = new JMESPathRuleBuilder();

    expect($jmes->validate('age >= `18`'))->toBeTrue();
});

test('validate returns false for invalid expression', function (): void {
    $jmes = new JMESPathRuleBuilder();

    expect($jmes->validate('age >= [invalid'))->toBeFalse();
});

test('JMESPathProposition toString works', function (): void {
    $adapter = new JMESPathAdapter();
    $proposition = new JMESPathProposition('age >= `18`', $adapter);

    expect((string) $proposition)->toContain('JMESPath');
    expect((string) $proposition)->toContain('age >= `18`');
});
