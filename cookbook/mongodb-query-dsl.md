# MongoDB Query DSL Cookbook

**Status:** Most Feature-Complete DSL (28 Custom Operators)
**Complexity:** Low (JSON-based, no parsing)
**Best For:** REST APIs, GraphQL, JSON-based filtering, frontend query builders

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Extended Comparisons](#extended-comparisons)
4. [Logical Operators](#logical-operators)
5. [List Membership](#list-membership)
6. [String Operations](#string-operations)
7. [Date Operations](#date-operations)
8. [Type Checking](#type-checking)
9. [Nested Properties](#nested-properties)
10. [Advanced Patterns](#advanced-patterns)
11. [Performance Optimization](#performance-optimization)
12. [Common Pitfalls](#common-pitfalls)
13. [Real-World Examples](#real-world-examples)

---

## Quick Start

### Installation

```bash
composer require cline/ruler
```

### Basic Usage

```php
use Cline\Ruler\DSL\MongoQuery\MongoQueryRuleBuilder;
use Cline\Ruler\Core\Context;

$mqb = new MongoQueryRuleBuilder();

// Parse from PHP array
$rule = $mqb->parse(['age' => ['$gte' => 18]]);

// Or parse from JSON string
$rule = $mqb->parse('{"age": {"$gte": 18}, "country": "US"}');

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

### Why MongoDB Query DSL?

**Most Feature-Complete DSL:**
- 28 custom operators extending standard MongoDB syntax
- ONLY DSL with native date operations ($after, $before, $betweenDates)
- Most comprehensive string operations (11 operators, 8 custom)
- Extended logical operators ($xor, $nand)
- Full type checking with multiple type operators
- Strict equality support ($same, $nsame)
- JSON-based - zero parsing complexity

---

## Basic Comparisons

### Standard MongoDB Operators

MongoDB Query DSL includes all standard comparison operators:

#### Equality ($eq)

```php
// Explicit equality
$rule = $mqb->parse(['status' => ['$eq' => 'active']]);
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'inactive'])); // false

// Implicit equality (shorthand)
$rule = $mqb->parse(['status' => 'active']);
$rule->evaluate(new Context(['status' => 'active'])); // true
```

#### Inequality ($ne)

```php
// Not equal to
$rule = $mqb->parse(['status' => ['$ne' => 'banned']]);
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false
```

#### Greater Than ($gt, $gte)

```php
// Greater than
$rule = $mqb->parse(['price' => ['$gt' => 100]]);
$rule->evaluate(new Context(['price' => 150])); // true
$rule->evaluate(new Context(['price' => 100])); // false

// Greater than or equal
$rule = $mqb->parse(['age' => ['$gte' => 18]]);
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 25])); // true
$rule->evaluate(new Context(['age' => 17])); // false
```

#### Less Than ($lt, $lte)

```php
// Less than
$rule = $mqb->parse(['quantity' => ['$lt' => 10]]);
$rule->evaluate(new Context(['quantity' => 5])); // true
$rule->evaluate(new Context(['quantity' => 10])); // false

// Less than or equal
$rule = $mqb->parse(['temperature' => ['$lte' => 32]]);
$rule->evaluate(new Context(['temperature' => 20])); // true
$rule->evaluate(new Context(['temperature' => 32])); // true
$rule->evaluate(new Context(['temperature' => 40])); // false
```

#### Range Queries (Multiple Operators)

```php
// Between (inclusive) - multiple operators on same field
$rule = $mqb->parse([
    'age' => [
        '$gte' => 18,
        '$lte' => 65
    ]
]);

$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 65])); // true
$rule->evaluate(new Context(['age' => 70])); // false
$rule->evaluate(new Context(['age' => 16])); // false

// Price range
$rule = $mqb->parse([
    'price' => [
        '$gt' => 10,
        '$lt' => 100
    ]
]);

$rule->evaluate(new Context(['price' => 50])); // true
$rule->evaluate(new Context(['price' => 10])); // false (not inclusive)
$rule->evaluate(new Context(['price' => 100])); // false (not inclusive)
```

---

## Extended Comparisons

**CUSTOM OPERATORS** - These extend standard MongoDB syntax and are unique to this DSL.

### Strict Equality ($same, $nsame)

Prevent type coercion with strict equality checks.

```php
// $same - Strict equality (===)
$rule = $mqb->parse(['age' => ['$same' => 18]]);
$rule->evaluate(new Context(['age' => 18]));   // true (int === int)
$rule->evaluate(new Context(['age' => "18"])); // false (string !== int)

// $nsame - Strict inequality (!==)
$rule = $mqb->parse(['verified' => ['$nsame' => false]]);
$rule->evaluate(new Context(['verified' => 0]));     // true (0 !== false)
$rule->evaluate(new Context(['verified' => null]));  // true (null !== false)
$rule->evaluate(new Context(['verified' => false])); // false (false === false)

// Compare: loose vs strict equality
$rule = $mqb->parse(['value' => ['$eq' => 1]]);
$rule->evaluate(new Context(['value' => 1]));     // true
$rule->evaluate(new Context(['value' => "1"]));   // true (type coercion)
$rule->evaluate(new Context(['value' => true])); // true (type coercion)

$rule = $mqb->parse(['value' => ['$same' => 1]]);
$rule->evaluate(new Context(['value' => 1]));     // true
$rule->evaluate(new Context(['value' => "1"]));   // false (no coercion)
$rule->evaluate(new Context(['value' => true])); // false (no coercion)
```

### Between ($between)

**CUSTOM OPERATOR** - Convenient range check with single operator.

```php
// Between (inclusive range)
$rule = $mqb->parse(['age' => ['$between' => [18, 65]]]);
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 65])); // true
$rule->evaluate(new Context(['age' => 70])); // false

// Price range
$rule = $mqb->parse(['price' => ['$between' => [10.0, 100.0]]]);
$rule->evaluate(new Context(['price' => 50.0])); // true
$rule->evaluate(new Context(['price' => 10.0])); // true (inclusive)
$rule->evaluate(new Context(['price' => 5.0]));  // false

// Score range
$rule = $mqb->parse(['score' => ['$between' => [70, 100]]]);
$rule->evaluate(new Context(['score' => 85])); // true
$rule->evaluate(new Context(['score' => 65])); // false

// Note: Array must have exactly 2 values [min, max]
// $rule = $mqb->parse(['age' => ['$between' => [18]]]); // Error!
// $rule = $mqb->parse(['age' => ['$between' => [18, 30, 65]]]); // Error!
```

---

## Logical Operators

### Standard MongoDB Logical Operators

#### AND ($and)

```php
// Explicit $and
$rule = $mqb->parse([
    '$and' => [
        ['age' => ['$gte' => 18]],
        ['country' => 'US'],
        ['status' => ['$ne' => 'banned']]
    ]
]);

$valid = new Context(['age' => 25, 'country' => 'US', 'status' => 'active']);
$rule->evaluate($valid); // true

$invalid = new Context(['age' => 25, 'country' => 'FR', 'status' => 'active']);
$rule->evaluate($invalid); // false (country fails)

// Implicit $and (multiple fields at top level)
$rule = $mqb->parse([
    'age' => ['$gte' => 18],
    'country' => 'US',
    'status' => ['$ne' => 'banned']
]);
// Equivalent to explicit $and above
```

#### OR ($or)

```php
// At least one condition must match
$rule = $mqb->parse([
    '$or' => [
        ['status' => 'active'],
        ['status' => 'pending'],
        ['vip' => true]
    ]
]);

$rule->evaluate(new Context(['status' => 'active', 'vip' => false])); // true
$rule->evaluate(new Context(['status' => 'pending', 'vip' => false])); // true
$rule->evaluate(new Context(['status' => 'deleted', 'vip' => true])); // true
$rule->evaluate(new Context(['status' => 'deleted', 'vip' => false])); // false

// OR with complex conditions
$rule = $mqb->parse([
    '$or' => [
        ['age' => ['$gte' => 18, '$lt' => 65]],
        ['vip' => true]
    ]
]);
```

#### NOT ($not)

```php
// Negate a condition
$rule = $mqb->parse([
    '$not' => ['status' => 'banned']
]);
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// NOT with complex conditions
$rule = $mqb->parse([
    '$not' => [
        '$or' => [
            ['status' => 'banned'],
            ['status' => 'deleted']
        ]
    ]
]);
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false
```

#### NOR ($nor)

```php
// None of the conditions can match
$rule = $mqb->parse([
    '$nor' => [
        ['status' => 'banned'],
        ['status' => 'deleted'],
        ['status' => 'suspended']
    ]
]);

$rule->evaluate(new Context(['status' => 'active'])); // true (not in list)
$rule->evaluate(new Context(['status' => 'banned'])); // false (in list)

// Equivalent to: NOT(status=banned OR status=deleted OR status=suspended)
```

### Custom Logical Operators

**CUSTOM OPERATORS** - Extended logical operators not in standard MongoDB.

#### XOR ($xor)

**CUSTOM OPERATOR** - Exactly one condition must be true (exclusive OR).

```php
// Exactly one must match
$rule = $mqb->parse([
    '$xor' => [
        ['has_coupon' => true],
        ['is_member' => true]
    ]
]);

$rule->evaluate(new Context(['has_coupon' => true, 'is_member' => false])); // true
$rule->evaluate(new Context(['has_coupon' => false, 'is_member' => true])); // true
$rule->evaluate(new Context(['has_coupon' => true, 'is_member' => true]));  // false (both)
$rule->evaluate(new Context(['has_coupon' => false, 'is_member' => false])); // false (neither)

// Shipping method: choose one and only one
$rule = $mqb->parse([
    '$xor' => [
        ['ship_to_home' => true],
        ['ship_to_store' => true],
        ['ship_to_locker' => true]
    ]
]);
```

#### NAND ($nand)

**CUSTOM OPERATOR** - Not all conditions can be true simultaneously.

```php
// Not all can be true (inverse of AND)
$rule = $mqb->parse([
    '$nand' => [
        ['high_risk' => true],
        ['large_amount' => true]
    ]
]);

$rule->evaluate(new Context(['high_risk' => true, 'large_amount' => false])); // true
$rule->evaluate(new Context(['high_risk' => false, 'large_amount' => true])); // true
$rule->evaluate(new Context(['high_risk' => false, 'large_amount' => false])); // true
$rule->evaluate(new Context(['high_risk' => true, 'large_amount' => true])); // false (both true)

// Fraud detection: reject if both conditions true
$rule = $mqb->parse([
    '$nand' => [
        ['new_customer' => true],
        ['high_value_order' => true]
    ]
]);
```

### Complex Nested Conditions

```php
// Combine multiple logical operators
$rule = $mqb->parse([
    '$and' => [
        [
            '$or' => [
                ['age' => ['$gte' => 18, '$lt' => 65]],
                ['vip' => true]
            ]
        ],
        ['country' => ['$in' => ['US', 'CA', 'UK']]],
        [
            '$not' => [
                'status' => ['$in' => ['banned', 'deleted']]
            ]
        ]
    ]
]);

// Nested XOR and AND
$rule = $mqb->parse([
    '$and' => [
        ['verified' => true],
        [
            '$xor' => [
                ['payment_method' => 'card'],
                ['payment_method' => 'paypal']
            ]
        ]
    ]
]);
```

---

## List Membership

### IN Operator ($in)

```php
// Check if value is in array
$rule = $mqb->parse(['country' => ['$in' => ['US', 'CA', 'UK']]]);
$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'CA'])); // true
$rule->evaluate(new Context(['country' => 'FR'])); // false

// Numeric values
$rule = $mqb->parse(['status_code' => ['$in' => [200, 201, 204]]]);
$rule->evaluate(new Context(['status_code' => 200])); // true
$rule->evaluate(new Context(['status_code' => 404])); // false

// Mixed types
$rule = $mqb->parse(['value' => ['$in' => [1, "two", true, null]]]);
$rule->evaluate(new Context(['value' => 1]));      // true
$rule->evaluate(new Context(['value' => "two"]));  // true
$rule->evaluate(new Context(['value' => true]));   // true
$rule->evaluate(new Context(['value' => null]));   // true
$rule->evaluate(new Context(['value' => false]));  // false

// Empty array
$rule = $mqb->parse(['tag' => ['$in' => []]]);
$rule->evaluate(new Context(['tag' => 'anything'])); // false (nothing matches empty array)
```

### NOT IN Operator ($nin)

```php
// Exclude values
$rule = $mqb->parse(['status' => ['$nin' => ['banned', 'suspended', 'deleted']]]);
$rule->evaluate(new Context(['status' => 'active']));    // true
$rule->evaluate(new Context(['status' => 'pending']));   // true
$rule->evaluate(new Context(['status' => 'banned']));    // false
$rule->evaluate(new Context(['status' => 'deleted']));   // false

// Exclude numeric ranges
$rule = $mqb->parse(['http_status' => ['$nin' => [400, 401, 403, 404, 500]]]);
$rule->evaluate(new Context(['http_status' => 200])); // true
$rule->evaluate(new Context(['http_status' => 404])); // false

// Block specific IPs
$rule = $mqb->parse(['ip_address' => ['$nin' => ['192.168.1.100', '10.0.0.50']]]);
$rule->evaluate(new Context(['ip_address' => '192.168.1.1']));   // true
$rule->evaluate(new Context(['ip_address' => '192.168.1.100'])); // false
```

---

## String Operations

**Most Comprehensive String Support** - 11 operators, 8 custom (not in standard MongoDB).

### Regular Expressions ($regex)

```php
// Basic regex
$rule = $mqb->parse([
    'email' => ['$regex' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$']
]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'invalid-email']));    // false

// Case-insensitive regex
$rule = $mqb->parse([
    'name' => [
        '$regex' => 'john',
        '$options' => 'i'
    ]
]);
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'JOHN'])); // true
$rule->evaluate(new Context(['name' => 'john'])); // true

// Multiline regex
$rule = $mqb->parse([
    'description' => [
        '$regex' => '^Important',
        '$options' => 'm'
    ]
]);

// Phone number validation (US format)
$rule = $mqb->parse([
    'phone' => ['$regex' => '^\\d{3}-\\d{3}-\\d{4}$']
]);
$rule->evaluate(new Context(['phone' => '555-123-4567'])); // true

// URL validation
$rule = $mqb->parse([
    'website' => ['$regex' => '^https?://[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}']
]);
$rule->evaluate(new Context(['website' => 'https://example.com'])); // true

// Postal code (Canada)
$rule = $mqb->parse([
    'postal_code' => [
        '$regex' => '^[A-Z]\\d[A-Z] \\d[A-Z]\\d$',
        '$options' => 'i'
    ]
]);
$rule->evaluate(new Context(['postal_code' => 'K1A 0B1'])); // true
```

### Inverse Regex ($notRegex)

**CUSTOM OPERATOR** - Match strings that DON'T match pattern.

```php
// Exclude certain patterns
$rule = $mqb->parse([
    'email' => ['$notRegex' => '@(spam|fake|test)\\.com$']
]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@spam.com']));    // false
$rule->evaluate(new Context(['email' => 'user@fake.com']));    // false

// Exclude special characters
$rule = $mqb->parse([
    'username' => ['$notRegex' => '[^a-zA-Z0-9_]']
]);
$rule->evaluate(new Context(['username' => 'john_doe123'])); // true
$rule->evaluate(new Context(['username' => 'john@doe']));    // false
```

### Contains ($contains, $containsi)

**CUSTOM OPERATORS** - Case-sensitive and case-insensitive substring matching.

```php
// Case-sensitive contains
$rule = $mqb->parse(['description' => ['$contains' => 'important']]);
$rule->evaluate(new Context(['description' => 'This is important stuff'])); // true
$rule->evaluate(new Context(['description' => 'This is IMPORTANT stuff'])); // false
$rule->evaluate(new Context(['description' => 'Nothing special']));         // false

// Case-insensitive contains
$rule = $mqb->parse(['description' => ['$containsi' => 'important']]);
$rule->evaluate(new Context(['description' => 'This is important stuff'])); // true
$rule->evaluate(new Context(['description' => 'This is IMPORTANT stuff'])); // true
$rule->evaluate(new Context(['description' => 'This is ImPoRtAnT stuff'])); // true

// Product search (case-insensitive)
$rule = $mqb->parse(['product_name' => ['$containsi' => 'laptop']]);
$rule->evaluate(new Context(['product_name' => 'Dell Laptop 15"'])); // true
$rule->evaluate(new Context(['product_name' => 'LAPTOP STAND']));    // true
```

### Not Contains ($notContains, $notContainsi)

**CUSTOM OPERATORS** - Exclude strings containing substring.

```php
// Case-sensitive not contains
$rule = $mqb->parse(['comment' => ['$notContains' => 'spam']]);
$rule->evaluate(new Context(['comment' => 'Great product!'])); // true
$rule->evaluate(new Context(['comment' => 'This is spam']));   // false

// Case-insensitive not contains
$rule = $mqb->parse(['message' => ['$notContainsi' => 'unsubscribe']]);
$rule->evaluate(new Context(['message' => 'Hello there']));        // true
$rule->evaluate(new Context(['message' => 'Click to UNSUBSCRIBE'])); // false
$rule->evaluate(new Context(['message' => 'unsubscribe here']));   // false

// Filter out profanity
$rule = $mqb->parse([
    '$and' => [
        ['comment' => ['$notContainsi' => 'badword1']],
        ['comment' => ['$notContainsi' => 'badword2']],
        ['comment' => ['$notContainsi' => 'badword3']]
    ]
]);
```

### Starts With ($startsWith, $startsWithi)

**CUSTOM OPERATORS** - Prefix matching.

```php
// Case-sensitive starts with
$rule = $mqb->parse(['name' => ['$startsWith' => 'John']]);
$rule->evaluate(new Context(['name' => 'John Doe']));  // true
$rule->evaluate(new Context(['name' => 'John Smith'])); // true
$rule->evaluate(new Context(['name' => 'jane Doe']));  // false
$rule->evaluate(new Context(['name' => 'JOHN Doe']));  // false

// Case-insensitive starts with
$rule = $mqb->parse(['name' => ['$startsWithi' => 'john']]);
$rule->evaluate(new Context(['name' => 'John Doe']));  // true
$rule->evaluate(new Context(['name' => 'JOHN Smith'])); // true
$rule->evaluate(new Context(['name' => 'jane Doe']));  // false

// URL protocol check
$rule = $mqb->parse(['url' => ['$startsWithi' => 'https://']]);
$rule->evaluate(new Context(['url' => 'https://example.com'])); // true
$rule->evaluate(new Context(['url' => 'HTTPS://EXAMPLE.COM'])); // true
$rule->evaluate(new Context(['url' => 'http://example.com']));  // false

// Phone number country code
$rule = $mqb->parse(['phone' => ['$startsWith' => '+1']]);
$rule->evaluate(new Context(['phone' => '+1-555-1234'])); // true
```

### Ends With ($endsWith, $endsWithi)

**CUSTOM OPERATORS** - Suffix matching.

```php
// Case-sensitive ends with
$rule = $mqb->parse(['email' => ['$endsWith' => '@example.com']]);
$rule->evaluate(new Context(['email' => 'user@example.com']));  // true
$rule->evaluate(new Context(['email' => 'admin@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com']));     // false
$rule->evaluate(new Context(['email' => 'user@EXAMPLE.COM']));  // false

// Case-insensitive ends with
$rule = $mqb->parse(['email' => ['$endsWithi' => '@example.com']]);
$rule->evaluate(new Context(['email' => 'user@EXAMPLE.COM'])); // true
$rule->evaluate(new Context(['email' => 'user@Example.Com'])); // true

// File extension check
$rule = $mqb->parse(['filename' => ['$endsWithi' => '.pdf']]);
$rule->evaluate(new Context(['filename' => 'document.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'REPORT.PDF']));   // true
$rule->evaluate(new Context(['filename' => 'image.jpg']));    // false

// Domain check
$rule = $mqb->parse(['website' => ['$endsWithi' => '.gov']]);
$rule->evaluate(new Context(['website' => 'whitehouse.gov'])); // true
```

### String Length ($strLength)

**CUSTOM OPERATOR** - Check string length with comparisons.

```php
// Exact length
$rule = $mqb->parse(['zip_code' => ['$strLength' => 5]]);
$rule->evaluate(new Context(['zip_code' => '12345'])); // true
$rule->evaluate(new Context(['zip_code' => '1234']));  // false
$rule->evaluate(new Context(['zip_code' => '123456'])); // false

// Minimum length
$rule = $mqb->parse(['password' => ['$strLength' => ['$gte' => 8]]]);
$rule->evaluate(new Context(['password' => 'secret123'])); // true (9 chars)
$rule->evaluate(new Context(['password' => 'short']));     // false (5 chars)

// Maximum length
$rule = $mqb->parse(['username' => ['$strLength' => ['$lte' => 20]]]);
$rule->evaluate(new Context(['username' => 'john_doe'])); // true (8 chars)
$rule->evaluate(new Context(['username' => 'very_long_username_here_12345'])); // false (29 chars)

// Range
$rule = $mqb->parse([
    'comment' => [
        '$strLength' => [
            '$gte' => 10,
            '$lte' => 500
        ]
    ]
]);
$rule->evaluate(new Context(['comment' => 'This is a valid comment'])); // true (24 chars)
$rule->evaluate(new Context(['comment' => 'Too short']));               // false (9 chars)

// Tweet length limit
$rule = $mqb->parse(['tweet' => ['$strLength' => ['$lte' => 280]]]);

// Product title validation
$rule = $mqb->parse([
    'product_title' => [
        '$strLength' => [
            '$gte' => 5,
            '$lte' => 100
        ]
    ]
]);
```

---

## Date Operations

**UNIQUE TO THIS DSL** - ONLY MongoDB Query DSL has native date operation support!

### After ($after)

**CUSTOM OPERATOR** - Check if date is after specified date.

```php
// Date after specific timestamp
$rule = $mqb->parse(['created_at' => ['$after' => '2024-01-01']]);
$rule->evaluate(new Context(['created_at' => '2024-06-15'])); // true
$rule->evaluate(new Context(['created_at' => '2023-12-31'])); // false

// Unix timestamp
$rule = $mqb->parse(['expires_at' => ['$after' => 1704067200]]);
$rule->evaluate(new Context(['expires_at' => 1735689600])); // true

// DateTime object
$rule = $mqb->parse(['updated_at' => ['$after' => new \DateTime('2024-01-01')]]);

// Recent activity check
$rule = $mqb->parse(['last_login' => ['$after' => '2024-12-01']]);
$rule->evaluate(new Context(['last_login' => '2024-12-15'])); // true

// Subscription active
$rule = $mqb->parse(['subscription_start' => ['$after' => '2024-01-01']]);
```

### Before ($before)

**CUSTOM OPERATOR** - Check if date is before specified date.

```php
// Date before specific date
$rule = $mqb->parse(['created_at' => ['$before' => '2024-12-31']]);
$rule->evaluate(new Context(['created_at' => '2024-06-15'])); // true
$rule->evaluate(new Context(['created_at' => '2025-01-01'])); // false

// Expiration check
$rule = $mqb->parse(['expires_at' => ['$before' => '2025-01-01']]);
$rule->evaluate(new Context(['expires_at' => '2024-12-31'])); // true

// Trial period check
$currentDate = new \DateTime('now');
$rule = $mqb->parse(['trial_ends' => ['$before' => $currentDate]]);

// Promotional deadline
$rule = $mqb->parse(['promotion_ends' => ['$before' => '2024-12-25']]);
```

### Between Dates ($betweenDates)

**CUSTOM OPERATOR** - Check if date falls within range.

```php
// Date range (inclusive)
$rule = $mqb->parse([
    'event_date' => ['$betweenDates' => ['2024-01-01', '2024-12-31']]
]);
$rule->evaluate(new Context(['event_date' => '2024-06-15'])); // true
$rule->evaluate(new Context(['event_date' => '2024-01-01'])); // true (inclusive)
$rule->evaluate(new Context(['event_date' => '2024-12-31'])); // true (inclusive)
$rule->evaluate(new Context(['event_date' => '2025-01-01'])); // false

// Quarter date range
$rule = $mqb->parse([
    'transaction_date' => ['$betweenDates' => ['2024-01-01', '2024-03-31']]
]);

// Active subscription period
$rule = $mqb->parse([
    'current_date' => [
        '$betweenDates' => ['2024-01-01', '2025-01-01']
    ]
]);

// Event registration window
$rule = $mqb->parse([
    'registration_date' => [
        '$betweenDates' => ['2024-10-01', '2024-10-31']
    ]
]);

// Birthday in current year
$currentYear = date('Y');
$rule = $mqb->parse([
    'birthday' => [
        '$betweenDates' => ["{$currentYear}-01-01", "{$currentYear}-12-31"]
    ]
]);

// Note: Array must have exactly 2 dates [start, end]
```

### Complex Date Queries

```php
// Active subscription: started before today, expires after today
$today = date('Y-m-d');
$rule = $mqb->parse([
    '$and' => [
        ['subscription_start' => ['$before' => $today]],
        ['subscription_end' => ['$after' => $today]]
    ]
]);

// Recent and not expired
$rule = $mqb->parse([
    '$and' => [
        ['created_at' => ['$after' => '2024-01-01']],
        ['expires_at' => ['$after' => date('Y-m-d')]]
    ]
]);

// Historical data range with exclusions
$rule = $mqb->parse([
    '$and' => [
        ['date' => ['$betweenDates' => ['2024-01-01', '2024-12-31']]],
        ['date' => ['$ne' => '2024-07-04']], // Exclude holiday
        ['date' => ['$ne' => '2024-12-25']]  // Exclude holiday
    ]
]);

// Trial expired
$rule = $mqb->parse([
    '$and' => [
        ['trial_start' => ['$before' => date('Y-m-d')]],
        ['trial_end' => ['$before' => date('Y-m-d')]],
        ['subscription_status' => 'trial']
    ]
]);
```

---

## Type Checking

**CUSTOM OPERATORS** - Comprehensive type validation (3 operators).

### Type Check ($type)

**CUSTOM OPERATOR** - Validate field type.

```php
// String type
$rule = $mqb->parse(['email' => ['$type' => 'string']]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 123]));                // false

// Number type (numeric, int, double all work)
$rule = $mqb->parse(['age' => ['$type' => 'number']]);
$rule->evaluate(new Context(['age' => 25]));     // true
$rule->evaluate(new Context(['age' => 25.5]));   // true
$rule->evaluate(new Context(['age' => "25"]));   // false

// Boolean type
$rule = $mqb->parse(['verified' => ['$type' => 'boolean']]);
$rule->evaluate(new Context(['verified' => true]));  // true
$rule->evaluate(new Context(['verified' => false])); // true
$rule->evaluate(new Context(['verified' => 1]));     // false

// Array type
$rule = $mqb->parse(['tags' => ['$type' => 'array']]);
$rule->evaluate(new Context(['tags' => ['a', 'b']])); // true
$rule->evaluate(new Context(['tags' => 'string']]);   // false

// Null type
$rule = $mqb->parse(['deleted_at' => ['$type' => 'null']]);
$rule->evaluate(new Context(['deleted_at' => null]));  // true
$rule->evaluate(new Context(['deleted_at' => '']));    // false
$rule->evaluate(new Context(['deleted_at' => false])); // false

// Supported types: string, number, numeric, int, double, boolean, bool, array, null
```

### Empty Check ($empty)

**CUSTOM OPERATOR** - Check if value is empty (null, empty array, empty string).

```php
// Check if empty
$rule = $mqb->parse(['notes' => ['$empty' => true]]);
$rule->evaluate(new Context(['notes' => '']));       // true (empty string)
$rule->evaluate(new Context(['notes' => null]));     // true (null)
$rule->evaluate(new Context(['notes' => []]));       // true (empty array)
$rule->evaluate(new Context(['notes' => 'text']));   // false
$rule->evaluate(new Context(['notes' => ['item']])); // false

// Check if not empty
$rule = $mqb->parse(['email' => ['$empty' => false]]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true (has value)
$rule->evaluate(new Context(['email' => '']));                 // false (empty)
$rule->evaluate(new Context(['email' => null]));               // false (null)

// Require non-empty comment
$rule = $mqb->parse([
    '$and' => [
        ['comment' => ['$type' => 'string']],
        ['comment' => ['$empty' => false]]
    ]
]);

// Optional field (empty or specific value)
$rule = $mqb->parse([
    '$or' => [
        ['middle_name' => ['$empty' => true]],
        ['middle_name' => ['$type' => 'string']]
    ]
]);
```

### Array Size ($size)

**CUSTOM OPERATOR** - Check array element count.

```php
// Exact size
$rule = $mqb->parse(['tags' => ['$size' => 3]]);
$rule->evaluate(new Context(['tags' => ['a', 'b', 'c']])); // true
$rule->evaluate(new Context(['tags' => ['a', 'b']]));      // false
$rule->evaluate(new Context(['tags' => [])]); // false

// Minimum size
$rule = $mqb->parse(['items' => ['$size' => ['$gte' => 1]]]);
$rule->evaluate(new Context(['items' => ['item1']]));           // true
$rule->evaluate(new Context(['items' => ['item1', 'item2']]));  // true
$rule->evaluate(new Context(['items' => []]));                  // false

// Maximum size
$rule = $mqb->parse(['selected_options' => ['$size' => ['$lte' => 5]]]);
$rule->evaluate(new Context(['selected_options' => ['a', 'b', 'c']])); // true (3 items)
$rule->evaluate(new Context(['selected_options' => ['a', 'b', 'c', 'd', 'e', 'f']])); // false (6 items)

// Range
$rule = $mqb->parse([
    'participants' => [
        '$size' => [
            '$gte' => 2,
            '$lte' => 10
        ]
    ]
]);
$rule->evaluate(new Context(['participants' => ['user1', 'user2', 'user3']])); // true

// Non-empty array
$rule = $mqb->parse(['cart_items' => ['$size' => ['$gt' => 0]]]);

// Validate selection count
$rule = $mqb->parse(['answers' => ['$size' => 4]]); // Quiz must have exactly 4 answers
```

### Exists ($exists)

Standard MongoDB operator for field presence checking.

```php
// Field must exist
$rule = $mqb->parse(['email' => ['$exists' => true]]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => null]));               // false (null = doesn't exist)
$rule->evaluate(new Context(['name' => 'John']));              // false (field missing)

// Field must not exist
$rule = $mqb->parse(['deleted_at' => ['$exists' => false]]);
$rule->evaluate(new Context(['created_at' => '2024-01-01'])); // true (deleted_at missing)
$rule->evaluate(new Context(['deleted_at' => null]));         // true (null = doesn't exist)
$rule->evaluate(new Context(['deleted_at' => '2024-01-01'])); // false (exists)

// Optional field with default
$rule = $mqb->parse([
    '$or' => [
        ['timezone' => ['$exists' => false]],
        ['timezone' => 'UTC']
    ]
]);
```

### Combined Type Validation

```php
// Validate email field
$rule = $mqb->parse([
    '$and' => [
        ['email' => ['$exists' => true]],
        ['email' => ['$type' => 'string']],
        ['email' => ['$empty' => false]],
        ['email' => ['$regex' => '^[^@]+@[^@]+\\.[^@]+$']]
    ]
]);

// Validate required array with elements
$rule = $mqb->parse([
    '$and' => [
        ['tags' => ['$type' => 'array']],
        ['tags' => ['$size' => ['$gte' => 1]]]
    ]
]);

// Validate optional numeric field
$rule = $mqb->parse([
    '$or' => [
        ['discount' => ['$exists' => false]],
        [
            '$and' => [
                ['discount' => ['$type' => 'number']],
                ['discount' => ['$gte' => 0]],
                ['discount' => ['$lte' => 100]]
            ]
        ]
    ]
]);
```

---

## Nested Properties

### Dot Notation

Access nested object properties using dot notation (standard MongoDB syntax).

```php
// Simple nested property
$rule = $mqb->parse(['user.age' => ['$gte' => 18]]);

$context = new Context([
    'user' => [
        'age' => 25,
        'name' => 'John'
    ]
]);
$rule->evaluate($context); // true

// Deep nesting
$rule = $mqb->parse(['order.shipping.address.country' => 'US']);

$context = new Context([
    'order' => [
        'shipping' => [
            'address' => [
                'country' => 'US',
                'city' => 'New York'
            ]
        ]
    ]
]);
$rule->evaluate($context); // true

// Profile validation
$rule = $mqb->parse([
    '$and' => [
        ['user.profile.email' => ['$regex' => '@example\\.com$']],
        ['user.profile.verified' => true],
        ['user.settings.notifications' => true]
    ]
]);
```

### Array Element Access

```php
// Access array by index
$rule = $mqb->parse(['items.0.price' => ['$gt' => 100]]);

$context = new Context([
    'items' => [
        ['name' => 'Item 1', 'price' => 150],
        ['name' => 'Item 2', 'price' => 50]
    ]
]);
$rule->evaluate($context); // true (first item price > 100)

// Nested array access
$rule = $mqb->parse(['users.0.orders.0.total' => ['$gte' => 1000]]);
```

### Complex Nested Conditions

```php
// E-commerce order validation
$rule = $mqb->parse([
    '$and' => [
        ['order.customer.age' => ['$gte' => 18]],
        ['order.customer.country' => ['$in' => ['US', 'CA']]],
        ['order.payment.method' => 'card'],
        ['order.payment.verified' => true],
        ['order.shipping.address.country' => 'US'],
        ['order.items.0.price' => ['$gt' => 0]]
    ]
]);

// User profile completeness
$rule = $mqb->parse([
    '$and' => [
        ['profile.personal.first_name' => ['$empty' => false]],
        ['profile.personal.last_name' => ['$empty' => false]],
        ['profile.contact.email' => ['$exists' => true]],
        ['profile.contact.phone' => ['$exists' => true]],
        ['profile.address.country' => ['$exists' => true]]
    ]
]);

// SaaS subscription check
$rule = $mqb->parse([
    '$and' => [
        ['account.subscription.tier' => ['$in' => ['pro', 'enterprise']]],
        ['account.subscription.status' => 'active'],
        ['account.subscription.expires_at' => ['$after' => date('Y-m-d')]],
        ['account.usage.api_calls' => ['$lt' => 10000]]
    ]
]);
```

---

## Advanced Patterns

### Multi-Tier Eligibility

```php
// VIP access with multiple tiers
$rule = $mqb->parse([
    '$or' => [
        // Tier 1: Admin access
        ['role' => 'admin'],
        // Tier 2: Premium subscribers
        [
            '$and' => [
                ['subscription.tier' => 'premium'],
                ['subscription.expires_at' => ['$after' => date('Y-m-d')]],
                ['account_age_days' => ['$gte' => 30]]
            ]
        ],
        // Tier 3: High-value customers
        [
            '$and' => [
                ['total_purchases' => ['$gte' => 1000]],
                ['account_status' => 'verified'],
                ['loyalty_points' => ['$gte' => 500]]
            ]
        ],
        // Tier 4: Beta testers
        [
            '$and' => [
                ['beta_tester' => true],
                ['beta_agreement_signed' => true],
                ['test_environment_access' => true]
            ]
        ]
    ]
]);
```

### Dynamic Pricing Rules

```php
// Volume discount tiers
$rule = $mqb->parse([
    '$or' => [
        // 30% discount for 100+ items
        [
            '$and' => [
                ['quantity' => ['$gte' => 100]],
                ['discount_pct' => 30]
            ]
        ],
        // 20% discount for 50-99 items
        [
            '$and' => [
                ['quantity' => ['$gte' => 50]],
                ['quantity' => ['$lt' => 100]],
                ['discount_pct' => 20]
            ]
        ],
        // 10% discount for 10-49 items
        [
            '$and' => [
                ['quantity' => ['$gte' => 10]],
                ['quantity' => ['$lt' => 50]],
                ['discount_pct' => 10]
            ]
        ],
        // No discount for < 10 items
        [
            '$and' => [
                ['quantity' => ['$lt' => 10]],
                ['discount_pct' => 0]
            ]
        ]
    ]
]);

// Geographic pricing
$rule = $mqb->parse([
    '$or' => [
        [
            '$and' => [
                ['country' => 'US'],
                ['base_price' => 99.99]
            ]
        ],
        [
            '$and' => [
                ['country' => ['$in' => ['CA', 'MX']]],
                ['base_price' => 89.99]
            ]
        ],
        [
            '$and' => [
                ['country' => ['$in' => ['UK', 'DE', 'FR']]],
                ['base_price' => 79.99]
            ]
        ]
    ]
]);
```

### Fraud Detection

```php
// High-risk transaction detection
$rule = $mqb->parse([
    '$and' => [
        // Transaction characteristics
        [
            '$or' => [
                ['amount' => ['$gt' => 5000]],
                ['velocity_1h' => ['$gt' => 5]],
                ['unusual_time' => true]
            ]
        ],
        // Risk factors
        [
            '$or' => [
                ['ip_country' => ['$nsame' => 'billing_country']], // Strict inequality
                ['device_fingerprint' => ['$in' => ['known_fraud_1', 'known_fraud_2']]],
                ['email' => ['$regex' => '(temp|disposable|fake)']],
                ['card_bin' => ['$in' => ['123456', '654321']]]
            ]
        ],
        // Exclude trusted users
        [
            '$not' => [
                '$or' => [
                    ['verified_customer' => true],
                    ['whitelist' => true],
                    ['manual_approval' => true]
                ]
            ]
        ]
    ]
]);

// Velocity checking
$rule = $mqb->parse([
    '$or' => [
        ['transactions_last_hour' => ['$gt' => 10]],
        ['transactions_last_day' => ['$gt' => 50]],
        [
            '$and' => [
                ['transactions_last_hour' => ['$gte' => 3]],
                ['total_amount_last_hour' => ['$gt' => 10000]]
            ]
        ]
    ]
]);
```

### Content Filtering

```php
// Content moderation
$rule = $mqb->parse([
    '$or' => [
        // Spam indicators
        [
            '$and' => [
                ['content' => ['$containsi' => 'buy now']],
                ['content' => ['$containsi' => 'click here']],
                ['external_links_count' => ['$gt' => 3]]
            ]
        ],
        // Excessive caps
        [
            '$and' => [
                ['caps_ratio' => ['$gt' => 0.5]],
                ['length' => ['$gte' => 20]]
            ]
        ],
        // Profanity filter
        [
            '$or' => [
                ['content' => ['$containsi' => 'badword1']],
                ['content' => ['$containsi' => 'badword2']],
                ['content' => ['$containsi' => 'badword3']]
            ]
        ],
        // Repeated content
        [
            '$and' => [
                ['similarity_score' => ['$gt' => 0.9]],
                ['same_author' => false]
            ]
        ]
    ]
]);

// Age-appropriate content
$rule = $mqb->parse([
    '$and' => [
        ['rating' => ['$in' => ['G', 'PG', 'PG-13']]],
        ['violence_level' => ['$lte' => 2]],
        ['language_level' => ['$lte' => 1]],
        [
            '$not' => [
                'tags' => ['$in' => ['adult', 'mature', 'explicit']]
            ]
        ]
    ]
]);
```

### A/B Testing & Feature Flags

```php
// Feature rollout by percentage
$rule = $mqb->parse([
    '$or' => [
        // Always enabled for staff
        ['is_staff' => true],
        // Enabled for beta testers
        [
            '$and' => [
                ['beta_program' => true],
                ['feature_opt_in' => true]
            ]
        ],
        // Gradual rollout based on user ID
        [
            '$and' => [
                ['user_id_mod_100' => ['$lt' => 20]], // 20% of users
                ['account_created' => ['$before' => '2024-01-01']] // Established users only
            ]
        ]
    ]
]);

// A/B test assignment
$rule = $mqb->parse([
    '$and' => [
        // Eligible users
        ['country' => ['$in' => ['US', 'CA', 'UK']]],
        ['age' => ['$between' => [18, 65]]],
        ['active_last_30_days' => true],
        // Test assignment (50/50 split)
        [
            '$xor' => [ // Exactly one group
                ['user_id_mod_2' => 0], // Group A
                ['user_id_mod_2' => 1]  // Group B
            ]
        ]
    ]
]);
```

### Time-Based Access Control

```php
// Business hours access
$rule = $mqb->parse([
    '$and' => [
        ['current_hour' => ['$between' => [9, 17]]], // 9 AM - 5 PM
        ['current_day' => ['$in' => [1, 2, 3, 4, 5]]], // Monday-Friday
        [
            '$not' => [
                'current_date' => ['$in' => ['2024-07-04', '2024-12-25']] // Holidays
            ]
        ]
    ]
]);

// Scheduled maintenance window
$rule = $mqb->parse([
    '$or' => [
        // Normal access (outside maintenance)
        [
            '$not' => [
                '$and' => [
                    ['current_day' => 0], // Sunday
                    ['current_hour' => ['$between' => [2, 4]]] // 2-4 AM
                ]
            ]
        ],
        // Admin override
        ['role' => 'admin']
    ]
]);

// Limited-time promotion
$rule = $mqb->parse([
    '$and' => [
        ['promo_code' => 'SUMMER24'],
        ['order_date' => ['$betweenDates' => ['2024-06-01', '2024-08-31']]],
        ['total_amount' => ['$gte' => 50]]
    ]
]);
```

---

## Performance Optimization

### Query Simplification

```php
// ❌ Bad - redundant conditions
$rule = $mqb->parse([
    '$and' => [
        ['status' => 'active'],
        ['status' => ['$ne' => 'inactive']] // Redundant!
    ]
]);

// ✅ Good - single condition
$rule = $mqb->parse(['status' => 'active']);

// ❌ Bad - complex OR chain
$rule = $mqb->parse([
    '$or' => [
        ['country' => 'US'],
        ['country' => 'CA'],
        ['country' => 'UK'],
        ['country' => 'AU']
    ]
]);

// ✅ Good - use $in
$rule = $mqb->parse(['country' => ['$in' => ['US', 'CA', 'UK', 'AU']]]);
```

### Short-Circuit Evaluation

```php
// ✅ Good - put most restrictive/cheapest checks first
$rule = $mqb->parse([
    '$and' => [
        ['status' => 'active'],              // Cheapest check first
        ['country' => 'US'],                 // Simple equality
        ['age' => ['$gte' => 18]],          // Simple comparison
        ['email' => ['$regex' => '...']]    // Expensive regex last
    ]
]);

// ❌ Bad - expensive checks first
$rule = $mqb->parse([
    '$and' => [
        ['description' => ['$regex' => 'complex.*pattern.*here']], // Expensive!
        ['status' => 'active'] // Should be first
    ]
]);

// ✅ Good - fail fast on OR
$rule = $mqb->parse([
    '$or' => [
        ['is_admin' => true],        // Quick win
        ['is_moderator' => true],    // Quick win
        ['has_permission' => 'x']    // More expensive check last
    ]
]);
```

### Index-Friendly Queries

```php
// ✅ Good - equality checks are index-friendly
$rule = $mqb->parse([
    '$and' => [
        ['user_id' => 12345],        // Indexed field
        ['status' => 'active'],      // Indexed field
        ['created_at' => ['$after' => '2024-01-01']]
    ]
]);

// ⚠️ Caution - regex can be slow without indexes
$rule = $mqb->parse(['email' => ['$regex' => '.*@example\\.com$']]);

// ✅ Better - use string operators when possible
$rule = $mqb->parse(['email' => ['$endsWithi' => '@example.com']]);
```

### Pre-Compute Values

```php
// ❌ Bad - if you need computed values
$context = new Context([
    'price' => 100,
    'tax_rate' => 0.08,
    'shipping' => 15
    // Total needs to be computed...
]);

// ✅ Good - pre-compute in context
$price = 100;
$taxRate = 0.08;
$shipping = 15;
$total = $price + ($price * $taxRate) + $shipping;

$context = new Context([
    'price' => $price,
    'tax_rate' => $taxRate,
    'shipping' => $shipping,
    'total' => $total  // Pre-computed
]);

$rule = $mqb->parse(['total' => ['$between' => [100, 200]]]);
$rule->evaluate($context); // Fast!
```

### Caching Compiled Rules

```php
// Compile once, use many times
$ruleCache = [];

function getCompiledRule(string $key, array $query, MongoQueryRuleBuilder $mqb): Rule
{
    global $ruleCache;

    if (!isset($ruleCache[$key])) {
        $ruleCache[$key] = $mqb->parse($query); // Expensive
    }

    return $ruleCache[$key]; // Cached
}

// Usage
$mqb = new MongoQueryRuleBuilder();
$rule = getCompiledRule('eligibility', ['age' => ['$gte' => 18]], $mqb);

// Evaluate multiple times
$rule->evaluate($context1);
$rule->evaluate($context2);
$rule->evaluate($context3);
```

---

## Common Pitfalls

### JSON Syntax Errors

```php
// ❌ Wrong - invalid JSON
$query = "{'age': {'$gte': 18}}"; // Single quotes not valid JSON
$rule = $mqb->parse($query); // JsonException!

// ✅ Correct - valid JSON
$query = '{"age": {"$gte": 18}}'; // Double quotes

// ✅ Better - use PHP arrays (no JSON parsing needed)
$query = ['age' => ['$gte' => 18]];
$rule = $mqb->parse($query);
```

### Operator Typos

```php
// ❌ Wrong - typo in operator name
$rule = $mqb->parse(['age' => ['$greaterthan' => 18]]); // Error!

// ✅ Correct
$rule = $mqb->parse(['age' => ['$gt' => 18]]);
$rule = $mqb->parse(['age' => ['$gte' => 18]]);

// ❌ Wrong - missing $
$rule = $mqb->parse(['age' => ['gte' => 18]]); // Treated as equality!

// ✅ Correct - operators need $
$rule = $mqb->parse(['age' => ['$gte' => 18]]);
```

### Type Mismatches

```php
// ❌ Wrong - comparing number to string
$rule = $mqb->parse(['age' => ['$gte' => "18"]]); // String "18"
$rule->evaluate(new Context(['age' => 25])); // May not work as expected

// ✅ Correct - use proper types
$rule = $mqb->parse(['age' => ['$gte' => 18]]); // Number 18

// ❌ Wrong - boolean as string
$rule = $mqb->parse(['verified' => "true"]); // String "true"
$rule->evaluate(new Context(['verified' => true])); // false!

// ✅ Correct - use actual boolean
$rule = $mqb->parse(['verified' => true]); // Boolean true
```

### Array Structure Errors

```php
// ❌ Wrong - $between with wrong number of elements
$rule = $mqb->parse(['age' => ['$between' => [18]]]); // Error: needs 2 values
$rule = $mqb->parse(['age' => ['$between' => [18, 30, 65]]]); // Error: too many

// ✅ Correct
$rule = $mqb->parse(['age' => ['$between' => [18, 65]]]); // Exactly 2

// ❌ Wrong - $in without array
$rule = $mqb->parse(['country' => ['$in' => 'US']]); // Error: needs array

// ✅ Correct
$rule = $mqb->parse(['country' => ['$in' => ['US']]]); // Array
```

### Implicit Equality Confusion

```php
// ❌ Confusing - empty object treated as equality
$rule = $mqb->parse(['tags' => []]);
$rule->evaluate(new Context(['tags' => []])); // true (tags === [])
$rule->evaluate(new Context(['tags' => ['a']])); // false

// ✅ Clear - explicit empty check
$rule = $mqb->parse(['tags' => ['$empty' => true]]);

// ❌ Confusing - nested object treated as equality
$rule = $mqb->parse(['user' => ['name' => 'John']]);
// Expects: user === {name: "John"}

// ✅ Clear - use dot notation
$rule = $mqb->parse(['user.name' => 'John']);
```

### Regex Escaping

```php
// ❌ Wrong - not escaping special characters
$rule = $mqb->parse(['email' => ['$regex' => '.+@example.com']]); // . matches any char

// ✅ Correct - escape dots
$rule = $mqb->parse(['email' => ['$regex' => '.+@example\\.com']]);

// ❌ Wrong - not escaping in JSON strings
$json = '{"email": {"$regex": ".+@example.com"}}'; // . still matches any

// ✅ Correct - double escape in JSON
$json = '{"email": {"$regex": ".+@example\\\\.com"}}'; // \\\\ becomes \\

// ✅ Better - use PHP arrays (single escape)
$rule = $mqb->parse(['email' => ['$regex' => '.+@example\\.com']]);
```

### Nested Field Access

```php
// ❌ Wrong - trying to use nested arrays as operators
$rule = $mqb->parse([
    'user' => [
        'age' => ['$gte' => 18]
    ]
]);
// This checks: user === {age: {$gte: 18}}

// ✅ Correct - use dot notation
$rule = $mqb->parse(['user.age' => ['$gte' => 18]]);

// ❌ Wrong - missing index
$rule = $mqb->parse(['items.price' => ['$gt' => 100]]);
// Expects: items.price field (not array element)

// ✅ Correct - specify index for arrays
$rule = $mqb->parse(['items.0.price' => ['$gt' => 100]]);
```

### Logical Operator Mistakes

```php
// ❌ Wrong - $and at field level
$rule = $mqb->parse([
    'age' => [
        '$and' => [
            ['$gte' => 18],
            ['$lte' => 65]
        ]
    ]
]);

// ✅ Correct - multiple operators on same field
$rule = $mqb->parse([
    'age' => [
        '$gte' => 18,
        '$lte' => 65
    ]
]);

// ❌ Wrong - trying to OR operators on same field
$rule = $mqb->parse([
    'status' => [
        '$or' => ['active', 'pending'] // Error!
    ]
]);

// ✅ Correct - use $in for OR on same field
$rule = $mqb->parse(['status' => ['$in' => ['active', 'pending']]]);

// ✅ Or use explicit $or at top level
$rule = $mqb->parse([
    '$or' => [
        ['status' => 'active'],
        ['status' => 'pending']
    ]
]);
```

---

## Real-World Examples

### E-Commerce

#### Product Catalog Filtering

```php
// Advanced product search
$rule = $mqb->parse([
    '$and' => [
        // Category
        ['category' => ['$in' => ['electronics', 'computers']]],

        // Price range
        ['price' => ['$between' => [100, 1000]]],

        // In stock
        ['inventory.quantity' => ['$gt' => 0]],

        // Ratings
        [
            '$or' => [
                ['rating.average' => ['$gte' => 4.0]],
                ['review_count' => ['$gte' => 100]]
            ]
        ],

        // Brand
        ['brand' => ['$nin' => ['BrandX', 'BrandY']]],

        // Features
        [
            '$or' => [
                ['features' => ['$containsi' => 'wireless']],
                ['features' => ['$containsi' => 'bluetooth']]
            ]
        ],

        // Exclude discontinued
        [
            '$not' => [
                'status' => ['$in' => ['discontinued', 'out-of-stock']]
            ]
        ]
    ]
]);

// Flash sale eligibility
$rule = $mqb->parse([
    '$and' => [
        // Sale period
        ['current_time' => ['$betweenDates' => ['2024-12-01', '2024-12-31']]],

        // Eligible products
        ['flash_sale_eligible' => true],
        ['inventory.quantity' => ['$gte' => 10]],

        // Price threshold
        ['original_price' => ['$gte' => 50]],

        // Not already on sale
        ['current_discount' => 0]
    ]
]);

// Bundle recommendations
$rule = $mqb->parse([
    '$and' => [
        // Product attributes
        ['category' => 'accessories'],
        ['compatible_with' => ['$contains' => 'laptop']],

        // Pricing
        ['price' => ['$lte' => 100]],

        // Popularity
        [
            '$or' => [
                ['bestseller_rank' => ['$lte' => 50]],
                ['rating.average' => ['$gte' => 4.5]]
            ]
        ]
    ]
]);
```

#### Shipping & Fulfillment

```php
// Shipping eligibility
$rule = $mqb->parse([
    '$and' => [
        // Weight limits
        ['total_weight' => ['$lte' => 50]],

        // Destination
        [
            '$or' => [
                [
                    '$and' => [
                        ['country' => 'US'],
                        ['state' => ['$nin' => ['AK', 'HI']]] // Exclude Alaska/Hawaii
                    ]
                ],
                ['country' => ['$in' => ['CA', 'MX']]]
            ]
        ],

        // Shipping method available
        ['hazmat' => false],
        ['oversized' => false],

        // Value limits
        ['total_value' => ['$lte' => 5000]]
    ]
]);

// Same-day delivery
$rule = $mqb->parse([
    '$and' => [
        // Order time
        ['order_hour' => ['$lt' => 14]], // Before 2 PM
        ['order_day' => ['$in' => [1, 2, 3, 4, 5]]], // Weekdays

        // Location
        ['shipping.zip_code' => ['$startsWith' => '900']], // Local area

        // Inventory
        ['warehouse_stock' => ['$gt' => 0]],
        ['warehouse_distance' => ['$lte' => 25]], // Within 25 miles

        // Eligibility
        ['express_shipping_available' => true],
        ['total_value' => ['$gte' => 35]] // Minimum order
    ]
]);
```

### User Access Control

#### Content Access Permissions

```php
// Premium content access
$rule = $mqb->parse([
    '$or' => [
        // Paid subscription
        [
            '$and' => [
                ['subscription.tier' => ['$in' => ['premium', 'enterprise']]],
                ['subscription.status' => 'active'],
                ['subscription.expires_at' => ['$after' => date('Y-m-d')]],
                ['payment.last_failed' => ['$empty' => true]]
            ]
        ],

        // Active trial
        [
            '$and' => [
                ['trial.active' => true],
                ['trial.expires_at' => ['$after' => date('Y-m-d')]],
                ['trial.features' => ['$containsi' => 'premium_content']]
            ]
        ],

        // Promotional access
        [
            '$and' => [
                ['promo_code' => ['$exists' => true]],
                ['promo_code' => ['$notEmpty' => true]],
                ['promo.expires_at' => ['$after' => date('Y-m-d')]]
            ]
        ],

        // Staff override
        ['role' => ['$in' => ['admin', 'editor', 'content_manager']]]
    ]
]);

// Comment moderation bypass
$rule = $mqb->parse([
    '$or' => [
        // Trusted users
        [
            '$and' => [
                ['reputation_score' => ['$gte' => 100]],
                ['account_age_days' => ['$gte' => 90]],
                ['violations_count' => 0]
            ]
        ],

        // Verified users
        [
            '$and' => [
                ['email_verified' => true],
                ['phone_verified' => true],
                ['identity_verified' => true]
            ]
        ],

        // Moderators
        ['role' => ['$in' => ['moderator', 'admin']]]
    ]
]);
```

#### Rate Limiting

```php
// API rate limit check
$rule = $mqb->parse([
    '$or' => [
        // Free tier
        [
            '$and' => [
                ['plan' => 'free'],
                ['requests_per_hour' => ['$lte' => 100]],
                ['requests_per_day' => ['$lte' => 1000]]
            ]
        ],

        // Pro tier
        [
            '$and' => [
                ['plan' => 'pro'],
                ['requests_per_hour' => ['$lte' => 5000]],
                ['requests_per_day' => ['$lte' => 100000]]
            ]
        ],

        // Enterprise (unlimited)
        [
            '$and' => [
                ['plan' => 'enterprise'],
                ['custom_rate_limit' => ['$exists' => false]]
            ]
        ]
    ]
]);

// Upload throttling
$rule = $mqb->parse([
    '$and' => [
        // Size limits by tier
        [
            '$or' => [
                [
                    '$and' => [
                        ['account.tier' => 'free'],
                        ['file_size_mb' => ['$lte' => 10]]
                    ]
                ],
                [
                    '$and' => [
                        ['account.tier' => 'pro'],
                        ['file_size_mb' => ['$lte' => 100]]
                    ]
                ],
                ['account.tier' => 'enterprise']
            ]
        ],

        // Usage limits
        ['storage_used_pct' => ['$lt' => 95]],
        ['uploads_today' => ['$lte' => 100]]
    ]
]);
```

### Financial Services

#### Loan Approval

```php
// Loan pre-qualification
$rule = $mqb->parse([
    '$and' => [
        // Age requirements
        ['applicant.age' => ['$between' => [18, 75]]],

        // Income requirements
        ['applicant.annual_income' => ['$gte' => 30000]],
        ['applicant.employment_months' => ['$gte' => 6]],

        // Credit requirements
        [
            '$or' => [
                ['applicant.credit_score' => ['$gte' => 650]],
                [
                    '$and' => [
                        ['applicant.credit_score' => ['$gte' => 580]],
                        ['co_signer.exists' => true],
                        ['co_signer.credit_score' => ['$gte' => 700]]
                    ]
                ]
            ]
        ],

        // Debt-to-income ratio
        ['applicant.debt_to_income_ratio' => ['$lte' => 0.43]],

        // Loan amount
        ['loan.amount' => ['$lte' => 'applicant.annual_income_times_3']],

        // Exclusions
        [
            '$not' => [
                '$or' => [
                    [
                        '$and' => [
                            ['applicant.bankruptcy_history' => true],
                            ['applicant.years_since_bankruptcy' => ['$lt' => 7]]
                        ]
                    ],
                    ['applicant.active_collections' => true],
                    ['applicant.current_default' => true]
                ]
            ]
        ]
    ]
]);

// Credit card approval
$rule = $mqb->parse([
    '$and' => [
        // Basic eligibility
        ['age' => ['$gte' => 18]],
        ['annual_income' => ['$gte' => 15000]],

        // Credit tier matching
        [
            '$or' => [
                // Excellent credit
                [
                    '$and' => [
                        ['credit_score' => ['$gte' => 750]],
                        ['card_type' => 'premium'],
                        ['credit_limit' => ['$between' => [10000, 50000]]]
                    ]
                ],
                // Good credit
                [
                    '$and' => [
                        ['credit_score' => ['$between' => [670, 749]]],
                        ['card_type' => 'standard'],
                        ['credit_limit' => ['$between' => [3000, 15000]]]
                    ]
                ],
                // Fair credit (secured)
                [
                    '$and' => [
                        ['credit_score' => ['$between' => [580, 669]]],
                        ['card_type' => 'secured'],
                        ['security_deposit' => ['$gte' => 500]]
                    ]
                ]
            ]
        ]
    ]
]);
```

#### Fraud Prevention

```php
// Transaction fraud detection
$rule = $mqb->parse([
    '$and' => [
        // Risk indicators
        [
            '$or' => [
                // High-value unusual transaction
                [
                    '$and' => [
                        ['amount' => ['$gt' => 5000]],
                        ['amount' => ['$gt' => 'average_transaction_times_10']]
                    ]
                ],

                // Velocity abuse
                [
                    '$or' => [
                        ['transactions_last_hour' => ['$gt' => 10]],
                        ['amount_last_hour' => ['$gt' => 10000]],
                        ['unique_merchants_last_hour' => ['$gt' => 5]]
                    ]
                ],

                // Geographic anomaly
                [
                    '$and' => [
                        ['ip_country' => ['$nsame' => 'card_country']],
                        ['hours_since_last_transaction' => ['$lt' => 4]]
                    ]
                ],

                // Device fingerprint mismatch
                ['device_fingerprint' => ['$nin' => 'known_devices']],

                // Suspicious patterns
                [
                    '$or' => [
                        ['merchant_category' => ['$in' => ['high_risk_1', 'high_risk_2']]],
                        ['card_not_present' => true],
                        ['billing_shipping_mismatch' => true]
                    ]
                ]
            ]
        ],

        // Exclude trusted scenarios
        [
            '$not' => [
                '$or' => [
                    ['customer_verification_passed' => true],
                    ['transaction_whitelisted' => true],
                    ['merchant_trusted' => true]
                ]
            ]
        ]
    ]
]);
```

### Healthcare

#### Patient Eligibility

```php
// Treatment eligibility
$rule = $mqb->parse([
    '$and' => [
        // Age requirements
        ['patient.age' => ['$between' => [18, 80]]],

        // Insurance
        [
            '$and' => [
                ['insurance.active' => true],
                ['insurance.coverage_type' => ['$in' => ['PPO', 'HMO', 'Medicare']]],
                ['insurance.coverage_amount_remaining' => ['$gte' => 'procedure.estimated_cost']]
            ]
        ],

        // Medical requirements
        [
            '$or' => [
                // No referral needed
                ['procedure.requires_referral' => false],

                // Valid referral exists
                [
                    '$and' => [
                        ['referral.exists' => true],
                        ['referral.date' => ['$after' => date('Y-m-d', strtotime('-90 days'))]],
                        ['referral.specialist_type' => 'procedure.specialist_required']
                    ]
                ]
            ]
        ],

        // Exclusions
        [
            '$not' => [
                '$or' => [
                    ['patient.contraindications' => ['$containsi' => 'procedure.name']],
                    ['patient.allergies' => ['$containsi' => 'procedure.medication']],
                    ['patient.conditions' => ['$in' => 'procedure.excluded_conditions']]
                ]
            ]
        ]
    ]
]);

// Clinical trial enrollment
$rule = $mqb->parse([
    '$and' => [
        // Demographics
        ['age' => ['$between' => [25, 65]]],
        ['gender' => ['$in' => ['M', 'F']]],

        // Medical history
        ['diagnosis' => 'trial.target_condition'],
        ['diagnosis_date' => ['$betweenDates' => ['2023-01-01', '2024-01-01']]],

        // Inclusion criteria
        ['severity_score' => ['$between' => [3, 7]]],
        ['previous_treatment' => ['$empty' => false]],
        ['previous_treatment_success' => false],

        // Exclusions
        [
            '$not' => [
                '$or' => [
                    ['pregnant' => true],
                    ['breastfeeding' => true],
                    ['immunocompromised' => true],
                    ['current_medications' => ['$containsi' => 'trial.excluded_medications']],
                    ['allergies' => ['$containsi' => 'trial.study_drug']]
                ]
            ]
        ],

        // Consent
        ['informed_consent_signed' => true],
        ['consent_date' => ['$after' => date('Y-m-d', strtotime('-30 days'))]]
    ]
]);
```

### SaaS Applications

#### Feature Access Control

```php
// API endpoint authorization
$rule = $mqb->parse([
    '$and' => [
        // Account status
        ['account.status' => 'active'],
        ['account.payment_current' => true],

        // Plan features
        [
            '$or' => [
                // Enterprise plan
                [
                    '$and' => [
                        ['account.plan' => 'enterprise'],
                        ['feature.enterprise_feature' => true]
                    ]
                ],

                // Business plan
                [
                    '$and' => [
                        ['account.plan' => ['$in' => ['business', 'enterprise']]],
                        ['feature.business_feature' => true]
                    ]
                ],

                // All plans
                ['feature.available_to_all' => true]
            ]
        ],

        // Usage limits
        [
            '$or' => [
                ['feature.usage_limited' => false],
                [
                    '$and' => [
                        ['usage.monthly_count' => ['$lt' => 'account.plan_limit']],
                        ['usage.rate_limit_ok' => true]
                    ]
                ]
            ]
        ]
    ]
]);

// Workspace collaboration limits
$rule = $mqb->parse([
    '$and' => [
        // Plan-based limits
        [
            '$or' => [
                [
                    '$and' => [
                        ['workspace.plan' => 'free'],
                        ['workspace.member_count' => ['$lte' => 5]],
                        ['workspace.project_count' => ['$lte' => 3]]
                    ]
                ],
                [
                    '$and' => [
                        ['workspace.plan' => 'team'],
                        ['workspace.member_count' => ['$lte' => 50]],
                        ['workspace.project_count' => ['$lte' => 50]]
                    ]
                ],
                ['workspace.plan' => 'enterprise'] // Unlimited
            ]
        ],

        // Storage limits
        ['workspace.storage_used_gb' => ['$lt' => 'workspace.storage_limit_gb']],

        // Active status
        ['workspace.status' => 'active'],
        ['workspace.suspended' => false]
    ]
]);
```

### Gaming

#### Achievement Unlocks

```php
// Legendary achievement
$rule = $mqb->parse([
    '$and' => [
        // Level requirements
        ['player.level' => ['$gte' => 100]],

        // Time investment
        ['stats.total_playtime_hours' => ['$gte' => 500]],

        // Combat stats
        ['stats.boss_defeats' => ['$gte' => 50]],
        ['stats.pvp_wins' => ['$gte' => 100]],
        ['stats.death_count' => ['$lte' => 10]],

        // Collection
        ['stats.rare_items_collected' => ['$gte' => 20]],
        ['stats.legendary_items_collected' => ['$gte' => 5]],

        // Social
        [
            '$or' => [
                ['stats.guild_rank' => ['$lte' => 10]],
                ['stats.friends_count' => ['$gte' => 50]]
            ]
        ],

        // Special conditions
        ['stats.secret_areas_found' => ['$gte' => 15]],
        ['stats.easter_eggs_found' => ['$gte' => 10]]
    ]
]);

// Matchmaking eligibility
$rule = $mqb->parse([
    '$and' => [
        // Skill-based
        ['mmr' => ['$between' => [1500, 2000]]],

        // Behavior score
        ['behavior_score' => ['$gte' => 7000]],
        ['reports_last_week' => ['$lte' => 2]],

        // Technical
        ['latency_ms' => ['$lte' => 150]],
        ['region' => ['$in' => ['NA-East', 'NA-West', 'NA-Central']]],

        // Queue restrictions
        [
            '$not' => [
                '$or' => [
                    ['banned_until' => ['$after' => date('Y-m-d H:i:s')]],
                    ['queue_dodge_cooldown' => ['$after' => date('Y-m-d H:i:s')]],
                    ['in_low_priority_queue' => true]
                ]
            ]
        ],

        // Group size
        ['party_size' => ['$lte' => 5]]
    ]
]);
```

---

## Best Practices Summary

1. **Use PHP Arrays Over JSON Strings**: Avoid JSON parsing overhead and syntax errors
2. **Pre-Compute Complex Values**: Calculate in context, not in rules
3. **Leverage $in for OR on Same Field**: More efficient than explicit $or
4. **Put Restrictive Checks First**: Enable short-circuit evaluation
5. **Use Dot Notation for Nested Fields**: Clear and standard MongoDB syntax
6. **Use Custom Operators**: Take advantage of 28 custom operators unique to this DSL
7. **Prefer String Operators Over Regex**: $startsWith is faster than ^pattern
8. **Use Strict Equality When Needed**: $same/$nsame prevent type coercion bugs
9. **Cache Compiled Rules**: Parse once, evaluate many times
10. **Validate Types Explicitly**: Use $type, $empty, $exists for robustness

---

## Unique Advantages

**MongoDB Query DSL is the most feature-complete DSL with:**

- **28 custom operators** extending standard MongoDB
- **ONLY DSL with date operations**: $after, $before, $betweenDates
- **Most comprehensive string operations**: 11 operators (8 custom)
- **Extended logical operators**: $xor, $nand
- **Strict equality support**: $same, $nsame
- **Full type checking**: $type, $empty, $size
- **JSON-native**: Zero parsing complexity, perfect for REST APIs

---

## See Also

- [ADR 003: MongoDB Query DSL](../../adr/003-mongodb-query-dsl.md)
- [DSL Feature Support Matrix](dsl-feature-matrix.md)
- [Other DSL Cookbooks](../cookbooks/)
