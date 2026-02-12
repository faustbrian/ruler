<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Controls optional compatibility transforms applied before rule compilation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum CompatibilityMode: string
{
    case Strict = 'strict';
    case Legacy = 'legacy';
}
