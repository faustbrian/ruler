# Wirefilter DSL Cookbook

**Status:** Primary/Recommended DSL
**Complexity:** Moderate
**Best For:** General-purpose filtering, inline arithmetic, developers familiar with expression languages

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Logical Operators](#logical-operators)
4. [String Operations](#string-operations)
5. [Arithmetic & Mathematical Expressions](#arithmetic--mathematical-expressions)
6. [Type Checking & Null Handling](#type-checking--null-handling)
7. [List Membership](#list-membership)
8. [Nested Properties](#nested-properties)
9. [Advanced Patterns](#advanced-patterns)
10. [Action Callbacks](#action-callbacks)
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
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;
use Cline\Ruler\Core\Context;

$rb = new StringRuleBuilder();

// Parse a rule
$rule = $rb->parse('age >= 18 && country == "US"');

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

---

## Basic Comparisons

### Equality

```php
// Equal to (==)
$rule = $rb->parse('status == "active"');
$rule->evaluate(new Context(['status' => 'active'])); // true

// Not equal to (!=)
$rule = $rb->parse('status != "banned"');
$rule->evaluate(new Context(['status' => 'active'])); // true

// Strict equality (===) - type-safe
$rule = $rb->parse('age === 18');
$rule->evaluate(new Context(['age' => 18]));    // true
$rule->evaluate(new Context(['age' => "18"]));  // false (string vs int)

// Strict not equal (!==)
$rule = $rb->parse('verified !== false');
$rule->evaluate(new Context(['verified' => 0]));     // true (0 is not strictly false)
$rule->evaluate(new Context(['verified' => false])); // false
```

### Numeric Comparisons

```php
// Greater than (>)
$rule = $rb->parse('price > 100');
$rule->evaluate(new Context(['price' => 150])); // true

// Greater than or equal (>=)
$rule = $rb->parse('age >= 18');
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 25])); // true

// Less than (<)
$rule = $rb->parse('quantity < 10');
$rule->evaluate(new Context(['quantity' => 5])); // true

// Less than or equal (<=)
$rule = $rb->parse('temperature <= 32');
$rule->evaluate(new Context(['temperature' => 20])); // true
```

### Range Checks

```php
// Between (inclusive)
$rule = $rb->parse('age >= 18 && age <= 65');
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 70])); // false

// Outside range
$rule = $rb->parse('temperature < 0 || temperature > 100');
$rule->evaluate(new Context(['temperature' => -5]));  // true
$rule->evaluate(new Context(['temperature' => 105])); // true
$rule->evaluate(new Context(['temperature' => 50]));  // false
```

---

## Logical Operators

### AND (&&)

```php
// Multiple conditions must be true
$rule = $rb->parse('age >= 18 && country == "US" && verified == true');

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
$rule = $rb->parse('status == "active" || status == "pending"');

$rule->evaluate(new Context(['status' => 'active']));  // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned']));  // false
```

### NOT (!)

```php
// Negate a condition
$rule = $rb->parse('!(status == "banned")');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Negate compound expression
$rule = $rb->parse('!(age < 18 || country == "FR")');
$rule->evaluate(new Context(['age' => 25, 'country' => 'US'])); // true
$rule->evaluate(new Context(['age' => 15, 'country' => 'US'])); // false
```

### Operator Precedence

```php
// Precedence: NOT > AND > OR
// This evaluates as: (active AND (age >= 18)) OR vip
$rule = $rb->parse('status == "active" && age >= 18 || vip == true');

// Use parentheses for clarity
$rule = $rb->parse('(status == "active" && age >= 18) || vip == true');

// Complex nesting
$rule = $rb->parse('
    (age >= 18 && age <= 65) &&
    (country == "US" || country == "CA") &&
    !(status == "banned" || status == "suspended")
');
```

---

## String Operations

### String Comparison

```php
// Case-sensitive equality
$rule = $rb->parse('name == "John"');
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'john'])); // false

// Case-insensitive - use regex
$rule = $rb->parse('name matches "(?i)john"');
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'JOHN'])); // true
```

### Pattern Matching (Regex)

```php
// Email validation
$rule = $rb->parse('email matches "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$"');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true

// Phone number (US format)
$rule = $rb->parse('phone matches "^\\d{3}-\\d{3}-\\d{4}$"');
$rule->evaluate(new Context(['phone' => '555-123-4567'])); // true

// Starts with
$rule = $rb->parse('name matches "^John"');
$rule->evaluate(new Context(['name' => 'John Doe'])); // true

// Ends with
$rule = $rb->parse('email matches "@example\\.com$"');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true

// Contains
$rule = $rb->parse('description matches "important"');
$rule->evaluate(new Context(['description' => 'This is important stuff'])); // true

// Case-insensitive contains
$rule = $rb->parse('description matches "(?i)important"');
$rule->evaluate(new Context(['description' => 'This is IMPORTANT stuff'])); // true
```

### String Length

```php
// Using strlen() function (if implemented)
// Note: Check your implementation for available string functions

// Workaround - compare against known strings
$rule = $rb->parse('zip_code matches "^\\d{5}$"'); // Must be exactly 5 digits
$rule->evaluate(new Context(['zip_code' => '12345'])); // true
```

---

## Arithmetic & Mathematical Expressions

**⭐ UNIQUE FEATURE - Only Wirefilter supports inline arithmetic!**

### Basic Arithmetic

```php
// Addition
$rule = $rb->parse('price + shipping > 100');
$rule->evaluate(new Context(['price' => 75, 'shipping' => 30])); // true

// Subtraction
$rule = $rb->parse('total - discount >= 50');
$rule->evaluate(new Context(['total' => 100, 'discount' => 25])); // true

// Multiplication
$rule = $rb->parse('quantity * unit_price > 500');
$rule->evaluate(new Context(['quantity' => 10, 'unit_price' => 60])); // true

// Division
$rule = $rb->parse('total / items < 20');
$rule->evaluate(new Context(['total' => 100, 'items' => 10])); // true (10 < 20)

// Modulo (remainder)
$rule = $rb->parse('order_number % 2 == 0'); // Even orders
$rule->evaluate(new Context(['order_number' => 42])); // true
```

### Exponentiation

```php
// Power operator (**)
$rule = $rb->parse('radius ** 2 * 3.14159 > 100'); // Area of circle
$rule->evaluate(new Context(['radius' => 6])); // true (113.09 > 100)
```

### Complex Expressions

```php
// Tax calculation
$rule = $rb->parse('(price + shipping) * 1.08 > 100'); // 8% tax
$rule->evaluate(new Context(['price' => 80, 'shipping' => 15])); // true (102.6 > 100)

// Discount logic
$rule = $rb->parse('price - (price * discount_pct / 100) < budget');
$context = new Context([
    'price' => 100,
    'discount_pct' => 20,
    'budget' => 85
]);
$rule->evaluate($context); // true (80 < 85)

// Volume calculation
$rule = $rb->parse('length * width * height > 1000');
$rule->evaluate(new Context(['length' => 10, 'width' => 15, 'height' => 8])); // true (1200)
```

### Order of Operations

```php
// Standard math precedence: ** > * / % > + -
$rule = $rb->parse('2 + 3 * 4'); // 2 + (3 * 4) = 14, not (2 + 3) * 4 = 20

// Use parentheses for clarity
$rule = $rb->parse('(price + shipping) * tax_rate');
$rule = $rb->parse('price * (1 + tax_rate)');
```

### Division by Zero

```php
// Be careful with division
$rule = $rb->parse('total / count > 10');

// This will throw an error if count is 0
$context = new Context(['total' => 100, 'count' => 0]);
// $rule->evaluate($context); // Error!

// Guard against division by zero
$rule = $rb->parse('count > 0 && total / count > 10');
$rule->evaluate(new Context(['total' => 100, 'count' => 0])); // false (short-circuits)
```

---

## Type Checking & Null Handling

### Null Checks

```php
// Check if null
$rule = $rb->parse('deleted_at == null');
$rule->evaluate(new Context(['deleted_at' => null])); // true

// Check if not null
$rule = $rb->parse('email != null');
$rule->evaluate(new Context(['email' => 'user@example.com'])); // true

// Strict null check
$rule = $rb->parse('value === null');
$rule->evaluate(new Context(['value' => null]));  // true
$rule->evaluate(new Context(['value' => 0]));     // false
$rule->evaluate(new Context(['value' => false])); // false
$rule->evaluate(new Context(['value' => '']));    // false
```

### Boolean Values

```php
// Boolean comparison
$rule = $rb->parse('verified == true');
$rule->evaluate(new Context(['verified' => true])); // true

// Strict boolean check
$rule = $rb->parse('verified === true');
$rule->evaluate(new Context(['verified' => true])); // true
$rule->evaluate(new Context(['verified' => 1]));    // false (int vs bool)

// Falsy values
$rule = $rb->parse('active != false');
$rule->evaluate(new Context(['active' => 0]));     // true (0 != false with loose equality)
$rule->evaluate(new Context(['active' => null]));  // true
$rule->evaluate(new Context(['active' => false])); // false

// Strict false check
$rule = $rb->parse('active !== false');
$rule->evaluate(new Context(['active' => 0]));     // true (0 is not strictly false)
$rule->evaluate(new Context(['active' => false])); // false
```

### Type Coercion

```php
// Loose equality uses type coercion
$rule = $rb->parse('age == 18');
$rule->evaluate(new Context(['age' => 18]));   // true
$rule->evaluate(new Context(['age' => "18"])); // true (string coerced to int)

// Strict equality prevents coercion
$rule = $rb->parse('age === 18');
$rule->evaluate(new Context(['age' => 18]));   // true
$rule->evaluate(new Context(['age' => "18"])); // false
```

---

## List Membership

### IN Operator

```php
// Check if value is in array
$rule = $rb->parse('country in ["US", "CA", "UK"]');
$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'FR'])); // false

// Numeric values
$rule = $rb->parse('status_code in [200, 201, 204]');
$rule->evaluate(new Context(['status_code' => 200])); // true
$rule->evaluate(new Context(['status_code' => 404])); // false

// Mixed types
$rule = $rb->parse('value in [1, "two", true, null]');
$rule->evaluate(new Context(['value' => 1]));      // true
$rule->evaluate(new Context(['value' => "two"]));  // true
$rule->evaluate(new Context(['value' => true]));   // true
$rule->evaluate(new Context(['value' => null]));   // true
```

### NOT IN

```php
// Exclude values
$rule = $rb->parse('!(status in ["banned", "suspended", "deleted"])');
$rule->evaluate(new Context(['status' => 'active']));    // true
$rule->evaluate(new Context(['status' => 'banned']));    // false

// Alternative syntax
$rule = $rb->parse('status in ["banned", "suspended"] == false');
```

### Array Contains (Reverse)

```php
// Check if array field contains value
// Note: Syntax depends on your implementation

// Example: tags array contains "premium"
$rule = $rb->parse('"premium" in tags');
$context = new Context(['tags' => ['premium', 'verified', 'featured']]);
$rule->evaluate($context); // true
```

---

## Nested Properties

### Dot Notation

```php
// Access nested object properties
$rule = $rb->parse('user.profile.age >= 18');

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
$rule = $rb->parse('order.shipping.address.country == "US"');

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

### Array Access

```php
// Access array elements by index
$rule = $rb->parse('items[0].price > 100');

$context = new Context([
    'items' => [
        ['price' => 150],
        ['price' => 50]
    ]
]);
$rule->evaluate($context); // true

// Combined with nested objects
$rule = $rb->parse('user.orders[0].total > 1000');
```

---

## Advanced Patterns

### Complex Business Rules

```php
// E-commerce product eligibility
$rule = $rb->parse('
    category == "electronics" &&
    price >= 10 && price <= 500 &&
    in_stock == true &&
    (featured == true || rating >= 4.0) &&
    !(status in ["clearance", "discontinued"])
');

// User access control
$rule = $rb->parse('
    (role == "admin" || role == "moderator") &&
    account_age >= 30 &&
    email_verified == true &&
    !(status in ["banned", "suspended"])
');

// Subscription eligibility
$rule = $rb->parse('
    (subscription_status == "active" || trial_days_left > 0) &&
    payment_method != null &&
    total_spend > 0
');
```

### Dynamic Pricing

```php
// Volume discount
$rule = $rb->parse('
    quantity >= 100 && (price * quantity * 0.9) > minimum_order ||
    quantity >= 50 && (price * quantity * 0.95) > minimum_order ||
    (price * quantity) > minimum_order
');

// Tiered pricing with tax
$rule = $rb->parse('
    (base_price +
     (quantity > 100 ? quantity * 10 : quantity * 15) +
     shipping_cost) * 1.08 <= budget
');
```

### Time-Based Rules

```php
// Using timestamps
$rule = $rb->parse('
    created_at > 1704067200 &&
    expires_at > current_timestamp &&
    last_login > (current_timestamp - 86400)
');

$context = new Context([
    'created_at' => 1704100000,
    'expires_at' => 1735689600,
    'last_login' => time() - 3600,
    'current_timestamp' => time()
]);
```

### Compound Conditions

```php
// Premium feature access
$rule = $rb->parse('
    (
        (subscription_tier == "premium" || subscription_tier == "enterprise") &&
        subscription_expires > current_time
    ) || (
        trial_active == true &&
        trial_expires > current_time &&
        feature_usage < trial_limit
    )
');

// Multi-factor eligibility
$rule = $rb->parse('
    age >= 18 &&
    (
        (country == "US" && state in ["CA", "NY", "TX"]) ||
        (country == "CA" && province in ["ON", "BC", "QC"]) ||
        (country in ["UK", "DE", "FR"])
    ) &&
    verification_score >= 70
');
```

---

## Action Callbacks

**⭐ UNIQUE FEATURE - Only Wirefilter supports action callbacks!**

### Basic Action

```php
$actionExecuted = false;

$rule = $rb->parseWithAction(
    'age >= 18 && country == "US"',
    function(Context $context) use (&$actionExecuted) {
        $actionExecuted = true;
        // Perform action when rule matches
        echo "User {$context['name']} is eligible!\n";
    }
);

$context = new Context(['age' => 25, 'country' => 'US', 'name' => 'John']);
$rule->evaluate($context); // Prints: "User John is eligible!"
// $actionExecuted is now true
```

### Logging

```php
use Psr\Log\LoggerInterface;

$rule = $rb->parseWithAction(
    'error_count > 10',
    function(Context $context, LoggerInterface $logger) {
        $logger->warning('High error count detected', [
            'error_count' => $context['error_count'],
            'user_id' => $context['user_id']
        ]);
    }
);
```

### Database Operations

```php
$rule = $rb->parseWithAction(
    'subscription_expires < current_time',
    function(Context $context, $db) {
        // Deactivate expired subscription
        $db->table('subscriptions')
           ->where('id', $context['subscription_id'])
           ->update(['status' => 'expired']);
    }
);
```

### Event Dispatching

```php
$rule = $rb->parseWithAction(
    'total_purchases > 1000',
    function(Context $context, $eventBus) {
        $eventBus->dispatch(new VipCustomerReached([
            'customer_id' => $context['customer_id'],
            'total_purchases' => $context['total_purchases']
        ]));
    }
);
```

### Notification Systems

```php
$rule = $rb->parseWithAction(
    'stock_level < reorder_threshold',
    function(Context $context, $notifier) {
        $notifier->send([
            'type' => 'low_stock_alert',
            'product_id' => $context['product_id'],
            'current_stock' => $context['stock_level'],
            'threshold' => $context['reorder_threshold']
        ]);
    }
);
```

### Multiple Actions

```php
// Chain multiple actions
$rule = $rb->parseWithAction(
    'high_value == true && fraud_score > 75',
    function(Context $context, $services) {
        // Action 1: Flag for review
        $services['reviewQueue']->add($context['order_id']);

        // Action 2: Notify security team
        $services['notifier']->sendToSecurityTeam($context);

        // Action 3: Log incident
        $services['logger']->critical('High-value fraud alert', [
            'order_id' => $context['order_id'],
            'fraud_score' => $context['fraud_score']
        ]);
    }
);
```

---

## Performance Optimization

### Short-Circuit Evaluation

```php
// Put cheaper checks first
// ✅ Good - check simple field before expensive calculation
$rule = $rb->parse('status == "active" && (price * quantity > 1000)');

// ❌ Bad - expensive calculation happens even if status is inactive
$rule = $rb->parse('(price * quantity > 1000) && status == "active"');

// ✅ Good - fail fast on common conditions
$rule = $rb->parse('
    country == "US" &&
    age >= 18 &&
    complex_calculation > threshold
');
```

### Avoiding Redundant Calculations

```php
// ❌ Bad - calculates total twice
$rule = $rb->parse('
    (price + shipping + tax) > 100 &&
    (price + shipping + tax) < 500
');

// ✅ Good - pre-compute in context
$context = new Context([
    'price' => 75,
    'shipping' => 15,
    'tax' => 12,
    'total' => 102  // Pre-computed
]);
$rule = $rb->parse('total > 100 && total < 500');
```

### Simplifying Complex Rules

```php
// ❌ Complex nested logic
$rule = $rb->parse('
    (a == 1 || a == 2 || a == 3) &&
    (b == 1 || b == 2 || b == 3) &&
    (c == 1 || c == 2 || c == 3)
');

// ✅ Better - use IN operator
$rule = $rb->parse('
    a in [1, 2, 3] &&
    b in [1, 2, 3] &&
    c in [1, 2, 3]
');
```

### Caching Compiled Rules

```php
// Compile once, evaluate many times
$ruleString = 'age >= 18 && country == "US"';
$compiledRule = $rb->parse($ruleString); // Expensive

// Store compiled rule
$cache->set('eligibility_rule', $compiledRule);

// Reuse for multiple evaluations
$compiledRule = $cache->get('eligibility_rule');
$result1 = $compiledRule->evaluate($context1);
$result2 = $compiledRule->evaluate($context2);
```

---

## Common Pitfalls

### String Quoting

```php
// ❌ Wrong - missing quotes around string values
$rule = $rb->parse('country == US'); // Error!

// ✅ Correct - use quotes
$rule = $rb->parse('country == "US"');
```

### Operator Confusion

```php
// ❌ Wrong - using = instead of ==
$rule = $rb->parse('status = "active"'); // Error!

// ✅ Correct
$rule = $rb->parse('status == "active"');

// When you need strict equality
$rule = $rb->parse('age === 18');
```

### Boolean Values

```php
// ❌ Wrong - quoting boolean
$rule = $rb->parse('verified == "true"'); // Compares to string "true"

// ✅ Correct - no quotes for booleans
$rule = $rb->parse('verified == true');
```

### Null Handling

```php
// ❌ Wrong - quoted null
$rule = $rb->parse('deleted_at == "null"'); // Compares to string "null"

// ✅ Correct
$rule = $rb->parse('deleted_at == null');
```

### Regex Escaping

```php
// ❌ Wrong - not escaping special characters
$rule = $rb->parse('email matches ".+@example.com"'); // . matches any char

// ✅ Correct - escape dots
$rule = $rb->parse('email matches ".+@example\\.com"');

// Escaping backslashes in PHP strings
$rule = $rb->parse('phone matches "\\d{3}-\\d{4}"'); // Use double backslash
```

### Missing Parentheses

```php
// ❌ Ambiguous - relies on precedence
$rule = $rb->parse('a && b || c && d');

// ✅ Clear - use parentheses
$rule = $rb->parse('(a && b) || (c && d)');
```

### Field Name Conflicts

```php
// ❌ Field name conflicts with operator
// If you have a field literally named "and", "or", "in", etc.

// ✅ Use different field naming
// Avoid: in, and, or, not, true, false, null, matches
// Use: status, type, role, category instead
```

---

## Real-World Examples

### E-Commerce

#### Product Eligibility

```php
$rule = $rb->parse('
    category == "electronics" &&
    price >= 10 &&
    price <= 5000 &&
    in_stock == true &&
    (
        (featured == true && rating >= 4.0) ||
        (sales_count > 100 && rating >= 4.5)
    ) &&
    !(status in ["discontinued", "recalled"])
');
```

#### Dynamic Shipping

```php
$rule = $rb->parse('
    weight > 0 &&
    (
        (country == "US" && weight <= 50) ||
        (country == "CA" && weight <= 30) ||
        (country in ["UK", "DE", "FR"] && weight <= 20)
    ) &&
    (price + (weight * shipping_rate_per_lb)) <= max_shipping_cost
');
```

#### Promotional Pricing

```php
$rule = $rb->parse('
    (
        (price * quantity >= 500 && discount_pct == 20) ||
        (price * quantity >= 1000 && discount_pct == 30)
    ) &&
    (price * quantity * (1 - discount_pct / 100)) > minimum_order_value
');
```

### User Access Control

#### Admin Privileges

```php
$rule = $rb->parse('
    (role == "admin" || role == "super_admin") &&
    email_verified == true &&
    two_factor_enabled == true &&
    account_age_days >= 30 &&
    !(status in ["suspended", "locked", "pending_review"])
');
```

#### Feature Flags

```php
$rule = $rb->parse('
    (
        user_id in [1, 2, 3, 100, 200] ||
        (beta_tester == true && opt_in_date != null) ||
        (subscription_tier == "enterprise" && feature_flags matches ".*new_dashboard.*")
    ) &&
    environment in ["staging", "production"]
');
```

#### Content Moderation

```php
$rule = $rb->parse('
    (
        report_count >= 5 ||
        (spam_score > 80 && account_age_days < 7) ||
        content matches "(?i)(spam|scam|phishing)"
    ) &&
    !(user_id in trusted_user_ids) &&
    auto_moderation_enabled == true
');
```

### SaaS Applications

#### Subscription Limits

```php
$rule = $rb->parse('
    (
        (plan == "free" && api_calls_this_month < 1000) ||
        (plan == "pro" && api_calls_this_month < 50000) ||
        (plan == "enterprise" && api_calls_this_month < 1000000)
    ) &&
    subscription_status == "active" &&
    payment_failed == false
');
```

#### Usage-Based Billing

```php
$rule = $rb->parse('
    total_storage_gb > included_storage_gb &&
    (total_storage_gb - included_storage_gb) * overage_rate_per_gb +
    base_price <= spending_limit
');
```

### Financial Services

#### Loan Approval

```php
$rule = $rb->parse('
    age >= 18 && age <= 75 &&
    credit_score >= 650 &&
    annual_income >= 30000 &&
    (debt_to_income_ratio < 0.43 || has_cosigner == true) &&
    employment_length_months >= 6 &&
    loan_amount <= (annual_income * 3) &&
    !(bankruptcy_history == true && years_since_bankruptcy < 7)
');
```

#### Fraud Detection

```php
$rule = $rb->parse('
    (
        transaction_amount > 5000 ||
        (transaction_amount > 1000 && velocity_last_hour > 5) ||
        ip_country != billing_country
    ) &&
    (
        fraud_score > 75 ||
        device_fingerprint in known_fraud_devices ||
        billing_address matches ".*P\\.?O\\.? Box.*"
    )
');
```

### Healthcare

#### Patient Eligibility

```php
$rule = $rb->parse('
    age >= 18 &&
    insurance_active == true &&
    insurance_coverage_remaining > procedure_cost &&
    (
        (referral_required == false) ||
        (referral_required == true && referral_date != null &&
         current_date - referral_date <= 90)
    ) &&
    !(condition in excluded_conditions)
');
```

### Gaming

#### Achievement Unlock

```php
$rule = $rb->parse('
    player_level >= 50 &&
    total_playtime_hours >= 100 &&
    boss_defeats >= 20 &&
    rare_items_collected >= 5 &&
    (
        (solo_achievements >= 10) ||
        (multiplayer_wins >= 50 && team_participation_rate >= 0.8)
    )
');
```

#### Matchmaking

```php
$rule = $rb->parse('
    (player_rating >= 1000 && player_rating <= 1500) &&
    (average_match_duration >= 10 && average_match_duration <= 45) &&
    region in ["NA-East", "NA-West", "EU"] &&
    queue_time < 300 &&
    latency < 100
');
```

### IoT & Monitoring

#### Alert Conditions

```php
$rule = $rb->parse('
    (
        temperature > 80 ||
        humidity < 20 ||
        (cpu_usage > 90 && duration_seconds > 300) ||
        memory_available_mb < 512
    ) &&
    alert_cooldown_expired == true &&
    maintenance_mode == false
');
```

---

## Best Practices Summary

1. **Use Strict Equality (===) for Type Safety**: Prevent type coercion bugs
2. **Put Cheap Checks First**: Leverage short-circuit evaluation
3. **Pre-Compute Complex Values**: Move calculations to context when possible
4. **Use Parentheses Liberally**: Make operator precedence explicit
5. **Cache Compiled Rules**: Parse once, evaluate many times
6. **Avoid Reserved Keywords as Field Names**: Don't name fields "and", "or", "in", etc.
7. **Escape Regex Special Characters**: Use `\\.` for literal dots, `\\d` for digits
8. **Quote String Values**: Always use `"..."` for strings
9. **Test Edge Cases**: Null values, empty strings, zero, negative numbers
10. **Document Complex Rules**: Add comments explaining business logic

---

## See Also

- [ADR 001: Wirefilter-style DSL](../../adr/001-wirefilter-style-dsl.md)
- [DSL Feature Support Matrix](dsl-feature-matrix.md)
- [Other DSL Cookbooks](../cookbooks/)
