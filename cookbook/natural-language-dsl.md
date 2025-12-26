# Natural Language DSL Cookbook

**Status:** Alternative DSL (Business User Focused)
**Complexity:** Low
**Best For:** Admin panels, no-code builders, business users, audit-friendly rules

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Logical Operators](#logical-operators)
4. [Range Queries](#range-queries)
5. [List Membership](#list-membership)
6. [String Operations](#string-operations)
7. [Existence Checks](#existence-checks)
8. [Boolean Values](#boolean-values)
9. [Advanced Patterns](#advanced-patterns)
10. [Multiple Phrasings](#multiple-phrasings)
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
use Cline\Ruler\DSL\Natural\NaturalLanguageRuleBuilder;
use Cline\Ruler\Core\Context;

$nl = new NaturalLanguageRuleBuilder();

// Parse a rule in plain English
$rule = $nl->parse('age is at least 18 and country is "US"');

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

### Design Philosophy

Natural Language DSL prioritizes **readability over expressiveness**:

- ✅ **Zero technical knowledge required** - Anyone can write rules
- ✅ **Self-documenting** - Rules read like plain English
- ✅ **Audit-friendly** - Perfect for compliance documentation
- ✅ **Business-first** - Designed for non-technical stakeholders
- ⚠️ **Intentionally limited** - No arithmetic, no regex, no advanced features

**When to Use:**
- Admin panels for business users
- No-code/low-code rule builders
- Rules requiring audit trails
- Teams with non-technical stakeholders

**When NOT to Use:**
- Complex logic requiring arithmetic or regex
- Machine-generated rules (too verbose)
- Performance-critical filtering (use Wirefilter instead)

---

## Basic Comparisons

### Equality

```php
// Equal to
$rule = $nl->parse('status is "active"');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // false

// Without quotes for simple values
$rule = $nl->parse('status is active');
$rule->evaluate(new Context(['status' => 'active'])); // true

// Numbers don't need quotes
$rule = $nl->parse('age is 18');
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => '18'])); // true (loose equality)
```

### Inequality

```php
// Not equal to
$rule = $nl->parse('status is not "banned"');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Alternative phrasing
$rule = $nl->parse('status is not banned');
$rule->evaluate(new Context(['status' => 'active'])); // true

// With "does not equal"
$rule = $nl->parse('role does not equal "admin"');
$rule->evaluate(new Context(['role' => 'user'])); // true
```

### Greater Than Comparisons

```php
// Greater than
$rule = $nl->parse('age is more than 18');
$rule->evaluate(new Context(['age' => 25])); // true
$rule->evaluate(new Context(['age' => 18])); // false

// Alternative phrasing
$rule = $nl->parse('age is greater than 18');
$rule->evaluate(new Context(['age' => 25])); // true

// Price example
$rule = $nl->parse('price is more than 100');
$rule->evaluate(new Context(['price' => 150])); // true
$rule->evaluate(new Context(['price' => 99])); // false
```

### Greater Than or Equal To

```php
// At least
$rule = $nl->parse('age is at least 18');
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 25])); // true
$rule->evaluate(new Context(['age' => 17])); // false

// Alternative phrasing
$rule = $nl->parse('age is greater than or equal to 18');
$rule->evaluate(new Context(['age' => 18])); // true

// Minimum order value
$rule = $nl->parse('orderTotal is at least 50');
$rule->evaluate(new Context(['orderTotal' => 50])); // true
$rule->evaluate(new Context(['orderTotal' => 75])); // true
```

### Less Than Comparisons

```php
// Less than
$rule = $nl->parse('quantity is less than 10');
$rule->evaluate(new Context(['quantity' => 5])); // true
$rule->evaluate(new Context(['quantity' => 15])); // false

// Temperature example
$rule = $nl->parse('temperature is less than 32');
$rule->evaluate(new Context(['temperature' => 20])); // true
```

### Less Than or Equal To

```php
// At most
$rule = $nl->parse('age is at most 65');
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 65])); // true
$rule->evaluate(new Context(['age' => 70])); // false

// Alternative phrasing
$rule = $nl->parse('age is less than or equal to 65');
$rule->evaluate(new Context(['age' => 65])); // true

// Maximum quantity
$rule = $nl->parse('itemCount is at most 100');
$rule->evaluate(new Context(['itemCount' => 50])); // true
```

### Nested Properties

```php
// Dot notation for nested objects
$rule = $nl->parse('user.profile.age is at least 18');

$context = new Context([
    'user' => [
        'profile' => [
            'age' => 25
        ]
    ]
]);
$rule->evaluate($context); // true

// Deep nesting
$rule = $nl->parse('order.shipping.address.country is "US"');

$context = new Context([
    'order' => [
        'shipping' => [
            'address' => [
                'country' => 'US'
            ]
        ]
    ]
]);
$rule->evaluate($context); // true

// Multiple level access
$rule = $nl->parse('user.profile.settings.theme is "dark"');
$context = new Context([
    'user' => [
        'profile' => [
            'settings' => [
                'theme' => 'dark'
            ]
        ]
    ]
]);
$rule->evaluate($context); // true
```

---

## Logical Operators

### AND Operator

```php
// Multiple conditions must be true
$rule = $nl->parse('age is at least 18 and country is "US"');

$validContext = new Context([
    'age' => 25,
    'country' => 'US'
]);
$rule->evaluate($validContext); // true

$invalidContext = new Context([
    'age' => 25,
    'country' => 'FR'
]);
$rule->evaluate($invalidContext); // false (country fails)

// Three conditions
$rule = $nl->parse('age is at least 18 and country is "US" and verified is true');

$context = new Context([
    'age' => 25,
    'country' => 'US',
    'verified' => true
]);
$rule->evaluate($context); // true
```

### OR Operator

```php
// At least one condition must be true
$rule = $nl->parse('status is "active" or status is "pending"');

$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Multiple OR conditions
$rule = $nl->parse('tier is "premium" or tier is "enterprise" or tier is "vip"');
$rule->evaluate(new Context(['tier' => 'premium'])); // true
$rule->evaluate(new Context(['tier' => 'basic'])); // false
```

### Parentheses for Grouping

```php
// Control evaluation order with parentheses
$rule = $nl->parse('(age is at least 18 and country is "US") or age is at least 21');

// Passes: 20 years old in US
$context1 = new Context(['age' => 20, 'country' => 'US']);
$rule->evaluate($context1); // true

// Passes: 25 years old anywhere
$context2 = new Context(['age' => 25, 'country' => 'CA']);
$rule->evaluate($context2); // true

// Fails: 18 in CA
$context3 = new Context(['age' => 18, 'country' => 'CA']);
$rule->evaluate($context3); // false
```

### Complex Logical Combinations

```php
// Multiple groups
$rule = $nl->parse(
    '(age is at least 18 and country is "US") or ' .
    '(age is at least 21 and country is "CA")'
);

// Nested parentheses
$rule = $nl->parse(
    'status is "active" and ' .
    '(tier is "premium" or (tier is "free" and trialActive is true))'
);

// Multiple AND/OR combinations
$rule = $nl->parse(
    'age is at least 18 and ' .
    '(country is "US" or country is "CA" or country is "UK") and ' .
    'emailVerified is true'
);
```

---

## Range Queries

### Between (Inclusive Range)

```php
// Age range
$rule = $nl->parse('age is between 18 and 65');

$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 18])); // true (inclusive)
$rule->evaluate(new Context(['age' => 65])); // true (inclusive)
$rule->evaluate(new Context(['age' => 17])); // false
$rule->evaluate(new Context(['age' => 70])); // false

// Price range
$rule = $nl->parse('price is between 10 and 100');
$rule->evaluate(new Context(['price' => 50])); // true
$rule->evaluate(new Context(['price' => 10])); // true
$rule->evaluate(new Context(['price' => 100])); // true
$rule->evaluate(new Context(['price' => 5])); // false
```

### Alternative Phrasing: "from X to Y"

```php
// Same as "between" - inclusive range
$rule = $nl->parse('age is from 18 to 65');

$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 65])); // true
$rule->evaluate(new Context(['age' => 17])); // false

// Discount percentage range
$rule = $nl->parse('discountPercent is from 5 to 25');
$rule->evaluate(new Context(['discountPercent' => 15])); // true
$rule->evaluate(new Context(['discountPercent' => 30])); // false
```

### Combining Ranges with Other Conditions

```php
// Range with AND
$rule = $nl->parse('age is between 18 and 65 and country is "US"');

$validContext = new Context(['age' => 30, 'country' => 'US']);
$rule->evaluate($validContext); // true

$invalidContext = new Context(['age' => 30, 'country' => 'FR']);
$rule->evaluate($invalidContext); // false

// Multiple ranges
$rule = $nl->parse(
    'age is between 18 and 65 and ' .
    'income is between 30000 and 200000'
);

// Range with OR
$rule = $nl->parse(
    'age is between 18 and 25 or ' .
    'age is between 55 and 65'
);
$rule->evaluate(new Context(['age' => 20])); // true (first range)
$rule->evaluate(new Context(['age' => 60])); // true (second range)
$rule->evaluate(new Context(['age' => 40])); // false (neither range)
```

---

## List Membership

### "is one of" Pattern

```php
// Check if value is in list
$rule = $nl->parse('country is one of "US", "CA", "UK"');

$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'CA'])); // true
$rule->evaluate(new Context(['country' => 'UK'])); // true
$rule->evaluate(new Context(['country' => 'FR'])); // false

// Without quotes for simple values
$rule = $nl->parse('status is one of active, pending, approved');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'rejected'])); // false

// Numbers
$rule = $nl->parse('statusCode is one of 200, 201, 204');
$rule->evaluate(new Context(['statusCode' => 200])); // true
$rule->evaluate(new Context(['statusCode' => 404])); // false
```

### "is either X or Y" Pattern

```php
// Two-value shorthand
$rule = $nl->parse('status is either "active" or "pending"');

$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Boolean example
$rule = $nl->parse('value is either "yes" or "no"');
$rule->evaluate(new Context(['value' => 'yes'])); // true
$rule->evaluate(new Context(['value' => 'maybe'])); // false
```

### "is not one of" Pattern (Exclusion)

```php
// Exclude values
$rule = $nl->parse('status is not one of "banned", "suspended", "deleted"');

$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false
$rule->evaluate(new Context(['status' => 'deleted'])); // false

// Exclude specific roles
$rule = $nl->parse('role is not one of "admin", "super_admin"');
$rule->evaluate(new Context(['role' => 'user'])); // true
$rule->evaluate(new Context(['role' => 'admin'])); // false
```

### Combining List Membership

```php
// With AND
$rule = $nl->parse(
    'country is one of "US", "CA", "UK" and ' .
    'status is not one of "banned", "suspended"'
);

$validContext = new Context(['country' => 'US', 'status' => 'active']);
$rule->evaluate($validContext); // true

$invalidContext = new Context(['country' => 'US', 'status' => 'banned']);
$rule->evaluate($invalidContext); // false

// With OR
$rule = $nl->parse(
    'tier is one of "premium", "enterprise" or ' .
    'betaTester is true'
);

// Multiple list checks
$rule = $nl->parse(
    'country is one of "US", "CA" and ' .
    'state is one of "CA", "NY", "TX"'
);
```

---

## String Operations

### Contains / Includes

```php
// Check if string contains substring
$rule = $nl->parse('email contains "@example.com"');

$rule->evaluate(new Context(['email' => 'john@example.com'])); // true
$rule->evaluate(new Context(['email' => 'jane@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com'])); // false

// Alternative phrasing: "includes"
$rule = $nl->parse('description includes "important"');
$rule->evaluate(new Context(['description' => 'This is important stuff'])); // true
$rule->evaluate(new Context(['description' => 'Regular content'])); // false

// Phone number contains pattern
$rule = $nl->parse('phone contains "123"');
$rule->evaluate(new Context(['phone' => '123-4567'])); // true
$rule->evaluate(new Context(['phone' => '987-6543'])); // false

// Case-sensitive by design
$rule = $nl->parse('name contains "John"');
$rule->evaluate(new Context(['name' => 'John Doe'])); // true
$rule->evaluate(new Context(['name' => 'john doe'])); // false (case-sensitive!)
```

### Starts With / Begins With

```php
// Check if string starts with prefix
$rule = $nl->parse('name starts with "John"');

$rule->evaluate(new Context(['name' => 'John Doe'])); // true
$rule->evaluate(new Context(['name' => 'Jane Doe'])); // false

// Alternative phrasing: "begins with"
$rule = $nl->parse('sku begins with "PROD-"');
$rule->evaluate(new Context(['sku' => 'PROD-12345'])); // true
$rule->evaluate(new Context(['sku' => 'TEST-12345'])); // false

// URL protocol check
$rule = $nl->parse('url starts with "https://"');
$rule->evaluate(new Context(['url' => 'https://example.com'])); // true
$rule->evaluate(new Context(['url' => 'http://example.com'])); // false
```

### Ends With

```php
// Check if string ends with suffix
$rule = $nl->parse('email ends with "@example.com"');

$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@test.com'])); // false

// File extension check
$rule = $nl->parse('filename ends with ".pdf"');
$rule->evaluate(new Context(['filename' => 'document.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'image.jpg'])); // false

// Domain check
$rule = $nl->parse('website ends with ".gov"');
$rule->evaluate(new Context(['website' => 'www.irs.gov'])); // true
$rule->evaluate(new Context(['website' => 'www.example.com'])); // false
```

### Combining String Operations

```php
// Multiple string conditions
$rule = $nl->parse(
    'email contains "@" and ' .
    'email ends with ".com"'
);

$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@example.org'])); // false

// With other operators
$rule = $nl->parse(
    'description contains "urgent" and ' .
    'priority is "high"'
);

// OR with string operations
$rule = $nl->parse(
    'filename ends with ".pdf" or ' .
    'filename ends with ".docx"'
);
```

---

## Existence Checks

### Exists / Is Present / Is Set

```php
// Check if field has a value (not null)
$rule = $nl->parse('email exists');

$rule->evaluate(new Context(['email' => 'user@example.com'])); // true
$rule->evaluate(new Context(['email' => null])); // false
$rule->evaluate(new Context([])); // false (missing field)

// Alternative phrasings
$rule = $nl->parse('phoneNumber is present');
$rule->evaluate(new Context(['phoneNumber' => '123-4567'])); // true
$rule->evaluate(new Context(['phoneNumber' => null])); // false

$rule = $nl->parse('address is set');
$rule->evaluate(new Context(['address' => '123 Main St'])); // true
$rule->evaluate(new Context([])); // false
```

### Does Not Exist / Is Not Present / Is Not Set

```php
// Check if field is null or missing
$rule = $nl->parse('deletedAt does not exist');

$rule->evaluate(new Context(['deletedAt' => null])); // true
$rule->evaluate(new Context([])); // true (missing field)
$rule->evaluate(new Context(['deletedAt' => '2025-01-01'])); // false

// Alternative phrasings
$rule = $nl->parse('cancelledAt is not present');
$rule->evaluate(new Context(['cancelledAt' => null])); // true

$rule = $nl->parse('errorMessage is not set');
$rule->evaluate(new Context(['errorMessage' => null])); // true
$rule->evaluate(new Context(['errorMessage' => 'Error occurred'])); // false
```

### Practical Existence Examples

```php
// Optional fields
$rule = $nl->parse(
    'email exists and ' .
    'phoneNumber exists'
);

$validContext = new Context([
    'email' => 'user@example.com',
    'phoneNumber' => '123-4567'
]);
$rule->evaluate($validContext); // true

// Check for soft deletes
$rule = $nl->parse('deletedAt does not exist');
$rule->evaluate(new Context(['id' => 1])); // true (not deleted)
$rule->evaluate(new Context(['id' => 1, 'deletedAt' => '2025-01-01'])); // false

// Required payment method
$rule = $nl->parse(
    'subscriptionActive is true and ' .
    'paymentMethod exists'
);

// Optional field with OR
$rule = $nl->parse(
    'email exists or ' .
    'phoneNumber exists'
);
```

---

## Boolean Values

### Is True / Is Yes

```php
// Check if boolean is true
$rule = $nl->parse('verified is true');

$rule->evaluate(new Context(['verified' => true])); // true
$rule->evaluate(new Context(['verified' => false])); // false

// Alternative phrasing: "is yes"
$rule = $nl->parse('acceptedTerms is yes');
$rule->evaluate(new Context(['acceptedTerms' => true])); // true
$rule->evaluate(new Context(['acceptedTerms' => false])); // false

// Multiple boolean checks
$rule = $nl->parse(
    'emailVerified is true and ' .
    'phoneVerified is true and ' .
    'identityVerified is true'
);
```

### Is False / Is No

```php
// Check if boolean is false
$rule = $nl->parse('banned is false');

$rule->evaluate(new Context(['banned' => false])); // true
$rule->evaluate(new Context(['banned' => true])); // false

// Alternative phrasing: "is no"
$rule = $nl->parse('suspended is no');
$rule->evaluate(new Context(['suspended' => false])); // true

// Check inactive status
$rule = $nl->parse('active is false');
$rule->evaluate(new Context(['active' => false])); // true
```

### Boolean in Complex Rules

```php
// Feature flags
$rule = $nl->parse(
    'betaTester is true or ' .
    'tier is "premium"'
);

$betaUser = new Context(['betaTester' => true, 'tier' => 'free']);
$rule->evaluate($betaUser); // true

$premiumUser = new Context(['betaTester' => false, 'tier' => 'premium']);
$rule->evaluate($premiumUser); // true

// Access control
$rule = $nl->parse(
    'isAdmin is true and ' .
    'twoFactorEnabled is true and ' .
    'accountLocked is false'
);

// Subscription validation
$rule = $nl->parse(
    'subscriptionActive is true and ' .
    'paymentFailed is false and ' .
    'trialExpired is false'
);

// Pre-computed boolean flags
$rule = $nl->parse('isEven is true');
// Where isEven = (orderNumber % 2 == 0) computed before evaluation
$rule->evaluate(new Context(['isEven' => true])); // true
```

---

## Advanced Patterns

### Complex Business Rules

```php
// E-commerce product eligibility
$rule = $nl->parse(
    'category is "electronics" and ' .
    'price is at least 10 and ' .
    'price is at most 500 and ' .
    'inStock is true and ' .
    '(featured is true or rating is at least 4.0) and ' .
    'status is not one of "clearance", "discontinued"'
);

$validProduct = new Context([
    'category' => 'electronics',
    'price' => 250,
    'inStock' => true,
    'featured' => true,
    'rating' => 4.5,
    'status' => 'active'
]);
$rule->evaluate($validProduct); // true

// User access control
$rule = $nl->parse(
    '(role is "admin" or role is "moderator") and ' .
    'accountAgeDays is at least 30 and ' .
    'emailVerified is true and ' .
    'status is not one of "banned", "suspended"'
);

// Subscription eligibility
$rule = $nl->parse(
    '(subscriptionStatus is "active" or trialDaysLeft is more than 0) and ' .
    'paymentMethod exists and ' .
    'totalSpend is more than 0'
);
```

### Multi-Tier Logic

```php
// Tiered discount eligibility
$rule = $nl->parse(
    '(' .
        '(tier is "enterprise" and yearlySpend is at least 100000) or ' .
        '(tier is "premium" and yearlySpend is at least 10000) or ' .
        '(tier is "basic" and yearlySpend is at least 1000)' .
    ') and ' .
    'accountStatus is "active"'
);

// Regional compliance
$rule = $nl->parse(
    '(' .
        '(country is "US" and state is one of "CA", "NY", "TX") or ' .
        '(country is "CA" and province is one of "ON", "BC", "QC") or ' .
        '(country is one of "UK", "DE", "FR")' .
    ') and ' .
    'verificationScore is at least 70'
);

// Multi-factor authentication requirement
$rule = $nl->parse(
    'accountValue is more than 10000 and ' .
    '(' .
        'twoFactorEnabled is true or ' .
        '(lastPasswordChange is less than 90 and securityQuestions is at least 3)' .
    ')'
);
```

### Content Moderation

```php
// Automatic flagging
$rule = $nl->parse(
    '(' .
        'reportCount is at least 5 or ' .
        '(userReputationScore is less than 10 and linkCount is more than 3) or ' .
        'spamScore is more than 80' .
    ') and ' .
    'autoModerationEnabled is true'
);

// Trust score calculation (pre-computed)
$rule = $nl->parse(
    'accountAgeDays is at least 90 and ' .
    'verifiedEmail is true and ' .
    'reportedCount is 0 and ' .
    'contributionScore is at least 100'
);
```

### Feature Access Gates

```php
// Beta feature access
$rule = $nl->parse(
    '(' .
        'betaTester is true or ' .
        'tier is one of "premium", "enterprise" or ' .
        'userId is one of 1, 2, 3, 100, 200' .
    ') and ' .
    'environment is one of "staging", "production"'
);

// Premium feature with fallback
$rule = $nl->parse(
    '(' .
        'subscriptionTier is "premium" and subscriptionExpires is more than 1704067200' .
    ') or (' .
        'trialActive is true and featureUsageCount is less than 10' .
    ')'
);
```

---

## Multiple Phrasings

Natural Language DSL supports multiple ways to express the same concept for maximum flexibility.

### Comparison Operators

```php
// Greater than or equal - all equivalent
$rule1 = $nl->parse('age is at least 18');
$rule2 = $nl->parse('age is greater than or equal to 18');
// Both work identically

// Greater than - all equivalent
$rule1 = $nl->parse('price is more than 100');
$rule2 = $nl->parse('price is greater than 100');

// Less than or equal - all equivalent
$rule1 = $nl->parse('age is at most 65');
$rule2 = $nl->parse('age is less than or equal to 65');
```

### String Operations

```php
// Contains - both equivalent
$rule1 = $nl->parse('description contains "important"');
$rule2 = $nl->parse('description includes "important"');

// Starts with - both equivalent
$rule1 = $nl->parse('name starts with "John"');
$rule2 = $nl->parse('name begins with "John"');
```

### Existence Checks

```php
// Field exists - all equivalent
$rule1 = $nl->parse('email exists');
$rule2 = $nl->parse('email is present');
$rule3 = $nl->parse('email is set');

// Field does not exist - all equivalent
$rule1 = $nl->parse('deletedAt does not exist');
$rule2 = $nl->parse('deletedAt is not present');
$rule3 = $nl->parse('deletedAt is not set');
```

### Boolean Values

```php
// True - both equivalent
$rule1 = $nl->parse('verified is true');
$rule2 = $nl->parse('verified is yes');

// False - both equivalent
$rule1 = $nl->parse('banned is false');
$rule2 = $nl->parse('banned is no');
```

### Range Queries

```php
// Range - both equivalent
$rule1 = $nl->parse('age is between 18 and 65');
$rule2 = $nl->parse('age is from 18 to 65');
```

### Inequality

```php
// Not equal - both equivalent
$rule1 = $nl->parse('status is not "banned"');
$rule2 = $nl->parse('status does not equal "banned"');
```

### List Membership

```php
// Two values - equivalent ways
$rule1 = $nl->parse('status is one of "active", "pending"');
$rule2 = $nl->parse('status is either "active" or "pending"');
```

---

## Common Pitfalls

### 1. Case Sensitivity in String Operations

```php
// ❌ PITFALL: String operations are case-sensitive
$rule = $nl->parse('name contains "John"');
$rule->evaluate(new Context(['name' => 'john doe'])); // false! (lowercase j)

// ✅ SOLUTION: Normalize data before evaluation
$context = new Context(['name' => 'John Doe']); // Ensure consistent casing
$rule->evaluate($context); // true

// ✅ ALTERNATIVE: Pre-compute case-insensitive flags
$context = new Context([
    'name' => 'john doe',
    'nameContainsJohn' => str_contains(strtolower('john doe'), 'john')
]);
$rule = $nl->parse('nameContainsJohn is true');
```

### 2. No Inline Arithmetic

```php
// ❌ WRONG: Natural language doesn't support math expressions
// $rule = $nl->parse('price + shipping > 100'); // Won't work!

// ✅ CORRECT: Pre-compute values
$context = new Context([
    'price' => 75,
    'shipping' => 30,
    'total' => 105  // Pre-computed: 75 + 30
]);
$rule = $nl->parse('total is more than 100');
$rule->evaluate($context); // true
```

### 3. No Regex Patterns

```php
// ❌ WRONG: No regex support
// $rule = $nl->parse('email matches "[a-z]+@[a-z]+\\.com"'); // Won't work!

// ✅ CORRECT: Use string operations for simple patterns
$rule = $nl->parse('email contains "@" and email ends with ".com"');

// ✅ ALTERNATIVE: Pre-compute validation
$context = new Context([
    'email' => 'user@example.com',
    'emailValid' => filter_var('user@example.com', FILTER_VALIDATE_EMAIL) !== false
]);
$rule = $nl->parse('emailValid is true');
```

### 4. Quote Handling

```php
// ❌ AMBIGUOUS: Forgetting quotes for strings with spaces
// $rule = $nl->parse('status is pending approval'); // May parse incorrectly

// ✅ CORRECT: Use quotes for multi-word strings
$rule = $nl->parse('status is "pending approval"');

// ✅ ALSO CORRECT: Single-word values don't need quotes
$rule = $nl->parse('status is active'); // Works fine
```

### 5. Reserved Word Conflicts

```php
// ⚠️ CAUTION: Field names that are reserved words
// Fields named "and", "or", "is", "between", etc. may cause parsing issues

// ❌ PROBLEMATIC: Field literally named "and"
// $rule = $nl->parse('and is true'); // Confusing!

// ✅ SOLUTION: Use clear field names
$rule = $nl->parse('isConjunction is true');
$rule = $nl->parse('logicalOperator is "and"');
```

### 6. Negated Comparisons Can Be Confusing

```php
// ⚠️ CONFUSING: Double negatives
$rule = $nl->parse('age is not less than 18');
// Means: age >= 18

// ✅ CLEARER: Use positive phrasing when possible
$rule = $nl->parse('age is at least 18');

// Both work, but second is more readable for business users
```

### 7. Missing Field Handling

```php
// ⚠️ IMPORTANT: Missing fields evaluate as null
$rule = $nl->parse('email contains "@example.com"');

$context = new Context([]); // email field missing
// May throw error or return false depending on implementation

// ✅ SAFER: Check existence first
$rule = $nl->parse('email exists and email contains "@example.com"');
```

### 8. Type Coercion (Loose Equality)

```php
// ⚠️ IMPORTANT: Natural language uses loose equality (==)
$rule = $nl->parse('age is 18');

$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => '18'])); // true (string coerced!)

// ℹ️ NOTE: This is intentional for business users
// If you need strict typing, use Wirefilter DSL instead
```

### 9. Whitespace Sensitivity

```php
// ✅ ROBUST: Parser normalizes whitespace
$rule1 = $nl->parse('age is at least 18');
$rule2 = $nl->parse('age   is   at   least   18'); // Extra spaces
$rule3 = $nl->parse('age is at least     18'); // Tab characters
// All three parse to the same rule

// ℹ️ NOTE: Leading/trailing whitespace is also trimmed
$rule = $nl->parse('  age is at least 18  '); // Works fine
```

### 10. Parentheses Balance

```php
// ❌ WRONG: Unbalanced parentheses
// $rule = $nl->parse('(age is at least 18 and country is "US"'); // Error!

// ✅ CORRECT: Ensure parentheses are balanced
$rule = $nl->parse('(age is at least 18 and country is "US")');

// ❌ WRONG: Mismatched nesting
// $rule = $nl->parse('((age is 18) and (status is "active")'); // Error!

// ✅ CORRECT: Proper nesting
$rule = $nl->parse('((age is 18) and (status is "active"))');
```

---

## Real-World Examples

### E-Commerce

#### Product Listing Filters

```php
$nl = new NaturalLanguageRuleBuilder();

// Basic product filter
$rule = $nl->parse(
    'category is "electronics" and ' .
    'price is between 10 and 5000 and ' .
    'inStock is true'
);

$context = new Context([
    'category' => 'electronics',
    'price' => 299,
    'inStock' => true
]);
$rule->evaluate($context); // true

// Featured products only
$rule = $nl->parse(
    'category is "electronics" and ' .
    'inStock is true and ' .
    '(' .
        '(featured is true and rating is at least 4.0) or ' .
        '(salesCount is more than 100 and rating is at least 4.5)' .
    ') and ' .
    'status is not one of "discontinued", "recalled"'
);
```

#### Shopping Cart Validation

```php
// Minimum order validation
$rule = $nl->parse(
    'subtotal is at least 25 and ' .
    'itemCount is at least 1 and ' .
    'itemCount is at most 50'
);

$cart = new Context([
    'subtotal' => 47.50,
    'itemCount' => 3
]);
$rule->evaluate($cart); // true

// Free shipping eligibility
$rule = $nl->parse(
    '(' .
        'orderTotal is at least 50 or ' .
        'isPremiumMember is true' .
    ') and ' .
    'shippingCountry is one of "US", "CA"'
);
```

#### Promotional Discounts

```php
// Volume discount eligibility
$rule = $nl->parse(
    'quantity is at least 10 and ' .
    'category is one of "office-supplies", "electronics" and ' .
    'customerType is either "business" or "wholesale"'
);

// Seasonal promotion
$rule = $nl->parse(
    'productCategory is "seasonal" and ' .
    'seasonActive is true and ' .
    'inventoryCount is more than 50 and ' .
    'daysSinceAdded is more than 30'
);

// First-time customer discount
$rule = $nl->parse(
    'isFirstOrder is true and ' .
    'orderTotal is at least 25 and ' .
    'emailVerified is true'
);
```

### User Access Control

#### Admin Dashboard Access

```php
// Basic admin access
$rule = $nl->parse(
    '(role is "admin" or role is "super_admin") and ' .
    'emailVerified is true and ' .
    'twoFactorEnabled is true and ' .
    'accountAgeDays is at least 30 and ' .
    'status is not one of "suspended", "locked", "pending_review"'
);

$user = new Context([
    'role' => 'admin',
    'emailVerified' => true,
    'twoFactorEnabled' => true,
    'accountAgeDays' => 90,
    'status' => 'active'
]);
$rule->evaluate($user); // true

// Department-specific access
$rule = $nl->parse(
    'role is one of "admin", "manager" and ' .
    'department is one of "sales", "marketing" and ' .
    'clearanceLevel is at least 3'
);
```

#### Feature Flags

```php
// Beta feature access
$rule = $nl->parse(
    '(' .
        'userId is one of 1, 2, 3, 100, 200 or ' .
        '(betaTester is true and optInDate exists) or ' .
        '(subscriptionTier is "enterprise" and featureFlags contains "new_dashboard")' .
    ') and ' .
    'environment is one of "staging", "production"'
);

// Progressive rollout
$rule = $nl->parse(
    '(' .
        'featureRolloutPercent is at least 50 or ' .
        'region is one of "US-WEST", "EU-CENTRAL" or ' .
        'tier is "premium"' .
    ') and ' .
    'featureEnabled is true'
);
```

#### Content Moderation

```php
// Auto-moderation trigger
$rule = $nl->parse(
    '(' .
        'reportCount is at least 5 or ' .
        '(userReputation is less than 10 and linkCount is more than 3) or ' .
        'spamScore is more than 80' .
    ') and ' .
    'autoModerationEnabled is true'
);

// Manual review queue
$rule = $nl->parse(
    '(' .
        'flagCount is at least 3 or ' .
        'sensitiveContentScore is more than 70 or ' .
        '(newAccount is true and externalLinks is more than 2)' .
    ') and ' .
        'manualReviewRequired is false'
);
```

### SaaS Applications

#### Subscription Limits

```php
// API rate limiting by tier
$rule = $nl->parse(
    '(' .
        '(plan is "free" and apiCallsThisMonth is less than 1000) or ' .
        '(plan is "pro" and apiCallsThisMonth is less than 50000) or ' .
        '(plan is "enterprise" and apiCallsThisMonth is less than 1000000)' .
    ') and ' .
    'subscriptionStatus is "active" and ' .
    'paymentFailed is false'
);

$context = new Context([
    'plan' => 'pro',
    'apiCallsThisMonth' => 25000,
    'subscriptionStatus' => 'active',
    'paymentFailed' => false
]);
$rule->evaluate($context); // true

// Storage quota check
$rule = $nl->parse(
    'storageUsedGB is less than storageQuotaGB and ' .
    'subscriptionActive is true'
);
```

#### Trial & Onboarding

```php
// Trial eligibility
$rule = $nl->parse(
    'hasHadTrialBefore is false and ' .
    'emailVerified is true and ' .
    'paymentMethodAdded is true'
);

// Onboarding completion
$rule = $nl->parse(
    'profileComplete is true and ' .
    'emailVerified is true and ' .
    'firstProjectCreated is true and ' .
    'teamInvitesSent is at least 1'
);

// Trial expiration warning
$rule = $nl->parse(
    'trialActive is true and ' .
    'trialDaysRemaining is at most 3 and ' .
    'subscriptionChosen is false'
);
```

#### Billing & Payments

```php
// Payment collection required
$rule = $nl->parse(
    'subscriptionActive is true and ' .
    '(' .
        'paymentMethod exists and lastPaymentDate exists' .
    ') and ' .
    'balanceOwed is more than 0'
);

// Overage billing
$rule = $nl->parse(
    'storageUsedGB is more than includedStorageGB and ' .
    'overageBillingEnabled is true and ' .
    'subscriptionTier is not "enterprise"'
);
```

### Financial Services

#### Loan Approval

```php
// Basic eligibility
$rule = $nl->parse(
    'age is at least 18 and ' .
    'age is at most 75 and ' .
    'creditScore is at least 650 and ' .
    'annualIncome is at least 30000 and ' .
    '(' .
        'debtToIncomeRatio is less than 0.43 or ' .
        'hasCosigner is true' .
    ') and ' .
    'employmentLengthMonths is at least 6'
);

$applicant = new Context([
    'age' => 35,
    'creditScore' => 720,
    'annualIncome' => 65000,
    'debtToIncomeRatio' => 0.38,
    'hasCosigner' => false,
    'employmentLengthMonths' => 24
]);
$rule->evaluate($applicant); // true

// Loan amount validation
$rule = $nl->parse(
    'loanAmount is at most maxLoanAmount and ' .
    'bankruptcyHistory is false'
);
```

#### Fraud Detection

```php
// High-risk transaction
$rule = $nl->parse(
    '(' .
        'transactionAmount is more than 5000 or ' .
        '(transactionAmount is more than 1000 and velocityLastHour is more than 5) or ' .
        'ipCountry is not billingCountry' .
    ') and ' .
    '(' .
        'fraudScore is more than 75 or ' .
        'deviceFingerprint is one of "known-fraud-device-1", "known-fraud-device-2"' .
    ')'
);

// Manual review trigger
$rule = $nl->parse(
    'riskScore is more than 80 and ' .
    'transactionAmount is more than 1000 and ' .
    'accountAgeDays is less than 30'
);
```

### Healthcare

#### Patient Eligibility

```php
// Procedure eligibility
$rule = $nl->parse(
    'age is at least 18 and ' .
    'insuranceActive is true and ' .
    'insuranceCoverageRemaining is more than procedureCost and ' .
    '(' .
        'referralRequired is false or ' .
        '(referralRequired is true and referralDate exists and daysSinceReferral is at most 90)' .
    ')'
);

$patient = new Context([
    'age' => 45,
    'insuranceActive' => true,
    'insuranceCoverageRemaining' => 15000,
    'procedureCost' => 5000,
    'referralRequired' => true,
    'referralDate' => '2025-09-01',
    'daysSinceReferral' => 45
]);
$rule->evaluate($patient); // true
```

### Gaming

#### Achievement Unlock

```php
// Epic achievement
$rule = $nl->parse(
    'playerLevel is at least 50 and ' .
    'totalPlaytimeHours is at least 100 and ' .
    'bossDefeats is at least 20 and ' .
    'rareItemsCollected is at least 5 and ' .
    '(' .
        'soloAchievements is at least 10 or ' .
        '(multiplayerWins is at least 50 and teamParticipationRate is at least 0.8)' .
    ')'
);

// Daily quest completion
$rule = $nl->parse(
    'questsCompletedToday is at least 3 and ' .
    'loginStreak is at least 1 and ' .
    'lastQuestCompletedMinutesAgo is less than 1440'
);
```

#### Matchmaking

```php
// Ranked match eligibility
$rule = $nl->parse(
    'playerRating is between 1000 and 1500 and ' .
    'averageMatchDuration is between 10 and 45 and ' .
    'region is one of "NA-East", "NA-West", "EU" and ' .
    'queueTime is less than 300 and ' .
    'latency is less than 100'
);
```

### Marketing & Analytics

#### Audience Segmentation

```php
// High-value customer
$rule = $nl->parse(
    'lifetimeValue is more than 1000 and ' .
    'purchaseCount is at least 5 and ' .
    'daysSinceLastPurchase is less than 90 and ' .
    'averageOrderValue is more than 150 and ' .
    'emailEngagementScore is at least 70'
);

// Re-engagement campaign
$rule = $nl->parse(
    'daysSinceLastPurchase is between 90 and 365 and ' .
    'purchaseCount is at least 3 and ' .
    'emailUnsubscribed is false and ' .
    'emailBounced is false'
);

// VIP customer segment
$rule = $nl->parse(
    '(' .
        'lifetimeValue is more than 10000 or ' .
        'subscriptionTier is "platinum"' .
    ') and ' .
    'accountStatus is "active" and ' .
    'memberSince is at least 365'
);
```

#### Campaign Targeting

```php
// Email campaign eligibility
$rule = $nl->parse(
    'emailVerified is true and ' .
    'emailUnsubscribed is false and ' .
    'lastEmailSent is at least 7 and ' .
    '(' .
        'purchaseCount is 0 or ' .
        'daysSinceLastPurchase is more than 30' .
    ')'
);

// Retargeting ad eligibility
$rule = $nl->parse(
    'hasVisited is true and ' .
    'hasNotPurchased is true and ' .
    'daysSinceLastVisit is between 1 and 30 and ' .
    'adOptOut is false'
);
```

### IoT & Monitoring

#### Alert Conditions

```php
// Critical system alert
$rule = $nl->parse(
    '(' .
        'temperature is more than 80 or ' .
        'humidity is less than 20 or ' .
        '(cpuUsage is more than 90 and durationSeconds is more than 300) or ' .
        'memoryAvailableMB is less than 512' .
    ') and ' .
    'alertCooldownExpired is true and ' .
    'maintenanceMode is false'
);

$sensor = new Context([
    'temperature' => 85,
    'humidity' => 45,
    'cpuUsage' => 75,
    'durationSeconds' => 120,
    'memoryAvailableMB' => 2048,
    'alertCooldownExpired' => true,
    'maintenanceMode' => false
]);
$rule->evaluate($sensor); // true (temperature exceeded)

// Predictive maintenance
$rule = $nl->parse(
    'operatingHours is more than 5000 and ' .
    'vibrationLevel is more than normalVibrationLevel and ' .
    'temperatureDeviation is more than 10 and ' .
    'lastMaintenanceDate is more than 180'
);
```

---

## Best Practices Summary

### 1. Write for Business Users

```php
// ✅ GOOD: Clear, readable, business-friendly
$rule = $nl->parse('customer is a premium member and order total is at least $100');

// ❌ AVOID: Technical jargon
// Use Wirefilter DSL instead if you need technical precision
```

### 2. Pre-Compute Complex Logic

```php
// ✅ GOOD: Compute outside the rule
$context = new Context([
    'price' => 75,
    'shipping' => 30,
    'tax' => 8.40,
    'grandTotal' => 113.40  // Pre-computed
]);
$rule = $nl->parse('grandTotal is more than 100');

// ❌ AVOID: Trying to do math in natural language
// Natural Language DSL doesn't support arithmetic
```

### 3. Use Consistent Casing

```php
// ✅ GOOD: Normalize data before evaluation
$context = new Context([
    'email' => strtolower('USER@EXAMPLE.COM'),
    'name' => ucwords(strtolower('JOHN DOE'))
]);

// ❌ AVOID: Relying on case-insensitive matching
// String operations are case-sensitive
```

### 4. Break Complex Rules into Simple Ones

```php
// ✅ GOOD: Multiple simple rules
$eligibilityRule = $nl->parse('age is at least 18 and emailVerified is true');
$locationRule = $nl->parse('country is one of "US", "CA", "UK"');
$statusRule = $nl->parse('status is not one of "banned", "suspended"');

// Evaluate separately or combine with AND
$isEligible = $eligibilityRule->evaluate($context);
$isValidLocation = $locationRule->evaluate($context);
$hasValidStatus = $statusRule->evaluate($context);

if ($isEligible && $isValidLocation && $hasValidStatus) {
    // All conditions met
}

// ❌ AVOID: Overly complex single rule
// Hard to read, debug, and maintain
```

### 5. Use Existence Checks for Optional Fields

```php
// ✅ GOOD: Check existence first
$rule = $nl->parse('middleName exists and middleName contains "Van"');

// ❌ RISKY: Assuming field exists
// May fail if middleName is null/missing
```

### 6. Leverage Multiple Phrasings for Clarity

```php
// ✅ GOOD: Use most natural phrasing for your audience
$rule1 = $nl->parse('age is at least 18'); // Financial/legal context
$rule2 = $nl->parse('age is 18 or more');  // Casual/marketing context

// Both work - choose what's clearest for your users
```

### 7. Document Business Intent

```php
// ✅ GOOD: Add comments explaining business logic
// Rule: VIP customers get free shipping
$rule = $nl->parse(
    'customerTier is "VIP" and orderTotal is at least 25'
);

// Rule: Students get discount on first order
$rule = $nl->parse(
    'isStudent is true and isFirstOrder is true'
);
```

### 8. Test Edge Cases

```php
// ✅ GOOD: Test boundary conditions
$rule = $nl->parse('age is between 18 and 65');

// Test exact boundaries
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 65])); // true

// Test just outside boundaries
$rule->evaluate(new Context(['age' => 17])); // false
$rule->evaluate(new Context(['age' => 66])); // false
```

### 9. Use Explicit Parentheses

```php
// ✅ GOOD: Clear grouping
$rule = $nl->parse(
    '(age is at least 18 and country is "US") or ' .
    '(age is at least 21 and country is "CA")'
);

// ❌ AMBIGUOUS: Relies on precedence
$rule = $nl->parse(
    'age is at least 18 and country is "US" or age is at least 21 and country is "CA"'
);
```

### 10. Keep Rules Audit-Friendly

```php
// ✅ GOOD: Rules serve as documentation
// Anyone reading logs can understand this:
"User eligible: age is at least 18 and emailVerified is true and country is one of US, CA, UK"

// ❌ LESS CLEAR: Technical syntax
"User eligible: age >= 18 && emailVerified === true && country in ['US', 'CA', 'UK']"
// Still readable, but Natural Language is better for non-technical stakeholders
```

---

## See Also

- [ADR 007: Natural Language DSL](../../adr/007-natural-language-dsl.md)
- [Wirefilter DSL Cookbook](./wirefilter-dsl.md) - For technical users needing arithmetic & regex
- [DSL Feature Support Matrix](dsl-feature-matrix.md) - Compare all DSLs
- [Other DSL Cookbooks](../cookbooks/)
