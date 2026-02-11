<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use Cline\Ruler\Enums\RuleErrorCode;
use Cline\Ruler\Enums\RuleErrorPhase;
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
    public const string ERROR_CONTRACT_VERSION = '1.0.0';

    /**
     * @param array<int, int|string> $path
     * @param array<string, mixed>   $details
     */
    private function __construct(
        string $message,
        private readonly RuleErrorCode $errorCode,
        private readonly RuleErrorPhase $phase,
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
            RuleErrorCode::CompileInvalidCombinator,
            RuleErrorPhase::Compile,
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
            RuleErrorCode::CompileInvalidRuleStructure,
            RuleErrorPhase::Compile,
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
            RuleErrorCode::CompileInvalidNotArity,
            RuleErrorPhase::Compile,
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
            RuleErrorCode::CompileCacheKeyGenerationFailed,
            RuleErrorPhase::Compile,
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
            RuleErrorCode::CompileUnknownOperator,
            RuleErrorPhase::Compile,
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
            RuleErrorCode::RuntimeEvaluationFailed,
            RuleErrorPhase::Runtime,
            $path,
            $details,
            $previous,
        );
    }

    public function getErrorCode(): RuleErrorCode
    {
        return $this->errorCode;
    }

    public function getPhase(): RuleErrorPhase
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
     *     contractVersion: string,
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
            'contractVersion' => self::ERROR_CONTRACT_VERSION,
            'message' => $this->getMessage(),
            'errorCode' => $this->errorCode->value,
            'phase' => $this->phase->value,
            'path' => $this->path,
            'details' => $this->details,
        ];
    }
}
