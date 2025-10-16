<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Logical;

use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Operators\PropositionOperator;

/**
 * Abstract base class for logical operators that combine propositions using boolean logic.
 *
 * Provides common functionality for all logical operators (AND, OR, NOT, NAND, NOR)
 * that evaluate proposition operands according to boolean algebra rules. Extends
 * PropositionOperator to inherit operand management and implements Proposition to
 * enable logical operators to be nested and composed into complex expressions.
 *
 * @method array<int, Proposition> getOperands()
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class LogicalOperator extends PropositionOperator implements Proposition
{
    /**
     * Creates a new logical operator with the specified proposition operands.
     *
     * The number of propositions required depends on the specific operator
     * implementation: NOT requires exactly one operand, while AND/OR/NAND/NOR
     * require two or more operands.
     *
     * @param array<Proposition> $props Array of proposition operands to evaluate using this
     *                                  operator's boolean logic rules. Operands are added in
     *                                  the order provided and evaluated according to the
     *                                  specific operator's implementation.
     */
    public function __construct(array $props = [])
    {
        foreach ($props as $operand) {
            $this->addOperand($operand);
        }
    }
}
