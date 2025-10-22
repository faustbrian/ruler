<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Builder;

use ArrayAccess;
use Cline\Ruler\Core\Operator;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Operators\Mathematical\Ceil;
use Cline\Ruler\Operators\Set\ContainsSubset;
use Cline\Ruler\Operators\Mathematical\Division;
use Cline\Ruler\Operators\Set\DoesNotContainSubset;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\EndsWithInsensitive;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Mathematical\Exponentiate;
use Cline\Ruler\Operators\Mathematical\Floor;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Mathematical\Max;
use Cline\Ruler\Operators\Mathematical\Min;
use Cline\Ruler\Operators\Mathematical\Modulo;
use Cline\Ruler\Operators\Mathematical\Multiplication;
use Cline\Ruler\Operators\Mathematical\Negation;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotSameAs;
use Cline\Ruler\Operators\Comparison\SameAs;
use Cline\Ruler\Operators\Set\SetContains;
use Cline\Ruler\Operators\Set\SetDoesNotContain;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StartsWithInsensitive;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringContainsInsensitive;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Operators\Mathematical\Subtraction;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Variables\Variable as BaseVariable;
use Cline\Ruler\Variables\VariableOperand;
use LogicException;
use ReflectionClass;

use function array_key_exists;
use function array_map;
use function array_unshift;

/**
 * Enhanced Variable with fluent interface for building rule conditions.
 *
 * Variables are placeholders in propositions and comparison operators that get
 * resolved to concrete values during evaluation. This RuleBuilder-enhanced version
 * extends the base Variable class with a fluent interface for creating variable
 * properties, operators, and rules without verbose object instantiation.
 *
 * The ArrayAccess implementation enables array-style property access:
 * `$variable['propertyName']` creates or retrieves a VariableProperty.
 *
 * Magic method __call() allows dynamic operator registration and invocation,
 * enabling custom operators to be called as methods on Variable instances.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @implements ArrayAccess<string, mixed>
 */
class Variable extends BaseVariable implements ArrayAccess
{
    /**
     * Cache of VariableProperty instances created for this variable.
     *
     * @var array<string, VariableProperty>
     */
    private array $properties = [];

    /**
     * Create a new RuleBuilder Variable instance.
     *
     * @param RuleBuilder $ruleBuilder Reference to the RuleBuilder instance that manages this
     *                                 variable and provides access to registered operators.
     *                                 Used to resolve dynamic operator calls via __call() magic
     *                                 method and to enable nested variable property access.
     * @param null|string $name        The variable name used for context lookups. When null,
     *                                 the variable can only resolve from its default value.
     * @param mixed       $value       default value returned when the variable name is not
     *                                 found in the evaluation context
     */
    public function __construct(
        private readonly mixed $ruleBuilder,
        ?string $name = null,
        mixed $value = null,
    )
    {
        parent::__construct($name, $value);
    }

    /**
     * Dynamically invoke operators registered with the RuleBuilder.
     *
     * This magic method enables calling registered operators as methods on the variable.
     * The operator is looked up in the RuleBuilder's registered namespaces and instantiated
     * with this variable as the first argument, followed by any provided arguments.
     *
     * All non-Variable arguments are automatically wrapped in Variable instances before
     * being passed to the operator constructor.
     *
     * @param string $name the operator method name to invoke (case-sensitive)
     * @param array<mixed>  $args arguments to pass to the operator constructor
     *
     * @throws LogicException when the requested operator is not registered in any namespace
     *
     * @return Operator|self returns the created Operator, or a new Variable wrapping
     *                       a VariableOperand to enable method chaining
     *
     * @see RuleBuilder::registerOperatorNamespace
     * @see RuleBuilder::findOperator
     */
    public function __call(string $name, array $args)
    {
        /** @var class-string $operatorClass */
        $operatorClass = $this->ruleBuilder->findOperator($name);
        $reflection = new ReflectionClass($operatorClass);
        $args = array_map([$this, 'asVariable'], $args);
        array_unshift($args, $this);

        $op = $reflection->newInstanceArgs($args);

        if ($op instanceof VariableOperand) {
            \assert($op instanceof VariableOperator);
            return $this->wrap($op);
        }

        \assert($op instanceof Operator);
        return $op;
    }

    /**
     * Retrieve the RuleBuilder instance that manages this variable.
     *
     * @return RuleBuilder the RuleBuilder instance
     */
    public function getRuleBuilder(): RuleBuilder
    {
        return $this->ruleBuilder;
    }

    /**
     * Get or create a VariableProperty for accessing nested data.
     *
     * VariableProperties enable accessing methods, array indexes, and object properties
     * of this variable's resolved value. Properties are cached to ensure the same
     * VariableProperty instance is returned for repeated accesses.
     *
     * @param  string           $name  the property name to access on the variable's value
     * @param  mixed            $value optional default value if the property doesn't exist
     * @return VariableProperty the cached or newly created VariableProperty instance
     */
    public function getProperty(string $name, $value = null): VariableProperty
    {
        if (!array_key_exists($name, $this->properties)) {
            $this->properties[$name] = new VariableProperty($this, $name, $value);
        }

        return $this->properties[$name];
    }

    /**
     * Check whether a VariableProperty has been defined for this variable.
     *
     * Part of the ArrayAccess interface implementation for fluent property access.
     *
     * @param  string $name the property name to check
     * @return bool   true if the property has been created, false otherwise
     */
    public function offsetExists($name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Get or create a VariableProperty using array access syntax.
     *
     * Part of the ArrayAccess interface implementation. Enables accessing properties
     * using array syntax: `$variable['propertyName']`.
     *
     * @param  string           $name the property name to retrieve
     * @return VariableProperty the cached or newly created VariableProperty instance
     *
     * @see getProperty
     */
    public function offsetGet($name): VariableProperty
    {
        return $this->getProperty($name);
    }

    /**
     * Set the default value of a VariableProperty using array access syntax.
     *
     * Part of the ArrayAccess interface implementation. Enables setting property
     * default values using array syntax: `$variable['propertyName'] = $value`.
     *
     * @param string $name  the property name to set
     * @param mixed  $value the default value for the property
     *
     * @see setValue
     */
    public function offsetSet($name, $value): void
    {
        $this->getProperty($name)->setValue($value);
    }

    /**
     * Remove a VariableProperty reference from the cache.
     *
     * Part of the ArrayAccess interface implementation. Enables removing properties
     * using array syntax: `unset($variable['propertyName'])`.
     *
     * @param string $name the property name to remove
     */
    public function offsetUnset($name): void
    {
        unset($this->properties[$name]);
    }

    /**
     * Create a string contains comparison operator.
     *
     * @param  mixed          $variable the value or Variable to search for within this variable's string value
     * @return StringContains the comparison operator instance
     */
    public function stringContains($variable): StringContains
    {
        return new StringContains($this, $this->asVariable($variable));
    }

    /**
     * Create a string does not contain comparison operator.
     *
     * @param  mixed                $variable the value or Variable that should not be found within this variable's string value
     * @return StringDoesNotContain the comparison operator instance
     */
    public function stringDoesNotContain($variable): StringDoesNotContain
    {
        return new StringDoesNotContain($this, $this->asVariable($variable));
    }

    /**
     * Create a case-insensitive string contains comparison operator.
     *
     * @param  mixed                     $variable the value or Variable to search for (case-insensitive) within this variable's string value
     * @return StringContainsInsensitive the comparison operator instance
     */
    public function stringContainsInsensitive($variable): StringContainsInsensitive
    {
        return new StringContainsInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Create a greater than comparison operator.
     *
     * @param  mixed       $variable the value or Variable to compare against
     * @return GreaterThan the comparison operator instance
     */
    public function greaterThan($variable): GreaterThan
    {
        return new GreaterThan($this, $this->asVariable($variable));
    }

    /**
     * Create a greater than or equal to comparison operator.
     *
     * @param  mixed                $variable the value or Variable to compare against
     * @return GreaterThanOrEqualTo the comparison operator instance
     */
    public function greaterThanOrEqualTo($variable): GreaterThanOrEqualTo
    {
        return new GreaterThanOrEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create a less than comparison operator.
     *
     * @param  mixed    $variable the value or Variable to compare against
     * @return LessThan the comparison operator instance
     */
    public function lessThan($variable): LessThan
    {
        return new LessThan($this, $this->asVariable($variable));
    }

    /**
     * Create a less than or equal to comparison operator.
     *
     * @param  mixed             $variable the value or Variable to compare against
     * @return LessThanOrEqualTo the comparison operator instance
     */
    public function lessThanOrEqualTo($variable): LessThanOrEqualTo
    {
        return new LessThanOrEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create an equal to comparison operator using loose equality (==).
     *
     * @param  mixed   $variable the value or Variable to compare against
     * @return EqualTo the comparison operator instance
     */
    public function equalTo($variable): EqualTo
    {
        return new EqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create a not equal to comparison operator using loose inequality (!=).
     *
     * @param  mixed      $variable the value or Variable to compare against
     * @return NotEqualTo the comparison operator instance
     */
    public function notEqualTo($variable): NotEqualTo
    {
        return new NotEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create a same as comparison operator using strict equality (===).
     *
     * @param  mixed  $variable the value or Variable to compare against
     * @return SameAs the comparison operator instance
     */
    public function sameAs($variable): SameAs
    {
        return new SameAs($this, $this->asVariable($variable));
    }

    /**
     * Create a not same as comparison operator using strict inequality (!==).
     *
     * @param  mixed     $variable the value or Variable to compare against
     * @return NotSameAs the comparison operator instance
     */
    public function notSameAs($variable): NotSameAs
    {
        return new NotSameAs($this, $this->asVariable($variable));
    }

    /**
     * Create a set union operator combining this set with one or more other sets.
     *
     * @param  mixed ...$variables One or more sets to union with this variable's set value.
     * @return self  a new Variable containing the union operation for method chaining
     */
    public function union(...$variables): self
    {
        return $this->applySetOperator('Union', $variables);
    }

    /**
     * Create a set intersection operator finding common elements between sets.
     *
     * @param  mixed ...$variables One or more sets to intersect with this variable's set value.
     * @return self  a new Variable containing the intersection operation for method chaining
     */
    public function intersect(...$variables): self
    {
        return $this->applySetOperator('Intersect', $variables);
    }

    /**
     * Create a set complement operator finding elements in this set but not in others.
     *
     * @param  mixed ...$variables One or more sets to exclude from this variable's set value.
     * @return self  a new Variable containing the complement operation for method chaining
     */
    public function complement(...$variables): self
    {
        return $this->applySetOperator('Complement', $variables);
    }

    /**
     * Create a symmetric difference operator finding elements in either set but not both.
     *
     * @param  mixed ...$variables One or more sets to compare against this variable's set value.
     * @return self  a new Variable containing the symmetric difference operation for method chaining
     */
    public function symmetricDifference(...$variables): self
    {
        return $this->applySetOperator('SymmetricDifference', $variables);
    }

    /**
     * Create a minimum value operator for numeric sets.
     *
     * @return self a new Variable containing the min operation for method chaining
     */
    public function min(): self
    {
        return $this->wrap(
            new Min($this),
        );
    }

    /**
     * Create a maximum value operator for numeric sets.
     *
     * @return self a new Variable containing the max operation for method chaining
     */
    public function max(): self
    {
        return $this->wrap(
            new Max($this),
        );
    }

    /**
     * Create a contains subset comparison operator.
     *
     * @param  mixed          $variable the subset to check for within this variable's set value
     * @return ContainsSubset the comparison operator instance
     */
    public function containsSubset($variable): ContainsSubset
    {
        return new ContainsSubset($this, $this->asVariable($variable));
    }

    /**
     * Create a does not contain subset comparison operator.
     *
     * @param  mixed                $variable the subset that should not be found within this variable's set value
     * @return DoesNotContainSubset the comparison operator instance
     */
    public function doesNotContainSubset($variable): DoesNotContainSubset
    {
        return new DoesNotContainSubset($this, $this->asVariable($variable));
    }

    /**
     * Create a set contains element comparison operator.
     *
     * @param  mixed       $variable the element to check for within this variable's set value
     * @return SetContains the comparison operator instance
     */
    public function setContains($variable): SetContains
    {
        return new SetContains($this, $this->asVariable($variable));
    }

    /**
     * Create a set does not contain element comparison operator.
     *
     * @param  mixed             $variable the element that should not be found within this variable's set value
     * @return SetDoesNotContain the comparison operator instance
     */
    public function setDoesNotContain($variable): SetDoesNotContain
    {
        return new SetDoesNotContain($this, $this->asVariable($variable));
    }

    /**
     * Create an addition arithmetic operator.
     *
     * @param  mixed $variable the value or Variable to add to this variable's value
     * @return self  a new Variable containing the addition operation for method chaining
     */
    public function add($variable): self
    {
        return $this->wrap(
            new Addition($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a division arithmetic operator.
     *
     * @param  mixed $variable the value or Variable to divide this variable's value by
     * @return self  a new Variable containing the division operation for method chaining
     */
    public function divide($variable): self
    {
        return $this->wrap(
            new Division($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a modulo arithmetic operator.
     *
     * @param  mixed $variable the value or Variable to calculate modulo with this variable's value
     * @return self  a new Variable containing the modulo operation for method chaining
     */
    public function modulo($variable): self
    {
        return $this->wrap(
            new Modulo($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a multiplication arithmetic operator.
     *
     * @param  mixed $variable the value or Variable to multiply with this variable's value
     * @return self  a new Variable containing the multiplication operation for method chaining
     */
    public function multiply($variable): self
    {
        return $this->wrap(
            new Multiplication($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a subtraction arithmetic operator.
     *
     * @param  mixed $variable the value or Variable to subtract from this variable's value
     * @return self  a new Variable containing the subtraction operation for method chaining
     */
    public function subtract($variable): self
    {
        return $this->wrap(
            new Subtraction($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a negation arithmetic operator.
     *
     * @return self a new Variable containing the negation operation for method chaining
     */
    public function negate(): self
    {
        return $this->wrap(
            new Negation($this),
        );
    }

    /**
     * Create a ceiling rounding operator.
     *
     * @return self a new Variable containing the ceiling operation for method chaining
     */
    public function ceil(): self
    {
        return $this->wrap(
            new Ceil($this),
        );
    }

    /**
     * Create a floor rounding operator.
     *
     * @return self a new Variable containing the floor operation for method chaining
     */
    public function floor(): self
    {
        return $this->wrap(
            new Floor($this),
        );
    }

    /**
     * Create an exponentiation arithmetic operator.
     *
     * @param  mixed $variable the exponent value or Variable to raise this variable's value to
     * @return self  a new Variable containing the exponentiation operation for method chaining
     */
    public function exponentiate($variable): self
    {
        return $this->wrap(
            new Exponentiate($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a string ends with comparison operator.
     *
     * @param  mixed    $variable the suffix value or Variable to check for at the end of this variable's string value
     * @return EndsWith the comparison operator instance
     */
    public function endsWith($variable): EndsWith
    {
        return new EndsWith($this, $this->asVariable($variable));
    }

    /**
     * Create a case-insensitive string ends with comparison operator.
     *
     * @param  mixed               $variable the suffix value or Variable to check for (case-insensitive) at the end of this variable's string value
     * @return EndsWithInsensitive the comparison operator instance
     */
    public function endsWithInsensitive($variable): EndsWithInsensitive
    {
        return new EndsWithInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Create a string starts with comparison operator.
     *
     * @param  mixed      $variable the prefix value or Variable to check for at the start of this variable's string value
     * @return StartsWith the comparison operator instance
     */
    public function startsWith($variable): StartsWith
    {
        return new StartsWith($this, $this->asVariable($variable));
    }

    /**
     * Create a case-insensitive string starts with comparison operator.
     *
     * @param  mixed                 $variable the prefix value or Variable to check for (case-insensitive) at the start of this variable's string value
     * @return StartsWithInsensitive the comparison operator instance
     */
    public function startsWithInsensitive($variable): StartsWithInsensitive
    {
        return new StartsWithInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Convert a value to a Variable instance if it isn't already.
     *
     * @param  mixed        $variable a BaseVariable instance or any value to wrap in a Variable
     * @return BaseVariable the existing Variable instance or a new Variable wrapping the value
     */
    private function asVariable($variable): BaseVariable
    {
        return ($variable instanceof BaseVariable) ? $variable : new BaseVariable(null, $variable);
    }

    /**
     * Apply a set operator by dynamically instantiating it with reflection.
     *
     * @param  string               $name the operator class name (without namespace)
     * @param  array<int|string, mixed> $args the arguments to pass to the operator constructor
     * @return self                 a new Variable wrapping the operator for method chaining
     */
    private function applySetOperator(string $name, array $args): self
    {
        /** @var class-string $operatorClass */
        $operatorClass = '\\Cline\\Ruler\\Operators\\Set\\'.$name;
        $reflection = new ReflectionClass($operatorClass);
        array_unshift($args, $this);

        $op = $reflection->newInstanceArgs($args);
        \assert($op instanceof VariableOperator);
        return $this->wrap($op);
    }

    /**
     * Wrap a VariableOperator in a new Variable instance for method chaining.
     *
     * @param  VariableOperator $op the operator to wrap in a Variable
     * @return self             a new Variable instance with the operator as its value
     */
    private function wrap(VariableOperator $op): self
    {
        return new self($this->ruleBuilder, null, $op);
    }
}
