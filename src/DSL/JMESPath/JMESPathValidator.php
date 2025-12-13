<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\JMESPath;

use Cline\Ruler\DSL\Wirefilter\ValidationResult;
use Throwable;

/**
 * Validates JMESPath filter expressions without full compilation.
 *
 * Provides validation of JMESPath filter syntax by attempting to evaluate expressions
 * with empty data and catching any syntax or runtime errors. Returns structured validation
 * results with detailed error information.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $validator = new JMESPathValidator();
 *
 * // Simple validation
 * if ($validator->validate('user.age >= `18`')) {
 *     // Expression is valid
 * }
 *
 * // Detailed validation with error info
 * $result = $validator->validateWithErrors('user.age >= invalid');
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see JMESPathParser For parsing valid expressions into Rules
 * @see JMESPathSerializer For converting Rules back to DSL strings
 * @see ValidationResult For structured validation error information
 *
 * @psalm-immutable
 */
final readonly class JMESPathValidator
{
    /**
     * JMESPath adapter for validation.
     */
    private JMESPathAdapter $adapter;

    /**
     * Create a new JMESPathValidator instance.
     */
    public function __construct()
    {
        $this->adapter = new JMESPathAdapter();
    }

    /**
     * Validate a JMESPath filter expression.
     *
     * Performs quick validation by attempting to evaluate the expression
     * with empty data. Returns true if the expression is syntactically
     * valid, false otherwise.
     *
     * @param  string $expression The JMESPath filter expression to validate
     * @return bool   True if the expression is valid, false otherwise
     */
    public function validate(string $expression): bool
    {
        try {
            $this->adapter->evaluate($expression, []);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Validate a JMESPath filter expression and return detailed errors.
     *
     * Attempts to evaluate the expression with empty data and captures any
     * syntax or runtime errors. Returns a ValidationResult with structured
     * error information including error messages.
     *
     * @param  string           $expression The JMESPath filter expression to validate
     * @return ValidationResult Structured validation result with error details
     */
    public function validateWithErrors(string $expression): ValidationResult
    {
        try {
            $this->adapter->evaluate($expression, []);

            return ValidationResult::success();
        } catch (Throwable $throwable) {
            $errors = [[
                'message' => $throwable->getMessage(),
            ]];

            return ValidationResult::failure($errors);
        }
    }
}
