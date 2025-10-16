<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\JMESPath;

use JmesPath\AstRuntime;
use JmesPath\CompilerRuntime;
use JmesPath\Env as JmesPath;

use function in_array;
use function is_bool;
use function is_numeric;

/**
 * Adapter for evaluating JMESPath expressions as boolean conditions.
 *
 * JMESPathAdapter wraps the JMESPath library to enable JMESPath query expressions
 * to be used as rule conditions. It evaluates JMESPath queries against data structures
 * and converts the results to boolean values using JavaScript-style truthiness rules,
 * making it suitable for filtering and conditional logic.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class JMESPathAdapter
{
    /** @var AstRuntime|CompilerRuntime JMESPath runtime instance for evaluating query expressions */
    private AstRuntime|CompilerRuntime $jmespath;

    /**
     * Create a new JMESPath adapter instance.
     *
     * Initializes the JMESPath AST runtime for efficient query evaluation.
     * The runtime compiles and caches query expressions for performance.
     */
    public function __construct()
    {
        $runtime = JmesPath::createRuntime();

        /** @var AstRuntime|CompilerRuntime $runtime */
        $this->jmespath = $runtime;
    }

    /**
     * Evaluate a JMESPath expression against data and return boolean result.
     *
     * Executes a JMESPath query expression against the provided data structure
     * and converts the result to a boolean value using JavaScript-style truthiness
     * rules. This enables JMESPath queries to be used as conditional expressions
     * in rule evaluation.
     *
     * @param  string               $expression JMESPath query expression defining the data selection
     *                                          and transformation logic. Supports the full JMESPath
     *                                          specification including projections, filters, functions,
     *                                          and multi-select operations.
     * @param  array<string, mixed> $data       Data structure to query. Typically an associative array
     *                                          or nested structure representing the evaluation context.
     *                                          JMESPath queries navigate this structure to extract and
     *                                          transform values.
     * @return bool                 Boolean result of the query. True when the expression returns a truthy value
     *                              according to JavaScript-style rules: non-null, non-empty array, non-empty string,
     *                              non-zero number, or explicit true boolean. False for null, empty array, empty
     *                              string, zero, or explicit false boolean.
     */
    public function evaluate(string $expression, array $data): bool
    {
        $result = ($this->jmespath)($expression, $data);

        return self::toBoolean($result);
    }

    /**
     * Convert a JMESPath query result to a boolean value.
     *
     * Applies JavaScript-style truthiness rules to convert query results into
     * boolean values suitable for conditional logic. Treats null, empty arrays,
     * empty strings, and zero as falsy values; all other values as truthy.
     *
     * @param  mixed $result the result value from a JMESPath query evaluation
     * @return bool  true for truthy values (non-null, non-empty, non-zero), false for falsy values
     */
    private static function toBoolean(mixed $result): bool
    {
        if (is_bool($result)) {
            return $result;
        }

        if (in_array($result, [null, [], ''], true)) {
            return false;
        }

        if (is_numeric($result)) {
            return $result !== 0;
        }

        return true;
    }
}
