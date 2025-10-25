<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

use Cline\Ruler\DSL\Wirefilter\ValidationResult;
use JsonException;
use Throwable;

use function array_keys;
use function array_slice;
use function count;
use function implode;
use function is_string;
use function max;
use function mb_strlen;
use function mb_substr;
use function min;
use function preg_match;

/**
 * Validates GraphQL Filter DSL expressions without full compilation.
 *
 * Provides validation of GraphQL filter syntax by attempting to parse expressions
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
 * $validator = new GraphQLFilterValidator();
 *
 * // Simple validation
 * if ($validator->validate(['age' => ['gte' => 18]])) {
 *     // Expression is valid
 * }
 *
 * // Detailed validation with error info
 * $result = $validator->validateWithErrors(['age' => ['invalid' => 18]]);
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see GraphQLFilterParser For parsing valid expressions into Rules
 * @see GraphQLFilterSerializer For converting Rules back to filter arrays
 * @see ValidationResult For structured validation error information
 *
 * @psalm-immutable
 */
final readonly class GraphQLFilterValidator
{
    /**
     * Filter parser for validation.
     */
    private GraphQLParser $parser;

    /**
     * Create a new GraphQLFilterValidator instance.
     */
    public function __construct()
    {
        $this->parser = new GraphQLParser();
    }

    /**
     * Validate a GraphQL Filter DSL expression.
     *
     * Performs quick validation by attempting to parse the expression.
     * Returns true if the expression is syntactically valid, false otherwise.
     *
     * @param  array<string, mixed>|string $filter The GraphQL filter expression as array or JSON string
     * @return bool                        True if the expression is valid, false otherwise
     */
    public function validate(array|string $filter): bool
    {
        try {
            $this->parser->parse($filter);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Validate a GraphQL Filter DSL expression and return detailed errors.
     *
     * Attempts to parse the expression and captures any syntax or compilation
     * errors. Returns a ValidationResult with structured error information
     * including error messages and context when available.
     *
     * @param  array<string, mixed>|string $filter The GraphQL filter expression as array or JSON string
     * @return ValidationResult            Structured validation result with error details
     */
    public function validateWithErrors(array|string $filter): ValidationResult
    {
        try {
            $this->parser->parse($filter);

            return ValidationResult::success();
        } catch (JsonException $e) {
            $context = self::extractJsonContext($filter, $e);
            $error = [
                'message' => 'Invalid JSON: '.$e->getMessage(),
            ];

            // @codeCoverageIgnoreStart
            if ($context !== null) {
                $error['context'] = $context;
            }

            // @codeCoverageIgnoreEnd

            return ValidationResult::failure([$error]);
        } catch (Throwable $e) {
            $error = [
                'message' => $e->getMessage(),
                'context' => self::extractFilterContext($filter),
            ];

            return ValidationResult::failure([$error]);
        }
    }

    /**
     * Extract context around JSON parsing error.
     *
     * @param  array<string, mixed>|string $filter The filter that failed to parse
     * @param  JsonException               $error  The JSON exception
     * @return null|string                 A snippet of the JSON around the error
     */
    private static function extractJsonContext(array|string $filter, JsonException $error): ?string
    {
        // @codeCoverageIgnoreStart
        if (!is_string($filter)) {
            return null;
        }

        // @codeCoverageIgnoreEnd

        // Try to extract position from error message
        // @codeCoverageIgnoreStart
        if (preg_match('/at position (\d+)/', $error->getMessage(), $matches)) {
            $position = (int) $matches[1];

            // Extract 20 characters before and after the error position
            $start = max(0, $position - 20);
            $length = min(40, mb_strlen($filter) - $start);
            $context = mb_substr($filter, $start, $length);

            // Add ellipsis if truncated
            if ($start > 0) {
                $context = '...'.$context;
            }

            if ($start + $length < mb_strlen($filter)) {
                $context .= '...';
            }

            return $context;
        }

        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Extract context from filter for error reporting.
     *
     * Provides a summary of the filter structure to help identify the issue.
     *
     * @param  array<string, mixed>|string $filter The filter being validated
     * @return string                      A summary of the filter structure
     */
    private static function extractFilterContext(array|string $filter): string
    {
        if (is_string($filter)) {
            // If it's a string, try to show the beginning
            $maxLength = 100;

            if (mb_strlen($filter) > $maxLength) {
                return mb_substr($filter, 0, $maxLength).'...';
            }

            return $filter;
        }

        // Show the top-level keys
        $keys = array_keys($filter);

        if (count($keys) > 5) {
            $keys = array_slice($keys, 0, 5);
            $keys[] = '...';
        }

        return 'Filter with keys: '.implode(', ', $keys);
    }
}
