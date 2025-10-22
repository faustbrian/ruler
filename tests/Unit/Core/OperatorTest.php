<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Operator;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Variables\VariableOperand;

describe('Operator', function (): void {
    describe('Happy Paths', function (): void {
        test('constructs with no operands', function (): void {
            $operator = new class() extends Operator
            {
                public function addOperand($operand): void
                {
                    $this->operands[] = $operand;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Multiple;
                }
            };

            expect($operator)->toBeInstanceOf(Operator::class);
        });

        test('constructs with operands via constructor', function (): void {
            $operand1 = $this->createMock(VariableOperand::class);
            $operand2 = $this->createMock(VariableOperand::class);

            $operator = new class($operand1, $operand2) extends Operator
            {
                public function addOperand($operand): void
                {
                    $this->operands[] = $operand;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Binary;
                }
            };

            $operands = $operator->getOperands();
            expect($operands)->toHaveCount(2);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception for unary operator with wrong operand count', function (): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessageMatches('/takes only 1 operand/');

            $operand1 = $this->createMock(VariableOperand::class);
            $operand2 = $this->createMock(VariableOperand::class);

            $operator = new class($operand1, $operand2) extends Operator
            {
                public function addOperand($operand): void
                {
                    $this->operands[] = $operand;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Unary;
                }
            };

            $operator->getOperands();
        });

        test('throws exception for binary operator with wrong operand count', function (): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessageMatches('/takes 2 operands/');

            $operand1 = $this->createMock(VariableOperand::class);

            $operator = new class($operand1) extends Operator
            {
                public function addOperand($operand): void
                {
                    $this->operands[] = $operand;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Binary;
                }
            };

            $operator->getOperands();
        });

        test('throws exception for multiple operator with no operands', function (): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessageMatches('/takes at least 1 operand/');

            $operator = new class() extends Operator
            {
                public function addOperand($operand): void
                {
                    $this->operands[] = $operand;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Multiple;
                }
            };

            $operator->getOperands();
        });
    });
});
