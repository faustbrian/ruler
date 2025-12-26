<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\PropositionOperator;

describe('PropositionOperator', function (): void {
    describe('Happy Paths', function (): void {
        test('adds proposition operands successfully', function (): void {
            $operand1 = $this->createMock(Proposition::class);
            $operand2 = $this->createMock(Proposition::class);

            $operator = new class($operand1, $operand2) extends PropositionOperator
            {
                public function evaluate(Context $context): bool
                {
                    return true;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Binary;
                }
            };

            $operands = $operator->getOperands();
            expect($operands)->toHaveCount(2);
        });

        test('adds single proposition for unary operator', function (): void {
            $operand = $this->createMock(Proposition::class);

            $operator = new class($operand) extends PropositionOperator
            {
                public function evaluate(Context $context): bool
                {
                    return true;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Unary;
                }
            };

            $operands = $operator->getOperands();
            expect($operands)->toHaveCount(1);
        });

        test('adds multiple propositions', function (): void {
            $operand1 = $this->createMock(Proposition::class);
            $operand2 = $this->createMock(Proposition::class);
            $operand3 = $this->createMock(Proposition::class);

            $operator = new class($operand1, $operand2, $operand3) extends PropositionOperator
            {
                public function evaluate(Context $context): bool
                {
                    return true;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Multiple;
                }
            };

            $operands = $operator->getOperands();
            expect($operands)->toHaveCount(3);
        });

        test('addOperand method delegates to addProposition', function (): void {
            $operand = $this->createMock(Proposition::class);

            $operator = new class() extends PropositionOperator
            {
                public function evaluate(Context $context): bool
                {
                    return true;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Multiple;
                }
            };

            $operator->addOperand($operand);

            $operands = $operator->getOperands();
            expect($operands)->toHaveCount(1);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception when adding second proposition to unary operator', function (): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessageMatches('/can only have 1 operand/');

            $operand1 = $this->createMock(Proposition::class);
            $operand2 = $this->createMock(Proposition::class);

            new class($operand1, $operand2) extends PropositionOperator
            {
                public function evaluate(Context $context): bool
                {
                    return true;
                }

                protected function getOperandCardinality(): OperandCardinality
                {
                    return OperandCardinality::Unary;
                }
            };
        });
    });
});
