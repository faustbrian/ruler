<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedEndOfInputException extends ParserException
{
    public static function create(): self
    {
        return new self('Unexpected end of input');
    }
}
