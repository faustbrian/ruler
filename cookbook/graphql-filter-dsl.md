# GraphQL Filter DSL Cookbook

**Status:** Proposed
**Complexity:** Low to Moderate
**Best For:** GraphQL APIs, frontend applications, type-safe filtering, React/Vue/Angular apps

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Implicit Equality](#implicit-equality)
4. [Logical Operators](#logical-operators)
5. [String Operations](#string-operations)
6. [List Membership](#list-membership)
7. [Null Checks](#null-checks)
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
use Cline\Ruler\DSL\GraphQL\GraphQLFilterRuleBuilder;
use Cline\Ruler\Core\Context;

$gql = new GraphQLFilterRuleBuilder();

// Parse a GraphQL filter (as JSON string)
$rule = $gql->parse('{"age": {"gte": 18}, "country": "US"}');

// Or use PHP array syntax
$rule = $gql->parse([
    'age' => ['gte' => 18],
    'country' => 'US'
]);

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

### Why GraphQL Filter DSL?

- **Frontend-friendly** - Natural JSON syntax for JavaScript/TypeScript developers
- **Type-safe** - Works with GraphQL schemas for validation
- **Less verbose** - Cleaner than MongoDB for many queries
- **Industry standard** - Used by Hasura, Prisma, Postgraphile
- **IDE support** - Autocomplete and validation with GraphQL tooling
- **Composable** - Easy to merge filters programmatically

---

## Basic Comparisons

### Equality (eq)

```php
// Explicit equality
$rule = $gql->parse(['status' => ['eq' => 'active']]);
$rule->evaluate(new Context(['status' => 'active'])); // true

// Numeric equality
$rule = $gql->parse(['age' => ['eq' => 18]]);
$rule->evaluate(new Context(['age' => 18])); // true

// Boolean equality
$rule = $gql->parse(['verified' => ['eq' => true]]);
$rule->evaluate(new Context(['verified' => true])); // true
```

### Not Equal (ne)

```php
// String comparison
$rule = $gql->parse(['status' => ['ne' => 'banned']]);
$rule->evaluate(new Context(['status' => 'active'])); // true

// Numeric comparison
$rule = $gql->parse(['quantity' => ['ne' => 0]]);
$rule->evaluate(new Context(['quantity' => 5])); // true

// Exclude specific value
$rule = $gql->parse(['role' => ['ne' => 'guest']]);
$rule->evaluate(new Context(['role' => 'admin'])); // true
```

### Greater Than (gt)

```php
// Numeric greater than
$rule = $gql->parse(['price' => ['gt' => 100]]);
$rule->evaluate(new Context(['price' => 150])); // true
$rule->evaluate(new Context(['price' => 100])); // false (not inclusive)

// Age requirement
$rule = $gql->parse(['age' => ['gt' => 17]]);
$rule->evaluate(new Context(['age' => 18])); // true

// Decimal values
$rule = $gql->parse(['rating' => ['gt' => 4.5]]);
$rule->evaluate(new Context(['rating' => 4.8])); // true
```

### Greater Than or Equal (gte)

```php
// Minimum age (inclusive)
$rule = $gql->parse(['age' => ['gte' => 18]]);
$rule->evaluate(new Context(['age' => 18])); // true (inclusive)
$rule->evaluate(new Context(['age' => 25])); // true

// Minimum price
$rule = $gql->parse(['price' => ['gte' => 10]]);
$rule->evaluate(new Context(['price' => 10])); // true

// Score threshold
$rule = $gql->parse(['score' => ['gte' => 70]]);
$rule->evaluate(new Context(['score' => 85])); // true
```

### Less Than (lt)

```php
// Maximum limit
$rule = $gql->parse(['quantity' => ['lt' => 10]]);
$rule->evaluate(new Context(['quantity' => 5])); // true
$rule->evaluate(new Context(['quantity' => 10])); // false (not inclusive)

// Temperature threshold
$rule = $gql->parse(['temperature' => ['lt' => 32]]);
$rule->evaluate(new Context(['temperature' => 20])); // true

// Budget limit
$rule = $gql->parse(['price' => ['lt' => 100]]);
$rule->evaluate(new Context(['price' => 75])); // true
```

### Less Than or Equal (lte)

```php
// Maximum age (inclusive)
$rule = $gql->parse(['age' => ['lte' => 65]]);
$rule->evaluate(new Context(['age' => 65])); // true (inclusive)
$rule->evaluate(new Context(['age' => 50])); // true

// Max quantity
$rule = $gql->parse(['quantity' => ['lte' => 100]]);
$rule->evaluate(new Context(['quantity' => 100])); // true

// Max price
$rule = $gql->parse(['price' => ['lte' => 500]]);
$rule->evaluate(new Context(['price' => 499.99])); // true
```

### Range Checks (Combining Operators)

```php
// Age range (18-65)
$rule = $gql->parse([
    'age' => [
        'gte' => 18,
        'lte' => 65
    ]
]);
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 17])); // false
$rule->evaluate(new Context(['age' => 70])); // false

// Price range
$rule = $gql->parse([
    'price' => [
        'gte' => 10,
        'lte' => 500
    ]
]);
$rule->evaluate(new Context(['price' => 150])); // true

// Temperature range
$rule = $gql->parse([
    'temperature' => [
        'gt' => 0,
        'lt' => 100
    ]
]);
$rule->evaluate(new Context(['temperature' => 50])); // true
```

---

## Implicit Equality

**GraphQL Filter DSL's cleanest feature** - omit the `eq` operator for simple equality checks.

### Basic Implicit Equality

```php
// Simple field check
$rule = $gql->parse(['status' => 'active']);
$rule->evaluate(new Context(['status' => 'active'])); // true

// Numeric value
$rule = $gql->parse(['age' => 18]);
$rule->evaluate(new Context(['age' => 18])); // true

// Boolean value
$rule = $gql->parse(['verified' => true]);
$rule->evaluate(new Context(['verified' => true])); // true

// Null value
$rule = $gql->parse(['deletedAt' => null]);
$rule->evaluate(new Context(['deletedAt' => null])); // true
```

### Multiple Fields (Implicit AND)

```php
// Clean multi-field filter
$rule = $gql->parse([
    'country' => 'US',
    'age' => 18,
    'verified' => true
]);

$context = new Context([
    'country' => 'US',
    'age' => 18,
    'verified' => true
]);
$rule->evaluate($context); // true
```

### Comparison: Explicit vs Implicit

```php
// ❌ Verbose - explicit eq operator
$rule = $gql->parse([
    'status' => ['eq' => 'active'],
    'country' => ['eq' => 'US'],
    'verified' => ['eq' => true]
]);

// ✅ Clean - implicit equality
$rule = $gql->parse([
    'status' => 'active',
    'country' => 'US',
    'verified' => true
]);

// Both are equivalent!
```

### When to Use Explicit Operators

```php
// Use explicit operators for non-equality comparisons
$rule = $gql->parse([
    'age' => ['gte' => 18],      // Must use gte
    'country' => 'US',            // Can use implicit
    'price' => ['lt' => 100]      // Must use lt
]);

// Ranges require explicit operators
$rule = $gql->parse([
    'price' => [
        'gte' => 10,    // Explicit
        'lte' => 500    // Explicit
    ]
]);
```

---

## Logical Operators

**Convention:** Logical operators use UPPERCASE (AND, OR, NOT) while field names use camelCase.

### AND (Explicit)

```php
// Explicit AND
$rule = $gql->parse([
    'AND' => [
        ['age' => ['gte' => 18]],
        ['country' => 'US'],
        ['verified' => true]
    ]
]);

$valid = new Context([
    'age' => 25,
    'country' => 'US',
    'verified' => true
]);
$rule->evaluate($valid); // true

$invalid = new Context([
    'age' => 25,
    'country' => 'FR',  // Fails here
    'verified' => true
]);
$rule->evaluate($invalid); // false
```

### AND (Implicit)

```php
// Multiple fields = implicit AND
$rule = $gql->parse([
    'age' => ['gte' => 18],
    'country' => 'US',
    'verified' => true
]);

// Equivalent to:
$rule = $gql->parse([
    'AND' => [
        ['age' => ['gte' => 18]],
        ['country' => 'US'],
        ['verified' => true]
    ]
]);
```

### OR

```php
// At least one condition must match
$rule = $gql->parse([
    'OR' => [
        ['status' => 'active'],
        ['status' => 'pending']
    ]
]);

$rule->evaluate(new Context(['status' => 'active']));  // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned']));  // false

// OR with different fields
$rule = $gql->parse([
    'OR' => [
        ['age' => ['gte' => 65]],
        ['disabled' => true]
    ]
]);

$rule->evaluate(new Context(['age' => 70, 'disabled' => false])); // true (age matches)
$rule->evaluate(new Context(['age' => 30, 'disabled' => true]));  // true (disabled matches)
$rule->evaluate(new Context(['age' => 30, 'disabled' => false])); // false (neither)
```

### NOT

```php
// Negate a single condition
$rule = $gql->parse([
    'NOT' => ['status' => 'banned']
]);
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Negate multiple conditions (implicit AND inside NOT)
$rule = $gql->parse([
    'NOT' => [
        'age' => ['lt' => 18],
        'country' => 'FR'
    ]
]);
// True if NOT (age < 18 AND country == FR)

// Negate complex expression
$rule = $gql->parse([
    'NOT' => [
        'OR' => [
            ['status' => 'banned'],
            ['status' => 'suspended']
        ]
    ]
]);
$rule->evaluate(new Context(['status' => 'active'])); // true
```

### Complex Nesting

```php
// Combining AND, OR, NOT
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['age' => ['gte' => 18, 'lt' => 65]],
                ['vip' => true]
            ]
        ],
        ['country' => ['in' => ['US', 'CA', 'UK']]],
        [
            'NOT' => [
                'status' => ['in' => ['banned', 'deleted']]
            ]
        ]
    ]
]);

// Readable breakdown:
// (age between 18-65 OR vip)
// AND country is US/CA/UK
// AND status is NOT banned/deleted
```

### Operator Precedence

```php
// Implicit AND at root level
$rule = $gql->parse([
    'OR' => [
        ['status' => 'active'],
        ['status' => 'pending']
    ],
    'age' => ['gte' => 18]  // AND with the OR above
]);

// Equivalent to: (status == active OR status == pending) AND age >= 18

// Use explicit AND for clarity
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['status' => 'active'],
                ['status' => 'pending']
            ]
        ],
        ['age' => ['gte' => 18]]
    ]
]);
```

---

## String Operations

### Contains (Case-Sensitive)

```php
// Email domain check
$rule = $gql->parse(['email' => ['contains' => '@example.com']]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com']));    // false

// Keyword in description
$rule = $gql->parse(['description' => ['contains' => 'premium']]);
$rule->evaluate(new Context(['description' => 'This is a premium product'])); // true

// Case-sensitive
$rule = $gql->parse(['title' => ['contains' => 'Important']]);
$rule->evaluate(new Context(['title' => 'Very Important Notice'])); // true
$rule->evaluate(new Context(['title' => 'Very important Notice'])); // false (lowercase 'i')
```

### Not Contains

```php
// Exclude test emails
$rule = $gql->parse(['email' => ['notContains' => '@test.com']]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com']));    // false

// Exclude specific keywords
$rule = $gql->parse(['content' => ['notContains' => 'spam']]);
$rule->evaluate(new Context(['content' => 'This is legitimate content'])); // true
$rule->evaluate(new Context(['content' => 'Buy now - spam offer']));       // false
```

### Contains Insensitive (Case-Insensitive)

```php
// Case-insensitive search
$rule = $gql->parse(['country' => ['containsInsensitive' => 'united']]);
$rule->evaluate(new Context(['country' => 'United States'])); // true
$rule->evaluate(new Context(['country' => 'UNITED KINGDOM'])); // true
$rule->evaluate(new Context(['country' => 'united arab emirates'])); // true

// Flexible keyword matching
$rule = $gql->parse(['title' => ['containsInsensitive' => 'important']]);
$rule->evaluate(new Context(['title' => 'Very IMPORTANT Notice'])); // true
$rule->evaluate(new Context(['title' => 'important update']));      // true
$rule->evaluate(new Context(['title' => 'ImPoRtAnT']));             // true
```

### Starts With

```php
// Name prefix
$rule = $gql->parse(['name' => ['startsWith' => 'John']]);
$rule->evaluate(new Context(['name' => 'John Doe']));   // true
$rule->evaluate(new Context(['name' => 'John Smith'])); // true
$rule->evaluate(new Context(['name' => 'Jane Doe']));   // false

// URL protocol
$rule = $gql->parse(['url' => ['startsWith' => 'https://']]);
$rule->evaluate(new Context(['url' => 'https://example.com'])); // true
$rule->evaluate(new Context(['url' => 'http://example.com']));  // false

// Product code prefix
$rule = $gql->parse(['sku' => ['startsWith' => 'PROD-']]);
$rule->evaluate(new Context(['sku' => 'PROD-12345'])); // true
$rule->evaluate(new Context(['sku' => 'ITEM-12345'])); // false
```

### Ends With

```php
// File extension
$rule = $gql->parse(['filename' => ['endsWith' => '.pdf']]);
$rule->evaluate(new Context(['filename' => 'document.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'image.png']));    // false

// Email domain
$rule = $gql->parse(['email' => ['endsWith' => '@company.com']]);
$rule->evaluate(new Context(['email' => 'john@company.com'])); // true
$rule->evaluate(new Context(['email' => 'john@gmail.com']));   // false

// Suffix check
$rule = $gql->parse(['description' => ['endsWith' => '...']]);
$rule->evaluate(new Context(['description' => 'Read more...'])); // true
```

### Match (Regex)

```php
// Email validation
$rule = $gql->parse([
    'email' => ['match' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$']
]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'invalid-email']));    // false

// Phone number (US format)
$rule = $gql->parse(['phone' => ['match' => '^\\d{3}-\\d{3}-\\d{4}$']]);
$rule->evaluate(new Context(['phone' => '555-123-4567'])); // true
$rule->evaluate(new Context(['phone' => '5551234567']));   // false

// Postal code
$rule = $gql->parse(['zip' => ['match' => '^\\d{5}(-\\d{4})?$']]);
$rule->evaluate(new Context(['zip' => '12345']));      // true
$rule->evaluate(new Context(['zip' => '12345-6789'])); // true
$rule->evaluate(new Context(['zip' => '123']));        // false

// Product code (3 letters + 3 digits)
$rule = $gql->parse(['code' => ['match' => '^[A-Z]{3}\\d{3}$']]);
$rule->evaluate(new Context(['code' => 'ABC123'])); // true
$rule->evaluate(new Context(['code' => 'AB1234'])); // false
```

### Combining String Operators

```php
// Email must be corporate and end with specific domain
$rule = $gql->parse([
    'email' => [
        'contains' => '@company',
        'endsWith' => '.com'
    ]
]);

// Name starts with 'Dr.' and contains specialty
$rule = $gql->parse([
    'AND' => [
        ['name' => ['startsWith' => 'Dr.']],
        [
            'OR' => [
                ['specialty' => ['containsInsensitive' => 'cardio']],
                ['specialty' => ['containsInsensitive' => 'neuro']]
            ]
        ]
    ]
]);
```

---

## List Membership

### In

```php
// Country whitelist
$rule = $gql->parse(['country' => ['in' => ['US', 'CA', 'UK']]]);
$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'FR'])); // false

// Status values
$rule = $gql->parse(['status' => ['in' => ['active', 'pending', 'verified']]]);
$rule->evaluate(new Context(['status' => 'active']));  // true
$rule->evaluate(new Context(['status' => 'banned']]);  // false

// Numeric values
$rule = $gql->parse(['statusCode' => ['in' => [200, 201, 204]]]);
$rule->evaluate(new Context(['statusCode' => 200])); // true
$rule->evaluate(new Context(['statusCode' => 404])); // false

// Mixed types
$rule = $gql->parse(['value' => ['in' => [1, 'two', true, null]]]);
$rule->evaluate(new Context(['value' => 1]));      // true
$rule->evaluate(new Context(['value' => 'two']));  // true
$rule->evaluate(new Context(['value' => true]));   // true
$rule->evaluate(new Context(['value' => null]));   // true
$rule->evaluate(new Context(['value' => 'three'])); // false
```

### Not In

```php
// Exclude specific statuses
$rule = $gql->parse(['status' => ['notIn' => ['banned', 'suspended', 'deleted']]]);
$rule->evaluate(new Context(['status' => 'active']));    // true
$rule->evaluate(new Context(['status' => 'banned']));    // false

// Exclude test users
$rule = $gql->parse(['userId' => ['notIn' => [1, 2, 3, 99, 100]]]);
$rule->evaluate(new Context(['userId' => 50]));  // true
$rule->evaluate(new Context(['userId' => 1]));   // false

// Exclude specific countries
$rule = $gql->parse(['country' => ['notIn' => ['XX', 'ZZ', 'TEST']]]);
$rule->evaluate(new Context(['country' => 'US']));   // true
$rule->evaluate(new Context(['country' => 'TEST'])); // false
```

### Combining In/NotIn

```php
// Must be in allowed list AND not in blocked list
$rule = $gql->parse([
    'country' => ['in' => ['US', 'CA', 'UK', 'DE', 'FR']],
    'userId' => ['notIn' => [1, 2, 3]]  // Exclude test users
]);

// Category whitelist with status blacklist
$rule = $gql->parse([
    'AND' => [
        ['category' => ['in' => ['electronics', 'books', 'toys']]],
        ['status' => ['notIn' => ['discontinued', 'recalled']]]
    ]
]);
```

---

## Null Checks

### Is Null (True)

```php
// Check if field is null
$rule = $gql->parse(['deletedAt' => ['isNull' => true]]);
$rule->evaluate(new Context(['deletedAt' => null]));  // true
$rule->evaluate(new Context(['deletedAt' => '2024-01-01'])); // false

// Optional field not set
$rule = $gql->parse(['middleName' => ['isNull' => true]]);
$rule->evaluate(new Context(['middleName' => null]));   // true
$rule->evaluate(new Context(['middleName' => 'James'])); // false

// Unprocessed records
$rule = $gql->parse(['processedAt' => ['isNull' => true]]);
$rule->evaluate(new Context(['processedAt' => null])); // true
```

### Is Null (False) - Not Null

```php
// Check if field has a value
$rule = $gql->parse(['email' => ['isNull' => false]]);
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => null]));               // false

// Required field check
$rule = $gql->parse(['phoneNumber' => ['isNull' => false]]);
$rule->evaluate(new Context(['phoneNumber' => '555-1234'])); // true
$rule->evaluate(new Context(['phoneNumber' => null]));       // false

// Completed records
$rule = $gql->parse(['completedAt' => ['isNull' => false]]);
$rule->evaluate(new Context(['completedAt' => '2024-01-01'])); // true
$rule->evaluate(new Context(['completedAt' => null]));         // false
```

### Null vs Empty String

```php
// Important: null is NOT the same as empty string
$rule = $gql->parse(['value' => ['isNull' => true]]);
$rule->evaluate(new Context(['value' => null])); // true
$rule->evaluate(new Context(['value' => '']));   // false (empty string is not null)

// Check for either null or empty
$rule = $gql->parse([
    'OR' => [
        ['value' => ['isNull' => true]],
        ['value' => '']
    ]
]);
$rule->evaluate(new Context(['value' => null])); // true
$rule->evaluate(new Context(['value' => '']));   // true
```

### Combining Null Checks

```php
// Active records (not deleted)
$rule = $gql->parse([
    'status' => 'active',
    'deletedAt' => ['isNull' => true]
]);

// Complete user profile
$rule = $gql->parse([
    'email' => ['isNull' => false],
    'phoneNumber' => ['isNull' => false],
    'address' => ['isNull' => false]
]);

// Processing queue (created but not processed)
$rule = $gql->parse([
    'createdAt' => ['isNull' => false],
    'processedAt' => ['isNull' => true]
]);
```

---

## Type Checking

### Basic Type Checks

```php
// String type
$rule = $gql->parse(['name' => ['isType' => 'string']]);
$rule->evaluate(new Context(['name' => 'John']));  // true
$rule->evaluate(new Context(['name' => 123]));     // false

// Number type
$rule = $gql->parse(['age' => ['isType' => 'number']]);
$rule->evaluate(new Context(['age' => 25]));      // true
$rule->evaluate(new Context(['age' => '25']));    // false (string)
$rule->evaluate(new Context(['age' => 25.5]));    // true (float is number)

// Boolean type
$rule = $gql->parse(['verified' => ['isType' => 'boolean']]);
$rule->evaluate(new Context(['verified' => true]));  // true
$rule->evaluate(new Context(['verified' => false])); // true
$rule->evaluate(new Context(['verified' => 1]));     // false

// Array type
$rule = $gql->parse(['tags' => ['isType' => 'array']]);
$rule->evaluate(new Context(['tags' => ['a', 'b']])); // true
$rule->evaluate(new Context(['tags' => 'string']));   // false

// Null type
$rule = $gql->parse(['value' => ['isType' => 'null']]);
$rule->evaluate(new Context(['value' => null]));  // true
$rule->evaluate(new Context(['value' => 0]));     // false
```

### Combining Type Checks with Value Checks

```php
// Must be a number AND greater than 18
$rule = $gql->parse([
    'age' => [
        'isType' => 'number',
        'gte' => 18
    ]
]);

// Must be a string AND contain '@'
$rule = $gql->parse([
    'email' => [
        'isType' => 'string',
        'contains' => '@'
    ]
]);

// Must be an array AND not empty
$rule = $gql->parse([
    'tags' => [
        'isType' => 'array',
        'isEmpty' => false
    ]
]);
```

### Validation Patterns

```php
// Ensure numeric before comparison
$rule = $gql->parse([
    'AND' => [
        ['price' => ['isType' => 'number']],
        ['price' => ['gte' => 0, 'lte' => 1000]]
    ]
]);

// Ensure string before regex
$rule = $gql->parse([
    'AND' => [
        ['email' => ['isType' => 'string']],
        ['email' => ['match' => '^[\\w.]+@[\\w.]+$']]
    ]
]);

// Type-safe boolean check
$rule = $gql->parse([
    'AND' => [
        ['active' => ['isType' => 'boolean']],
        ['active' => true]
    ]
]);
```

---

## Nested Properties

### Object Syntax (Nested)

```php
// Nested object structure
$rule = $gql->parse([
    'user' => [
        'profile' => [
            'age' => ['gte' => 18]
        ]
    ]
]);

$context = new Context([
    'user' => [
        'profile' => [
            'age' => 25
        ]
    ]
]);
$rule->evaluate($context); // true
```

### Dot Notation (Flattened)

```php
// Flattened dot notation
$rule = $gql->parse([
    'user_profile_age' => ['gte' => 18]
]);

// Internally converts to: user.profile.age >= 18
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
// Multiple levels deep
$rule = $gql->parse([
    'order' => [
        'shipping' => [
            'address' => [
                'country' => 'US',
                'zip' => ['match' => '^\\d{5}$']
            ]
        ]
    ]
]);

$context = new Context([
    'order' => [
        'shipping' => [
            'address' => [
                'country' => 'US',
                'zip' => '12345'
            ]
        ]
    ]
]);
$rule->evaluate($context); // true
```

### Mixed Nesting Styles

```php
// Combine nested and flat
$rule = $gql->parse([
    'user' => [
        'profile' => [
            'age' => ['gte' => 18]
        ]
    ],
    'subscription_status' => 'active'  // Flat field
]);

// Nested with logical operators
$rule = $gql->parse([
    'AND' => [
        [
            'user' => [
                'profile' => [
                    'age' => ['gte' => 18]
                ]
            ]
        ],
        [
            'user' => [
                'profile' => [
                    'verified' => true
                ]
            ]
        ]
    ]
]);
```

### Array Element Access

```php
// Access array elements (if supported)
$rule = $gql->parse([
    'items_0_price' => ['gt' => 100]  // items[0].price > 100
]);

$context = new Context([
    'items' => [
        ['price' => 150],
        ['price' => 50]
    ]
]);
$rule->evaluate($context); // true
```

---

## Advanced Patterns

### Complex Business Rules

```php
// E-commerce product eligibility
$rule = $gql->parse([
    'AND' => [
        ['category' => 'electronics'],
        ['price' => ['gte' => 10, 'lte' => 500]],
        ['inStock' => true],
        [
            'OR' => [
                ['featured' => true],
                ['rating' => ['gte' => 4.0]]
            ]
        ],
        ['status' => ['notIn' => ['clearance', 'discontinued']]]
    ]
]);

// User access control
$rule = $gql->parse([
    'AND' => [
        ['role' => ['in' => ['admin', 'moderator']]],
        ['accountAgeDays' => ['gte' => 30]],
        ['emailVerified' => true],
        ['status' => ['notIn' => ['banned', 'suspended']]]
    ]
]);

// Subscription eligibility
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['subscriptionStatus' => 'active'],
                ['trialDaysLeft' => ['gt' => 0]]
            ]
        ],
        ['paymentMethod' => ['isNull' => false]],
        ['totalSpend' => ['gt' => 0]]
    ]
]);
```

### Multi-Tier Logic

```php
// Premium feature access
$rule = $gql->parse([
    'OR' => [
        [
            'AND' => [
                ['subscriptionTier' => ['in' => ['premium', 'enterprise']]],
                ['subscriptionExpires' => ['gt' => '2024-01-01']]
            ]
        ],
        [
            'AND' => [
                ['trialActive' => true],
                ['trialExpires' => ['gt' => '2024-01-01']],
                ['featureUsage' => ['lt' => 100]]
            ]
        ]
    ]
]);

// Multi-region eligibility
$rule = $gql->parse([
    'AND' => [
        ['age' => ['gte' => 18]],
        [
            'OR' => [
                [
                    'AND' => [
                        ['country' => 'US'],
                        ['state' => ['in' => ['CA', 'NY', 'TX']]]
                    ]
                ],
                [
                    'AND' => [
                        ['country' => 'CA'],
                        ['province' => ['in' => ['ON', 'BC', 'QC']]]
                    ]
                ],
                ['country' => ['in' => ['UK', 'DE', 'FR']]]
            ]
        ],
        ['verificationScore' => ['gte' => 70]]
    ]
]);
```

### Dynamic Filters

```php
// Programmatically build filters
function buildProductFilter(array $options): array
{
    $filter = ['AND' => []];

    if (isset($options['category'])) {
        $filter['AND'][] = ['category' => $options['category']];
    }

    if (isset($options['minPrice'])) {
        $filter['AND'][] = ['price' => ['gte' => $options['minPrice']]];
    }

    if (isset($options['maxPrice'])) {
        $filter['AND'][] = ['price' => ['lte' => $options['maxPrice']]];
    }

    if (isset($options['inStockOnly']) && $options['inStockOnly']) {
        $filter['AND'][] = ['inStock' => true];
    }

    return $filter;
}

$options = [
    'category' => 'electronics',
    'minPrice' => 10,
    'maxPrice' => 500,
    'inStockOnly' => true
];

$rule = $gql->parse(buildProductFilter($options));
```

### Merging Filters

```php
// Base filter
$baseFilter = [
    'status' => 'active',
    'deletedAt' => ['isNull' => true]
];

// User-specific filter
$userFilter = [
    'userId' => 123,
    'role' => ['in' => ['admin', 'moderator']]
];

// Merge filters
$combinedFilter = [
    'AND' => [
        $baseFilter,
        $userFilter
    ]
];

$rule = $gql->parse($combinedFilter);
```

### Reusable Filter Components

```php
// Define reusable filter components
$isActiveUser = [
    'status' => 'active',
    'emailVerified' => true,
    'deletedAt' => ['isNull' => true]
];

$isPremiumSubscriber = [
    'subscriptionTier' => ['in' => ['premium', 'enterprise']],
    'subscriptionExpires' => ['gt' => date('Y-m-d')]
];

$isInAllowedRegion = [
    'country' => ['in' => ['US', 'CA', 'UK', 'DE', 'FR']]
];

// Combine components
$premiumRegionalUser = [
    'AND' => [
        $isActiveUser,
        $isPremiumSubscriber,
        $isInAllowedRegion
    ]
];

$rule = $gql->parse($premiumRegionalUser);
```

---

## Performance Optimization

### Order Conditions for Short-Circuiting

```php
// ✅ Good - cheap checks first
$rule = $gql->parse([
    'status' => 'active',  // Fast field check
    'country' => 'US',     // Fast field check
    'age' => ['gte' => 18],
    'complexCalculation' => ['gt' => 1000]  // Expensive check last
]);

// ❌ Bad - expensive check first
$rule = $gql->parse([
    'complexCalculation' => ['gt' => 1000],  // Expensive check first
    'status' => 'active',
    'country' => 'US'
]);
```

### Use OR for Early Exit

```php
// ✅ Good - likely matches exit early
$rule = $gql->parse([
    'OR' => [
        ['vip' => true],              // Check VIP first (likely true for VIPs)
        ['totalPurchases' => ['gt' => 1000]]  // Expensive calculation
    ]
]);

// For VIP users, the rule returns true immediately without checking purchases
```

### Avoid Redundant Checks

```php
// ❌ Bad - redundant type check
$rule = $gql->parse([
    'AND' => [
        ['age' => ['isType' => 'number']],
        ['age' => ['gte' => 18]]
    ]
]);

// ✅ Good - comparison operator implies type
$rule = $gql->parse([
    'age' => ['gte' => 18]  // Already assumes numeric
]);
```

### Pre-compute Values in Context

```php
// ❌ Bad - multiple fields when one computed would work
// In your filter
$rule = $gql->parse([
    'AND' => [
        ['price' => ['gte' => 10]],
        ['shipping' => ['gte' => 5]],
        // Can't directly add price + shipping in GraphQL Filter
    ]
]);

// ✅ Good - pre-compute total in context
$context = new Context([
    'price' => 75,
    'shipping' => 15,
    'total' => 90  // Pre-computed: price + shipping
]);

$rule = $gql->parse([
    'total' => ['gte' => 100]  // Simple single field check
]);
```

### Simplify Complex Filters

```php
// ❌ Complex - multiple OR conditions
$rule = $gql->parse([
    'OR' => [
        ['status' => 'active'],
        ['status' => 'pending'],
        ['status' => 'verified'],
        ['status' => 'approved']
    ]
]);

// ✅ Better - use IN operator
$rule = $gql->parse([
    'status' => ['in' => ['active', 'pending', 'verified', 'approved']]
]);
```

### Caching Parsed Rules

```php
// Parse once, evaluate many times
class FilterCache
{
    private array $cache = [];

    public function getRule(string|array $filter): Rule
    {
        $key = is_string($filter) ? $filter : json_encode($filter);

        if (!isset($this->cache[$key])) {
            $gql = new GraphQLFilterRuleBuilder();
            $this->cache[$key] = $gql->parse($filter);
        }

        return $this->cache[$key];
    }
}

// Usage
$cache = new FilterCache();
$rule = $cache->getRule(['age' => ['gte' => 18]]);  // Parsed once

$result1 = $rule->evaluate($context1);
$result2 = $rule->evaluate($context2);
$result3 = $rule->evaluate($context3);
// Same rule, different contexts - no re-parsing
```

### Batch Processing

```php
// Process multiple contexts efficiently
$rule = $gql->parse([
    'age' => ['gte' => 18],
    'country' => 'US'
]);

$contexts = [
    new Context(['age' => 25, 'country' => 'US']),
    new Context(['age' => 30, 'country' => 'CA']),
    new Context(['age' => 20, 'country' => 'US']),
    // ... thousands more
];

$results = array_map(
    fn($ctx) => $rule->evaluate($ctx),
    $contexts
);
```

---

## Common Pitfalls

### Case Sensitivity

```php
// ❌ Wrong - logical operators must be UPPERCASE
$rule = $gql->parse([
    'and' => [  // Wrong! Should be 'AND'
        ['age' => ['gte' => 18]],
        ['country' => 'US']
    ]
]);

// ✅ Correct - UPPERCASE logical operators
$rule = $gql->parse([
    'AND' => [
        ['age' => ['gte' => 18]],
        ['country' => 'US']
    ]
]);
```

### String vs Number Confusion

```php
// ❌ Wrong - comparing number to string
$rule = $gql->parse(['age' => '18']);  // String '18'
$rule->evaluate(new Context(['age' => 18])); // May fail depending on implementation

// ✅ Correct - use proper types
$rule = $gql->parse(['age' => 18]);  // Number 18
```

### Null vs Empty String

```php
// ❌ Wrong - confusing null with empty string
$rule = $gql->parse(['email' => ['isNull' => true]]);
$rule->evaluate(new Context(['email' => ''])); // false - empty string is NOT null

// ✅ Correct - check for both if needed
$rule = $gql->parse([
    'OR' => [
        ['email' => ['isNull' => true]],
        ['email' => '']
    ]
]);
```

### Operator Typos

```php
// ❌ Wrong - common typos
$rule = $gql->parse(['age' => ['greater_than' => 18]]);  // Wrong!
$rule = $gql->parse(['name' => ['startWith' => 'John']]);  // Wrong! (missing 's')
$rule = $gql->parse(['tags' => ['include' => 'premium']]);  // Wrong!

// ✅ Correct - exact operator names
$rule = $gql->parse(['age' => ['gt' => 18]]);  // or 'gte'
$rule = $gql->parse(['name' => ['startsWith' => 'John']]);
$rule = $gql->parse(['tags' => ['has' => 'premium']]);
```

### Forgetting Array Wrappers

```php
// ❌ Wrong - OR/AND need arrays
$rule = $gql->parse([
    'OR' => ['status' => 'active']  // Wrong! OR needs array of conditions
]);

// ✅ Correct - wrap in array
$rule = $gql->parse([
    'OR' => [
        ['status' => 'active'],
        ['status' => 'pending']
    ]
]);
```

### Nested Object Mistakes

```php
// ❌ Wrong - mixing nested syntax
$rule = $gql->parse([
    'user.profile' => [  // Don't mix dot notation with nested objects
        'age' => ['gte' => 18]
    ]
]);

// ✅ Correct - use full nested structure
$rule = $gql->parse([
    'user' => [
        'profile' => [
            'age' => ['gte' => 18]
        ]
    ]
]);

// OR use flattened naming
$rule = $gql->parse([
    'user_profile_age' => ['gte' => 18]
]);
```

### Regex Pattern Mistakes

```php
// ❌ Wrong - not escaping backslashes
$rule = $gql->parse(['phone' => ['match' => '^\d{3}-\d{4}$']]);  // \d won't work

// ✅ Correct - double escape in JSON string
$rule = $gql->parse(['phone' => ['match' => '^\\d{3}-\\d{4}$']]);

// Or use PHP array (single escape)
$rule = $gql->parse([
    'phone' => ['match' => '^\d{3}-\d{4}$']
]);
```

### Boolean Value Mistakes

```php
// ❌ Wrong - using strings for booleans
$rule = $gql->parse(['verified' => 'true']);  // String 'true', not boolean

// ✅ Correct - use actual boolean
$rule = $gql->parse(['verified' => true]);
```

### Range Operator Mistakes

```php
// ❌ Wrong - using wrong operator combination
$rule = $gql->parse([
    'age' => [
        'gt' => 18,   // Greater than
        'lt' => 65    // Less than
    ]
]);
// Excludes 18 and 65!

// ✅ Correct - use inclusive operators for ranges
$rule = $gql->parse([
    'age' => [
        'gte' => 18,  // Greater than or equal
        'lte' => 65   // Less than or equal
    ]
]);
// Includes 18 and 65
```

---

## Real-World Examples

### E-Commerce

#### Product Catalog Filter

```php
$rule = $gql->parse([
    'AND' => [
        ['category' => ['in' => ['electronics', 'computers', 'accessories']]],
        ['price' => ['gte' => 10, 'lte' => 500]],
        ['inStock' => true],
        [
            'OR' => [
                ['featured' => true],
                ['rating' => ['gte' => 4.0]],
                ['salesCount' => ['gt' => 100]]
            ]
        ],
        ['status' => ['notIn' => ['discontinued', 'recalled', 'clearance']]],
        ['brand' => ['notIn' => ['Generic', 'Unknown']]]
    ]
]);
```

#### Dynamic Shipping Eligibility

```php
$rule = $gql->parse([
    'AND' => [
        ['weight' => ['gt' => 0, 'lte' => 50]],
        [
            'OR' => [
                [
                    'AND' => [
                        ['country' => 'US'],
                        ['state' => ['notIn' => ['AK', 'HI']]]  // Exclude Alaska/Hawaii
                    ]
                ],
                [
                    'AND' => [
                        ['country' => 'CA'],
                        ['weight' => ['lte' => 30]]
                    ]
                ],
                [
                    'AND' => [
                        ['country' => ['in' => ['UK', 'DE', 'FR']]],
                        ['weight' => ['lte' => 20]]
                    ]
                ]
            ]
        ],
        ['totalValue' => ['lte' => 2500]]  // Customs limit
    ]
]);
```

#### Promotional Pricing

```php
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['totalAmount' => ['gte' => 100]],  // $100+ order
                ['itemCount' => ['gte' => 5]]       // 5+ items
            ]
        ],
        ['customerTier' => ['in' => ['silver', 'gold', 'platinum']]],
        ['promoCode' => ['isNull' => false]],
        ['firstPurchase' => false]  // Not first-time buyer discount
    ]
]);
```

#### Cart Abandonment Recovery

```php
$rule = $gql->parse([
    'AND' => [
        ['cartValue' => ['gte' => 50]],
        ['cartAgeHours' => ['gte' => 24, 'lte' => 72]],
        ['emailSent' => ['isNull' => true]],
        ['lastPurchaseDays' => ['gte' => 30]],
        ['emailVerified' => true]
    ]
]);
```

### User Access Control

#### Admin Dashboard Access

```php
$rule = $gql->parse([
    'AND' => [
        ['role' => ['in' => ['admin', 'super_admin', 'moderator']]],
        ['emailVerified' => true],
        ['twoFactorEnabled' => true],
        ['accountAgeDays' => ['gte' => 30]],
        ['status' => ['notIn' => ['suspended', 'locked', 'pending_review']]],
        [
            'OR' => [
                ['lastLoginHours' => ['lte' => 24]],
                ['sessionValid' => true]
            ]
        ]
    ]
]);
```

#### Beta Feature Access

```php
$rule = $gql->parse([
    'OR' => [
        ['userId' => ['in' => [1, 2, 3, 100, 200]]],  // Internal team
        [
            'AND' => [
                ['betaTester' => true],
                ['optInDate' => ['isNull' => false]],
                ['accountAgeDays' => ['gte' => 90]]
            ]
        ],
        [
            'AND' => [
                ['subscriptionTier' => 'enterprise'],
                ['featureFlags' => ['contains' => 'beta_access']]
            ]
        ]
    ]
]);
```

#### Content Moderation Queue

```php
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['reportCount' => ['gte' => 5]],
                [
                    'AND' => [
                        ['spamScore' => ['gt' => 80]],
                        ['accountAgeDays' => ['lt' => 7]]
                    ]
                ],
                ['content' => ['containsInsensitive' => 'spam']],
                ['content' => ['containsInsensitive' => 'scam']]
            ]
        ],
        ['userId' => ['notIn' => [1, 2, 3]]],  // Exclude trusted users
        ['autoModerationEnabled' => true],
        ['moderationStatus' => ['isNull' => true]]
    ]
]);
```

### SaaS Applications

#### API Rate Limiting

```php
$rule = $gql->parse([
    'OR' => [
        [
            'AND' => [
                ['plan' => 'free'],
                ['apiCallsThisMonth' => ['lt' => 1000]]
            ]
        ],
        [
            'AND' => [
                ['plan' => 'pro'],
                ['apiCallsThisMonth' => ['lt' => 50000]]
            ]
        ],
        [
            'AND' => [
                ['plan' => 'enterprise'],
                ['apiCallsThisMonth' => ['lt' => 1000000]]
            ]
        ]
    ],
    'subscriptionStatus' => 'active',
    'paymentFailed' => false
]);
```

#### Feature Gating

```php
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['plan' => ['in' => ['pro', 'enterprise']]],
                [
                    'AND' => [
                        ['trialActive' => true],
                        ['trialExpires' => ['gt' => '2024-01-01']]
                    ]
                ]
            ]
        ],
        ['featureUsage' => ['lt' => 100]],
        ['accountStatus' => 'active']
    ]
]);
```

#### Subscription Renewal Reminder

```php
$rule = $gql->parse([
    'AND' => [
        ['subscriptionExpiresDays' => ['gte' => 0, 'lte' => 7]],  // Expires in 7 days
        ['autoRenew' => false],
        ['reminderSent' => ['isNull' => true]],
        ['totalPaidMonths' => ['gte' => 3]],  // Long-term customer
        ['emailOptIn' => true]
    ]
]);
```

### Financial Services

#### Loan Pre-Approval

```php
$rule = $gql->parse([
    'AND' => [
        ['age' => ['gte' => 18, 'lte' => 75]],
        ['creditScore' => ['gte' => 650]],
        ['annualIncome' => ['gte' => 30000]],
        [
            'OR' => [
                ['debtToIncomeRatio' => ['lt' => 0.43]],
                ['hasCosigner' => true]
            ]
        ],
        ['employmentLengthMonths' => ['gte' => 6]],
        [
            'NOT' => [
                'AND' => [
                    ['bankruptcyHistory' => true],
                    ['yearsSinceBankruptcy' => ['lt' => 7]]
                ]
            ]
        ]
    ]
]);
```

#### Fraud Detection

```php
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['transactionAmount' => ['gt' => 5000]],
                [
                    'AND' => [
                        ['transactionAmount' => ['gt' => 1000]],
                        ['velocityLastHour' => ['gt' => 5]]
                    ]
                ],
                ['ipCountry' => ['ne' => '$billingCountry']]
            ]
        ],
        [
            'OR' => [
                ['fraudScore' => ['gt' => 75]],
                ['deviceFingerprint' => ['in' => ['known', 'fraud', 'devices']]],
                ['billingAddress' => ['match' => '.*P\\.?O\\.? Box.*']]
            ]
        ]
    ]
]);
```

#### Transaction Approval

```php
$rule = $gql->parse([
    'AND' => [
        ['accountStatus' => 'active'],
        ['accountBalance' => ['gte' => 0]],  // No overdraft
        ['dailyTransactionCount' => ['lt' => 50]],
        ['dailyTransactionTotal' => ['lt' => 10000]],
        [
            'OR' => [
                ['transactionAmount' => ['lte' => 500]],  // Auto-approve under $500
                [
                    'AND' => [
                        ['twoFactorVerified' => true],
                        ['transactionAmount' => ['lte' => 5000]]
                    ]
                ]
            ]
        ]
    ]
]);
```

### Healthcare

#### Patient Eligibility

```php
$rule = $gql->parse([
    'AND' => [
        ['age' => ['gte' => 18]],
        ['insuranceActive' => true],
        ['insuranceCoverageRemaining' => ['gt' => 0]],
        [
            'OR' => [
                ['referralRequired' => false],
                [
                    'AND' => [
                        ['referralRequired' => true],
                        ['referralDate' => ['isNull' => false]],
                        ['referralAgeDays' => ['lte' => 90]]
                    ]
                ]
            ]
        ],
        ['condition' => ['notIn' => ['excluded1', 'excluded2']]],
        ['priorAuthorizationStatus' => ['in' => ['approved', 'not_required']]]
    ]
]);
```

#### Appointment Scheduling

```php
$rule = $gql->parse([
    'AND' => [
        ['patientStatus' => 'active'],
        ['lastVisitDays' => ['gte' => 90]],
        ['missedAppointmentCount' => ['lt' => 3]],
        ['balanceOwed' => ['lte' => 100]],
        [
            'OR' => [
                ['preferredDoctor' => ['isNull' => false]],
                ['acceptsAnyDoctor' => true]
            ]
        ]
    ]
]);
```

### Gaming

#### Matchmaking Eligibility

```php
$rule = $gql->parse([
    'AND' => [
        ['playerRating' => ['gte' => 1000, 'lte' => 1500]],
        ['averageMatchDuration' => ['gte' => 10, 'lte' => 45]],
        ['region' => ['in' => ['NA-East', 'NA-West', 'EU']]],
        ['queueTime' => ['lt' => 300]],
        ['latency' => ['lt' => 100]],
        ['accountStatus' => 'active'],
        ['reportCount' => ['lt' => 5]]
    ]
]);
```

#### Achievement Unlock

```php
$rule = $gql->parse([
    'AND' => [
        ['playerLevel' => ['gte' => 50]],
        ['totalPlaytimeHours' => ['gte' => 100]],
        ['bossDefeats' => ['gte' => 20]],
        ['rareItemsCollected' => ['gte' => 5]],
        [
            'OR' => [
                ['soloAchievements' => ['gte' => 10]],
                [
                    'AND' => [
                        ['multiplayerWins' => ['gte' => 50]],
                        ['teamParticipationRate' => ['gte' => 0.8]]
                    ]
                ]
            ]
        ]
    ]
]);
```

### IoT & Monitoring

#### Alert Conditions

```php
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['temperature' => ['gt' => 80]],
                ['humidity' => ['lt' => 20]],
                [
                    'AND' => [
                        ['cpuUsage' => ['gt' => 90]],
                        ['durationSeconds' => ['gt' => 300]]
                    ]
                ],
                ['memoryAvailableMb' => ['lt' => 512]]
            ]
        ],
        ['alertCooldownExpired' => true],
        ['maintenanceMode' => false],
        ['deviceStatus' => 'online']
    ]
]);
```

#### Predictive Maintenance

```php
$rule = $gql->parse([
    'AND' => [
        ['deviceAgeMonths' => ['gte' => 24]],
        ['errorCount' => ['gte' => 10]],
        ['uptimePercentage' => ['lt' => 95]],
        [
            'OR' => [
                ['lastMaintenanceDays' => ['gte' => 180]],
                ['maintenanceOverdue' => true]
            ]
        ],
        ['criticalComponentStatus' => ['ne' => 'optimal']]
    ]
]);
```

---

## Best Practices Summary

1. **Use Implicit Equality** - Cleaner syntax for simple equality checks
2. **UPPERCASE Logical Operators** - AND, OR, NOT vs camelCase fields
3. **Put Cheap Checks First** - Leverage short-circuit evaluation
4. **Pre-Compute Complex Values** - Add to context before filtering
5. **Use IN for Multiple Values** - Cleaner than multiple OR conditions
6. **Cache Parsed Rules** - Parse once, evaluate many times
7. **Validate Types When Needed** - Use isType for type-safety
8. **Combine Operators for Ranges** - Use gte/lte together
9. **Use Object Syntax for Nested Data** - Or flatten with underscores
10. **Test Edge Cases** - Null values, empty strings, boundary conditions

---

## GraphQL vs Other DSLs

### Compared to Wirefilter

**GraphQL Advantages:**
- JSON-native (no parsing required from API)
- Type-safe with schema validation
- Better IDE support with GraphQL tooling
- Frontend-friendly for React/Vue developers

**Wirefilter Advantages:**
- Inline arithmetic expressions
- Action callbacks on match
- More concise for simple conditions
- Better for backend developers

### Compared to MongoDB Query

**GraphQL Advantages:**
- Less verbose for many queries
- Cleaner implicit AND syntax
- Better for frontend developers
- Industry standard (Hasura, Prisma)

**MongoDB Advantages:**
- More operators (XOR, NAND, etc.)
- Richer type system
- Better nested query support
- More familiar to NoSQL developers

---

## Limitations

### No Inline Arithmetic

```php
// ❌ Not supported - can't do math in filters
// $rule = $gql->parse(['price + shipping' => ['gt' => 100]]);

// ✅ Workaround - pre-compute in context
$context = new Context([
    'price' => 75,
    'shipping' => 30,
    'total' => 105  // Pre-computed
]);
$rule = $gql->parse(['total' => ['gt' => 100]]);
```

### No Action Callbacks

```php
// ❌ Not supported - GraphQL Filter is declarative only
// Can't execute code when rule matches

// ✅ Workaround - handle in application code
if ($rule->evaluate($context)) {
    // Execute action here
    logEvent('Rule matched', $context);
}
```

### No Strict Type Equality

```php
// ❌ Not supported - no === operator
// GraphQL uses standard equality with type coercion

// ✅ Workaround - combine type check with value check
$rule = $gql->parse([
    'age' => [
        'isType' => 'number',
        'eq' => 18
    ]
]);
```

### No Extended Logical Operators

```php
// ❌ Not supported - no XOR, NAND
// Only AND, OR, NOT

// ✅ Workaround - compose with AND/OR/NOT
// XOR(a, b) = (a OR b) AND NOT(a AND b)
$rule = $gql->parse([
    'AND' => [
        [
            'OR' => [
                ['conditionA' => true],
                ['conditionB' => true]
            ]
        ],
        [
            'NOT' => [
                'AND' => [
                    ['conditionA' => true],
                    ['conditionB' => true]
                ]
            ]
        ]
    ]
]);
```

---

## See Also

- [ADR 004: GraphQL Filter DSL](../../adr/004-graphql-filter-dsl.md)
- [Wirefilter DSL Cookbook](wirefilter-dsl.md)
- [DSL Feature Support Matrix](../dsl-feature-matrix.md)
- [Hasura Filter Syntax](https://hasura.io/docs/latest/queries/postgres/filters/)
- [Prisma Filter API](https://www.prisma.io/docs/concepts/components/prisma-client/filtering-and-sorting)

---

## Quick Reference Card

### Comparison Operators
- `eq` - Equal (or implicit)
- `ne` - Not equal
- `gt` - Greater than
- `gte` - Greater than or equal
- `lt` - Less than
- `lte` - Less than or equal
- `in` - In array
- `notIn` - Not in array

### String Operators
- `contains` - Contains substring (case-sensitive)
- `notContains` - Does not contain
- `containsInsensitive` - Contains (case-insensitive)
- `startsWith` - Starts with prefix
- `endsWith` - Ends with suffix
- `match` - Regex pattern match

### Logical Operators (UPPERCASE)
- `AND` - All conditions (or implicit)
- `OR` - Any condition
- `NOT` - Negate condition

### Other Operators
- `isNull` - Check null/not null (true/false)
- `isType` - Type check (string/number/boolean/array/null)

### Syntax Patterns

```javascript
// Implicit equality
{field: value}

// Explicit operator
{field: {operator: value}}

// Range
{field: {gte: min, lte: max}}

// Implicit AND
{field1: value1, field2: value2}

// Explicit logical
{OPERATOR: [condition1, condition2]}

// Nested
{parent: {child: {field: value}}}
```
