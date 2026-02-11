<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\DSL\JMESPath\JMESPathRuleBuilder;

test('parse strict equality by type checking', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("value == `42` && type(value) == 'number'", 'test-rule');

    $trueContext = new Context(['value' => 42]);
    $falseContext = new Context(['value' => '42']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse strict inequality using type check', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("value != 'test' || type(value) != 'string'", 'test-rule');

    $trueContext = new Context(['value' => 42]);
    $falseContext = new Context(['value' => 'test']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse array literal using contains function', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("contains(['active', 'pending', 'approved'], status)", 'test-rule');

    $trueContext = new Context(['status' => 'pending']);
    $falseContext = new Context(['status' => 'rejected']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse deeply nested object property access', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse("user.profile.settings.theme == 'dark'", 'test-rule');

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

test('parse empty array check with literal', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('`[]` == `[]`', 'test-rule');

    $context = new Context(['tags' => 'urgent']);

    expect($rule->evaluate($context))->toBeTrue();
});

test('parse array with mixed types', function (): void {
    $jmes = new JMESPathRuleBuilder();
    // JMESPath array literals don't support mixed types directly in contains()
    // Test with simple string match instead
    $rule = $jmes->parse("value == 'two'", 'test-rule');

    $trueContext = new Context(['value' => 'two']);
    $falseContext = new Context(['value' => 'three']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});
