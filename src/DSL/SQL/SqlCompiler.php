<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

use Cline\Ruler\Core\Proposition;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotIn;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\String\DoesNotMatch;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;
use InvalidArgumentException;

use function array_map;
use function mb_strlen;
use function preg_quote;
use function sprintf;
use function throw_unless;

/**
 * Compiles SQL WHERE clause AST to Ruler operator tree.
 *
 * Walks the AST from SqlParser and converts each node into the appropriate
 * Ruler operator or variable. Handles field resolution, operator mapping,
 * and LIKE pattern conversion.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class SqlCompiler
{
    /**
     * Create a new SqlCompiler instance.
     *
     * @param FieldResolver $fieldResolver Resolves dot-notation field paths to Variable instances.
     *                                     Used to map field references in the SQL AST to the appropriate
     *                                     data source paths during compilation, enabling support for
     *                                     nested field access and custom field resolution strategies.
     */
    public function __construct(
        private FieldResolver $fieldResolver,
    ) {}

    /**
     * Compile SQL AST into a Proposition.
     *
     * Transforms the parsed SQL WHERE clause abstract syntax tree into a
     * Proposition tree that can be evaluated against data. The compilation
     * process maps SQL operators to their corresponding Ruler operator classes.
     *
     * @param SqlNode $ast The root node of the abstract syntax tree produced by SqlParser.
     *                     Represents the complete SQL WHERE clause structure including all
     *                     nested operations and operands.
     *
     * @throws InvalidArgumentException if the AST root does not compile to a Proposition, which
     *                                  indicates invalid SQL structure or unsupported syntax
     *
     * @return Proposition The compiled Proposition tree ready for evaluation. Contains the
     *                     complete logical structure with all operators and variables resolved.
     */
    public function compile(SqlNode $ast): Proposition
    {
        $result = $this->compileNode($ast);

        throw_unless($result instanceof Proposition, InvalidArgumentException::class, 'SQL expression must compile to a Proposition');

        return $result;
    }

    /**
     * Compile a single AST node.
     *
     * Recursively processes AST nodes and converts them to their corresponding
     * Ruler components. Dispatches to specialized compilation methods based on
     * the node type.
     *
     * @param SqlNode $node the AST node to compile, which can be any SqlNode subtype
     *                      including field references, literals, comparisons, or logical operations
     *
     * @throws InvalidArgumentException if the node type is not recognized or supported
     *
     * @return Proposition|Variable The compiled component. Returns a Proposition for operations
     *                              that produce boolean results (comparisons, logical ops), or a
     *                              Variable for field references and literal values.
     */
    private function compileNode(SqlNode $node): mixed
    {
        return match (true) {
            $node instanceof LiteralNode => new Variable(null, $node->value),
            $node instanceof FieldNode => $this->fieldResolver->resolve($node->fieldName),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof InNode => $this->compileIn($node),
            $node instanceof BetweenNode => $this->compileBetween($node),
            $node instanceof LikeNode => $this->compileLike($node),
            $node instanceof NullNode => $this->compileNull($node),
            default => throw new InvalidArgumentException(sprintf('Unsupported node type: %s', $node::class)),
        };
    }

    /**
     * Compile a comparison node.
     *
     * Converts SQL comparison operators to their corresponding Ruler operator classes.
     * Supports standard SQL comparison operators including equality, inequality, and
     * relational comparisons.
     *
     * @param ComparisonNode $node the comparison node containing operator and operands
     *
     * @throws InvalidArgumentException if the comparison operator is not recognized or supported
     *
     * @return Proposition a Proposition instance representing the comparison operation
     *                     using the appropriate Ruler comparison operator (EqualTo, NotEqualTo,
     *                     GreaterThan, GreaterThanOrEqualTo, LessThan, LessThanOrEqualTo)
     */
    private function compileComparison(ComparisonNode $node): Proposition
    {
        $left = $this->compileNode($node->left);
        $right = $this->compileNode($node->right);

        return match ($node->operator) {
            '=' => new EqualTo($left, $right),
            '!=' => new NotEqualTo($left, $right),
            '<>' => new NotEqualTo($left, $right), // SQL alternative syntax
            '>' => new GreaterThan($left, $right),
            '>=' => new GreaterThanOrEqualTo($left, $right),
            '<' => new LessThan($left, $right),
            '<=' => new LessThanOrEqualTo($left, $right),
            default => throw new InvalidArgumentException(sprintf('Unsupported comparison operator: %s', $node->operator)),
        };
    }

    /**
     * Compile a logical node (AND, OR, NOT).
     *
     * Converts SQL logical operators to their corresponding Ruler operator classes.
     * Recursively compiles all operands before combining them with the logical operator.
     *
     * @param LogicalNode $node the logical node containing operator and child operands
     *
     * @throws InvalidArgumentException if the logical operator is not recognized or supported
     *
     * @return Proposition a Proposition instance representing the logical operation using
     *                     LogicalAnd, LogicalOr, or LogicalNot depending on the operator
     */
    private function compileLogical(LogicalNode $node): Proposition
    {
        $operands = array_map(
            function (SqlNode $operand): Proposition {
                $result = $this->compileNode($operand);

                return $result instanceof Proposition ? $result : throw new InvalidArgumentException('Expected Proposition');
            },
            $node->operands,
        );

        return match ($node->operator) {
            'AND' => new LogicalAnd($operands),
            'OR' => new LogicalOr($operands),
            'NOT' => new LogicalNot($operands),
            default => throw new InvalidArgumentException(sprintf('Unsupported logical operator: %s', $node->operator)),
        };
    }

    /**
     * Compile an IN or NOT IN node.
     *
     * Converts SQL IN/NOT IN operations to their corresponding Ruler operator classes.
     * The values array is wrapped in a Variable for evaluation.
     *
     * @param  InNode      $node the IN node containing the field, values array, and negation flag
     * @return Proposition a Proposition instance using either the In or NotIn operator
     *                     depending on the negation flag
     */
    private function compileIn(InNode $node): Proposition
    {
        $field = $this->compileNode($node->field);
        $values = new Variable(null, $node->values);

        return $node->negated
            ? new NotIn($field, $values)
            : new In($field, $values);
    }

    /**
     * Compile a BETWEEN node.
     *
     * Converts SQL BETWEEN operations to the Between operator. The BETWEEN
     * operator tests if a value falls within an inclusive range.
     *
     * @param  BetweenNode $node the BETWEEN node containing the field and min/max bounds
     * @return Proposition a Proposition instance using the Between operator that checks
     *                     if the field value satisfies: field >= min AND field <= max
     */
    private function compileBetween(BetweenNode $node): Proposition
    {
        $field = $this->compileNode($node->field);
        $min = $this->compileNode($node->min);
        $max = $this->compileNode($node->max);

        throw_unless($field instanceof VariableOperand, InvalidArgumentException::class, 'Between field must be VariableOperand');
        throw_unless($min instanceof VariableOperand, InvalidArgumentException::class, 'Between min must be VariableOperand');
        throw_unless($max instanceof VariableOperand, InvalidArgumentException::class, 'Between max must be VariableOperand');

        return new Between($field, $min, $max);
    }

    /**
     * Compile a LIKE or NOT LIKE node.
     *
     * Converts SQL LIKE patterns to regular expressions for pattern matching.
     * The SQL wildcards are translated to regex equivalents while preserving
     * escape sequences. The resulting regex uses anchors for full string matching.
     *
     * Pattern conversion rules:
     * - % (percent) becomes .* (match zero or more characters)
     * - _ (underscore) becomes . (match exactly one character)
     * - \% and \_ are escaped to match literal % and _ characters
     * - All other characters are escaped for regex safety
     *
     * @param  LikeNode    $node the LIKE node containing the field, pattern, and negation flag
     * @return Proposition a Proposition instance using either the Matches or DoesNotMatch
     *                     operator with the converted regex pattern, depending on the negation flag
     */
    private function compileLike(LikeNode $node): Proposition
    {
        $field = $this->compileNode($node->field);

        // Convert SQL LIKE pattern to regex
        // First, escape the pattern for regex (but preserve % and _)
        $pattern = $node->pattern;

        // Escape special regex characters except % and _
        $escaped = '';
        $length = mb_strlen($pattern);

        for ($i = 0; $i < $length; ++$i) {
            $char = $pattern[$i];

            if ($char === '%') {
                $escaped .= '.*';
            } elseif ($char === '_') {
                $escaped .= '.';
            } elseif ($char === '\\' && $i + 1 < $length) {
                // Handle escape sequences in SQL LIKE
                $nextChar = $pattern[$i + 1];

                if ($nextChar === '%' || $nextChar === '_') {
                    $escaped .= preg_quote($nextChar, '/');
                    ++$i; // Skip next character
                } else {
                    $escaped .= preg_quote($char, '/');
                }
            } else {
                $escaped .= preg_quote($char, '/');
            }
        }

        $regex = '/^'.$escaped.'$/';
        $regexVar = new Variable(null, $regex);

        return $node->negated
            ? new DoesNotMatch($field, $regexVar)
            : new Matches($field, $regexVar);
    }

    /**
     * Compile an IS NULL or IS NOT NULL node.
     *
     * Converts SQL null checks to equality comparisons with null values.
     * For IS NOT NULL, wraps the null check in a LogicalNot operator.
     *
     * @param  NullNode    $node the NULL node containing the field and negation flag
     * @return Proposition a Proposition instance using EqualTo to compare with null,
     *                     optionally wrapped in LogicalNot for IS NOT NULL operations
     */
    private function compileNull(NullNode $node): Proposition
    {
        $field = $this->compileNode($node->field);
        $nullValue = new Variable(null, null);

        $isNull = new EqualTo($field, $nullValue);

        return $node->negated
            ? new LogicalNot([$isNull])
            : $isNull;
    }
}
