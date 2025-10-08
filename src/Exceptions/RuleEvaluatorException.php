<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use Exception;

use function sprintf;

/**
 * Exception thrown when proposition evaluation encounters invalid rule structures or combinators.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RuleEvaluatorException extends Exception
{
    /**
     * Create exception for an invalid logical combinator.
     *
     * Thrown when the rule evaluator encounters a combinator that is not
     * recognized or supported (e.g., not AND, OR, or NOT).
     *
     * @param  string $combinator The invalid combinator value that was encountered
     * @return self   New exception instance with descriptive error message
     */
    public static function invalidCombinator(string $combinator): self
    {
        return new self(sprintf('Invalid combinator: %s', $combinator));
    }

    /**
     * Create exception for an invalid rule structure.
     *
     * Thrown when the rule data structure does not conform to the expected
     * format for proposition evaluation, such as missing required keys or
     * malformed nested structures.
     *
     * @return self New exception instance with generic structure error message
     */
    public static function invalidRuleStructure(): self
    {
        return new self('Invalid rule structure');
    }

    /**
     * Create exception for an invalid NOT rule configuration.
     *
     * Thrown when a logical NOT operation is provided with an incorrect number
     * of arguments. NOT operations must have exactly one argument to negate.
     *
     * @return self New exception instance with NOT-specific error message
     */
    public static function invalidNotRule(): self
    {
        return new self('Logical NOT must have exactly one argument');
    }
}
