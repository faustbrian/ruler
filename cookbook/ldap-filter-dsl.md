# LDAP Filter DSL Cookbook

**Status:** Specialized DSL
**Complexity:** Low (Simple Grammar) / High (Unusual Syntax)
**Best For:** Ultra-compact serialization, URL parameters, LDAP/AD integration, prefix notation preference

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Basic Comparisons](#basic-comparisons)
3. [Logical Operators](#logical-operators)
4. [Wildcard Matching](#wildcard-matching)
5. [Presence Checks](#presence-checks)
6. [Approximate Matching](#approximate-matching)
7. [Nested Properties](#nested-properties)
8. [Advanced Patterns](#advanced-patterns)
9. [Compactness Examples](#compactness-examples)
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
use Cline\Ruler\DSL\LDAP\LDAPFilterRuleBuilder;
use Cline\Ruler\Core\Context;

$ldap = new LDAPFilterRuleBuilder();

// Parse a rule - NOTE: Prefix notation with mandatory parentheses
$rule = $ldap->parse('(&(age>=18)(country=US))');

// Evaluate against data
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context); // true
```

### Key Syntax Characteristics

**⭐ MOST COMPACT DSL** - Fewest characters of all DSLs

**Prefix Notation (Polish Notation):**
- Operators come BEFORE operands
- `(&(a)(b))` not `(a AND b)`
- Zero operator precedence ambiguity

**Mandatory Parentheses:**
- Every expression MUST be wrapped in parentheses
- `(age>=18)` not `age>=18`

---

## Basic Comparisons

### Equality (=)

```php
// String equality
$rule = $ldap->parse('(status=active)');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'inactive'])); // false

// Numeric equality
$rule = $ldap->parse('(age=18)');
$rule->evaluate(new Context(['age' => 18])); // true
$rule->evaluate(new Context(['age' => 20])); // false

// Boolean equality
$rule = $ldap->parse('(verified=true)');
$rule->evaluate(new Context(['verified' => true])); // true
$rule->evaluate(new Context(['verified' => false])); // false

// Float equality
$rule = $ldap->parse('(price=19.99)');
$rule->evaluate(new Context(['price' => 19.99])); // true
```

**Character Count: `(age=18)` = 9 chars**

### Greater Than or Equal (>=)

```php
// Age check
$rule = $ldap->parse('(age>=18)');
$rule->evaluate(new Context(['age' => 18])); // true (equal)
$rule->evaluate(new Context(['age' => 25])); // true (greater)
$rule->evaluate(new Context(['age' => 16])); // false

// Price threshold
$rule = $ldap->parse('(price>=100)');
$rule->evaluate(new Context(['price' => 150])); // true
$rule->evaluate(new Context(['price' => 100])); // true (equal)
$rule->evaluate(new Context(['price' => 50])); // false
```

**Character Count: `(age>=18)` = 10 chars**

### Less Than or Equal (<=)

```php
// Maximum age
$rule = $ldap->parse('(age<=65)');
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 65])); // true (equal)
$rule->evaluate(new Context(['age' => 70])); // false

// Temperature limit
$rule = $ldap->parse('(temperature<=32)');
$rule->evaluate(new Context(['temperature' => 20])); // true
$rule->evaluate(new Context(['temperature' => 32])); // true
$rule->evaluate(new Context(['temperature' => 40])); // false
```

**Character Count: `(age<=65)` = 10 chars**

### Greater Than (>)

**Extension beyond RFC 4515**

```php
// Strictly greater than
$rule = $ldap->parse('(age>18)');
$rule->evaluate(new Context(['age' => 19])); // true
$rule->evaluate(new Context(['age' => 18])); // false (not equal)
$rule->evaluate(new Context(['age' => 16])); // false

// Price above threshold
$rule = $ldap->parse('(price>100)');
$rule->evaluate(new Context(['price' => 101])); // true
$rule->evaluate(new Context(['price' => 100])); // false
```

**Character Count: `(age>18)` = 9 chars**

### Less Than (<)

**Extension beyond RFC 4515**

```php
// Strictly less than
$rule = $ldap->parse('(age<18)');
$rule->evaluate(new Context(['age' => 17])); // true
$rule->evaluate(new Context(['age' => 18])); // false (not equal)
$rule->evaluate(new Context(['age' => 19])); // false

// Quantity below threshold
$rule = $ldap->parse('(quantity<10)');
$rule->evaluate(new Context(['quantity' => 9])); // true
$rule->evaluate(new Context(['quantity' => 10])); // false
```

**Character Count: `(age<18)` = 9 chars**

### Not Equal (!=)

**Extension beyond RFC 4515**

```php
// Status exclusion
$rule = $ldap->parse('(status!=banned)');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Alternative using NOT operator (RFC 4515 standard)
$rule = $ldap->parse('(!(status=banned))');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false
```

**Character Count Comparison:**
- `(status!=banned)` = 17 chars
- `(!(status=banned))` = 19 chars (2 extra for NOT syntax)

### Range Checks

```php
// Age range (18-65 inclusive) - NOTE: Prefix notation
$rule = $ldap->parse('(&(age>=18)(age<=65))');
$rule->evaluate(new Context(['age' => 30])); // true
$rule->evaluate(new Context(['age' => 18])); // true (inclusive)
$rule->evaluate(new Context(['age' => 65])); // true (inclusive)
$rule->evaluate(new Context(['age' => 70])); // false

// Outside range (temperature < 0 OR > 100)
$rule = $ldap->parse('(|(temperature<0)(temperature>100))');
$rule->evaluate(new Context(['temperature' => -5])); // true
$rule->evaluate(new Context(['temperature' => 105])); // true
$rule->evaluate(new Context(['temperature' => 50])); // false
```

**Character Count:**
- Range: `(&(age>=18)(age<=65))` = 22 chars
- Outside: `(|(temperature<0)(temperature>100))` = 38 chars

---

## Logical Operators

**⭐ PREFIX NOTATION - Operators come FIRST**

### AND (&) - All Conditions Must Match

```php
// Two conditions
$rule = $ldap->parse('(&(age>=18)(country=US))');

$valid = new Context(['age' => 25, 'country' => 'US']);
$rule->evaluate($valid); // true (both match)

$invalid1 = new Context(['age' => 16, 'country' => 'US']);
$rule->evaluate($invalid1); // false (age fails)

$invalid2 = new Context(['age' => 25, 'country' => 'FR']);
$rule->evaluate($invalid2); // false (country fails)
```

**Character Count: `(&(age>=18)(country=US))` = 25 chars**

**Syntax Breakdown:**
1. `(` - Opening parenthesis for entire expression
2. `&` - AND operator (prefix position)
3. `(age>=18)` - First condition
4. `(country=US)` - Second condition
5. `)` - Closing parenthesis

```php
// Three conditions
$rule = $ldap->parse('(&(age>=18)(country=US)(verified=true))');

$valid = new Context([
    'age' => 25,
    'country' => 'US',
    'verified' => true
]);
$rule->evaluate($valid); // true (all three match)

// Any single failure = false
$invalid = new Context([
    'age' => 25,
    'country' => 'US',
    'verified' => false // This one fails
]);
$rule->evaluate($invalid); // false
```

**Character Count: `(&(age>=18)(country=US)(verified=true))` = 41 chars**

### OR (|) - At Least One Condition Matches

```php
// Multiple status values
$rule = $ldap->parse('(|(status=active)(status=pending))');

$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'deleted'])); // false

// Age OR VIP
$rule = $ldap->parse('(|(age>=21)(vip=true))');

$rule->evaluate(new Context(['age' => 25, 'vip' => false])); // true (age)
$rule->evaluate(new Context(['age' => 18, 'vip' => true])); // true (vip)
$rule->evaluate(new Context(['age' => 18, 'vip' => false])); // false (neither)
```

**Character Count: `(|(status=active)(status=pending))` = 35 chars**

### NOT (!) - Negate Condition

```php
// Not banned
$rule = $ldap->parse('(!(status=banned))');
$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false

// Not under 18
$rule = $ldap->parse('(!(age<18))');
$rule->evaluate(new Context(['age' => 25])); // true
$rule->evaluate(new Context(['age' => 15])); // false

// Negate compound expression - age must NOT be between 18-65
$rule = $ldap->parse('(!(&(age>=18)(age<=65)))');
$rule->evaluate(new Context(['age' => 16])); // true (outside range)
$rule->evaluate(new Context(['age' => 70])); // true (outside range)
$rule->evaluate(new Context(['age' => 30])); // false (inside range)
```

**Character Count:**
- Simple NOT: `(!(status=banned))` = 19 chars
- Nested NOT: `(!(&(age>=18)(age<=65)))` = 25 chars

### Complex Nesting

**Zero Operator Precedence Ambiguity - Prefix notation makes order explicit**

```php
// (age >= 18 AND country = US) OR vip = true
// Wirefilter: age >= 18 && country == "US" || vip == true (ambiguous without parens)
// LDAP: Crystal clear with prefix notation
$rule = $ldap->parse('(|(&(age>=18)(country=US))(vip=true))');

$rule->evaluate(new Context(['age' => 20, 'country' => 'US', 'vip' => false])); // true
$rule->evaluate(new Context(['age' => 16, 'country' => 'FR', 'vip' => true])); // true
$rule->evaluate(new Context(['age' => 16, 'country' => 'FR', 'vip' => false])); // false
```

**Character Count: `(|(&(age>=18)(country=US))(vip=true))` = 39 chars**

**Comparison with Wirefilter:**
- Wirefilter (no parens): `age >= 18 && country == "US" || vip == true` (47 chars)
- Wirefilter (clear): `(age >= 18 && country == "US") || vip == true` (49 chars)
- LDAP: `(|(&(age>=18)(country=US))(vip=true))` (39 chars) ✅ **8-10 chars shorter**

```php
// Deep nesting: ((A AND B) OR C) AND NOT (D OR E)
$rule = $ldap->parse('(&(|(&(a=1)(b=2))(c=3))(!((d=4)(e=5))))');

// Multi-level AND/OR/NOT
$rule = $ldap->parse('
    (&
        (|(age>=18)(age<=65))
        (|(country=US)(country=CA)(country=UK))
        (!(|(status=banned)(status=deleted)))
    )
');

$valid = new Context([
    'age' => 30,
    'country' => 'US',
    'status' => 'active'
]);
$rule->evaluate($valid); // true
```

**Character Count (without whitespace): `(&(|(age>=18)(age<=65))(|(country=US)(country=CA)(country=UK))(!(|(status=banned)(status=deleted))))` = 104 chars**

---

## Wildcard Matching

**⭐ UNIQUE FEATURE - Wildcards can appear ANYWHERE in the value**

### Prefix Wildcard (Starts With)

```php
// Name starts with "John"
$rule = $ldap->parse('(name=John*)');
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'John Doe'])); // true
$rule->evaluate(new Context(['name' => 'Johnny'])); // true
$rule->evaluate(new Context(['name' => 'Jane Doe'])); // false

// Email domain
$rule = $ldap->parse('(email=admin@*)');
$rule->evaluate(new Context(['email' => 'admin@example.com'])); // true
$rule->evaluate(new Context(['email' => 'user@example.com'])); // false
```

**Character Count: `(name=John*)` = 13 chars**

### Suffix Wildcard (Ends With)

```php
// Email domain
$rule = $ldap->parse('(email=*@example.com)');
$rule->evaluate(new Context(['email' => 'john@example.com'])); // true
$rule->evaluate(new Context(['email' => 'jane@example.com'])); // true
$rule->evaluate(new Context(['email' => 'john@test.com'])); // false

// File extension
$rule = $ldap->parse('(filename=*.pdf)');
$rule->evaluate(new Context(['filename' => 'document.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'report.pdf'])); // true
$rule->evaluate(new Context(['filename' => 'image.png'])); // false
```

**Character Count: `(email=*@example.com)` = 22 chars**

### Contains Wildcard

```php
// Description contains keyword
$rule = $ldap->parse('(description=*important*)');
$rule->evaluate(new Context(['description' => 'This is important stuff'])); // true
$rule->evaluate(new Context(['description' => 'important notice'])); // true
$rule->evaluate(new Context(['description' => 'The important thing'])); // true
$rule->evaluate(new Context(['description' => 'Nothing to see here'])); // false

// Tag search
$rule = $ldap->parse('(tags=*premium*)');
$rule->evaluate(new Context(['tags' => 'premium-user'])); // true
$rule->evaluate(new Context(['tags' => 'user-premium'])); // true
$rule->evaluate(new Context(['tags' => 'basic-user'])); // false
```

**Character Count: `(description=*important*)` = 26 chars**

### Complex Wildcard Patterns

```php
// Multiple wildcards - matches A...B...C pattern
$rule = $ldap->parse('(code=A*B*C)');
$rule->evaluate(new Context(['code' => 'AXBXC'])); // true
$rule->evaluate(new Context(['code' => 'A123B456C'])); // true
$rule->evaluate(new Context(['code' => 'ABC'])); // true (empty wildcards)
$rule->evaluate(new Context(['code' => 'ABXC'])); // false (missing B position)

// Phone number pattern
$rule = $ldap->parse('(phone=*555-*)');
$rule->evaluate(new Context(['phone' => '1-555-1234'])); // true
$rule->evaluate(new Context(['phone' => '555-1234'])); // true
$rule->evaluate(new Context(['phone' => '1-444-1234'])); // false

// Wildcard at beginning, middle, and end
$rule = $ldap->parse('(path=*/users/*/profile)');
$rule->evaluate(new Context(['path' => '/api/users/123/profile'])); // true
$rule->evaluate(new Context(['path' => '/v2/users/john/profile'])); // true
$rule->evaluate(new Context(['path' => '/api/users/admin'])); // false
```

**Character Count:**
- `(code=A*B*C)` = 13 chars
- `(phone=*555-*)` = 15 chars
- `(path=*/users/*/profile)` = 25 chars

### Wildcard Implementation Details

**Under the hood:** Wildcards compile to regex patterns
- `*` → `.*` (zero or more characters)
- Special characters in value are escaped
- Pattern is anchored: `^...$`

```php
// LDAP: (name=John*)
// Compiles to regex: /^John.*$/

// LDAP: (email=*@example.com)
// Compiles to regex: /^.*@example\.com$/

// LDAP: (description=*important*)
// Compiles to regex: /^.*important.*$/
```

---

## Presence Checks

**Check if field exists (is not null)**

### Field Exists

```php
// Email field must exist
$rule = $ldap->parse('(email=*)');
$rule->evaluate(new Context(['email' => 'test@example.com'])); // true
$rule->evaluate(new Context(['email' => ''])); // true (empty string exists)
$rule->evaluate(new Context(['email' => null])); // false
$rule->evaluate(new Context([])); // false (field missing)

// Phone number present
$rule = $ldap->parse('(phone=*)');
$rule->evaluate(new Context(['phone' => '555-1234'])); // true
$rule->evaluate(new Context(['phone' => null])); // false
```

**Character Count: `(email=*)` = 10 chars**

**Syntax Note:** `=*` (equals asterisk) means "field exists", NOT "equals anything with wildcard"

### Field Does Not Exist

```php
// DeletedAt must be null (record not deleted)
$rule = $ldap->parse('(!(deletedAt=*))');
$rule->evaluate(new Context(['deletedAt' => null])); // true
$rule->evaluate(new Context(['deletedAt' => '2023-01-01'])); // false
$rule->evaluate(new Context([])); // true (field missing = null)

// Optional field should be empty
$rule = $ldap->parse('(!(middleName=*))');
$rule->evaluate(new Context(['middleName' => null])); // true
$rule->evaluate(new Context(['middleName' => 'Lee'])); // false
```

**Character Count: `(!(deletedAt=*))` = 17 chars**

### Combining Presence with Other Conditions

```php
// Email exists AND verified
$rule = $ldap->parse('(&(email=*)(verified=true))');
$rule->evaluate(new Context(['email' => 'test@example.com', 'verified' => true])); // true
$rule->evaluate(new Context(['email' => null, 'verified' => true])); // false
$rule->evaluate(new Context(['email' => 'test@example.com', 'verified' => false])); // false

// Phone exists OR email exists (at least one contact method)
$rule = $ldap->parse('(|(phone=*)(email=*))');
$rule->evaluate(new Context(['phone' => '555-1234', 'email' => null])); // true
$rule->evaluate(new Context(['phone' => null, 'email' => 'test@example.com'])); // true
$rule->evaluate(new Context(['phone' => null, 'email' => null])); // false

// Active AND not deleted (deletedAt doesn't exist)
$rule = $ldap->parse('(&(status=active)(!(deletedAt=*)))');
$rule->evaluate(new Context(['status' => 'active', 'deletedAt' => null])); // true
$rule->evaluate(new Context(['status' => 'active', 'deletedAt' => '2023-01-01'])); // false
```

**Character Count:**
- `(&(email=*)(verified=true))` = 28 chars
- `(|(phone=*)(email=*))` = 21 chars
- `(&(status=active)(!(deletedAt=*)))` = 35 chars

---

## Approximate Matching

**Fuzzy matching operator (~=) - Case-insensitive contains**

### Basic Approximate Match

```php
// Name approximately matches "john"
$rule = $ldap->parse('(name~=john)');
$rule->evaluate(new Context(['name' => 'John'])); // true (case-insensitive)
$rule->evaluate(new Context(['name' => 'JOHN'])); // true
$rule->evaluate(new Context(['name' => 'john'])); // true
$rule->evaluate(new Context(['name' => 'Johnny'])); // true (contains)
$rule->evaluate(new Context(['name' => 'Johnson'])); // true (contains)
$rule->evaluate(new Context(['name' => 'Jane'])); // false

// City search
$rule = $ldap->parse('(city~=francisco)');
$rule->evaluate(new Context(['city' => 'San Francisco'])); // true
$rule->evaluate(new Context(['city' => 'FRANCISCO'])); // true
$rule->evaluate(new Context(['city' => 'francisco'])); // true
$rule->evaluate(new Context(['city' => 'Los Angeles'])); // false
```

**Character Count: `(name~=john)` = 13 chars**

### Implementation Details

**Default behavior:** Case-insensitive contains (regex `/value/i`)

```php
// LDAP: (name~=john)
// Compiles to regex: /john/i

// This means:
// - Case insensitive (i flag)
// - Contains (not anchored)
// - Simple substring match
```

### Approximate vs Wildcard Comparison

```php
// Approximate (case-insensitive contains)
$approx = $ldap->parse('(name~=john)');
$approx->evaluate(new Context(['name' => 'John'])); // true
$approx->evaluate(new Context(['name' => 'JOHN'])); // true
$approx->evaluate(new Context(['name' => 'Johnny'])); // true

// Exact wildcard (case-sensitive prefix)
$wild = $ldap->parse('(name=John*)');
$wild->evaluate(new Context(['name' => 'John'])); // true
$wild->evaluate(new Context(['name' => 'JOHN'])); // false (case matters!)
$wild->evaluate(new Context(['name' => 'Johnny'])); // true

// Exact wildcard (contains)
$wild2 = $ldap->parse('(name=*john*)');
$wild2->evaluate(new Context(['name' => 'john'])); // true
$wild2->evaluate(new Context(['name' => 'JOHN'])); // false (case matters!)
$wild2->evaluate(new Context(['name' => 'Johnny'])); // false (case matters!)
```

**Key Differences:**
- `~=` : Case-insensitive, contains match
- `=*pattern*` : Case-sensitive, exact pattern match

### Use Cases for Approximate Matching

```php
// User search (forgiving)
$rule = $ldap->parse('(|(name~=john)(email~=john))');
// Finds: "John Doe", "john@example.com", "JOHNNY", etc.

// Location search
$rule = $ldap->parse('(location~=york)');
// Finds: "New York", "YORK", "Yorkshire", etc.

// Tag search (case-insensitive)
$rule = $ldap->parse('(tags~=premium)');
// Finds: "Premium", "PREMIUM", "premium-user", etc.

// Product search
$rule = $ldap->parse('(|(title~=laptop)(description~=laptop))');
// Finds products with "laptop" anywhere in title or description
```

---

## Nested Properties

**Access nested object properties using dot notation**

### Single Level Nesting

```php
// User profile age
$rule = $ldap->parse('(user.age>=18)');

$context = new Context([
    'user' => [
        'age' => 25,
        'name' => 'John'
    ]
]);
$rule->evaluate($context); // true

// Order status
$rule = $ldap->parse('(order.status=completed)');

$context = new Context([
    'order' => [
        'status' => 'completed',
        'total' => 100
    ]
]);
$rule->evaluate($context); // true
```

**Character Count: `(user.age>=18)` = 15 chars**

### Deep Nesting

```php
// Multi-level nesting
$rule = $ldap->parse('(order.shipping.address.country=US)');

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

// Profile verification
$rule = $ldap->parse('(user.profile.verification.email=true)');

$context = new Context([
    'user' => [
        'profile' => [
            'verification' => [
                'email' => true,
                'phone' => false
            ]
        ]
    ]
]);
$rule->evaluate($context); // true
```

**Character Count: `(order.shipping.address.country=US)` = 37 chars**

### Nested Properties with Logical Operators

```php
// User age AND profile verified
$rule = $ldap->parse('(&(user.age>=18)(user.profile.verified=true))');

$valid = new Context([
    'user' => [
        'age' => 25,
        'profile' => [
            'verified' => true
        ]
    ]
]);
$rule->evaluate($valid); // true

// Order total OR user VIP status
$rule = $ldap->parse('(|(order.total>1000)(user.membership.vip=true))');

$context1 = new Context([
    'order' => ['total' => 1500],
    'user' => ['membership' => ['vip' => false]]
]);
$rule->evaluate($context1); // true (high total)

$context2 = new Context([
    'order' => ['total' => 50],
    'user' => ['membership' => ['vip' => true]]
]);
$rule->evaluate($context2); // true (vip)
```

**Character Count:**
- `(&(user.age>=18)(user.profile.verified=true))` = 46 chars
- `(|(order.total>1000)(user.membership.vip=true))` = 48 chars

### Array Access

**Note:** LDAP Filter DSL focuses on simple comparisons. For array index access, pre-compute values.

```php
// Pre-compute in context
$context = new Context([
    'firstItem' => ['price' => 150], // Extracted from items[0]
    'items' => [
        ['price' => 150],
        ['price' => 50]
    ]
]);

$rule = $ldap->parse('(firstItem.price>100)');
$rule->evaluate($context); // true
```

---

## Advanced Patterns

### Complex Business Rules

```php
// E-commerce product eligibility
$rule = $ldap->parse('
    (&
        (category=electronics)
        (price>=10)
        (price<=500)
        (inStock=true)
        (|(featured=true)(rating>=4.0))
        (!((status=clearance)(status=discontinued)))
    )
');

$product = new Context([
    'category' => 'electronics',
    'price' => 299,
    'inStock' => true,
    'featured' => false,
    'rating' => 4.5,
    'status' => 'active'
]);
$rule->evaluate($product); // true
```

**Character Count (without whitespace): 130 chars**

```php
// User access control
$rule = $ldap->parse('
    (&
        (|(role=admin)(role=moderator))
        (accountAge>=30)
        (emailVerified=true)
        (!(|(status=banned)(status=suspended)))
    )
');

$user = new Context([
    'role' => 'moderator',
    'accountAge' => 45,
    'emailVerified' => true,
    'status' => 'active'
]);
$rule->evaluate($user); // true
```

**Character Count (without whitespace): 115 chars**

```php
// Subscription eligibility
$rule = $ldap->parse('
    (&
        (|(subscriptionStatus=active)(trialDaysLeft>0))
        (!(paymentMethod=*))
        (totalSpend>0)
    )
');

$customer = new Context([
    'subscriptionStatus' => 'trial',
    'trialDaysLeft' => 7,
    'paymentMethod' => 'visa',
    'totalSpend' => 49.99
]);
$rule->evaluate($customer); // true
```

**Character Count (without whitespace): 89 chars**

### Multi-Level Exclusions

```php
// NOT (banned OR suspended OR deleted)
$rule = $ldap->parse('(!(|(status=banned)(status=suspended)(status=deleted)))');

$rule->evaluate(new Context(['status' => 'active'])); // true
$rule->evaluate(new Context(['status' => 'pending'])); // true
$rule->evaluate(new Context(['status' => 'banned'])); // false
$rule->evaluate(new Context(['status' => 'deleted'])); // false

// NOT (under 18 OR over 65)
$rule = $ldap->parse('(!((age<18)(age>65)))');

$rule->evaluate(new Context(['age' => 30])); // true (in range)
$rule->evaluate(new Context(['age' => 16])); // false (under)
$rule->evaluate(new Context(['age' => 70])); // false (over)
```

### Geographic Rules

```php
// North America countries
$rule = $ldap->parse('(|(country=US)(country=CA)(country=MX))');

$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'CA'])); // true
$rule->evaluate(new Context(['country' => 'MX'])); // true
$rule->evaluate(new Context(['country' => 'UK'])); // false

// US with specific states AND age
$rule = $ldap->parse('(&(country=US)(|(state=CA)(state=NY)(state=TX))(age>=21))');

$valid = new Context(['country' => 'US', 'state' => 'CA', 'age' => 25]);
$rule->evaluate($valid); // true

$invalid = new Context(['country' => 'US', 'state' => 'FL', 'age' => 25]);
$rule->evaluate($invalid); // false (state)
```

### Time-Based Rules

```php
// Using timestamps (pre-computed)
$rule = $ldap->parse('
    (&
        (createdAt>1704067200)
        (expiresAt>1735689600)
        (lastLogin>1704153600)
    )
');

$context = new Context([
    'createdAt' => 1704100000,
    'expiresAt' => 1735700000,
    'lastLogin' => 1704200000
]);
$rule->evaluate($context); // true

// Date range (string format)
$rule = $ldap->parse('(&(date>=2024-01-01)(date<=2024-12-31))');

$rule->evaluate(new Context(['date' => '2024-06-15'])); // true
$rule->evaluate(new Context(['date' => '2025-01-01'])); // false
```

### Compound Status Checks

```php
// Premium feature access
$rule = $ldap->parse('
    (|
        (&
            (|(tier=premium)(tier=enterprise))
            (subscriptionExpires>1735689600)
        )
        (&
            (trialActive=true)
            (trialExpires>1735689600)
            (featureUsage<100)
        )
    )
');

// Access via premium subscription
$premium = new Context([
    'tier' => 'premium',
    'subscriptionExpires' => 1735700000
]);
$rule->evaluate($premium); // true

// Access via trial
$trial = new Context([
    'trialActive' => true,
    'trialExpires' => 1735700000,
    'featureUsage' => 50
]);
$rule->evaluate($trial); // true
```

---

## Compactness Examples

**⭐ LDAP is the MOST COMPACT DSL - Demonstrating character count advantage**

### Simple Comparison

**Rule:** Age greater than or equal to 18

- **Wirefilter:** `age >= 18` (10 chars)
- **MongoDB:** `{"age": {"$gte": 18}}` (23 chars)
- **LDAP:** `(age>=18)` (9 chars) ✅ **Shortest**

**Savings:** 1-14 characters (10-61% reduction)

### AND Logic

**Rule:** Age ≥ 18 AND country = US

- **Wirefilter:** `age >= 18 && country == "US"` (31 chars)
- **MongoDB:** `{"$and": [{"age": {"$gte": 18}}, {"country": "US"}]}` (53 chars)
- **LDAP:** `(&(age>=18)(country=US))` (24 chars) ✅ **Shortest**

**Savings:** 7-29 characters (22-55% reduction)

### OR Logic

**Rule:** Status = active OR status = pending

- **Wirefilter:** `status == "active" || status == "pending"` (43 chars)
- **MongoDB:** `{"$or": [{"status": "active"}, {"status": "pending"}]}` (55 chars)
- **LDAP:** `(|(status=active)(status=pending))` (34 chars) ✅ **Shortest**

**Savings:** 9-21 characters (21-38% reduction)

### NOT Logic

**Rule:** Status NOT equal to banned

- **Wirefilter:** `status != "banned"` (18 chars) or `!(status == "banned")` (23 chars)
- **MongoDB:** `{"status": {"$ne": "banned"}}` (30 chars)
- **LDAP:** `(status!=banned)` (16 chars) ✅ **Shortest**

**Savings:** 2-14 characters (11-47% reduction)

### Wildcard Match

**Rule:** Email ends with @example.com

- **Wirefilter:** `email matches "@example\\.com$"` (31 chars, regex)
- **MongoDB:** `{"email": {"$regex": "@example\\.com$"}}` (41 chars)
- **LDAP:** `(email=*@example.com)` (21 chars) ✅ **Shortest**

**Savings:** 10-20 characters (32-49% reduction)

### Complex Nested Logic

**Rule:** (Age ≥ 18 AND country = US) OR vip = true

- **Wirefilter:** `(age >= 18 && country == "US") || vip == true` (47 chars)
- **MongoDB:** `{"$or": [{"$and": [{"age": {"$gte": 18}}, {"country": "US"}]}, {"vip": true}]}` (79 chars)
- **LDAP:** `(|(&(age>=18)(country=US))(vip=true))` (37 chars) ✅ **Shortest**

**Savings:** 10-42 characters (21-53% reduction)

### Range Check

**Rule:** Age between 18 and 65 (inclusive)

- **Wirefilter:** `age >= 18 && age <= 65` (22 chars)
- **MongoDB:** `{"$and": [{"age": {"$gte": 18}}, {"age": {"$lte": 65}}]}` (56 chars)
- **LDAP:** `(&(age>=18)(age<=65))` (20 chars) ✅ **Shortest**

**Savings:** 2-36 characters (9-64% reduction)

### Multi-Condition AND

**Rule:** Age ≥ 18 AND country = US AND verified = true

- **Wirefilter:** `age >= 18 && country == "US" && verified == true` (50 chars)
- **MongoDB:** `{"$and": [{"age": {"$gte": 18}}, {"country": "US"}, {"verified": true}]}` (73 chars)
- **LDAP:** `(&(age>=18)(country=US)(verified=true))` (39 chars) ✅ **Shortest**

**Savings:** 11-34 characters (22-47% reduction)

### Presence Check

**Rule:** Email field exists

- **Wirefilter:** `email != null` (13 chars)
- **MongoDB:** `{"email": {"$ne": null}}` (24 chars)
- **LDAP:** `(email=*)` (9 chars) ✅ **Shortest**

**Savings:** 4-15 characters (31-63% reduction)

### URL Query Parameter Example

**Perfect for URL encoding due to compactness:**

```
# Wirefilter (51 chars, needs encoding)
?filter=age%20%3E%3D%2018%20%26%26%20country%20%3D%3D%20%22US%22

# LDAP (24 chars, minimal encoding)
?filter=%28%26%28age%3E%3D18%29%28country%3DUS%29%29

# Even better - URL-safe characters
?filter=(&(age>=18)(country=US))
```

**Character count after URL encoding:**
- Wirefilter: 51 → 60 chars encoded
- LDAP: 24 → 42 chars encoded

**LDAP wins by 18 characters (30% reduction) even after encoding**

### Log File Compactness

```
# Application logs with embedded filters

# Wirefilter
[2024-01-15 10:30:00] FILTER_MATCH: age >= 18 && country == "US" || vip == true
[2024-01-15 10:30:01] FILTER_MATCH: status == "active" && verified == true

# LDAP (saves log storage space)
[2024-01-15 10:30:00] FILTER_MATCH: (|(&(age>=18)(country=US))(vip=true))
[2024-01-15 10:30:01] FILTER_MATCH: (&(status=active)(verified=true))

# Character savings per log line: 10-15 chars
# Over 1M log entries: 10-15 MB savings
```

---

## Performance Optimization

### Short-Circuit Evaluation

**Put cheapest/most-likely-to-fail checks first in AND operations**

```php
// ✅ Good - check simple field before complex wildcard
$rule = $ldap->parse('(&(status=active)(email=*@example.com))');

// ❌ Bad - expensive wildcard check happens first
$rule = $ldap->parse('(&(email=*@example.com)(status=active))');
```

**Why it matters:** AND operations short-circuit - if first condition fails, remaining conditions are never evaluated.

```php
// ✅ Good - fail fast on common disqualifier
$rule = $ldap->parse('(&(country=US)(age>=18)(verified=true))');
// If country != US (common), stops immediately

// ❌ Bad - always checks age and verification before country
$rule = $ldap->parse('(&(age>=18)(verified=true)(country=US))');
```

### Simplifying Complex Rules

```php
// ❌ Complex nested logic - hard to optimize
$rule = $ldap->parse('
    (|
        (&(a=1)(b=1)(c=1))
        (&(a=2)(b=2)(c=2))
        (&(a=3)(b=3)(c=3))
    )
');

// ✅ Better - if pattern allows, restructure
// Pre-compute composite value in context
$context = new Context([
    'composite' => 'a1-b1-c1', // Computed from a, b, c
]);
$rule = $ldap->parse('(|(composite=a1-b1-c1)(composite=a2-b2-c2)(composite=a3-b3-c3))');
```

### Avoiding Redundant Wildcards

```php
// ❌ Multiple wildcard checks
$rule = $ldap->parse('(|(email=*@gmail.com)(email=*@yahoo.com)(email=*@hotmail.com))');

// ✅ Better - pre-compute domain
$context = new Context([
    'email' => 'user@gmail.com',
    'emailDomain' => 'gmail.com' // Extracted once
]);
$rule = $ldap->parse('(|(emailDomain=gmail.com)(emailDomain=yahoo.com)(emailDomain=hotmail.com))');
```

### Pre-Computing Complex Values

```php
// ❌ Bad - checking multiple related fields
$rule = $ldap->parse('(&(price>=10)(price<=500)(category=electronics))');

// ✅ Better - pre-compute eligibility flags
$context = new Context([
    'priceInRange' => true, // Computed: price >= 10 && price <= 500
    'category' => 'electronics'
]);
$rule = $ldap->parse('(&(priceInRange=true)(category=electronics))');
```

### Caching Compiled Rules

**Parse once, evaluate many times**

```php
// ✅ Good - compile once
$ldap = new LDAPFilterRuleBuilder();
$filterString = '(&(age>=18)(country=US))';
$compiledRule = $ldap->parse($filterString); // Expensive

// Reuse for many evaluations
$result1 = $compiledRule->evaluate($context1);
$result2 = $compiledRule->evaluate($context2);
$result3 = $compiledRule->evaluate($context3);

// ❌ Bad - parsing repeatedly
foreach ($contexts as $context) {
    $rule = $ldap->parse('(&(age>=18)(country=US))'); // Parsed every time!
    $result = $rule->evaluate($context);
}
```

### Presence Check Optimization

```php
// ✅ Good - check presence before expensive operations
$rule = $ldap->parse('(&(email=*)(email=*@example.com))');
// First checks if email exists (cheap), then pattern match (expensive)

// ✅ Good - presence check to avoid null comparisons
$rule = $ldap->parse('(&(deletedAt=*)(deletedAt>1704067200))');
// Avoids comparing null values
```

### OR Operation Ordering

**Put most-likely-to-succeed conditions first in OR operations**

```php
// ✅ Good - most common status first
$rule = $ldap->parse('(|(status=active)(status=pending)(status=trial))');
// If 90% of records are "active", succeeds immediately

// ❌ Bad - least common first
$rule = $ldap->parse('(|(status=trial)(status=pending)(status=active))');
// Must check trial and pending before finding active
```

---

## Common Pitfalls

### Missing Parentheses

**❌ WRONG - Every expression MUST be wrapped in parentheses**

```php
// ❌ Error - missing outer parentheses
$ldap->parse('age>=18'); // INVALID

// ❌ Error - missing condition parentheses
$ldap->parse('(&age>=18)(country=US)'); // INVALID

// ✅ Correct
$ldap->parse('(age>=18)');
$ldap->parse('(&(age>=18)(country=US))');
```

### Unbalanced Parentheses

**❌ WRONG - Must have matching opening/closing parentheses**

```php
// ❌ Error - missing closing parenthesis
$ldap->parse('(&(age>=18)(country=US)'); // INVALID

// ❌ Error - extra closing parenthesis
$ldap->parse('(&(age>=18)(country=US)))'); // INVALID

// ✅ Correct - balanced
$ldap->parse('(&(age>=18)(country=US))');
```

### Operator Position Confusion

**❌ WRONG - LDAP uses PREFIX notation, not INFIX**

```php
// ❌ Wrong - trying to use infix notation
$ldap->parse('((age>=18)&(country=US))'); // INVALID

// ❌ Wrong - operator at end
$ldap->parse('((age>=18)(country=US)&)'); // INVALID

// ✅ Correct - operator comes FIRST (prefix)
$ldap->parse('(&(age>=18)(country=US))');
```

### Wildcard vs Presence Confusion

**❌ WRONG - Misunderstanding `=*` syntax**

```php
// Field exists (presence check)
$rule = $ldap->parse('(email=*)');
// TRUE if email is not null
// FALSE if email is null

// NOT the same as wildcard match
$rule = $ldap->parse('(email=**.com)'); // Looking for "*.com" literally!
// TRUE if email equals "*.com" (with asterisk)
// NOT a wildcard pattern

// ✅ Correct wildcard - asterisk is part of pattern, not value
$rule = $ldap->parse('(email=*@example.com)'); // Ends with @example.com
```

### String Quoting

**LDAP Filter DSL does NOT use quotes around string values**

```php
// ❌ Wrong - using quotes (they become part of value)
$rule = $ldap->parse('(country="US")');
// Looks for country == '"US"' (with quotes!)

// ❌ Wrong - single quotes
$rule = $ldap->parse("(country='US')");
// Looks for country == "'US'" (with quotes!)

// ✅ Correct - no quotes needed
$rule = $ldap->parse('(country=US)');
```

### Escaping Special Characters

**Characters that need escaping in LDAP values:**
- `(` → `\28`
- `)` → `\29`
- `\` → `\5c`
- `*` → `\2a` (when you want literal asterisk, not wildcard)
- `NUL` → `\00`

```php
// Value contains parentheses
$rule = $ldap->parse('(name=John\28Doe\29)'); // John(Doe)

// Value contains backslash
$rule = $ldap->parse('(path=C:\5cUsers)'); // C:\Users

// Literal asterisk (not wildcard)
$rule = $ldap->parse('(note=\2aimportant\2a)'); // *important*

// ✅ For most cases, no escaping needed
$rule = $ldap->parse('(email=john@example.com)'); // Works fine
$rule = $ldap->parse('(description=Hello, world!)'); // Works fine
```

### Empty OR/AND Operations

**❌ WRONG - Logical operators need at least one condition**

```php
// ❌ Error - AND with no conditions
$ldap->parse('(&)'); // INVALID

// ❌ Error - OR with no conditions
$ldap->parse('(|)'); // INVALID

// ✅ Correct - at least one condition
$ldap->parse('(&(age>=18))');
$ldap->parse('(|(status=active))');

// ✅ Correct - multiple conditions
$ldap->parse('(&(age>=18)(country=US))');
```

### Case Sensitivity Confusion

**LDAP comparisons are case-sensitive by default (except approximate match)**

```php
// Case-sensitive equality
$rule = $ldap->parse('(country=US)');
$rule->evaluate(new Context(['country' => 'US'])); // true
$rule->evaluate(new Context(['country' => 'us'])); // false
$rule->evaluate(new Context(['country' => 'Us'])); // false

// Case-sensitive wildcard
$rule = $ldap->parse('(name=John*)');
$rule->evaluate(new Context(['name' => 'John Doe'])); // true
$rule->evaluate(new Context(['name' => 'john doe'])); // false

// ✅ Use approximate match for case-insensitive
$rule = $ldap->parse('(name~=john)');
$rule->evaluate(new Context(['name' => 'John'])); // true
$rule->evaluate(new Context(['name' => 'JOHN'])); // true
$rule->evaluate(new Context(['name' => 'john'])); // true
```

### NOT Operator Syntax

**NOT applies to the ENTIRE following expression**

```php
// ✅ Correct - NOT applied to single condition
$rule = $ldap->parse('(!(status=banned))');

// ✅ Correct - NOT applied to compound expression
$rule = $ldap->parse('(!(&(age<18)(country=US)))');

// ❌ Wrong - trying to use NOT as suffix
$ldap->parse('((status=banned)!)'); // INVALID

// ❌ Wrong - NOT without expression
$ldap->parse('(!)'); // INVALID
```

---

## Real-World Examples

### E-Commerce

#### Product Search & Filtering

```php
// Electronics in price range, in stock, with good ratings
$rule = $ldap->parse('
    (&
        (category=electronics)
        (price>=10)
        (price<=5000)
        (inStock=true)
        (|
            (&(featured=true)(rating>=4.0))
            (&(salesCount>100)(rating>=4.5))
        )
        (!(|(status=discontinued)(status=recalled)))
    )
');

$product = new Context([
    'category' => 'electronics',
    'price' => 299,
    'inStock' => true,
    'featured' => false,
    'salesCount' => 150,
    'rating' => 4.7,
    'status' => 'active'
]);
$rule->evaluate($product); // true
```

**Character count: 183 chars (without whitespace)**

#### Dynamic Shipping Rules

```php
// International shipping eligibility
$rule = $ldap->parse('
    (&
        (weight>0)
        (|
            (&(country=US)(weight<=50))
            (&(country=CA)(weight<=30))
            (&(|(country=UK)(country=DE)(country=FR))(weight<=20))
        )
    )
');

$shipment = new Context([
    'country' => 'US',
    'weight' => 45
]);
$rule->evaluate($shipment); // true
```

**Character count: 136 chars (without whitespace)**

#### Promotional Eligibility

```php
// Bulk discount eligibility
$rule = $ldap->parse('
    (&
        (|(category=electronics)(category=books)(category=toys))
        (|
            (&(quantity>=10)(quantity<50)(discount=10))
            (&(quantity>=50)(quantity<100)(discount=15))
            (&(quantity>=100)(discount=20))
        )
        (customer.accountType=business)
    )
');

$order = new Context([
    'category' => 'electronics',
    'quantity' => 75,
    'discount' => 15,
    'customer' => [
        'accountType' => 'business'
    ]
]);
$rule->evaluate($order); // true
```

### User Access Control

#### Admin Privileges

```php
// Full admin access requirements
$rule = $ldap->parse('
    (&
        (|(role=admin)(role=super_admin))
        (emailVerified=true)
        (twoFactorEnabled=true)
        (accountAgeDays>=30)
        (!(|(status=suspended)(status=locked)(status=pending_review)))
    )
');

$user = new Context([
    'role' => 'admin',
    'emailVerified' => true,
    'twoFactorEnabled' => true,
    'accountAgeDays' => 45,
    'status' => 'active'
]);
$rule->evaluate($user); // true
```

#### Feature Flags

```php
// Beta feature access
$rule = $ldap->parse('
    (|
        (userId=1)
        (userId=2)
        (userId=100)
        (&(betaTester=true)(!(optInDate=*)))
        (&(subscriptionTier=enterprise)(featureFlags=*new_dashboard*))
    )
');

// Direct user ID
$rule->evaluate(new Context(['userId' => 1])); // true

// Beta tester with opt-in
$rule->evaluate(new Context([
    'betaTester' => true,
    'optInDate' => '2024-01-15'
])); // true

// Enterprise with feature flag
$rule->evaluate(new Context([
    'subscriptionTier' => 'enterprise',
    'featureFlags' => 'feature_a,new_dashboard,feature_b'
])); // true
```

#### Content Moderation

```php
// Auto-flag for review
$rule = $ldap->parse('
    (&
        (|
            (reportCount>=5)
            (&(spamScore>80)(accountAgeDays<7))
            (content=*spam*)
            (content=*scam*)
            (content=*phishing*)
        )
        (!(userId=*trusted_users*))
        (autoModerationEnabled=true)
    )
');

$post = new Context([
    'reportCount' => 3,
    'spamScore' => 85,
    'accountAgeDays' => 2,
    'content' => 'Check out this deal!',
    'userId' => 'user_12345',
    'autoModerationEnabled' => true
]);
$rule->evaluate($post); // true (spam score + new account)
```

### SaaS Applications

#### Subscription Limits

```php
// API rate limiting
$rule = $ldap->parse('
    (&
        (|
            (&(plan=free)(apiCallsThisMonth<1000))
            (&(plan=pro)(apiCallsThisMonth<50000))
            (&(plan=enterprise)(apiCallsThisMonth<1000000))
        )
        (subscriptionStatus=active)
        (paymentFailed=false)
    )
');

// Free plan user
$rule->evaluate(new Context([
    'plan' => 'free',
    'apiCallsThisMonth' => 500,
    'subscriptionStatus' => 'active',
    'paymentFailed' => false
])); // true

// Pro plan at limit
$rule->evaluate(new Context([
    'plan' => 'pro',
    'apiCallsThisMonth' => 50000,
    'subscriptionStatus' => 'active',
    'paymentFailed' => false
])); // false (at limit)
```

#### Storage Quotas

```php
// Storage limit check with overage
$rule = $ldap->parse('
    (|
        (storageUsedGB<=includedStorageGB)
        (&
            (storageUsedGB>includedStorageGB)
            (overageAllowed=true)
            (storageUsedGB<=maxStorageGB)
        )
    )
');

// Within quota
$rule->evaluate(new Context([
    'storageUsedGB' => 8,
    'includedStorageGB' => 10
])); // true

// Overage allowed
$rule->evaluate(new Context([
    'storageUsedGB' => 15,
    'includedStorageGB' => 10,
    'overageAllowed' => true,
    'maxStorageGB' => 20
])); // true
```

### Financial Services

#### Loan Approval

```php
// Loan pre-qualification
$rule = $ldap->parse('
    (&
        (age>=18)
        (age<=75)
        (creditScore>=650)
        (annualIncome>=30000)
        (|(debtToIncomeRatio<0.43)(hasCosigner=true))
        (employmentLengthMonths>=6)
        (|
            (bankruptcyHistory=false)
            (&(bankruptcyHistory=true)(yearsSinceBankruptcy>=7))
        )
    )
');

$applicant = new Context([
    'age' => 35,
    'creditScore' => 720,
    'annualIncome' => 75000,
    'debtToIncomeRatio' => 0.35,
    'employmentLengthMonths' => 24,
    'bankruptcyHistory' => false,
    'hasCosigner' => false
]);
$rule->evaluate($applicant); // true
```

#### Fraud Detection

```php
// High-risk transaction flagging
$rule = $ldap->parse('
    (|
        (transactionAmount>5000)
        (&(transactionAmount>1000)(velocityLastHour>5))
        (!(ipCountry=billingCountry))
        (deviceFingerprint=*known_fraud*)
        (billingAddress=*P.O. Box*)
        (&(accountAgeDays<30)(transactionAmount>500))
    )
');

// Large transaction
$rule->evaluate(new Context([
    'transactionAmount' => 6000
])); // true (flagged)

// High velocity
$rule->evaluate(new Context([
    'transactionAmount' => 1500,
    'velocityLastHour' => 8
])); // true (flagged)

// Country mismatch
$rule->evaluate(new Context([
    'transactionAmount' => 500,
    'ipCountry' => 'RU',
    'billingCountry' => 'US'
])); // true (flagged)
```

### Healthcare

#### Patient Eligibility

```php
// Procedure eligibility check
$rule = $ldap->parse('
    (&
        (age>=18)
        (insuranceActive=true)
        (insuranceCoverageRemaining>procedureCost)
        (|
            (referralRequired=false)
            (&
                (referralRequired=true)
                (!(referralDate=*))
                (referralAgeDays<=90)
            )
        )
        (!(condition=*excluded_conditions*))
    )
');

$patient = new Context([
    'age' => 45,
    'insuranceActive' => true,
    'insuranceCoverageRemaining' => 5000,
    'procedureCost' => 3000,
    'referralRequired' => true,
    'referralDate' => '2024-01-15',
    'referralAgeDays' => 30,
    'condition' => 'routine_checkup'
]);
$rule->evaluate($patient); // true
```

### Gaming

#### Achievement Unlock

```php
// Epic achievement criteria
$rule = $ldap->parse('
    (&
        (playerLevel>=50)
        (totalPlaytimeHours>=100)
        (bossDefeats>=20)
        (rareItemsCollected>=5)
        (|
            (soloAchievements>=10)
            (&(multiplayerWins>=50)(teamParticipationRate>=0.8))
        )
    )
');

$player = new Context([
    'playerLevel' => 52,
    'totalPlaytimeHours' => 150,
    'bossDefeats' => 25,
    'rareItemsCollected' => 7,
    'soloAchievements' => 8,
    'multiplayerWins' => 60,
    'teamParticipationRate' => 0.85
]);
$rule->evaluate($player); // true
```

#### Matchmaking

```php
// Competitive matchmaking
$rule = $ldap->parse('
    (&
        (playerRating>=1000)
        (playerRating<=1500)
        (averageMatchDuration>=10)
        (averageMatchDuration<=45)
        (|(region=NA-East)(region=NA-West)(region=EU))
        (queueTime<300)
        (latency<100)
        (!(|(status=banned)(status=timeout)))
    )
');

$player = new Context([
    'playerRating' => 1250,
    'averageMatchDuration' => 25,
    'region' => 'NA-East',
    'queueTime' => 120,
    'latency' => 45,
    'status' => 'active'
]);
$rule->evaluate($player); // true
```

### IoT & Monitoring

#### Alert Conditions

```php
// System health alert triggers
$rule = $ldap->parse('
    (&
        (|
            (temperature>80)
            (humidity<20)
            (&(cpuUsage>90)(durationSeconds>300))
            (memoryAvailableMB<512)
            (diskUsagePercent>95)
        )
        (alertCooldownExpired=true)
        (maintenanceMode=false)
    )
');

// CPU critical
$rule->evaluate(new Context([
    'cpuUsage' => 95,
    'durationSeconds' => 400,
    'alertCooldownExpired' => true,
    'maintenanceMode' => false
])); // true (alert triggered)

// Temperature warning
$rule->evaluate(new Context([
    'temperature' => 85,
    'alertCooldownExpired' => true,
    'maintenanceMode' => false
])); // true (alert triggered)
```

### LDAP/Active Directory Integration

```php
// Employee directory search
$rule = $ldap->parse('
    (&
        (|(department=Engineering)(department=DevOps))
        (employeeType=full-time)
        (accountEnabled=true)
        (email=*@company.com)
        (!(|(title=*Intern*)(title=*Contractor*)))
    )
');

$employee = new Context([
    'department' => 'Engineering',
    'employeeType' => 'full-time',
    'accountEnabled' => true,
    'email' => 'john.doe@company.com',
    'title' => 'Senior Engineer'
]);
$rule->evaluate($employee); // true
```

---

## Best Practices Summary

1. **Always Use Parentheses**: Every expression must be wrapped in `()`
2. **Prefix Notation**: Operators come FIRST: `(&(a)(b))` not `(a & b)`
3. **No String Quotes**: Values don't need quotes: `(country=US)` not `(country="US")`
4. **Compact for URLs**: LDAP is ideal for query parameters due to minimal characters
5. **Short-Circuit AND**: Put cheapest/most-likely-to-fail conditions first
6. **OR Optimization**: Put most-likely-to-succeed conditions first
7. **Cache Compiled Rules**: Parse once, evaluate many times
8. **Wildcard = Regex**: Understand `*` compiles to regex `.*`
9. **Presence Check**: `(field=*)` means "field exists" (not null)
10. **Approximate Match**: Use `~=` for case-insensitive contains
11. **Pre-Compute Values**: Move complex calculations to context
12. **Test Edge Cases**: Null values, empty strings, deep nesting

---

## Comparison with Other DSLs

### When to Use LDAP Filter DSL

**✅ Perfect for:**
- Ultra-compact serialization (logging, URLs)
- LDAP/Active Directory integration
- Systems requiring maximum compactness
- APIs with character limits
- Prefix notation preference (Lisp/Scheme developers)
- Zero operator precedence ambiguity requirements

**❌ NOT ideal for:**
- Human-authored rules (use Wirefilter instead)
- Inline arithmetic (use Wirefilter DSL)
- Complex date operations (use Wirefilter or MongoDB DSL)
- Developers unfamiliar with prefix notation
- Strict type equality checks (no === operator)

### Character Count Comparison Summary

| Rule Type | Wirefilter | MongoDB | LDAP | Savings |
|-----------|-----------|---------|------|---------|
| Simple comparison | 10 | 23 | 9 | 10-61% |
| AND logic | 31 | 53 | 24 | 22-55% |
| OR logic | 43 | 55 | 34 | 21-38% |
| NOT logic | 18 | 30 | 16 | 11-47% |
| Wildcard | 31 | 41 | 21 | 32-49% |
| Complex nested | 47 | 79 | 37 | 21-53% |
| Range check | 22 | 56 | 20 | 9-64% |

**Average savings: 11-53% fewer characters than alternatives**

---

## See Also

- [ADR 005: LDAP Filter DSL](/Users/brian/Developer/packages/ruler/adr/005-ldap-filter-dsl.md)
- [Wirefilter DSL Cookbook](/Users/brian/Developer/packages/ruler/docs/cookbooks/wirefilter-dsl.md)
- [DSL Feature Support Matrix](dsl-feature-matrix.md)
- [RFC 4515: LDAP String Representation of Search Filters](https://tools.ietf.org/html/rfc4515)
