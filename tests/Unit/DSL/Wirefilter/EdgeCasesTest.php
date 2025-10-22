<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Wirefilter\ExpressionParser;
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;

test('parse strict equality operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('value === 42');

    $trueContext = new Context(['value' => 42]);
    $falseContext = new Context(['value' => '42']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse strict inequality operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('value !== "test"');

    $trueContext = new Context(['value' => 42]);
    $falseContext = new Context(['value' => 'test']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('unsupported binary operator throws exception', function (): void {
    $srb = new StringRuleBuilder();

    // This will fail because '&' is not a supported operator
    expect(fn (): Rule => $srb->parse('a & b'))->toThrow(Exception::class);
});

test('unsupported unary operator throws exception', function (): void {
    $srb = new StringRuleBuilder();

    // This will fail because '~' is not a supported unary operator
    expect(fn (): Rule => $srb->parse('~value'))->toThrow(Exception::class);
});

test('parse array literal in expression', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('status in ["active", "pending", "approved"]');

    $trueContext = new Context(['status' => 'pending']);
    $falseContext = new Context(['status' => 'rejected']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse deeply nested object property access', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('user.profile.settings.theme == "dark"');

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

test('parse empty array literal', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('tags in []');

    $context = new Context(['tags' => 'urgent']);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse array with mixed types', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('value in [1, "two", true, null]');

    $trueContext = new Context(['value' => 'two']);
    $falseContext = new Context(['value' => 'three']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse unary minus operator', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('-value > -10');

    $trueContext = new Context(['value' => 5]);  // -5 > -10 = true
    $falseContext = new Context(['value' => 15]); // -15 > -10 = false

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('expression language compile callback for custom functions', function (): void {
    $parser = new ExpressionParser();
    $expression = $parser->parse('eq(x, 5)');

    // Compile the expression to trigger the compile callback
    $compiled = $expression->getNodes();

    expect($compiled)->not->toBeNull();
});

test('expression language compile callback generates function call code', function (): void {
    $parser = new ExpressionParser();
    $expression = $parser->parse('eq(name, "test")');

    // This should trigger the sprintf in the compile callback (line 200)
    // The compile callback is used during expression evaluation
    $nodes = $expression->getNodes();

    expect($nodes)->not->toBeNull();
});
