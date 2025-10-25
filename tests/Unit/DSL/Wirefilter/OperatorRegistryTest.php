<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\Wirefilter\OperatorRegistry;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Date\After;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Operators\Mathematical\Ceil;
use Cline\Ruler\Operators\Set\Union;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\Type\IsArray;

test('get returns correct operator class for comparison operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('eq'))->toBe(EqualTo::class)
        ->and($registry->get('gt'))->toBe(GreaterThan::class)
        ->and($registry->get('in'))->toBe(In::class);
});

test('get returns correct operator class for logical operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('and'))->toBe(LogicalAnd::class)
        ->and($registry->get('or'))->toBe(LogicalOr::class)
        ->and($registry->get('not'))->toBe(LogicalNot::class);
});

test('get returns correct operator class for mathematical operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('add'))->toBe(Addition::class)
        ->and($registry->get('ceil'))->toBe(Ceil::class);
});

test('get returns correct operator class for string operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('contains'))->toBe(StringContains::class);
});

test('get returns correct operator class for set operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('union'))->toBe(Union::class);
});

test('get returns correct operator class for type operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('isArray'))->toBe(IsArray::class);
});

test('get returns correct operator class for date operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->get('after'))->toBe(After::class);
});

test('get throws exception for unknown operator', function (): void {
    $registry = new OperatorRegistry();

    $registry->get('unknownOperator');
})->throws(LogicException::class, 'Unknown DSL operator: "unknownOperator"');

test('has returns true for registered operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->has('eq'))->toBeTrue()
        ->and($registry->has('gt'))->toBeTrue()
        ->and($registry->has('contains'))->toBeTrue();
});

test('has returns false for unregistered operators', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->has('unknownOperator'))->toBeFalse();
});

test('all returns array of all operator names', function (): void {
    $registry = new OperatorRegistry();

    $operators = $registry->all();

    expect($operators)
        ->toBeArray()
        ->toContain('eq', 'gt', 'and', 'or', 'contains', 'union', 'isArray', 'after')
        ->and(count($operators))->toBeGreaterThan(50);
});
