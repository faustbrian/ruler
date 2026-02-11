<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

final class CannotExtractFieldNameException extends SerializerException
{
    public static function forType(string $type): self
    {
        return new self(sprintf('Cannot extract field name from: %s', $type));
    }
}
