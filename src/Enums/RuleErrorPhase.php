<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Enums;

/**
 * Rule engine lifecycle phases where failures can occur.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum RuleErrorPhase: string
{
    case Compile = 'compile';
    case Runtime = 'runtime';
}
