<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

/**
 * Represents a single token in SQL WHERE clause lexical analysis.
 *
 * Tokens are the atomic units produced by the lexer, containing type
 * information, the actual value (for literals and identifiers), and
 * position data for error reporting.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class Token
{
    /**
     * SQL keyword token (AND, OR, NOT, IN, LIKE, BETWEEN, etc.).
     */
    public const string KEYWORD = 'KEYWORD';

    /**
     * Field name or identifier token.
     */
    public const string IDENTIFIER = 'IDENTIFIER';

    /**
     * Numeric literal token (integer or float).
     */
    public const string NUMBER = 'NUMBER';

    /**
     * String literal token (single or double quoted).
     */
    public const string STRING = 'STRING';

    /**
     * Comparison operator token (=, !=, <, >, <=, >=).
     */
    public const string OPERATOR = 'OPERATOR';

    /**
     * Left parenthesis token.
     */
    public const string LPAREN = 'LPAREN';

    /**
     * Right parenthesis token.
     */
    public const string RPAREN = 'RPAREN';

    /**
     * Comma separator token (used in IN lists).
     */
    public const string COMMA = 'COMMA';

    /**
     * End-of-file marker token indicating end of input.
     */
    public const string EOF = 'EOF';

    /**
     * Create a new Token instance.
     *
     * @param string $type     Token type constant (e.g., Token::STRING, Token::KEYWORD)
     * @param mixed  $value    Token value - the actual string, number, or keyword.
     *                         For structural tokens (LPAREN, RPAREN, COMMA), this
     *                         typically contains the character itself.
     * @param int    $position character position in the source string where this
     *                         token begins, used for error reporting and debugging
     */
    public function __construct(
        public string $type,
        public mixed $value,
        public int $position,
    ) {}
}
