# ADR 008: DSL Facade Pattern

**Status:** Accepted
**Date:** 2025-01-16
**Context:** Standardizing public API for all DSL implementations

---

## Context

The Ruler library supports multiple DSL syntaxes (Wirefilter, SQL WHERE, MongoDB Query, GraphQL Filter, LDAP Filter, JMESPath, Natural Language). Each DSL has internal implementation classes but lacks a consistent public API for common operations:

1. **Parsing** - Converting DSL strings → Rule objects
2. **Serializing** - Converting Rule objects → DSL strings
3. **Validating** - Checking DSL syntax without full parsing

Without a standardized pattern, users face:
- Inconsistent APIs across DSLs
- Difficulty discovering capabilities
- No clear pattern for implementing new DSLs
- Confusion about which classes are public vs internal

## Decision

We will adopt the **Three-Class Facade Pattern** for all DSL implementations:

### Pattern Structure

Each DSL must provide exactly **three public classes**:

```
src/DSL/{DSLName}/
├── {DSLName}Parser.php       - Parse DSL → Rules
├── {DSLName}Serializer.php   - Serialize Rules → DSL
├── {DSLName}Validator.php    - Validate DSL syntax
└── ValidationResult.php      - Shared validation result DTO
```

### 1. Parser: DSL String → Rule Object

**Purpose:** Convert DSL expression strings into executable Rule objects.

**Naming:** `{DSLName}Parser` (e.g., `WirefilterParser`, `SQLWhereParser`)

**Interface:**
```php
final readonly class {DSLName}Parser
{
    public function __construct(?RuleBuilder $ruleBuilder = null) {}

    public function parse(string $expression): Rule {}

    public function parseWithAction(string $expression, callable $action): Rule {}
}
```

**Example:**
```php
$parser = new WirefilterParser();
$rule = $parser->parse('age >= 18 && country == "US"');
$result = $rule->evaluate($context);
```

### 2. Serializer: Rule Object → DSL String

**Purpose:** Convert Rule objects back to DSL expression strings.

**Naming:** `{DSLName}Serializer` (e.g., `WirefilterSerializer`, `SQLWhereSerializer`)

**Interface:**
```php
final readonly class {DSLName}Serializer
{
    public function serialize(Rule $rule): string {}
}
```

**Example:**
```php
$serializer = new WirefilterSerializer();
$expression = $serializer->serialize($rule);
// Returns: 'age >= 18 && country == "US"'
```

### 3. Validator: Syntax Validation

**Purpose:** Validate DSL syntax without full compilation overhead.

**Naming:** `{DSLName}Validator` (e.g., `WirefilterValidator`, `SQLWhereValidator`)

**Interface:**
```php
final readonly class {DSLName}Validator
{
    public function validate(string $expression): bool {}

    public function validateWithErrors(string $expression): ValidationResult {}
}
```

**Example:**
```php
$validator = new WirefilterValidator();

// Quick validation
if ($validator->validate('age >= 18')) {
    // Valid syntax
}

// Detailed validation
$result = $validator->validateWithErrors('age >= invalid');
if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo $error['message'];
    }
}
```

### ValidationResult DTO

Shared across all DSLs for structured error reporting:

```php
final readonly class ValidationResult
{
    public static function success(): self {}
    public static function failure(array $errors): self {}

    public function isValid(): bool {}
    public function getErrors(): array {}
    public function getErrorMessages(): array {}
    public function getFirstError(): ?string {}
}
```

---

## Implementation Guidelines

### Internal vs Public Classes

**Public (user-facing):**
- `{DSLName}Parser`
- `{DSLName}Serializer`
- `{DSLName}Validator`
- `ValidationResult`

**Internal (implementation details):**
- `StringRuleBuilder` - Internal DSL machinery
- `ExpressionParser` - AST parsing
- `RuleCompiler` - AST → Proposition compilation
- `OperatorRegistry` - Operator mappings
- `FieldResolver` - Variable resolution

### Documentation Requirements

Each facade class must include:

1. **Pattern reminder in docblock:**
```php
/**
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 */
```

2. **Cross-references:**
```php
/**
 * @see {DSL}Parser For parsing DSL strings into Rules
 * @see {DSL}Serializer For converting Rules back to DSL strings
 * @see {DSL}Validator For validating DSL strings
 */
```

3. **Usage examples in docblock**

### Delegation Pattern

Facade classes **delegate** to internal implementation:

```php
final readonly class WirefilterParser
{
    private StringRuleBuilder $builder;

    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        // Delegate to internal implementation
        $this->builder = new StringRuleBuilder($ruleBuilder);
    }

    public function parse(string $expression): Rule
    {
        return $this->builder->parse($expression);
    }
}
```

**Benefits:**
- Internal classes remain for advanced users
- No breaking changes to existing code
- Clean separation of concerns
- Easy to evolve internal implementation

---

## Consequences

### Positive

1. **Consistent API** - Same pattern across all DSLs
2. **Discoverability** - Clear naming makes features obvious
3. **Future-proof** - Easy to add new DSLs following the pattern
4. **Backward compatible** - Internal classes remain available
5. **Testability** - Each facade class has focused responsibility
6. **Documentation** - Pattern serves as living documentation

### Negative

1. **More classes** - 3 new classes per DSL (but clearer purpose)
2. **Delegation overhead** - Minimal runtime cost for cleaner API
3. **Migration effort** - Need to update existing DSLs

### Neutral

1. **Testing** - Each facade needs its own test suite
2. **Maintenance** - More classes to maintain, but clearer boundaries

---

## Migration Plan

### Phase 1: Wirefilter (Reference Implementation)
- ✅ Create `WirefilterParser`
- ✅ Create `WirefilterSerializer`
- ✅ Create `WirefilterValidator`
- ✅ Create `ValidationResult`
- ✅ Write comprehensive tests
- ✅ Document pattern in ADR

### Phase 2: Remaining DSLs
Apply pattern to:
- SQL WHERE DSL
- MongoDB Query DSL
- GraphQL Filter DSL
- LDAP Filter DSL
- JMESPath DSL
- Natural Language DSL

### Phase 3: Documentation
- Update README with new API
- Add migration guide
- Update cookbook examples

---

## Examples

### Basic Usage

```php
use Cline\Ruler\DSL\Wirefilter\{WirefilterParser, WirefilterSerializer, WirefilterValidator};
use Cline\Ruler\Core\Context;

// Validate
$validator = new WirefilterValidator();
if (!$validator->validate($input)) {
    $result = $validator->validateWithErrors($input);
    foreach ($result->getErrors() as $error) {
        echo $error['message'];
    }
    exit(1);
}

// Parse
$parser = new WirefilterParser();
$rule = $parser->parse('age >= 18 && country == "US"');

// Evaluate
$context = new Context(['age' => 25, 'country' => 'US']);
if ($rule->evaluate($context)) {
    echo "Eligible!";
}

// Serialize
$serializer = new WirefilterSerializer();
$expression = $serializer->serialize($rule);
echo $expression; // 'age >= 18 && country == "US"'
```

### Round-Trip Guarantee

```php
$parser = new WirefilterParser();
$serializer = new WirefilterSerializer();

$original = 'age >= 18 && country in ["US", "CA"]';
$rule = $parser->parse($original);
$serialized = $serializer->serialize($rule);

assert($original === $serialized); // True
```

---

## References

- **Wirefilter Reference Implementation:** `src/DSL/Wirefilter/`
- **Tests:** `tests/Unit/DSL/Wirefilter/`
- **Facade Pattern:** [Martin Fowler - Facade](https://martinfowler.com/eaaCatalog/facade.html)
