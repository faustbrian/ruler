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
