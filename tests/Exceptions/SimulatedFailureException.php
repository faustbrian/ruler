<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Tests\Exceptions;

use Exception;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class SimulatedFailureException extends Exception
{
    public static function create(): self
    {
        return new self('Simulated failure');
    }
}
