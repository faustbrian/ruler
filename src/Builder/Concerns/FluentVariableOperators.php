<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Builder\Concerns;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Core\Operator;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotSameAs;
use Cline\Ruler\Operators\Comparison\SameAs;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Operators\Mathematical\Ceil;
use Cline\Ruler\Operators\Mathematical\Division;
use Cline\Ruler\Operators\Mathematical\Exponentiate;
use Cline\Ruler\Operators\Mathematical\Floor;
use Cline\Ruler\Operators\Mathematical\Max;
use Cline\Ruler\Operators\Mathematical\Min;
use Cline\Ruler\Operators\Mathematical\Modulo;
use Cline\Ruler\Operators\Mathematical\Multiplication;
use Cline\Ruler\Operators\Mathematical\Negation;
use Cline\Ruler\Operators\Mathematical\Subtraction;
use Cline\Ruler\Operators\Set\ContainsSubset;
use Cline\Ruler\Operators\Set\DoesNotContainSubset;
use Cline\Ruler\Operators\Set\SetContains;
use Cline\Ruler\Operators\Set\SetDoesNotContain;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\EndsWithInsensitive;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StartsWithInsensitive;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringContainsInsensitive;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\Variable as BaseVariable;
use Cline\Ruler\Variables\VariableOperand;
use LogicException;
use ReflectionClass;

use function array_map;
use function array_unshift;
use function assert;

/**
 * Fluent interface methods for building rule conditions.
 *
 * This trait provides the operator methods used by Variable and VariableProperty
 * to create comparison, mathematical, set, and string operators through a fluent
 * interface. Classes using this trait must implement getRuleBuilder() to provide
 * access to the RuleBuilder for dynamic operator resolution.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait FluentVariableOperators
{
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
     * @see RuleBuilder::registerOperatorNamespace
     * @see RuleBuilder::findOperator
     * @param  string            $name the operator method name to invoke (case-sensitive)
     * @param  array<mixed>      $args arguments to pass to the operator constructor
     * @throws LogicException    when the requested operator is not registered in any namespace
     * @return Operator|Variable returns the created Operator, or a new Variable wrapping
     *                           a VariableOperand to enable method chaining
     */
    public function __call(string $name, array $args)
    {
        /** @var class-string $operatorClass */
        $operatorClass = $this->getRuleBuilder()->findOperator($name);
        $reflection = new ReflectionClass($operatorClass);
        $args = array_map([$this, 'asVariable'], $args);
        array_unshift($args, $this);

        $op = $reflection->newInstanceArgs($args);

        if ($op instanceof VariableOperand) {
            assert($op instanceof VariableOperator);

            return $this->wrapOperator($op);
        }

        assert($op instanceof Operator);

        return $op;
    }

    /**
     * Create a string contains comparison operator.
     *
     * @param  mixed          $variable the value or Variable to search for within this variable's string value
     * @return StringContains the comparison operator instance
     */
    public function stringContains(mixed $variable): StringContains
    {
        return new StringContains($this, $this->asVariable($variable));
    }

    /**
     * Create a string does not contain comparison operator.
     *
     * @param  mixed                $variable the value or Variable that should not be found within this variable's string value
     * @return StringDoesNotContain the comparison operator instance
     */
    public function stringDoesNotContain(mixed $variable): StringDoesNotContain
    {
        return new StringDoesNotContain($this, $this->asVariable($variable));
    }

    /**
     * Create a case-insensitive string contains comparison operator.
     *
     * @param  mixed                     $variable the value or Variable to search for (case-insensitive) within this variable's string value
     * @return StringContainsInsensitive the comparison operator instance
     */
    public function stringContainsInsensitive(mixed $variable): StringContainsInsensitive
    {
        return new StringContainsInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Create a greater than comparison operator.
     *
     * @param  mixed       $variable the value or Variable to compare against
     * @return GreaterThan the comparison operator instance
     */
    public function greaterThan(mixed $variable): GreaterThan
    {
        return new GreaterThan($this, $this->asVariable($variable));
    }

    /**
     * Create a greater than or equal to comparison operator.
     *
     * @param  mixed                $variable the value or Variable to compare against
     * @return GreaterThanOrEqualTo the comparison operator instance
     */
    public function greaterThanOrEqualTo(mixed $variable): GreaterThanOrEqualTo
    {
        return new GreaterThanOrEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create a less than comparison operator.
     *
     * @param  mixed    $variable the value or Variable to compare against
     * @return LessThan the comparison operator instance
     */
    public function lessThan(mixed $variable): LessThan
    {
        return new LessThan($this, $this->asVariable($variable));
    }

    /**
     * Create a less than or equal to comparison operator.
     *
     * @param  mixed             $variable the value or Variable to compare against
     * @return LessThanOrEqualTo the comparison operator instance
     */
    public function lessThanOrEqualTo(mixed $variable): LessThanOrEqualTo
    {
        return new LessThanOrEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create an equal to comparison operator using loose equality (==).
     *
     * @param  mixed   $variable the value or Variable to compare against
     * @return EqualTo the comparison operator instance
     */
    public function equalTo(mixed $variable): EqualTo
    {
        return new EqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create a not equal to comparison operator using loose inequality (!=).
     *
     * @param  mixed      $variable the value or Variable to compare against
     * @return NotEqualTo the comparison operator instance
     */
    public function notEqualTo(mixed $variable): NotEqualTo
    {
        return new NotEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Create a same as comparison operator using strict equality (===).
     *
     * @param  mixed  $variable the value or Variable to compare against
     * @return SameAs the comparison operator instance
     */
    public function sameAs(mixed $variable): SameAs
    {
        return new SameAs($this, $this->asVariable($variable));
    }

    /**
     * Create a not same as comparison operator using strict inequality (!==).
     *
     * @param  mixed     $variable the value or Variable to compare against
     * @return NotSameAs the comparison operator instance
     */
    public function notSameAs(mixed $variable): NotSameAs
    {
        return new NotSameAs($this, $this->asVariable($variable));
    }

    /**
     * Create a set union operator combining this set with one or more other sets.
     *
     * @param  mixed    ...$variables One or more sets to union with this variable's set value.
     * @return Variable a new Variable containing the union operation for method chaining
     */
    public function union(...$variables): Variable
    {
        return $this->applySetOperator('Union', $variables);
    }

    /**
     * Create a set intersection operator finding common elements between sets.
     *
     * @param  mixed    ...$variables One or more sets to intersect with this variable's set value.
     * @return Variable a new Variable containing the intersection operation for method chaining
     */
    public function intersect(...$variables): Variable
    {
        return $this->applySetOperator('Intersect', $variables);
    }

    /**
     * Create a set complement operator finding elements in this set but not in others.
     *
     * @param  mixed    ...$variables One or more sets to exclude from this variable's set value.
     * @return Variable a new Variable containing the complement operation for method chaining
     */
    public function complement(...$variables): Variable
    {
        return $this->applySetOperator('Complement', $variables);
    }

    /**
     * Create a symmetric difference operator finding elements in either set but not both.
     *
     * @param  mixed    ...$variables One or more sets to compare against this variable's set value.
     * @return Variable a new Variable containing the symmetric difference operation for method chaining
     */
    public function symmetricDifference(...$variables): Variable
    {
        return $this->applySetOperator('SymmetricDifference', $variables);
    }

    /**
     * Create a minimum value operator for numeric sets.
     *
     * @return Variable a new Variable containing the min operation for method chaining
     */
    public function min(): Variable
    {
        return $this->wrapOperator(
            new Min($this),
        );
    }

    /**
     * Create a maximum value operator for numeric sets.
     *
     * @return Variable a new Variable containing the max operation for method chaining
     */
    public function max(): Variable
    {
        return $this->wrapOperator(
            new Max($this),
        );
    }

    /**
     * Create a contains subset comparison operator.
     *
     * @param  mixed          $variable the subset to check for within this variable's set value
     * @return ContainsSubset the comparison operator instance
     */
    public function containsSubset(mixed $variable): ContainsSubset
    {
        return new ContainsSubset($this, $this->asVariable($variable));
    }

    /**
     * Create a does not contain subset comparison operator.
     *
     * @param  mixed                $variable the subset that should not be found within this variable's set value
     * @return DoesNotContainSubset the comparison operator instance
     */
    public function doesNotContainSubset(mixed $variable): DoesNotContainSubset
    {
        return new DoesNotContainSubset($this, $this->asVariable($variable));
    }

    /**
     * Create a set contains element comparison operator.
     *
     * @param  mixed       $variable the element to check for within this variable's set value
     * @return SetContains the comparison operator instance
     */
    public function setContains(mixed $variable): SetContains
    {
        return new SetContains($this, $this->asVariable($variable));
    }

    /**
     * Create a set does not contain element comparison operator.
     *
     * @param  mixed             $variable the element that should not be found within this variable's set value
     * @return SetDoesNotContain the comparison operator instance
     */
    public function setDoesNotContain(mixed $variable): SetDoesNotContain
    {
        return new SetDoesNotContain($this, $this->asVariable($variable));
    }

    /**
     * Create an addition arithmetic operator.
     *
     * @param  mixed    $variable the value or Variable to add to this variable's value
     * @return Variable a new Variable containing the addition operation for method chaining
     */
    public function add(mixed $variable): Variable
    {
        return $this->wrapOperator(
            new Addition($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a division arithmetic operator.
     *
     * @param  mixed    $variable the value or Variable to divide this variable's value by
     * @return Variable a new Variable containing the division operation for method chaining
     */
    public function divide(mixed $variable): Variable
    {
        return $this->wrapOperator(
            new Division($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a modulo arithmetic operator.
     *
     * @param  mixed    $variable the value or Variable to calculate modulo with this variable's value
     * @return Variable a new Variable containing the modulo operation for method chaining
     */
    public function modulo(mixed $variable): Variable
    {
        return $this->wrapOperator(
            new Modulo($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a multiplication arithmetic operator.
     *
     * @param  mixed    $variable the value or Variable to multiply with this variable's value
     * @return Variable a new Variable containing the multiplication operation for method chaining
     */
    public function multiply(mixed $variable): Variable
    {
        return $this->wrapOperator(
            new Multiplication($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a subtraction arithmetic operator.
     *
     * @param  mixed    $variable the value or Variable to subtract from this variable's value
     * @return Variable a new Variable containing the subtraction operation for method chaining
     */
    public function subtract(mixed $variable): Variable
    {
        return $this->wrapOperator(
            new Subtraction($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a negation arithmetic operator.
     *
     * @return Variable a new Variable containing the negation operation for method chaining
     */
    public function negate(): Variable
    {
        return $this->wrapOperator(
            new Negation($this),
        );
    }

    /**
     * Create a ceiling rounding operator.
     *
     * @return Variable a new Variable containing the ceiling operation for method chaining
     */
    public function ceil(): Variable
    {
        return $this->wrapOperator(
            new Ceil($this),
        );
    }

    /**
     * Create a floor rounding operator.
     *
     * @return Variable a new Variable containing the floor operation for method chaining
     */
    public function floor(): Variable
    {
        return $this->wrapOperator(
            new Floor($this),
        );
    }

    /**
     * Create an exponentiation arithmetic operator.
     *
     * @param  mixed    $variable the exponent value or Variable to raise this variable's value to
     * @return Variable a new Variable containing the exponentiation operation for method chaining
     */
    public function exponentiate(mixed $variable): Variable
    {
        return $this->wrapOperator(
            new Exponentiate($this, $this->asVariable($variable)),
        );
    }

    /**
     * Create a string ends with comparison operator.
     *
     * @param  mixed    $variable the suffix value or Variable to check for at the end of this variable's string value
     * @return EndsWith the comparison operator instance
     */
    public function endsWith(mixed $variable): EndsWith
    {
        return new EndsWith($this, $this->asVariable($variable));
    }

    /**
     * Create a case-insensitive string ends with comparison operator.
     *
     * @param  mixed               $variable the suffix value or Variable to check for (case-insensitive) at the end of this variable's string value
     * @return EndsWithInsensitive the comparison operator instance
     */
    public function endsWithInsensitive(mixed $variable): EndsWithInsensitive
    {
        return new EndsWithInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Create a string starts with comparison operator.
     *
     * @param  mixed      $variable the prefix value or Variable to check for at the start of this variable's string value
     * @return StartsWith the comparison operator instance
     */
    public function startsWith(mixed $variable): StartsWith
    {
        return new StartsWith($this, $this->asVariable($variable));
    }

    /**
     * Create a case-insensitive string starts with comparison operator.
     *
     * @param  mixed                 $variable the prefix value or Variable to check for (case-insensitive) at the start of this variable's string value
     * @return StartsWithInsensitive the comparison operator instance
     */
    public function startsWithInsensitive(mixed $variable): StartsWithInsensitive
    {
        return new StartsWithInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Retrieve the RuleBuilder instance that manages this variable.
     */
    abstract public function getRuleBuilder(): RuleBuilder;

    /**
     * Convert a value to a VariableOperand instance if it isn't already.
     *
     * @param  mixed           $variable a VariableOperand instance or any value to wrap in a Variable
     * @return VariableOperand the existing VariableOperand instance or a new Variable wrapping the value
     */
    private function asVariable(mixed $variable): VariableOperand
    {
        return ($variable instanceof VariableOperand) ? $variable : new BaseVariable(null, $variable);
    }

    /**
     * Apply a set operator by dynamically instantiating it with reflection.
     *
     * @param  string                   $name the operator class name (without namespace)
     * @param  array<int|string, mixed> $args the arguments to pass to the operator constructor
     * @return Variable                 a new Variable wrapping the operator for method chaining
     */
    private function applySetOperator(string $name, array $args): Variable
    {
        /** @var class-string $operatorClass */
        $operatorClass = '\\Cline\\Ruler\\Operators\\Set\\'.$name;
        $reflection = new ReflectionClass($operatorClass);
        array_unshift($args, $this);

        $op = $reflection->newInstanceArgs($args);
        assert($op instanceof VariableOperator);

        return $this->wrapOperator($op);
    }

    /**
     * Wrap a VariableOperator in a new Variable instance for method chaining.
     *
     * @param  VariableOperator $op the operator to wrap in a Variable
     * @return Variable         a new Variable instance with the operator as its value
     */
    private function wrapOperator(VariableOperator $op): Variable
    {
        return new Variable($this->getRuleBuilder(), null, $op);
    }
}
