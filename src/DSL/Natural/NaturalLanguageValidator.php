<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\DSL\Wirefilter\ValidationResult;
use Throwable;

/**
 * Validates Natural Language DSL expressions without full compilation.
 *
 * Provides validation of natural language DSL syntax by attempting to parse
 * expressions and catching any syntax or compilation errors. Returns structured
 * validation results with detailed error information.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $validator = new NaturalLanguageValidator();
 *
 * // Simple validation
 * if ($validator->validate('age is greater than 18')) {
 *     // Expression is valid
 * }
 *
 * // Detailed validation with error info
 * $result = $validator->validateWithErrors('age is invalid operator 18');
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see NaturalLanguageParser For parsing valid expressions into Rules
 * @see NaturalLanguageSerializer For converting Rules back to DSL strings
 * @see ValidationResult For structured validation error information
 *
 * @psalm-immutable
 */
final readonly class NaturalLanguageValidator
{
    /**
     * Expression parser for validation.
     */
    private ASTParser $parser;

    /**
     * Create a new NaturalLanguageValidator instance.
     */
    public function __construct()
    {
        $this->parser = new ASTParser();
    }

    /**
     * Validate a Natural Language DSL expression.
     *
     * Performs quick validation by attempting to parse the expression.
     * Returns true if the expression is syntactically valid, false otherwise.
     *
     * @param  string $expression The Natural Language DSL expression to validate
     * @return bool   True if the expression is valid, false otherwise
     */
    public function validate(string $expression): bool
    {
        try {
            $this->parser->parse($expression);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Validate a Natural Language DSL expression and return detailed errors.
     *
     * Attempts to parse the expression and captures any syntax or compilation
     * errors. Returns a ValidationResult with structured error information
     * including error messages.
     *
     * @param  string           $expression The Natural Language DSL expression to validate
     * @return ValidationResult Structured validation result with error details
     */
    public function validateWithErrors(string $expression): ValidationResult
    {
        try {
            $this->parser->parse($expression);

            return ValidationResult::success();
        } catch (Throwable $throwable) {
            $errors = [[
                'message' => $throwable->getMessage(),
            ]];

            return ValidationResult::failure($errors);
        }
    }
}
