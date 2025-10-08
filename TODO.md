# Directory Restructure TODO

## Phase 1: Create New Directory Structure

- [ ] Create `src/Core/` directory
- [ ] Create `src/Operators/` directory with subdirectories:
  - [ ] `src/Operators/Comparison/`
  - [ ] `src/Operators/Logical/`
  - [ ] `src/Operators/Mathematical/`
  - [ ] `src/Operators/String/`
  - [ ] `src/Operators/Set/`
  - [ ] `src/Operators/Type/`
  - [ ] `src/Operators/Date/`
- [ ] Create `src/Variables/` directory
- [ ] Create `src/Values/` directory
- [ ] Create `src/Builder/` directory
- [ ] Create `src/Enums/` directory

## Phase 2: Move Core Domain Classes

- [ ] Move `src/Rule.php` → `src/Core/Rule.php`
- [ ] Move `src/RuleSet.php` → `src/Core/RuleSet.php`
- [ ] Move `src/Context.php` → `src/Core/Context.php`
- [ ] Move `src/Proposition.php` → `src/Core/Proposition.php`
- [ ] Move `src/RuleEvaluator.php` → `src/Core/RuleEvaluator.php`
- [ ] Move `src/Operator.php` → `src/Core/Operator.php`

## Phase 3: Move Variable-Related Classes

- [ ] Move `src/Variable.php` → `src/Variables/Variable.php`
- [ ] Move `src/VariableOperand.php` → `src/Variables/VariableOperand.php`
- [ ] Move `src/VariableProperty.php` → `src/Variables/VariableProperty.php`
- [ ] Move `src/RuleBuilder/Variable.php` → `src/Variables/BuilderVariable.php` (rename to avoid conflict)
- [ ] Move `src/RuleBuilder/VariableProperty.php` → `src/Variables/BuilderVariableProperty.php` (rename)
- [ ] Move `src/RuleBuilder/VariablePropertyTrait.php` → `src/Variables/BuilderVariablePropertyTrait.php` (rename)

## Phase 4: Move Value Classes

- [ ] Move `src/Value.php` → `src/Values/Value.php`
- [ ] Move `src/Set.php` → `src/Values/Set.php`

## Phase 5: Move Builder Classes

- [ ] Move `src/RuleBuilder.php` → `src/Builder/RuleBuilder.php`

## Phase 6: Move Enum Classes

- [ ] Move `src/OperandCardinality.php` → `src/Enums/OperandCardinality.php`

## Phase 7: Organize Operators by Category

### Comparison Operators
- [ ] Move `src/Operator/EqualTo.php` → `src/Operators/Comparison/EqualTo.php`
- [ ] Move `src/Operator/NotEqualTo.php` → `src/Operators/Comparison/NotEqualTo.php`
- [ ] Move `src/Operator/GreaterThan.php` → `src/Operators/Comparison/GreaterThan.php`
- [ ] Move `src/Operator/GreaterThanOrEqualTo.php` → `src/Operators/Comparison/GreaterThanOrEqualTo.php`
- [ ] Move `src/Operator/LessThan.php` → `src/Operators/Comparison/LessThan.php`
- [ ] Move `src/Operator/LessThanOrEqualTo.php` → `src/Operators/Comparison/LessThanOrEqualTo.php`
- [ ] Move `src/Operator/SameAs.php` → `src/Operators/Comparison/SameAs.php`
- [ ] Move `src/Operator/NotSameAs.php` → `src/Operators/Comparison/NotSameAs.php`
- [ ] Move `src/Operator/In.php` → `src/Operators/Comparison/In.php`
- [ ] Move `src/Operator/NotIn.php` → `src/Operators/Comparison/NotIn.php`
- [ ] Move `src/Operator/Between.php` → `src/Operators/Comparison/Between.php`

### Logical Operators
- [ ] Move `src/Operator/LogicalOperator.php` → `src/Operators/Logical/LogicalOperator.php`
- [ ] Move `src/Operator/LogicalAnd.php` → `src/Operators/Logical/LogicalAnd.php`
- [ ] Move `src/Operator/LogicalOr.php` → `src/Operators/Logical/LogicalOr.php`
- [ ] Move `src/Operator/LogicalNot.php` → `src/Operators/Logical/LogicalNot.php`
- [ ] Move `src/Operator/LogicalXor.php` → `src/Operators/Logical/LogicalXor.php`
- [ ] Move `src/Operator/LogicalNand.php` → `src/Operators/Logical/LogicalNand.php`
- [ ] Move `src/Operator/LogicalNor.php` → `src/Operators/Logical/LogicalNor.php`

### Mathematical Operators
- [ ] Move `src/Operator/Addition.php` → `src/Operators/Mathematical/Addition.php`
- [ ] Move `src/Operator/Subtraction.php` → `src/Operators/Mathematical/Subtraction.php`
- [ ] Move `src/Operator/Multiplication.php` → `src/Operators/Mathematical/Multiplication.php`
- [ ] Move `src/Operator/Division.php` → `src/Operators/Mathematical/Division.php`
- [ ] Move `src/Operator/Modulo.php` → `src/Operators/Mathematical/Modulo.php`
- [ ] Move `src/Operator/Exponentiate.php` → `src/Operators/Mathematical/Exponentiate.php`
- [ ] Move `src/Operator/Abs.php` → `src/Operators/Mathematical/Abs.php`
- [ ] Move `src/Operator/Negation.php` → `src/Operators/Mathematical/Negation.php`
- [ ] Move `src/Operator/Ceil.php` → `src/Operators/Mathematical/Ceil.php`
- [ ] Move `src/Operator/Floor.php` → `src/Operators/Mathematical/Floor.php`
- [ ] Move `src/Operator/Round.php` → `src/Operators/Mathematical/Round.php`
- [ ] Move `src/Operator/Min.php` → `src/Operators/Mathematical/Min.php`
- [ ] Move `src/Operator/Max.php` → `src/Operators/Mathematical/Max.php`

### String Operators
- [ ] Move `src/Operator/StartsWith.php` → `src/Operators/String/StartsWith.php`
- [ ] Move `src/Operator/StartsWithInsensitive.php` → `src/Operators/String/StartsWithInsensitive.php`
- [ ] Move `src/Operator/EndsWith.php` → `src/Operators/String/EndsWith.php`
- [ ] Move `src/Operator/EndsWithInsensitive.php` → `src/Operators/String/EndsWithInsensitive.php`
- [ ] Move `src/Operator/StringContains.php` → `src/Operators/String/StringContains.php`
- [ ] Move `src/Operator/StringContainsInsensitive.php` → `src/Operators/String/StringContainsInsensitive.php`
- [ ] Move `src/Operator/StringDoesNotContain.php` → `src/Operators/String/StringDoesNotContain.php`
- [ ] Move `src/Operator/StringDoesNotContainInsensitive.php` → `src/Operators/String/StringDoesNotContainInsensitive.php`
- [ ] Move `src/Operator/StringLength.php` → `src/Operators/String/StringLength.php`
- [ ] Move `src/Operator/Matches.php` → `src/Operators/String/Matches.php`
- [ ] Move `src/Operator/DoesNotMatch.php` → `src/Operators/String/DoesNotMatch.php`

### Set Operators
- [ ] Move `src/Operator/Union.php` → `src/Operators/Set/Union.php`
- [ ] Move `src/Operator/Intersect.php` → `src/Operators/Set/Intersect.php`
- [ ] Move `src/Operator/Complement.php` → `src/Operators/Set/Complement.php`
- [ ] Move `src/Operator/SymmetricDifference.php` → `src/Operators/Set/SymmetricDifference.php`
- [ ] Move `src/Operator/SetContains.php` → `src/Operators/Set/SetContains.php`
- [ ] Move `src/Operator/SetDoesNotContain.php` → `src/Operators/Set/SetDoesNotContain.php`
- [ ] Move `src/Operator/ContainsSubset.php` → `src/Operators/Set/ContainsSubset.php`
- [ ] Move `src/Operator/DoesNotContainSubset.php` → `src/Operators/Set/DoesNotContainSubset.php`

### Type Operators
- [ ] Move `src/Operator/IsNull.php` → `src/Operators/Type/IsNull.php`
- [ ] Move `src/Operator/IsBoolean.php` → `src/Operators/Type/IsBoolean.php`
- [ ] Move `src/Operator/IsNumeric.php` → `src/Operators/Type/IsNumeric.php`
- [ ] Move `src/Operator/IsString.php` → `src/Operators/Type/IsString.php`
- [ ] Move `src/Operator/IsArray.php` → `src/Operators/Type/IsArray.php`
- [ ] Move `src/Operator/IsEmpty.php` → `src/Operators/Type/IsEmpty.php`
- [ ] Move `src/Operator/ArrayCount.php` → `src/Operators/Type/ArrayCount.php`

### Date Operators
- [ ] Move `src/Operator/Before.php` → `src/Operators/Date/Before.php`
- [ ] Move `src/Operator/After.php` → `src/Operators/Date/After.php`
- [ ] Move `src/Operator/IsBetweenDates.php` → `src/Operators/Date/IsBetweenDates.php`

### Base Operator Classes
- [ ] Move `src/Operator/PropositionOperator.php` → `src/Operators/PropositionOperator.php`
- [ ] Move `src/Operator/VariableOperator.php` → `src/Operators/VariableOperator.php`

## Phase 8: Rename Helper File

- [ ] Rename `src/functions.php` → `src/helpers.php`

## Phase 9: Update Namespaces

- [ ] Update all namespaces in moved files to match new directory structure
- [ ] Update all `use` statements in files that reference moved classes

## Phase 10: Update Autoloader

- [ ] Update `composer.json` if PSR-4 paths need adjustment
- [ ] Run `composer dump-autoload`

## Phase 11: Clean Up

- [ ] Remove empty `src/Operator/` directory
- [ ] Remove empty `src/RuleBuilder/` directory

## Phase 12: Testing

- [ ] Run full test suite to ensure nothing broke
- [ ] Fix any failing tests due to namespace changes
