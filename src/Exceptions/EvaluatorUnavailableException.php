<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use LogicException;

final class EvaluatorUnavailableException extends LogicException implements RulerException
{
    public static function compilationFailed(): self
    {
        return new self('Compilation failed; evaluator is unavailable.');
    }
}
