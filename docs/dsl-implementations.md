---
title: DSL Implementations
description: Overview of Ruler's five DSL implementations and when to use each one.
---

Ruler provides five distinct DSL implementations for different use cases. Each produces the same Ruler operators but with syntax optimized for specific contexts.

## Quick Comparison

| Feature | Wirefilter | JMESPath | Natural Language | MongoDB Query | GraphQL Filter |
|---------|------------|----------|------------------|---------------|----------------|
| **Comparison** | ✅ Full | ✅ Full | ✅ Full | ✅ Full + Strict | ✅ Full |
| **Logical Ops** | ✅ Full | ✅ Full | ✅ Full | ✅ Full + XOR/NAND | ✅ Full |
| **Arithmetic** | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No |
| **String Ops** | ✅ Regex | ⚠️ Limited | ⚠️ Limited | ✅ Full | ✅ Good |
| **Action Callbacks** | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No |
| **Strict Equality** | ✅ Yes | ⚠️ Workaround | ❌ No | ✅ Yes | ❌ No |

## Wirefilter DSL

SQL-like text syntax. Best for developers and stored rules.

```php
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;

$srb = new StringRuleBuilder;
$rule = $srb->parse('age >= 18 and country == "US"');
$rule = $srb->parse('price + shipping > 100');  // Arithmetic supported
```

**Best for:** Developer-facing rules, config files, arithmetic expressions

## JMESPath DSL

AWS-style JSON path expressions. Follows JMESPath standard.

```php
use Cline\Ruler\DSL\JMESPath\JMESPathRuleBuilder;

$builder = new JMESPathRuleBuilder;
$rule = $builder->parse('age >= `18` && country == \'US\'');
```

**Syntax notes:**
- Use backticks for numbers: `` `18` ``
- Use `&&`, `||`, `!` (not `and`, `or`, `not`)
- Supports `contains()`, `starts_with()`, `ends_with()`

**Best for:** AWS integrations, JSON document filtering

## Natural Language DSL

Human-readable expressions for business users.

```php
use Cline\Ruler\DSL\NaturalLanguage\NaturalLanguageRuleBuilder;

$builder = new NaturalLanguageRuleBuilder;
$rule = $builder->parse('age is at least 18 and country is "US"');
$rule = $builder->parse('status is not "banned"');
```

**Operators:**
- `is`, `is not` — equality
- `is more than`, `is greater than` — greater than
- `is at least`, `is X or more` — greater than or equal
- `is less than` — less than
- `is at most`, `is X or less` — less than or equal

**Best for:** Non-technical stakeholders, business rule management

## MongoDB Query DSL

JSON-based query syntax compatible with MongoDB.

```php
use Cline\Ruler\DSL\MongoDB\MongoDBRuleBuilder;

$builder = new MongoDBRuleBuilder;
$rule = $builder->parse('{"age": {"$gte": 18}, "country": "US"}');
$rule = $builder->parse('{"$or": [{"age": {"$gte": 21}}, {"vip": true}]}');
```

**Extended operators:**
- `$same`, `$nsame` — strict equality (`===`, `!==`)
- `$between` — range check
- `$xor`, `$nand`, `$nor` — advanced logical
- `$regex`, `$contains`, `$startsWith`, `$endsWith` — string operations
- Case-insensitive variants: `$containsi`, `$startsWithi`, `$endsWithi`

**Best for:** REST APIs, NoSQL databases, microservices

## GraphQL Filter DSL

JSON syntax following GraphQL filter conventions.

```php
use Cline\Ruler\DSL\GraphQL\GraphQLFilterRuleBuilder;

$builder = new GraphQLFilterRuleBuilder;
$rule = $builder->parse('{"age": {"gte": 18}, "country": "US"}');
$rule = $builder->parse('{"OR": [{"age": {"gte": 21}}, {"vip": true}]}');
```

**Syntax notes:**
- No `$` prefix on operators
- Uppercase `AND`, `OR`, `NOT`
- Supports `contains`, `startsWith`, `endsWith`, `match` (regex)

**Best for:** GraphQL APIs, Hasura/Prisma integrations, frontend developers

## Choosing a DSL

| Use Case | Recommended DSL |
|----------|-----------------|
| Config files with calculations | Wirefilter |
| AWS/JSON document filtering | JMESPath |
| Business stakeholder rules | Natural Language |
| REST API query parameters | MongoDB Query |
| GraphQL API filters | GraphQL Filter |
| Rules with action callbacks | Wirefilter |
| Maximum string operations | MongoDB Query |
| Type-strict comparisons | Wirefilter or MongoDB |

## Workaround for Non-Arithmetic DSLs

Pre-compute values in context:

```php
$context = new Context([
    'total' => $price + $shipping,  // Compute outside DSL
]);

// Then use the pre-computed value
$builder->parse('{"total": {"$gt": 100}}');
```
