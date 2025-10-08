<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Enums;

/**
 * Defines operand cardinality constraints for rule engine operators.
 *
 * This enum specifies how many operands an operator requires during rule
 * evaluation. The cardinality is enforced when operands are added to an
 * operator and when they are retrieved for evaluation, ensuring type-safe
 * operator construction and preventing runtime errors from incorrect operand counts.
 *
 * Cardinality validation occurs in the Operator base class during getOperands(),
 * which checks the operand count against the value returned by getOperandCardinality()
 * and throws a LogicException if the count doesn't match the required cardinality.
 *
 * @see \Cline\Ruler\Operator::getOperands() Validates operand count against cardinality
 * @see \Cline\Ruler\Operator::getOperandCardinality() Returns the required cardinality
 */
enum OperandCardinality: string
{
    /**
     * Unary operators require exactly one operand.
     *
     * Used for operators that transform or validate a single value,
     * such as negation, absolute value, type checks, or string length.
     *
     * Examples: IsNull, IsEmpty, Abs, Floor, Ceil, StringLength
     */
    case Unary = 'UNARY';

    /**
     * Binary operators require exactly two operands.
     *
     * Used for operators that compare or combine two values,
     * such as arithmetic operations, comparisons, or string matching.
     *
     * Examples: EqualTo, GreaterThan, Addition, Subtraction, StartsWith
     */
    case Binary = 'BINARY';

    /**
     * Multiple cardinality operators accept one or more operands.
     *
     * Used for operators that perform operations on variable-length
     * operand sets, such as logical operations on multiple conditions
     * or set operations on multiple collections.
     *
     * Examples: LogicalAnd, LogicalOr, Union, Intersect, Between
     */
    case Multiple = 'MULTIPLE';
}
