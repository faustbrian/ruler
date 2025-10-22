# ADR 004: GraphQL Filter DSL

**Status:** Proposed
**Date:** 2025-10-14
**Deciders:** Development Team
**Related:** [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md), [ADR 003: MongoDB Query DSL](003-mongodb-query-dsl.md)

## Context

GraphQL has become the de facto standard for modern API development, especially in frontend-heavy applications. GraphQL's filter syntax provides a type-safe, schema-driven approach to querying that feels natural to developers working in React, Vue, and other modern frameworks. Unlike MongoDB's verbose nested structure, GraphQL filters use a cleaner, more readable object syntax.

### Use Cases
- GraphQL API endpoints with filter arguments
- Frontend developers building dynamic filters
- Type-safe API contracts with schema validation
- React/Vue/Angular applications with GraphQL clients
- Tools like Hasura, Prisma, Postgraphile that expose GraphQL filters
- Admin panels with auto-generated filter UIs from GraphQL schema

### Key Advantages
- **Type-safe** - Schema defines valid fields and operations
- **Frontend-friendly** - Natural syntax for JS/TS developers
- **Tooling** - IDE autocomplete, validation, codegen
- **Composable** - Easy to merge filters programmatically
- **Less verbose** - Cleaner than MongoDB for complex queries
- **Industry momentum** - Widely adopted in modern web apps

## Decision

We will implement a GraphQL-style filter DSL under the `Cline\Ruler\DSL\GraphQL` namespace that accepts GraphQL filter input syntax and compiles it to Ruler's operator tree structure.

### Language Design

#### Syntax Specification

**Basic Comparison Operators:**
```graphql
# Implicit equality
{age: 18}
{country: "US"}
{verified: true}

# Explicit operators
{age: {gte: 18}}
{price: {lt: 100.50}}
{country: {eq: "US"}}
{status: {ne: "banned"}}
{category: {in: ["electronics", "books", "toys"]}}
{status: {notIn: ["deleted", "archived"]}}
{email: {contains: "@example.com"}}
{name: {startsWith: "John"}}
{description: {endsWith: "..."}}
{bio: {isNull: true}}
{deletedAt: {isNull: false}}
```

**Logical Operators (camelCase):**
```graphql
# AND (implicit with multiple fields)
{
  age: {gte: 18},
  country: "US"
}

# OR
{
  OR: [
    {status: "active"},
    {status: "pending"}
  ]
}

# AND (explicit)
{
  AND: [
    {age: {gte: 18}},
    {country: "US"}
  ]
}

# NOT
{
  NOT: {
    status: "banned"
  }
}

# Complex nesting
{
  AND: [
    {
      OR: [
        {age: {gte: 18, lt: 65}},
        {vip: true}
      ]
    },
    {country: {in: ["US", "CA", "UK"]}},
    {
      NOT: {
        status: {in: ["banned", "deleted"]}
      }
    }
  ]
}
```

**Range Queries:**
```graphql
{age: {gte: 18, lte: 65}}
{price: {gt: 10, lt: 100}}
{createdAt: {gte: "2024-01-01", lt: "2025-01-01"}}
```

**String Operators:**
```graphql
{email: {contains: "@example.com"}}
{email: {notContains: "@test.com"}}
{name: {startsWith: "John"}}
{name: {endsWith: "Doe"}}
{code: {match: "^[A-Z]{3}\\d{3}$"}}  # Regex
{country: {containsInsensitive: "united"}}  # Case-insensitive
```

**Array Operators:**
```graphql
{tags: {has: "premium"}}
{tags: {hasEvery: ["premium", "verified"]}}
{tags: {hasSome: ["featured", "trending"]}}
{tags: {isEmpty: false}}
{tags: {size: 3}}
```

**Nested Field Access (dot notation):**
```graphql
{user_profile_age: {gte: 18}}  # Flattened
# OR with nested object
{
  user: {
    profile: {
      age: {gte: 18}
    }
  }
}
```

**Comparison with Other Fields:**
```graphql
# Not standard GraphQL, but useful extension
{
  _compare: {
    left: "balance",
    operator: "gt",
    right: "minimumThreshold"
  }
}
```

**Mathematical Expressions:**
```graphql
# Via computed fields
{
  _computed: {
    expression: {add: ["price", "shipping"]},
    gt: 100
  }
}
```

**Real-World Examples:**

**Example 1: E-commerce Product Filters**
```graphql
{
  AND: [
    {price: {gte: 10, lte: 500}},
    {category: {in: ["electronics", "books"]}},
    {inStock: true},
    {
      OR: [
        {rating: {gte: 4.0}},
        {featured: true}
      ]
    },
    {NOT: {tags: {has: "clearance"}}}
  ]
}
```

**Example 2: User Eligibility Check**
```graphql
{
  AND: [
    {age: {gte: 18}},
    {country: {in: ["US", "CA", "UK"]}},
    {email: {contains: "@"}},
    {emailVerified: true},
    {
      OR: [
        {subscriptionStatus: "active"},
        {trialEndsAt: {gt: "2024-01-01"}}
      ]
    },
    {NOT: {accountStatus: {in: ["suspended", "banned"]}}}
  ]
}
```

**Example 3: Content Moderation**
```graphql
{
  OR: [
    {content: {containsInsensitive: "spam"}},
    {reportCount: {gte: 5}},
    {
      AND: [
        {userReputation: {lt: 10}},
        {links: {size: {gt: 3}}}
      ]
    }
  ]
}
```

#### Supported Operators

**Comparison:**
- `eq` - Equal to (implicit with value)
- `ne` - Not equal to
- `gt` - Greater than
- `gte` - Greater than or equal
- `lt` - Less than
- `lte` - Less than or equal
- `in` - In array
- `notIn` - Not in array

**String:**
- `contains` - Contains substring
- `notContains` - Doesn't contain substring
- `startsWith` - Starts with prefix
- `endsWith` - Ends with suffix
- `match` - Regex match
- `containsInsensitive` - Case-insensitive contains

**Logical:**
- `AND` - All conditions must match
- `OR` - At least one condition matches
- `NOT` - Negates condition

**Null:**
- `isNull` - Field is null/not null (true/false)

**Array:**
- `has` - Array contains value
- `hasEvery` - Array contains all values
- `hasSome` - Array contains at least one value
- `isEmpty` - Array is empty
- `size` - Array length equals

**Type Checking:**
- `isType` - Field type equals ("string", "number", "boolean", etc.)

### Implementation Plan

#### Phase 1: Parser (Week 1)

**1.1 Create GraphQL Filter Parser (`GraphQLParser.php`)**
```php
namespace Cline\Ruler\DSL\GraphQL;

class GraphQLParser
{
    /**
     * Parse GraphQL filter input
     *
     * Accepts JSON string or PHP array
     */
    public function parse(string|array $filter): GraphQLNode
    {
        if (is_string($filter)) {
            $filter = json_decode($filter, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->parseFilter($filter);
    }

    private function parseFilter(array $filter): GraphQLNode
    {
        // Handle logical operators (uppercase)
        if (isset($filter['AND'])) {
            return new LogicalNode('and', array_map(
                fn($f) => $this->parseFilter($f),
                $filter['AND']
            ));
        }

        if (isset($filter['OR'])) {
            return new LogicalNode('or', array_map(
                fn($f) => $this->parseFilter($f),
                $filter['OR']
            ));
        }

        if (isset($filter['NOT'])) {
            return new LogicalNode('not', [
                $this->parseFilter($filter['NOT'])
            ]);
        }

        // Handle field conditions (implicit AND)
        $conditions = [];
        foreach ($filter as $field => $value) {
            $conditions[] = $this->parseFieldCondition($field, $value);
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return new LogicalNode('and', $conditions);
    }

    private function parseFieldCondition(string $field, mixed $value): GraphQLNode
    {
        // Implicit equality
        if (!is_array($value)) {
            return new ComparisonNode('eq', $field, $value);
        }

        // Nested object (dot notation alternative)
        if (!$this->hasOperators($value)) {
            // Flatten: {user: {age: {gte: 18}}} => user.age >= 18
            return $this->parseNestedObject($field, $value);
        }

        // Handle operators
        $conditions = [];
        foreach ($value as $operator => $operandValue) {
            $conditions[] = $this->parseOperator($field, $operator, $operandValue);
        }

        return count($conditions) === 1
            ? $conditions[0]
            : new LogicalNode('and', $conditions);
    }

    private function parseOperator(string $field, string $operator, mixed $value): GraphQLNode
    {
        return match ($operator) {
            'eq' => new ComparisonNode('eq', $field, $value),
            'ne' => new ComparisonNode('ne', $field, $value),
            'gt' => new ComparisonNode('gt', $field, $value),
            'gte' => new ComparisonNode('gte', $field, $value),
            'lt' => new ComparisonNode('lt', $field, $value),
            'lte' => new ComparisonNode('lte', $field, $value),
            'in' => new InNode($field, $value, false),
            'notIn' => new InNode($field, $value, true),
            'contains' => new ContainsNode($field, $value, false, false),
            'notContains' => new ContainsNode($field, $value, true, false),
            'containsInsensitive' => new ContainsNode($field, $value, false, true),
            'startsWith' => new StartsWithNode($field, $value),
            'endsWith' => new EndsWithNode($field, $value),
            'match' => new RegexNode($field, $value),
            'isNull' => new IsNullNode($field, $value),
            'has' => new ArrayHasNode($field, $value),
            'hasEvery' => new ArrayHasEveryNode($field, $value),
            'hasSome' => new ArrayHasSomeNode($field, $value),
            'isEmpty' => new ArrayIsEmptyNode($field, $value),
            'size' => new ArraySizeNode($field, $value),
            'isType' => new TypeCheckNode($field, $value),
            default => throw new \InvalidArgumentException("Unsupported operator: $operator"),
        };
    }

    private function parseNestedObject(string $prefix, array $obj): GraphQLNode
    {
        $conditions = [];
        foreach ($obj as $key => $value) {
            $path = $prefix . '.' . $key;

            if (is_array($value) && !$this->hasOperators($value)) {
                $conditions[] = $this->parseNestedObject($path, $value);
            } else {
                $conditions[] = $this->parseFieldCondition($path, $value);
            }
        }

        return count($conditions) === 1
            ? $conditions[0]
            : new LogicalNode('and', $conditions);
    }

    private function hasOperators(array $value): bool
    {
        $operators = [
            'eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'in', 'notIn',
            'contains', 'notContains', 'containsInsensitive',
            'startsWith', 'endsWith', 'match', 'isNull',
            'has', 'hasEvery', 'hasSome', 'isEmpty', 'size', 'isType'
        ];

        return !empty(array_intersect(array_keys($value), $operators));
    }
}
```

**1.2 Create AST Node Structure (`GraphQLNode.php`)**
```php
namespace Cline\Ruler\DSL\GraphQL;

abstract class GraphQLNode {}

class LogicalNode extends GraphQLNode
{
    public function __construct(
        public string $operator,  // and, or, not
        public array $conditions
    ) {}
}

class ComparisonNode extends GraphQLNode
{
    public function __construct(
        public string $operator,  // eq, ne, gt, gte, lt, lte
        public string $field,
        public mixed $value
    ) {}
}

class InNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public array $values,
        public bool $negated = false
    ) {}
}

class ContainsNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public string $substring,
        public bool $negated = false,
        public bool $caseInsensitive = false
    ) {}
}

class StartsWithNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public string $prefix
    ) {}
}

class EndsWithNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public string $suffix
    ) {}
}

class RegexNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public string $pattern
    ) {}
}

class IsNullNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public bool $shouldBeNull
    ) {}
}

class ArrayHasNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public mixed $value
    ) {}
}

class ArrayHasEveryNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public array $values
    ) {}
}

class ArrayHasSomeNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public array $values
    ) {}
}

class ArrayIsEmptyNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public bool $shouldBeEmpty
    ) {}
}

class ArraySizeNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public int $size
    ) {}
}

class TypeCheckNode extends GraphQLNode
{
    public function __construct(
        public string $field,
        public string $expectedType
    ) {}
}
```

#### Phase 2: Compiler (Week 2)

**2.1 Create Compiler (`GraphQLCompiler.php`)**
```php
namespace Cline\Ruler\DSL\GraphQL;

use Cline\Ruler\Operator\Proposition;
use Cline\Ruler\Variable;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class GraphQLCompiler
{
    public function __construct(
        private FieldResolver $fieldResolver,
        private GraphQLOperatorRegistry $operatorRegistry
    ) {}

    public function compile(GraphQLNode $ast): Proposition
    {
        return $this->compileNode($ast);
    }

    private function compileNode(GraphQLNode $node): mixed
    {
        return match (true) {
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof InNode => $this->compileIn($node),
            $node instanceof ContainsNode => $this->compileContains($node),
            $node instanceof StartsWithNode => $this->compileStartsWith($node),
            $node instanceof EndsWithNode => $this->compileEndsWith($node),
            $node instanceof RegexNode => $this->compileRegex($node),
            $node instanceof IsNullNode => $this->compileIsNull($node),
            $node instanceof ArrayHasNode => $this->compileArrayHas($node),
            $node instanceof ArrayHasEveryNode => $this->compileArrayHasEvery($node),
            $node instanceof ArrayHasSomeNode => $this->compileArrayHasSome($node),
            $node instanceof ArrayIsEmptyNode => $this->compileArrayIsEmpty($node),
            $node instanceof ArraySizeNode => $this->compileArraySize($node),
            $node instanceof TypeCheckNode => $this->compileTypeCheck($node),
            default => throw new \RuntimeException("Unknown node type"),
        };
    }

    private function compileLogical(LogicalNode $node): Proposition
    {
        $operatorClass = $this->operatorRegistry->getLogical($node->operator);
        $compiledConditions = array_map(
            fn($c) => $this->compileNode($c),
            $node->conditions
        );

        return new $operatorClass($compiledConditions);
    }

    private function compileComparison(ComparisonNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $operatorClass = $this->operatorRegistry->getComparison($node->operator);

        return new $operatorClass($field, $node->value);
    }

    private function compileIn(InNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $inOp = new \Cline\Ruler\Operators\Comparison\In($field, $node->values);

        return $node->negated
            ? new \Cline\Ruler\Operators\Logical\LogicalNot([$inOp])
            : $inOp;
    }

    private function compileContains(ContainsNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        // Use StringContains operator or regex
        $pattern = '/' . preg_quote($node->substring, '/') . '/';
        if ($node->caseInsensitive) {
            $pattern .= 'i';
        }

        $regexOp = new \Cline\Ruler\Operators\String\Regex($field, $pattern);

        return $node->negated
            ? new \Cline\Ruler\Operators\Logical\LogicalNot([$regexOp])
            : $regexOp;
    }

    private function compileStartsWith(StartsWithNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $pattern = '/^' . preg_quote($node->prefix, '/') . '/';

        return new \Cline\Ruler\Operators\String\Regex($field, $pattern);
    }

    private function compileEndsWith(EndsWithNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $pattern = '/' . preg_quote($node->suffix, '/') . '$/';

        return new \Cline\Ruler\Operators\String\Regex($field, $pattern);
    }

    private function compileRegex(RegexNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $pattern = '/' . $node->pattern . '/';

        return new \Cline\Ruler\Operators\String\Regex($field, $pattern);
    }

    private function compileIsNull(IsNullNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $isNull = new \Cline\Ruler\Operators\Comparison\EqualTo($field, null);

        return $node->shouldBeNull ? $isNull : new \Cline\Ruler\Operators\Logical\LogicalNot([$isNull]);
    }

    private function compileArrayHas(ArrayHasNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        return new \Cline\Ruler\Operators\Comparison\In($node->value, $field);
    }

    private function compileArrayHasEvery(ArrayHasEveryNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        $conditions = [];
        foreach ($node->values as $value) {
            $conditions[] = new \Cline\Ruler\Operators\Comparison\In($value, $field);
        }

        return new \Cline\Ruler\Operators\Logical\LogicalAnd($conditions);
    }

    private function compileArrayHasSome(ArrayHasSomeNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        $conditions = [];
        foreach ($node->values as $value) {
            $conditions[] = new \Cline\Ruler\Operators\Comparison\In($value, $field);
        }

        return new \Cline\Ruler\Operators\Logical\LogicalOr($conditions);
    }

    private function compileArrayIsEmpty(ArrayIsEmptyNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        $lengthOp = new \Cline\Ruler\Operators\String\StringLength($field);
        $lengthVar = new Variable($this->fieldResolver->getRuleBuilder(), null, $lengthOp);

        $isEmpty = new \Cline\Ruler\Operators\Comparison\EqualTo($lengthVar, 0);

        return $node->shouldBeEmpty ? $isEmpty : new \Cline\Ruler\Operators\Logical\LogicalNot([$isEmpty]);
    }

    private function compileArraySize(ArraySizeNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        $lengthOp = new \Cline\Ruler\Operators\String\StringLength($field);
        $lengthVar = new Variable($this->fieldResolver->getRuleBuilder(), null, $lengthOp);

        return new \Cline\Ruler\Operators\Comparison\EqualTo($lengthVar, $node->size);
    }

    private function compileTypeCheck(TypeCheckNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        $typeOp = new \Cline\Ruler\Operators\Type\TypeOf($field);
        $typeVar = new Variable($this->fieldResolver->getRuleBuilder(), null, $typeOp);

        return new \Cline\Ruler\Operators\Comparison\EqualTo($typeVar, $node->expectedType);
    }
}
```

#### Phase 3: Facade (Week 2)

**3.1 Create GraphQLFilterRuleBuilder**
```php
namespace Cline\Ruler\DSL\GraphQL;

use Cline\Ruler\Rule;
use Cline\Ruler\RuleBuilder;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class GraphQLFilterRuleBuilder
{
    private GraphQLParser $parser;
    private GraphQLCompiler $compiler;

    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        $this->parser = new GraphQLParser();

        $fieldResolver = new FieldResolver($ruleBuilder ?? new RuleBuilder());
        $operatorRegistry = new GraphQLOperatorRegistry();
        $this->compiler = new GraphQLCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parse GraphQL filter and return Rule
     *
     * @param string|array $filter GraphQL filter (JSON string or PHP array)
     * @return Rule Compiled rule ready for evaluation
     */
    public function parse(string|array $filter): Rule
    {
        $ast = $this->parser->parse($filter);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();
        return $rb->create($proposition);
    }
}
```

#### Phase 4: Testing (Week 3)

**4.1 Integration Tests**
```php
test('implicit equality works', function (): void {
    $gql = new GraphQLFilterRuleBuilder();
    $rule = $gql->parse(['age' => 18]);

    expect($rule->evaluate(new Context(['age' => 18])))->toBeTrue();
});

test('gte operator works', function (): void {
    $rule = $gql->parse(['age' => ['gte' => 18]]);
    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
});

test('in operator works', function (): void {
    $rule = $gql->parse(['country' => ['in' => ['US', 'CA']]]);
    expect($rule->evaluate(new Context(['country' => 'US'])))->toBeTrue();
});

test('OR operator works', function (): void {
    $rule = $gql->parse([
        'OR' => [
            ['status' => 'active'],
            ['status' => 'pending']
        ]
    ]);

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
});

test('contains operator works', function (): void {
    $rule = $gql->parse(['email' => ['contains' => '@example.com']]);
    expect($rule->evaluate(new Context(['email' => 'john@example.com'])))->toBeTrue();
});

test('startsWith operator works', function (): void {
    $rule = $gql->parse(['name' => ['startsWith' => 'John']]);
    expect($rule->evaluate(new Context(['name' => 'John Doe'])))->toBeTrue();
});

test('complex nested conditions', function (): void {
    $rule = $gql->parse([
        'AND' => [
            [
                'OR' => [
                    ['age' => ['gte' => 18, 'lt' => 65]],
                    ['vip' => true]
                ]
            ],
            ['country' => ['in' => ['US', 'CA', 'UK']]],
            ['NOT' => ['status' => ['in' => ['banned', 'deleted']]]]
        ]
    ]);

    $valid = new Context(['age' => 30, 'vip' => false, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($valid))->toBeTrue();
});
```

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), GraphQL Filter DSL has the following limitations:

### Unsupported Features

**❌ Inline Arithmetic**
- No mathematical expressions in filters
- **Workaround:** Pre-compute values: `$context['total'] = $price + $shipping`
- **Why:** GraphQL filters are designed for querying, not computation (calculations belong in resolvers)

**❌ Date Operations**
- No native date comparison operators (after, before, betweenDates)
- **Workaround:** Use comparison operators with date strings/timestamps
- **Why:** Date handling varies across GraphQL implementations; use comparison operators instead

**❌ Strict Equality**
- No strict type equality (=== operator)
- GraphQL's eq uses standard equality with type coercion
- **Workaround:** Use MongoDB Query or Wirefilter DSL for strict type checking
- **Why:** GraphQL Filter spec doesn't include strict equality operators

**❌ Extended Logical Operators**
- No XOR, NAND operators (available in MongoDB Query DSL)
- **Workaround:** Compose with AND/OR/NOT: `XOR(a,b)` = `(a OR b) AND NOT(a AND b)`
- **Why:** GraphQL Filter follows standard Boolean logic only

**❌ Action Callbacks**
- Cannot execute code on rule match (feature unique to Wirefilter DSL)
- **Workaround:** Handle actions in resolvers after rule evaluation
- **Why:** JSON-based DSLs are declarative, not imperative

### Supported Features

**✅ Good String Operations (6 operators)**
- **Contains:** `contains` (case-sensitive)
- **Contains (insensitive):** `containsInsensitive`
- **Not contains:** `notContains`
- **Prefix:** `startsWith`
- **Suffix:** `endsWith`
- **Regex:** `match` with standard patterns

**✅ Limited Type Checking**
- **Type check:** `isType` supporting common types (string, number, boolean, array, null)
- Simpler than MongoDB's type system but covers most use cases

**✅ All Comparison Operators**
- Full support: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`
- Implicit equality for cleaner syntax
- Range queries with multiple operators

**✅ Logical Operators**
- AND (explicit and implicit for multiple fields)
- OR, NOT
- Uppercase convention (AND, OR, NOT) vs lowercase field names
- Clean separation between operators and fields

**✅ List Membership**
- `in` and `notIn` for array membership
- `isNull` for null/not null checks

**✅ Nested Properties**
- Nested object syntax: `{user: {profile: {age: {gte: 18}}}}`
- Flattens to dot notation internally: `user.profile.age`

**✅ JSON-Native & Frontend-Friendly**
- Already parsed and validated
- IDE autocomplete support (with GraphQL schemas)
- Type-safe with schema validation
- camelCase naming convention (GraphQL standard)

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison.

## Consequences

### Positive
- **Type-safe** - Schema validation catches errors early
- **Frontend-friendly** - Natural for React/Vue developers
- **Less verbose** - Cleaner than MongoDB for complex queries
- **Excellent tooling** - IDE autocomplete, codegen
- **Industry standard** - Hasura, Prisma, Postgraphile all use similar syntax
- **Composable** - Easy to merge filters programmatically

### Negative
- **Less standardized** - Multiple competing GraphQL filter implementations
- **Schema dependency** - Requires maintaining filter schema
- **Limited adoption** - Not as ubiquitous as MongoDB or SQL

## Verification

### Acceptance Criteria
- [ ] Parse all comparison operators
- [ ] Parse logical operators (AND, OR, NOT)
- [ ] Parse string operators (contains, startsWith, endsWith)
- [ ] Parse array operators (has, hasEvery, hasSome)
- [ ] Support implicit equality and AND
- [ ] Support nested objects as dot notation
- [ ] 100% test coverage

## Timeline

- **Week 1:** Parser + AST
- **Week 2:** Compiler + facade
- **Week 3:** Testing + documentation

**Total Effort:** 3 weeks for 1 senior developer

## References

- [Hasura Filter Syntax](https://hasura.io/docs/latest/queries/postgres/filters/)
- [Prisma Filter API](https://www.prisma.io/docs/concepts/components/prisma-client/filtering-and-sorting)
- [ADR 003: MongoDB Query DSL](003-mongodb-query-dsl.md)
