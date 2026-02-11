<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use Exception;
use Throwable;

use function sprintf;

/**
 * Exception thrown when proposition evaluation encounters invalid rule structures or combinators.
 *
 * This exception is raised during rule parsing/compilation and runtime evaluation
 * when the RuleEvaluator encounters malformed rule data, unrecognized operators,
 * or execution failures.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RuleEvaluatorException extends Exception
{
    /**
     * @param array<int, int|string> $path
     * @param array<string, mixed>   $details
     */
    private function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly string $phase,
        private readonly array $path = [],
        private readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create exception for an invalid logical combinator.
     *
     * @param array<int, int|string> $path
     */
    public static function invalidCombinator(string $combinator, array $path = ['combinator']): self
    {
        return new self(
            sprintf('Invalid combinator: %s', $combinator),
            'compile.invalid_combinator',
            'compile',
            $path,
            ['combinator' => $combinator],
        );
    }

    /**
     * Create exception for an invalid rule structure.
     *
     * @param array<int, int|string> $path
     * @param array<string, mixed>   $details
     */
    public static function invalidRuleStructure(
        string $reason = 'Invalid rule structure',
        array $path = [],
        array $details = [],
    ): self {
        return new self(
            $reason,
            'compile.invalid_rule_structure',
            'compile',
            $path,
            $details,
        );
    }

    /**
     * Create exception for an invalid NOT rule configuration.
     *
     * @param array<int, int|string> $path
     */
    public static function invalidNotRule(array $path = ['value']): self
    {
        return new self(
            'Logical NOT must have exactly one argument',
            'compile.invalid_not_arity',
            'compile',
            $path,
        );
    }

    /**
     * Create exception for cache key generation failures.
     */
    public static function invalidRuleCacheKey(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Unable to generate rule cache key: %s', $reason),
            'compile.cache_key_generation_failed',
            'compile',
            ['rules'],
            ['reason' => $reason],
            $previous,
        );
    }

    /**
     * Create exception for unknown operators encountered during compilation.
     *
     * @param array<int, int|string> $path
     */
    public static function unknownOperator(
        string $operator,
        string $field,
        array $path = ['operator'],
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf('Unknown operator: "%s"', $operator),
            'compile.unknown_operator',
            'compile',
            $path,
            [
                'operator' => $operator,
                'field' => $field,
            ],
            $previous,
        );
    }

    /**
     * Create exception for runtime evaluation failures.
     *
     * @param array<int, int|string> $path
     * @param array<string, mixed>   $details
     */
    public static function runtimeEvaluationFailed(
        string $reason,
        array $path = [],
        array $details = [],
        ?Throwable $previous = null,
    ): self {
        return new self(
            $reason,
            'runtime.evaluation_failed',
            'runtime',
            $path,
            $details,
            $previous,
        );
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }

    /**
     * @return array<int, int|string>
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array{
     *     message: string,
     *     errorCode: string,
     *     phase: string,
     *     path: array<int, int|string>,
     *     details: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errorCode' => $this->errorCode,
            'phase' => $this->phase,
            'path' => $this->path,
            'details' => $this->details,
        ];
    }
}
