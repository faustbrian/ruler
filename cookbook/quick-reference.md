# Quick Reference

## Core Concepts

**Variables**: Placeholders replaced by values during evaluation
**Propositions**: Building blocks of rules (comparisons, checks)
**Rules**: Combinations of propositions with optional actions
**Context**: ViewModel providing values for rule evaluation
**RuleSet**: Collection of rules executed together

## Common Patterns

### Create rules with RuleBuilder

```php
$rb = new RuleBuilder;
$rule = $rb->create($rb['user']->equalTo('admin'));
```

### Evaluate rules

```php
$rule->evaluate($context); // returns bool
```

### Execute rules with actions

```php
$rule->execute($context); // runs callback if true
```

### Combine rules

```php
$rb->logicalAnd($ruleA, $ruleB);
$rb->logicalOr($ruleA, $ruleB);
$rb->logicalNot($rule);
```

### Work with context

```php
$context = new Context(['key' => 'value']);
$context['lazy'] = fn() => expensiveOperation();
```

## Highlights

- Simple, straightforward DSL provided by RuleBuilder
- Stateless evaluation with Context as ViewModel
- Combine rules using logical operators (AND, OR, XOR, NOT)
- Support for comparison, mathematical, and set operations
- Lazy evaluation of context variables
- Access object properties, methods, and array offsets
- Extensible with custom operators
- Execute actions based on rule evaluation

## Extensibility

Ruler focuses exclusively on rule evaluation logic. Rule storage and retrieval are left to your implementation—whether that's an ORM, ODM, file-based DSL, or custom solution. This separation allows Ruler to integrate seamlessly into any architecture.
