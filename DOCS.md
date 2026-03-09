## Table of Contents

1. [Persisted Rules](#doc-docs-persisted-rules)
2. [Mutation Testing Strategy](#doc-docs-mutation-testing)
3. [Overview](#doc-docs-readme)
4. [Dsl Implementations](#doc-docs-dsl-implementations)
5. [Operators](#doc-docs-operators)
6. [Rules And Context](#doc-docs-rules-and-context)
7. [Text Dsl](#doc-docs-text-dsl)
<a id="doc-docs-persisted-rules"></a>

# Persisted Rule Definitions

## Versioning Contract

Persisted rules should be treated as versioned documents, not ad hoc arrays.

- Current documented schema: `schemas/rule-definition.v1.schema.json`
- Current explicit reference syntax: `@path.to.value`

## Recommended Stored Envelope

Store rule payloads with a version field:

```json
{
  "version": "v1",
  "definition": {
    "field": "score",
    "operator": "greaterThanOrEqualTo",
    "value": "@limits.minScore"
  }
}
```

## Migration: Legacy String References

Older payloads may encode references as plain dotted strings:

```json
{
  "field": "score",
  "operator": "greaterThanOrEqualTo",
  "value": "limits.minScore"
}
```

Migrate before compile/evaluate:

```php
use Cline\Ruler\Core\RuleDefinitionMigrator;

$migrated = RuleDefinitionMigrator::migrateLegacyStringReferences($legacy);
```

After migration:

```json
{
  "field": "score",
  "operator": "greaterThanOrEqualTo",
  "value": "@limits.minScore"
}
```

## Compatibility Testing

Repository fixtures and compatibility tests live under:

- `tests/Fixtures/Rules/v1`
- `tests/Unit/Core/RuleSchemaCompatibilityTest.php`

When introducing a new persisted schema version, add new fixture folders and
compatibility tests before changing compile logic.

<a id="doc-docs-mutation-testing"></a>

# Mutation Testing Strategy

## Current Decision

This project uses Pest's native mutation runner (`pest --mutate`) as the
authoritative mutation gate.

## Why

- It integrates directly with the existing Pest-based test suite.
- It keeps mutation execution and local developer setup simple.

## Scope

The required mutation gate currently targets evaluator and structured error
contract code:

- `Cline\Ruler\Core\RuleEvaluator`
- `Cline\Ruler\Exceptions\RuleEvaluatorException`
- `Cline\Ruler\Enums\RuleErrorCode`
- `Cline\Ruler\Enums\RuleErrorPhase`

## Revisit Criteria

Reevaluate the mutation setup if any of the following are true:

- Mutation runs become unstable or non-deterministic in CI.
- We need broader mutation scope than the current targeted gate.
- Reporting requirements outgrow the current Pest-native workflow.

Pest mutation testing remains the default and supported path.

<a id="doc-docs-readme"></a>

Ruler is a fluent rule engine with proposition-based evaluation and 50+ operators for building conditional business logic. Create readable, testable rules using either the fluent PHP API or text-based DSL syntax.

## Installation

```bash
composer require cline/ruler
```

## Quick Start

### Using RuleBuilder (Fluent API)

```php
use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Context;

$rb = new RuleBuilder;

// Create a rule with logical conditions
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['minNumPeople']->lessThanOrEqualTo($rb['actualNumPeople']),
        $rb['maxNumPeople']->greaterThanOrEqualTo($rb['actualNumPeople'])
    ),
    function (Context $context): void {
        echo 'Capacity is within range!';
    },
    'capacity-in-range'
);

// Evaluate with context
$context = new Context([
    'minNumPeople' => 5,
    'maxNumPeople' => 25,
    'actualNumPeople' => fn() => 6,  // Lazy evaluation
]);

$report = $rule->execute($context);  // "Capacity is within range!"
$report->matched;                    // true
$report->actionExecuted;             // true
```

### Using Text DSL

```php
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;
use Cline\Ruler\Core\Context;

$srb = new StringRuleBuilder;

// Create rule from text
$rule = $srb->parse('age >= 18 and country == "US"');

// Evaluate
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context);  // true
```

## Core Concepts

### Variables

Placeholders that resolve to values during evaluation:

```php
$rb['userName'];           // Simple variable
$rb['user']['roles'];      // Nested property access
```

### Context

The ViewModel providing values for rule evaluation. Supports both static values and lazy evaluation:

```php
$context = new Context([
    'staticValue' => 42,
    'lazyValue' => fn() => expensiveOperation(),
]);
```

### Propositions

Building blocks of rules—comparisons and checks that evaluate to boolean:

```php
$rb['age']->greaterThanOrEqualTo(18);
$rb['status']->equalTo('active');
```

### Rules

Combinations of propositions with optional action callbacks:

```php
$rule = $rb->create(
    $rb['age']->greaterThanOrEqualTo(18),
    fn (Context $context): void => grantAccess()
);

$rule->evaluate($context);  // Returns bool
$rule->execute($context);   // Returns RuleExecutionResult
```

### RuleSet

Collection of rules executed together:

```php
$rules = new RuleSet([$rule1, $rule2, $rule3]);
$report = $rules->executeRules($context);  // RuleSetExecutionReport
$report->getActionExecutionCount();        // Number of fired actions
```

## Without RuleBuilder

For more control, construct rules directly:

```php
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Core\Operator;
use Cline\Ruler\Core\Context;

$actualNumPeople = new Variable('actualNumPeople');

$rule = new Rule(
    new Operator\LogicalAnd([
        new Operator\LessThanOrEqualTo(new Variable('minNumPeople'), $actualNumPeople),
        new Operator\GreaterThanOrEqualTo(new Variable('maxNumPeople'), $actualNumPeople)
    ]),
    fn() => echo 'YAY!'
);

$context = new Context([
    'minNumPeople' => 5,
    'maxNumPeople' => 25,
    'actualNumPeople' => fn() => 6,
]);

$rule->execute($context);
```

## Extensibility

Ruler focuses exclusively on rule evaluation logic. Rule storage and retrieval are left to your implementation—whether that's an ORM, ODM, file-based DSL, or custom solution. This separation allows Ruler to integrate seamlessly into any architecture.

## Migration Notes

- `Rule::execute()` returns `RuleExecutionResult`.
- `RuleSet::executeRules()` and `executeForwardChaining()` return
  `RuleSetExecutionReport`.
- `RuleEvaluator::evaluateFrom*()` returns `RuleEvaluatorReport`.
- Action callbacks must accept `Context`, for example
  `fn (Context $context): void => ...`.
- Rules managed by `RuleSet` must have non-empty unique IDs.
- `RuleEvaluator::createFrom*()` uses an isolated in-memory compiled-rule cache
  per evaluator by default. Pass the same `CompiledRuleCache` instance to share
  compiled rule graphs across evaluators.

<a id="doc-docs-dsl-implementations"></a>

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

<a id="doc-docs-operators"></a>

Ruler provides over 50 operators for building expressive rule conditions.

## Comparison Operators

Define variables for rule evaluation:

```php
$a = $rb['a'];
$b = $rb['b'];
```

### Equality

```php
$a->equalTo($b);                          // true if $a == $b
$a->notEqualTo($b);                       // true if $a != $b
$a->sameAs($b);                           // true if $a === $b
$a->notSameAs($b);                        // true if $a !== $b
```

### Numeric Comparison

```php
$a->greaterThan($b);                      // true if $a > $b
$a->greaterThanOrEqualTo($b);             // true if $a >= $b
$a->lessThan($b);                         // true if $a < $b
$a->lessThanOrEqualTo($b);                // true if $a <= $b
```

### String Comparison

```php
$a->stringContains($b);                   // true if strpos($b, $a) !== false
$a->stringDoesNotContain($b);             // true if strpos($b, $a) === false
$a->stringContainsInsensitive($b);        // true if stripos($b, $a) !== false
$a->stringDoesNotContainInsensitive($b);  // true if stripos($b, $a) === false
$a->startsWith($b);                       // true if strpos($b, $a) === 0
$a->startsWithInsensitive($b);            // true if stripos($b, $a) === 0
$a->endsWith($b);                         // true if ends with substring
$a->endsWithInsensitive($b);              // case-insensitive ends with
```

## Mathematical Operators

Mathematical operators return values rather than boolean results, so they must be combined with comparison operators:

```php
$rb['price']
    ->add($rb['shipping'])
    ->greaterThanOrEqualTo(50);
```

### Arithmetic

```php
$c = $rb['c'];
$d = $rb['d'];

$c->add($d);          // $c + $d
$c->subtract($d);     // $c - $d
$c->multiply($d);     // $c * $d
$c->divide($d);       // $c / $d
$c->modulo($d);       // $c % $d
$c->exponentiate($d); // $c ** $d
```

### Unary Math

```php
$c->negate();         // -$c
$c->ceil();           // ceil($c)
$c->floor();          // floor($c)
```

## Set Operators

For working with arrays:

```php
$e = $rb['e'];  // Array
$f = $rb['f'];  // Array
```

### Set Manipulation

```php
$e->union($f);               // Elements in either set
$e->intersect($f);           // Elements in both sets
$e->complement($f);          // Elements in $e but not $f
$e->symmetricDifference($f); // Elements in one set but not both
$e->min();                   // Minimum value
$e->max();                   // Maximum value
```

### Set Propositions

```php
$e->containsSubset($f);       // true if $f ⊆ $e
$e->doesNotContainSubset($f); // true if $f ⊄ $e
$e->setContains($a);          // true if $a ∈ $e
$e->setDoesNotContain($a);    // true if $a ∉ $e
```

## Logical Operators

Combine propositions:

```php
$rb->logicalAnd($propA, $propB);    // True if both are true
$rb->logicalOr($propA, $propB);     // True if either is true
$rb->logicalXor($propA, $propB);    // True if exactly one is true
$rb->logicalNot($propA);            // Inverts the result
```

## Custom Operators

Extend Ruler with domain-specific operators:

```php
namespace My\Ruler\Operators;

use Ruler\Context;
use Ruler\Operator\VariableOperator;
use Ruler\Proposition;
use Ruler\Value;

class ALotGreaterThan extends VariableOperator implements Proposition
{
    public function evaluate(Context $context): bool
    {
        list($left, $right) = $this->getOperands();
        $value = $right->prepareValue($context)->getValue() * 10;

        return $left->prepareValue($context)->greaterThan(new Value($value));
    }

    protected function getOperandCardinality()
    {
        return static::BINARY;
    }
}
```

Register and use:

```php
$rb->registerOperatorNamespace('My\\Ruler\\Operators');
$rb->create($rb['a']->aLotGreaterThan(10));
```

<a id="doc-docs-rules-and-context"></a>

## Working with Rules

### Combining Rules

Rules are also Propositions, enabling complex rule composition:

```php
// Create individual rules
$aEqualsB = $rb->create($a->equalTo($b));
$aDoesNotEqualB = $rb->create($a->notEqualTo($b));

// Combine into composite rules
$eitherOne = $rb->create($rb->logicalOr($aEqualsB, $aDoesNotEqualB));

$context = new Context([
    'a' => rand(),
    'b' => rand(),
]);

// This is always true!
$eitherOne->evaluate($context);
```

### Evaluating Rules

Use `evaluate()` to get a boolean result:

```php
$context = new Context([
    'userName' => fn() => $_SESSION['userName'] ?? null,
]);

$userIsLoggedIn = $rb->create($rb['userName']->notEqualTo(null));

if ($userIsLoggedIn->evaluate($context)) {
    // Do something for logged-in users
}
```

### Executing Rules with Actions

If a Rule has an action, use `execute()` to run it when the rule evaluates to true:

```php
$hiJustin = $rb->create(
    $rb['userName']->equalTo('bobthecow'),
    fn (Context $context): void => print "Hi, Justin!",
    'greet-justin'
);

$hiJustin->execute($context);  // "Hi, Justin!"
```

### RuleSet: Executing Multiple Rules

```php
$hiJon = $rb->create(
    $rb['userName']->equalTo('jwage'),
    fn (Context $context): void => print "Hey there Jon!",
    'greet-jon'
);

$hiEveryoneElse = $rb->create(
    $rb->logicalAnd(
        $rb->logicalNot($rb->logicalOr($hiJustin, $hiJon)),
        $userIsLoggedIn
    ),
    function (Context $context): void {
        echo sprintf("Hello, %s", $context['userName']);
    },
    'greet-others'
);

$rules = new RuleSet([$hiJustin, $hiJon, $hiEveryoneElse]);

// Add more rules dynamically
$redirectForAuth = $rb->create(
    $rb->logicalNot($userIsLoggedIn),
    function (Context $context): void {
        header('Location: /login');
        exit;
    },
    'redirect-auth'
);
$rules->addRule($redirectForAuth);

// Execute all matching rules
$report = $rules->executeRules($context);
$report->getActionExecutionCount();
```

## Working with Context

### Dynamic Context Population

Context supports both static values and lazy evaluation:

```php
$context = new Context;

// Static values
$context['reallyAnnoyingUsers'] = ['bobthecow', 'jwage'];

// Lazy evaluation
$context['userName'] = fn() => $_SESSION['userName'] ?? null;

// Dependent lazy values
$context['user'] = function() use ($em, $context) {
    if ($userName = $context['userName']) {
        return $em->getRepository('Users')->findByUserName($userName);
    }
};

$context['orderCount'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $em->getRepository('Orders')->findByUser($user)->count();
    }
    return 0;
};
```

### Complex Business Logic

Build rules based on lazy context values:

```php
// Free shipping for loyal customers (excluding problem users)
$rb->create(
    $rb->logicalAnd(
        $rb['orderCount']->greaterThanOrEqualTo(5),
        $rb['reallyAnnoyingUsers']->doesNotContain($rb['userName'])
    ),
    function (Context $context) use ($shipManager): void {
        $shipManager->giveFreeShippingTo($context['user']);
    },
    'free-shipping'
);
```

## Variable Properties

Access properties, methods, and offsets on context values directly:

```php
// Define context with user roles
$context['userRoles'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $user->roles();
    }
    return ['anonymous'];
};

$context['userFullName'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $user->fullName;
    }
};

// Log admin activity
$rb->create(
    $rb->logicalAnd(
        $userIsLoggedIn,
        $rb['userRoles']->contains('admin')
    ),
    function (Context $context) use ($logger): void {
        $logger->info(sprintf("Admin user %s did a thing!", $context['userFullName']));
    },
    'log-admin-activity'
);
```

### Direct Property Access

Instead of creating separate context variables, use VariableProperties:

```php
// Set defaults on VariableProperty
$rb['user']['roles'] = ['anonymous'];

$rb->create(
    $rb->logicalAnd(
        $userIsLoggedIn,
        $rb['user']['roles']->contains('admin')
    ),
    function (Context $context) use ($logger): void {
        $logger->info(sprintf("Admin user %s did a thing!", $context['user']['fullName']));
    },
    'log-admin-property-activity'
);
```

### Property Resolution Order

When the parent Variable resolves to an **object**, VariableProperty looks up:
1. A method with the property name
2. A public property with the property name
3. ArrayAccess + offsetExists

When the parent resolves to an **array**:
1. Array index with the property name

If none match, returns the default value (if set).

<a id="doc-docs-text-dsl"></a>

Ruler supports a Wirefilter-style text-based DSL for creating rules using natural, readable syntax. This complements the fluent PHP API—both produce identical results.

## Quick Start

```php
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;
use Cline\Ruler\Core\Context;

$srb = new StringRuleBuilder;

// Create a rule from text
$rule = $srb->parse('age >= 18 and country == "US"');

// Evaluate with context
$context = new Context(['age' => 25, 'country' => 'US']);
$result = $rule->evaluate($context);  // true

// Add action callback
$rule = $srb->parseWithAction(
    'price + shipping > 100',
    fn() => applyFreeShipping()
);
$rule->execute($context);
```

## Syntax Reference

### Comparison Operators

```php
age > 18                    // Greater than
age >= 21                   // Greater than or equal
age < 65                    // Less than
age <= 64                   // Less than or equal
status == "active"          // Loose equality
status != "inactive"        // Loose inequality
role === "admin"            // Strict equality
role !== "guest"            // Strict inequality
country in ["US", "CA"]     // Array membership
role notIn ["banned"]       // Array exclusion
```

### Logical Operators

```php
age >= 18 and country == "US"                    // AND
age >= 21 or country == "US"                     // OR
not (age < 18)                                   // NOT
(age >= 18 and country == "US") or age >= 21     // Parentheses for grouping
```

### Mathematical Operators

```php
price + shipping > 100      // Addition
total - discount < 500      // Subtraction
quantity * price >= 1000    // Multiplication
total / items > 10          // Division
value % 2 == 0              // Modulo
```

### String Operators

Function-style syntax for string operations:

```php
contains(email, "@example.com")                  // String contains
startsWith(username, "admin_")                   // Starts with
endsWith(filename, ".pdf")                       // Ends with
matches(phone, "^\\d{3}-\\d{4}$")                // Regex match
```

## Complex Examples

### User Eligibility Check

```php
(user.age >= 18 and user.country in ["US", "CA"]) or
(user.isVerified == true and user.role != "guest")
```

### Pricing Logic

```php
(price + shipping > 100 and user.isPremium == true) or
(price > 200)
```

### Combined Conditions

```php
user.age >= 18 and
country == "US" and
not (status == "banned") and
(subscription == "premium" or purchases > 10)
```

## DSL vs Fluent PHP

Both syntaxes produce identical Operator trees:

**DSL Syntax:**
```php
$srb = new StringRuleBuilder;
$rule = $srb->parse('age >= 18 and country == "US"');
```

**Fluent PHP:**
```php
$rb = new RuleBuilder;
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['age']->greaterThanOrEqualTo(18),
        $rb['country']->equalTo('US')
    )
);
```

## Operator Precedence

From highest to lowest:

1. **Parentheses** — `()`
2. **Unary operators** — `not`, `-`
3. **Exponentiation** — `**`
4. **Multiplicative** — `*`, `/`, `%`
5. **Additive** — `+`, `-`
6. **Comparison** — `>`, `>=`, `<`, `<=`
7. **Equality** — `==`, `!=`, `===`, `!==`
8. **Logical AND** — `and`
9. **Logical OR** — `or`

Use parentheses for explicit grouping when precedence is unclear.

## Variables and Properties

Simple names or dot-notation for nested properties:

```php
age                         // Simple variable
user.age                    // Nested property
http.request.uri.path       // Deeply nested
```

## Type Inference

The DSL automatically infers types:

```php
age > 18                    // Numeric
status == "active"          // String
tags in ["urgent"]          // Array
enabled == true             // Boolean
count == null               // Null
```

## When to Use DSL vs Fluent PHP

**Use DSL when:**
- Rules are stored as text (database, config files)
- Non-developers need to read/understand rules
- Brevity is important for complex expressions
- Rules are generated from UI forms

**Use Fluent PHP when:**
- IDE autocomplete is important
- Type safety at the code level is critical
- Rules are built programmatically
- PHP code generation is your workflow

Both approaches coexist seamlessly.
