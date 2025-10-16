# ADR 005: LDAP Filter DSL

**Status:** Proposed
**Date:** 2025-10-14
**Deciders:** Development Team
**Related:** [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)

## Context

LDAP (Lightweight Directory Access Protocol) filter syntax has been used since 1993 for querying directory services. Its prefix notation and parenthesis-based structure create extremely compact, unambiguous expressions. While it looks unusual to modern developers, LDAP filters excel at representing complex Boolean logic with minimal characters and zero operator precedence ambiguity.

### Use Cases
- Systems integrating with Active Directory/LDAP
- Ultra-compact rule serialization (logging, URLs, storage)
- Prefix notation preference (Lisp-style developers)
- Unambiguous operator precedence requirements
- Command-line tools needing minimal escaping
- Legacy systems already using LDAP-style filters

### Key Advantages
- **Extremely compact** - Minimal character count
- **Zero ambiguity** - Prefix notation eliminates precedence issues
- **Battle-tested** - 30+ years of production use
- **URL-safe** - Easy to encode in query parameters
- **No reserved words** - Field names never conflict with operators
- **Trivial parsing** - Simple recursive descent parser

### Example Comparison

**Same rule in different DSLs:**

**Wirefilter:** `(age >= 18 && country == "US") || vip == true`
**MongoDB:** `{"$or": [{"$and": [{"age": {"$gte": 18}}, {"country": "US"}]}, {"vip": true}]}`
**LDAP:** `(|((&(age>=18)(country=US)))(vip=true))`

LDAP wins on character count: 37 vs 51 vs 77 characters.

## Decision

We will implement an LDAP-style filter DSL under the `Cline\Ruler\DSL\LDAP` namespace that accepts LDAP filter syntax (RFC 4515) and compiles it to Ruler's operator tree structure.

### Language Design

#### Syntax Specification

**Basic Structure:**
```
(operator field value)
```

**Comparison Operators:**
```ldap
(age=18)                    # Equal to
(age>=18)                   # Greater than or equal
(age<=65)                   # Less than or equal
(country=US)                # Equal to (string)
(status=active)             # Equal to
```

**Logical Operators (prefix):**
```ldap
# AND - all conditions must match
(&(age>=18)(country=US))

# OR - at least one condition matches
(|(status=active)(status=pending))

# NOT - negates condition
(!(status=banned))

# Complex nesting
(&
  (|
    (&(age>=18)(age<=65))
    (vip=true)
  )
  (|(country=US)(country=CA)(country=UK))
  (!(|(status=banned)(status=deleted)))
)
```

**Wildcard Matching:**
```ldap
(name=John*)                # Starts with "John"
(email=*@example.com)       # Ends with "@example.com"
(description=*important*)   # Contains "important"
(code=A*B*C)                # Complex wildcard patterns
```

**Approximate Match:**
```ldap
(name~=John)                # Fuzzy match (implementation-defined)
```

**Presence Check (field exists):**
```ldap
(email=*)                   # Email field exists
(!(deletedAt=*))            # deletedAt does not exist
```

**Extensible Match (advanced):**
```ldap
(cn:caseExactMatch:=Fred Flintstone)
(sn:dn:2.4.6.8.10:=Barney Rubble)
```

**Real-World Examples:**

**Example 1: User Eligibility**
```ldap
(&
  (age>=18)
  (age<=65)
  (|(country=US)(country=CA))
  (emailVerified=true)
  (!(|(status=banned)(status=suspended)))
)
```

**Example 2: Product Search**
```ldap
(&
  (category=electronics)
  (price>=10)
  (price<=500)
  (inStock=true)
  (|(featured=true)(rating>=4.0))
)
```

**Example 3: Content Moderation**
```ldap
(|
  (reportCount>=5)
  (&(userReputation<10)(linkCount>3))
  (content=*spam*)
)
```

**Example 4: Simple OR**
```ldap
(|(email=*@example.com)(email=*@test.com)(email=*@demo.com))
```

#### LDAP Filter Grammar (RFC 4515)

```
filter       ::= "(" filtercomp ")"
filtercomp   ::= and | or | not | item
and          ::= "&" filterlist
or           ::= "|" filterlist
not          ::= "!" filter
filterlist   ::= filter+
item         ::= simple | present | substring | extensible
simple       ::= attr filtertype value
filtertype   ::= "=" | "~=" | ">=" | "<="
present      ::= attr "=*"
substring    ::= attr "=" [initial] any [final]
any          ::= "*" *(value "*")
initial      ::= value
final        ::= value
extensible   ::= attr [":" dn] [":" matchingrule] ":=" value
attr         ::= AttributeDescription
value        ::= AttributeValue
```

#### Operator Mapping

**LDAP → Ruler:**
- `=` → EqualTo (or Regex for wildcards)
- `>=` → GreaterThanOrEqualTo
- `<=` → LessThanOrEqualTo
- `~=` → ApproximateMatch (custom operator)
- `&` → LogicalAnd
- `|` → LogicalOr
- `!` → LogicalNot
- `=*` → Exists (field != null)
- `=prefix*` → Regex `/^prefix/`
- `=*suffix` → Regex `/suffix$/`
- `=*contains*` → Regex `/contains/`

**Special Handling:**
- `>` (greater than) → Not in LDAP spec, can add as extension: `(age>18)`
- `<` (less than) → Extension: `(age<65)`
- `!=` (not equal) → Compose as `(!(field=value))`

### Implementation Plan

#### Phase 1: Lexer & Parser (Week 1-2)

**1.1 Create LDAP Lexer (`LDAPLexer.php`)**
```php
namespace Cline\Ruler\DSL\LDAP;

class LDAPLexer
{
    private string $input;
    private int $position = 0;

    public function __construct(string $input)
    {
        $this->input = trim($input);
    }

    public function tokenize(): array
    {
        $tokens = [];

        while ($this->position < strlen($this->input)) {
            $char = $this->input[$this->position];

            match ($char) {
                '(' => $tokens[] = new Token('LPAREN', '('),
                ')' => $tokens[] = new Token('RPAREN', ')'),
                '&' => $tokens[] = new Token('AND', '&'),
                '|' => $tokens[] = new Token('OR', '|'),
                '!' => $tokens[] = new Token('NOT', '!'),
                default => $tokens[] = $this->readItem(),
            };

            $this->position++;
        }

        return $tokens;
    }

    private function readItem(): Token
    {
        // Read until we hit a closing paren
        $start = $this->position;
        $depth = 0;

        while ($this->position < strlen($this->input)) {
            $char = $this->input[$this->position];

            if ($char === '(' && $this->position !== $start) {
                $depth++;
            } elseif ($char === ')' && $depth === 0) {
                break;
            } elseif ($char === ')') {
                $depth--;
            }

            $this->position++;
        }

        $item = substr($this->input, $start, $this->position - $start);
        $this->position--; // Back up one so main loop can increment

        return $this->parseItem($item);
    }

    private function parseItem(string $item): Token
    {
        // Match: attribute operator value
        // Operators: =, >=, <=, ~=, >. <
        if (preg_match('/^([a-zA-Z0-9._-]+)(>=|<=|~=|!=|=|>|<)(.*)$/', $item, $matches)) {
            return new Token('ITEM', [
                'attribute' => $matches[1],
                'operator' => $matches[2],
                'value' => $matches[3],
            ]);
        }

        throw new \InvalidArgumentException("Invalid filter item: $item");
    }
}

class Token
{
    public function __construct(
        public string $type,
        public mixed $value
    ) {}
}
```

**1.2 Create LDAP Parser (`LDAPParser.php`)**
```php
namespace Cline\Ruler\DSL\LDAP;

class LDAPParser
{
    private array $tokens;
    private int $position = 0;

    public function parse(string $filter): LDAPNode
    {
        $lexer = new LDAPLexer($filter);
        $this->tokens = $lexer->tokenize();
        $this->position = 0;

        return $this->parseFilter();
    }

    private function parseFilter(): LDAPNode
    {
        $this->expect('LPAREN');

        $token = $this->current();

        $node = match ($token->type) {
            'AND' => $this->parseAnd(),
            'OR' => $this->parseOr(),
            'NOT' => $this->parseNot(),
            'ITEM' => $this->parseItem(),
            default => throw new \RuntimeException("Unexpected token: {$token->type}"),
        };

        $this->expect('RPAREN');

        return $node;
    }

    private function parseAnd(): LDAPNode
    {
        $this->advance(); // consume '&'

        $conditions = [];
        while ($this->current()->type !== 'RPAREN') {
            $conditions[] = $this->parseFilter();
        }

        return new LogicalNode('and', $conditions);
    }

    private function parseOr(): LDAPNode
    {
        $this->advance(); // consume '|'

        $conditions = [];
        while ($this->current()->type !== 'RPAREN') {
            $conditions[] = $this->parseFilter();
        }

        return new LogicalNode('or', $conditions);
    }

    private function parseNot(): LDAPNode
    {
        $this->advance(); // consume '!'

        return new LogicalNode('not', [$this->parseFilter()]);
    }

    private function parseItem(): LDAPNode
    {
        $item = $this->current()->value;
        $this->advance();

        $attribute = $item['attribute'];
        $operator = $item['operator'];
        $value = $item['value'];

        // Handle presence check: field=*
        if ($value === '*' && $operator === '=') {
            return new PresenceNode($attribute);
        }

        // Handle wildcards: field=*value*, field=prefix*, field=*suffix
        if (str_contains($value, '*')) {
            return new WildcardNode($attribute, $value);
        }

        // Handle approximate match
        if ($operator === '~=') {
            return new ApproximateNode($attribute, $value);
        }

        // Regular comparison
        return new ComparisonNode($operator, $attribute, $value);
    }

    private function current(): Token
    {
        return $this->tokens[$this->position] ?? throw new \RuntimeException("Unexpected end of input");
    }

    private function advance(): void
    {
        $this->position++;
    }

    private function expect(string $type): void
    {
        if ($this->current()->type !== $type) {
            throw new \RuntimeException("Expected $type, got {$this->current()->type}");
        }
        $this->advance();
    }
}
```

**1.3 Create AST Node Structure (`LDAPNode.php`)**
```php
namespace Cline\Ruler\DSL\LDAP;

abstract class LDAPNode {}

class LogicalNode extends LDAPNode
{
    public function __construct(
        public string $operator,  // and, or, not
        public array $conditions
    ) {}
}

class ComparisonNode extends LDAPNode
{
    public function __construct(
        public string $operator,  // =, >=, <=, >, <, !=
        public string $attribute,
        public string $value
    ) {}
}

class WildcardNode extends LDAPNode
{
    public function __construct(
        public string $attribute,
        public string $pattern  // Contains * wildcards
    ) {}
}

class PresenceNode extends LDAPNode
{
    public function __construct(
        public string $attribute
    ) {}
}

class ApproximateNode extends LDAPNode
{
    public function __construct(
        public string $attribute,
        public string $value
    ) {}
}
```

#### Phase 2: Compiler (Week 2)

**2.1 Create LDAP Compiler (`LDAPCompiler.php`)**
```php
namespace Cline\Ruler\DSL\LDAP;

use Cline\Ruler\Operator\Proposition;
use Cline\Ruler\Variable;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class LDAPCompiler
{
    public function __construct(
        private FieldResolver $fieldResolver,
        private LDAPOperatorRegistry $operatorRegistry
    ) {}

    public function compile(LDAPNode $ast): Proposition
    {
        return $this->compileNode($ast);
    }

    private function compileNode(LDAPNode $node): mixed
    {
        return match (true) {
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof WildcardNode => $this->compileWildcard($node),
            $node instanceof PresenceNode => $this->compilePresence($node),
            $node instanceof ApproximateNode => $this->compileApproximate($node),
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
        $field = $this->fieldResolver->resolve($node->attribute);

        // Type casting: try to infer type from value
        $value = $this->parseValue($node->value);

        $operatorClass = $this->operatorRegistry->getComparison($node->operator);
        return new $operatorClass($field, $value);
    }

    private function compileWildcard(WildcardNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);

        // Convert LDAP wildcard to regex
        // * → .*
        // Escape everything else
        $parts = explode('*', $node->pattern);
        $parts = array_map(fn($p) => preg_quote($p, '/'), $parts);
        $pattern = '/^' . implode('.*', $parts) . '$/';

        return new \Cline\Ruler\Operators\String\Regex($field, $pattern);
    }

    private function compilePresence(PresenceNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);

        // Field exists = field != null
        $isNull = new \Cline\Ruler\Operators\Comparison\EqualTo($field, null);
        return new \Cline\Ruler\Operators\Logical\LogicalNot([$isNull]);
    }

    private function compileApproximate(ApproximateNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);

        // Approximate match - use case-insensitive contains as default
        $pattern = '/' . preg_quote($node->value, '/') . '/i';

        return new \Cline\Ruler\Operators\String\Regex($field, $pattern);
    }

    /**
     * Parse value string to appropriate PHP type
     */
    private function parseValue(string $value): mixed
    {
        // Boolean
        if ($value === 'true') return true;
        if ($value === 'false') return false;

        // Null
        if ($value === 'null') return null;

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // String
        return $value;
    }
}
```

**2.2 Create Operator Registry (`LDAPOperatorRegistry.php`)**
```php
namespace Cline\Ruler\DSL\LDAP;

use Cline\Ruler\Operators;

class LDAPOperatorRegistry
{
    private const COMPARISON_MAP = [
        '=' => Operators\Comparison\EqualTo::class,
        '>=' => Operators\Comparison\GreaterThanOrEqualTo::class,
        '<=' => Operators\Comparison\LessThanOrEqualTo::class,
        '>' => Operators\Comparison\GreaterThan::class,
        '<' => Operators\Comparison\LessThan::class,
        '!=' => Operators\Comparison\NotEqualTo::class,
    ];

    private const LOGICAL_MAP = [
        'and' => Operators\Logical\LogicalAnd::class,
        'or' => Operators\Logical\LogicalOr::class,
        'not' => Operators\Logical\LogicalNot::class,
    ];

    public function getComparison(string $operator): string
    {
        return self::COMPARISON_MAP[$operator]
            ?? throw new \InvalidArgumentException("Unknown comparison operator: $operator");
    }

    public function getLogical(string $operator): string
    {
        return self::LOGICAL_MAP[$operator]
            ?? throw new \InvalidArgumentException("Unknown logical operator: $operator");
    }
}
```

#### Phase 3: Facade (Week 2)

**3.1 Create LDAPFilterRuleBuilder**
```php
namespace Cline\Ruler\DSL\LDAP;

use Cline\Ruler\Rule;
use Cline\Ruler\RuleBuilder;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class LDAPFilterRuleBuilder
{
    private LDAPParser $parser;
    private LDAPCompiler $compiler;

    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        $this->parser = new LDAPParser();

        $fieldResolver = new FieldResolver($ruleBuilder ?? new RuleBuilder());
        $operatorRegistry = new LDAPOperatorRegistry();
        $this->compiler = new LDAPCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parse LDAP filter and return Rule
     *
     * @param string $filter LDAP filter expression
     * @return Rule Compiled rule ready for evaluation
     *
     * @throws \InvalidArgumentException if filter syntax is invalid
     */
    public function parse(string $filter): Rule
    {
        $ast = $this->parser->parse($filter);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();
        return $rb->create($proposition);
    }

    /**
     * Validate LDAP filter syntax
     */
    public function validate(string $filter): bool
    {
        try {
            $this->parser->parse($filter);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
```

#### Phase 4: Testing (Week 3)

**4.1 Parser Tests (`LDAPParserTest.php`)**
```php
test('parses simple equality', function (): void {
    $parser = new LDAPParser();
    $ast = $parser->parse('(age=18)');

    expect($ast)->toBeInstanceOf(ComparisonNode::class)
        ->and($ast->operator)->toBe('=')
        ->and($ast->attribute)->toBe('age')
        ->and($ast->value)->toBe('18');
});

test('parses AND', function (): void {
    $ast = $parser->parse('(&(age>=18)(country=US))');

    expect($ast)->toBeInstanceOf(LogicalNode::class)
        ->and($ast->operator)->toBe('and')
        ->and($ast->conditions)->toHaveCount(2);
});

test('parses OR', function (): void {
    $ast = $parser->parse('(|(status=active)(status=pending))');

    expect($ast->operator)->toBe('or');
});

test('parses NOT', function (): void {
    $ast = $parser->parse('(!(status=banned))');

    expect($ast->operator)->toBe('not');
});

test('parses presence check', function (): void {
    $ast = $parser->parse('(email=*)');

    expect($ast)->toBeInstanceOf(PresenceNode::class);
});

test('parses wildcard patterns', function (): void {
    $ast = $parser->parse('(name=John*)');

    expect($ast)->toBeInstanceOf(WildcardNode::class)
        ->and($ast->pattern)->toBe('John*');
});

test('parses complex nested expression', function (): void {
    $ast = $parser->parse('(&(|(age>=18)(vip=true))(country=US)(!(status=banned)))');

    expect($ast->operator)->toBe('and')
        ->and($ast->conditions)->toHaveCount(3);
});

test('parses approximate match', function (): void {
    $ast = $parser->parse('(name~=John)');

    expect($ast)->toBeInstanceOf(ApproximateNode::class);
});
```

**4.2 Integration Tests (`LDAPIntegrationTest.php`)**
```php
test('simple equality works', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(age=18)');

    expect($rule->evaluate(new Context(['age' => 18])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 20])))->toBeFalse();
});

test('gte operator works', function (): void {
    $rule = $ldap->parse('(age>=18)');

    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 16])))->toBeFalse();
});

test('AND works', function (): void {
    $rule = $ldap->parse('(&(age>=18)(country=US))');

    expect($rule->evaluate(new Context(['age' => 20, 'country' => 'US'])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 20, 'country' => 'FR'])))->toBeFalse();
});

test('OR works', function (): void {
    $rule = $ldap->parse('(|(status=active)(status=pending))');

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'pending'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'deleted'])))->toBeFalse();
});

test('NOT works', function (): void {
    $rule = $ldap->parse('(!(status=banned))');

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'banned'])))->toBeFalse();
});

test('presence check works', function (): void {
    $rule = $ldap->parse('(email=*)');

    expect($rule->evaluate(new Context(['email' => 'test@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => null])))->toBeFalse();
});

test('wildcard prefix works', function (): void {
    $rule = $ldap->parse('(name=John*)');

    expect($rule->evaluate(new Context(['name' => 'John Doe'])))->toBeTrue();
    expect($rule->evaluate(new Context(['name' => 'Jane Doe'])))->toBeFalse();
});

test('wildcard suffix works', function (): void {
    $rule = $ldap->parse('(email=*@example.com)');

    expect($rule->evaluate(new Context(['email' => 'john@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => 'john@test.com'])))->toBeFalse();
});

test('wildcard contains works', function (): void {
    $rule = $ldap->parse('(description=*important*)');

    expect($rule->evaluate(new Context(['description' => 'This is important stuff'])))->toBeTrue();
    expect($rule->evaluate(new Context(['description' => 'Nothing to see here'])))->toBeFalse();
});

test('complex nested conditions work', function (): void {
    $rule = $ldap->parse('(&(|(age>=18)(vip=true))(country=US)(!(status=banned)))');

    $valid = new Context(['age' => 20, 'vip' => false, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($valid))->toBeTrue();

    $invalid = new Context(['age' => 16, 'vip' => false, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($invalid))->toBeFalse();
});

test('approximate match works', function (): void {
    $rule = $ldap->parse('(name~=john)');

    expect($rule->evaluate(new Context(['name' => 'John'])))->toBeTrue();
    expect($rule->evaluate(new Context(['name' => 'JOHN'])))->toBeTrue();
    expect($rule->evaluate(new Context(['name' => 'Johnny'])))->toBeTrue();
});

test('type coercion works', function (): void {
    $rule = $ldap->parse('(age=18)');

    expect($rule->evaluate(new Context(['age' => 18])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => '18'])))->toBeFalse();  // String vs int
});

test('boolean values work', function (): void {
    $rule = $ldap->parse('(verified=true)');

    expect($rule->evaluate(new Context(['verified' => true])))->toBeTrue();
    expect($rule->evaluate(new Context(['verified' => false])))->toBeFalse();
});

test('very compact expression works', function (): void {
    // Ultra-compact: 25 chars for "age >= 18 AND country = US"
    $rule = $ldap->parse('(&(age>=18)(country=US))');

    expect($rule->evaluate(new Context(['age' => 20, 'country' => 'US'])))->toBeTrue();
});
```

#### Phase 5: Documentation (Week 3-4)

**5.1 Create Cookbook (`cookbook/ldap-filter-syntax.md`)**
**5.2 Add Comparison with Other DSLs**
**5.3 URL Encoding Guide**

### Architecture

```
DSL/
├── Wirefilter/
├── SqlWhere/
├── MongoQuery/
├── GraphQL/
└── LDAP/                        # New
    ├── LDAPFilterRuleBuilder.php    # Main facade
    ├── LDAPParser.php               # Text → AST
    ├── LDAPLexer.php                # Tokenization
    ├── LDAPNode.php                 # AST node definitions
    ├── LDAPCompiler.php             # AST → Operator tree
    └── LDAPOperatorRegistry.php     # LDAP → Ruler operator mapping
```

### Dependencies

**Required:** None

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), LDAP Filter DSL has intentional limitations focused on compactness:

### Unsupported Features

**❌ Inline Arithmetic**
- No mathematical expressions in filters
- **Workaround:** Pre-compute values: `$context['total'] = $price + $shipping`
- **Why:** LDAP filters are designed for directory queries, not computation

**❌ Date Operations**
- No native date comparison operators
- **Workaround:** Use comparison operators with numeric timestamps or string dates
- **Why:** LDAP spec doesn't include date operations; use >= and <= for date ranges

**❌ Advanced Type Checking**
- No built-in type operators
- **Workaround:** Validate types at application layer
- **Why:** LDAP filter spec focuses on string matching and basic comparisons

**❌ Strict Equality**
- No strict type equality (===)
- LDAP's = operator has its own matching rules
- **Workaround:** Use Wirefilter or MongoDB Query DSL for strict type checking
- **Why:** LDAP doesn't distinguish between strict and loose equality

**❌ Action Callbacks**
- Cannot execute code on rule match (feature unique to Wirefilter DSL)
- **Workaround:** Handle actions in application code after rule evaluation
- **Why:** LDAP is a text-based filter language, not an execution framework

**❌ Extended Operators**
- Limited to standard LDAP operators (no BETWEEN, no extended logical like XOR/NAND)
- **Workaround:** Compose with basic operators: `(&(age>=18)(age<=65))` for BETWEEN
- **Why:** RFC 4515 defines specific operator set for LDAP compatibility

### Supported Features

**✅ Ultra-Compact Syntax**
- **Most compact DSL** of all implementations
- Prefix notation eliminates operator precedence ambiguity
- Example: `(&(age>=18)(country=US))` - only 25 characters

**✅ All Basic Comparison Operators**
- Equality: `=`
- Greater/equal: `>=`
- Less/equal: `<=`
- Extensions: `>`, `<`, `!=` (beyond standard LDAP)

**✅ Logical Operators (Prefix Notation)**
- AND: `&` - all conditions must match
- OR: `|` - at least one matches
- NOT: `!` - negates condition
- Zero ambiguity with prefix notation

**✅ Powerful Wildcard Matching**
- Prefix: `(name=John*)`
- Suffix: `(email=*@example.com)`
- Contains: `(description=*important*)`
- Complex patterns: `(code=A*B*C)`
- Wildcards at any position in value

**✅ Presence Checks**
- Field exists: `(email=*)`
- Field not exists: `(!(email=*))`
- Simple syntax for null/not null checks

**✅ Approximate Matching**
- Fuzzy match: `(name~=John)` compiles to case-insensitive contains
- Implementation-defined matching strategy

**✅ URL-Safe & Logging-Friendly**
- Easy to encode in query parameters
- Minimal characters for log files
- No special escaping needed for most cases

**✅ Battle-Tested Standard**
- RFC 4515 specification (LDAP v3)
- 30+ years of production use
- Standard in Active Directory, OpenLDAP

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison.

## Consequences

### Positive
- **Most compact** - Minimal character count of all DSLs
- **Zero ambiguity** - Prefix notation eliminates precedence confusion
- **URL-safe** - Easy to encode in query parameters
- **Battle-tested** - 30+ years of production use
- **Simple parsing** - Straightforward recursive descent
- **No reserved words** - Field names never conflict
- **Perfect for logging** - Compact expressions in log files

### Negative
- **Unusual syntax** - Looks alien to modern developers
- **Not human-friendly** - Hard to read/write manually
- **Verbose for simple cases** - `(age=18)` vs `age=18`
- **Limited operators** - No native >, <, !=
- **Parenthesis overload** - Many nested parens

### Neutral
- Best suited for machine-generated filters or compact serialization
- Not recommended for human-authored rules (use Wirefilter instead)

## Alternatives Considered

### S-expressions (Lisp-style)
- **Pros:** More flexible than LDAP
- **Cons:** Not a standard, requires custom grammar
- **Decision:** LDAP provides same benefits with established standard

### Polish Notation (no parens)
- **Pros:** Even more compact
- **Cons:** Harder to parse, less readable
- **Decision:** LDAP's parentheses add clarity worth the extra characters

## Implementation Risks

### Medium Risk
1. **Wildcard edge cases** - Complex patterns like `*a*b*c*` need careful handling
   - Mitigation: Extensive wildcard tests, document limitations

2. **Type coercion** - String "18" vs int 18 needs clear rules
   - Mitigation: Document type handling explicitly, add validation

### Low Risk
1. **Nested parentheses** - Deep nesting might confuse parser
   - Mitigation: Recursive descent handles naturally

## Verification

### Acceptance Criteria
- [ ] Parse basic comparisons (=, >=, <=)
- [ ] Parse logical operators (&, |, !)
- [ ] Parse wildcards (prefix*, *suffix, *contains*)
- [ ] Parse presence checks (field=*)
- [ ] Parse approximate match (~=)
- [ ] Support arbitrary nesting depth
- [ ] Type coercion for numbers and booleans
- [ ] 100% test coverage without mocks
- [ ] Performance: parse 5000+ filters/second
- [ ] Verify character count is lowest of all DSLs

### Performance Targets
- Parse simple filter: < 0.5ms
- Parse complex nested filter: < 2ms
- Memory: < 200KB per parser instance

### Testing Strategy
1. **Unit Tests** - Lexer, parser, each node type
2. **Integration Tests** - End-to-end evaluation
3. **Wildcard Tests** - All pattern combinations
4. **Edge Cases** - Deep nesting, type coercion
5. **Comparison Tests** - Verify same logic across all DSLs
6. **Performance Tests** - Benchmark character count and parse speed

## Timeline

- **Week 1:** Lexer + basic parser
- **Week 2:** Advanced parser + compiler + facade
- **Week 3:** Testing + documentation + comparison benchmarks

**Total Effort:** 3 weeks for 1 senior developer

## References

- [RFC 4515: LDAP String Representation of Search Filters](https://tools.ietf.org/html/rfc4515)
- [LDAP Filter Syntax Documentation](https://ldap.com/ldap-filters/)
- [Active Directory Search Filter Syntax](https://docs.microsoft.com/en-us/windows/win32/adsi/search-filter-syntax)
- [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)
