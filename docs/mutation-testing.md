# Mutation Testing Strategy

## Current Decision

This project uses Pest's native mutation runner (`pest --mutate`) as the
authoritative mutation gate.

## Why

- It integrates directly with the existing Pest-based test suite.
- It avoids namespace/JUnit mapping issues observed with Infection + Pest in
  this codebase.
- It keeps mutation execution and local developer setup simple.

## Scope

The required mutation gate currently targets evaluator and structured error
contract code:

- `Cline\Ruler\Core\RuleEvaluator`
- `Cline\Ruler\Exceptions\RuleEvaluatorException`
- `Cline\Ruler\Enums\RuleErrorCode`
- `Cline\Ruler\Enums\RuleErrorPhase`

## Revisit Criteria

Reevaluate Infection if all of the following are true:

- Pest/coverage/JUnit mapping interoperability is stable in CI.
- Infection can run with equivalent target scope and deterministic results.
- Migration provides clear value over the current Pest-native workflow.

Until then, Pest mutation testing remains the default and supported path.
