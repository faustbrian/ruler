<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Variables\Concerns;

use ArrayAccess;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;
use Closure;

use function array_key_exists;
use function is_array;
use function is_callable;
use function is_object;
use function method_exists;

/**
 * Shared property resolution logic for VariableProperty implementations.
 *
 * This trait provides the prepareValue() implementation for resolving nested
 * property values from a parent variable. It handles objects (methods, properties,
 * ArrayAccess) and arrays with a prioritized lookup strategy.
 *
 * Classes using this trait must implement:
 * - getParent(): VariableOperand - returns the parent variable
 * - getName(): ?string - returns the property name
 * - getValue(): mixed - returns the default value
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait ResolvesPropertyValue
{
    /**
     * Resolve this property's value from the parent variable using the current context.
     *
     * Resolution follows a prioritized lookup strategy based on the parent value's type:
     *
     * For objects (excluding Closures):
     *   1. Method named `{name}` - calls the method and returns the result
     *   2. Public property named `{name}` - returns the property value
     *   3. ArrayAccess offset `{name}` - calls offsetGet and returns the value
     *
     * For arrays:
     *   1. Array key `{name}` - returns the value at that key
     *
     * If none of these succeed, returns this property's default value.
     *
     * @param  Context $context the evaluation context used to resolve the parent variable
     * @return Value   the resolved property value wrapped in a Value instance
     */
    public function prepareValue(Context $context): Value
    {
        $name = $this->getName();
        $value = $this->getParent()->prepareValue($context)->getValue();

        if ($name === null) {
            return $this->asValue($this->getValue());
        }

        if (is_object($value) && !$value instanceof Closure) {
            if (method_exists($value, $name) && is_callable([$value, $name])) {
                /** @var callable $callable */
                $callable = [$value, $name];

                return $this->asValue($callable());
            }

            if (isset($value->{$name})) {
                return $this->asValue($value->{$name});
            }

            if ($value instanceof ArrayAccess && $value->offsetExists($name)) {
                return $this->asValue($value->offsetGet($name));
            }
        } elseif (is_array($value) && array_key_exists($name, $value)) {
            return $this->asValue($value[$name]);
        }

        return $this->asValue($this->getValue());
    }

    /**
     * Get the property name to access on the parent's resolved value.
     */
    abstract public function getName(): ?string;

    /**
     * Get the default value for this property.
     */
    abstract public function getValue(): mixed;

    /**
     * Get the parent variable whose value will be accessed.
     */
    abstract protected function getParent(): VariableOperand;

    /**
     * Convert a value to a Value instance if it isn't already.
     *
     * @param  mixed $value a Value instance or any value to wrap in a Value
     * @return Value the existing Value instance or a new Value wrapping the value
     */
    private function asValue(mixed $value): Value
    {
        return ($value instanceof Value) ? $value : new Value($value);
    }
}
