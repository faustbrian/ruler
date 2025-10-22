# ADR 002: SQL-WHERE Style DSL

**Status:** Proposed
**Date:** 2025-10-14
**Deciders:** Development Team
**Related:** [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)

## Context

Developers with database backgrounds find SQL WHERE clause syntax highly intuitive. This DSL would provide familiar syntax for rule definition, reducing cognitive load and training requirements. SQL-WHERE syntax is battle-tested, widely understood, and offers excellent readability for complex conditional logic.

### Use Cases
- Database-centric applications where rules mirror query logic
- Teams with strong SQL expertise
- Rules that closely map to database filtering requirements
- Business analysts familiar with SQL reporting tools

## Decision

We will implement a SQL-WHERE style DSL under the `Cline\Ruler\DSL\SqlWhere` namespace that accepts standard SQL WHERE clause syntax and compiles it to Ruler's operator tree structure.

### Language Design

#### Syntax Specification

**Basic Comparison Operators:**
```sql
age >= 18
price < 100.50
country = 'US'
status != 'banned'
name LIKE 'John%'
email NOT LIKE '%@test.com'
category IN ('electronics', 'books', 'toys')
status NOT IN ('deleted', 'archived')
bio IS NULL
verified IS NOT NULL
created_at BETWEEN '2024-01-01' AND '2024-12-31'
```

**Logical Operators:**
```sql
age >= 18 AND country = 'US'
status = 'active' OR status = 'pending'
NOT (status = 'banned')
(age >= 18 AND age < 65) OR vip = true
```

**Mathematical Operators:**
```sql
price + shipping > 100
quantity * unit_price >= 500
(total - discount) / 2 > minimum_threshold
age % 2 = 0  -- even ages
```

**Dot Notation for Nested Fields:**
```sql
user.profile.age >= 18
order.shipping.country = 'US'
metadata.tags[0] = 'premium'
```

**String Functions:**
```sql
UPPER(country) = 'US'
LOWER(email) LIKE '%@example.com'
LENGTH(description) > 100
CONCAT(first_name, ' ', last_name) = 'John Doe'
```

**Date Functions:**
```sql
YEAR(created_at) = 2024
MONTH(created_at) IN (1, 2, 3)  -- Q1
DATEDIFF(expires_at, NOW()) > 30
```

**Aggregate-style Operators (for array fields):**
```sql
COUNT(tags) > 3
SUM(line_items.quantity) >= 10
MAX(scores) > 90
MIN(prices) >= 5.00
```

**Case Sensitivity:**
```sql
-- Default: case-sensitive
name = 'John'

-- Explicit case-insensitive
UPPER(name) = 'JOHN'
name COLLATE NOCASE = 'john'
```

**Operator Precedence (standard SQL):**
1. `()` - Parentheses
2. `NOT`
3. `*`, `/`, `%` - Multiplication, Division, Modulo
4. `+`, `-` - Addition, Subtraction
5. `=`, `!=`, `<`, `>`, `<=`, `>=`, `LIKE`, `IN`, `BETWEEN`, `IS NULL`, `IS NOT NULL`
6. `AND`
7. `OR`

#### Reserved Keywords
```
AND, OR, NOT, IN, LIKE, BETWEEN, IS, NULL, TRUE, FALSE,
UPPER, LOWER, LENGTH, CONCAT, YEAR, MONTH, DAY, DATEDIFF, NOW,
COUNT, SUM, MAX, MIN, AVG, COLLATE, NOCASE
```

### Implementation Plan

#### Phase 1: Core Parser (Week 1-2)

**1.1 Create SQL Parser (`SqlParser.php`)**
```php
namespace Cline\Ruler\DSL\SqlWhere;

use PhpParser\Lexer;
use PhpParser\Parser;

class SqlParser
{
    /**
     * Parse SQL WHERE expression into AST
     *
     * Uses a custom SQL lexer/parser or leverages existing library
     * like doctrine/lexer or a lightweight SQL parser
     */
    public function parse(string $sql): SqlNode
    {
        // Tokenize SQL string
        // Build AST with proper precedence
        // Return root SqlNode
    }

    /**
     * Extract field names from SQL expression
     */
    private function extractFieldNames(string $sql): array
    {
        // Extract all identifiers that aren't reserved keywords
    }
}
```

**1.2 Create AST Node Structure (`SqlNode.php`)**
```php
namespace Cline\Ruler\DSL\SqlWhere;

abstract class SqlNode {}

class BinaryOpNode extends SqlNode
{
    public function __construct(
        public string $operator,
        public SqlNode $left,
        public SqlNode $right
    ) {}
}

class UnaryOpNode extends SqlNode
{
    public function __construct(
        public string $operator,
        public SqlNode $operand
    ) {}
}

class FieldNode extends SqlNode
{
    public function __construct(public string $fieldName) {}
}

class LiteralNode extends SqlNode
{
    public function __construct(public mixed $value) {}
}

class FunctionNode extends SqlNode
{
    public function __construct(
        public string $functionName,
        public array $arguments
    ) {}
}

class InNode extends SqlNode
{
    public function __construct(
        public SqlNode $field,
        public array $values,
        public bool $negated = false
    ) {}
}

class BetweenNode extends SqlNode
{
    public function __construct(
        public SqlNode $field,
        public SqlNode $min,
        public SqlNode $max
    ) {}
}

class LikeNode extends SqlNode
{
    public function __construct(
        public SqlNode $field,
        public string $pattern,
        public bool $negated = false,
        public bool $caseInsensitive = false
    ) {}
}

class IsNullNode extends SqlNode
{
    public function __construct(
        public SqlNode $field,
        public bool $negated = false
    ) {}
}
```

**1.3 Create Lexer (`SqlLexer.php`)**
```php
namespace Cline\Ruler\DSL\SqlWhere;

class SqlLexer
{
    private const KEYWORDS = [
        'AND', 'OR', 'NOT', 'IN', 'LIKE', 'BETWEEN', 'IS', 'NULL',
        'TRUE', 'FALSE', 'UPPER', 'LOWER', 'LENGTH', /* ... */
    ];

    public function tokenize(string $sql): array
    {
        // Return array of Token objects
        // Handle quoted strings, numbers, identifiers, operators
    }
}

class Token
{
    public function __construct(
        public string $type,  // KEYWORD, IDENTIFIER, NUMBER, STRING, OPERATOR
        public mixed $value,
        public int $position
    ) {}
}
```

#### Phase 2: Operator Mapping (Week 2)

**2.1 Create Operator Registry Extension (`SqlOperatorRegistry.php`)**
```php
namespace Cline\Ruler\DSL\SqlWhere;

class SqlOperatorRegistry
{
    private const COMPARISON_MAP = [
        '=' => 'eq',
        '!=' => 'neq',
        '<>' => 'neq',  // SQL alternative syntax
        '>' => 'gt',
        '<' => 'lt',
        '>=' => 'gte',
        '<=' => 'lte',
    ];

    private const LOGICAL_MAP = [
        'AND' => 'and',
        'OR' => 'or',
        'NOT' => 'not',
    ];

    private const FUNCTION_MAP = [
        'UPPER' => 'stringToUpper',
        'LOWER' => 'stringToLower',
        'LENGTH' => 'stringLength',
        'CONCAT' => 'stringConcat',
        // Maps to custom String operators we'll need to create
    ];

    public function mapOperator(string $sqlOp): string
    {
        return self::COMPARISON_MAP[$sqlOp]
            ?? self::LOGICAL_MAP[$sqlOp]
            ?? throw new \InvalidArgumentException("Unknown operator: $sqlOp");
    }

    public function mapFunction(string $functionName): string
    {
        return self::FUNCTION_MAP[strtoupper($functionName)]
            ?? throw new \InvalidArgumentException("Unknown function: $functionName");
    }
}
```

**2.2 Create New String Operators (if needed)**
```php
namespace Cline\Ruler\Operators\String;

class StringToUpper extends VariableOperator
{
    public function prepareValue(mixed $value): string
    {
        return strtoupper((string) $value);
    }
}

class StringToLower extends VariableOperator { /* ... */ }
class StringLength extends VariableOperator { /* ... */ }
class StringConcat extends VariableOperator { /* ... */ }
```

#### Phase 3: Compiler (Week 3)

**3.1 Create SQL Compiler (`SqlCompiler.php`)**
```php
namespace Cline\Ruler\DSL\SqlWhere;

use Cline\Ruler\Operator\Proposition;
use Cline\Ruler\Variable;
use Cline\Ruler\RuleBuilder;

class SqlCompiler
{
    public function __construct(
        private FieldResolver $fieldResolver,
        private SqlOperatorRegistry $operatorRegistry
    ) {}

    public function compile(SqlNode $ast): Proposition
    {
        return $this->compileNode($ast);
    }

    private function compileNode(SqlNode $node): mixed
    {
        return match (true) {
            $node instanceof BinaryOpNode => $this->compileBinaryOp($node),
            $node instanceof UnaryOpNode => $this->compileUnaryOp($node),
            $node instanceof FieldNode => $this->compileField($node),
            $node instanceof LiteralNode => $this->compileLiteral($node),
            $node instanceof FunctionNode => $this->compileFunction($node),
            $node instanceof InNode => $this->compileIn($node),
            $node instanceof BetweenNode => $this->compileBetween($node),
            $node instanceof LikeNode => $this->compileLike($node),
            $node instanceof IsNullNode => $this->compileIsNull($node),
            default => throw new \RuntimeException("Unknown node type"),
        };
    }

    private function compileBinaryOp(BinaryOpNode $node): Proposition
    {
        $left = $this->compileNode($node->left);
        $right = $this->compileNode($node->right);

        $operatorName = $this->operatorRegistry->mapOperator($node->operator);

        // Handle mathematical operators (return Variable)
        if (in_array($operatorName, ['add', 'subtract', 'multiply', 'divide', 'modulo'])) {
            $operatorClass = $this->operatorRegistry->getClass($operatorName);
            $mathOp = new $operatorClass($left, $right);
            return new Variable($this->fieldResolver->getRuleBuilder(), null, $mathOp);
        }

        // Handle logical operators (need array wrapping)
        if (in_array($operatorName, ['and', 'or'])) {
            $operatorClass = $this->operatorRegistry->getClass($operatorName);
            return new $operatorClass([$left, $right]);
        }

        // Handle comparison operators
        $operatorClass = $this->operatorRegistry->getClass($operatorName);
        return new $operatorClass($left, $right);
    }

    private function compileIn(InNode $node): Proposition
    {
        $field = $this->compileNode($node->field);
        $values = array_map(fn($v) => $v instanceof LiteralNode ? $v->value : $v, $node->values);

        $inOperator = new \Cline\Ruler\Operators\Comparison\In($field, $values);

        return $node->negated
            ? new \Cline\Ruler\Operators\Logical\LogicalNot([$inOperator])
            : $inOperator;
    }

    private function compileBetween(BetweenNode $node): Proposition
    {
        // BETWEEN x AND y compiles to: field >= x AND field <= y
        $field = $this->compileNode($node->field);
        $min = $this->compileNode($node->min);
        $max = $this->compileNode($node->max);

        $gte = new \Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo($field, $min);
        $lte = new \Cline\Ruler\Operators\Comparison\LessThanOrEqualTo($field, $max);

        return new \Cline\Ruler\Operators\Logical\LogicalAnd([$gte, $lte]);
    }

    private function compileLike(LikeNode $node): Proposition
    {
        $field = $this->compileNode($node->field);

        // Convert SQL LIKE pattern to regex
        // % -> .*
        // _ -> .
        $pattern = str_replace(
            ['%', '_'],
            ['.*', '.'],
            preg_quote($node->pattern, '/')
        );
        $pattern = '/^' . $pattern . '$/';

        if ($node->caseInsensitive) {
            $pattern .= 'i';
        }

        $regexOp = new \Cline\Ruler\Operators\String\Regex($field, $pattern);

        return $node->negated
            ? new \Cline\Ruler\Operators\Logical\LogicalNot([$regexOp])
            : $regexOp;
    }

    private function compileIsNull(IsNullNode $node): Proposition
    {
        $field = $this->compileNode($node->field);

        $eqNull = new \Cline\Ruler\Operators\Comparison\EqualTo($field, null);

        return $node->negated
            ? new \Cline\Ruler\Operators\Logical\LogicalNot([$eqNull])
            : $eqNull;
    }

    private function compileFunction(FunctionNode $node): Variable
    {
        $operatorName = $this->operatorRegistry->mapFunction($node->functionName);
        $operatorClass = $this->operatorRegistry->getClass($operatorName);

        $args = array_map(fn($arg) => $this->compileNode($arg), $node->arguments);

        $functionOp = new $operatorClass(...$args);
        return new Variable($this->fieldResolver->getRuleBuilder(), null, $functionOp);
    }

    private function compileField(FieldNode $node): Variable
    {
        return $this->fieldResolver->resolve($node->fieldName);
    }

    private function compileLiteral(LiteralNode $node): mixed
    {
        return $node->value;
    }
}
```

**3.2 Reuse FieldResolver from Wirefilter**
```php
// No changes needed - same dot notation resolution
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
```

#### Phase 4: Main Facade (Week 3)

**4.1 Create SqlWhereRuleBuilder**
```php
namespace Cline\Ruler\DSL\SqlWhere;

use Cline\Ruler\Rule;
use Cline\Ruler\RuleBuilder;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class SqlWhereRuleBuilder
{
    private SqlParser $parser;
    private SqlCompiler $compiler;
    private ?RuleBuilder $ruleBuilder;

    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        $this->ruleBuilder = $ruleBuilder;
        $this->parser = new SqlParser();

        $fieldResolver = new FieldResolver($ruleBuilder ?? new RuleBuilder());
        $operatorRegistry = new SqlOperatorRegistry();
        $this->compiler = new SqlCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parse SQL WHERE clause and return Rule
     *
     * @param string $sql SQL WHERE clause (without the 'WHERE' keyword)
     * @return Rule Compiled rule ready for evaluation
     *
     * @throws \InvalidArgumentException if SQL syntax is invalid
     */
    public function parse(string $sql): Rule
    {
        $ast = $this->parser->parse($sql);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();
        return $rb->create($proposition);
    }

    /**
     * Validate SQL syntax without creating Rule
     */
    public function validate(string $sql): bool
    {
        try {
            $this->parser->parse($sql);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
```

#### Phase 5: Testing (Week 4)

**5.1 Parser Tests (`SqlParserTest.php`)**
```php
test('parses basic comparison', function (): void {
    $parser = new SqlParser();
    $ast = $parser->parse("age >= 18");

    expect($ast)->toBeInstanceOf(BinaryOpNode::class)
        ->and($ast->operator)->toBe('>=')
        ->and($ast->left)->toBeInstanceOf(FieldNode::class)
        ->and($ast->right)->toBeInstanceOf(LiteralNode::class);
});

test('parses IN operator', function (): void {
    $ast = $parser->parse("country IN ('US', 'CA', 'UK')");
    expect($ast)->toBeInstanceOf(InNode::class);
});

test('parses BETWEEN operator', function (): void {
    $ast = $parser->parse("age BETWEEN 18 AND 65");
    expect($ast)->toBeInstanceOf(BetweenNode::class);
});

test('parses LIKE pattern', function (): void {
    $ast = $parser->parse("email LIKE '%@example.com'");
    expect($ast)->toBeInstanceOf(LikeNode::class);
});

test('parses IS NULL', function (): void {
    $ast = $parser->parse("deleted_at IS NULL");
    expect($ast)->toBeInstanceOf(IsNullNode::class);
});

test('parses complex nested logic', function (): void {
    $ast = $parser->parse(
        "(age >= 18 AND country = 'US') OR (vip = true AND status != 'banned')"
    );
    expect($ast)->toBeInstanceOf(BinaryOpNode::class)
        ->and($ast->operator)->toBe('OR');
});

test('handles operator precedence', function (): void {
    $ast = $parser->parse("a = 1 OR b = 2 AND c = 3");
    // Should parse as: a = 1 OR (b = 2 AND c = 3)
    expect($ast->operator)->toBe('OR')
        ->and($ast->right)->toBeInstanceOf(BinaryOpNode::class);
});
```

**5.2 Integration Tests (`SqlWhereIntegrationTest.php`)**
```php
test('basic comparison works', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("age >= 18");

    $context = new Context(['age' => 20]);
    expect($rule->evaluate($context))->toBeTrue();

    $context = new Context(['age' => 16]);
    expect($rule->evaluate($context))->toBeFalse();
});

test('IN operator works', function (): void {
    $rule = $srb->parse("country IN ('US', 'CA', 'UK')");

    expect($rule->evaluate(new Context(['country' => 'US'])))->toBeTrue();
    expect($rule->evaluate(new Context(['country' => 'FR'])))->toBeFalse();
});

test('BETWEEN operator works', function (): void {
    $rule = $srb->parse("age BETWEEN 18 AND 65");

    expect($rule->evaluate(new Context(['age' => 30])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 70])))->toBeFalse();
});

test('LIKE pattern matching works', function (): void {
    $rule = $srb->parse("email LIKE '%@example.com'");

    expect($rule->evaluate(new Context(['email' => 'john@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => 'john@test.com'])))->toBeFalse();
});

test('IS NULL works', function (): void {
    $rule = $srb->parse("deleted_at IS NULL");

    expect($rule->evaluate(new Context(['deleted_at' => null])))->toBeTrue();
    expect($rule->evaluate(new Context(['deleted_at' => '2024-01-01'])))->toBeFalse();
});

test('complex nested conditions', function (): void {
    $rule = $srb->parse(
        "(age >= 18 AND age < 65) AND (country = 'US' OR country = 'CA') AND status != 'banned'"
    );

    $valid = new Context(['age' => 30, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($valid))->toBeTrue();

    $invalid = new Context(['age' => 70, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($invalid))->toBeFalse();
});

test('mathematical expressions work', function (): void {
    $rule = $srb->parse("price + shipping > 100");

    expect($rule->evaluate(new Context(['price' => 80, 'shipping' => 25])))->toBeTrue();
    expect($rule->evaluate(new Context(['price' => 80, 'shipping' => 10])))->toBeFalse();
});

test('string functions work', function (): void {
    $rule = $srb->parse("UPPER(country) = 'US'");

    expect($rule->evaluate(new Context(['country' => 'us'])))->toBeTrue();
    expect($rule->evaluate(new Context(['country' => 'ca'])))->toBeFalse();
});

test('dot notation for nested fields', function (): void {
    $rule = $srb->parse("user.profile.age >= 18");

    $context = new Context([
        'user' => ['profile' => ['age' => 25]]
    ]);
    expect($rule->evaluate($context))->toBeTrue();
});

test('NOT IN operator', function (): void {
    $rule = $srb->parse("status NOT IN ('deleted', 'banned')");

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'deleted'])))->toBeFalse();
});

test('IS NOT NULL operator', function (): void {
    $rule = $srb->parse("email IS NOT NULL");

    expect($rule->evaluate(new Context(['email' => 'test@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => null])))->toBeFalse();
});
```

**5.3 Edge Cases Tests**
```php
test('handles single quotes in strings', function (): void {
    $rule = $srb->parse("name = 'O''Brien'");  // SQL escape
    expect($rule->evaluate(new Context(['name' => "O'Brien"])))->toBeTrue();
});

test('handles numeric strings correctly', function (): void {
    $rule = $srb->parse("code = '123'");
    expect($rule->evaluate(new Context(['code' => '123'])))->toBeTrue();
    expect($rule->evaluate(new Context(['code' => 123])))->toBeFalse();  // Type matters
});

test('case sensitivity in comparisons', function (): void {
    $rule = $srb->parse("country = 'US'");
    expect($rule->evaluate(new Context(['country' => 'us'])))->toBeFalse();
});

test('whitespace handling', function (): void {
    $rule = $srb->parse("  age   >=   18  ");
    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
});
```

#### Phase 6: Documentation (Week 4)

**6.1 Update Cookbook (`cookbook/sql-where-syntax.md`)**
**6.2 Add Examples to README**
**6.3 API Documentation**

### Architecture

```
DSL/
├── Wirefilter/           # Existing
└── SqlWhere/             # New
    ├── SqlWhereRuleBuilder.php  # Main facade
    ├── SqlParser.php            # SQL tokenizer + parser
    ├── SqlLexer.php             # Tokenization
    ├── SqlNode.php              # AST node definitions
    ├── SqlCompiler.php          # AST → Operator tree
    └── SqlOperatorRegistry.php  # SQL → Ruler operator mapping
```

### Dependencies

**Required:**
- None (implement custom SQL parser)

**Optional (alternative approach):**
- `doctrine/lexer` - For tokenization
- Custom recursive descent parser for SQL WHERE subset

**Recommended approach:** Custom implementation to avoid heavy dependencies and maintain control over syntax extensions.

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), SQL WHERE DSL has the following limitations:

### Unsupported Features

**❌ Inline Arithmetic**
- No mathematical expressions in filters (cannot use `price + shipping > 100`)
- **Workaround:** Pre-compute values before rule evaluation: `$context['total'] = $price + $shipping`
- **Why:** SQL WHERE clauses focus on filtering, not computation (use SELECT for calculations)

**❌ Date Operations**
- No native date comparison operators (BEFORE, AFTER, BETWEEN dates)
- **Workaround:** Use comparison operators with date strings/timestamps, or pre-compute
- **Why:** Date handling varies significantly across SQL dialects (MySQL, PostgreSQL, etc.)

**❌ Advanced Type Checking**
- No built-in type operators beyond basic IS NULL
- **Workaround:** Validate types at application layer before passing to context
- **Why:** SQL type system differs from PHP's dynamic typing

**❌ Strict Equality**
- No strict type equality (=== operator)
- SQL's = operator has its own type coercion rules
- **Workaround:** Ensure types match in context, or use Wirefilter/MongoDB DSL for strict equality
- **Why:** SQL doesn't distinguish strict vs loose equality

###  Supported Features

**✅ All Comparison Operators**
- Full SQL comparison support: =, !=, <>, >, >=, <, <=
- BETWEEN...AND for range queries
- IN and NOT IN for list membership
- LIKE with % and _ wildcards
- IS NULL and IS NOT NULL

**✅ Logical Operators**
- AND, OR, NOT with proper SQL precedence (NOT > AND > OR)
- Parentheses for grouping

**✅ String Pattern Matching**
- LIKE operator with wildcards (%, _)
- Converts to regex patterns internally
- Case-sensitive by default

**✅ Nested Properties**
- Dot notation for nested field access: `user.profile.age >= 18`

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison.

## Consequences

### Positive
- **Immediate familiarity** for 90%+ of developers with SQL background
- **Reduced training time** - zero learning curve for database-centric teams
- **Natural mapping** to database-backed rule systems
- **Battle-tested syntax** with 40+ years of refinement
- **Rich operator support** - BETWEEN, IN, LIKE, IS NULL all native
- **Excellent readability** for complex conditions

### Negative
- **Parser complexity** - SQL parsing is more complex than Wirefilter
- **Function ambiguity** - SQL functions (UPPER, LENGTH) require custom operator implementation
- **Type coercion** - SQL's loose typing vs PHP's strict typing needs careful handling
- **Limited array operations** - SQL wasn't designed for nested JSON/array structures

### Neutral
- Adds another DSL option, increasing maintenance surface
- Documentation needs clear guidance on when to use SQL-WHERE vs Wirefilter

## Alternatives Considered

### Use Existing SQL Parser Library
- **Pros:** Faster implementation, battle-tested parsing
- **Cons:** Heavy dependencies (doctrine/dbal), hard to extend, overkill for WHERE clauses only
- **Decision:** Build custom parser for SQL WHERE subset - lighter weight, full control

### Support Full SQL (with SELECT, FROM)
- **Pros:** Complete SQL experience
- **Cons:** Massive scope creep, doesn't fit rule engine use case
- **Decision:** WHERE clauses only - perfectly scoped for conditional logic

### Case-Insensitive Keywords
- **Pros:** More forgiving for users
- **Cons:** Adds parser complexity
- **Decision:** Support both UPPER and lower keywords in parser

## Implementation Risks

### High Risk
1. **Operator precedence bugs** - SQL precedence is subtle
   - Mitigation: Comprehensive precedence tests, reference PostgreSQL/MySQL behavior

2. **LIKE pattern edge cases** - SQL LIKE has many edge cases (escaping, wildcards)
   - Mitigation: Extensive LIKE pattern tests, document limitations upfront

### Medium Risk
1. **Performance of custom parser** - hand-rolled parser might be slow
   - Mitigation: Benchmark against doctrine/lexer, optimize hot paths

2. **Function implementation gaps** - users may expect SQL functions we don't support
   - Mitigation: Clear documentation of supported functions, graceful error messages

### Low Risk
1. **Keyword conflicts** - field names matching SQL keywords
   - Mitigation: Support backtick quoting: `` `select` = 'value' ``

## Verification

### Acceptance Criteria
- [ ] Parse all basic comparison operators (=, !=, <, >, <=, >=)
- [ ] Parse logical operators (AND, OR, NOT) with correct precedence
- [ ] Parse IN, NOT IN with multiple values
- [ ] Parse BETWEEN x AND y
- [ ] Parse LIKE, NOT LIKE with % and _ wildcards
- [ ] Parse IS NULL, IS NOT NULL
- [ ] Support parentheses for grouping
- [ ] Support mathematical operators (+, -, *, /, %)
- [ ] Support dot notation for nested fields
- [ ] Support at least 5 string functions (UPPER, LOWER, LENGTH, CONCAT, TRIM)
- [ ] Handle single-quoted strings with escaping
- [ ] 100% test coverage without mocks
- [ ] Performance: parse + compile 1000 rules/second minimum
- [ ] Documentation with 20+ examples

### Performance Targets
- Parse simple rule (3 operators): < 1ms
- Parse complex rule (20+ operators): < 5ms
- Memory overhead: < 1MB for parser instances

### Testing Strategy
1. **Unit Tests** - Each AST node type, each operator mapping
2. **Integration Tests** - End-to-end rule evaluation with real contexts
3. **Precedence Tests** - All operator precedence combinations
4. **Edge Case Tests** - Escaping, whitespace, type coercion
5. **Performance Tests** - Benchmark against Wirefilter DSL
6. **Compatibility Tests** - Verify SQL behavior matches PostgreSQL/MySQL

## Timeline

- **Week 1:** Lexer + Parser + AST structure
- **Week 2:** Operator registry + basic compiler
- **Week 3:** Advanced features (LIKE, BETWEEN, functions) + facade
- **Week 4:** Testing + documentation + performance optimization

**Total Effort:** 4 weeks for 1 senior developer

## References

- SQL-92 Standard (WHERE clause specification)
- PostgreSQL WHERE Clause Documentation
- MySQL WHERE Clause Documentation
- [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)
