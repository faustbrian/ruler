# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.3.0] - 2026-02-12

### Added

- Introduced `CompatibilityMode` with `CompatibilityMode::Legacy` and
  `CompatibilityMode::Strict` (default) for centralized compatibility control.
- Added optional `compatibilityMode` argument to:
  - `RuleCompiler::compileFromArray()`
  - `RuleCompiler::compileFromJson()`
  - `RuleCompiler::compileFromJsonFile()`
  - `RuleCompiler::compileFromYaml()`
  - `RuleCompiler::compileFromYamlFile()`
  - `RuleEvaluator::compileFromArray()`
  - `RuleEvaluator::compileFromJson()`
  - `RuleEvaluator::compileFromJsonFile()`
  - `RuleEvaluator::compileFromYaml()`
  - `RuleEvaluator::compileFromYamlFile()`
- Added first-party legacy rule migration support in `RuleDefinitionMigrator`:
  - `migrateLegacyLogicalCombinators()` for `type/rules` payloads
  - `migrateForCompatibilityMode()` for one-step compatibility transforms

### Changed

- Legacy schema and implicit value-reference handling can now be opted into
  directly via `CompatibilityMode::Legacy`, removing the need for
  app-specific adapters.
- `CompatibilityMode::Legacy` now also normalizes legacy operator aliases
  (`contains`, `doesNotContain`, `in`, `notIn`) and supports dotted `field`
  evaluation for both flat and nested context payloads.

## [4.1.0] - 2026-02-11

### Breaking Changes

- `RuleBuilder::create(...)` and DSL `parse*()` methods no longer accept raw
  string IDs. You must pass `RuleId` value objects (for example
  `RuleIds::fromString('my-rule-id')`).

### Added

- `RuleCompiler` with non-throwing compile helpers for array/JSON/YAML payloads:
  - `compileFromArray()`
  - `compileFromJson()`
  - `compileFromJsonFile()`
  - `compileFromYaml()`
  - `compileFromYamlFile()`
- `RuleCompilationResult` for structured success/failure access:
  - `isSuccess()`
  - `getRule()`
  - `getError()`
- Shared internal definition-to-proposition compiler path so `RuleEvaluator`
  and direct rule compilation use the same semantics.

### Changed

- `RuleEvaluator` now uses the shared typed definition compiler internally.
- Compile-on-definition consumers no longer need custom recursive
  `buildProposition(...)` implementations.

### Upgrade Notes

- If you currently convert persisted rule arrays/JSON/YAML into `Rule`
  instances with custom mapping code, prefer `RuleCompiler` to reduce
  duplication and drift.

## [4.0.0] - 2026-02-11

### Breaking Changes (Consumer Impact)

- **Read the migration guide first**: see [UPGRADE.md](UPGRADE.md).
- **`RuleEvaluator` construction changed**:
  `RuleEvaluator::createFrom*()` was replaced by non-throwing
  `RuleEvaluator::compileFrom*()` methods.
- **Evaluation return types changed**:
  `RuleEvaluator::evaluateFrom*()` now returns `RuleEvaluatorReport`
  instead of `bool`.
- **Execution return types changed**:
  `Rule::execute()` now returns `RuleExecutionResult`, and
  `RuleSet::executeRules()` / `executeForwardChaining()` now return
  `RuleSetExecutionReport`.
- **Explicit rule IDs are required**:
  `RuleBuilder::create(...)` and DSL `parse(...)` APIs now require a rule ID;
  `RuleSet` enforces non-empty unique IDs.
- **Reference semantics changed for rule definitions**:
  string values are literals by default; context references must use explicit
  `@path.to.value` syntax.
- **Runtime requirement changed**:
  minimum PHP version is now `8.5`.

### Changed

- `Rule::execute()` now returns a `RuleExecutionResult` object that includes
  match and action execution metadata.
- `RuleSet::executeRules()` and `RuleSet::executeForwardChaining()` now return
  `RuleSetExecutionReport` objects instead of scalar counts.
- `RuleEvaluator::evaluateFrom*()` methods now return
  `RuleEvaluatorReport` objects instead of plain booleans.
- Rule actions are now context-aware by contract and must accept a
  `Context` argument.
- `RuleSet` now requires non-empty, unique rule IDs for all managed rules.

### Migration Notes

- Replace boolean/count result assumptions with report accessors:
  - `Rule::execute($context)->matched`
  - `RuleSet::executeRules($context)->getActionExecutionCount()`
  - `RuleSet::executeForwardChaining($context)->getCycleCount()`
  - `RuleEvaluator::evaluateFromArray($values)->getResult()`
- Update action callbacks from `fn () => ...` to
  `fn (Context $context): void => ...`.
- Ensure every rule added to a `RuleSet` has a stable unique ID.

## [2.0.0] - 2025-10-15

### Added

- **DSL Support**: Six new Domain-Specific Languages for rule definition
  - Wirefilter-style DSL for Cloudflare-like filtering syntax
  - SQL WHERE clause DSL for SQL-familiar syntax
  - MongoDB Query DSL for NoSQL query expressions
  - GraphQL Filter DSL for GraphQL-style filtering
  - LDAP Filter DSL for directory service queries
  - JMESPath DSL for JSON path expressions
  - Natural Language DSL for human-readable rule definitions
- **DSL Infrastructure**
  - `src/DSL/Wirefilter/*` - Complete Wirefilter parser, compiler, and operator registry
  - `src/DSL/SQL/*` - SQL WHERE lexer, parser, and compiler with AST nodes
  - `src/DSL/MongoDB/*` - MongoDB query compiler supporting 50+ operators
  - `src/DSL/GraphQL/*` - GraphQL filter parser with type system support
  - `src/DSL/LDAP/*` - LDAP filter lexer, parser, and compiler
  - `src/DSL/JMESPath/*` - JMESPath adapter and proposition system
  - `src/DSL/Natural/*` - Natural language parser and compiler
- **Documentation**
  - Architecture Decision Records (ADRs) for each DSL in `adr/` directory
  - Comprehensive cookbook guides for each DSL in `cookbook/` directory
  - DSL feature matrix comparison in `docs/dsl-feature-matrix.md`
  - Quick reference guide in `cookbook/quick-reference.md`
  - DSL design overview in `adr/DSL_DESIGN.md`
- **Development Infrastructure**
  - Docker Compose setup for PHP 8.4 development
  - Makefile for common development tasks
  - GitHub Actions quality assurance workflow
  - Issue and PR templates
- **Project Governance**
  - Code of Conduct
  - Security policy
  - Enhanced contributing guidelines

### Changed

- **Core Engine Improvements**
  - Enhanced `Context` class with better variable resolution
  - Improved `RuleEvaluator` with extended error handling
  - Refactored `Variable` and `VariableProperty` classes for better DSL support
  - Updated all operators with improved type handling and edge case coverage
- **Code Quality**
  - Updated PHP CS Fixer configuration
  - Enhanced Rector rules for PHP 8.4
  - Comprehensive test coverage for all DSLs (23,000+ new lines of tests)
- **Dependencies**
  - Updated `composer.json` with DSL-related dependencies
  - PHP 8.4+ requirement

### Removed

- Deprecated helper functions in `src/helpers.php`
- Legacy GitHub workflow files (`github/workflows/*`)
- Old test configuration (`peck.json`)
- TODO.md file (replaced with ADRs and structured documentation)

### Fixed

- Multiple edge cases in comparison operators
- Date operator handling for various date formats
- Set operation edge cases and type coercion
- String operator case sensitivity issues
- Mathematical operator precision handling

---

## [1.0.0] - Initial Release

### Added

- Core rule engine with proposition-based evaluation
- 50+ built-in operators across multiple categories:
  - Comparison operators (equals, greater than, less than, etc.)
  - Mathematical operators (addition, subtraction, multiplication, etc.)
  - Logical operators (AND, OR, NOT, XOR, etc.)
  - String operators (contains, starts with, ends with, matches, etc.)
  - Set operators (union, intersect, complement, etc.)
  - Date operators (before, after, between dates)
  - Type operators (is array, is string, is numeric, etc.)
- Fluent rule builder API
- Context system for variable evaluation
- Custom operator support
- Comprehensive test suite

[4.0.0]: https://git.cline.sh/faustbrian/ruler/compare/2.0.0...4.0.0
[4.1.0]: https://git.cline.sh/faustbrian/ruler/compare/4.0.0...4.1.0
[4.3.0]: https://git.cline.sh/faustbrian/ruler/compare/4.1.0...4.3.0
[2.0.0]: https://git.cline.sh/faustbrian/ruler/compare/1.0.0...2.0.0
[1.0.0]: https://git.cline.sh/faustbrian/ruler/releases/tag/1.0.0
