<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

use Cline\Ruler\DSL\Wirefilter\ValidationResult;
use Throwable;

/**
 * Validates SQL WHERE clause expressions without full compilation.
 *
 * Provides validation of SQL WHERE clause syntax by attempting to parse expressions
 * and catching any syntax or compilation errors. Returns structured validation
 * results with detailed error information.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $validator = new SQLWhereValidator();
 *
 * // Simple validation
 * if ($validator->validate("age >= 18")) {
 *     // Expression is valid
 * }
 *
 * // Detailed validation with error info
 * $result = $validator->validateWithErrors("age >= invalid");
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see SQLWhereParser For parsing valid expressions into Rules
 * @see SQLWhereSerializer For converting Rules back to SQL WHERE strings
 * @see ValidationResult For structured validation error information
 *
 * @psalm-immutable
 */
final readonly class SQLWhereValidator
{
    /**
     * SQL WHERE clause rule builder for validation.
     */
    private SqlWhereRuleBuilder $builder;

    /**
     * Create a new SQLWhereValidator instance.
     */
    public function __construct()
    {
        $this->builder = new SqlWhereRuleBuilder();
    }

    /**
     * Validate a SQL WHERE clause expression.
     *
     * Performs quick validation by attempting to parse the expression.
     * Returns true if the expression is syntactically valid, false otherwise.
     *
     * @param  string $expression The SQL WHERE clause expression to validate
     * @return bool   True if the expression is valid, false otherwise
     */
    public function validate(string $expression): bool
    {
        return $this->builder->validate($expression);
    }

    /**
     * Validate a SQL WHERE clause expression and return detailed errors.
     *
     * Attempts to parse the expression and captures any syntax or compilation
     * errors. Returns a ValidationResult with structured error information
     * including error messages and positions when available.
     *
     * @param  string           $expression The SQL WHERE clause expression to validate
     * @return ValidationResult Structured validation result with error details
     */
    public function validateWithErrors(string $expression): ValidationResult
    {
        try {
            $this->builder->parse($expression);

            return ValidationResult::success();
        } catch (Throwable $throwable) {
            $errors = [[
                'message' => $throwable->getMessage(),
            ]];

            return ValidationResult::failure($errors);
        }
    }
}
