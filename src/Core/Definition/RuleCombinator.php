<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core\Definition;

/**
 * Supported logical combinators for typed rule definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum RuleCombinator: string
{
    case And = 'and';
    case Or = 'or';
    case Xor = 'xor';
    case Not = 'not';
}
