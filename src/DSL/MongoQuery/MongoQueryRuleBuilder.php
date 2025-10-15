<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\MongoQuery;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use InvalidArgumentException;
use JsonException;

use const JSON_THROW_ON_ERROR;

use function is_array;
use function json_decode;

/**
 * Builds executable rules from MongoDB-style query documents.
 *
 * Provides a high-level interface for converting MongoDB query syntax into
 * executable Rule objects. Supports both array and JSON string inputs, handling
 * the full compilation pipeline from query parsing to rule instantiation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class MongoQueryRuleBuilder
{
    /**
     * Internal rule builder for creating rule instances.
     */
    private RuleBuilder $ruleBuilder;

    /**
     * MongoDB query compiler for translating queries to propositions.
     */
    private MongoQueryCompiler $compiler;

    /**
     * Create a new MongoDB query rule builder.
     *
     * @param null|RuleBuilder $ruleBuilder Optional rule builder instance. If null, creates a new
     *                                      default RuleBuilder for managing variable context and
     *                                      rule creation during the compilation process.
     */
    public function __construct(?RuleBuilder $ruleBuilder = null)
    {
        $this->ruleBuilder = $ruleBuilder ?? new RuleBuilder();
        $this->compiler = new MongoQueryCompiler($this->ruleBuilder);
    }

    /**
     * Parse a MongoDB query document and return an executable Rule.
     *
     * Compiles a MongoDB-style query array into a Rule object that can be evaluated
     * against data contexts. The query supports MongoDB's standard operators plus
     * extended operators for advanced filtering.
     *
     * @param  array<string, mixed> $query MongoDB-style query document with fields and operators
     * @return Rule                 Compiled rule ready for evaluation against data
     */
    public function parse(array $query): Rule
    {
        $proposition = $this->compiler->compile($query);

        return $this->ruleBuilder->create($proposition);
    }

    /**
     * Parse a JSON-encoded MongoDB query string and return an executable Rule.
     *
     * Convenience method that accepts a JSON string representation of a MongoDB
     * query, decodes it, and compiles it into an executable Rule object.
     *
     * @param string $json JSON-encoded MongoDB query document
     *
     * @throws JsonException If JSON string is malformed or cannot be decoded
     *
     * @return Rule Compiled rule ready for evaluation against data
     */
    public function parseJson(string $json): Rule
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string, mixed> $query */
        $query = is_array($decoded) ? $decoded : throw new InvalidArgumentException('JSON must decode to array');

        return $this->parse($query);
    }
}
