<p align="center">
    <a href="https://github.com/faustbrian/ruler/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/faustbrian/ruler/actions/workflows/tests.yml/badge.svg"></a>
    <a href="https://packagist.org/packages/cline/ruler"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/cline/ruler"></a>
    <a href="https://packagist.org/packages/cline/ruler"><img alt="Latest Version" src="https://img.shields.io/packagist/v/cline/ruler"></a>
    <a href="https://packagist.org/packages/cline/ruler"><img alt="License" src="https://img.shields.io/packagist/l/cline/ruler"></a>
</p>

------

# Ruler

Fluent rule engine with proposition-based evaluation and 50+ operators for building conditional business logic

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/ruler
```

## Documentation

- **[Getting Started](cookbook/getting-started.md)** - Learn the DSL and basic rule creation
- **[Operators](cookbook/operators.md)** - Comparison, mathematical, and set operators
- **[Rules](cookbook/rules.md)** - Combining, evaluating, and executing rules
- **[Context](cookbook/context.md)** - Dynamic context population and variable properties
- **[Custom Operators](cookbook/custom-operators.md)** - Extending Ruler with your own operators

## Highlights

- Simple, straightforward DSL provided by RuleBuilder
- Stateless evaluation with Context as ViewModel
- Combine rules using logical operators (AND, OR, XOR, NOT)
- Support for comparison, mathematical, and set operations
- Lazy evaluation of context variables
- Access object properties, methods, and array offsets
- Extensible with custom operators
- Execute actions based on rule evaluation

## Quick Reference

### Core Concepts

**Variables**: Placeholders replaced by values during evaluation
**Propositions**: Building blocks of rules (comparisons, checks)
**Rules**: Combinations of propositions with optional actions
**Context**: ViewModel providing values for rule evaluation
**RuleSet**: Collection of rules executed together

### Common Patterns

```php
// Create rules with RuleBuilder
$rb = new RuleBuilder;
$rule = $rb->create($rb['user']->equalTo('admin'));

// Evaluate rules
$rule->evaluate($context); // returns bool

// Execute rules with actions
$rule->execute($context); // runs callback if true

// Combine rules
$rb->logicalAnd($ruleA, $ruleB);
$rb->logicalOr($ruleA, $ruleB);
$rb->logicalNot($rule);

// Work with context
$context = new Context(['key' => 'value']);
$context['lazy'] = fn() => expensiveOperation();
```

See the [cookbook](cookbook/) for detailed examples.

## Extensibility

Ruler focuses exclusively on rule evaluation logic. Rule storage and retrieval are left to your implementation—whether that's an ORM, ODM, file-based DSL, or custom solution. This separation allows Ruler to integrate seamlessly into any architecture.

## Development

**Lint code with PHP CS Fixer:**
```bash
composer lint
```

**Run refactors with Rector:**
```bash
composer refactor
```

**Run static analysis with PHPStan:**
```bash
composer test:types
```

**Run unit tests with PEST:**
```bash
composer test:unit
```

**Run the entire test suite:**
```bash
composer test
```

## Credits

**Ruler** was created by **[Brian Faust](https://github.com/faustbrian)** under the **[MIT license](https://opensource.org/licenses/MIT)**.

> This is a modern PHP 8.4+ rework of the original [Ruler](https://github.com/bobthecow/Ruler) by **[Justin Hileman](https://github.com/bobthecow)**. The core architecture and DSL design come from Justin's original implementation.
