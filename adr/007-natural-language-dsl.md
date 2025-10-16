# ADR 007: Natural Language DSL

**Status:** Proposed
**Date:** 2025-10-14
**Deciders:** Development Team
**Related:** [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)

## Context

Business users, product managers, and non-technical stakeholders often need to define rules but struggle with programming syntax. Natural language DSLs allow humans to express logic in plain English, dramatically lowering the barrier to entry. While more ambiguous than formal syntax, controlled natural language with clear grammar rules can provide an excellent user experience.

### Use Cases
- Admin panels for business users
- No-code/low-code rule builders
- Customer support teams defining eligibility rules
- Product managers specifying feature flags
- Marketing teams creating audience segments
- Citizen developers in enterprise applications
- Compliance teams documenting business rules

### Key Advantages
- **Zero technical knowledge required** - Anyone can write rules
- **Self-documenting** - Rules explain themselves
- **Approachable** - Non-intimidating for business users
- **Audit-friendly** - Compliance teams can read rules directly
- **Reduced training** - Minimal onboarding needed
- **Business alignment** - Rules match how stakeholders think

### Key Challenges
- **Ambiguity** - Natural language is imprecise
- **Parsing complexity** - Requires NLP or complex grammar
- **Verbose** - More characters than formal syntax
- **Edge cases** - Handling typos, synonyms, variations
- **Limited expressiveness** - Complex logic is hard to express naturally

## Decision

We will implement a Natural Language DSL under the `Cline\Ruler\DSL\Natural` namespace that accepts controlled English expressions and compiles them to Ruler's operator tree structure. We will use a **pattern-based parser** with clear grammar rules rather than full NLP to maintain predictability.

### Language Design

#### Syntax Specification

**Basic Comparisons:**
```
age is 18
age is at least 18
age is greater than or equal to 18
age is more than 18
age is 18 or more
price is less than 100
country is US
country is not US
status is not banned
```

**Logical Operators:**
```
age is at least 18 and country is US
status is active or status is pending
status is not banned
age is at least 18 and (country is US or country is CA)
```

**Range Queries:**
```
age is between 18 and 65
age is from 18 to 65
price is between 10 and 100
```

**List Membership:**
```
country is one of US, CA, UK
country is either US or CA
status is not one of banned, deleted
```

**String Matching:**
```
email contains @example.com
email ends with @example.com
name starts with John
description includes important
```

**Existence Checks:**
```
email exists
email is present
email is set
deleted_at does not exist
deleted_at is not set
```

**Array Operations:**
```
tags contains premium
tags has all of premium, verified
tags has any of featured, trending
tags is empty
tags has 3 items
```

**Mathematical Expressions:**
```
price plus shipping is more than 100
total minus discount is at least 50
quantity times unit_price is greater than 500
```

**Real-World Examples:**

**Example 1: User Eligibility**
```
age is at least 18
and age is less than 65
and country is one of US, CA, UK
and email is verified
and status is not one of banned, suspended
```

**Example 2: Product Filtering**
```
category is electronics
and price is between 10 and 500
and in_stock is true
and (featured is true or rating is at least 4.0)
```

**Example 3: Premium Features**
```
(subscription is active or trial_ends_after is after today)
and account_age is more than 30 days
and payment_method exists
```

**Example 4: Content Moderation**
```
report_count is at least 5
or (user_reputation is less than 10 and link_count is more than 3)
or content contains spam
```

#### Grammar Rules

**Comparison Phrases:**
- `is` → equals
- `is not` → not equals
- `is at least` / `is greater than or equal to` / `is X or more` → >=
- `is more than` / `is greater than` → >
- `is at most` / `is less than or equal to` / `is X or less` → <=
- `is less than` → <
- `equals` → ==
- `does not equal` → !=

**Logical Phrases:**
- `and` → &&
- `or` → ||
- `not` / `is not` → !

**Range Phrases:**
- `is between X and Y` → >= X && <= Y
- `is from X to Y` → >= X && <= Y

**List Phrases:**
- `is one of A, B, C` → in [A, B, C]
- `is either A or B` → in [A, B]
- `is not one of A, B` → not in [A, B]

**String Phrases:**
- `contains` / `includes` / `has` → string contains
- `starts with` / `begins with` → starts with
- `ends with` → ends with
- `matches` → regex

**Existence Phrases:**
- `exists` / `is present` / `is set` → != null
- `does not exist` / `is not present` / `is not set` → == null

**Boolean Phrases:**
- `is true` → == true
- `is false` → == false
- `is yes` → == true
- `is no` → == false

**Array Phrases:**
- `contains X` / `has X` → X in array
- `has all of X, Y` → all in array
- `has any of X, Y` → any in array
- `is empty` → length == 0
- `has N items` → length == N

**Math Phrases:**
- `plus` / `and` → +
- `minus` → -
- `times` / `multiplied by` → *
- `divided by` → /

### Implementation Plan

#### Phase 1: Pattern Parser (Week 1-2)

**1.1 Create Pattern Matcher (`NaturalLanguageParser.php`)**
```php
namespace Cline\Ruler\DSL\Natural;

class NaturalLanguageParser
{
    private array $patterns = [];

    public function __construct()
    {
        $this->registerPatterns();
    }

    /**
     * Parse natural language expression into AST
     */
    public function parse(string $text): NaturalNode
    {
        // Normalize: lowercase, trim, normalize whitespace
        $text = $this->normalize($text);

        // Split by logical operators (and, or) while preserving parentheses
        return $this->parseExpression($text);
    }

    private function parseExpression(string $text): NaturalNode
    {
        // Handle parentheses
        if ($this->hasTopLevelParentheses($text)) {
            $inner = $this->extractParentheses($text);
            return $this->parseExpression($inner);
        }

        // Split by OR (lowest precedence)
        $orParts = $this->splitByLogical($text, 'or');
        if (count($orParts) > 1) {
            return new LogicalNode('or', array_map(
                fn($part) => $this->parseExpression($part),
                $orParts
            ));
        }

        // Split by AND (higher precedence)
        $andParts = $this->splitByLogical($text, 'and');
        if (count($andParts) > 1) {
            return new LogicalNode('and', array_map(
                fn($part) => $this->parseExpression($part),
                $andParts
            ));
        }

        // Parse single condition
        return $this->parseCondition($text);
    }

    private function parseCondition(string $text): NaturalNode
    {
        // Try each pattern until one matches
        foreach ($this->patterns as $pattern) {
            if ($node = $pattern->match($text)) {
                return $node;
            }
        }

        throw new \InvalidArgumentException("Could not parse condition: $text");
    }

    private function registerPatterns(): void
    {
        // Between pattern: "X is between A and B"
        $this->patterns[] = new BetweenPattern();

        // List membership: "X is one of A, B, C"
        $this->patterns[] = new ListMembershipPattern();

        // String operations: "X contains Y"
        $this->patterns[] = new StringOperationPattern();

        // Existence: "X exists"
        $this->patterns[] = new ExistencePattern();

        // Array operations: "X has Y"
        $this->patterns[] = new ArrayOperationPattern();

        // Math expressions: "X plus Y is more than Z"
        $this->patterns[] = new MathExpressionPattern();

        // Comparison: "X is at least Y"
        $this->patterns[] = new ComparisonPattern();
    }

    private function normalize(string $text): string
    {
        $text = trim($text);
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
        return $text;
    }

    private function splitByLogical(string $text, string $operator): array
    {
        // Split by operator, respecting parentheses depth
        $parts = [];
        $current = '';
        $depth = 0;

        $words = explode(' ', $text);
        foreach ($words as $word) {
            if ($word === '(') $depth++;
            if ($word === ')') $depth--;

            if ($word === $operator && $depth === 0) {
                if (!empty(trim($current))) {
                    $parts[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $word . ' ';
            }
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return count($parts) > 1 ? $parts : [$text];
    }

    private function hasTopLevelParentheses(string $text): bool
    {
        return str_starts_with($text, '(') && str_ends_with($text, ')');
    }

    private function extractParentheses(string $text): string
    {
        return substr($text, 1, -1);
    }
}
```

**1.2 Create Pattern Classes**

```php
namespace Cline\Ruler\DSL\Natural\Patterns;

interface Pattern
{
    public function match(string $text): ?NaturalNode;
}

class ComparisonPattern implements Pattern
{
    private const OPERATORS = [
        'is at least' => 'gte',
        'is greater than or equal to' => 'gte',
        'is more than' => 'gt',
        'is greater than' => 'gt',
        'is at most' => 'lte',
        'is less than or equal to' => 'lte',
        'is less than' => 'lt',
        'is not' => 'ne',
        'does not equal' => 'ne',
        'is' => 'eq',
        'equals' => 'eq',
    ];

    public function match(string $text): ?NaturalNode
    {
        foreach (self::OPERATORS as $phrase => $operator) {
            $pattern = '/^(\w+(?:\.\w+)*)\s+' . preg_quote($phrase, '/') . '\s+(.+)$/';

            if (preg_match($pattern, $text, $matches)) {
                $field = $matches[1];
                $value = $this->parseValue($matches[2]);

                return new ComparisonNode($operator, $field, $value);
            }
        }

        return null;
    }

    private function parseValue(string $value): mixed
    {
        // Boolean
        if ($value === 'true' || $value === 'yes') return true;
        if ($value === 'false' || $value === 'no') return false;

        // Null
        if ($value === 'null') return null;

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // String (remove quotes if present)
        return trim($value, '"\'');
    }
}

class BetweenPattern implements Pattern
{
    public function match(string $text): ?NaturalNode
    {
        $pattern = '/^(\w+(?:\.\w+)*)\s+is\s+(?:between|from)\s+(.+?)\s+(?:and|to)\s+(.+)$/';

        if (preg_match($pattern, $text, $matches)) {
            $field = $matches[1];
            $min = $this->parseValue($matches[2]);
            $max = $this->parseValue($matches[3]);

            return new BetweenNode($field, $min, $max);
        }

        return null;
    }

    private function parseValue(string $value): mixed
    {
        return is_numeric($value)
            ? (str_contains($value, '.') ? (float) $value : (int) $value)
            : trim($value, '"\'');
    }
}

class ListMembershipPattern implements Pattern
{
    public function match(string $text): ?NaturalNode
    {
        // "X is one of A, B, C"
        $pattern = '/^(\w+(?:\.\w+)*)\s+is\s+(not\s+)?one\s+of\s+(.+)$/';

        if (preg_match($pattern, $text, $matches)) {
            $field = $matches[1];
            $negated = !empty($matches[2]);
            $valueString = $matches[3];

            $values = array_map(
                fn($v) => $this->parseValue(trim($v)),
                explode(',', $valueString)
            );

            return new InNode($field, $values, $negated);
        }

        // "X is either A or B"
        $pattern = '/^(\w+(?:\.\w+)*)\s+is\s+either\s+(.+?)\s+or\s+(.+)$/';

        if (preg_match($pattern, $text, $matches)) {
            $field = $matches[1];
            $values = [
                $this->parseValue($matches[2]),
                $this->parseValue($matches[3])
            ];

            return new InNode($field, $values, false);
        }

        return null;
    }

    private function parseValue(string $value): mixed
    {
        $value = trim($value);
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        return trim($value, '"\'');
    }
}

class StringOperationPattern implements Pattern
{
    public function match(string $text): ?NaturalNode
    {
        $operations = [
            'contains' => 'contains',
            'includes' => 'contains',
            'starts with' => 'startsWith',
            'begins with' => 'startsWith',
            'ends with' => 'endsWith',
        ];

        foreach ($operations as $phrase => $operation) {
            $pattern = '/^(\w+(?:\.\w+)*)\s+' . preg_quote($phrase, '/') . '\s+(.+)$/';

            if (preg_match($pattern, $text, $matches)) {
                $field = $matches[1];
                $value = trim($matches[2], '"\'');

                return new StringOperationNode($operation, $field, $value);
            }
        }

        return null;
    }
}

class ExistencePattern implements Pattern
{
    public function match(string $text): ?NaturalNode
    {
        // "X exists"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+(?:exists|is\s+present|is\s+set)$/', $text, $matches)) {
            return new ExistenceNode($matches[1], true);
        }

        // "X does not exist"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+(?:does\s+not\s+exist|is\s+not\s+present|is\s+not\s+set)$/', $text, $matches)) {
            return new ExistenceNode($matches[1], false);
        }

        return null;
    }
}

class ArrayOperationPattern implements Pattern
{
    public function match(string $text): ?NaturalNode
    {
        // "X has Y" / "X contains Y"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+(?:contains|has)\s+(.+)$/', $text, $matches)) {
            $field = $matches[1];
            $value = trim($matches[2], '"\'');

            return new ArrayHasNode($field, $value);
        }

        // "X has all of Y, Z"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+has\s+all\s+of\s+(.+)$/', $text, $matches)) {
            $field = $matches[1];
            $values = array_map('trim', explode(',', $matches[2]));

            return new ArrayHasAllNode($field, $values);
        }

        // "X has any of Y, Z"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+has\s+any\s+of\s+(.+)$/', $text, $matches)) {
            $field = $matches[1];
            $values = array_map('trim', explode(',', $matches[2]));

            return new ArrayHasAnyNode($field, $values);
        }

        // "X is empty"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+is\s+empty$/', $text, $matches)) {
            return new ArrayIsEmptyNode($matches[1], true);
        }

        // "X has N items"
        if (preg_match('/^(\w+(?:\.\w+)*)\s+has\s+(\d+)\s+items?$/', $text, $matches)) {
            return new ArraySizeNode($matches[1], (int) $matches[2]);
        }

        return null;
    }
}
```

**1.3 Create AST Node Structure (`NaturalNode.php`)**
```php
namespace Cline\Ruler\DSL\Natural;

abstract class NaturalNode {}

class LogicalNode extends NaturalNode
{
    public function __construct(
        public string $operator,  // and, or, not
        public array $conditions
    ) {}
}

class ComparisonNode extends NaturalNode
{
    public function __construct(
        public string $operator,  // eq, ne, gt, gte, lt, lte
        public string $field,
        public mixed $value
    ) {}
}

class BetweenNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public mixed $min,
        public mixed $max
    ) {}
}

class InNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public array $values,
        public bool $negated = false
    ) {}
}

class StringOperationNode extends NaturalNode
{
    public function __construct(
        public string $operation,  // contains, startsWith, endsWith
        public string $field,
        public string $value
    ) {}
}

class ExistenceNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public bool $shouldExist
    ) {}
}

class ArrayHasNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public mixed $value
    ) {}
}

class ArrayHasAllNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public array $values
    ) {}
}

class ArrayHasAnyNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public array $values
    ) {}
}

class ArrayIsEmptyNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public bool $shouldBeEmpty
    ) {}
}

class ArraySizeNode extends NaturalNode
{
    public function __construct(
        public string $field,
        public int $size
    ) {}
}
```

#### Phase 2: Compiler (Week 2)

**2.1 Create Compiler (`NaturalLanguageCompiler.php`)**
```php
namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\Operator\Proposition;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class NaturalLanguageCompiler
{
    public function __construct(
        private FieldResolver $fieldResolver,
        private NaturalOperatorRegistry $operatorRegistry
    ) {}

    public function compile(NaturalNode $ast): Proposition
    {
        return $this->compileNode($ast);
    }

    private function compileNode(NaturalNode $node): mixed
    {
        return match (true) {
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof BetweenNode => $this->compileBetween($node),
            $node instanceof InNode => $this->compileIn($node),
            $node instanceof StringOperationNode => $this->compileStringOperation($node),
            $node instanceof ExistenceNode => $this->compileExistence($node),
            $node instanceof ArrayHasNode => $this->compileArrayHas($node),
            $node instanceof ArrayHasAllNode => $this->compileArrayHasAll($node),
            $node instanceof ArrayHasAnyNode => $this->compileArrayHasAny($node),
            $node instanceof ArrayIsEmptyNode => $this->compileArrayIsEmpty($node),
            $node instanceof ArraySizeNode => $this->compileArraySize($node),
            default => throw new \RuntimeException("Unknown node type"),
        };
    }

    // Compiler methods similar to other DSLs...
    // (compileLogical, compileComparison, etc.)
}
```

#### Phase 3: Facade (Week 2)

**3.1 Create NaturalLanguageRuleBuilder**
```php
namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\Rule;
use Cline\Ruler\RuleBuilder;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class NaturalLanguageRuleBuilder
{
    private NaturalLanguageParser $parser;
    private NaturalLanguageCompiler $compiler;

    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        $this->parser = new NaturalLanguageParser();

        $fieldResolver = new FieldResolver($ruleBuilder ?? new RuleBuilder());
        $operatorRegistry = new NaturalOperatorRegistry();
        $this->compiler = new NaturalLanguageCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parse natural language expression and return Rule
     *
     * @param string $text Natural language rule expression
     * @return Rule Compiled rule ready for evaluation
     *
     * @throws \InvalidArgumentException if text cannot be parsed
     */
    public function parse(string $text): Rule
    {
        $ast = $this->parser->parse($text);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();
        return $rb->create($proposition);
    }

    /**
     * Suggest corrections for ambiguous text
     */
    public function suggest(string $text): array
    {
        // Return array of possible interpretations
        // Useful for UI autocomplete
    }
}
```

#### Phase 4: Testing (Week 3)

**4.1 Integration Tests**
```php
test('basic comparison works', function (): void {
    $nl = new NaturalLanguageRuleBuilder();
    $rule = $nl->parse('age is at least 18');

    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 16])))->toBeFalse();
});

test('between works', function (): void {
    $rule = $nl->parse('age is between 18 and 65');

    expect($rule->evaluate(new Context(['age' => 30])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 70])))->toBeFalse();
});

test('list membership works', function (): void {
    $rule = $nl->parse('country is one of US, CA, UK');

    expect($rule->evaluate(new Context(['country' => 'US'])))->toBeTrue();
    expect($rule->evaluate(new Context(['country' => 'FR'])))->toBeFalse();
});

test('string contains works', function (): void {
    $rule = $nl->parse('email contains @example.com');

    expect($rule->evaluate(new Context(['email' => 'john@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => 'john@test.com'])))->toBeFalse();
});

test('complex expression works', function (): void {
    $rule = $nl->parse(
        'age is at least 18 and country is one of US, CA and status is not banned'
    );

    $valid = new Context(['age' => 20, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($valid))->toBeTrue();
});
```

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), Natural Language DSL has intentional limitations to maintain readability:

### Unsupported Features (By Design)

**❌ Inline Arithmetic**
- No mathematical expressions in filters
- **Workaround:** Pre-compute values: `$context['total'] = $price + $shipping`
- **Why:** Complex math obscures business logic intent; natural language should be declarative

**❌ Strict Equality**
- No strict type distinction (=== operator)
- **Why:** Natural language is designed for business users who don't think in terms of type coercion
- **When needed:** Use Wirefilter or MongoDB Query DSL for technical rules requiring strict types

**❌ Date Operations**
- No native date comparison operators
- **Workaround:** Use comparison operators with date strings/timestamps
- **Why:** Date syntax adds complexity; keep natural language simple for business users

**❌ Advanced Type Checking**
- No built-in type operators
- **Workaround:** Validate types at application layer
- **Why:** Type checking is technical detail that business users shouldn't need to understand

**❌ Action Callbacks**
- Cannot execute code on rule match (feature unique to Wirefilter DSL)
- **Workaround:** Handle actions in application code after rule evaluation
- **Why:** Natural language is declarative, not imperative

**❌ Regex Support**
- No regular expression matching
- **Workaround:** Use `contains`, `starts with`, `ends with` for simple patterns
- **Why:** Business users shouldn't need to understand regex patterns

### Supported Features

**✅ All Comparison Operators**
- `is` → equals
- `is not` → not equals
- `is at least` / `is greater than or equal to` / `is X or more` → >=
- `is more than` / `is greater than` → >
- `is at most` / `is less than or equal to` / `is X or less` → <=
- `is less than` → <

**✅ Logical Operators**
- `and` → AND
- `or` → OR
- `not` / `is not` → NOT
- Parentheses for grouping

**✅ Range Queries**
- `is between X and Y` → range check
- `is from X to Y` → range check
- Natural phrasing for common patterns

**✅ List Membership**
- `is one of A, B, C` → IN operator
- `is either A or B` → IN with 2 values
- `is not one of A, B` → NOT IN
- Conversational syntax

**✅ String Operations (Limited)**
- `contains` / `includes` → substring match
- `starts with` / `begins with` → prefix match
- `ends with` → suffix match
- **Case-sensitive** by design (simpler for users)

**✅ Existence Checks**
- `exists` / `is present` / `is set` → NOT NULL
- `does not exist` / `is not present` / `is not set` → NULL
- Natural phrasing for null checks

**✅ Boolean Values**
- `is true` / `is yes` → true
- `is false` / `is no` → false
- Multiple phrasings for flexibility

**✅ Negated Comparisons (Unique Feature)**
- `is not less than 18` → >= 18
- `is not greater than 65` → <= 65
- `is not one of "a", "b"` → NOT IN
- More natural than `not (...)` wrappers

**✅ Self-Documenting**
- Rules read like plain English
- No training required
- Audit-friendly for compliance

### Design Philosophy

Natural Language DSL prioritizes **readability over expressiveness**:
- Business users should write rules without technical knowledge
- Rules serve as documentation
- Complex logic should be broken into simpler rules
- Pre-compute technical details outside rules

### When to Use Natural Language

- **Best for:** Admin panels for business users
- **Best for:** No-code/low-code rule builders
- **Best for:** Rules that need to be audit-friendly
- **Best for:** Teams with non-technical stakeholders
- **Avoid for:** Complex logic requiring regex/arithmetic
- **Avoid for:** Machine-generated rules (too verbose)

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison.

## Consequences

### Positive
- **Zero technical barrier** - Anyone can write rules
- **Self-documenting** - Rules read like English
- **Business-friendly** - Non-technical stakeholders empowered
- **Audit trail** - Compliance-ready rule documentation
- **Reduced training** - Minimal onboarding required

### Negative
- **Verbose** - Many more characters than formal syntax
- **Ambiguity** - Natural language is imprecise
- **Limited expressiveness** - Complex logic is awkward
- **Parser complexity** - Many edge cases to handle
- **Performance** - Pattern matching is slower than formal parsing

### Neutral
- Best for business user interfaces
- Not recommended for programmatic rule generation
- Consider alongside formal DSL for technical users

## Timeline

- **Week 1:** Pattern parser + core patterns
- **Week 2:** Compiler + facade + additional patterns
- **Week 3:** Testing + edge cases + documentation

**Total Effort:** 3 weeks for 1 senior developer

## References

- [Gherkin Language](https://cucumber.io/docs/gherkin/) - Behavior-driven development syntax
- [Attempto Controlled English](http://attempto.ifi.uzh.ch/site/) - Controlled natural language
- [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)
