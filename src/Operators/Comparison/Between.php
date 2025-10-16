<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Comparison;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;
use RuntimeException;

use function is_numeric;
use function throw_if;

/**
 * Evaluates whether a numeric value falls within an inclusive range.
 *
 * Performs an inclusive range check where the value must be greater than
 * or equal to the minimum and less than or equal to the maximum. All
 * operands must resolve to numeric values for the comparison to succeed.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Between extends VariableOperator implements Proposition
{
    /**
     * Creates a new between range operator.
     *
     * @param VariableOperand $value Value to test for inclusion within the range
     * @param VariableOperand $min   Minimum boundary value (inclusive) for the range comparison
     * @param VariableOperand $max   Maximum boundary value (inclusive) for the range comparison
     */
    public function __construct(VariableOperand $value, VariableOperand $min, VariableOperand $max)
    {
        parent::__construct($value, $min, $max);
    }

    /**
     * Evaluates whether the value is between the minimum and maximum boundaries (inclusive).
     *
     * @param Context $context Execution context providing variable values for operand resolution
     *
     * @throws RuntimeException When any operand value is not numeric
     *
     * @return bool True if value is within the range [min, max], false otherwise
     */
    public function evaluate(Context $context): bool
    {
        [$value, $min, $max] = $this->getOperands();

        /** @var VariableOperand $value */
        /** @var VariableOperand $min */
        /** @var VariableOperand $max */
        $val = $value->prepareValue($context)->getValue();
        $minVal = $min->prepareValue($context)->getValue();
        $maxVal = $max->prepareValue($context)->getValue();

        throw_if(!is_numeric($val) || !is_numeric($minVal) || !is_numeric($maxVal), RuntimeException::class, 'Between: all values must be numeric');

        return $val >= $minVal && $val <= $maxVal;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Multiple cardinality requiring three operands (value, min, max)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
