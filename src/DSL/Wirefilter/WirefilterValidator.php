<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Throwable;

use function max;
use function mb_strlen;
use function mb_substr;
use function min;
use function preg_match;

/**
 * Validates Wirefilter DSL expressions without full compilation.
 *
 * Provides validation of Wirefilter DSL syntax by attempting to parse expressions
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
 * $validator = new WirefilterValidator();
 *
 * // Simple validation
 * if ($validator->validate('age >= 18')) {
 *     // Expression is valid
 * }
 *
 * // Detailed validation with error info
 * $result = $validator->validateWithErrors('age >= invalid');
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see WirefilterParser For parsing valid expressions into Rules
 * @see WirefilterSerializer For converting Rules back to DSL strings
 * @see ValidationResult For structured validation error information
 *
 * @psalm-immutable
 */
final readonly class WirefilterValidator
{
    /**
     * Expression parser for validation.
     */
    private ExpressionParser $parser;

    /**
     * Create a new WirefilterValidator instance.
     */
    public function __construct()
    {
        $this->parser = new ExpressionParser();
    }

    /**
     * Validate a Wirefilter DSL expression.
     *
     * Performs quick validation by attempting to parse the expression.
     * Returns true if the expression is syntactically valid, false otherwise.
     *
     * @param  string $expression The Wirefilter DSL expression to validate
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
     * Validate a Wirefilter DSL expression and return detailed errors.
     *
     * Attempts to parse the expression and captures any syntax or compilation
     * errors. Returns a ValidationResult with structured error information
     * including error messages and positions when available.
     *
     * @param  string           $expression The Wirefilter DSL expression to validate
     * @return ValidationResult Structured validation result with error details
     */
    public function validateWithErrors(string $expression): ValidationResult
    {
        try {
            $this->parser->parse($expression);

            return ValidationResult::success();
        } catch (SyntaxError $e) {
            $error = ['message' => $e->getMessage()];

            $position = self::extractPosition($e);

            if ($position !== null) {
                $error['position'] = $position;
            }

            $context = self::extractContext($expression, $e);

            if ($context !== null) {
                $error['context'] = $context;
            }

            return ValidationResult::failure([$error]);
        } catch (Throwable $e) {
            $errors = [[
                'message' => $e->getMessage(),
            ]];

            return ValidationResult::failure($errors);
        }
    }

    /**
     * Extract position information from SyntaxError.
     *
     * @param  SyntaxError $error The syntax error to extract position from
     * @return null|int    The character position where the error occurred, or null if unavailable
     */
    private static function extractPosition(SyntaxError $error): ?int
    {
        // Try to extract position from error message
        // SyntaxError message format: "message around position X"
        if (preg_match('/position (\d+)/', $error->getMessage(), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract context around the error position.
     *
     * Provides a snippet of the expression around where the error occurred
     * to help users identify and fix the issue.
     *
     * @param  string      $expression The original expression being validated
     * @param  SyntaxError $error      The syntax error containing position info
     * @return null|string A snippet of the expression around the error, or null if position unavailable
     */
    private static function extractContext(string $expression, SyntaxError $error): ?string
    {
        $position = self::extractPosition($error);

        if ($position === null) {
            return null;
        }

        // Extract 20 characters before and after the error position
        $start = max(0, $position - 20);
        $length = min(40, mb_strlen($expression) - $start);
        $context = mb_substr($expression, $start, $length);

        // Add ellipsis if truncated
        if ($start > 0) {
            $context = '...'.$context;
        }

        if ($start + $length < mb_strlen($expression)) {
            $context .= '...';
        }

        return $context;
    }
}
