<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidFilterItemException extends ParserException
{
    public static function forItem(string $item): self
    {
        return new self(sprintf('Invalid filter item: %s', $item));
    }
}
