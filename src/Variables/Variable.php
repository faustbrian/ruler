<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Variables;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Values\Value;

/**
 * Propositional variable placeholder for rule evaluation.
 *
 * Variables are named placeholders in propositions and comparison operators that
 * get resolved to concrete Values during evaluation. Resolution follows a priority:
 * context values override default values, and VariableOperand values are recursively
 * resolved.
 *
 * Variables serve as the bridge between rule definitions and runtime data, enabling
 * rules to be defined once and evaluated against different contexts with varying
 * data.
 *
 * @author Brian Faust <brian@cline.sh>
 */
class Variable implements VariableOperand
{
    /**
     * Create a new Variable instance.
     *
     * @param null|string $name the variable name used for looking up values in the
     *                          evaluation context. When null, the variable can only
     *                          resolve from its default value. This name is used to
     *                          lookup values in the Context during rule evaluation.
     * @param mixed $value the default value used when the variable name is not found
     *                     in the context, or when name is null. Can be a scalar, array,
     *                     object, or VariableOperand instance. If the value is a
     *                     VariableOperand, it will be recursively resolved during evaluation.
     */
    public function __construct(
        private readonly mixed $name = null,
        private mixed $value = null
    ) {
    }

    /**
     * Get the variable name used for context lookups.
     *
     * @return null|string the variable name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the default value for this variable.
     *
     * @param mixed $value the default value to use when the variable name is not
     *                     found in the evaluation context
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Get the default value for this variable.
     *
     * @return mixed the default value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Resolve this variable to a concrete Value using the current context.
     *
     * Resolution follows this priority:
     * 1. If the variable name exists in the context, use the context value
     * 2. If the default value is a VariableOperand, recursively resolve it
     * 3. Otherwise, use the default value
     *
     * The resolved value is wrapped in a Value instance if it isn't already.
     *
     * @param Context $context the evaluation context containing variable values
     * @return Value the resolved value wrapped in a Value instance
     */
    public function prepareValue(Context $context): Value
    {
        if ($this->name !== null && $context->offsetExists($this->name)) {
            $value = $context[$this->name];
        } elseif ($this->value instanceof VariableOperand) {
            $value = $this->value->prepareValue($context);
        } else {
            $value = $this->value;
        }

        return ($value instanceof Value) ? $value : new Value($value);
    }
}
