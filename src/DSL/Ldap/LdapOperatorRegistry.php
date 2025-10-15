<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Ldap;

use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use InvalidArgumentException;

use function array_key_exists;
use function sprintf;
use function throw_unless;

/**
 * Registry mapping LDAP operators to Ruler operator classes.
 *
 * Provides centralized mapping between LDAP filter operator symbols and their
 * corresponding Ruler operator implementations. Supports both comparison
 * operators (=, >=, etc.) and logical operators (and, or, not).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LdapOperatorRegistry
{
    /** @var array<string, class-string> Mapping of LDAP comparison operators to Ruler classes */
    private const array COMPARISON_MAP = [
        '=' => EqualTo::class,
        '>=' => GreaterThanOrEqualTo::class,
        '<=' => LessThanOrEqualTo::class,
        '>' => GreaterThan::class,
        '<' => LessThan::class,
        '!=' => NotEqualTo::class,
    ];

    /** @var array<string, class-string> Mapping of LDAP logical operators to Ruler classes */
    private const array LOGICAL_MAP = [
        'and' => LogicalAnd::class,
        'or' => LogicalOr::class,
        'not' => LogicalNot::class,
    ];

    /**
     * Retrieves Ruler operator class for LDAP comparison operator.
     *
     * @param string $operator LDAP comparison operator symbol: '=', '>=', '<=', '>', '<', or '!='
     *
     * @throws InvalidArgumentException If the operator is not recognized
     *
     * @return class-string Fully qualified class name of the Ruler comparison operator
     */
    public function getComparison(string $operator): string
    {
        throw_unless(array_key_exists($operator, self::COMPARISON_MAP), InvalidArgumentException::class, sprintf('Unknown comparison operator: %s', $operator));

        return self::COMPARISON_MAP[$operator];
    }

    /**
     * Retrieves Ruler operator class for LDAP logical operator.
     *
     * @param string $operator LDAP logical operator name: 'and', 'or', or 'not'
     *
     * @throws InvalidArgumentException If the operator is not recognized
     *
     * @return class-string Fully qualified class name of the Ruler logical operator
     */
    public function getLogical(string $operator): string
    {
        throw_unless(array_key_exists($operator, self::LOGICAL_MAP), InvalidArgumentException::class, sprintf('Unknown logical operator: %s', $operator));

        return self::LOGICAL_MAP[$operator];
    }
}
