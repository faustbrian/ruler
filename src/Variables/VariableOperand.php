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
 * Interface for objects that can be resolved to values during rule evaluation.
 *
 * VariableOperand defines the contract for objects that can produce concrete Values
 * when evaluated against a Context. This includes Variables, VariableProperties,
 * and various Operators that need to resolve their operands before computing results.
 *
 * Implementing classes must provide value resolution logic that produces a Value
 * instance given the current evaluation context.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface VariableOperand
{
    /**
     * Resolve this operand to a concrete Value using the current context.
     *
     * Implementations should resolve any nested variables or operands and return
     * a Value instance representing the final, evaluated result.
     *
     * @param  Context $context the evaluation context containing variable values
     *                          and facts needed to resolve this operand
     * @return Value   the resolved value wrapped in a Value instance
     */
    public function prepareValue(Context $context): Value;
}
