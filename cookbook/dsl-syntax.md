# Text-Based DSL Syntax

Ruler now supports a Wirefilter-style text-based DSL for creating rules using natural, readable syntax. This is a complement to the existing fluent PHP API—both approaches produce identical results.

## Quick Start

```php
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;
use Cline\Ruler\Core\Context;

$srb = new StringRuleBuilder;

// Create a rule from text
$rule = $srb->parse('age >= 18 and country == "US"');

// Evaluate with context
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true

// Add action callback
$rule = $srb->parseWithAction(
    'price + shipping > 100',
    fn() => applyFreeShipping()
);
$rule->execute($context);
```

## Syntax Overview

### Comparison Operators

```php
age > 18                    // Greater than
age >= 21                   // Greater than or equal
age < 65                    // Less than
age <= 64                   // Less than or equal
status == "active"          // Loose equality
status != "inactive"        // Loose inequality
role === "admin"            // Strict equality
role !== "guest"            // Strict inequality
country in ["US", "CA"]     // Array membership
role notIn ["banned"]       // Array exclusion
```

### Logical Operators

```php
age >= 18 and country == "US"                    // AND
age >= 21 or country == "US"                     // OR
not (age < 18)                                   // NOT
(age >= 18 and country == "US") or age >= 21    // Parentheses for grouping
```

### Mathematical Operators

```php
price + shipping > 100      // Addition
total - discount < 500      // Subtraction
quantity * price >= 1000    // Multiplication
total / items > 10          // Division
value % 2 == 0              // Modulo
```

### String Operators

Use function-style syntax for string operations:

```php
contains(email, "@example.com")                  // String contains
startsWith(username, "admin_")                   // Starts with
endsWith(filename, ".pdf")                       // Ends with
matches(phone, "^\\d{3}-\\d{4}$")               // Regex match
```

### Complex Nested Rules

```php
// User eligibility check
(user.age >= 18 and user.country in ["US", "CA"]) or
(user.isVerified == true and user.role != "guest")

// Pricing logic
(price + shipping > 100 and user.isPremium == true) or
(price > 200)

// Combined conditions
user.age >= 18 and
country == "US" and
not (status == "banned") and
(subscription == "premium" or purchases > 10)
```

## DSL vs Fluent PHP Comparison

Both syntaxes produce identical Operator trees and evaluation results:

**DSL Syntax:**
```php
$srb = new StringRuleBuilder;
$rule = $srb->parse('age >= 18 and country == "US"');
```

**Fluent PHP:**
```php
$rb = new RuleBuilder;
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['age']->greaterThanOrEqualTo(18),
        $rb['country']->equalTo('US')
    )
);
```

## Operator Precedence

1. **Parentheses** - `()`
2. **Unary operators** - `not`, `-`
3. **Exponentiation** - `**`
4. **Multiplicative** - `*`, `/`, `%`
5. **Additive** - `+`, `-`
6. **Comparison** - `>`, `>=`, `<`, `<=`
7. **Equality** - `==`, `!=`, `===`, `!==`
8. **Logical AND** - `and`
9. **Logical OR** - `or`

Use parentheses for explicit grouping when precedence is unclear.

## Variable Naming

Variables use simple names or dot-notation for nested properties:

```php
age                         // Simple variable
user.age                    // Nested property
http.request.uri.path       // Deeply nested
```

The DSL automatically resolves these to Variable and VariableProperty chains.

## Type Handling

The DSL automatically infers types:

```php
age > 18                    // Numeric comparison
status == "active"          // String comparison
tags in ["urgent"]          // Array membership
enabled == true             // Boolean comparison
count == null               // Null comparison
```

## Advanced Features

### With Actions

```php
$rule = $srb->parseWithAction(
    'temperature > 100',
    function() {
        triggerAlert('High temperature detected');
    }
);

$rule->execute($context);
```

### Reusable Builder

```php
// Share a RuleBuilder instance for cached Variables
$rb = new RuleBuilder;
$srb = new StringRuleBuilder($rb);

$rule1 = $srb->parse('age >= 18');
$rule2 = $srb->parse('age >= 21');
// Both rules share the same Variable('age') instance
```

## Benefits

✅ **Readable** - Natural syntax resembles English
✅ **Concise** - Less verbose than fluent PHP for complex rules
✅ **Portable** - Store rules as strings in databases/config
✅ **Compatible** - 100% compatible with fluent PHP API
✅ **Type-safe** - Full operator type checking at compile time
✅ **Tested** - Same rigorous testing as fluent API

## When to Use DSL vs Fluent PHP

**Use DSL when:**
- Rules are stored as text (database, config files)
- Non-developers need to read/understand rules
- Brevity is important for complex expressions
- Rules are generated from UI forms

**Use Fluent PHP when:**
- IDE autocomplete is important
- Type safety at the code level is critical
- Rules are built programmatically
- PHP code generation is your workflow

Both approaches coexist seamlessly—choose what works best for your use case.
