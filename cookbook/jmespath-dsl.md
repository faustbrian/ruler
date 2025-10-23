# JMESPath DSL Cookbook

**Status:** Specialized DSL
**Complexity:** Advanced
**Best For:** Complex JSON/array filtering, AWS-integrated applications, nested data structures

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Logical Operators](#logical-operators)
4. [Backtick Literals](#backtick-literals)
5. [String Functions](#string-functions)
6. [Array Operations](#array-operations)
7. [Type Checking](#type-checking)
8. [Nested Property Access](#nested-property-access)
9. [Advanced Array Patterns](#advanced-array-patterns)
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
use Cline\Ruler\DSL\JMESPath\JMESPathRuleBuilder;
use Cline\Ruler\Core\Context;

$jmes = new JMESPathRuleBuilder();

// Parse a rule (note the backticks for number literals)
$rule = $jmes->parse('age >= `18` && country == \'US\'');

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

### Why JMESPath?

**JMESPath is the AWS standard** for JSON querying, used in CloudWatch, AWS CLI, and countless services. It's designed for:
- Complex nested JSON structures
- Advanced array filtering and transformations
- Cross-platform compatibility (JavaScript, Python, Go, Ruby, etc.)
- Type-safe JSON operations

**Key Strength:** Best-in-class array operations across all DSLs

**Key Limitation:** No regex support (intentional cross-platform decision)

---

## Basic Comparisons

### Equality

```php
// Equal to (==)
$rule = $jmes->parse('status == \'active\'');
$rule->evaluate(new Context(['status' => 'active'])); // true

// Not equal to (!=)
$rule = $jmes->parse('status != \'banned\'');
$rule->evaluate(new Context(['status' => 'active'])); // true

// IMPORTANT: Backticks for number literals
$rule = $jmes->parse('age == `18`');
$rule->evaluate(new Context(['age' => 18])); // true

// String literals use single quotes (no backticks)
$rule = $jmes->parse('country == \'US\'');
$rule->evaluate(new Context(['country' => 'US'])); // true
```

### Numeric Comparisons

```php
// Greater than (>)
$rule = $jmes->parse('price > `100`');
$rule->evaluate(new Context(['price' => 150])); // true

// Greater than or equal (>=)
$rule = $jmes->parse('age >= `18`');
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 25])); // true

// Less than (<)
$rule = $jmes->parse('quantity < `10`');
$rule->evaluate(new Context(['quantity' => 5])); // true

// Less than or equal (<=)
$rule = $jmes->parse('temperature <= `32`');
$rule->evaluate(new Context(['temperature' => 20])); // true

// Floating point numbers
$rule = $jmes->parse('price >= `99.99`');
$rule->evaluate(new Context(['price' => 149.99])); // true
```

### Range Checks

```php
// Between (inclusive)
$rule = $jmes->parse('age >= `18` && age <= `65`');
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 70])); // false

// Outside range
$rule = $jmes->parse('temperature < `0` || temperature > `100`');
$rule->evaluate(new Context(['temperature' => -5]));  // true
$rule->evaluate(new Context(['temperature' => 105])); // true
$rule->evaluate(new Context(['temperature' => 50]));  // false
```

---

## Logical Operators

**IMPORTANT:** JMESPath uses JavaScript-style operators: `&&`, `||`, `!` (NOT SQL-style `and`, `or`, `not`)

### AND (&&)

```php
// Multiple conditions must be true
$rule = $jmes->parse('age >= `18` && country == \'US\' && verified == `true`');

$valid = new Context([
    'age' => 25,
    'country' => 'US',
    'verified' => true
]);
$rule->evaluate($valid); // true

$invalid = new Context([
    'age' => 25,
    'country' => 'FR',
    'verified' => true
]);
$rule->evaluate($invalid); // false (country fails)
```

### OR (||)

```php
// At least one condition must be true
$rule = $jmes->parse('status == \'active\' || status == \'pending\'');

$rule->evaluate(new Context(['status' => 'active']));  // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned']));  // false
```

### NOT (!)

```php
// Negate a condition
$rule = $jmes->parse('!(status == \'banned\')');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Negate compound expression
$rule = $jmes->parse('!(age < `18` || country == \'FR\')');
$rule->evaluate(new Context(['age' => 25, 'country' => 'US'])); // true
$rule->evaluate(new Context(['age' => 15, 'country' => 'US'])); // false

// Negate function results
$rule = $jmes->parse('!contains(tags, \'banned\')');
$rule->evaluate(new Context(['tags' => ['premium', 'verified']])); // true
```

### Operator Precedence

```php
// Precedence: NOT (!) > AND (&&) > OR (||)
// This evaluates as: (active AND (age >= 18)) OR vip
$rule = $jmes->parse('status == \'active\' && age >= `18` || vip == `true`');

// Use parentheses for clarity
$rule = $jmes->parse('(status == \'active\' && age >= `18`) || vip == `true`');

// Complex nesting
$rule = $jmes->parse('
    (age >= `18` && age <= `65`) &&
    (country == \'US\' || country == \'CA\') &&
    !(status == \'banned\' || status == \'suspended\')
');
```

---

## Backtick Literals

**CRITICAL:** JMESPath requires backticks for number, boolean, and null literals. This ensures unambiguous parsing.

### Number Literals

```php
// ✅ Correct - backticks for numbers
$rule = $jmes->parse('age >= `18`');
$rule = $jmes->parse('price < `99.99`');
$rule = $jmes->parse('count == `0`');
$rule = $jmes->parse('temperature == `-10`'); // Negative numbers

// ❌ Wrong - no backticks (will be treated as field reference)
$rule = $jmes->parse('age >= 18'); // ERROR: treats "18" as field name
```

### Boolean Literals

```php
// ✅ Correct - backticks for booleans
$rule = $jmes->parse('verified == `true`');
$rule = $jmes->parse('active != `false`');

// ❌ Wrong - no backticks
$rule = $jmes->parse('verified == true'); // ERROR: treats "true" as field name
```

### Null Literals

```php
// ✅ Correct - backticks for null
$rule = $jmes->parse('deleted_at == `null`');
$rule = $jmes->parse('optional_field != `null`');

// ❌ Wrong - no backticks
$rule = $jmes->parse('deleted_at == null'); // ERROR: treats "null" as field name
```

### String Literals

```php
// ✅ Correct - single quotes for strings (NO backticks)
$rule = $jmes->parse('country == \'US\'');
$rule = $jmes->parse('status != \'banned\'');

// Double quotes also work in some implementations
$rule = $jmes->parse('country == "US"');

// Empty string
$rule = $jmes->parse('name != \'\'');
```

### Array Literals

```php
// Arrays for contains() function
$rule = $jmes->parse('contains(`["US", "CA", "UK"]`, country)');

$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'FR'])); // false

// Mixed type arrays
$rule = $jmes->parse('contains(`[1, "two", true, null]`, value)');
```

---

## String Functions

**IMPORTANT:** JMESPath has NO regex support. This is intentional for cross-platform compatibility. Use literal string functions instead.

### contains() Function

```php
// Substring search (case-sensitive)
$rule = $jmes->parse('contains(email, \'@example.com\')');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.org']));    // false

// Array membership (note argument order)
$rule = $jmes->parse('contains(`["US", "CA", "UK"]`, country)');
$rule->evaluate(new Context(['country' => 'US'])); // true

// Checking if array field contains value
$rule = $jmes->parse('contains(tags, \'premium\')');
$context = new Context(['tags' => ['premium', 'verified', 'featured']]);
$rule->evaluate($context); // true
```

### starts_with() Function

```php
// Check string prefix (case-sensitive)
$rule = $jmes->parse('starts_with(email, \'admin\')');
$rule->evaluate(new Context(['email' => 'admin@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@example.com']));  // false

// URL validation
$rule = $jmes->parse('starts_with(url, \'https://\')');
$rule->evaluate(new Context(['url' => 'https://example.com'])); // true
$rule->evaluate(new Context(['url' => 'http://example.com']));  // false

// Prefix matching
$rule = $jmes->parse('starts_with(name, \'John\')');
$rule->evaluate(new Context(['name' => 'John Doe']));   // true
$rule->evaluate(new Context(['name' => 'Jane Doe']));   // false
```

### ends_with() Function

```php
// Check string suffix (case-sensitive)
$rule = $jmes->parse('ends_with(filename, \'.pdf\')');
$rule->evaluate(new Context(['filename' => 'document.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'image.jpg']));    // false

// Email domain validation
$rule = $jmes->parse('ends_with(email, \'@example.com\')');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.org']));    // false

// File extension check
$rule = $jmes->parse('ends_with(path, \'.js\') || ends_with(path, \'.ts\')');
$rule->evaluate(new Context(['path' => 'app.js']));  // true
$rule->evaluate(new Context(['path' => 'app.ts']));  // true
$rule->evaluate(new Context(['path' => 'app.css'])); // false
```

### String Combination Patterns

```php
// Domain email validation
$rule = $jmes->parse('
    contains(email, \'@\') &&
    ends_with(email, \'.com\') &&
    !starts_with(email, \'spam\')
');

// Secure URL check
$rule = $jmes->parse('
    starts_with(url, \'https://\') &&
    !contains(url, \'localhost\')
');

// Valid filename check
$rule = $jmes->parse('
    !starts_with(filename, \'.\') &&
    (ends_with(filename, \'.pdf\') || ends_with(filename, \'.docx\'))
');
```

### Case Sensitivity

```php
// JMESPath string functions are CASE-SENSITIVE

// ❌ This will NOT match
$rule = $jmes->parse('contains(email, \'EXAMPLE\')');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // false

// ✅ Workaround: normalize data before evaluation
$context = new Context(['email' => strtolower('User@EXAMPLE.com')]);
$rule->evaluate($context); // Now can match with lowercase
```

---

## Array Operations

**⭐ JMESPath's SUPERPOWER** - Best-in-class array operations across all DSLs

### length() Function

```php
// Array length
$rule = $jmes->parse('length(tags) > `3`');
$rule->evaluate(new Context(['tags' => ['a', 'b', 'c', 'd']])); // true
$rule->evaluate(new Context(['tags' => ['a', 'b']]));           // false

// Empty array check
$rule = $jmes->parse('length(items) == `0`');
$rule->evaluate(new Context(['items' => []])); // true

// String length
$rule = $jmes->parse('length(password) >= `8`');
$rule->evaluate(new Context(['password' => 'secret123'])); // true (length 9)
```

### max() Function

```php
// Maximum value in array
$rule = $jmes->parse('max(scores) > `90`');
$rule->evaluate(new Context(['scores' => [75, 88, 95, 82]])); // true (max is 95)
$rule->evaluate(new Context(['scores' => [75, 88, 82]]));     // false (max is 88)

// Find highest price
$rule = $jmes->parse('max(prices) <= `100`');
$rule->evaluate(new Context(['prices' => [25, 50, 75, 99]])); // true
```

### min() Function

```php
// Minimum value in array
$rule = $jmes->parse('min(prices) >= `10`');
$rule->evaluate(new Context(['prices' => [15, 25, 35]])); // true (min is 15)
$rule->evaluate(new Context(['prices' => [5, 25, 35]]));  // false (min is 5)

// Lowest score validation
$rule = $jmes->parse('min(scores) > `50`');
$rule->evaluate(new Context(['scores' => [60, 70, 80]])); // true
```

### sum() Function

```php
// Sum of array values
$rule = $jmes->parse('sum(quantities) > `100`');
$rule->evaluate(new Context(['quantities' => [30, 40, 50]])); // true (sum is 120)

// Total validation
$rule = $jmes->parse('sum(items) == total');
$context = new Context([
    'items' => [10, 20, 30],
    'total' => 60
]);
$rule->evaluate($context); // true
```

### avg() Function

```php
// Average of array values
$rule = $jmes->parse('avg(scores) >= `75`');
$rule->evaluate(new Context(['scores' => [70, 80, 90]])); // true (avg is 80)
$rule->evaluate(new Context(['scores' => [60, 70, 80]])); // false (avg is 70)

// Grade threshold
$rule = $jmes->parse('avg(grades) > `3.5`');
$rule->evaluate(new Context(['grades' => [3.8, 3.9, 4.0]])); // true
```

### Array Indexing

```php
// Access array element by index (zero-based)
$rule = $jmes->parse('items[0].price > `100`');

$context = new Context([
    'items' => [
        ['price' => 150],
        ['price' => 50]
    ]
]);
$rule->evaluate($context); // true

// First element check
$rule = $jmes->parse('tags[0] == \'featured\'');
$rule->evaluate(new Context(['tags' => ['featured', 'premium']])); // true

// Last element (negative indexing may not be supported)
$rule = $jmes->parse('users[2].status == \'active\'');
```

---

## Type Checking

JMESPath includes a powerful `type()` function that returns the JSON type as a string.

### type() Function

```php
// Check if field is a number
$rule = $jmes->parse('type(age) == \'number\'');
$rule->evaluate(new Context(['age' => 25]));   // true
$rule->evaluate(new Context(['age' => '25'])); // false (string)

// Check if field is a string
$rule = $jmes->parse('type(name) == \'string\'');
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 123]));    // false

// Check if field is a boolean
$rule = $jmes->parse('type(verified) == \'boolean\'');
$rule->evaluate(new Context(['verified' => true])); // true
$rule->evaluate(new Context(['verified' => 1]));    // false

// Check if field is an array
$rule = $jmes->parse('type(tags) == \'array\'');
$rule->evaluate(new Context(['tags' => ['a', 'b']])); // true
$rule->evaluate(new Context(['tags' => 'a,b']));      // false

// Check if field is an object
$rule = $jmes->parse('type(user) == \'object\'');
$rule->evaluate(new Context(['user' => ['name' => 'John']])); // true

// Check if field is null
$rule = $jmes->parse('type(deleted_at) == \'null\'');
$rule->evaluate(new Context(['deleted_at' => null])); // true
```

### Type Validation Patterns

```php
// Ensure value is number before comparison
$rule = $jmes->parse('type(age) == \'number\' && age >= `18`');

// Validate array before length check
$rule = $jmes->parse('type(tags) == \'array\' && length(tags) > `0`');

// Strict type equality workaround (since no === operator)
$rule = $jmes->parse('value == `42` && type(value) == \'number\'');
$rule->evaluate(new Context(['value' => 42]));   // true
$rule->evaluate(new Context(['value' => '42'])); // false

// Optional field handling
$rule = $jmes->parse('type(email) == \'null\' || contains(email, \'@\')');
$rule->evaluate(new Context(['email' => null]));             // true
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
```

### not_null() Function

```php
// Check if value is not null
$rule = $jmes->parse('not_null(email)');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => null]));               // false

// Required field validation
$rule = $jmes->parse('not_null(name) && not_null(email) && not_null(phone)');

// Optional vs required fields
$rule = $jmes->parse('
    not_null(required_field) &&
    (type(optional_field) == \'null\' || length(optional_field) > `0`)
');
```

---

## Nested Property Access

JMESPath excels at navigating deep JSON structures with intuitive dot notation.

### Basic Dot Notation

```php
// Access nested object properties
$rule = $jmes->parse('user.profile.age >= `18`');

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
$rule = $jmes->parse('order.shipping.address.country == \'US\'');

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

// Very deep structures
$rule = $jmes->parse('app.config.database.connections.mysql.host == \'localhost\'');
```

### Array of Objects

```php
// Access property of first array element
$rule = $jmes->parse('orders[0].total > `100`');

$context = new Context([
    'orders' => [
        ['id' => 1, 'total' => 150],
        ['id' => 2, 'total' => 50]
    ]
]);
$rule->evaluate($context); // true

// Nested array access
$rule = $jmes->parse('users[0].addresses[0].city == \'New York\'');
```

### Combined Patterns

```php
// Dot notation + array indexing + functions
$rule = $jmes->parse('length(user.permissions) >= `3`');

$context = new Context([
    'user' => [
        'permissions' => ['read', 'write', 'delete', 'admin']
    ]
]);
$rule->evaluate($context); // true

// Multiple nested paths
$rule = $jmes->parse('
    customer.profile.verified == `true` &&
    customer.subscription.status == \'active\' &&
    customer.billing.payment_method != `null`
');
```

---

## Advanced Array Patterns

**⭐ THIS IS WHERE JMESPATH SHINES** - Showcasing the most powerful array operations

### Array Filtering with [?condition]

```php
// Filter array and check if any match
$rule = $jmes->parse('length(orders[?total > `100`]) > `0`');

$context = new Context([
    'orders' => [
        ['id' => 1, 'total' => 150],
        ['id' => 2, 'total' => 50],
        ['id' => 3, 'total' => 200]
    ]
]);
$rule->evaluate($context); // true (2 orders have total > 100)

// Check if ALL orders meet criteria (filtered count equals total count)
$rule = $jmes->parse('length(orders[?total > `100`]) == length(orders)');
$rule->evaluate($context); // false (not all orders > 100)

// Find orders by status
$rule = $jmes->parse('length(orders[?status == \'pending\']) >= `3`');
```

### Current Element Reference (@)

```php
// Use @ to reference current element in filter
$rule = $jmes->parse('length(products[?@.price > `50` && @.inStock == `true`]) > `0`');

$context = new Context([
    'products' => [
        ['name' => 'Widget', 'price' => 75, 'inStock' => true],
        ['name' => 'Gadget', 'price' => 25, 'inStock' => true],
        ['name' => 'Tool', 'price' => 100, 'inStock' => false]
    ]
]);
$rule->evaluate($context); // true (Widget matches)

// Complex filtering
$rule = $jmes->parse('orders[?@.total > `100` && @.status == \'completed\'] | length(@) >= `5`');
```

### Projection and Filtering

```php
// Filter array, then apply function to result
$rule = $jmes->parse('max(products[?category == \'electronics\'].price) < `1000`');

$context = new Context([
    'products' => [
        ['name' => 'Laptop', 'category' => 'electronics', 'price' => 899],
        ['name' => 'Phone', 'category' => 'electronics', 'price' => 699],
        ['name' => 'Desk', 'category' => 'furniture', 'price' => 299]
    ]
]);
$rule->evaluate($context); // true (max electronics price is 899)

// Sum filtered values
$rule = $jmes->parse('sum(transactions[?status == \'completed\'].amount) > `10000`');
```

### Nested Array Filtering

```php
// Filter nested arrays
$rule = $jmes->parse('length(users[?length(permissions[?@ == \'admin\']) > `0`]) > `0`');

$context = new Context([
    'users' => [
        ['name' => 'John', 'permissions' => ['read', 'write']],
        ['name' => 'Jane', 'permissions' => ['read', 'write', 'admin']],
        ['name' => 'Bob', 'permissions' => ['read']]
    ]
]);
$rule->evaluate($context); // true (Jane has admin permission)
```

### Multi-Condition Array Filters

```php
// Complex product eligibility
$rule = $jmes->parse('
    length(
        products[?
            @.category == \'electronics\' &&
            @.price >= `10` &&
            @.price <= `500` &&
            @.inStock == `true` &&
            (@.featured == `true` || @.rating >= `4.0`)
        ]
    ) > `0`
');

// High-value customer filter
$rule = $jmes->parse('
    length(
        customers[?
            @.lifetime_value > `10000` &&
            @.subscription_status == \'active\' &&
            length(@.orders[?@.total > `500`]) >= `3`
        ]
    ) >= `10`
');
```

### Array Contains Patterns

```php
// Check if any user has specific role
$rule = $jmes->parse('length(users[?contains(roles, \'admin\')]) > `0`');

$context = new Context([
    'users' => [
        ['name' => 'John', 'roles' => ['user', 'editor']],
        ['name' => 'Jane', 'roles' => ['user', 'admin', 'editor']],
    ]
]);
$rule->evaluate($context); // true (Jane has admin role)

// Premium users with specific tag
$rule = $jmes->parse('
    length(users[?
        contains(tags, \'premium\') &&
        @.verified == `true`
    ]) >= `100`
');
```

### max_by() and min_by() Functions

```php
// Find maximum by specific field
$rule = $jmes->parse('max_by(orders, &total).total > `1000`');

$context = new Context([
    'orders' => [
        ['id' => 1, 'total' => 150],
        ['id' => 2, 'total' => 500],
        ['id' => 3, 'total' => 1200]
    ]
]);
$rule->evaluate($context); // true (highest order is 1200)

// Find minimum by specific field
$rule = $jmes->parse('min_by(products, &price).price >= `10`');

// Most recent order validation
$rule = $jmes->parse('max_by(orders, &created_at).status == \'completed\'');
```

### Flattening and Complex Queries

```php
// Flatten nested arrays and apply filters
$rule = $jmes->parse('length([].orders[].items[]) > `50`');

$context = new Context([
    [
        'orders' => [
            ['items' => ['a', 'b', 'c']],
            ['items' => ['d', 'e']]
        ]
    ],
    [
        'orders' => [
            ['items' => ['f', 'g', 'h', 'i']]
        ]
    ]
]);
// Flattens all items into single array, then checks length

// Count all line items across all orders
$rule = $jmes->parse('sum(orders[].lineItems[].quantity) > `100`');
```

---

## Performance Optimization

### Short-Circuit Evaluation

```php
// Put cheaper checks first
// ✅ Good - check simple field before complex array operation
$rule = $jmes->parse('status == \'active\' && length(orders[?total > `1000`]) > `0`');

// ❌ Bad - expensive array filter happens even if status is inactive
$rule = $jmes->parse('length(orders[?total > `1000`]) > `0` && status == \'active\'');

// ✅ Good - fail fast on common conditions
$rule = $jmes->parse('
    country == \'US\' &&
    age >= `18` &&
    length(purchases[?amount > `100`]) >= `5`
');
```

### Avoiding Redundant Filtering

```php
// ❌ Bad - filters same array twice
$rule = $jmes->parse('
    length(orders[?total > `100`]) > `0` &&
    max(orders[?total > `100`].total) < `1000`
');

// ✅ Good - pre-compute in context
$context = new Context([
    'high_value_orders' => array_filter($orders, fn($o) => $o['total'] > 100)
]);
$rule = $jmes->parse('
    length(high_value_orders) > `0` &&
    max(high_value_orders[].total) < `1000`
');
```

### Simplifying Complex Expressions

```php
// ❌ Complex nested logic
$rule = $jmes->parse('
    (status == \'a\' || status == \'b\' || status == \'c\') &&
    (type == \'x\' || type == \'y\' || type == \'z\')
');

// ✅ Better - use contains with array literals
$rule = $jmes->parse('
    contains(`["a", "b", "c"]`, status) &&
    contains(`["x", "y", "z"]`, type)
');
```

### Pre-Computing Array Operations

```php
// ❌ Expensive array operations in rule
$rule = $jmes->parse('
    length(orders[?total > `100` && status == \'completed\']) >= `10` &&
    sum(orders[?status == \'completed\'].total) > `50000`
');

// ✅ Better - pre-compute statistics
$completedOrders = array_filter($orders, fn($o) => $o['status'] === 'completed');
$highValueCompleted = array_filter($completedOrders, fn($o) => $o['total'] > 100);

$context = new Context([
    'high_value_completed_count' => count($highValueCompleted),
    'total_completed_revenue' => array_sum(array_column($completedOrders, 'total'))
]);

$rule = $jmes->parse('
    high_value_completed_count >= `10` &&
    total_completed_revenue > `50000`
');
```

### Caching Compiled Rules

```php
// Compile once, evaluate many times
$ruleString = 'age >= `18` && contains(permissions, \'admin\')';
$compiledRule = $jmes->parse($ruleString); // Expensive

// Store compiled rule (if using cache)
$cache->set('admin_eligibility_rule', $compiledRule);

// Reuse for multiple evaluations
$compiledRule = $cache->get('admin_eligibility_rule');
$result1 = $compiledRule->evaluate($context1);
$result2 = $compiledRule->evaluate($context2);
$result3 = $compiledRule->evaluate($context3);
```

---

## Common Pitfalls

### Forgetting Backticks for Literals

```php
// ❌ Wrong - missing backticks for number
$rule = $jmes->parse('age >= 18'); // ERROR: "18" treated as field name

// ✅ Correct - use backticks
$rule = $jmes->parse('age >= `18`');

// ❌ Wrong - missing backticks for boolean
$rule = $jmes->parse('verified == true'); // ERROR: "true" treated as field name

// ✅ Correct - use backticks
$rule = $jmes->parse('verified == `true`');
```

### Using Backticks for Strings

```php
// ❌ Wrong - backticks around strings
$rule = $jmes->parse('country == `US`'); // ERROR: invalid syntax

// ✅ Correct - single quotes for strings
$rule = $jmes->parse('country == \'US\'');

// ✅ Double quotes also work
$rule = $jmes->parse('country == "US"');
```

### Wrong Logical Operator Syntax

```php
// ❌ Wrong - using SQL-style operators
$rule = $jmes->parse('age >= `18` and country == \'US\''); // ERROR
$rule = $jmes->parse('status == \'active\' or vip == `true`'); // ERROR
$rule = $jmes->parse('not (age < `18`)'); // ERROR

// ✅ Correct - use JavaScript-style operators
$rule = $jmes->parse('age >= `18` && country == \'US\'');
$rule = $jmes->parse('status == \'active\' || vip == `true`');
$rule = $jmes->parse('!(age < `18`)');
```

### Attempting Arithmetic Operations

```php
// ❌ Wrong - inline arithmetic not supported
$rule = $jmes->parse('price + shipping > `100`'); // ERROR: not in JMESPath spec

// ✅ Correct - pre-compute values
$context = new Context([
    'price' => 75,
    'shipping' => 30,
    'total' => 105  // Pre-computed
]);
$rule = $jmes->parse('total > `100`');
```

### Attempting Regex

```php
// ❌ Wrong - JMESPath has no regex support
$rule = $jmes->parse('email matches "^[a-z]+@[a-z]+\.com$"'); // ERROR

// ✅ Correct - use string functions
$rule = $jmes->parse('
    contains(email, \'@\') &&
    ends_with(email, \'.com\')
');

// ✅ Alternative - validate in application code before rule evaluation
if (preg_match('/^[a-z]+@[a-z]+\.com$/', $email)) {
    $context = new Context(['email_valid' => true, 'email' => $email]);
    $rule = $jmes->parse('email_valid == `true`');
}
```

### contains() Argument Order

```php
// ❌ Wrong - reversed arguments for array membership
$rule = $jmes->parse('contains(country, `["US", "CA", "UK"]`)'); // Wrong order

// ✅ Correct - array first, value second
$rule = $jmes->parse('contains(`["US", "CA", "UK"]`, country)');

// For string contains, it's: contains(haystack, needle)
$rule = $jmes->parse('contains(email, \'@example.com\')'); // Correct
```

### No Strict Equality Operator

```php
// ❌ Not supported - strict equality operator doesn't exist
$rule = $jmes->parse('value === `42`'); // ERROR: not in JMESPath spec

// ✅ Workaround - combine equality with type check
$rule = $jmes->parse('value == `42` && type(value) == \'number\'');
$rule->evaluate(new Context(['value' => 42]));   // true
$rule->evaluate(new Context(['value' => '42'])); // false (string)
```

### Nested Quotes

```php
// ❌ Wrong - quote escaping issues
$rule = $jmes->parse('message == 'He said "hello"''); // Syntax error

// ✅ Correct - use different quotes or escape
$rule = $jmes->parse('message == "He said \'hello\'"');
$rule = $jmes->parse('message == \'He said "hello"\'');
```

---

## Real-World Examples

### E-Commerce

#### Product Catalog Filtering

```php
// Premium electronics eligibility
$rule = $jmes->parse('
    category == \'electronics\' &&
    price >= `10` &&
    price <= `5000` &&
    inStock == `true` &&
    (
        (featured == `true` && rating >= `4.0`) ||
        (sales_count > `100` && rating >= `4.5`)
    ) &&
    !contains(`["discontinued", "recalled"]`, status)
');

$context = new Context([
    'category' => 'electronics',
    'price' => 299.99,
    'inStock' => true,
    'featured' => true,
    'rating' => 4.7,
    'status' => 'active',
    'sales_count' => 150
]);
$rule->evaluate($context); // true
```

#### Cart Validation

```php
// Validate shopping cart
$rule = $jmes->parse('
    length(items) > `0` &&
    sum(items[].quantity) <= `50` &&
    sum(items[].price * items[].quantity) >= `25` &&
    length(items[?quantity > `10`]) == `0`
');

// Not possible due to no arithmetic - need workaround:
$items = [
    ['name' => 'Widget', 'price' => 10, 'quantity' => 2],
    ['name' => 'Gadget', 'price' => 25, 'quantity' => 1]
];

// Pre-compute totals
$context = new Context([
    'items' => $items,
    'total_items' => array_sum(array_column($items, 'quantity')),
    'subtotal' => array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items))
]);

$rule = $jmes->parse('
    length(items) > `0` &&
    total_items <= `50` &&
    subtotal >= `25` &&
    length(items[?quantity > `10`]) == `0`
');
```

#### Inventory Management

```php
// Low stock alert for popular items
$rule = $jmes->parse('
    stock_level < reorder_threshold &&
    (
        avg(sales_last_30_days) > `10` ||
        contains(tags, \'high-demand\')
    ) &&
    status == \'active\'
');

// Multi-warehouse availability
$rule = $jmes->parse('
    sum(warehouses[].stock) > `0` &&
    length(warehouses[?stock >= `10` && shipping_enabled == `true`]) > `0`
');
```

### User Access Control

#### Admin Privileges

```php
// Full admin access requirements
$rule = $jmes->parse('
    (role == \'admin\' || role == \'super_admin\') &&
    email_verified == `true` &&
    two_factor_enabled == `true` &&
    account_age_days >= `30` &&
    !contains(`["suspended", "locked", "pending_review"]`, status)
');

$context = new Context([
    'role' => 'admin',
    'email_verified' => true,
    'two_factor_enabled' => true,
    'account_age_days' => 45,
    'status' => 'active'
]);
$rule->evaluate($context); // true
```

#### Permission-Based Access

```php
// Feature access with permission checks
$rule = $jmes->parse('
    contains(permissions, \'feature.advanced_analytics\') &&
    subscription_tier == \'enterprise\' &&
    length(users[?contains(permissions, \'admin\')]) >= `1`
');

// Role hierarchy validation
$rule = $jmes->parse('
    contains(`["owner", "admin", "editor"]`, role) ||
    (
        role == \'contributor\' &&
        contains(granted_permissions, \'write\')
    )
');
```

#### Content Moderation

```php
// Auto-flag suspicious content
$rule = $jmes->parse('
    (
        report_count >= `5` ||
        (spam_score > `80` && account_age_days < `7`) ||
        contains(content, \'spam\') ||
        contains(content, \'scam\')
    ) &&
    !contains(trusted_user_ids, user_id) &&
    auto_moderation_enabled == `true`
');

// Community guidelines check
$rule = $jmes->parse('
    length(violations[?severity == \'high\']) == `0` &&
    length(violations[?created_at > recent_threshold]) < `3` &&
    user_reputation >= `50`
');
```

### SaaS Applications

#### Subscription Limits

```php
// API rate limiting by tier
$rule = $jmes->parse('
    (
        (plan == \'free\' && api_calls_this_month < `1000`) ||
        (plan == \'pro\' && api_calls_this_month < `50000`) ||
        (plan == \'enterprise\')
    ) &&
    subscription_status == \'active\' &&
    payment_failed == `false`
');

// Feature access tiers
$rule = $jmes->parse('
    (
        contains(`["pro", "enterprise"]`, plan) ||
        (plan == \'free\' && beta_features_enabled == `true`)
    ) &&
    feature_flags[\'advanced_export\'] == `true`
');
```

#### Usage Monitoring

```php
// Quota exceeded alert
$rule = $jmes->parse('
    (
        (type(quota_limit) == \'number\' && current_usage >= quota_limit) ||
        (current_usage > soft_limit && days_until_renewal < `3`)
    ) &&
    notifications_enabled == `true`
');

// Storage limit validation
$rule = $jmes->parse('
    total_storage_gb <= included_storage_gb ||
    (
        overage_allowed == `true` &&
        total_storage_gb <= max_storage_gb
    )
');
```

#### Multi-Tenant Rules

```php
// Tenant-specific feature access
$rule = $jmes->parse('
    tenant.subscription.tier == \'enterprise\' &&
    contains(tenant.enabled_features, feature_name) &&
    length(tenant.users[?status == \'active\']) <= tenant.user_limit
');

// Cross-tenant data access validation
$rule = $jmes->parse('
    user.tenant_id == resource.tenant_id ||
    (
        contains(user.permissions, \'cross_tenant_access\') &&
        contains(user.accessible_tenant_ids, resource.tenant_id)
    )
');
```

### Financial Services

#### Transaction Validation

```php
// High-value transaction approval
$rule = $jmes->parse('
    amount <= daily_limit &&
    length(transactions_today[?amount > `1000`]) < `5` &&
    account_verified == `true` &&
    !contains(blocked_countries, recipient_country)
');

// Fraud detection triggers
$rule = $jmes->parse('
    (
        amount > `5000` ||
        (amount > `1000` && length(transactions_last_hour) > `5`) ||
        ip_country != billing_country
    ) &&
    (
        fraud_score > `75` ||
        contains(known_fraud_devices, device_fingerprint)
    )
');
```

#### Credit Scoring

```php
// Loan eligibility with nested criteria
$rule = $jmes->parse('
    age >= `18` && age <= `75` &&
    credit_score >= `650` &&
    annual_income >= `30000` &&
    (
        debt_to_income_ratio < `0.43` ||
        has_cosigner == `true`
    ) &&
    employment_length_months >= `6` &&
    !contains(`["bankruptcy", "foreclosure"]`, credit_history_flags)
');

// Premium account qualification
$rule = $jmes->parse('
    (
        avg(monthly_balances) >= `10000` ||
        sum(yearly_deposits) >= `50000`
    ) &&
    account_age_months >= `12` &&
    length(overdrafts_last_year) == `0`
');
```

### Healthcare

#### Patient Eligibility

```php
// Procedure eligibility check
$rule = $jmes->parse('
    age >= `18` &&
    insurance_active == `true` &&
    insurance_coverage_remaining > procedure_cost &&
    (
        referral_required == `false` ||
        (
            type(referral_date) != \'null\' &&
            days_since_referral <= `90`
        )
    ) &&
    !contains(excluded_conditions, primary_diagnosis)
');

// Medication authorization
$rule = $jmes->parse('
    contains(approved_medications, medication_id) &&
    dosage <= max_dosage &&
    !contains(patient.allergies, medication.active_ingredient) &&
    length(patient.current_medications[?
        contains(contraindications, medication_id)
    ]) == `0`
');
```

### Gaming

#### Achievement Unlock

```php
// Rare achievement requirements
$rule = $jmes->parse('
    player_level >= `50` &&
    total_playtime_hours >= `100` &&
    length(achievements[?rarity == \'legendary\']) >= `5` &&
    (
        length(solo_achievements) >= `10` ||
        (
            multiplayer_wins >= `50` &&
            team_participation_rate >= `0.8`
        )
    )
');

// Skill-based progression
$rule = $jmes->parse('
    avg(recent_match_scores) >= `75` &&
    max(recent_match_scores) >= `90` &&
    length(recent_matches[?outcome == \'win\']) >= length(recent_matches) / `2`
');
```

#### Matchmaking

```php
// Competitive matchmaking
$rule = $jmes->parse('
    (player_rating >= `1000` && player_rating <= `1500`) &&
    avg(match_durations) >= `10` && avg(match_durations) <= `45` &&
    contains(`["NA-East", "NA-West", "EU"]`, region) &&
    queue_time < `300` &&
    latency < `100` &&
    length(party_members[?player_rating < `900` || player_rating > `1600`]) == `0`
');
```

### IoT & Monitoring

#### Alert Conditions

```php
// Server health monitoring
$rule = $jmes->parse('
    (
        cpu_usage > `90` ||
        memory_used_percent > `95` ||
        disk_used_percent > `90` ||
        (response_time_ms > `1000` && duration_seconds > `300`)
    ) &&
    alert_cooldown_expired == `true` &&
    maintenance_mode == `false` &&
    !contains(ignored_alert_types, alert_type)
');

// Multi-sensor validation
$rule = $jmes->parse('
    length(sensors[?status == \'active\']) >= `3` &&
    avg(sensors[?type == \'temperature\'].value) > `80` &&
    max(sensors[?type == \'humidity\'].value) < `20`
');
```

#### Anomaly Detection

```php
// Detect unusual patterns
$rule = $jmes->parse('
    (
        current_value > avg(historical_values) * `2` ||
        current_value < avg(historical_values) / `2`
    ) &&
    abs(current_value - avg(recent_values)) > threshold &&
    length(anomalies_last_hour) < `5`
');

// Equipment failure prediction
$rule = $jmes->parse('
    vibration_level > normal_threshold &&
    temperature > normal_temp &&
    length(error_logs_last_24h) >= `10` &&
    last_maintenance_days > `90`
');
```

---

## Best Practices Summary

1. **Always Use Backticks for Literals**: Numbers, booleans, and null MUST have backticks
2. **Use JavaScript-Style Operators**: `&&`, `||`, `!` (NOT `and`, `or`, `not`)
3. **No Regex - Use String Functions**: `contains()`, `starts_with()`, `ends_with()`
4. **Pre-Compute Arithmetic**: JMESPath has no inline math - calculate before evaluation
5. **Leverage Array Operations**: This is JMESPath's superpower - use filtering, projections, aggregations
6. **Put Cheap Checks First**: Short-circuit evaluation saves computation
7. **Type-Check When Necessary**: Use `type()` function for strict type validation
8. **Cache Compiled Rules**: Parse once, evaluate many times
9. **Pre-Compute Complex Array Stats**: Avoid expensive filtering in rules
10. **Use Nested Dot Notation**: JMESPath excels at deep JSON navigation

---

## Limitations vs Other DSLs

### What JMESPath CANNOT Do

**❌ Inline Arithmetic**
- No math expressions: `price + shipping > 100`
- **Workaround:** Pre-compute totals before evaluation

**❌ Regex Pattern Matching**
- No regex support: `email matches "^[a-z]+@.*"`
- **Workaround:** Use `contains()`, `starts_with()`, `ends_with()`

**❌ Strict Equality Operator**
- No `===` operator for type-safe comparison
- **Workaround:** `value == \`42\` && type(value) == 'number'`

**❌ Action Callbacks**
- Cannot execute code on rule match (Wirefilter-only feature)
- **Workaround:** Handle actions in application code after evaluation

**❌ Date Operations**
- No date-specific operators (MongoDB Query DSL has this)
- **Workaround:** Convert dates to timestamps or strings

### What JMESPath EXCELS At

**✅ Array Operations** - Best-in-class filtering, projections, aggregations
**✅ Nested JSON Navigation** - Deep object traversal with intuitive syntax
**✅ Type Checking** - Comprehensive `type()` function
**✅ Cross-Platform** - AWS standard, multi-language support
**✅ JSON-Native** - Designed specifically for JSON data structures

---

## See Also

- [ADR 006: JMESPath DSL](../../adr/006-jmespath-dsl.md)
- [DSL Feature Support Matrix](../dsl-feature-matrix.md)
- [Wirefilter DSL Cookbook](wirefilter-dsl.md) - For arithmetic and action callbacks
- [JMESPath Official Specification](https://jmespath.org/specification.html)
- [JMESPath Tutorial](https://jmespath.org/tutorial.html)
