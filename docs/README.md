---
title: Getting Started
description: Install and start using Ruler for fluent rule evaluation and conditional business logic in PHP.
---

Ruler is a fluent rule engine with proposition-based evaluation and 50+ operators for building conditional business logic. Create readable, testable rules using either the fluent PHP API or text-based DSL syntax.

## Installation

```bash
composer require cline/ruler
```

## Quick Start

### Using RuleBuilder (Fluent API)

```php
use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Context;

$rb = new RuleBuilder;

// Create a rule with logical conditions
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['minNumPeople']->lessThanOrEqualTo($rb['actualNumPeople']),
        $rb['maxNumPeople']->greaterThanOrEqualTo($rb['actualNumPeople'])
    ),
    function() {
        echo 'Capacity is within range!';
    }
);

// Evaluate with context
$context = new Context([
    'minNumPeople' => 5,
    'maxNumPeople' => 25,
    'actualNumPeople' => fn() => 6,  // Lazy evaluation
]);

$rule->execute($context);  // "Capacity is within range!"
```

### Using Text DSL

```php
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;
use Cline\Ruler\Core\Context;

$srb = new StringRuleBuilder;

// Create rule from text
$rule = $srb->parse('age >= 18 and country == "US"');

// Evaluate
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context);  // true
```

## Core Concepts

### Variables

Placeholders that resolve to values during evaluation:

```php
$rb['userName'];           // Simple variable
$rb['user']['roles'];      // Nested property access
```

### Context

The ViewModel providing values for rule evaluation. Supports both static values and lazy evaluation:

```php
$context = new Context([
    'staticValue' => 42,
    'lazyValue' => fn() => expensiveOperation(),
]);
```

### Propositions

Building blocks of rules—comparisons and checks that evaluate to boolean:

```php
$rb['age']->greaterThanOrEqualTo(18);
$rb['status']->equalTo('active');
```

### Rules

Combinations of propositions with optional action callbacks:

```php
$rule = $rb->create(
    $rb['age']->greaterThanOrEqualTo(18),
    fn() => grantAccess()
);

$rule->evaluate($context);  // Returns bool
$rule->execute($context);   // Runs callback if true
```

### RuleSet

Collection of rules executed together:

```php
$rules = new RuleSet([$rule1, $rule2, $rule3]);
$rules->executeRules($context);  // Executes all matching rules
```

## Without RuleBuilder

For more control, construct rules directly:

```php
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Core\Operator;
use Cline\Ruler\Core\Context;

$actualNumPeople = new Variable('actualNumPeople');

$rule = new Rule(
    new Operator\LogicalAnd([
        new Operator\LessThanOrEqualTo(new Variable('minNumPeople'), $actualNumPeople),
        new Operator\GreaterThanOrEqualTo(new Variable('maxNumPeople'), $actualNumPeople)
    ]),
    fn() => echo 'YAY!'
);

$context = new Context([
    'minNumPeople' => 5,
    'maxNumPeople' => 25,
    'actualNumPeople' => fn() => 6,
]);

$rule->execute($context);
```

## Extensibility

Ruler focuses exclusively on rule evaluation logic. Rule storage and retrieval are left to your implementation—whether that's an ORM, ODM, file-based DSL, or custom solution. This separation allows Ruler to integrate seamlessly into any architecture.
