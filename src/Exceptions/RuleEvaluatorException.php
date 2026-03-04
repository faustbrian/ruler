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

/**
 * Exception thrown when proposition evaluation encounters invalid rule structures or combinators.
 *
 * This exception is raised during rule parsing/compilation and runtime evaluation
 * when the RuleEvaluator encounters malformed rule data, unrecognized operators,
 * or execution failures.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class RuleEvaluatorException extends Exception implements RulerException
{
    public const string ERROR_CONTRACT_VERSION = '1.0.0';

    /**
     * @param array<int, int|string> $path
     * @param array<string, mixed>   $details
     */
    protected function __construct(
        string $message,
        private readonly RuleErrorCode $errorCode,
        private readonly RuleErrorPhase $phase,
        private readonly array $path = [],
        private readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
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
