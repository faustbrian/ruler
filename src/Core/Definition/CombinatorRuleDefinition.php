<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core\Definition;

use Cline\Ruler\Exceptions\InvalidNotRuleException;
use Cline\Ruler\Exceptions\InvalidRuleStructureException;

use function count;
use function sprintf;
use function throw_if;

/**
 * Typed rule definition for logical combinator nodes.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class CombinatorRuleDefinition implements RuleDefinition
{
    /**
     * @param RuleCombinator             $combinator Logical combinator for this node
     * @param array<int, RuleDefinition> $operands   Nested typed rule definitions
     */
    public function __construct(
        public RuleCombinator $combinator,
        public array $operands,
    ) {
        if ($this->combinator === RuleCombinator::Not) {
            throw_if(count($this->operands) !== 1, InvalidNotRuleException::create());
        }

        if ($this->combinator === RuleCombinator::Not) {
            return;
        }

        throw_if(
            count($this->operands) < 1,
            InvalidRuleStructureException::forReason(sprintf('Combinator "%s" requires at least one operand', $this->combinator->value)),
        );
    }
}
