<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\MongoDB;

use Cline\Ruler\DSL\Wirefilter\ValidationResult;
use JsonException;
use Throwable;

use const JSON_THROW_ON_ERROR;

use function is_array;
use function json_decode;
use function sprintf;

/**
 * Validates MongoDB Query DSL documents without full compilation.
 *
 * Provides validation of MongoDB query syntax by attempting to parse queries
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
 * $validator = new MongoQueryValidator();
 *
 * // Simple validation - array input
 * if ($validator->validate(['age' => ['$gte' => 18]])) {
 *     // Query is valid
 * }
 *
 * // Simple validation - JSON input
 * if ($validator->validateJson('{"age": {"$gte": 18}}')) {
 *     // Query is valid
 * }
 *
 * // Detailed validation with error info
 * $result = $validator->validateWithErrors(['age' => ['$invalid' => 18]]);
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see MongoQueryParser For parsing valid queries into Rules
 * @see MongoQuerySerializer For converting Rules back to query documents
 * @see ValidationResult For structured validation error information
 *
 * @psalm-immutable
 */
final readonly class MongoQueryValidator
{
    /**
     * MongoDB query parser for validation.
     */
    private MongoQueryParser $parser;

    /**
     * Create a new MongoQueryValidator instance.
     */
    public function __construct()
    {
        $this->parser = new MongoQueryParser();
    }

    /**
     * Validate a MongoDB query document array.
     *
     * Performs quick validation by attempting to parse the query.
     * Returns true if the query is syntactically valid, false otherwise.
     *
     * @param  array<string, mixed> $query The MongoDB query document to validate
     * @return bool                 True if the query is valid, false otherwise
     */
    public function validate(array $query): bool
    {
        try {
            $this->parser->parse($query);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Validate a JSON-encoded MongoDB query string.
     *
     * Decodes the JSON and validates the resulting query document.
     * Returns true if the JSON is valid and the query is syntactically valid.
     *
     * @param  string $json JSON-encoded MongoDB query document
     * @return bool   True if the query is valid, false otherwise
     */
    public function validateJson(string $json): bool
    {
        try {
            $this->parser->parseJson($json);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Validate a MongoDB query document and return detailed errors.
     *
     * Attempts to parse the query and captures any syntax or compilation
     * errors. Returns a ValidationResult with structured error information
     * including error messages.
     *
     * @param  array<string, mixed> $query The MongoDB query document to validate
     * @return ValidationResult     Structured validation result with error details
     */
    public function validateWithErrors(array $query): ValidationResult
    {
        try {
            $this->parser->parse($query);

            return ValidationResult::success();
        } catch (Throwable $throwable) {
            $errors = [[
                'message' => $throwable->getMessage(),
            ]];

            return ValidationResult::failure($errors);
        }
    }

    /**
     * Validate a JSON-encoded MongoDB query string and return detailed errors.
     *
     * Attempts to decode and parse the query, capturing any JSON or compilation
     * errors. Returns a ValidationResult with structured error information.
     *
     * @param  string           $json JSON-encoded MongoDB query document
     * @return ValidationResult Structured validation result with error details
     */
    public function validateJsonWithErrors(string $json): ValidationResult
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return ValidationResult::failure([[
                    'message' => 'JSON must decode to an object/array',
                ]]);
            }

            /** @var array<string, mixed> $query */
            $query = $decoded;

            return $this->validateWithErrors($query);
        } catch (JsonException $e) {
            $errors = [[
                'message' => sprintf('JSON decode error: %s', $e->getMessage()),
            ]];

            return ValidationResult::failure($errors);
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            $errors = [[
                'message' => $e->getMessage(),
            ]];

            return ValidationResult::failure($errors);
        }

        // @codeCoverageIgnoreEnd
    }
}
