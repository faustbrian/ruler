<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Builder;

use ArrayAccess;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Values\Value;
use Closure;
use Override;

use function array_key_exists;
use function is_array;
use function is_object;
use function method_exists;

/**
 * RuleBuilder-enhanced property accessor for nested variable data.
 *
 * VariableProperty extends Variable to access properties, methods, or array offsets
 * of a parent variable's resolved value. During evaluation, it resolves the parent
 * variable first, then extracts the named property from the result using a prioritized
 * lookup strategy.
 *
 * The RuleBuilder integration provides the same fluent interface as Variable, enabling
 * property access to be chained with operators and comparisons without verbose object
 * instantiation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class VariableProperty extends Variable
{
    /**
     * Create a new VariableProperty instance.
     *
     * @param Variable $parent The parent Variable instance whose resolved value will
     *                         be accessed to extract this property. During evaluation,
     *                         the parent is resolved first, then this property name is
     *                         used to access a method, property, or array key on the
     *                         parent's resolved value.
     * @param string   $name   The property name to access on the parent's resolved value.
     *                         Used for method calls, property access, or array key lookup.
     * @param mixed    $value  default value to return if the property cannot be resolved
     *                         from the parent variable's value
     */
    public function __construct(
        private readonly mixed $parent,
        ?string $name,
        mixed $value = null,
    ) {
        parent::__construct($this->parent->getRuleBuilder(), $name, $value);
    }

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
    #[Override()]
    public function prepareValue(Context $context): Value
    {
        $name = $this->getName();
        $value = $this->parent->prepareValue($context)->getValue();

        if ($name === null) {
            return self::asValue($this->getValue());
        }

        if (is_object($value) && !$value instanceof Closure) {
            if (method_exists($value, $name)) {
                /** @var callable $callable */
                $callable = [$value, $name];

                return self::asValue($callable());
            }

            if (isset($value->{$name})) {
                return self::asValue($value->{$name});
            }

            if ($value instanceof ArrayAccess && $value->offsetExists($name)) {
                return self::asValue($value->offsetGet($name));
            }
        } elseif (is_array($value) && array_key_exists($name, $value)) {
            return self::asValue($value[$name]);
        }

        return self::asValue($this->getValue());
    }

    /**
     * Convert a value to a Value instance if it isn't already.
     *
     * @param  mixed $value a Value instance or any value to wrap in a Value
     * @return Value the existing Value instance or a new Value wrapping the value
     */
    private static function asValue($value): Value
    {
        return ($value instanceof Value) ? $value : new Value($value);
    }
}
