# Wirefilter-Style DSL Design for Ruler

**Goal:** Implement a Cloudflare Wirefilter-inspired text-based DSL using `symfony/expression-language` while maintaining backward compatibility with the existing fluent PHP API.

## Current Architecture Analysis

### Core Components

1. **RuleBuilder** - Factory for creating rules via fluent PHP DSL
2. **Variable** - Represents fields/values with fluent operator methods
3. **Operators** (62 total) - Implement comparison, logical, mathematical, string, set, type, and date operations
4. **Context** - Runtime value container for rule evaluation
5. **Rule** - Combines Proposition with optional action callback
6. **Proposition** - Boolean-evaluatable condition

### Existing PHP DSL

```php
$rb = new RuleBuilder;
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['user']['age']->greaterThanOrEqualTo(18),
        $rb['user']['country']->in(['US', 'CA'])
    )
);
```

---

## Proposed Text-Based DSL Syntax

### Philosophy

- **Natural reading** - Left-to-right evaluation like English
- **Field namespacing** - Dot notation for nested data (`user.age`, `http.host`)
- **Explicit operators** - Text operators (`and`, `or`, `not`) over symbols where clarity helps
- **Type-aware** - Smart handling of strings, numbers, arrays, dates
- **Parentheses** - Explicit grouping to avoid precedence confusion

### Basic Syntax Examples

```javascript
// Comparison
user.age >= 18
http.host eq "example.com"
ip.src ne "1.2.3.4"

// Logical combinations
user.age >= 18 and user.country in ["US", "CA"]
http.host contains "admin" or ip.src eq "1.2.3.4"
not (user.role eq "guest")

// Mathematical operations
price + shipping > 100
(quantity * price) - discount <= 1000

// String operations
http.path startsWith "/api"
user.email endsWith "@example.com"
username contains "admin"
bio matches "regex_pattern"

// Set operations
tags containsSubset ["urgent", "security"]
permissions setContains "write"

// Date operations
created_at after "2024-01-01"
expires_at before "2025-12-31"
event_date isBetweenDates ["2024-01-01", "2024-12-31"]

// Type checks
user.metadata isArray
user.phone isNumeric
user.bio isEmpty

// Complex nested rules
(user.age >= 18 and user.country in ["US", "CA"]) or
(user.isVerified eq true and user.role ne "guest")
```

---

## Operator Mapping

### Comparison Operators (11 total)

| DSL Syntax | PHP Method | Operator Class | Notes |
|------------|------------|----------------|-------|
| `>` | `greaterThan()` | `GreaterThan` | Numeric comparison |
| `>=` | `greaterThanOrEqualTo()` | `GreaterThanOrEqualTo` | |
| `<` | `lessThan()` | `LessThan` | |
| `<=` | `lessThanOrEqualTo()` | `LessThanOrEqualTo` | |
| `eq` | `equalTo()` | `EqualTo` | Loose equality (==) |
| `ne` | `notEqualTo()` | `NotEqualTo` | Loose inequality (!=) |
| `is` | `sameAs()` | `SameAs` | Strict equality (===) |
| `isNot` | `notSameAs()` | `NotSameAs` | Strict inequality (!==) |
| `in` | `in()` | `In` | Array membership |
| `notIn` | `notIn()` | `NotIn` | Array exclusion |
| `between` | `between()` | `Between` | Range check |

### Logical Operators (6 total)

| DSL Syntax | PHP Method | Operator Class |
|------------|------------|----------------|
| `and` | `logicalAnd()` | `LogicalAnd` |
| `or` | `logicalOr()` | `LogicalOr` |
| `not` | `logicalNot()` | `LogicalNot` |
| `xor` | `logicalXor()` | `LogicalXor` |
| `nand` | `logicalNand()` | `LogicalNand` |
| `nor` | `logicalNor()` | `LogicalNor` |

### Mathematical Operators (13 total)

| DSL Syntax | PHP Method | Operator Class |
|------------|------------|----------------|
| `+` | `add()` | `Addition` |
| `-` | `subtract()` | `Subtraction` |
| `*` | `multiply()` | `Multiplication` |
| `/` | `divide()` | `Division` |
| `%` | `modulo()` | `Modulo` |
| `**` | `exponentiate()` | `Exponentiate` |
| `-x` | `negate()` | `Negation` |
| `abs(x)` | `abs()` | `Abs` |
| `ceil(x)` | `ceil()` | `Ceil` |
| `floor(x)` | `floor()` | `Floor` |
| `round(x)` | `round()` | `Round` |
| `min(x)` | `min()` | `Min` |
| `max(x)` | `max()` | `Max` |

### String Operators (10 total)

| DSL Syntax | PHP Method | Operator Class |
|------------|------------|----------------|
| `contains` | `stringContains()` | `StringContains` |
| `!contains` | `stringDoesNotContain()` | `StringDoesNotContain` |
| `icontains` | `stringContainsInsensitive()` | `StringContainsInsensitive` |
| `!icontains` | `stringDoesNotContainInsensitive()` | `StringDoesNotContainInsensitive` |
| `startsWith` | `startsWith()` | `StartsWith` |
| `istartsWith` | `startsWithInsensitive()` | `StartsWithInsensitive` |
| `endsWith` | `endsWith()` | `EndsWith` |
| `iendsWith` | `endsWithInsensitive()` | `EndsWithInsensitive` |
| `matches` | `matches()` | `Matches` |
| `!matches` | `doesNotMatch()` | `DoesNotMatch` |

### Set Operators (8 total)

| DSL Syntax | PHP Method | Operator Class |
|------------|------------|----------------|
| `union` | `union()` | `Union` |
| `intersect` | `intersect()` | `Intersect` |
| `complement` | `complement()` | `Complement` |
| `symmetricDifference` | `symmetricDifference()` | `SymmetricDifference` |
| `containsSubset` | `containsSubset()` | `ContainsSubset` |
| `!containsSubset` | `doesNotContainSubset()` | `DoesNotContainSubset` |
| `setContains` | `setContains()` | `SetContains` |
| `!setContains` | `setDoesNotContain()` | `SetDoesNotContain` |

### Type Operators (6 total)

| DSL Syntax | PHP Method | Operator Class |
|------------|------------|----------------|
| `isArray` | `isArray()` | `IsArray` |
| `isBoolean` | `isBoolean()` | `IsBoolean` |
| `isEmpty` | `isEmpty()` | `IsEmpty` |
| `isNull` | `isNull()` | `IsNull` |
| `isNumeric` | `isNumeric()` | `IsNumeric` |
| `isString` | `isString()` | `IsString` |

### Date Operators (3 total)

| DSL Syntax | PHP Method | Operator Class |
|------------|------------|----------------|
| `after` | `after()` | `After` |
| `before` | `before()` | `Before` |
| `isBetweenDates` | `isBetweenDates()` | `IsBetweenDates` |

---

## Operator Precedence (High to Low)

1. **Parentheses** - `()`
2. **Unary operators** - `not`, `-` (negation)
3. **Exponentiation** - `**`
4. **Multiplicative** - `*`, `/`, `%`
5. **Additive** - `+`, `-`
6. **Comparison** - `>`, `>=`, `<`, `<=`
7. **Equality** - `eq`, `ne`, `is`, `isNot`
8. **Membership** - `in`, `notIn`, `contains`, etc.
9. **Logical AND** - `and`
10. **Logical XOR** - `xor`
11. **Logical OR** - `or`

---

## Implementation Plan

### Phase 1: Core Infrastructure

**File:** `src/DSL/ExpressionParser.php`
- Configure `symfony/expression-language`
- Register custom operators as ExpressionLanguage functions
- Handle operator precedence and grouping

**File:** `src/DSL/RuleCompiler.php`
- Translate parsed expression AST to Ruler operator tree
- Map DSL operators to Operator classes
- Resolve field namespaces to Variables

**File:** `src/DSL/FieldResolver.php`
- Parse dot-notation field paths (`user.age.years`)
- Create Variable and VariableProperty chains
- Cache resolved fields for performance

### Phase 2: Operator Registration

**File:** `src/DSL/OperatorRegistry.php`
- Map DSL operator names to Operator classes
- Register comparison operators (`eq`, `ne`, `gt`, etc.)
- Register logical operators (`and`, `or`, `not`)
- Register string operators (`contains`, `startsWith`, etc.)
- Register set operators (`in`, `containsSubset`, etc.)
- Register type checkers (`isArray`, `isNull`, etc.)
- Register date operators (`after`, `before`)

### Phase 3: Facade & Integration

**File:** `src/DSL/StringRuleBuilder.php`
```php
class StringRuleBuilder
{
    public function __construct(
        private ExpressionParser $parser,
        private RuleCompiler $compiler,
        private RuleBuilder $ruleBuilder
    ) {}

    public function parse(string $expression): Rule
    {
        $ast = $this->parser->parse($expression);
        $proposition = $this->compiler->compile($ast);
        return $this->ruleBuilder->create($proposition);
    }

    public function parseWithAction(string $expression, callable $action): Rule
    {
        $proposition = $this->compile($expression);
        return $this->ruleBuilder->create($proposition, $action);
    }
}
```

**Usage:**
```php
$srb = new StringRuleBuilder;

// Simple usage
$rule = $srb->parse('user.age >= 18 and user.country in ["US", "CA"]');
$result = $rule->evaluate($context);

// With action
$rule = $srb->parseWithAction(
    'price + shipping > 100',
    fn() => applyFreeShipping()
);
$rule->execute($context);
```

### Phase 4: symfony/expression-language Integration

**Custom Functions to Register:**

```php
// Comparison operators
$lang->register('eq', fn($a, $b) => new EqualTo($a, $b));
$lang->register('ne', fn($a, $b) => new NotEqualTo($a, $b));
$lang->register('gt', fn($a, $b) => new GreaterThan($a, $b));
// ... etc

// String operators
$lang->register('contains', fn($haystack, $needle) =>
    new StringContains($haystack, $needle)
);
$lang->register('startsWith', fn($str, $prefix) =>
    new StartsWith($str, $prefix)
);
// ... etc

// Logical operators (handle via custom parser for infix notation)
$lang->addFunction(new ExpressionFunction(
    'and',
    fn(...$args) => sprintf('(%s)', implode(' && ', $args)),
    fn($args, ...$values) => array_reduce($values, fn($carry, $item) =>
        $carry && $item, true
    )
));
```

**Field Resolution:**

```php
// Override variable resolution to handle dot notation
class RulerExpressionLanguage extends ExpressionLanguage
{
    protected function evaluate($nodes, $functions, $values)
    {
        // Intercept field access like "user.age"
        // Transform to Variable + VariableProperty chain
        // Return evaluated value from context
    }
}
```

---

## Testing Strategy

### Unit Tests

1. **ExpressionParser** - Test parsing all operator types
2. **RuleCompiler** - Test AST → Operator tree conversion
3. **FieldResolver** - Test dot-notation field resolution
4. **OperatorRegistry** - Test all operator mappings

### Integration Tests

Test DSL expressions against equivalent fluent PHP:

```php
test('DSL matches fluent PHP for comparison operators', function() {
    $context = new Context(['age' => 25]);

    // DSL
    $dslRule = $srb->parse('age >= 18');

    // Fluent PHP
    $rb = new RuleBuilder;
    $phpRule = $rb->create($rb['age']->greaterThanOrEqualTo(18));

    expect($dslRule->evaluate($context))
        ->toBe($phpRule->evaluate($context));
});

test('DSL matches fluent PHP for complex nested rules', function() {
    $context = new Context([
        'user' => ['age' => 25, 'country' => 'US'],
    ]);

    // DSL
    $dslRule = $srb->parse(
        '(user.age >= 18 and user.country in ["US", "CA"]) or user.age >= 21'
    );

    // Fluent PHP
    $rb = new RuleBuilder;
    $phpRule = $rb->create(
        $rb->logicalOr(
            $rb->logicalAnd(
                $rb['user']['age']->greaterThanOrEqualTo(18),
                $rb['user']['country']->in(['US', 'CA'])
            ),
            $rb['user']['age']->greaterThanOrEqualTo(21)
        )
    );

    expect($dslRule->evaluate($context))
        ->toBe($phpRule->evaluate($context));
});
```

### Operator Coverage Matrix

Create test for each of the 62 operators mapping DSL → PHP → Result:

| Operator | DSL | PHP | Expected Result |
|----------|-----|-----|-----------------|
| GreaterThan | `age > 18` | `$rb['age']->greaterThan(18)` | `true` |
| In | `country in ["US"]` | `$rb['country']->in(['US'])` | `true` |
| ... | ... | ... | ... |

---

## Backward Compatibility

- **100% backward compatible** - Existing fluent PHP DSL unchanged
- Both APIs coexist and produce identical Operator trees
- Users can choose text-based or fluent PHP based on preference
- Internal architecture remains unchanged

---

## Documentation Requirements

1. **Migration Guide** - How to convert fluent PHP to text DSL
2. **Syntax Reference** - Complete operator documentation
3. **Examples** - Common use cases in both syntaxes
4. **Performance Notes** - Any differences between approaches
5. **Cookbook Updates** - Add DSL examples to existing cookbook

---

## Example: Full Migration

**Before (Fluent PHP):**
```php
$rb = new RuleBuilder;
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['http']['host']->stringContains('admin'),
        $rb->logicalOr(
            $rb['ip']['src']->equalTo('1.2.3.4'),
            $rb['cf']['threat_score']->greaterThan(10)
        )
    )
);
```

**After (Text DSL):**
```php
$srb = new StringRuleBuilder;
$rule = $srb->parse(
    'http.host contains "admin" and (ip.src eq "1.2.3.4" or cf.threat_score > 10)'
);
```

Both produce identical Operator trees and evaluation results.

---

## Success Criteria

✅ All 62 operators have DSL equivalents
✅ DSL syntax matches Cloudflare Wirefilter style
✅ 100% backward compatible with fluent PHP API
✅ Comprehensive test coverage (100% operator mapping)
✅ Documentation includes migration guide
✅ Performance within 5% of fluent PHP approach
✅ Supports nested field access via dot notation
✅ Clear operator precedence rules
✅ Parentheses for explicit grouping
✅ Type-safe operator resolution

---

## Files to Create/Modify

### New Files
- `src/DSL/ExpressionParser.php`
- `src/DSL/RuleCompiler.php`
- `src/DSL/FieldResolver.php`
- `src/DSL/OperatorRegistry.php`
- `src/DSL/StringRuleBuilder.php`
- `tests/DSL/ExpressionParserTest.php`
- `tests/DSL/RuleCompilerTest.php`
- `tests/DSL/FieldResolverTest.php`
- `tests/DSL/OperatorRegistryTest.php`
- `tests/DSL/StringRuleBuilderTest.php`
- `tests/DSL/OperatorMappingTest.php` (matrix tests)
- `cookbook/dsl-syntax.md`
- `cookbook/migration-guide.md`

### Modified Files
- `README.md` - Add DSL syntax section
- `cookbook/getting-started.md` - Add DSL examples
- `cookbook/quick-reference.md` - Add DSL quick reference

---

## Next Steps

1. ✅ Complete architecture analysis
2. Design symfony/expression-language integration strategy
3. Prototype ExpressionParser with 5 basic operators
4. Implement RuleCompiler for basic operators
5. Add FieldResolver for dot notation
6. Register all 62 operators in OperatorRegistry
7. Create StringRuleBuilder facade
8. Write comprehensive test suite
9. Update documentation
10. Performance benchmarking
