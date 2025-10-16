# ADR 006: JMESPath DSL

**Status:** Proposed
**Date:** 2025-10-14
**Deciders:** Development Team
**Related:** [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)

## Context

JMESPath is a query language for JSON developed by AWS for filtering and transforming JSON data. It excels at navigating nested structures, array operations, and complex data transformations. While originally designed for querying (not filtering), its powerful expression syntax can be adapted for rule evaluation.

### Use Cases
- Systems already using JMESPath for JSON queries
- Complex nested JSON/array filtering requirements
- AWS-integrated applications (CloudWatch, CLI)
- Advanced array operations (map, filter, reduce)
- JSON transformation pipelines
- Data extraction with filtering

### Key Advantages
- **Powerful array operations** - Best-in-class array handling
- **Nested navigation** - Deep JSON traversal built-in
- **Expressive** - Complex queries in compact syntax
- **Industry adoption** - Used by AWS, Azure CLI
- **Existing libraries** - Mature PHP implementations available

### Key Challenges
- **Query language, not filter language** - Designed for data extraction, not Boolean evaluation
- **Complex syntax** - Steeper learning curve
- **Overkill for simple rules** - Most rule engines don't need projection/transformation
- **Library dependency** - Requires JMESPath parser (e.g., `mtdowling/jmespath.php`)

## Decision

We will implement a JMESPath-style DSL under the `Cline\Ruler\DSL\JMESPath` namespace that accepts JMESPath expressions and compiles them to Ruler's operator tree structure. **Note:** This implementation focuses on the filtering subset of JMESPath, not full projection/transformation capabilities.

### Language Design

#### Syntax Specification

**Basic Comparison:**
```jmespath
age >= `18`
price < `100.50`
country == 'US'
status != 'banned'
```

**Logical Operators:**
```jmespath
age >= `18` && country == 'US'
status == 'active' || status == 'pending'
!(status == 'banned')
(age >= `18` && age < `65`) || vip == `true`
```

**Array Operations:**
```jmespath
# Contains
contains(tags, 'premium')

# Array length
length(tags) > `3`

# Filter array and check result
length(orders[?total > `100`]) > `0`

# Check if any array element matches
orders[?status == 'pending'] | length(@) > `0`
```

**Nested Field Access:**
```jmespath
user.profile.age >= `18`
order.shipping.country == 'US'
metadata.tags[0] == 'featured'
```

**Functions:**
```jmespath
# String functions
starts_with(email, '@example.com')
ends_with(name, ' Jr.')
contains(description, 'important')

# Type checking
type(age) == 'number'
type(verified) == 'boolean'

# Array functions
contains(roles, 'admin')
length(items) >= `5`
max(scores) > `90`
min(prices) >= `10.00`
```

**Real-World Examples:**

**Example 1: User Eligibility**
```jmespath
age >= `18` && age < `65` && contains(['US', 'CA', 'UK'], country) && emailVerified == `true` && !(contains(['banned', 'suspended'], status))
```

**Example 2: Product Filtering**
```jmespath
category == 'electronics' && price >= `10` && price <= `500` && inStock == `true` && (featured == `true` || rating >= `4.0`)
```

**Example 3: Advanced Array Filtering**
```jmespath
# Has at least 3 premium tags
length(tags[?contains(@, 'premium')]) >= `3`

# All orders over $100
length(orders[?total <= `100`]) == `0`

# Any high-value purchase
max_by(orders, &total).total > `1000`
```

**Example 4: Nested Object Checks**
```jmespath
user.profile.age >= `18` && user.profile.verified == `true` && contains(user.permissions, 'write')
```

#### Supported JMESPath Features (Filtering Subset)

**Comparison Operators:**
- `==` - Equal
- `!=` - Not equal
- `>` - Greater than
- `>=` - Greater than or equal
- `<` - Less than
- `<=` - Less than or equal

**Logical Operators:**
- `&&` - AND
- `||` - OR
- `!` - NOT

**Functions:**
- `contains(array, value)` - Array/string contains
- `starts_with(str, prefix)` - String starts with
- `ends_with(str, suffix)` - String ends with
- `length(array|string)` - Length
- `type(value)` - Type checking
- `max(array)` - Maximum value
- `min(array)` - Minimum value
- `sum(array)` - Sum of values
- `avg(array)` - Average value
- `not_null(value)` - Check if not null

**Array Operations:**
- `array[index]` - Array indexing
- `array[?condition]` - Array filtering
- `@` - Current element in filter

**Literals:**
- `` `18` `` - Number literal (backticks)
- `` `true` `` - Boolean literal
- `'text'` - String literal (single quotes)
- `` `null` `` - Null literal

### Implementation Plan

#### Phase 1: Evaluation - Use Existing Library (Week 1)

**1.1 Dependency: mtdowling/jmespath.php**
```bash
composer require mtdowling/jmespath.php
```

**1.2 Create Adapter (`JMESPathAdapter.php`)**
```php
namespace Cline\Ruler\DSL\JMESPath;

use JMESPath\Env as JmesPath;

class JMESPathAdapter
{
    private JmesPath $jmespath;

    public function __construct()
    {
        $this->jmespath = JmesPath::createRuntime();
    }

    /**
     * Evaluate JMESPath expression against data
     *
     * Returns boolean result for rule evaluation
     */
    public function evaluate(string $expression, array $data): bool
    {
        $result = $this->jmespath->search($expression, $data);

        // Convert result to boolean
        return $this->toBoolean($result);
    }

    /**
     * Convert JMESPath result to boolean
     */
    private function toBoolean(mixed $result): bool
    {
        if (is_bool($result)) {
            return $result;
        }

        // Truthy/falsy conversion
        if ($result === null || $result === [] || $result === '') {
            return false;
        }

        if (is_numeric($result)) {
            return $result != 0;
        }

        return true;
    }
}
```

#### Phase 2: Simple Facade (Week 1)

**2.1 Create JMESPathRuleBuilder**
```php
namespace Cline\Ruler\DSL\JMESPath;

use Cline\Ruler\Rule;
use Cline\Ruler\Context;
use Cline\Ruler\RuleBuilder;

class JMESPathRuleBuilder
{
    private JMESPathAdapter $adapter;

    public function __construct()
    {
        $this->adapter = new JMESPathAdapter();
    }

    /**
     * Parse JMESPath expression and return Rule
     *
     * @param string $expression JMESPath expression (must return boolean or truthy value)
     * @return Rule Compiled rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        // Create wrapper rule that evaluates JMESPath
        $rb = new RuleBuilder();

        return $rb->create(
            new JMESPathProposition($expression, $this->adapter)
        );
    }

    /**
     * Validate expression syntax
     */
    public function validate(string $expression): bool
    {
        try {
            $this->adapter->evaluate($expression, []);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
```

**2.2 Create JMESPathProposition**
```php
namespace Cline\Ruler\DSL\JMESPath;

use Cline\Ruler\Operator\Proposition;
use Cline\Ruler\Context;

class JMESPathProposition implements Proposition
{
    public function __construct(
        private string $expression,
        private JMESPathAdapter $adapter
    ) {}

    public function evaluate(Context $context): bool
    {
        return $this->adapter->evaluate(
            $this->expression,
            $context->getArrayCopy()
        );
    }

    public function __toString(): string
    {
        return "JMESPath: {$this->expression}";
    }
}
```

#### Alternative Phase 1-2: Custom Parser (Advanced, Week 1-3)

If we want to avoid library dependency and compile to native Ruler operators:

**1.1 Create JMESPath Parser (`JMESPathParser.php`)**
```php
namespace Cline\Ruler\DSL\JMESPath;

class JMESPathParser
{
    /**
     * Parse JMESPath expression into AST
     *
     * Implements subset of JMESPath grammar focused on filtering
     */
    public function parse(string $expression): JMESPathNode
    {
        $lexer = new JMESPathLexer($expression);
        $tokens = $lexer->tokenize();

        return $this->parseExpression($tokens);
    }

    // Recursive descent parser for JMESPath subset
    // ... (complex implementation)
}
```

**1.2 Create Compiler (`JMESPathCompiler.php`)**
```php
namespace Cline\Ruler\DSL\JMESPath;

class JMESPathCompiler
{
    public function compile(JMESPathNode $ast): Proposition
    {
        // Compile JMESPath AST to Ruler operators
        // ... (complex implementation)
    }
}
```

**Decision:** Start with library-based approach for speed, consider custom parser later if needed.

#### Phase 3: Testing (Week 2)

**3.1 Integration Tests (`JMESPathIntegrationTest.php`)**
```php
test('basic comparison works', function (): void {
    $jmes = new JMESPathRuleBuilder();
    $rule = $jmes->parse('age >= `18`');

    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 16])))->toBeFalse();
});

test('logical AND works', function (): void {
    $rule = $jmes->parse('age >= `18` && country == \'US\'');

    expect($rule->evaluate(new Context(['age' => 20, 'country' => 'US'])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 20, 'country' => 'FR'])))->toBeFalse();
});

test('logical OR works', function (): void {
    $rule = $jmes->parse('status == \'active\' || status == \'pending\'');

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'deleted'])))->toBeFalse();
});

test('contains function works', function (): void {
    $rule = $jmes->parse('contains(tags, \'premium\')');

    expect($rule->evaluate(new Context(['tags' => ['premium', 'verified']])))->toBeTrue();
    expect($rule->evaluate(new Context(['tags' => ['basic']])))->toBeFalse();
});

test('starts_with function works', function (): void {
    $rule = $jmes->parse('starts_with(email, \'admin\')');

    expect($rule->evaluate(new Context(['email' => 'admin@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => 'user@example.com'])))->toBeFalse();
});

test('array length works', function (): void {
    $rule = $jmes->parse('length(tags) > `3`');

    expect($rule->evaluate(new Context(['tags' => ['a', 'b', 'c', 'd']])))->toBeTrue();
    expect($rule->evaluate(new Context(['tags' => ['a', 'b']])))->toBeFalse();
});

test('nested field access works', function (): void {
    $rule = $jmes->parse('user.profile.age >= `18`');

    $context = new Context([
        'user' => ['profile' => ['age' => 25]]
    ]);
    expect($rule->evaluate($context))->toBeTrue();
});

test('array filtering works', function (): void {
    $rule = $jmes->parse('length(orders[?total > `100`]) > `0`');

    $context = new Context([
        'orders' => [
            ['total' => 150],
            ['total' => 50],
            ['total' => 200]
        ]
    ]);
    expect($rule->evaluate($context))->toBeTrue();
});

test('max function works', function (): void {
    $rule = $jmes->parse('max(scores) > `90`');

    expect($rule->evaluate(new Context(['scores' => [75, 88, 95, 82]])))->toBeTrue();
    expect($rule->evaluate(new Context(['scores' => [75, 88, 82]])))->toBeFalse();
});

test('complex expression works', function (): void {
    $rule = $jmes->parse(
        'age >= `18` && age < `65` && contains([\'US\', \'CA\', \'UK\'], country) && emailVerified == `true`'
    );

    $valid = new Context([
        'age' => 30,
        'country' => 'US',
        'emailVerified' => true
    ]);
    expect($rule->evaluate($valid))->toBeTrue();
});

test('type checking works', function (): void {
    $rule = $jmes->parse('type(age) == \'number\'');

    expect($rule->evaluate(new Context(['age' => 25])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => '25'])))->toBeFalse();
});

test('not_null function works', function (): void {
    $rule = $jmes->parse('not_null(email)');

    expect($rule->evaluate(new Context(['email' => 'test@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => null])))->toBeFalse();
});
```

#### Phase 4: Documentation (Week 2)

**4.1 Create Cookbook (`cookbook/jmespath-syntax.md`)**
**4.2 Function Reference**
**4.3 Migration Guide from Pure JMESPath**

### Architecture

**Simple Approach (Using Library):**
```
DSL/
└── JMESPath/
    ├── JMESPathRuleBuilder.php      # Main facade
    ├── JMESPathAdapter.php           # Wraps mtdowling/jmespath.php
    └── JMESPathProposition.php       # Evaluates expression against context
```

**Advanced Approach (Custom Parser):**
```
DSL/
└── JMESPath/
    ├── JMESPathRuleBuilder.php      # Main facade
    ├── JMESPathParser.php            # Text → AST
    ├── JMESPathLexer.php             # Tokenization
    ├── JMESPathNode.php              # AST node definitions
    ├── JMESPathCompiler.php          # AST → Operator tree
    └── JMESPathOperatorRegistry.php  # JMESPath → Ruler operator mapping
```

### Dependencies

**Required:**
- `mtdowling/jmespath.php` - JMESPath parser and evaluator

**Alternative:**
- None (if implementing custom parser)

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), JMESPath DSL has significant limitations as it's a query language adapted for filtering:

### Unsupported Features

**❌ Inline Arithmetic**
- No mathematical expressions in filters
- **Workaround:** Pre-compute values: `$context['total'] = $price + $shipping`
- **Why:** JMESPath is a query language for extracting/transforming data, not computation

**❌ Strict Equality**
- No strict type equality (=== operator) in JMESPath spec
- **Workaround:** Combine equality with type check: `` value == `42` && type(value) == 'number' ``
- **Why:** JMESPath spec doesn't distinguish between strict and loose equality

**❌ Date Operations**
- No native date comparison operators
- **Workaround:** Use comparison operators with date strings/timestamps
- **Why:** JMESPath focuses on JSON querying, not domain-specific operations

**❌ Advanced String Operations**
- No regex support in JMESPath spec
- **Workaround:** Use `starts_with()`, `ends_with()`, `contains()` functions for basic patterns
- **Why:** JMESPath is designed for cross-platform JSON querying; regex would require consistent implementations

**❌ Action Callbacks**
- Cannot execute code on rule match (feature unique to Wirefilter DSL)
- **Workaround:** Handle actions in application code after rule evaluation
- **Why:** JMESPath is declarative query language, not execution framework

**❌ Extended Logical Operators**
- No XOR, NAND operators
- **Workaround:** Compose with && and || operators
- **Why:** JMESPath spec includes only standard Boolean operators

### Supported Features

**✅ Powerful Array Operations**
- **Best-in-class array handling** across all DSLs
- Array filtering: `orders[?total > \`100\`]`
- Array functions: `length()`, `max()`, `min()`, `sum()`, `avg()`
- Array projection and transformation
- Current element reference: `@`

**✅ Type Checking**
- **Type function:** `` type(value) == 'number' ``
- Returns: number, string, boolean, array, object, null
- Enables type validation in filters

**✅ Limited String Functions**
- `contains(haystack, needle)` - substring/array contains
- `starts_with(str, prefix)` - string starts with
- `ends_with(str, suffix)` - string ends with
- **Note:** No regex support (intentional cross-platform decision)

**✅ All Comparison Operators**
- Full support: `==`, `!=`, `>`, `>=`, `<`, `<=`
- Proper operator precedence

**✅ Logical Operators (JavaScript style)**
- AND: `&&` (not `and`)
- OR: `||` (not `or`)
- NOT: `!` (not `not`)
- Follows JavaScript conventions

**✅ Nested Property Access**
- Dot notation: `user.profile.age >= \`18\``
- Array indexing: `items[0]`
- Deep navigation built-in

**✅ Industry Standard**
- AWS standard (CloudWatch, CLI)
- Mature PHP implementation (`mtdowling/jmespath.php`)
- Multi-language support (JavaScript, Python, Go, etc.)

**✅ Backtick Literals**
- Number: `` `18` ``
- Boolean: `` `true` ``
- Null: `` `null` ``
- Ensures unambiguous literal values

### When to Use JMESPath

- **Best for:** Complex nested JSON/array filtering
- **Best for:** AWS-integrated applications
- **Best for:** Advanced array operations (map, filter, reduce patterns)
- **Avoid for:** Simple rules (use Wirefilter instead)
- **Avoid for:** String pattern matching (limited regex support)

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison.

## Consequences

### Positive
- **Powerful array operations** - Best DSL for complex array filtering
- **Nested navigation** - Deep JSON traversal is trivial
- **Mature library** - Battle-tested JMESPath implementation
- **Industry standard** - AWS, Azure CLI adoption
- **Expressive** - Complex queries in readable syntax
- **Existing knowledge** - Developers may already know JMESPath

### Negative
- **Overkill for simple rules** - Most rules don't need this power
- **Library dependency** - Adds external dependency
- **Learning curve** - More complex than Wirefilter
- **Not filter-first** - Designed for querying, adapted for filtering
- **Performance** - Library may be slower than native operators
- **Backtick literals** - Unconventional syntax for numbers/booleans

### Neutral
- Best for systems already using JMESPath elsewhere
- Consider library-based approach for faster implementation
- Custom parser would be significant effort (3+ weeks)

## Alternatives Considered

### JSONPath
- **Pros:** Similar capabilities, slightly simpler
- **Cons:** Less standardized, fewer PHP implementations
- **Decision:** JMESPath is more mature and AWS-backed

### Custom Array DSL
- **Pros:** Tailored exactly to our needs
- **Cons:** Zero ecosystem, requires full documentation
- **Decision:** JMESPath provides same capabilities with existing adoption

### Extend Wirefilter with Functions
- **Pros:** Keeps syntax consistent
- **Cons:** Would need to implement all JMESPath functions anyway
- **Decision:** If you need advanced array ops, use JMESPath; otherwise use Wirefilter

## Implementation Risks

### Medium Risk
1. **Library dependency** - External library adds maintenance burden
   - Mitigation: Consider custom parser for Phase 2, benchmark performance

2. **Expression complexity** - JMESPath can create very complex expressions
   - Mitigation: Document recommended complexity limits, provide linting tools

### Low Risk
1. **Truthy conversion** - JMESPath returns various types, need consistent boolean conversion
   - Mitigation: Clear conversion rules, extensive tests

## Verification

### Acceptance Criteria
- [ ] Parse all comparison operators
- [ ] Parse logical operators (&&, ||, !)
- [ ] Support string functions (contains, starts_with, ends_with)
- [ ] Support array functions (length, max, min, sum, avg)
- [ ] Support nested field access with dot notation
- [ ] Support array filtering [?condition]
- [ ] Support array indexing [0]
- [ ] Support type checking
- [ ] 100% test coverage without mocks
- [ ] Performance acceptable (< 10ms for complex expressions)
- [ ] Documentation with 20+ examples

### Performance Targets
- Parse + evaluate simple rule: < 2ms
- Parse + evaluate complex rule: < 10ms
- Memory: < 2MB per parser instance (with library)

### Testing Strategy
1. **Unit Tests** - Adapter, proposition
2. **Integration Tests** - End-to-end with various expressions
3. **Array Operation Tests** - All array functions
4. **Edge Cases** - Type conversion, null handling
5. **Performance Tests** - Benchmark vs other DSLs
6. **Comparison Tests** - Verify same logic as other DSLs

## Timeline

**Simple Approach (Using Library):**
- **Week 1:** Adapter + facade + basic tests
- **Week 2:** Advanced tests + documentation

**Total Effort:** 2 weeks for 1 senior developer

**Advanced Approach (Custom Parser):**
- **Week 1-2:** Parser + lexer + AST
- **Week 3:** Compiler + facade
- **Week 4:** Testing + documentation

**Total Effort:** 4 weeks for 1 senior developer

## Recommendation

**Start with library-based approach:**
1. Faster implementation (2 weeks vs 4 weeks)
2. Leverages battle-tested JMESPath library
3. Can always build custom parser later if needed
4. Gets us 90% of value with 50% of effort

**Consider custom parser if:**
- Library performance is insufficient
- Want to avoid external dependency
- Need to compile to native Ruler operators for introspection
- Want to extend syntax with custom operators

## References

- [JMESPath Specification](https://jmespath.org/specification.html)
- [JMESPath Tutorial](https://jmespath.org/tutorial.html)
- [mtdowling/jmespath.php](https://github.com/jmespath/jmespath.php)
- [AWS CLI JMESPath Documentation](https://docs.aws.amazon.com/cli/latest/userguide/cli-usage-filter.html)
- [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)
