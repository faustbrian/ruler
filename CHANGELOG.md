# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[2.0.0]: https://git.cline.sh/faustbrian/ruler/compare/1.0.0...2.0.0
[1.0.0]: https://git.cline.sh/faustbrian/ruler/releases/tag/1.0.0
