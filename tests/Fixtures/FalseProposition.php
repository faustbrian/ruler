<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;

final class FalseProposition implements Proposition
{
    public function evaluate(Context $context): bool
    {
        return false;
    }
}
