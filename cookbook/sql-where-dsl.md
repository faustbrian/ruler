# SQL WHERE DSL Cookbook

**Status:** Proposed/Alternative DSL
**Complexity:** Low
**Best For:** SQL-familiar teams, database-centric applications, developers seeking familiar syntax

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Logical Operators](#logical-operators)
4. [String Pattern Matching](#string-pattern-matching)
5. [Range Queries](#range-queries)
6. [List Membership](#list-membership)
7. [NULL Handling](#null-handling)
8. [Nested Properties](#nested-properties)
9. [Advanced Patterns](#advanced-patterns)
10. [Performance Optimization](#performance-optimization)
11. [Common Pitfalls](#common-pitfalls)
12. [Real-World Examples](#real-world-examples)

---

## Quick Start

### Installation

```bash
composer require cline/ruler
```

### Basic Usage

```php
use Cline\Ruler\DSL\SqlWhere\SqlWhereRuleBuilder;
use Cline\Ruler\Core\Context;

$srb = new SqlWhereRuleBuilder();

// Parse a SQL WHERE clause (without the 'WHERE' keyword)
$rule = $srb->parse("age >= 18 AND country = 'US'");

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

### Why SQL WHERE DSL?

SQL WHERE syntax is **the most familiar filtering syntax** to developers worldwide:

- **90%+ developers** know SQL - zero learning curve
- **Battle-tested** - 40+ years of refinement in WHERE clauses
- **Instantly readable** - `age >= 18 AND country = 'US'` needs no explanation
- **Database-aligned** - rules map naturally to database queries
- **Rich operators** - BETWEEN, IN, LIKE, IS NULL all native

If your team has database experience, this DSL will feel like home.

---

## Basic Comparisons

### Equality Operators

```php
// Equal to (=)
$rule = $srb->parse("status = 'active'");
$rule->evaluate(new Context(['status' => 'active'])); // true

// Not equal to (!=)
$rule = $srb->parse("status != 'banned'");
$rule->evaluate(new Context(['status' => 'active'])); // true

// Alternative not equal (<>)
$rule = $srb->parse("status <> 'banned'");
$rule->evaluate(new Context(['status' => 'active'])); // true
```

**Note on Type Coercion:**
SQL WHERE DSL uses standard SQL equality semantics. Unlike Wirefilter's strict equality (`===`), SQL's `=` follows SQL type coercion rules. For strict type checking, use Wirefilter DSL instead.

```php
// SQL equality performs type coercion similar to database behavior
$rule = $srb->parse("age = 18");
$rule->evaluate(new Context(['age' => '18'])); // true (string coerced to int)

// For strict type checking, use Wirefilter DSL with ===
$rb = new StringRuleBuilder();
$rule = $rb->parse("age === 18");
$rule->evaluate(new Context(['age' => '18'])); // false (strict type check)
```

### Numeric Comparisons

```php
// Greater than (>)
$rule = $srb->parse("price > 100");
$rule->evaluate(new Context(['price' => 150])); // true
$rule->evaluate(new Context(['price' => 100])); // false

// Greater than or equal (>=)
$rule = $srb->parse("age >= 18");
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 25])); // true
$rule->evaluate(new Context(['age' => 17])); // false

// Less than (<)
$rule = $srb->parse("quantity < 10");
$rule->evaluate(new Context(['quantity' => 5])); // true
$rule->evaluate(new Context(['quantity' => 10])); // false

// Less than or equal (<=)
$rule = $srb->parse("temperature <= 32");
$rule->evaluate(new Context(['temperature' => 20])); // true
$rule->evaluate(new Context(['temperature' => 32])); // true
$rule->evaluate(new Context(['temperature' => 35])); // false
```

### Decimal and Float Comparisons

```php
// Decimal precision
$rule = $srb->parse("price >= 99.99");
$rule->evaluate(new Context(['price' => 100.00])); // true
$rule->evaluate(new Context(['price' => 99.98])); // false

// Scientific notation
$rule = $srb->parse("distance > 1e6");
$rule->evaluate(new Context(['distance' => 1500000])); // true

// Negative numbers
$rule = $srb->parse("balance < -100.50");
$rule->evaluate(new Context(['balance' => -150.75])); // true
```

### String Comparisons

```php
// String equality (case-sensitive by default)
$rule = $srb->parse("name = 'John'");
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'john'])); // false
$rule->evaluate(new Context(['name' => 'JOHN'])); // false

// Lexicographic comparison
$rule = $srb->parse("name > 'Alice'");
$rule->evaluate(new Context(['name' => 'Bob'])); // true (B > A)
$rule->evaluate(new Context(['name' => 'Alice'])); // false

// String concatenation in expressions
$rule = $srb->parse("first_name = 'John'");
$rule->evaluate(new Context(['first_name' => 'John'])); // true
```

---

## Logical Operators

### AND Operator

```php
// Multiple conditions must ALL be true
$rule = $srb->parse("age >= 18 AND country = 'US' AND verified = true");

$valid = new Context([
    'age' => 25,
    'country' => 'US',
    'verified' => true
]);
$rule->evaluate($valid); // true

$invalid = new Context([
    'age' => 25,
    'country' => 'FR', // fails here
    'verified' => true
]);
$rule->evaluate($invalid); // false
```

**Short-Circuit Evaluation:**
AND stops evaluating as soon as one condition is false.

```php
// If status != 'active', price check never happens (performance optimization)
$rule = $srb->parse("status = 'active' AND price > 1000");
```

### OR Operator

```php
// At least ONE condition must be true
$rule = $srb->parse("status = 'active' OR status = 'pending'");

$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Multiple OR conditions
$rule = $srb->parse("role = 'admin' OR role = 'moderator' OR role = 'super_admin'");

// Better approach: use IN operator (see List Membership section)
$rule = $srb->parse("role IN ('admin', 'moderator', 'super_admin')");
```

### NOT Operator

```php
// Negate a single condition
$rule = $srb->parse("NOT status = 'banned'");
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Alternative syntax with !=
$rule = $srb->parse("status != 'banned'");

// Negate compound expression
$rule = $srb->parse("NOT (age < 18 OR country = 'FR')");
$rule->evaluate(new Context(['age' => 25, 'country' => 'US'])); // true
$rule->evaluate(new Context(['age' => 15, 'country' => 'US'])); // false

// Negate IN operator
$rule = $srb->parse("NOT status IN ('banned', 'suspended')");
// Alternative: use NOT IN (see List Membership)
$rule = $srb->parse("status NOT IN ('banned', 'suspended')");
```

### Operator Precedence

**SQL Standard Precedence:** NOT > AND > OR

```php
// This expression: a OR b AND c
// Evaluates as: a OR (b AND c)
// NOT as: (a OR b) AND c
$rule = $srb->parse("status = 'active' OR age >= 18 AND verified = true");
// Equivalent to:
$rule = $srb->parse("status = 'active' OR (age >= 18 AND verified = true)");

// Precedence demonstration
$rule = $srb->parse("a = 1 OR b = 2 AND c = 3");
// Parses as: a = 1 OR (b = 2 AND c = 3)

$context = new Context(['a' => 1, 'b' => 999, 'c' => 999]);
$rule->evaluate($context); // true (because a = 1 is true)

$context = new Context(['a' => 999, 'b' => 2, 'c' => 3]);
$rule->evaluate($context); // true (because b = 2 AND c = 3 is true)

$context = new Context(['a' => 999, 'b' => 2, 'c' => 999]);
$rule->evaluate($context); // false (a fails, and b AND c fails)
```

**Best Practice: Always Use Parentheses**

```php
// ❌ Unclear - relies on precedence knowledge
$rule = $srb->parse("status = 'active' AND age >= 18 OR vip = true");

// ✅ Clear - explicit grouping
$rule = $srb->parse("(status = 'active' AND age >= 18) OR vip = true");

// ✅ Even better - formatted for readability
$rule = $srb->parse("
    (status = 'active' AND age >= 18)
    OR
    vip = true
");
```

### Complex Nested Logic

```php
// Multi-level nesting
$rule = $srb->parse("
    (age >= 18 AND age <= 65)
    AND
    (country = 'US' OR country = 'CA')
    AND
    NOT (status = 'banned' OR status = 'suspended')
");

$valid = new Context([
    'age' => 30,
    'country' => 'US',
    'status' => 'active'
]);
$rule->evaluate($valid); // true

$invalid = new Context([
    'age' => 70, // too old
    'country' => 'US',
    'status' => 'active'
]);
$rule->evaluate($invalid); // false
```

---

## String Pattern Matching

### LIKE Operator with Wildcards

SQL's LIKE operator provides powerful pattern matching with two wildcards:
- `%` - Matches any sequence of characters (including zero characters)
- `_` - Matches exactly one character

#### Basic LIKE Patterns

```php
// Starts with
$rule = $srb->parse("name LIKE 'John%'");
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'John Doe'])); // true
$rule->evaluate(new Context(['name' => 'Johnny'])); // true
$rule->evaluate(new Context(['name' => 'Bob John'])); // false (doesn't start with John)

// Ends with
$rule = $srb->parse("email LIKE '%@example.com'");
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'admin@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com'])); // false

// Contains
$rule = $srb->parse("description LIKE '%important%'");
$rule->evaluate(new Context(['description' => 'This is important stuff'])); // true
$rule->evaluate(new Context(['description' => 'important'])); // true
$rule->evaluate(new Context(['description' => 'Something important here'])); // true
$rule->evaluate(new Context(['description' => 'Not relevant'])); // false
```

#### Underscore Wildcard

```php
// Exactly one character
$rule = $srb->parse("code LIKE 'A_C'");
$rule->evaluate(new Context(['code' => 'ABC'])); // true
$rule->evaluate(new Context(['code' => 'A1C'])); // true
$rule->evaluate(new Context(['code' => 'AC'])); // false (too short)
$rule->evaluate(new Context(['code' => 'ABBC'])); // false (too long)

// Multiple underscores
$rule = $srb->parse("phone LIKE '___-____'"); // 3 digits, dash, 4 digits
$rule->evaluate(new Context(['phone' => '555-1234'])); // true
$rule->evaluate(new Context(['phone' => '55-1234'])); // false

// Mixed wildcards
$rule = $srb->parse("product_code LIKE 'PR-____-%'"); // PR-[4 chars]-[anything]
$rule->evaluate(new Context(['product_code' => 'PR-2024-A'])); // true
$rule->evaluate(new Context(['product_code' => 'PR-2024-XYZ'])); // true
$rule->evaluate(new Context(['product_code' => 'PR-24-A'])); // false (middle part too short)
```

#### Advanced LIKE Patterns

```php
// Email domains
$rule = $srb->parse("email LIKE '%@gmail.com' OR email LIKE '%@yahoo.com'");
$rule->evaluate(new Context(['email' => 'user@gmail.com'])); // true

// Product SKUs
$rule = $srb->parse("sku LIKE 'ELEC-%'"); // All electronics
$rule->evaluate(new Context(['sku' => 'ELEC-12345'])); // true

// Version strings
$rule = $srb->parse("version LIKE '1.%.%'"); // Major version 1
$rule->evaluate(new Context(['version' => '1.2.3'])); // true
$rule->evaluate(new Context(['version' => '1.0.0'])); // true
$rule->evaluate(new Context(['version' => '2.0.0'])); // false

// File extensions
$rule = $srb->parse("filename LIKE '%.pdf' OR filename LIKE '%.docx'");
$rule->evaluate(new Context(['filename' => 'report.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'document.docx'])); // true
$rule->evaluate(new Context(['filename' => 'image.png'])); // false
```

### NOT LIKE Operator

```php
// Exclude patterns
$rule = $srb->parse("email NOT LIKE '%@test.com'");
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'admin@test.com'])); // false

// Exclude multiple patterns
$rule = $srb->parse("
    email NOT LIKE '%@test.com'
    AND email NOT LIKE '%@example.com'
");
$rule->evaluate(new Context(['email' => 'user@real.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com'])); // false

// Exclude temporary/test accounts
$rule = $srb->parse("
    username NOT LIKE 'test%'
    AND username NOT LIKE '%_temp'
    AND username NOT LIKE '%_backup'
");
```

### Case Sensitivity

```php
// LIKE is case-sensitive by default
$rule = $srb->parse("name LIKE 'John%'");
$rule->evaluate(new Context(['name' => 'John Doe'])); // true
$rule->evaluate(new Context(['name' => 'john doe'])); // false
$rule->evaluate(new Context(['name' => 'JOHN DOE'])); // false

// For case-insensitive matching, pre-convert to same case in context
// Or use string functions if implemented (see ADR 002)
```

### Escaping Special Characters in LIKE

```php
// To match literal % or _ characters, they need escaping
// Note: Implementation-specific - check your SQL WHERE DSL version

// Match filenames with underscores
// Pattern: product_% matches "product_123" but also "productX123"
// To match literal underscore: use implementation-specific escaping
```

### Real-World LIKE Examples

```php
// US ZIP codes
$rule = $srb->parse("zip LIKE '_____' OR zip LIKE '_____-____'");
$rule->evaluate(new Context(['zip' => '12345'])); // true
$rule->evaluate(new Context(['zip' => '12345-6789'])); // true

// Phone numbers
$rule = $srb->parse("phone LIKE '+1-%'"); // US country code
$rule->evaluate(new Context(['phone' => '+1-555-1234'])); // true

// URL patterns
$rule = $srb->parse("
    url LIKE 'https://%'
    AND url NOT LIKE '%/admin/%'
");
$rule->evaluate(new Context(['url' => 'https://example.com/page'])); // true
$rule->evaluate(new Context(['url' => 'https://example.com/admin/users'])); // false

// Database table names
$rule = $srb->parse("table_name LIKE 'wp_%'"); // WordPress tables
$rule->evaluate(new Context(['table_name' => 'wp_posts'])); // true
$rule->evaluate(new Context(['table_name' => 'wp_users'])); // true
```

---

## Range Queries

### BETWEEN...AND Operator

The BETWEEN operator tests if a value falls within a range (inclusive on both ends).

```php
// Basic numeric range
$rule = $srb->parse("age BETWEEN 18 AND 65");
$rule->evaluate(new Context(['age' => 18])); // true (inclusive lower bound)
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 65])); // true (inclusive upper bound)
$rule->evaluate(new Context(['age' => 17])); // false
$rule->evaluate(new Context(['age' => 66])); // false

// Equivalent to:
$rule = $srb->parse("age >= 18 AND age <= 65");
```

### BETWEEN with Different Data Types

```php
// Decimal ranges
$rule = $srb->parse("price BETWEEN 10.00 AND 99.99");
$rule->evaluate(new Context(['price' => 50.00])); // true
$rule->evaluate(new Context(['price' => 9.99])); // false
$rule->evaluate(new Context(['price' => 100.00])); // false

// String ranges (lexicographic)
$rule = $srb->parse("name BETWEEN 'A' AND 'M'");
$rule->evaluate(new Context(['name' => 'Alice'])); // true (A <= Alice <= M)
$rule->evaluate(new Context(['name' => 'Bob'])); // true
$rule->evaluate(new Context(['name' => 'Nancy'])); // false (N > M)

// Date strings (ISO format)
$rule = $srb->parse("date BETWEEN '2024-01-01' AND '2024-12-31'");
$rule->evaluate(new Context(['date' => '2024-06-15'])); // true
$rule->evaluate(new Context(['date' => '2023-12-31'])); // false
$rule->evaluate(new Context(['date' => '2025-01-01'])); // false

// Timestamp ranges
$rule = $srb->parse("created_at BETWEEN 1704067200 AND 1735689600");
// 2024-01-01 00:00:00 to 2025-01-01 00:00:00
$rule->evaluate(new Context(['created_at' => 1720000000])); // true (mid-2024)
```

### NOT BETWEEN

```php
// Exclude a range
$rule = $srb->parse("NOT (age BETWEEN 13 AND 17)");
$rule->evaluate(new Context(['age' => 12])); // true (too young for range)
$rule->evaluate(new Context(['age' => 15])); // false (in range)
$rule->evaluate(new Context(['age' => 18])); // true (too old for range)

// Alternative: use OR with comparison operators
$rule = $srb->parse("age < 13 OR age > 17");
```

### Complex Range Conditions

```php
// Multiple ranges
$rule = $srb->parse("
    (age BETWEEN 18 AND 35 OR age BETWEEN 50 AND 65)
    AND income BETWEEN 30000 AND 150000
");

$valid = new Context(['age' => 25, 'income' => 50000]);
$rule->evaluate($valid); // true

// Overlapping ranges with different criteria
$rule = $srb->parse("
    (price BETWEEN 0 AND 50 AND category = 'budget')
    OR (price BETWEEN 51 AND 200 AND category = 'standard')
    OR (price BETWEEN 201 AND 999999 AND category = 'premium')
");
```

### Real-World Range Examples

```php
// Working age population
$rule = $srb->parse("age BETWEEN 18 AND 65");

// Business hours (24-hour format)
$rule = $srb->parse("hour BETWEEN 9 AND 17");

// Acceptable temperature range
$rule = $srb->parse("temperature BETWEEN 68.0 AND 72.0");

// Valid discount percentage
$rule = $srb->parse("discount_pct BETWEEN 0 AND 100");

// Credit score ranges
$rule = $srb->parse("
    (credit_score BETWEEN 300 AND 579 AND risk_level = 'high')
    OR (credit_score BETWEEN 580 AND 669 AND risk_level = 'medium')
    OR (credit_score BETWEEN 670 AND 850 AND risk_level = 'low')
");

// Date ranges for seasonal promotions
$rule = $srb->parse("
    (month BETWEEN 6 AND 8 AND promo_type = 'summer')
    OR (month BETWEEN 11 AND 12 AND promo_type = 'holiday')
");

// Price tiers
$rule = $srb->parse("
    quantity BETWEEN 1 AND 10
    AND unit_price BETWEEN 5.00 AND 10.00
");

// Exam grade ranges
$rule = $srb->parse("
    (score BETWEEN 90 AND 100 AND grade = 'A')
    OR (score BETWEEN 80 AND 89 AND grade = 'B')
    OR (score BETWEEN 70 AND 79 AND grade = 'C')
");
```

### Performance Considerations

```php
// ✅ Good - BETWEEN is optimized
$rule = $srb->parse("age BETWEEN 18 AND 65");

// ❌ Less efficient - two separate comparisons
$rule = $srb->parse("age >= 18 AND age <= 65");

// Note: Both work, but BETWEEN may be optimized differently internally
// Always prefer BETWEEN for readability when checking ranges
```

---

## List Membership

### IN Operator

Test if a value exists in a list of values.

```php
// String values
$rule = $srb->parse("country IN ('US', 'CA', 'UK')");
$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'CA'])); // true
$rule->evaluate(new Context(['country' => 'FR'])); // false

// Numeric values
$rule = $srb->parse("status_code IN (200, 201, 204)");
$rule->evaluate(new Context(['status_code' => 200])); // true
$rule->evaluate(new Context(['status_code' => 404])); // false

// Mixed types (use with caution)
$rule = $srb->parse("value IN (1, 'two', true)");
$rule->evaluate(new Context(['value' => 1])); // true
$rule->evaluate(new Context(['value' => 'two'])); // true
$rule->evaluate(new Context(['value' => true])); // true
```

### NOT IN Operator

```php
// Exclude values from a list
$rule = $srb->parse("status NOT IN ('banned', 'suspended', 'deleted')");
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Exclude test/temporary data
$rule = $srb->parse("email NOT IN ('test@test.com', 'admin@localhost', 'noreply@example.com')");
$rule->evaluate(new Context(['email' => 'user@real.com'])); // true
$rule->evaluate(new Context(['email' => 'test@test.com'])); // false

// Exclude specific IDs
$rule = $srb->parse("user_id NOT IN (1, 2, 100, 200)");
```

### IN with Large Lists

```php
// Many allowed values
$rule = $srb->parse("
    state IN (
        'CA', 'NY', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA', 'NC', 'MI',
        'NJ', 'VA', 'WA', 'AZ', 'MA', 'TN', 'IN', 'MO', 'MD', 'WI'
    )
");

// Blacklist many values
$rule = $srb->parse("
    ip_address NOT IN (
        '192.168.1.1',
        '10.0.0.1',
        '172.16.0.1',
        '127.0.0.1'
    )
");
```

### Real-World IN Examples

```php
// Valid payment methods
$rule = $srb->parse("
    payment_method IN ('credit_card', 'debit_card', 'paypal', 'stripe')
    AND status = 'pending'
");

// Allowed file types
$rule = $srb->parse("
    file_extension IN ('jpg', 'jpeg', 'png', 'gif', 'webp')
    AND file_size < 5000000
");

// HTTP success codes
$rule = $srb->parse("response_code IN (200, 201, 202, 204, 206)");

// Business days (Monday = 1, Friday = 5)
$rule = $srb->parse("day_of_week IN (1, 2, 3, 4, 5)");

// Approved categories
$rule = $srb->parse("
    category IN ('electronics', 'books', 'clothing', 'home')
    AND price > 0
    AND in_stock = true
");

// User roles with permissions
$rule = $srb->parse("
    role IN ('admin', 'super_admin', 'moderator')
    AND account_status = 'active'
    AND email_verified = true
");

// Subscription tiers
$rule = $srb->parse("
    subscription_tier IN ('pro', 'enterprise', 'unlimited')
    AND payment_current = true
");

// Blocked/restricted countries
$rule = $srb->parse("
    country NOT IN ('XX', 'YY', 'ZZ')
    AND age >= 18
");
```

### IN vs Multiple OR

```php
// ❌ Verbose and less efficient
$rule = $srb->parse("
    status = 'active'
    OR status = 'pending'
    OR status = 'processing'
    OR status = 'completed'
");

// ✅ Cleaner and more efficient
$rule = $srb->parse("status IN ('active', 'pending', 'processing', 'completed')");
```

### Combining IN with Other Operators

```php
// IN + AND
$rule = $srb->parse("
    category IN ('electronics', 'appliances')
    AND price BETWEEN 100 AND 5000
    AND in_stock = true
");

// IN + OR
$rule = $srb->parse("
    (country IN ('US', 'CA') AND age >= 21)
    OR (country IN ('UK', 'DE', 'FR') AND age >= 18)
");

// Multiple IN clauses
$rule = $srb->parse("
    status IN ('active', 'pending')
    AND category IN ('electronics', 'books')
    AND shipping_method IN ('standard', 'express')
");

// IN + NOT IN
$rule = $srb->parse("
    category IN ('electronics', 'computers', 'phones')
    AND brand NOT IN ('BrandX', 'BrandY')
");
```

### Empty Lists

```php
// Empty IN list - always false
$rule = $srb->parse("status IN ()");
$rule->evaluate(new Context(['status' => 'active'])); // false

// Empty NOT IN list - always true
$rule = $srb->parse("status NOT IN ()");
$rule->evaluate(new Context(['status' => 'active'])); // true
```

---

## NULL Handling

### IS NULL Operator

Test if a field is NULL.

```php
// Check for NULL
$rule = $srb->parse("deleted_at IS NULL");
$rule->evaluate(new Context(['deleted_at' => null])); // true
$rule->evaluate(new Context(['deleted_at' => '2024-01-01'])); // false

// Active records (not soft-deleted)
$rule = $srb->parse("deleted_at IS NULL AND status = 'active'");

// Optional fields
$rule = $srb->parse("middle_name IS NULL");
$rule->evaluate(new Context(['middle_name' => null])); // true
$rule->evaluate(new Context(['middle_name' => ''])); // false (empty string != null)
```

### IS NOT NULL Operator

```php
// Check for non-NULL values
$rule = $srb->parse("email IS NOT NULL");
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => ''])); // true (empty string is not null)
$rule->evaluate(new Context(['email' => null])); // false

// Required fields
$rule = $srb->parse("
    first_name IS NOT NULL
    AND last_name IS NOT NULL
    AND email IS NOT NULL
");

// Completed profiles
$rule = $srb->parse("
    profile_picture IS NOT NULL
    AND bio IS NOT NULL
    AND phone IS NOT NULL
");
```

### NULL vs Empty String

**Important:** NULL and empty string are different!

```php
// NULL check
$rule = $srb->parse("description IS NULL");
$rule->evaluate(new Context(['description' => null])); // true
$rule->evaluate(new Context(['description' => ''])); // false

// Empty string check (use comparison)
$rule = $srb->parse("description = ''");
$rule->evaluate(new Context(['description' => ''])); // true
$rule->evaluate(new Context(['description' => null])); // false (null != '')

// Check for both NULL and empty
$rule = $srb->parse("description IS NULL OR description = ''");
$rule->evaluate(new Context(['description' => null])); // true
$rule->evaluate(new Context(['description' => ''])); // true
$rule->evaluate(new Context(['description' => 'text'])); // false
```

### NULL in Comparisons

```php
// NULL comparisons always return false (SQL standard behavior)
$rule = $srb->parse("value = NULL");
$rule->evaluate(new Context(['value' => null])); // false (don't use = for NULL!)

// ✅ Correct: Use IS NULL
$rule = $srb->parse("value IS NULL");
$rule->evaluate(new Context(['value' => null])); // true

// NULL in numeric comparisons
$rule = $srb->parse("age > 18");
$rule->evaluate(new Context(['age' => null])); // false (NULL > 18 is false)

// Guard against NULL
$rule = $srb->parse("age IS NOT NULL AND age > 18");
```

### NULL in IN Operator

```php
// NULL in IN list
$rule = $srb->parse("status IN ('active', 'pending', NULL)");
$rule->evaluate(new Context(['status' => null])); // false (NULL != NULL in SQL)
$rule->evaluate(new Context(['status' => 'active'])); // true

// Check for NULL or specific values
$rule = $srb->parse("status IS NULL OR status IN ('active', 'pending')");
$rule->evaluate(new Context(['status' => null])); // true
$rule->evaluate(new Context(['status' => 'active'])); // true
```

### Real-World NULL Examples

```php
// Soft deletes (active records only)
$rule = $srb->parse("
    deleted_at IS NULL
    AND archived_at IS NULL
");

// Complete user profiles
$rule = $srb->parse("
    email IS NOT NULL
    AND phone IS NOT NULL
    AND address IS NOT NULL
    AND date_of_birth IS NOT NULL
");

// Optional referral tracking
$rule = $srb->parse("
    (referred_by IS NOT NULL AND referral_bonus_paid = true)
    OR referred_by IS NULL
");

// Email verification status
$rule = $srb->parse("
    email IS NOT NULL
    AND email_verified_at IS NOT NULL
");

// Subscription cancellation
$rule = $srb->parse("
    subscription_started_at IS NOT NULL
    AND subscription_cancelled_at IS NULL
");

// Orders awaiting payment
$rule = $srb->parse("
    order_created_at IS NOT NULL
    AND payment_received_at IS NULL
    AND order_cancelled_at IS NULL
");

// Two-factor authentication
$rule = $srb->parse("
    (two_factor_secret IS NOT NULL AND two_factor_enabled = true)
    OR account_type = 'basic'
");

// Completed vs pending tasks
$rule = $srb->parse("
    completed_at IS NULL
    AND started_at IS NOT NULL
    AND cancelled_at IS NULL
");
```

### NULL Coalescing Pattern

```php
// Since SQL WHERE DSL doesn't have inline COALESCE, pre-compute in context

// ❌ Not available in SQL WHERE DSL
// $rule = $srb->parse("COALESCE(discount, 0) > 0");

// ✅ Pre-compute in context
$context = new Context([
    'discount' => $actualDiscount ?? 0  // PHP null coalescing
]);
$rule = $srb->parse("discount > 0");
```

### Three-Valued Logic

SQL uses three-valued logic: TRUE, FALSE, UNKNOWN (NULL)

```php
// AND truth table with NULL
$rule = $srb->parse("status = 'active' AND verified = true");

// If 'verified' is NULL:
$rule->evaluate(new Context(['status' => 'active', 'verified' => null])); // false
// Because: true AND unknown = unknown (treated as false)

// OR truth table with NULL
$rule = $srb->parse("vip = true OR status = 'premium'");

// If 'status' is NULL:
$rule->evaluate(new Context(['vip' => true, 'status' => null])); // true
// Because: true OR unknown = true

// NOT truth table with NULL
$rule = $srb->parse("NOT deleted = true");
$rule->evaluate(new Context(['deleted' => null])); // false
// Because: NOT unknown = unknown (treated as false)
```

---

## Nested Properties

SQL WHERE DSL supports dot notation for accessing nested object properties and array elements.

### Dot Notation Basics

```php
// Single-level nesting
$rule = $srb->parse("user.name = 'John'");

$context = new Context([
    'user' => ['name' => 'John']
]);
$rule->evaluate($context); // true

// Two levels deep
$rule = $srb->parse("user.profile.age >= 18");

$context = new Context([
    'user' => [
        'profile' => [
            'age' => 25
        ]
    ]
]);
$rule->evaluate($context); // true
```

### Deep Nesting

```php
// Multiple levels of nesting
$rule = $srb->parse("order.shipping.address.country = 'US'");

$context = new Context([
    'order' => [
        'shipping' => [
            'address' => [
                'country' => 'US',
                'city' => 'New York',
                'zip' => '10001'
            ]
        ]
    ]
]);
$rule->evaluate($context); // true

// Very deep nesting
$rule = $srb->parse("company.department.team.member.role = 'engineer'");

$context = new Context([
    'company' => [
        'department' => [
            'team' => [
                'member' => [
                    'role' => 'engineer'
                ]
            ]
        ]
    ]
]);
$rule->evaluate($context); // true
```

### Array Access by Index

```php
// Access array elements
$rule = $srb->parse("items[0].price > 100");

$context = new Context([
    'items' => [
        ['price' => 150, 'name' => 'Widget'],
        ['price' => 50, 'name' => 'Gadget']
    ]
]);
$rule->evaluate($context); // true (items[0].price = 150)

// Multiple array indexes
$rule = $srb->parse("orders[0].items[0].sku = 'WIDGET-001'");

$context = new Context([
    'orders' => [
        [
            'items' => [
                ['sku' => 'WIDGET-001', 'qty' => 5]
            ]
        ]
    ]
]);
$rule->evaluate($context); // true
```

### Mixing Objects and Arrays

```php
// Complex nested structures
$rule = $srb->parse("
    cart.items[0].product.category = 'electronics'
    AND cart.items[0].quantity >= 1
    AND cart.customer.verified = true
");

$context = new Context([
    'cart' => [
        'items' => [
            [
                'product' => [
                    'category' => 'electronics',
                    'name' => 'Laptop'
                ],
                'quantity' => 2
            ]
        ],
        'customer' => [
            'verified' => true
        ]
    ]
]);
$rule->evaluate($context); // true
```

### Real-World Nested Examples

```php
// User profile validation
$rule = $srb->parse("
    user.profile.age >= 18
    AND user.profile.country = 'US'
    AND user.settings.notifications.email = true
");

// Order fulfillment
$rule = $srb->parse("
    order.status = 'pending'
    AND order.payment.status = 'completed'
    AND order.shipping.address.country IN ('US', 'CA')
");

// E-commerce product eligibility
$rule = $srb->parse("
    product.category = 'electronics'
    AND product.pricing.current_price BETWEEN 100 AND 5000
    AND product.inventory.in_stock = true
    AND product.ratings.average >= 4.0
");

// Multi-tenant SaaS access
$rule = $srb->parse("
    tenant.subscription.status = 'active'
    AND tenant.subscription.plan IN ('pro', 'enterprise')
    AND user.role IN ('admin', 'owner')
    AND user.permissions.can_access_reports = true
");

// Healthcare patient eligibility
$rule = $srb->parse("
    patient.demographics.age >= 18
    AND patient.insurance.status = 'active'
    AND patient.insurance.coverage.type IN ('PPO', 'HMO')
    AND appointment.provider.accepts_insurance = true
");

// IoT device monitoring
$rule = $srb->parse("
    device.status.online = true
    AND device.sensors.temperature.current BETWEEN 20 AND 25
    AND device.location.zone = 'production_floor'
    AND device.alerts.critical_count = 0
");

// Financial transaction validation
$rule = $srb->parse("
    transaction.amount > 1000
    AND transaction.sender.account.verified = true
    AND transaction.sender.account.balance >= transaction.amount
    AND transaction.recipient.account.status = 'active'
");
```

### Null Safety with Nested Properties

```php
// Check nested property exists
$rule = $srb->parse("user.profile.bio IS NOT NULL");

$context = new Context([
    'user' => [
        'profile' => [
            'bio' => 'Software developer'
        ]
    ]
]);
$rule->evaluate($context); // true

// Missing nested properties evaluate as NULL
$rule = $srb->parse("user.profile.phone IS NULL");

$context = new Context([
    'user' => [
        'profile' => [
            'bio' => 'Developer'
            // 'phone' is missing
        ]
    ]
]);
$rule->evaluate($context); // true

// Guard against missing nested structures
$rule = $srb->parse("
    user IS NOT NULL
    AND user.profile IS NOT NULL
    AND user.profile.age >= 18
");
```

### Performance with Deep Nesting

```php
// ✅ Good - access nested value once
$rule = $srb->parse("order.shipping.address.country = 'US'");

// ❌ Inefficient - accesses same nested path multiple times
$rule = $srb->parse("
    order.shipping.address.country = 'US'
    OR order.shipping.address.country = 'CA'
    OR order.shipping.address.country = 'UK'
");

// ✅ Better - use IN operator
$rule = $srb->parse("order.shipping.address.country IN ('US', 'CA', 'UK')");

// ✅ Best - flatten in context if used frequently
$context = new Context([
    'order' => ['shipping' => ['address' => ['country' => 'US']]],
    'shipping_country' => 'US'  // Flattened for performance
]);
$rule = $srb->parse("shipping_country IN ('US', 'CA', 'UK')");
```

---

## Advanced Patterns

### Complex Business Rules

```php
// Multi-tier subscription eligibility
$rule = $srb->parse("
    (
        subscription.tier = 'free'
        AND api_calls_this_month < 1000
    )
    OR (
        subscription.tier = 'pro'
        AND api_calls_this_month < 50000
        AND payment.status = 'current'
    )
    OR (
        subscription.tier = 'enterprise'
        AND payment.status = 'current'
    )
");

// E-commerce checkout validation
$rule = $srb->parse("
    cart.total > 0
    AND cart.items_count > 0
    AND user.email IS NOT NULL
    AND user.email_verified = true
    AND (
        (shipping.method = 'standard' AND cart.total >= 25)
        OR (shipping.method = 'express' AND cart.total >= 50)
        OR shipping.method = 'free'
    )
    AND payment.method IN ('credit_card', 'paypal', 'stripe')
");

// Content access control
$rule = $srb->parse("
    content.status = 'published'
    AND content.published_at IS NOT NULL
    AND (
        content.visibility = 'public'
        OR (
            content.visibility = 'members'
            AND user.subscription.status = 'active'
        )
        OR (
            content.visibility = 'premium'
            AND user.subscription.tier IN ('pro', 'enterprise')
        )
    )
    AND NOT content.id IN (user.blocked_content_ids)
");
```

### Time-Based Rules

```php
// Business hours check (using hour of day)
$rule = $srb->parse("
    day_of_week BETWEEN 1 AND 5
    AND hour BETWEEN 9 AND 17
    AND holiday = false
");

// Session timeout
$rule = $srb->parse("
    session.last_activity_at IS NOT NULL
    AND (current_timestamp - session.last_activity_at) <= 3600
");

// Subscription expiration
$rule = $srb->parse("
    subscription.expires_at IS NOT NULL
    AND subscription.expires_at > current_timestamp
    AND subscription.cancelled_at IS NULL
");

// Limited-time promotion
$rule = $srb->parse("
    promo.start_date <= current_date
    AND promo.end_date >= current_date
    AND promo.code IS NOT NULL
    AND promo.uses_remaining > 0
");

// Age calculation (using timestamps)
$rule = $srb->parse("
    birth_timestamp IS NOT NULL
    AND (current_timestamp - birth_timestamp) >= 567648000
");
// 567648000 seconds = 18 years (approximate)
```

### Geolocation Rules

```php
// Regional restrictions
$rule = $srb->parse("
    (
        country = 'US'
        AND state IN ('CA', 'NY', 'TX', 'FL')
    )
    OR (
        country = 'CA'
        AND province IN ('ON', 'BC', 'QC')
    )
    OR country IN ('UK', 'DE', 'FR')
");

// Shipping zones
$rule = $srb->parse("
    (
        country = 'US'
        AND (
            (zone = 'west' AND state IN ('CA', 'OR', 'WA'))
            OR (zone = 'east' AND state IN ('NY', 'NJ', 'MA'))
        )
    )
");

// Distance-based (if pre-computed)
$rule = $srb->parse("
    distance_km <= 50
    AND delivery_available = true
    AND postal_code LIKE 'M%'
");
```

### Inventory and Stock Management

```php
// Stock availability
$rule = $srb->parse("
    inventory.quantity > 0
    AND inventory.reserved_quantity < inventory.quantity
    AND (inventory.quantity - inventory.reserved_quantity) >= requested_quantity
    AND inventory.location IN ('warehouse_a', 'warehouse_b')
");

// Low stock alert
$rule = $srb->parse("
    inventory.quantity > 0
    AND inventory.quantity <= reorder_threshold
    AND reorder_pending = false
    AND product.discontinued = false
");

// Backorder eligibility
$rule = $srb->parse("
    inventory.quantity = 0
    AND product.allow_backorder = true
    AND expected_restock_date IS NOT NULL
    AND expected_restock_date <= current_date + 30
");
```

### Fraud Detection Patterns

```php
// Transaction risk scoring
$rule = $srb->parse("
    (
        amount > 1000
        OR transaction_count_last_hour > 5
        OR (amount > 500 AND account_age_days < 7)
    )
    AND (
        ip_country != billing_country
        OR device_fingerprint IN (known_fraud_devices)
        OR email LIKE '%@temporary-email.%'
    )
");

// Suspicious activity
$rule = $srb->parse("
    failed_login_attempts >= 5
    AND (current_timestamp - last_failed_login) <= 300
    AND account_locked = false
");

// Velocity checks
$rule = $srb->parse("
    transaction_count_last_24h > 10
    AND total_amount_last_24h > 5000
    AND average_transaction_amount > 500
");
```

### Tiered Pricing Logic

```php
// Volume discounts
$rule = $srb->parse("
    (
        quantity >= 100
        AND unit_price <= 10.00
    )
    OR (
        quantity >= 50
        AND quantity < 100
        AND unit_price <= 12.00
    )
    OR (
        quantity >= 10
        AND quantity < 50
        AND unit_price <= 15.00
    )
    OR (
        quantity < 10
        AND unit_price <= 20.00
    )
");

// Loyalty tier benefits
$rule = $srb->parse("
    (
        customer.tier = 'bronze'
        AND customer.total_purchases >= 100
    )
    OR (
        customer.tier = 'silver'
        AND customer.total_purchases >= 500
        AND discount_rate >= 0.10
    )
    OR (
        customer.tier = 'gold'
        AND customer.total_purchases >= 2000
        AND discount_rate >= 0.20
    )
");
```

### Conditional Workflows

```php
// Approval workflow routing
$rule = $srb->parse("
    (
        amount < 1000
        AND approver_level = 'manager'
    )
    OR (
        amount >= 1000
        AND amount < 10000
        AND approver_level = 'director'
    )
    OR (
        amount >= 10000
        AND approver_level = 'vp'
        AND approvals_received >= 2
    )
");

// Support ticket routing
$rule = $srb->parse("
    (
        priority = 'critical'
        AND category IN ('security', 'data_loss')
        AND route_to = 'tier_3'
    )
    OR (
        priority IN ('high', 'urgent')
        AND route_to = 'tier_2'
    )
    OR (
        priority IN ('normal', 'low')
        AND route_to = 'tier_1'
    )
");
```

### Multi-Factor Eligibility

```php
// Loan approval criteria
$rule = $srb->parse("
    age >= 18
    AND age <= 75
    AND credit_score >= 650
    AND annual_income >= 30000
    AND employment_status = 'employed'
    AND employment_months >= 6
    AND (debt_to_income_ratio <= 0.43 OR has_cosigner = true)
    AND NOT (bankruptcy = true AND years_since_bankruptcy < 7)
");

// Insurance underwriting
$rule = $srb->parse("
    age BETWEEN 18 AND 65
    AND smoker = false
    AND (
        (health_conditions IS NULL)
        OR (health_conditions NOT LIKE '%diabetes%'
            AND health_conditions NOT LIKE '%heart%')
    )
    AND occupation NOT IN ('pilot', 'miner', 'firefighter')
    AND coverage_amount <= 1000000
");
```

---

## Performance Optimization

### Short-Circuit Evaluation

SQL WHERE DSL uses short-circuit evaluation for AND/OR operators. Put the most likely to fail (or succeed) conditions first.

```php
// ✅ Good - cheap checks first, likely to fail fast
$rule = $srb->parse("
    status = 'active'
    AND country = 'US'
    AND age >= 18
    AND subscription.tier IN ('pro', 'enterprise')
");

// ❌ Bad - expensive nested access first
$rule = $srb->parse("
    subscription.tier IN ('pro', 'enterprise')
    AND status = 'active'
");

// ✅ Optimal ordering for AND (most likely to fail first)
$rule = $srb->parse("
    status = 'active'                    -- Fast, often fails (30% active)
    AND country = 'US'                   -- Fast, medium selectivity (50%)
    AND age >= 18                        -- Fast, rarely fails (90%)
    AND order.total > 1000               -- More expensive, rarely fails
");

// ✅ Optimal ordering for OR (most likely to succeed first)
$rule = $srb->parse("
    vip_member = true                    -- Fast, 20% true - exits early
    OR subscription.tier = 'enterprise'  -- 5% true
    OR total_purchases > 10000           -- 2% true
");
```

### Avoid Redundant Evaluations

```php
// ❌ Bad - evaluates same nested path multiple times
$rule = $srb->parse("
    user.profile.country = 'US'
    OR user.profile.country = 'CA'
    OR user.profile.country = 'UK'
");

// ✅ Better - use IN operator (single evaluation)
$rule = $srb->parse("user.profile.country IN ('US', 'CA', 'UK')");

// ❌ Bad - LIKE pattern evaluated multiple times
$rule = $srb->parse("
    email LIKE '%@gmail.com'
    OR email LIKE '%@yahoo.com'
    OR email LIKE '%@outlook.com'
");

// ✅ Better - but still multiple LIKE evaluations
$rule = $srb->parse("
    email LIKE '%@gmail.com'
    OR email LIKE '%@yahoo.com'
    OR email LIKE '%@outlook.com'
");
// Note: For this case, pre-extract domain in context if performance critical

// ✅ Best - flatten in context for repeated use
$context = new Context([
    'user' => ['profile' => ['country' => 'US']],
    'country' => 'US'  // Flattened
]);
$rule = $srb->parse("country IN ('US', 'CA', 'UK')");
```

### Simplify Complex Expressions

```php
// ❌ Complex nested OR chains
$rule = $srb->parse("
    (status = 'active' OR status = 'pending' OR status = 'processing')
    AND (type = 'A' OR type = 'B' OR type = 'C')
    AND (region = 'US' OR region = 'CA' OR region = 'UK')
");

// ✅ Simplified with IN operators
$rule = $srb->parse("
    status IN ('active', 'pending', 'processing')
    AND type IN ('A', 'B', 'C')
    AND region IN ('US', 'CA', 'UK')
");

// ❌ Redundant checks
$rule = $srb->parse("
    age >= 18 AND age < 65
    AND (age >= 21 OR country != 'US')
");
// If age >= 21, then age >= 18 is redundant

// ✅ Optimized logic
$rule = $srb->parse("
    age >= 18
    AND age < 65
    AND (age >= 21 OR country != 'US')
");
```

### Pre-Compute Values in Context

Since SQL WHERE DSL doesn't support inline arithmetic (see ADR 002), pre-compute values in your context.

```php
// ❌ Not supported - inline arithmetic
// $rule = $srb->parse("price + shipping + tax > 100");

// ✅ Pre-compute in context
$context = new Context([
    'price' => 75.00,
    'shipping' => 15.00,
    'tax' => 12.00,
    'total' => 102.00  // Pre-computed
]);
$rule = $srb->parse("total > 100");

// ✅ Pre-compute derived values
$context = new Context([
    'quantity' => 10,
    'unit_price' => 50.00,
    'line_total' => 500.00,  // quantity * unit_price
    'discount_pct' => 20,
    'final_price' => 400.00  // line_total * (1 - discount_pct/100)
]);
$rule = $srb->parse("final_price >= 300");

// ✅ Pre-compute time-based values
$context = new Context([
    'created_at' => 1704067200,
    'current_timestamp' => time(),
    'age_seconds' => time() - 1704067200,
    'age_days' => (time() - 1704067200) / 86400
]);
$rule = $srb->parse("age_days >= 30");
```

### Flatten Deep Nesting

```php
// ❌ Deep nesting - slower access
$rule = $srb->parse("
    order.customer.profile.subscription.tier = 'pro'
    AND order.customer.profile.verified = true
");

$context = new Context([
    'order' => [
        'customer' => [
            'profile' => [
                'subscription' => ['tier' => 'pro'],
                'verified' => true
            ]
        ]
    ]
]);

// ✅ Flatten for performance
$rule = $srb->parse("
    subscription_tier = 'pro'
    AND customer_verified = true
");

$context = new Context([
    'subscription_tier' => 'pro',    // Flattened
    'customer_verified' => true      // Flattened
]);
```

### Use BETWEEN Instead of Two Comparisons

```php
// ❌ Less optimal - two comparisons
$rule = $srb->parse("age >= 18 AND age <= 65");

// ✅ Better - single BETWEEN operation
$rule = $srb->parse("age BETWEEN 18 AND 65");

// Performance difference is minimal but BETWEEN is also more readable
```

### Index-Friendly Patterns

When rules map to database queries, write them to be index-friendly:

```php
// ✅ Good - can use index on 'status'
$rule = $srb->parse("status = 'active' AND created_at > '2024-01-01'");

// ❌ Bad - LIKE with leading wildcard prevents index use
$rule = $srb->parse("email LIKE '%@example.com'");

// ✅ Better - if searching for domain, store separately
$rule = $srb->parse("email_domain = 'example.com'");

// ✅ Good - exact match can use index
$rule = $srb->parse("category = 'electronics'");

// ❌ Bad - NOT IN harder to optimize
$rule = $srb->parse("status NOT IN ('deleted', 'archived', 'banned')");

// ✅ Better - IN with positive logic (if possible)
$rule = $srb->parse("status IN ('active', 'pending', 'processing')");
```

### Cache Compiled Rules

```php
// Parse rules once, reuse many times
class RuleCache
{
    private array $compiledRules = [];

    public function getRule(string $sql): Rule
    {
        if (!isset($this->compiledRules[$sql])) {
            $srb = new SqlWhereRuleBuilder();
            $this->compiledRules[$sql] = $srb->parse($sql);
        }
        return $this->compiledRules[$sql];
    }
}

// Usage
$cache = new RuleCache();

// Compile once
$rule = $cache->getRule("age >= 18 AND country = 'US'");

// Reuse for many evaluations
$result1 = $rule->evaluate($context1);
$result2 = $rule->evaluate($context2);
$result3 = $rule->evaluate($context3);
```

### Batch Rule Evaluation

```php
// ✅ Efficient - reuse compiled rule
$rule = $srb->parse("age >= 18 AND verified = true");

$results = [];
foreach ($users as $user) {
    $context = new Context($user);
    $results[$user['id']] = $rule->evaluate($context);
}

// ❌ Inefficient - recompiles rule each time
foreach ($users as $user) {
    $rule = $srb->parse("age >= 18 AND verified = true");
    $context = new Context($user);
    $results[$user['id']] = $rule->evaluate($context);
}
```

---

## Common Pitfalls

### String Quoting

```php
// ❌ Wrong - missing quotes around string values
$rule = $srb->parse("country = US"); // Syntax error!

// ✅ Correct - use single quotes
$rule = $srb->parse("country = 'US'");

// ❌ Wrong - using double quotes (might work but not SQL standard)
$rule = $srb->parse('country = "US"'); // Use single quotes

// ✅ Correct - SQL standard single quotes
$rule = $srb->parse("country = 'US'");
```

### Escaping Single Quotes in Strings

```php
// ❌ Wrong - unescaped quote breaks syntax
$rule = $srb->parse("name = 'O'Brien'"); // Syntax error!

// ✅ Correct - escape single quote with double single quote
$rule = $srb->parse("name = 'O''Brien'");

// ✅ Alternative - use different PHP string quotes
$rule = $srb->parse('name = "O\'Brien"'); // If DSL supports double quotes

// Example: Searching for company names
$rule = $srb->parse("company = 'McDonald''s Corporation'");
```

### NULL Comparisons

```php
// ❌ Wrong - using = with NULL
$rule = $srb->parse("deleted_at = NULL");
$rule->evaluate(new Context(['deleted_at' => null])); // false (doesn't work!)

// ✅ Correct - use IS NULL
$rule = $srb->parse("deleted_at IS NULL");
$rule->evaluate(new Context(['deleted_at' => null])); // true

// ❌ Wrong - using != with NULL
$rule = $srb->parse("email != NULL");

// ✅ Correct - use IS NOT NULL
$rule = $srb->parse("email IS NOT NULL");
```

### Boolean Values

```php
// ❌ Wrong - quoting boolean as string
$rule = $srb->parse("verified = 'true'"); // Compares to string "true"
$rule->evaluate(new Context(['verified' => true])); // false!

// ✅ Correct - no quotes for booleans
$rule = $srb->parse("verified = true");
$rule->evaluate(new Context(['verified' => true])); // true

// ❌ Wrong - comparing to 1/0 when you mean boolean
$rule = $srb->parse("active = 1"); // This is numeric comparison

// ✅ Correct - use true/false for boolean fields
$rule = $srb->parse("active = true");
```

### Case Sensitivity

```php
// ❌ Wrong assumption - SQL WHERE is case-sensitive by default
$rule = $srb->parse("country = 'us'");
$rule->evaluate(new Context(['country' => 'US'])); // false!

// ✅ Correct - match case exactly
$rule = $srb->parse("country = 'US'");
$rule->evaluate(new Context(['country' => 'US'])); // true

// ✅ Alternative - pre-normalize case in context
$context = new Context([
    'country' => strtoupper($userInput) // Normalize to uppercase
]);
$rule = $srb->parse("country = 'US'");
```

### LIKE Pattern Confusion

```php
// ❌ Wrong - forgetting wildcards
$rule = $srb->parse("email LIKE 'example.com'");
$rule->evaluate(new Context(['email' => 'user@example.com'])); // false!

// ✅ Correct - use % wildcard
$rule = $srb->parse("email LIKE '%example.com'");
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true

// ❌ Wrong - using * instead of %
$rule = $srb->parse("name LIKE 'John*'"); // Literal asterisk!

// ✅ Correct - % is the SQL wildcard
$rule = $srb->parse("name LIKE 'John%'");

// ❌ Wrong - confusing _ with %
$rule = $srb->parse("code LIKE 'A_'");
$rule->evaluate(new Context(['code' => 'ABC'])); // false (_ matches exactly one char)

// ✅ Correct - use % for any characters
$rule = $srb->parse("code LIKE 'A%'");
$rule->evaluate(new Context(['code' => 'ABC'])); // true
```

### Operator Precedence Confusion

```php
// ❌ Ambiguous - what does this mean?
$rule = $srb->parse("a = 1 OR b = 2 AND c = 3");
// Evaluates as: a = 1 OR (b = 2 AND c = 3)
// Might not be what you intended!

// ✅ Clear - use parentheses
$rule = $srb->parse("(a = 1 OR b = 2) AND c = 3"); // Different meaning!

// ❌ Wrong assumption about NOT precedence
$rule = $srb->parse("NOT status = 'active' AND verified = true");
// Evaluates as: (NOT status = 'active') AND verified = true

// ✅ Clear intention
$rule = $srb->parse("NOT (status = 'active' AND verified = true)");
```

### IN Operator Mistakes

```php
// ❌ Wrong - forgetting parentheses
$rule = $srb->parse("status IN 'active', 'pending'"); // Syntax error!

// ✅ Correct - use parentheses
$rule = $srb->parse("status IN ('active', 'pending')");

// ❌ Wrong - mixing quote styles
$rule = $srb->parse("status IN ('active', \"pending\")"); // Inconsistent

// ✅ Correct - consistent single quotes
$rule = $srb->parse("status IN ('active', 'pending')");

// ❌ Wrong - putting field name in the list
$rule = $srb->parse("'US' IN country"); // Backwards!

// ✅ Correct - field first, list second
$rule = $srb->parse("country IN ('US', 'CA', 'UK')");
```

### BETWEEN Mistakes

```php
// ❌ Wrong - reversed range (min > max)
$rule = $srb->parse("age BETWEEN 65 AND 18"); // Backwards!
$rule->evaluate(new Context(['age' => 30])); // false

// ✅ Correct - min first, max second
$rule = $srb->parse("age BETWEEN 18 AND 65");

// ❌ Wrong - forgetting BETWEEN is inclusive
$rule = $srb->parse("age BETWEEN 18 AND 65");
// This includes both 18 and 65

// ✅ Correct - exclusive range if needed
$rule = $srb->parse("age > 18 AND age < 65");
```

### Type Coercion Issues

```php
// ⚠️ Caution - SQL WHERE uses type coercion
$rule = $srb->parse("age = 18");
$rule->evaluate(new Context(['age' => '18'])); // true (coerced!)
$rule->evaluate(new Context(['age' => 18])); // true

// ✅ Ensure types match in context for predictable behavior
$context = new Context([
    'age' => (int) $userInput // Explicit cast
]);

// ⚠️ Caution - boolean coercion
$rule = $srb->parse("active = true");
$rule->evaluate(new Context(['active' => 1])); // May be true (coerced)

// ✅ Better - explicit boolean conversion in context
$context = new Context([
    'active' => (bool) $value
]);
```

### Missing Whitespace

```php
// ❌ Hard to read - no spaces
$rule = $srb->parse("age>=18AND country='US'");

// ✅ Readable - proper spacing
$rule = $srb->parse("age >= 18 AND country = 'US'");

// ❌ Confusing - inconsistent spacing
$rule = $srb->parse("age>=18 AND country='US'");

// ✅ Consistent - uniform spacing
$rule = $srb->parse("age >= 18 AND country = 'US'");
```

### Reserved Keywords as Field Names

```php
// ⚠️ Problematic - field name conflicts with keyword
// If you have a field literally named "order", "select", "where", etc.

// ❌ Might cause parsing errors
$rule = $srb->parse("order = 'pending'"); // 'order' is SQL keyword!

// ✅ Solution 1 - rename field in context
$context = new Context([
    'order_status' => 'pending'  // Renamed
]);
$rule = $srb->parse("order_status = 'pending'");

// ✅ Solution 2 - check DSL for quoting support (e.g., backticks)
// $rule = $srb->parse("`order` = 'pending'");
// Note: Check if your SQL WHERE DSL implementation supports this
```

---

## Real-World Examples

### E-Commerce

#### Product Search & Filtering

```php
// Basic product listing
$rule = $srb->parse("
    category = 'electronics'
    AND price >= 100
    AND price <= 5000
    AND in_stock = true
    AND status = 'active'
");

// Advanced product filtering
$rule = $srb->parse("
    category IN ('electronics', 'computers', 'phones')
    AND price BETWEEN 500 AND 2000
    AND (
        (brand = 'BrandA' AND rating >= 4.0)
        OR (brand = 'BrandB' AND rating >= 4.5)
        OR (featured = true AND rating >= 3.5)
    )
    AND in_stock = true
    AND discount_pct >= 10
    AND NOT sku IN ('DISCONTINUED-001', 'RECALLED-002')
");

// Personalized product recommendations
$rule = $srb->parse("
    category IN (user.preferred_categories)
    AND price <= user.max_price_filter
    AND brand NOT IN (user.excluded_brands)
    AND rating >= 4.0
    AND (
        tags LIKE '%trending%'
        OR sales_count > 100
        OR release_date >= '2024-01-01'
    )
");
```

#### Shopping Cart & Checkout

```php
// Cart validation
$rule = $srb->parse("
    cart.total > 0
    AND cart.items_count > 0
    AND cart.items_count <= 50
    AND user.email IS NOT NULL
    AND user.email_verified = true
    AND payment.method IN ('credit_card', 'paypal', 'stripe')
");

// Free shipping eligibility
$rule = $srb->parse("
    (
        cart.subtotal >= 50
        AND shipping.country = 'US'
    )
    OR (
        cart.subtotal >= 75
        AND shipping.country IN ('CA', 'UK')
    )
    OR user.membership_tier IN ('gold', 'platinum')
");

// Promotional discounts
$rule = $srb->parse("
    promo_code IS NOT NULL
    AND promo.valid_from <= current_date
    AND promo.valid_until >= current_date
    AND (
        (promo.min_purchase IS NULL OR cart.subtotal >= promo.min_purchase)
        AND (promo.max_uses IS NULL OR promo.uses_count < promo.max_uses)
        AND (promo.allowed_categories IS NULL OR category IN promo.allowed_categories)
    )
    AND user.id NOT IN promo.excluded_users
");
```

#### Inventory Management

```php
// Low stock alert
$rule = $srb->parse("
    quantity > 0
    AND quantity <= reorder_threshold
    AND reorder_pending = false
    AND status = 'active'
    AND discontinued = false
");

// Stock availability for order
$rule = $srb->parse("
    (quantity - reserved_quantity) >= requested_quantity
    AND warehouse IN ('warehouse_a', 'warehouse_b', 'warehouse_c')
    AND status = 'available'
    AND quality_hold = false
");

// Backorder eligibility
$rule = $srb->parse("
    quantity = 0
    AND allow_backorder = true
    AND expected_restock_date IS NOT NULL
    AND expected_restock_date BETWEEN current_date AND current_date + 30
    AND supplier.status = 'active'
");
```

### User Access Control

#### Authentication & Authorization

```php
// Basic login validation
$rule = $srb->parse("
    email IS NOT NULL
    AND password_hash IS NOT NULL
    AND account_status = 'active'
    AND email_verified = true
    AND locked_until IS NULL
");

// Admin access
$rule = $srb->parse("
    role IN ('admin', 'super_admin')
    AND account_status = 'active'
    AND email_verified = true
    AND two_factor_enabled = true
    AND (last_password_change IS NULL OR last_password_change >= current_date - 90)
    AND NOT user_id IN (suspended_admin_ids)
");

// Feature access control
$rule = $srb->parse("
    (
        subscription.tier IN ('pro', 'enterprise')
        AND subscription.status = 'active'
        AND subscription.expires_at > current_timestamp
    )
    OR (
        beta_tester = true
        AND beta_features_enabled = true
    )
    OR user_id IN (early_access_users)
");
```

#### Content Access

```php
// Article visibility
$rule = $srb->parse("
    article.status = 'published'
    AND article.published_at <= current_timestamp
    AND (article.expires_at IS NULL OR article.expires_at > current_timestamp)
    AND (
        article.visibility = 'public'
        OR (article.visibility = 'members' AND user.registered = true)
        OR (article.visibility = 'premium' AND user.subscription.tier IN ('pro', 'enterprise'))
        OR article.author_id = user.id
    )
");

// Course enrollment eligibility
$rule = $srb->parse("
    course.status = 'active'
    AND course.enrollment_open = true
    AND (course.max_students IS NULL OR course.enrolled_count < course.max_students)
    AND (
        course.prerequisite_course_id IS NULL
        OR course.prerequisite_course_id IN (user.completed_courses)
    )
    AND user.account_status = 'active'
");
```

#### User Segmentation

```php
// VIP customers
$rule = $srb->parse("
    total_purchases >= 5000
    AND account_age_days >= 365
    AND (
        orders_count >= 20
        OR average_order_value >= 250
    )
    AND churn_risk_score < 30
    AND email_opt_in = true
");

// At-risk users (churn prevention)
$rule = $srb->parse("
    subscription.status = 'active'
    AND last_login_days_ago > 30
    AND (
        support_tickets_count > 5
        OR feature_usage_score < 20
        OR (trial_user = true AND trial_days_remaining < 7)
    )
");
```

### SaaS Applications

#### Subscription Management

```php
// Active subscription validation
$rule = $srb->parse("
    subscription.status = 'active'
    AND subscription.expires_at > current_timestamp
    AND payment.failed = false
    AND (cancellation.requested_at IS NULL OR cancellation.effective_at > current_timestamp)
");

// API rate limiting
$rule = $srb->parse("
    (
        plan = 'free'
        AND api_calls_this_month < 1000
    )
    OR (
        plan = 'starter'
        AND api_calls_this_month < 10000
        AND payment.status = 'current'
    )
    OR (
        plan = 'pro'
        AND api_calls_this_month < 100000
        AND payment.status = 'current'
    )
    OR (
        plan = 'enterprise'
        AND payment.status = 'current'
    )
");

// Feature flags per tier
$rule = $srb->parse("
    feature.enabled = true
    AND (
        (feature.tier = 'all')
        OR (feature.tier = 'pro' AND subscription.tier IN ('pro', 'enterprise'))
        OR (feature.tier = 'enterprise' AND subscription.tier = 'enterprise')
    )
    AND environment = 'production'
");
```

#### Usage-Based Billing

```php
// Overage charges
$rule = $srb->parse("
    total_storage_gb > included_storage_gb
    AND overage_billing_enabled = true
    AND payment.method IS NOT NULL
");

// Quota enforcement
$rule = $srb->parse("
    (
        (quota_type = 'seats' AND current_seats < max_seats)
        OR (quota_type = 'storage' AND current_storage_gb < max_storage_gb)
        OR (quota_type = 'api_calls' AND api_calls_this_month < monthly_limit)
    )
    AND subscription.status = 'active'
");
```

#### Multi-Tenancy

```php
// Tenant access control
$rule = $srb->parse("
    tenant.status = 'active'
    AND tenant.subscription.status = 'active'
    AND user.tenant_id = tenant.id
    AND user.role IN ('owner', 'admin', 'member')
    AND user.invitation_accepted = true
");

// Cross-tenant restrictions
$rule = $srb->parse("
    resource.tenant_id = user.tenant_id
    AND (
        resource.visibility = 'tenant'
        OR (resource.visibility = 'public' AND tenant.public_sharing_enabled = true)
    )
    AND NOT resource.id IN (user.blocked_resources)
");
```

### Financial Services

#### Loan & Credit Approval

```php
// Personal loan pre-qualification
$rule = $srb->parse("
    age >= 18
    AND age <= 75
    AND credit_score >= 650
    AND annual_income >= 30000
    AND employment_status = 'employed'
    AND employment_months >= 6
    AND (debt_to_income_ratio <= 0.43 OR has_cosigner = true)
    AND NOT (bankruptcy = true AND years_since_bankruptcy < 7)
    AND NOT (foreclosure = true AND years_since_foreclosure < 5)
");

// Credit card approval
$rule = $srb->parse("
    age >= 21
    AND credit_score >= 700
    AND (
        (annual_income >= 40000 AND employment_months >= 12)
        OR (annual_income >= 75000 AND employment_months >= 6)
    )
    AND existing_credit_cards <= 5
    AND payment_history_score >= 80
    AND recent_credit_inquiries <= 3
");

// Mortgage pre-approval
$rule = $srb->parse("
    age BETWEEN 25 AND 70
    AND credit_score >= 620
    AND annual_income >= 50000
    AND employment_months >= 24
    AND down_payment_pct >= 3.5
    AND debt_to_income_ratio <= 0.43
    AND property_value > 0
    AND (property_value * down_payment_pct / 100) >= 5000
");
```

#### Fraud Detection

```php
// Transaction fraud screening
$rule = $srb->parse("
    (
        amount > 5000
        OR (amount > 1000 AND velocity_last_hour > 5)
        OR (amount > 500 AND account_age_days < 7)
    )
    AND (
        fraud_score > 75
        OR ip_country != billing_country
        OR device_fingerprint IN (known_fraud_devices)
        OR email LIKE '%@temporary-email.%'
        OR billing_address LIKE '%P.O. Box%'
    )
");

// Account takeover detection
$rule = $srb->parse("
    failed_login_attempts >= 5
    AND (current_timestamp - last_failed_login) <= 300
    AND (
        ip_address != last_successful_login_ip
        OR device_fingerprint != last_successful_device
        OR geo_distance_km > 500
    )
    AND account_locked = false
");

// Suspicious withdrawal pattern
$rule = $srb->parse("
    transaction_type = 'withdrawal'
    AND (
        (amount >= 10000)
        OR (withdrawals_last_24h > 5 AND total_withdrawn_24h > 20000)
        OR (amount > average_monthly_withdrawal * 3)
    )
    AND (
        destination_account_age_days < 30
        OR destination_account NOT IN (trusted_accounts)
    )
");
```

#### Investment & Trading

```php
// Trading eligibility
$rule = $srb->parse("
    account.status = 'active'
    AND account.verified = true
    AND (account.margin_enabled = false OR margin_call_pending = false)
    AND day_trades_count < 3
    AND buying_power >= order.estimated_cost
    AND NOT security IN (restricted_securities)
");

// Risk tolerance alignment
$rule = $srb->parse("
    (
        (risk_profile = 'conservative' AND asset.risk_rating <= 3)
        OR (risk_profile = 'moderate' AND asset.risk_rating <= 6)
        OR (risk_profile = 'aggressive' AND asset.risk_rating <= 10)
    )
    AND asset.category IN (investor.allowed_categories)
    AND (concentration_limit IS NULL OR portfolio_pct < concentration_limit)
");
```

### Healthcare

#### Patient Eligibility

```php
// Appointment scheduling
$rule = $srb->parse("
    patient.age >= 18
    AND patient.status = 'active'
    AND insurance.status = 'active'
    AND insurance.expires_at > appointment.date
    AND (
        referral.required = false
        OR (referral.required = true AND referral.date IS NOT NULL AND referral.expires_at > appointment.date)
    )
    AND provider.accepting_patients = true
");

// Procedure authorization
$rule = $srb->parse("
    insurance.coverage_active = true
    AND insurance.coverage.includes_procedure = true
    AND (insurance.coverage_remaining - procedure.estimated_cost) >= 0
    AND (
        pre_authorization.required = false
        OR (pre_authorization.required = true AND pre_authorization.approved = true)
    )
    AND patient.conditions NOT LIKE '%contraindication%'
");

// Medication eligibility
$rule = $srb->parse("
    patient.age >= medication.min_age
    AND (medication.max_age IS NULL OR patient.age <= medication.max_age)
    AND NOT medication.id IN (patient.allergies)
    AND NOT medication.contraindications LIKE patient.conditions
    AND (
        medication.requires_specialist = false
        OR prescriber.specialty IN (medication.approved_specialties)
    )
");
```

#### Clinical Decision Support

```php
// High-risk patient identification
$rule = $srb->parse("
    (
        age >= 65
        OR conditions LIKE '%diabetes%'
        OR conditions LIKE '%heart disease%'
        OR conditions LIKE '%immunocompromised%'
    )
    AND (
        recent_hospitalization_days <= 30
        OR er_visits_last_year >= 3
        OR active_medications_count >= 10
    )
");

// Preventive care reminders
$rule = $srb->parse("
    (
        (age >= 50 AND last_colonoscopy_years >= 10)
        OR (age >= 40 AND gender = 'F' AND last_mammogram_years >= 2)
        OR (age >= 18 AND last_physical_exam_years >= 1)
        OR (diabetes = true AND last_a1c_test_months >= 3)
    )
    AND patient.opted_in_reminders = true
");
```

### Gaming

#### Achievement & Rewards

```php
// Achievement unlock criteria
$rule = $srb->parse("
    player.level >= 50
    AND player.total_playtime_hours >= 100
    AND stats.boss_defeats >= 20
    AND stats.rare_items_collected >= 5
    AND (
        (mode = 'solo' AND stats.solo_achievements >= 10)
        OR (mode = 'multiplayer' AND stats.multiplayer_wins >= 50 AND stats.team_participation_rate >= 0.8)
    )
    AND NOT achievement_id IN (player.unlocked_achievements)
");

// Daily reward eligibility
$rule = $srb->parse("
    last_daily_reward_claim < current_date
    AND consecutive_login_days >= 1
    AND account.status = 'active'
    AND NOT account.suspended = true
");

// Loot drop eligibility
$rule = $srb->parse("
    enemy.level BETWEEN player.level - 5 AND player.level + 5
    AND (
        (rarity = 'common' AND drop_chance >= random_roll)
        OR (rarity = 'rare' AND drop_chance * luck_modifier >= random_roll)
        OR (rarity = 'legendary' AND drop_chance * luck_modifier * event_multiplier >= random_roll)
    )
    AND player.inventory_slots_available > 0
");
```

#### Matchmaking

```php
// PvP matchmaking
$rule = $srb->parse("
    player.rating BETWEEN search_rating - 200 AND search_rating + 200
    AND player.region IN (allowed_regions)
    AND player.queue_time < 300
    AND player.latency < 100
    AND NOT player.id IN (blocked_players)
    AND player.status = 'online'
");

// Team balance
$rule = $srb->parse("
    team_a.avg_rating BETWEEN team_b.avg_rating - 100 AND team_b.avg_rating + 100
    AND team_a.size = team_b.size
    AND (team_a.avg_playtime - team_b.avg_playtime) BETWEEN -50 AND 50
");
```

#### In-Game Economy

```php
// Purchase validation
$rule = $srb->parse("
    player.currency >= item.price
    AND item.available = true
    AND player.level >= item.min_level
    AND (item.max_purchases IS NULL OR player.purchase_count < item.max_purchases)
    AND (
        item.season IS NULL
        OR item.season = current_season
    )
");

// Trading restrictions
$rule = $srb->parse("
    player.account_age_days >= 7
    AND player.email_verified = true
    AND player.trade_ban_expires IS NULL
    AND item.tradeable = true
    AND (item.trade_lock_expires IS NULL OR item.trade_lock_expires < current_timestamp)
");
```

### IoT & Monitoring

#### Device Management

```php
// Device health check
$rule = $srb->parse("
    device.status = 'online'
    AND device.last_heartbeat >= current_timestamp - 300
    AND device.battery_level > 10
    AND device.signal_strength >= -80
    AND device.firmware_version >= min_supported_version
");

// Firmware update eligibility
$rule = $srb->parse("
    device.status = 'online'
    AND device.battery_level >= 50
    AND device.current_firmware < latest_firmware
    AND device.update_pending = false
    AND (
        device.update_policy = 'automatic'
        OR (device.update_policy = 'scheduled' AND current_hour BETWEEN 2 AND 4)
    )
");
```

#### Alert Conditions

```php
// Temperature monitoring
$rule = $srb->parse("
    (
        sensor.temperature > 80
        OR sensor.temperature < 10
        OR (sensor.temperature - sensor.avg_temperature_24h) > 15
    )
    AND alert.cooldown_expired = true
    AND sensor.status = 'active'
    AND NOT sensor.maintenance_mode = true
");

// Industrial equipment monitoring
$rule = $srb->parse("
    (
        (metrics.vibration > threshold.vibration_max)
        OR (metrics.noise_level > threshold.noise_max)
        OR (metrics.temperature > threshold.temp_max)
        OR (metrics.pressure < threshold.pressure_min OR metrics.pressure > threshold.pressure_max)
    )
    AND equipment.running = true
    AND alert.last_sent < current_timestamp - 3600
");

// Network anomaly detection
$rule = $srb->parse("
    (
        bandwidth_usage_mbps > avg_bandwidth * 2
        OR packet_loss_pct > 5
        OR latency_ms > 200
        OR connection_drops_last_hour > 3
    )
    AND device.critical = true
    AND maintenance_window = false
");
```

---

## Best Practices Summary

1. **Use Familiar SQL Syntax**: Leverage SQL's 40+ years of refinement - your team already knows it
2. **Always Use Single Quotes for Strings**: `'value'` not `"value"`
3. **Use IS NULL/IS NOT NULL for NULL Checks**: Never use `= NULL`
4. **Prefer IN Over Multiple ORs**: More readable and efficient
5. **Use BETWEEN for Range Checks**: Clearer than `>= AND <=`
6. **Add Parentheses for Clarity**: Don't rely on precedence knowledge
7. **Pre-Compute Complex Values**: SQL WHERE DSL doesn't support inline arithmetic
8. **Flatten Deep Nesting**: For performance-critical paths
9. **Cache Compiled Rules**: Parse once, evaluate many times
10. **Order Conditions for Short-Circuiting**: Put likely-to-fail checks first in AND chains
11. **Match Case Exactly**: SQL WHERE is case-sensitive by default
12. **Test NULL Edge Cases**: NULL behavior can be surprising
13. **Use LIKE Wildcards Correctly**: `%` for any chars, `_` for single char
14. **Escape Single Quotes**: Use `''` (double single quote) for literal quotes
15. **Document Complex Rules**: Add comments explaining business logic

---

## See Also

- [ADR 002: SQL WHERE Style DSL](/Users/brian/Developer/packages/ruler/adr/002-sql-where-dsl.md)
- [Wirefilter DSL Cookbook](/Users/brian/Developer/packages/ruler/docs/cookbooks/wirefilter-dsl.md)
- [DSL Feature Support Matrix](dsl-feature-matrix.md)
