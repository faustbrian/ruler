# ADR 003: MongoDB Query DSL

**Status:** Proposed
**Date:** 2025-10-14
**Deciders:** Development Team
**Related:** [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md), [ADR 002: SQL-WHERE DSL](002-sql-where-dsl.md)

## Context

Modern web applications frequently expose filtering capabilities through REST APIs. MongoDB's query syntax is JSON-based, making it perfect for API endpoints that accept filter parameters. The syntax is widely adopted, well-documented, and already understood by frontend and backend developers working with NoSQL databases.

### Use Cases
- REST API filter parameters
- GraphQL query arguments
- Admin dashboards with dynamic filtering
- Mobile apps sending filter criteria as JSON
- Microservices with MongoDB as primary datastore
- Frontend developers building query builders

### Key Advantages
- **JSON-native** - Perfect for API communication
- **Type-safe** - JSON enforces structure
- **Composable** - Easy to build programmatically
- **No parsing complexity** - JSON decode is built-in
- **Industry standard** - Used by millions of MongoDB users

## Decision

We will implement a MongoDB-style query DSL under the `Cline\Ruler\DSL\MongoQuery` namespace that accepts MongoDB query documents and compiles them to Ruler's operator tree structure.

### Language Design

#### Syntax Specification

**Basic Comparison Operators:**
```json
{"age": {"$gte": 18}}
{"price": {"$lt": 100.50}}
{"country": {"$eq": "US"}}
{"status": {"$ne": "banned"}}
{"category": {"$in": ["electronics", "books", "toys"]}}
{"status": {"$nin": ["deleted", "archived"]}}
{"email": {"$exists": true}}
{"deleted_at": {"$exists": false}}
```

**Implicit Equality:**
```json
{"country": "US"}
{"status": "active"}
{"verified": true}
{"age": 18}
```

**Logical Operators:**
```json
{
  "$and": [
    {"age": {"$gte": 18}},
    {"country": "US"}
  ]
}

{
  "$or": [
    {"status": "active"},
    {"status": "pending"}
  ]
}

{
  "$not": {
    "status": "banned"
  }
}

{
  "$nor": [
    {"status": "deleted"},
    {"status": "banned"}
  ]
}
```

**Implicit AND (multiple fields):**
```json
{
  "age": {"$gte": 18},
  "country": "US",
  "status": {"$ne": "banned"}
}
```

**Range Queries:**
```json
{"age": {"$gte": 18, "$lte": 65}}
{"price": {"$gt": 10, "$lt": 100}}
```

**Array Operators:**
```json
{"tags": {"$all": ["premium", "verified"]}}
{"tags": {"$elemMatch": {"$eq": "featured"}}}
{"scores": {"$size": 5}}
```

**String Pattern Matching:**
```json
{"email": {"$regex": ".*@example\\.com$", "$options": "i"}}
{"name": {"$regex": "^John"}}
```

**Nested Field Access (dot notation):**
```json
{"user.profile.age": {"$gte": 18}}
{"order.shipping.country": "US"}
{"metadata.tags.0": "premium"}
```

**Evaluation Operators:**
```json
{"$expr": {"$gt": [{"$add": ["$price", "$shipping"]}, 100]}}
{"age": {"$mod": [2, 0]}}  // even ages
{"description": {"$type": "string"}}
```

**Comparison of Two Fields:**
```json
{
  "$expr": {
    "$gt": ["$balance", "$minimum_threshold"]
  }
}
```

**Complex Nested Conditions:**
```json
{
  "$and": [
    {
      "$or": [
        {"age": {"$gte": 18, "$lt": 65}},
        {"vip": true}
      ]
    },
    {"country": {"$in": ["US", "CA", "UK"]}},
    {
      "$not": {
        "status": {"$in": ["banned", "deleted"]}
      }
    }
  ]
}
```

**Geospatial Queries (if applicable):**
```json
{
  "location": {
    "$near": {
      "$geometry": {"type": "Point", "coordinates": [-73.9667, 40.78]},
      "$maxDistance": 5000
    }
  }
}
```

#### Supported Operators

**Comparison:**
- `$eq` - Equal to
- `$ne` - Not equal to
- `$gt` - Greater than
- `$gte` - Greater than or equal
- `$lt` - Less than
- `$lte` - Less than or equal
- `$in` - In array
- `$nin` - Not in array

**Logical:**
- `$and` - All conditions must match
- `$or` - At least one condition matches
- `$not` - Negates condition
- `$nor` - None of the conditions match

**Element:**
- `$exists` - Field exists/doesn't exist
- `$type` - Field type checking

**Evaluation:**
- `$expr` - Aggregation expressions
- `$regex` - Regular expression matching
- `$mod` - Modulo operation

**Array:**
- `$all` - Array contains all elements
- `$elemMatch` - Array element matches condition
- `$size` - Array has specific length

**String:**
- `$regex` - Pattern matching
- `$options` - Regex flags (i = case-insensitive, m = multiline)

#### Type Coercion Rules

```json
// Numbers
{"age": 18}        // int
{"price": 19.99}   // float

// Strings
{"name": "John"}

// Booleans
{"verified": true}
{"deleted": false}

// Null
{"deleted_at": null}

// Arrays
{"tags": ["a", "b"]}

// Nested objects
{"user": {"age": 18}}
```

### Implementation Plan

#### Phase 1: Query Parser (Week 1)

**1.1 Create MongoDB Query Parser (`MongoQueryParser.php`)**
```php
namespace Cline\Ruler\DSL\MongoQuery;

class MongoQueryParser
{
    /**
     * Parse MongoDB query document
     *
     * Accepts both JSON string and PHP array
     */
    public function parse(string|array $query): MongoNode
    {
        if (is_string($query)) {
            $query = json_decode($query, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->parseQuery($query);
    }

    /**
     * Recursively parse query structure
     */
    private function parseQuery(array $query): MongoNode
    {
        // Handle logical operators
        if (isset($query['$and'])) {
            return new LogicalNode('and', array_map(
                fn($q) => $this->parseQuery($q),
                $query['$and']
            ));
        }

        if (isset($query['$or'])) {
            return new LogicalNode('or', array_map(
                fn($q) => $this->parseQuery($q),
                $query['$or']
            ));
        }

        if (isset($query['$not'])) {
            return new LogicalNode('not', [
                $this->parseQuery($query['$not'])
            ]);
        }

        if (isset($query['$nor'])) {
            return new LogicalNode('nor', array_map(
                fn($q) => $this->parseQuery($q),
                $query['$nor']
            ));
        }

        // Handle $expr (aggregation expressions)
        if (isset($query['$expr'])) {
            return $this->parseExpression($query['$expr']);
        }

        // Handle implicit AND (multiple field conditions)
        $conditions = [];
        foreach ($query as $field => $value) {
            $conditions[] = $this->parseFieldCondition($field, $value);
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return new LogicalNode('and', $conditions);
    }

    /**
     * Parse field condition
     */
    private function parseFieldCondition(string $field, mixed $value): MongoNode
    {
        // Handle implicit equality
        if (!is_array($value)) {
            return new ComparisonNode('eq', $field, $value);
        }

        // Handle operator conditions
        $conditions = [];
        foreach ($value as $operator => $operandValue) {
            $conditions[] = match ($operator) {
                '$eq' => new ComparisonNode('eq', $field, $operandValue),
                '$ne' => new ComparisonNode('ne', $field, $operandValue),
                '$gt' => new ComparisonNode('gt', $field, $operandValue),
                '$gte' => new ComparisonNode('gte', $field, $operandValue),
                '$lt' => new ComparisonNode('lt', $field, $operandValue),
                '$lte' => new ComparisonNode('lte', $field, $operandValue),
                '$in' => new InNode('in', $field, $operandValue),
                '$nin' => new InNode('nin', $field, $operandValue),
                '$exists' => new ExistsNode($field, $operandValue),
                '$regex' => new RegexNode($field, $operandValue, $value['$options'] ?? ''),
                '$all' => new AllNode($field, $operandValue),
                '$elemMatch' => new ElemMatchNode($field, $this->parseQuery($operandValue)),
                '$size' => new SizeNode($field, $operandValue),
                '$mod' => new ModNode($field, $operandValue[0], $operandValue[1]),
                '$type' => new TypeNode($field, $operandValue),
                default => throw new \InvalidArgumentException("Unsupported operator: $operator"),
            };
        }

        return count($conditions) === 1
            ? $conditions[0]
            : new LogicalNode('and', $conditions);
    }

    /**
     * Parse aggregation expression ($expr)
     */
    private function parseExpression(array $expr): MongoNode
    {
        // $expr uses prefix notation: {"$gt": ["$field", value]}
        $operator = array_key_first($expr);
        $operands = $expr[$operator];

        return match ($operator) {
            '$gt', '$gte', '$lt', '$lte', '$eq', '$ne' => new ExpressionNode(
                ltrim($operator, '$'),
                $this->parseOperand($operands[0]),
                $this->parseOperand($operands[1])
            ),
            '$add', '$subtract', '$multiply', '$divide' => new MathExpressionNode(
                ltrim($operator, '$'),
                array_map(fn($op) => $this->parseOperand($op), $operands)
            ),
            '$and', '$or' => new LogicalNode(
                ltrim($operator, '$'),
                array_map(fn($e) => $this->parseExpression($e), $operands)
            ),
            default => throw new \InvalidArgumentException("Unsupported expression operator: $operator"),
        };
    }

    /**
     * Parse expression operand
     */
    private function parseOperand(mixed $operand): mixed
    {
        // Field reference: "$fieldName"
        if (is_string($operand) && str_starts_with($operand, '$')) {
            return new FieldRefNode(ltrim($operand, '$'));
        }

        // Nested expression
        if (is_array($operand)) {
            return $this->parseExpression($operand);
        }

        // Literal value
        return $operand;
    }
}
```

**1.2 Create AST Node Structure (`MongoNode.php`)**
```php
namespace Cline\Ruler\DSL\MongoQuery;

abstract class MongoNode {}

class LogicalNode extends MongoNode
{
    public function __construct(
        public string $operator,  // and, or, not, nor
        public array $conditions
    ) {}
}

class ComparisonNode extends MongoNode
{
    public function __construct(
        public string $operator,  // eq, ne, gt, gte, lt, lte
        public string $field,
        public mixed $value
    ) {}
}

class InNode extends MongoNode
{
    public function __construct(
        public string $operator,  // in, nin
        public string $field,
        public array $values
    ) {}
}

class ExistsNode extends MongoNode
{
    public function __construct(
        public string $field,
        public bool $shouldExist
    ) {}
}

class RegexNode extends MongoNode
{
    public function __construct(
        public string $field,
        public string $pattern,
        public string $options = ''
    ) {}
}

class AllNode extends MongoNode
{
    public function __construct(
        public string $field,
        public array $values
    ) {}
}

class ElemMatchNode extends MongoNode
{
    public function __construct(
        public string $field,
        public MongoNode $condition
    ) {}
}

class SizeNode extends MongoNode
{
    public function __construct(
        public string $field,
        public int $size
    ) {}
}

class ModNode extends MongoNode
{
    public function __construct(
        public string $field,
        public int $divisor,
        public int $remainder
    ) {}
}

class TypeNode extends MongoNode
{
    public function __construct(
        public string $field,
        public string $type
    ) {}
}

class ExpressionNode extends MongoNode
{
    public function __construct(
        public string $operator,
        public mixed $left,
        public mixed $right
    ) {}
}

class MathExpressionNode extends MongoNode
{
    public function __construct(
        public string $operator,  // add, subtract, multiply, divide
        public array $operands
    ) {}
}

class FieldRefNode extends MongoNode
{
    public function __construct(
        public string $fieldName
    ) {}
}
```

#### Phase 2: Operator Mapping (Week 1-2)

**2.1 Create Operator Registry (`MongoOperatorRegistry.php`)**
```php
namespace Cline\Ruler\DSL\MongoQuery;

use Cline\Ruler\Operators;

class MongoOperatorRegistry
{
    private const COMPARISON_MAP = [
        'eq' => Operators\Comparison\EqualTo::class,
        'ne' => Operators\Comparison\NotEqualTo::class,
        'gt' => Operators\Comparison\GreaterThan::class,
        'gte' => Operators\Comparison\GreaterThanOrEqualTo::class,
        'lt' => Operators\Comparison\LessThan::class,
        'lte' => Operators\Comparison\LessThanOrEqualTo::class,
        'in' => Operators\Comparison\In::class,
    ];

    private const LOGICAL_MAP = [
        'and' => Operators\Logical\LogicalAnd::class,
        'or' => Operators\Logical\LogicalOr::class,
        'not' => Operators\Logical\LogicalNot::class,
        'nor' => Operators\Logical\LogicalNor::class,
    ];

    private const MATH_MAP = [
        'add' => Operators\Mathematical\Addition::class,
        'subtract' => Operators\Mathematical\Subtraction::class,
        'multiply' => Operators\Mathematical\Multiplication::class,
        'divide' => Operators\Mathematical\Division::class,
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

    public function getMath(string $operator): string
    {
        return self::MATH_MAP[$operator]
            ?? throw new \InvalidArgumentException("Unknown math operator: $operator");
    }
}
```

#### Phase 3: Compiler (Week 2)

**3.1 Create Compiler (`MongoCompiler.php`)**
```php
namespace Cline\Ruler\DSL\MongoQuery;

use Cline\Ruler\Operator\Proposition;
use Cline\Ruler\Variable;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class MongoCompiler
{
    public function __construct(
        private FieldResolver $fieldResolver,
        private MongoOperatorRegistry $operatorRegistry
    ) {}

    public function compile(MongoNode $ast): Proposition
    {
        return $this->compileNode($ast);
    }

    private function compileNode(MongoNode $node): mixed
    {
        return match (true) {
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof InNode => $this->compileIn($node),
            $node instanceof ExistsNode => $this->compileExists($node),
            $node instanceof RegexNode => $this->compileRegex($node),
            $node instanceof AllNode => $this->compileAll($node),
            $node instanceof ElemMatchNode => $this->compileElemMatch($node),
            $node instanceof SizeNode => $this->compileSize($node),
            $node instanceof ModNode => $this->compileMod($node),
            $node instanceof TypeNode => $this->compileType($node),
            $node instanceof ExpressionNode => $this->compileExpression($node),
            $node instanceof MathExpressionNode => $this->compileMathExpression($node),
            $node instanceof FieldRefNode => $this->fieldResolver->resolve($node->fieldName),
            default => throw new \RuntimeException("Unknown node type: " . get_class($node)),
        };
    }

    private function compileLogical(LogicalNode $node): Proposition
    {
        $operatorClass = $this->operatorRegistry->getLogical($node->operator);
        $compiledConditions = array_map(
            fn($condition) => $this->compileNode($condition),
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

        $inOperator = new \Cline\Ruler\Operators\Comparison\In($field, $node->values);

        // $nin = NOT($in)
        if ($node->operator === 'nin') {
            return new \Cline\Ruler\Operators\Logical\LogicalNot([$inOperator]);
        }

        return $inOperator;
    }

    private function compileExists(ExistsNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        // $exists: true => field != null
        // $exists: false => field == null
        $isNull = new \Cline\Ruler\Operators\Comparison\EqualTo($field, null);

        return $node->shouldExist
            ? new \Cline\Ruler\Operators\Logical\LogicalNot([$isNull])
            : $isNull;
    }

    private function compileRegex(RegexNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        // Build regex pattern with flags
        $pattern = '/' . $node->pattern . '/';
        if (str_contains($node->options, 'i')) {
            $pattern .= 'i';
        }
        if (str_contains($node->options, 'm')) {
            $pattern .= 'm';
        }

        return new \Cline\Ruler\Operators\String\Regex($field, $pattern);
    }

    private function compileAll(AllNode $node): Proposition
    {
        // $all: [a, b] means array contains all values
        // Compile as: (a IN array) AND (b IN array)
        $field = $this->fieldResolver->resolve($node->field);

        $conditions = [];
        foreach ($node->values as $value) {
            $conditions[] = new \Cline\Ruler\Operators\Comparison\In($value, $field);
        }

        return new \Cline\Ruler\Operators\Logical\LogicalAnd($conditions);
    }

    private function compileElemMatch(ElemMatchNode $node): Proposition
    {
        // $elemMatch requires custom implementation
        // For now, compile the inner condition against the array field
        return $this->compileNode($node->condition);
    }

    private function compileSize(SizeNode $node): Proposition
    {
        // $size: N means array length equals N
        $field = $this->fieldResolver->resolve($node->field);

        // Use stringLength or custom ArrayLength operator
        $lengthOp = new \Cline\Ruler\Operators\String\StringLength($field);
        $lengthVar = new Variable($this->fieldResolver->getRuleBuilder(), null, $lengthOp);

        return new \Cline\Ruler\Operators\Comparison\EqualTo($lengthVar, $node->size);
    }

    private function compileMod(ModNode $node): Proposition
    {
        // $mod: [divisor, remainder] => field % divisor == remainder
        $field = $this->fieldResolver->resolve($node->field);

        $modOp = new \Cline\Ruler\Operators\Mathematical\Modulo($field, $node->divisor);
        $modVar = new Variable($this->fieldResolver->getRuleBuilder(), null, $modOp);

        return new \Cline\Ruler\Operators\Comparison\EqualTo($modVar, $node->remainder);
    }

    private function compileType(TypeNode $node): Proposition
    {
        // $type: "string" => gettype(field) === "string"
        // Requires custom TypeOf operator
        $field = $this->fieldResolver->resolve($node->field);

        $typeOp = new \Cline\Ruler\Operators\Type\TypeOf($field);
        $typeVar = new Variable($this->fieldResolver->getRuleBuilder(), null, $typeOp);

        return new \Cline\Ruler\Operators\Comparison\EqualTo($typeVar, $this->normalizeTypeName($node->type));
    }

    private function compileExpression(ExpressionNode $node): Proposition
    {
        $left = is_object($node->left) ? $this->compileNode($node->left) : $node->left;
        $right = is_object($node->right) ? $this->compileNode($node->right) : $node->right;

        $operatorClass = $this->operatorRegistry->getComparison($node->operator);
        return new $operatorClass($left, $right);
    }

    private function compileMathExpression(MathExpressionNode $node): Variable
    {
        $compiledOperands = array_map(
            fn($op) => is_object($op) ? $this->compileNode($op) : $op,
            $node->operands
        );

        $operatorClass = $this->operatorRegistry->getMath($node->operator);

        // Math operators are typically binary, so reduce for n-ary
        $result = array_shift($compiledOperands);
        foreach ($compiledOperands as $operand) {
            $mathOp = new $operatorClass($result, $operand);
            $result = new Variable($this->fieldResolver->getRuleBuilder(), null, $mathOp);
        }

        return $result;
    }

    private function normalizeTypeName(string $mongoType): string
    {
        // MongoDB type names to PHP type names
        return match ($mongoType) {
            'double', 'int', 'long', 'decimal' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            'null' => 'null',
            default => $mongoType,
        };
    }
}
```

**3.2 Create New Operators (if needed)**
```php
namespace Cline\Ruler\Operators\Type;

class TypeOf extends VariableOperator
{
    public function prepareValue(mixed $value): string
    {
        return gettype($value);
    }
}

namespace Cline\Ruler\Operators\Array;

class ArrayLength extends VariableOperator
{
    public function prepareValue(mixed $value): int
    {
        return is_array($value) ? count($value) : 0;
    }
}
```

#### Phase 4: Main Facade (Week 2)

**4.1 Create MongoQueryRuleBuilder**
```php
namespace Cline\Ruler\DSL\MongoQuery;

use Cline\Ruler\Rule;
use Cline\Ruler\RuleBuilder;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

class MongoQueryRuleBuilder
{
    private MongoQueryParser $parser;
    private MongoCompiler $compiler;
    private ?RuleBuilder $ruleBuilder;

    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        $this->ruleBuilder = $ruleBuilder;
        $this->parser = new MongoQueryParser();

        $fieldResolver = new FieldResolver($ruleBuilder ?? new RuleBuilder());
        $operatorRegistry = new MongoOperatorRegistry();
        $this->compiler = new MongoCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parse MongoDB query and return Rule
     *
     * @param string|array $query MongoDB query document (JSON string or PHP array)
     * @return Rule Compiled rule ready for evaluation
     *
     * @throws \InvalidArgumentException if query syntax is invalid
     * @throws \JsonException if JSON is malformed
     */
    public function parse(string|array $query): Rule
    {
        $ast = $this->parser->parse($query);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();
        return $rb->create($proposition);
    }

    /**
     * Validate query syntax without creating Rule
     */
    public function validate(string|array $query): bool
    {
        try {
            $this->parser->parse($query);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Convert MongoDB query to equivalent Wirefilter expression
     * Useful for debugging and understanding query translation
     */
    public function toWirefilter(string|array $query): string
    {
        // Optional helper for documentation/debugging
    }
}
```

#### Phase 5: Testing (Week 3)

**5.1 Parser Tests (`MongoQueryParserTest.php`)**
```php
test('parses implicit equality', function (): void {
    $parser = new MongoQueryParser();
    $ast = $parser->parse(['age' => 18]);

    expect($ast)->toBeInstanceOf(ComparisonNode::class)
        ->and($ast->operator)->toBe('eq')
        ->and($ast->field)->toBe('age')
        ->and($ast->value)->toBe(18);
});

test('parses comparison operators', function (): void {
    $ast = $parser->parse(['age' => ['$gte' => 18]]);
    expect($ast->operator)->toBe('gte');
});

test('parses $in operator', function (): void {
    $ast = $parser->parse(['country' => ['$in' => ['US', 'CA', 'UK']]]);
    expect($ast)->toBeInstanceOf(InNode::class);
});

test('parses implicit AND', function (): void {
    $ast = $parser->parse([
        'age' => ['$gte' => 18],
        'country' => 'US',
        'status' => ['$ne' => 'banned']
    ]);

    expect($ast)->toBeInstanceOf(LogicalNode::class)
        ->and($ast->operator)->toBe('and')
        ->and($ast->conditions)->toHaveCount(3);
});

test('parses explicit $and', function (): void {
    $ast = $parser->parse([
        '$and' => [
            ['age' => ['$gte' => 18]],
            ['country' => 'US']
        ]
    ]);

    expect($ast->operator)->toBe('and');
});

test('parses $expr with field comparison', function (): void {
    $ast = $parser->parse([
        '$expr' => [
            '$gt' => ['$balance', '$minimum']
        ]
    ]);

    expect($ast)->toBeInstanceOf(ExpressionNode::class);
});

test('parses nested $or and $and', function (): void {
    $ast = $parser->parse([
        '$and' => [
            [
                '$or' => [
                    ['age' => ['$gte' => 18]],
                    ['vip' => true]
                ]
            ],
            ['country' => 'US']
        ]
    ]);

    expect($ast->operator)->toBe('and');
});

test('parses regex with options', function (): void {
    $ast = $parser->parse([
        'email' => [
            '$regex' => '.*@example\\.com$',
            '$options' => 'i'
        ]
    ]);

    expect($ast)->toBeInstanceOf(RegexNode::class)
        ->and($ast->options)->toBe('i');
});

test('accepts both JSON string and array', function (): void {
    $json = '{"age": {"$gte": 18}}';
    $array = ['age' => ['$gte' => 18]];

    $astFromJson = $parser->parse($json);
    $astFromArray = $parser->parse($array);

    expect($astFromJson)->toEqual($astFromArray);
});
```

**5.2 Integration Tests (`MongoQueryIntegrationTest.php`)**
```php
test('implicit equality works', function (): void {
    $mqb = new MongoQueryRuleBuilder();
    $rule = $mqb->parse(['age' => 18]);

    expect($rule->evaluate(new Context(['age' => 18])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 20])))->toBeFalse();
});

test('$gte operator works', function (): void {
    $rule = $mqb->parse(['age' => ['$gte' => 18]]);

    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 16])))->toBeFalse();
});

test('$in operator works', function (): void {
    $rule = $mqb->parse(['country' => ['$in' => ['US', 'CA', 'UK']]]);

    expect($rule->evaluate(new Context(['country' => 'US'])))->toBeTrue();
    expect($rule->evaluate(new Context(['country' => 'FR'])))->toBeFalse();
});

test('implicit AND works', function (): void {
    $rule = $mqb->parse([
        'age' => ['$gte' => 18],
        'country' => 'US',
        'status' => ['$ne' => 'banned']
    ]);

    $valid = new Context(['age' => 20, 'country' => 'US', 'status' => 'active']);
    expect($rule->evaluate($valid))->toBeTrue();

    $invalid = new Context(['age' => 20, 'country' => 'FR', 'status' => 'active']);
    expect($rule->evaluate($invalid))->toBeFalse();
});

test('$or operator works', function (): void {
    $rule = $mqb->parse([
        '$or' => [
            ['status' => 'active'],
            ['status' => 'pending']
        ]
    ]);

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'pending'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'deleted'])))->toBeFalse();
});

test('$not operator works', function (): void {
    $rule = $mqb->parse(['$not' => ['status' => 'banned']]);

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'banned'])))->toBeFalse();
});

test('$exists operator works', function (): void {
    $rule = $mqb->parse(['email' => ['$exists' => true]]);

    expect($rule->evaluate(new Context(['email' => 'test@example.com'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => null])))->toBeFalse();
    expect($rule->evaluate(new Context([])))->toBeFalse();
});

test('$regex operator works', function (): void {
    $rule = $mqb->parse([
        'email' => [
            '$regex' => '.*@example\\.com$',
            '$options' => 'i'
        ]
    ]);

    expect($rule->evaluate(new Context(['email' => 'john@EXAMPLE.COM'])))->toBeTrue();
    expect($rule->evaluate(new Context(['email' => 'john@test.com'])))->toBeFalse();
});

test('$expr with field comparison works', function (): void {
    $rule = $mqb->parse([
        '$expr' => ['$gt' => ['$balance', '$minimum']]
    ]);

    $valid = new Context(['balance' => 100, 'minimum' => 50]);
    expect($rule->evaluate($valid))->toBeTrue();

    $invalid = new Context(['balance' => 30, 'minimum' => 50]);
    expect($rule->evaluate($invalid))->toBeFalse();
});

test('$expr with math works', function (): void {
    $rule = $mqb->parse([
        '$expr' => [
            '$gt' => [
                ['$add' => ['$price', '$shipping']],
                100
            ]
        ]
    ]);

    expect($rule->evaluate(new Context(['price' => 80, 'shipping' => 25])))->toBeTrue();
    expect($rule->evaluate(new Context(['price' => 80, 'shipping' => 10])))->toBeFalse();
});

test('complex nested conditions work', function (): void {
    $rule = $mqb->parse([
        '$and' => [
            [
                '$or' => [
                    ['age' => ['$gte' => 18, '$lt' => 65]],
                    ['vip' => true]
                ]
            ],
            ['country' => ['$in' => ['US', 'CA', 'UK']]],
            [
                '$not' => [
                    'status' => ['$in' => ['banned', 'deleted']]
                ]
            ]
        ]
    ]);

    $valid = new Context([
        'age' => 30,
        'vip' => false,
        'country' => 'US',
        'status' => 'active'
    ]);
    expect($rule->evaluate($valid))->toBeTrue();
});

test('accepts JSON string', function (): void {
    $json = '{"age": {"$gte": 18}, "country": "US"}';
    $rule = $mqb->parse($json);

    $valid = new Context(['age' => 20, 'country' => 'US']);
    expect($rule->evaluate($valid))->toBeTrue();
});

test('range query works', function (): void {
    $rule = $mqb->parse(['age' => ['$gte' => 18, '$lte' => 65]]);

    expect($rule->evaluate(new Context(['age' => 30])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 70])))->toBeFalse();
    expect($rule->evaluate(new Context(['age' => 16])))->toBeFalse();
});

test('dot notation for nested fields', function (): void {
    $rule = $mqb->parse(['user.profile.age' => ['$gte' => 18]]);

    $context = new Context([
        'user' => ['profile' => ['age' => 25]]
    ]);
    expect($rule->evaluate($context))->toBeTrue();
});

test('$nin operator works', function (): void {
    $rule = $mqb->parse(['status' => ['$nin' => ['banned', 'deleted']]]);

    expect($rule->evaluate(new Context(['status' => 'active'])))->toBeTrue();
    expect($rule->evaluate(new Context(['status' => 'banned'])))->toBeFalse();
});

test('$mod operator works', function (): void {
    $rule = $mqb->parse(['age' => ['$mod' => [2, 0]]]);  // even ages

    expect($rule->evaluate(new Context(['age' => 20])))->toBeTrue();
    expect($rule->evaluate(new Context(['age' => 21])))->toBeFalse();
});
```

#### Phase 6: Documentation (Week 3-4)

**6.1 Create Cookbook (`cookbook/mongodb-query-syntax.md`)**
**6.2 Add REST API Examples**
**6.3 Frontend Integration Guide**

### Architecture

```
DSL/
├── Wirefilter/
├── SqlWhere/
└── MongoQuery/                     # New
    ├── MongoQueryRuleBuilder.php   # Main facade
    ├── MongoQueryParser.php        # JSON → AST
    ├── MongoNode.php               # AST node definitions
    ├── MongoCompiler.php           # AST → Operator tree
    └── MongoOperatorRegistry.php   # Mongo → Ruler operator mapping
```

### Dependencies

**Required:**
- None (uses PHP's built-in `json_decode`)

**Optional:**
- `mongodb/mongodb` - For validation against real MongoDB syntax (dev dependency only)

## Limitations

Based on the [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md), MongoDB Query DSL has minimal limitations and is the **most feature-complete DSL**:

### Unsupported Features

**❌ Inline Arithmetic**
- No mathematical expressions in filters (cannot use `{"total": {"$gt": {"$add": ["price", "shipping"]}}}`)
- **Workaround:** Pre-compute values: `$context['total'] = $price + $shipping` OR use $expr operator for field comparisons
- **Why:** MongoDB query documents are designed for filtering, not computation (use aggregation pipeline for calculations)

**❌ Action Callbacks**
- Cannot execute code on rule match (feature unique to Wirefilter DSL)
- **Workaround:** Handle actions in application code after rule evaluation
- **Why:** JSON-based DSLs are declarative, not imperative

### Unique Advantages - Most Feature-Complete DSL

**✅ Extended Comparison Operators (Custom)**
- **Strict equality:** `$same` (===) and `$nsame` (!==)
- **Range:** `$between` for numeric ranges
- All standard operators: `$eq`, `$ne`, `$gt`, `$gte`, `$lt`, `$lte`, `$in`, `$nin`

**✅ Extended Logical Operators (Custom)**
- Standard: `$and`, `$or`, `$not`, `$nor`
- **Custom additions:** `$xor` (exactly one true), `$nand` (not all true)

**✅ Comprehensive String Operations (Custom - 11 operators)**
- **Regex:** `$regex` with options (`$options`: i, m, s)
- **Inverse regex:** `$notRegex` (custom)
- **Contains:** `$contains`, `$containsi` (case-insensitive)
- **Not contains:** `$notContains`, `$notContainsi` (case-insensitive)
- **Prefix:** `$startsWith`, `$startsWithi` (case-insensitive)
- **Suffix:** `$endsWith`, `$endsWithi` (case-insensitive)
- **Length:** `$strLength` with comparison support

**✅ Date Operations (Custom - ONLY DSL with date support)**
- **After:** `$after` - date is after specified date
- **Before:** `$before` - date is before specified date
- **Between:** `$betweenDates` - date range check

**✅ Type Checking (Custom - 3 operators)**
- **Type check:** `$type` (string, number, boolean, array, null)
- **Empty check:** `$empty` (null, empty array, empty string)
- **Array size:** `$size` with exact count or comparisons

**✅ JSON-Native**
- Already parsed and validated
- Perfect for REST APIs
- Type-safe structure
- Easy to generate programmatically

### Why MongoDB Query is Most Feature-Complete

1. **28 custom operators** extending standard MongoDB syntax
2. **Only DSL with date operations**
3. **Most comprehensive string operations** (11 operators, 8 custom)
4. **Extended logical operators** ($xor, $nand)
5. **Full type checking** with multiple type operators
6. **Strict equality support** ($same, $nsame)
7. **JSON-based** - no parsing complexity

See [DSL Feature Support Matrix](../docs/dsl-feature-matrix.md) for comprehensive comparison.

## Consequences

### Positive
- **API-first design** - Perfect for REST/GraphQL endpoints
- **Zero parsing complexity** - JSON decode is built-in and fast
- **Type safety** - JSON structure is validated during decode
- **Industry standard** - 30M+ developers familiar with MongoDB
- **Easy to generate** - Frontend can build queries programmatically
- **Excellent tooling** - JSON editors, validators, formatters everywhere
- **Composable** - Easy to merge/extend queries programmatically
- **No escaping issues** - JSON handles strings safely

### Negative
- **Verbose** - More characters than Wirefilter for simple conditions
- **Not human-authored** - Too verbose for manual writing (API-only)
- **Nested arrays** - Complex queries create deep nesting
- **Limited operators** - Some Ruler operators don't map to MongoDB equivalents

### Neutral
- Adds dependency on JSON validation
- Requires frontend developers to learn MongoDB query syntax

## Alternatives Considered

### GraphQL Filter Syntax
- **Pros:** Similar to MongoDB but more frontend-friendly
- **Cons:** Less standardized, multiple competing implementations
- **Decision:** MongoDB syntax is more established and documented

### JSON Schema
- **Pros:** Validation built-in
- **Cons:** Not designed for queries, too verbose
- **Decision:** MongoDB query syntax is purpose-built for filtering

### Custom JSON Format
- **Pros:** Could optimize for our specific use case
- **Cons:** Zero ecosystem, requires complete documentation
- **Decision:** Leverage existing MongoDB knowledge base

## Implementation Risks

### High Risk
1. **$expr complexity** - Aggregation expressions are powerful but complex
   - Mitigation: Start with subset of operators, document limitations clearly

2. **Type coercion** - MongoDB's type rules differ from PHP's
   - Mitigation: Document type handling explicitly, add type validation tests

### Medium Risk
1. **Array operators** - $all, $elemMatch need custom implementations
   - Mitigation: Phase these in after core operators working

2. **Performance** - Large JSON documents might be slow to parse
   - Mitigation: Add query size limits, benchmark against alternatives

### Low Risk
1. **JSON malformation** - Invalid JSON throws exceptions
   - Mitigation: Catch JsonException, return clear error messages

## Verification

### Acceptance Criteria
- [ ] Parse all basic comparison operators ($eq, $ne, $gt, $gte, $lt, $lte)
- [ ] Parse logical operators ($and, $or, $not, $nor)
- [ ] Parse $in and $nin
- [ ] Parse $exists
- [ ] Parse $regex with $options
- [ ] Parse implicit equality (field: value)
- [ ] Parse implicit AND (multiple fields)
- [ ] Support $expr with field comparisons
- [ ] Support $expr with math expressions
- [ ] Parse nested conditions (arbitrary depth)
- [ ] Accept both JSON string and PHP array
- [ ] Support dot notation for nested fields
- [ ] Parse $mod operator
- [ ] Parse $type operator
- [ ] 100% test coverage without mocks
- [ ] Performance: parse 10,000 simple queries/second
- [ ] Documentation with REST API integration examples

### Performance Targets
- Parse simple query: < 0.5ms
- Parse complex nested query: < 2ms
- Memory: < 500KB per parser instance

### Testing Strategy
1. **Unit Tests** - Each node type, operator mapping
2. **Integration Tests** - End-to-end with Context evaluation
3. **JSON Tests** - Both string and array input
4. **Edge Cases** - Malformed JSON, type coercion, null handling
5. **API Tests** - Real REST endpoint examples
6. **Performance Tests** - Benchmark against SQL-WHERE DSL

## Timeline

- **Week 1:** Parser + AST structure + basic operators
- **Week 2:** Compiler + advanced operators ($expr, $regex) + facade
- **Week 3:** Testing (unit + integration + edge cases)
- **Week 4:** Documentation + REST API examples + performance optimization

**Total Effort:** 4 weeks for 1 senior developer

## References

- [MongoDB Query Documentation](https://docs.mongodb.com/manual/tutorial/query-documents/)
- [MongoDB Comparison Operators](https://docs.mongodb.com/manual/reference/operator/query-comparison/)
- [MongoDB Logical Operators](https://docs.mongodb.com/manual/reference/operator/query-logical/)
- [MongoDB Evaluation Operators](https://docs.mongodb.com/manual/reference/operator/query-evaluation/)
- [ADR 001: Wirefilter-style DSL](001-wirefilter-style-dsl.md)
- [ADR 002: SQL-WHERE DSL](002-sql-where-dsl.md)
