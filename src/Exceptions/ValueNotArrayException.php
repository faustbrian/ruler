<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

final class ValueNotArrayException extends OperatorException
{
    public static function forOperation(string $operation): self
    {
        return new self(sprintf('%s: second operand must be an array', $operation));
    }
}
