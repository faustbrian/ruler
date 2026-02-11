<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

final class ExpectedLiteralValueException extends ParserException
{
    public static function inContext(string $context): self
    {
        return new self(sprintf('Expected literal value in %s', $context));
    }
}
