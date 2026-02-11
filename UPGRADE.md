# Upgrade Guide

This document covers upgrading from `v2.x` to `v4.x`.

## Summary Of Breaking Changes

1. `Rule::execute()` now returns `RuleExecutionResult` instead of `void`.
2. `RuleSet::executeRules()` and `executeForwardChaining()` now return
   `RuleSetExecutionReport` instead of scalar counts.
3. `RuleEvaluator::evaluateFrom*()` now returns `RuleEvaluatorReport` instead
   of `bool`.
4. `RuleEvaluator::createFrom*()` has been replaced by `compileFrom*()`
   methods that return `RuleEvaluatorCompilationResult`.
5. Rule IDs are mandatory and must be unique inside a `RuleSet`.
6. Action callbacks must accept `Context`.
7. Rule-definition value references must be explicit (`@path.to.value`).
8. DSL builders now require explicit `ruleId` in `parse*()` calls.
9. Minimum PHP version is now `8.5`.

## 1) Rule Execution Return Type

### Before (v2)

```php
$rule->execute($context);
```

### After (v4)

```php
$result = $rule->execute($context);

if ($result->matched) {
    // condition matched
}

if ($result->actionExecuted) {
    // action ran
}
```

## 2) RuleSet Execution Return Type

### Before (v2)

```php
$executed = $ruleSet->executeRules($context); // int
```

### After (v4)

```php
$report = $ruleSet->executeRules($context);

$matched = $report->getMatchedCount();
$actions = $report->getActionExecutionCount();
$results = $report->getResults();
```

Forward chaining now also returns a report:

```php
$report = $ruleSet->executeForwardChaining($context);
$cycles = $report->getCycleCount();
```

## 3) RuleEvaluator Evaluate Return Type

### Before (v2)

```php
$passed = $evaluator->evaluateFromArray($values); // bool
```

### After (v4)

```php
$report = $evaluator->evaluateFromArray($values);
$passed = $report->getResult();
$ruleResult = $report->getRuleResult();
```

## 4) RuleEvaluator Construction Flow

### Before (v2)

```php
$evaluator = RuleEvaluator::createFromArray($rules);
```

### After (v4)

```php
$compiled = RuleEvaluator::compileFromArray($rules);

if (!$compiled->isSuccess()) {
    $error = $compiled->getError();
    // handle compile error
}

$evaluator = $compiled->getEvaluator();
```

The same pattern applies to JSON/YAML/file variants (`compileFromJson`,
`compileFromYaml`, etc.).

## 5) Explicit Rule IDs Everywhere

### Before (v2)

```php
$rule = $rb->create($rb['age']->greaterThan(18));
```

### After (v4)

```php
$rule = $rb->create(
    $rb['age']->greaterThan(18),
    'age-over-18',
);
```

`RuleSet` now enforces unique IDs. Reusing the same ID across different rule
instances in one set throws.

## 6) Action Callback Signature

### Before (v2)

```php
$rule = $rb->create(
    $condition,
    'rule-id',
    fn (): void => doSomething(),
);
```

### After (v4)

```php
use Cline\Ruler\Core\Context;

$rule = $rb->create(
    $condition,
    'rule-id',
    fn (Context $context): void => doSomething(),
);
```

## 7) Explicit Context Reference Syntax In Persisted Rules

Values are now treated as literals unless prefixed with `@`.

### Before (v2)

```json
{
  "field": "score",
  "operator": "greaterThanOrEqualTo",
  "value": "limits.minScore"
}
```

### After (v4)

```json
{
  "field": "score",
  "operator": "greaterThanOrEqualTo",
  "value": "@limits.minScore"
}
```

For stored legacy payloads, migrate first:

```php
use Cline\Ruler\Core\RuleDefinitionMigrator;

$migrated = RuleDefinitionMigrator::migrateLegacyStringReferences($legacy);
```

## 8) DSL Parser Signatures Require `ruleId`

### Before (v2)

```php
$rule = $builder->parse('age >= 18');
```

### After (v4)

```php
$rule = $builder->parse('age >= 18', 'age-gate');
```

Same requirement applies to `parseWithAction(...)`.

## 9) Error Handling Is Structured

`RuleEvaluatorException` now provides machine-readable metadata:

```php
try {
    $report = $evaluator->evaluateFromJson($json);
} catch (RuleEvaluatorException $e) {
    $payload = $e->toArray();
    // [
    //   'errorCode' => '...',
    //   'phase' => 'compile|runtime',
    //   'path' => [...],
    //   'details' => [...]
    // ]
}
```

## 10) PHP Requirement

Update runtime to PHP `8.5` or newer before upgrading to `v4`.
