# DSL Feature Support Matrix

This document provides a comprehensive comparison of features supported across all five DSL implementations: Wirefilter, JMESPath, Natural Language, MongoDB Query, and GraphQL Filter.

## Quick Reference

| Feature | Wirefilter | JMESPath | Natural Language | MongoDB Query | GraphQL Filter |
|---------|------------|----------|------------------|---------------|----------------|
| **Comparison Operators** | ✅ Full | ✅ Full | ✅ Full | ✅ Full + Strict | ✅ Full |
| **Logical Operators** | ✅ Full | ✅ Full | ✅ Full | ✅ Full + Extended | ✅ Full |
| **Inline Arithmetic** | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No |
| **String Operations** | ✅ Regex | ⚠️ Limited | ⚠️ Limited | ✅ Full | ✅ Good |
| **Date Operations** | ❌ No | ❌ No | ❌ No | ✅ Yes | ❌ No |
| **Type Checking** | ❌ No | ⚠️ Limited | ❌ No | ✅ Full | ✅ Limited |
| **List Membership** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **Nested Properties** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **Action Callbacks** | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No |
| **Strict Equality** | ✅ Yes | ⚠️ Workaround | ❌ No | ✅ Yes | ❌ No |

---

## Comparison Operators

### Wirefilter DSL

| Operator | Syntax | Example | Status |
|----------|--------|---------|--------|
| Equal | `==` | `age == 18` | ✅ |
| Not Equal | `!=` | `status != "banned"` | ✅ |
| Greater Than | `>` | `score > 100` | ✅ |
| Greater or Equal | `>=` | `age >= 18` | ✅ |
| Less Than | `<` | `price < 50` | ✅ |
| Less or Equal | `<=` | `age <= 65` | ✅ |
| Strict Equal | `===` | `value === 42` | ✅ |
| Strict Not Equal | `!==` | `value !== "42"` | ✅ |

### JMESPath DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| Equal | `==` | `` age == `18` `` | ✅ | Use backticks for literals |
| Not Equal | `!=` | `` status != `null` `` | ✅ | |
| Greater Than | `>` | `` score > `100` `` | ✅ | |
| Greater or Equal | `>=` | `` age >= `18` `` | ✅ | |
| Less Than | `<` | `` price < `50` `` | ✅ | |
| Less or Equal | `<=` | `` age <= `65` `` | ✅ | |
| Strict Equal | `===` | N/A | ❌ | **Not in JMESPath spec** |
| Strict Not Equal | `!==` | N/A | ❌ | **Not in JMESPath spec** |

**Workaround for Strict Equality:**
```jmespath
value == `42` && type(value) == 'number'
```

### Natural Language DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| Equal | `is` | `age is 18` | ✅ | |
| Not Equal | `is not` | `status is not "banned"` | ✅ | |
| Greater Than | `is more than` | `score is more than 100` | ✅ | Also: `is greater than` |
| Greater or Equal | `is at least` | `age is at least 18` | ✅ | Also: `is greater than or equal to`, `is X or more` |
| Less Than | `is less than` | `price is less than 50` | ✅ | |
| Less or Equal | `is at most` | `age is at most 65` | ✅ | Also: `is less than or equal to`, `is X or less` |
| Strict Equal | N/A | N/A | ❌ | **By design: natural language doesn't distinguish strict equality** |
| Strict Not Equal | N/A | N/A | ❌ | **By design: natural language doesn't distinguish strict equality** |

**Why No Strict Equality?**
Natural language is designed for business users who don't think in terms of type coercion. Use Wirefilter or MongoDB Query DSL if strict type checking is required.

### MongoDB Query DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| Equal | `$eq` / implicit | `{"age": 18}` or `{"age": {"$eq": 18}}` | ✅ | Implicit equality when value is not an object |
| Not Equal | `$ne` | `{"status": {"$ne": "banned"}}` | ✅ | |
| Greater Than | `$gt` | `{"score": {"$gt": 100}}` | ✅ | |
| Greater or Equal | `$gte` | `{"age": {"$gte": 18}}` | ✅ | |
| Less Than | `$lt` | `{"price": {"$lt": 50}}` | ✅ | |
| Less or Equal | `$lte` | `{"age": {"$lte": 65}}` | ✅ | |
| Strict Equal | `$same` | `{"value": {"$same": 42}}` | ✅ | **Custom operator: strict ===** |
| Strict Not Equal | `$nsame` | `{"value": {"$nsame": "42"}}` | ✅ | **Custom operator: strict !==** |
| Between | `$between` | `{"age": {"$between": [18, 65]}}` | ✅ | **Custom operator: range check** |

**Why MongoDB Syntax?**
MongoDB Query is a widely-adopted JSON-based query language used in REST APIs, NoSQL databases, and microservices. JSON structure is already parsed and validated.

### GraphQL Filter DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| Equal | `eq` / implicit | `{"age": 18}` or `{"age": {"eq": 18}}` | ✅ | Implicit equality when value is not an object |
| Not Equal | `ne` | `{"status": {"ne": "banned"}}` | ✅ | |
| Greater Than | `gt` | `{"score": {"gt": 100}}` | ✅ | |
| Greater or Equal | `gte` | `{"age": {"gte": 18}}` | ✅ | |
| Less Than | `lt` | `{"price": {"lt": 50}}` | ✅ | |
| Less or Equal | `lte` | `{"age": {"lte": 65}}` | ✅ | |
| Strict Equal | N/A | N/A | ❌ | **Not in GraphQL Filter spec** |
| Strict Not Equal | N/A | N/A | ❌ | **Not in GraphQL Filter spec** |

**Why GraphQL Filter Syntax?**
GraphQL Filter is a JSON-based query language popularized by Hasura, Prisma, and Postgraphile. Designed for frontend developers building type-safe GraphQL APIs with IDE autocomplete support.

---

## Logical Operators

### Wirefilter DSL

| Operator | Syntax | Example | Status |
|----------|--------|---------|--------|
| AND | `and` | `age >= 18 and country == "US"` | ✅ |
| OR | `or` | `vip == true or total > 1000` | ✅ |
| NOT | `not` | `not (age < 18)` | ✅ |
| Parentheses | `()` | `(a and b) or c` | ✅ |

### JMESPath DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| AND | `&&` | `` age >= `18` && country == 'US' `` | ✅ | **Must use && not 'and'** |
| OR | `\|\|` | `` vip == `true` \|\| total > `1000` `` | ✅ | **Must use \|\| not 'or'** |
| NOT | `!` | `` !(age < `18`) `` | ✅ | **Must use ! not 'not'** |
| Parentheses | `()` | `(a && b) \|\| c` | ✅ | |

**Why Different Syntax?**
JMESPath is a JSON query language standard used by AWS and other systems. The syntax follows JavaScript conventions (&&, ||, !) rather than SQL-style (and, or, not).

### Natural Language DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| AND | `and` | `age is at least 18 and country is "US"` | ✅ | |
| OR | `or` | `vip is true or total is more than 1000` | ✅ | |
| NOT | Negated phrases | `age is not less than 18` | ✅ | More natural than `not (...)` |
| Parentheses | `()` | `(a and b) or c` | ✅ | |

**Unique Feature: Negated Comparisons**
```
age is not less than 18          → age >= 18
age is not greater than 65       → age <= 65
status is not one of "a", "b"    → status not in ["a", "b"]
```

### MongoDB Query DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| AND | `$and` / implicit | `{"$and": [...]}` or `{"age": {...}, "country": {...}}` | ✅ | Implicit AND for multiple root fields |
| OR | `$or` | `{"$or": [{"age": {"$gte": 21}}, {"country": "US"}]}` | ✅ | |
| NOT | `$not` | `{"$not": {"age": {"$lt": 18}}}` | ✅ | |
| NOR | `$nor` | `{"$nor": [{"age": {"$lt": 18}}, {"status": "banned"}]}` | ✅ | **None of the conditions can be true** |
| XOR | `$xor` | `{"$xor": [{"age": {"$gte": 18}}, {"country": "US"}]}` | ✅ | **Custom operator: exactly one true** |
| NAND | `$nand` | `{"$nand": [{"age": {"$gte": 18}}, {"country": "US"}]}` | ✅ | **Custom operator: not all true** |

**Extended Logical Operators:**
MongoDB Query DSL includes XOR and NAND for advanced logic patterns not available in standard MongoDB.

### GraphQL Filter DSL

| Operator | Syntax | Example | Status | Notes |
|----------|--------|---------|--------|-------|
| AND | `AND` / implicit | `{"AND": [...]}` or `{"age": {...}, "country": {...}}` | ✅ | Uppercase convention, implicit AND for multiple root fields |
| OR | `OR` | `{"OR": [{"age": {"gte": 21}}, {"country": "US"}]}` | ✅ | Uppercase convention |
| NOT | `NOT` | `{"NOT": {"age": {"lt": 18}}}` | ✅ | Uppercase convention |

**Why Uppercase Convention?**
GraphQL Filter DSL uses uppercase logical operators (AND, OR, NOT) following GraphQL schema naming conventions, distinguishing them from field names which are typically camelCase.

---

## Arithmetic Operators

### Wirefilter DSL

| Operator | Syntax | Example | Status |
|----------|--------|---------|--------|
| Addition | `+` | `price + shipping > 100` | ✅ |
| Subtraction | `-` | `total - discount >= 50` | ✅ |
| Multiplication | `*` | `quantity * price > 1000` | ✅ |
| Division | `/` | `total / count == 10` | ✅ |
| Modulo | `%` | `value % 2 == 0` | ✅ |
| Exponentiation | `**` | `base ** power > 100` | ✅ |
| Unary Minus | `-` | `-value > -10` | ✅ |

### JMESPath DSL

| Operator | Syntax | Example | Status | Reason |
|----------|--------|---------|--------|--------|
| All Arithmetic | N/A | N/A | ❌ | **Not in JMESPath spec** |
| Unary Minus | `-` | `` -value > `-10` `` | ⚠️ | **Limited: only for literal values** |

**Why No Arithmetic?**
JMESPath is a JSON query language, not a computation engine. It's designed for extracting and filtering data, not performing calculations.

### Natural Language DSL

| Operator | Syntax | Example | Status | Reason |
|----------|--------|---------|--------|--------|
| All Arithmetic | N/A | N/A | ❌ | **By design: pre-compute values** |
| Unary Minus | `-` | `negativeValue is more than -10` | ✅ | **Supports negative number literals** |

**Why No Arithmetic?**
Natural Language DSL is designed for business users writing declarative rules, not performing calculations. Complex math obscures business logic intent.

### MongoDB Query DSL

| Operator | Syntax | Example | Status | Reason |
|----------|--------|---------|--------|--------|
| All Arithmetic | N/A | N/A | ❌ | **By design: pre-compute values** |
| Unary Minus | `-` | `{"negativeValue": {"$gt": -10}}` | ✅ | **Supports negative number literals** |

**Why No Arithmetic?**
MongoDB Query DSL is designed for filtering documents, not computation. Use aggregation pipelines or pre-compute values if calculations are needed.

### GraphQL Filter DSL

| Operator | Syntax | Example | Status | Reason |
|----------|--------|---------|--------|--------|
| All Arithmetic | N/A | N/A | ❌ | **By design: pre-compute values** |
| Unary Minus | `-` | `{"negativeValue": {"gt": -10}}` | ✅ | **Supports negative number literals** |

**Why No Arithmetic?**
GraphQL Filter DSL is designed for filtering query results, not computation. Calculations should be performed in resolvers or pre-computed fields.

**Workaround for All Non-Wirefilter DSLs:**
```php
$context = new Context([
    'total' => $price + $shipping  // Compute outside DSL
]);
```

---

## String Operations

### Wirefilter DSL

| Operation | Syntax | Example | Status |
|-----------|--------|---------|--------|
| Regex Match | `matches` | `email matches "/^\\d{3}-\\d{4}$/"` | ✅ |
| Contains | `contains` (via regex) | `name matches "/john/i"` | ✅ |
| Starts With | (via regex) | `url matches "/^https/"` | ✅ |
| Ends With | (via regex) | `filename matches "/\.pdf$/"` | ✅ |

### JMESPath DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Regex Match | N/A | N/A | ❌ | **Not in JMESPath spec** |
| Contains | `contains()` | `contains(email, '@example.com')` | ✅ | **Substring only, not regex** |
| Starts With | `starts_with()` | `starts_with(url, 'https')` | ✅ | **Literal prefix, not regex** |
| Ends With | `ends_with()` | `ends_with(filename, '.pdf')` | ✅ | **Literal suffix, not regex** |

**Why No Regex?**
JMESPath is designed for JSON querying across multiple platforms. Regex support would require consistent implementations across JavaScript, Python, Go, etc.

### Natural Language DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Regex Match | N/A | N/A | ❌ | **By design: natural language avoids regex** |
| Contains | `contains` | `email contains "@example.com"` | ✅ | **Case-sensitive substring** |
| Starts With | `starts with` | `name starts with "John"` | ✅ | **Case-sensitive prefix** |
| Ends With | `ends with` | `file ends with ".pdf"` | ✅ | **Case-sensitive suffix** |

**Why No Regex?**
Natural language targets business users who shouldn't need to understand regex patterns. Use Wirefilter or MongoDB Query DSL for regex support.

### MongoDB Query DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Regex Match | `$regex` | `{"email": {"$regex": "^\\d{3}-\\d{4}$", "$options": "i"}}` | ✅ | **Full regex with options (i, m, s)** |
| Not Regex | `$notRegex` | `{"username": {"$notRegex": "[^a-zA-Z0-9]"}}` | ✅ | **Custom operator: inverse regex** |
| Contains | `$contains` | `{"description": {"$contains": "important"}}` | ✅ | **Custom operator: case-sensitive** |
| Contains (insensitive) | `$containsi` | `{"description": {"$containsi": "IMPORTANT"}}` | ✅ | **Custom operator: case-insensitive** |
| Not Contains | `$notContains` | `{"description": {"$notContains": "spam"}}` | ✅ | **Custom operator** |
| Not Contains (insensitive) | `$notContainsi` | `{"description": {"$notContainsi": "SPAM"}}` | ✅ | **Custom operator** |
| Starts With | `$startsWith` | `{"name": {"$startsWith": "John"}}` | ✅ | **Custom operator: case-sensitive** |
| Starts With (insensitive) | `$startsWithi` | `{"name": {"$startsWithi": "john"}}` | ✅ | **Custom operator** |
| Ends With | `$endsWith` | `{"email": {"$endsWith": "@example.com"}}` | ✅ | **Custom operator: case-sensitive** |
| Ends With (insensitive) | `$endsWithi` | `{"email": {"$endsWithi": "@EXAMPLE.COM"}}` | ✅ | **Custom operator** |
| String Length | `$strLength` | `{"password": {"$strLength": {"$gte": 8}}}` | ✅ | **Custom operator: supports comparisons** |

**Most Comprehensive String Support:**
MongoDB Query DSL offers the most extensive string operations of all DSLs, with both case-sensitive and case-insensitive variants plus regex support.

### GraphQL Filter DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Regex Match | `match` | `{"phone": {"match": "^\\\\d{3}-\\\\d{4}$"}}` | ✅ | **Regex support with standard pattern** |
| Contains | `contains` | `{"email": {"contains": "@example.com"}}` | ✅ | **Case-sensitive substring** |
| Contains (insensitive) | `containsInsensitive` | `{"email": {"containsInsensitive": "@EXAMPLE.COM"}}` | ✅ | **Case-insensitive substring** |
| Not Contains | `notContains` | `{"description": {"notContains": "spam"}}` | ✅ | **Case-sensitive inverse** |
| Starts With | `startsWith` | `{"name": {"startsWith": "John"}}` | ✅ | **Case-sensitive prefix** |
| Ends With | `endsWith` | `{"filename": {"endsWith": ".pdf"}}` | ✅ | **Case-sensitive suffix** |

**Good String Support:**
GraphQL Filter DSL provides comprehensive string operations including regex matching, contains variants, and prefix/suffix matching. Follows camelCase naming convention typical of GraphQL schemas.

---

## Date Operations

### Wirefilter DSL

Date operations not supported natively. Use string/numeric comparisons or pre-compute.

### JMESPath DSL

Date operations not supported natively. Use string/numeric comparisons or pre-compute.

### Natural Language DSL

Date operations not supported natively. Use string/numeric comparisons or pre-compute.

### MongoDB Query DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| After | `$after` | `{"createdAt": {"$after": "2024-01-01"}}` | ✅ | **Custom operator** |
| Before | `$before` | `{"expiresAt": {"$before": "2024-12-31"}}` | ✅ | **Custom operator** |
| Between Dates | `$betweenDates` | `{"eventDate": {"$betweenDates": ["2024-01-01", "2024-12-31"]}}` | ✅ | **Custom operator** |

**Only DSL with Date Operations:**
MongoDB Query DSL is the only DSL with explicit date operation support.

### GraphQL Filter DSL

Date operations not supported natively. Use string/numeric comparisons or pre-compute.

---

## Type Checking

### Wirefilter DSL

Type checking not supported. Use explicit comparisons or workarounds.

### JMESPath DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Type Check | `type()` | `` type(value) == 'number' `` | ✅ | Returns: number, string, boolean, array, object, null |

### Natural Language DSL

Type checking not supported. Use explicit comparisons or MongoDB Query DSL.

### MongoDB Query DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Type Check | `$type` | `{"value": {"$type": "number"}}` | ✅ | **Supported types: null, array, bool, number, string** |
| Is Empty | `$empty` | `{"data": {"$empty": true}}` | ✅ | **Checks for null, empty array, empty string** |
| Array Size | `$size` | `{"tags": {"$size": {"$gte": 5}}}` | ✅ | **Supports exact count or comparisons** |

**Most Comprehensive Type Support:**
MongoDB Query DSL offers the most extensive type checking capabilities.

### GraphQL Filter DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| Type Check | `isType` | `{"count": {"isType": "number"}}` | ✅ | **Supported types: string, number, boolean, array, null** |

**Limited Type Support:**
GraphQL Filter DSL provides basic type checking with the `isType` operator, supporting common JavaScript types but without advanced features like empty checks or size comparisons.

---

## List Membership

### Wirefilter DSL

| Operation | Syntax | Example | Status |
|-----------|--------|---------|--------|
| In | `in` | `country in ["US", "CA", "UK"]` | ✅ |
| Not In | `not in` | `role not in ["banned", "suspended"]` | ✅ |

### JMESPath DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| In | `contains()` | `` contains(`["US", "CA"]`, country) `` | ✅ | **Note reversed argument order** |
| Not In | `!contains()` | `` !contains(`["banned"]`, role) `` | ✅ | |

### Natural Language DSL

| Operation | Syntax | Example | Status | Notes |
|-----------|--------|---------|--------|-------|
| In | `is one of` | `country is one of "US", "CA", "UK"` | ✅ | Also: `is either X or Y` |
| Not In | `is not one of` | `role is not one of "banned", "suspended"` | ✅ | |

### MongoDB Query DSL

| Operation | Syntax | Example | Status |
|-----------|--------|---------|--------|
| In | `$in` | `{"country": {"$in": ["US", "CA", "UK"]}}` | ✅ |
| Not In | `$nin` | `{"role": {"$nin": ["banned", "suspended"]}}` | ✅ |

### GraphQL Filter DSL

| Operation | Syntax | Example | Status |
|-----------|--------|---------|--------|
| In | `in` | `{"country": {"in": ["US", "CA", "UK"]}}` | ✅ |
| Not In | `notIn` | `{"role": {"notIn": ["banned", "suspended"]}}` | ✅ |

---

## Nested Property Access

All DSLs support dot notation for nested property access:

**Wirefilter:** `user.profile.email == "test@example.com"`
**JMESPath:** `user.profile.email == 'test@example.com'`
**Natural Language:** `user.profile.email is "test@example.com"`
**MongoDB Query:** `{"user.profile.email": "test@example.com"}`
**GraphQL Filter:** `{"user": {"profile": {"email": "test@example.com"}}}`

**Note on GraphQL Filter:** Nested objects are represented using nested JSON structure rather than dot notation strings. The nested structure flattens to dot notation internally (e.g., `user.profile.email`).

---

## Special Features

### Wirefilter DSL

**Action Callbacks:**
```php
$rule = $srb->parseWithAction('age >= 18', function() {
    // Execute when rule evaluates to true
});
```

Only Wirefilter DSL supports imperative execution patterns.

### JMESPath DSL

**AWS Standard:**
Uses official AWS JMESPath spec, ensuring compatibility with cloud services and multi-language implementations.

### Natural Language DSL

**Business-Friendly Syntax:**
Designed for non-technical stakeholders to write and understand rules without learning query syntax.

### MongoDB Query DSL

**Most Feature-Complete:**
- ✅ Strict equality operators (`$same`, `$nsame`)
- ✅ Extended logical operators (`$xor`, `$nand`)
- ✅ Comprehensive string operations (8 custom operators)
- ✅ Date operations (3 custom operators)
- ✅ Type checking (3 custom operators)
- ✅ Range operations (`$between`, `$betweenDates`)
- ✅ JSON-based (already parsed and validated)
- ✅ REST API friendly
- ✅ Follows MongoDB naming conventions

**When to Use MongoDB Query DSL:**
- Building REST APIs that accept query filters
- Need comprehensive type checking and date operations
- Want case-insensitive string operations
- Require strict type equality
- Working with NoSQL databases or microservices

### GraphQL Filter DSL

**Frontend-Friendly JSON Syntax:**
- ✅ JSON-based query language (already parsed and validated)
- ✅ Type-safe (designed for schema-driven GraphQL APIs)
- ✅ IDE autocomplete friendly (works with GraphQL schemas)
- ✅ Implicit equality and AND operators
- ✅ Popular in Hasura, Prisma, Postgraphile
- ✅ Frontend developer optimized (React/Vue/Angular)
- ✅ camelCase naming convention (GraphQL standard)
- ✅ Good string operations (contains, startsWith, endsWith, match)
- ✅ Basic type checking with `isType` operator
- ❌ No date operations (use comparison operators)
- ❌ No strict equality (type coercion applies)

**When to Use GraphQL Filter DSL:**
- Building GraphQL APIs with filtering capabilities
- Frontend developers need to construct filter queries
- React/Vue/Angular applications querying GraphQL
- Type-safe API contracts with schema validation
- Schema-driven development workflows
- IDE autocomplete support is important

---

## Test Coverage Summary

| DSL | Total Tests | Baseline Coverage | Extended Features |
|-----|-------------|-------------------|-------------------|
| **Wirefilter** | 32 | 100% (baseline) | Action callbacks, arithmetic |
| **JMESPath** | 61 | 100% (baseline parity) | JSON functions, type checking |
| **Natural Language** | 27 | 100% (baseline parity) | Negated comparisons, conversational syntax |
| **MongoDB Query** | 61 | 100% (baseline parity) | 28 custom operators, most feature-complete |
| **GraphQL Filter** | 33 | 100% (baseline parity) | Type checking, comprehensive string operations |

---

## Decision Guide

**Choose Wirefilter if:**
- Need inline arithmetic operations
- Require action callback support
- Technical users writing complex rules
- SQL/programming-style syntax is familiar

**Choose JMESPath if:**
- Querying JSON data structures
- Need AWS compatibility
- Multi-language support required
- Already using JMESPath elsewhere

**Choose Natural Language if:**
- Business users writing rules
- Readability is paramount
- Rules serve as documentation
- Non-technical stakeholders involved

**Choose MongoDB Query if:**
- Building REST APIs
- Need comprehensive string operations
- Require date/type checking
- Want strict type equality
- Working with NoSQL databases
- Most feature-complete DSL needed

**Choose GraphQL Filter if:**
- Building GraphQL APIs
- Frontend developers need filter UI
- React/Vue/Angular applications
- Type-safe API contracts
- Schema-driven development
- IDE autocomplete support needed
