<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use function array_map;

/**
 * Represents the result of validating a Wirefilter DSL expression.
 *
 * Contains validation status and detailed error information when validation fails.
 * Provides structured access to validation errors including messages, positions,
 * and contextual information.
 *
 * Example usage:
 * ```php
 * $validator = new WirefilterValidator();
 * $result = $validator->validateWithErrors('age >= invalid');
 *
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo $error['message'];
 *     }
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ValidationResult
{
    /**
     * @param bool $valid Whether the expression is valid
     * @param array<int, array{
     *     message: string,
     *     position?: int,
     *     context?: string
     * }>                          $errors Array of validation errors
     */
    public function __construct(
        private bool $valid,
        private array $errors = [],
    ) {}

    /**
     * Create a successful validation result.
     *
     * @return self A ValidationResult indicating successful validation
     */
    public static function success(): self
    {
        return new self(true, []);
    }

    /**
     * Create a failed validation result with errors.
     *
     * @param array<int, array{
     *     message: string,
     *     position?: int,
     *     context?: string
     * }> $errors Array of validation errors
     * @return self A ValidationResult indicating failed validation
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    /**
     * Check if the expression is valid.
     *
     * @return bool True if the expression passed validation, false otherwise
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get validation errors.
     *
     * @return array<int, array{
     *     message: string,
     *     position?: int,
     *     context?: string
     * }> Array of validation errors with message, optional position, and context
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all error messages as strings.
     *
     * @return array<int, string> Array of error message strings
     */
    public function getErrorMessages(): array
    {
        return array_map(
            fn (array $error): string => $error['message'],
            $this->errors,
        );
    }

    /**
     * Get the first error message.
     *
     * @return null|string The first error message or null if no errors
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0]['message'] ?? null;
    }
}
