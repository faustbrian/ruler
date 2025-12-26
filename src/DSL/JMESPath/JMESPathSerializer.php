<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\JMESPath;

use Cline\Ruler\Core\Rule;
use LogicException;
use ReflectionClass;

use function is_string;
use function throw_unless;

/**
 * Serializes Rule objects back to JMESPath filter expression strings.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to human-readable JMESPath filter syntax. Since JMESPath uses a different
 * approach (wrapping entire filter expressions in JMESPathProposition),
 * this serializer extracts the original expression.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $serializer = new JMESPathSerializer();
 * $parser = new JMESPathParser();
 *
 * $rule = $parser->parse('user.age >= `18` && user.country == `"US"`');
 * $expression = $serializer->serialize($rule);
 * // Returns: 'user.age >= `18` && user.country == `"US"`'
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see JMESPathParser For parsing DSL strings into Rules
 * @see JMESPathValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class JMESPathSerializer
{
    /**
     * Serialize a Rule to a JMESPath filter expression string.
     *
     * Extracts the JMESPath expression from a Rule's JMESPathProposition.
     * Since JMESPath expressions are stored as-is in the proposition,
     * this simply retrieves the original expression string.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When the rule doesn't contain a JMESPathProposition
     *
     * @return string The JMESPath filter expression
     */
    public function serialize(Rule $rule): string
    {
        $reflection = new ReflectionClass($rule);
        $conditionProperty = $reflection->getProperty('condition');

        /** @var mixed $condition */
        $condition = $conditionProperty->getValue($rule);

        throw_unless($condition instanceof JMESPathProposition, LogicException::class, 'Rule must contain a JMESPathProposition to serialize to JMESPath');

        return $this->extractExpression($condition);
    }

    /**
     * Extract the expression from a JMESPathProposition.
     *
     * @param  JMESPathProposition $proposition The proposition to extract from
     * @return string              The JMESPath expression
     */
    private function extractExpression(JMESPathProposition $proposition): string
    {
        $reflection = new ReflectionClass($proposition);
        $expressionProperty = $reflection->getProperty('expression');
        $expression = $expressionProperty->getValue($proposition);

        throw_unless(is_string($expression), LogicException::class, 'Expression must be a string');

        return $expression;
    }
}
