<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;

/**
 * An EqualTo comparison operator.
 *
 * @author Justin Hileman <justin@shopopensky.com>
 */
final class ALotGreaterThan extends VariableOperator implements Proposition
{
    /**
     * Evaluate whether the given variables are equal in the current Context.
     *
     * @param Context $context Context with which to evaluate this ComparisonOperator
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();
        $value = $right->prepareValue($context)->getValue() * 10;

        return $left->prepareValue($context)->greaterThan(
            new Value($value),
        );
    }

    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
