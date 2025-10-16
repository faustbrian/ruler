# ADR 001: Wirefilter-Style Text-Based DSL for Rule Creation

**Status:** Accepted
**Date:** 2025-01-14
**Deciders:** Brian Faust
**Tags:** dsl, architecture, api-design

## Context

Ruler provides a powerful fluent PHP API for creating business rules, but it has limitations:

1. **Storage**: Rules built with fluent PHP cannot be easily stored as text in databases or configuration files
2. **Readability**: Complex nested rules become verbose and hard to read with the fluent API
3. **Portability**: Rules cannot be shared across non-PHP systems
4. **UI Integration**: Building rules from form inputs requires complex object construction
5. **Non-developer access**: Business users cannot easily read or modify rules

**Example of current fluent API verbosity:**
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

Cloudflare's Wirefilter demonstrates an effective text-based approach for rule expressions that balances readability with expressiveness.

## Decision

Implement a Wirefilter-style text-based DSL as a **complementary interface** to the existing fluent PHP API using `symfony/expression-language`.

### Architecture

```
StringRuleBuilder (facade)
    ↓
ExpressionParser (symfony/expression-language)
    ↓
RuleCompiler (AST → Operator tree)
    ↓
FieldResolver + OperatorRegistry
    ↓
Existing Ruler Core (unchanged)
```

**Key Design Decisions:**

1. **Namespace:** `Cline\Ruler\DSL\Wirefilter\*` to allow for future DSL variants
2. **100% Backward Compatible:** Existing fluent PHP API remains unchanged
3. **No Mocks in Tests:** All tests use real implementations for reliability
4. **Operator Parity:** All 58 existing operators mapped to DSL equivalents
5. **Immutability:** DSL classes marked `readonly` where applicable

### DSL Syntax

```javascript
// Comparison operators
age >= 18
country in ["US", "CA"]
status == "active"

// Logical operators
age >= 18 and country == "US"
not (status == "banned")
(a and b) or (c and d)

// Mathematical operators
price + shipping > 100
(quantity * price) - discount <= 1000

// Dot notation for nested properties
user.age >= 18
http.request.uri.path startsWith "/api"
```

### Implementation Components

1. **ExpressionParser** - Wraps `symfony/expression-language`, handles variable extraction
2. **FieldResolver** - Converts dot-notation (`user.age`) to Variable/VariableProperty chains
3. **OperatorRegistry** - Maps DSL operators (`gt`, `contains`) to Operator classes
4. **RuleCompiler** - Converts parsed AST to Ruler Operator tree
5. **StringRuleBuilder** - Main facade providing `parse()` and `parseWithAction()` methods

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), Wirefilter DSL has the following limitations compared to other DSLs:

### Unsupported Features

**❌ Date Operations**
- No native date comparison operators (before, after, between dates)
- **Workaround:** Pre-compute date comparisons or use numeric timestamps
- **Why:** Wirefilter syntax focuses on generic filtering, not domain-specific operations

**❌ Advanced Type Checking**
- No built-in type checking operators (isString, isNumeric, isArray)
- **Workaround:** Use explicit comparisons or validate types before rule evaluation
- **Why:** Type checking is context-specific and handled better at the application layer

**❌ Action Callbacks in Other DSLs**
- Action callbacks (`parseWithAction()`) are unique to Wirefilter DSL
- Other DSLs cannot execute imperative code on rule evaluation
- **Why:** Only Wirefilter syntax supports this extension to the core rule model

### Unique Advantages

**✅ Inline Arithmetic**
- **ONLY** DSL supporting mathematical expressions in filters: `price + shipping > 100`
- All other DSLs require pre-computed values in the context
- **Why:** Wirefilter's expression language includes arithmetic operators by design

**✅ Comprehensive Operator Support**
- Full comparison operators including strict equality (`===`, `!==`)
- Complete logical operators (and, or, not) with proper precedence
- All mathematical operators (+, -, *, /, %, **)

**✅ String Operations via Regex**
- Regex matching provides maximum flexibility for string operations
- Can express any string pattern: contains, startsWith, endsWith, custom patterns
- More powerful than dedicated string operators in other DSLs

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison of all DSLs.

## Consequences

### Positive

✅ **Storable rules** - Rules can be stored as strings in databases/config files
✅ **Improved readability** - Text syntax more concise for complex rules
✅ **UI-friendly** - Easy to generate from form inputs
✅ **Portable** - Can be evaluated by non-PHP systems if needed
✅ **Backward compatible** - Zero breaking changes to existing API
✅ **Type-safe** - Full compile-time type checking maintained
✅ **Well-tested** - 100% test coverage without mocks

### Negative

⚠️ **Additional dependency** - Adds `symfony/expression-language` (acceptable: widely-used, well-maintained)
⚠️ **Learning curve** - Developers must learn DSL syntax (mitigated by comprehensive docs)
⚠️ **Runtime parsing** - Text parsing adds minimal overhead vs fluent API (acceptable for most use cases)
⚠️ **Error messages** - Symfony parsing errors less specific than PHP syntax errors (documented with examples)

### Neutral

◯ **Two APIs** - Developers choose based on use case (flexibility vs. consistency)
◯ **Maintenance burden** - Both APIs must be maintained (offset by shared operator core)

## Alternatives Considered

### 1. JSON-based DSL
```json
{
  "operator": "and",
  "operands": [
    {"operator": "gte", "left": "age", "right": 18},
    {"operator": "eq", "left": "country", "right": "US"}
  ]
}
```

**Rejected:** Too verbose, poor readability, requires external tooling to construct

### 2. YAML-based DSL
```yaml
and:
  - gte: [age, 18]
  - eq: [country, "US"]
```

**Rejected:** Indentation sensitivity problematic, not natural for expression syntax

### 3. Custom hand-written parser
**Rejected:** Significant development effort, bug surface area, when `symfony/expression-language` provides proven solution

### 4. Extend existing fluent API only
**Rejected:** Doesn't solve storage/portability requirements

## Implementation Notes

### Operator Precedence
Follows standard mathematical precedence:
1. Parentheses
2. Unary (`not`, `-`)
3. Exponentiation (`**`)
4. Multiplicative (`*`, `/`, `%`)
5. Additive (`+`, `-`)
6. Comparison (`>`, `>=`, `<`, `<=`)
7. Equality (`==`, `!=`, `===`, `!==`)
8. Logical AND
9. Logical OR

### Variable Extraction Strategy
Uses regex to identify variable names, filtering reserved words. This best-effort approach handles 99% of cases while keeping implementation simple.

### Mathematical Operator Handling
Mathematical operators return `VariableOperand`, not `Proposition`. These are wrapped in Variable instances to enable chaining:
```php
price + shipping > 100  // Addition returns VariableOperand, then compared
```

### Logical Operator Special Case
Logical operators accept array of propositions as single constructor argument, unlike binary operators that use variadic arguments.

## Migration Path

No migration required - new DSL is additive:

**Before (continues to work):**
```php
$rb = new RuleBuilder;
$rule = $rb->create($rb['age']->greaterThanOrEqualTo(18));
```

**After (new option):**
```php
$srb = new StringRuleBuilder;
$rule = $srb->parse('age >= 18');
```

Both produce identical Operator trees and evaluation results.

## Verification

- ✅ All 58 operators mapped
- ✅ 27 DSL-specific tests (zero mocks)
- ✅ 544 total tests passing
- ✅ Documentation: `cookbook/dsl-syntax.md`
- ✅ Namespace: `Cline\Ruler\DSL\Wirefilter\*`
- ✅ 100% backward compatibility verified

## References

- [Cloudflare Wirefilter](https://github.com/cloudflare/wirefilter)
- [symfony/expression-language Documentation](https://symfony.com/doc/current/components/expression_language.html)
- Ruler `cookbook/dsl-syntax.md` - Full DSL syntax reference
- `DSL_DESIGN.md` - Detailed technical design document

## Future Considerations

- Additional DSL variants (e.g., `DSL\SQL`, `DSL\GraphQL`) can be added under `DSL\` namespace
- Performance optimization if parsing becomes bottleneck (precompile and cache)
- IDE plugin for DSL syntax highlighting and autocomplete
- Visual rule builder UI that generates DSL text
