<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\JmesPath;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Stringable;

/**
 * Proposition implementation using JMESPath expression evaluation.
 *
 * Evaluates JMESPath query expressions against Context data to produce
 * boolean results. Converts Context key-value pairs into an associative
 * array for JMESPath processing.
 *
 * ```php
 * $proposition = new JmesPathProposition(
 *     'user.age >= `18`',
 *     new JmesPathAdapter()
 * );
 * $result = $proposition->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class JmesPathProposition implements Proposition, Stringable
{
    /**
     * Create a new JMESPath proposition.
     *
     * @param string          $expression JMESPath query expression that evaluates to a boolean result.
     *                                    Supports the full JMESPath specification including filters,
     *                                    projections, and comparison operators for complex data queries.
     * @param JmesPathAdapter $adapter    adapter instance that wraps the JMESPath library and handles
     *                                    expression compilation and evaluation against data structures
     */
    public function __construct(
        private string $expression,
        private JmesPathAdapter $adapter,
    ) {}

    /**
     * Returns string representation of the proposition.
     *
     * @return string Formatted string with JMESPath label and expression
     */
    public function __toString(): string
    {
        return 'JMESPath: '.$this->expression;
    }

    /**
     * Evaluates the JMESPath expression against context data.
     *
     * Extracts all key-value pairs from the Context into an associative array,
     * then evaluates the JMESPath expression against that data structure.
     *
     * @param  Context $context The evaluation context containing data to query
     * @return bool    True if the JMESPath expression evaluates to a truthy value
     */
    public function evaluate(Context $context): bool
    {
        $data = [];

        foreach ($context->keys() as $key) {
            $data[$key] = $context[$key];
        }

        return $this->adapter->evaluate($this->expression, $data);
    }
}
