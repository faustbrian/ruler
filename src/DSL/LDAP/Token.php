<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

/**
 * Token representation for LDAP filter lexer.
 *
 * Represents individual lexical tokens produced during LDAP filter parsing.
 * Each token captures a syntactic element of the filter expression, such as
 * parentheses, logical operators, or filter items.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class Token
{
    /**
     * Left parenthesis token type.
     */
    public const string LPAREN = 'LPAREN';

    /**
     * Right parenthesis token type.
     */
    public const string RPAREN = 'RPAREN';

    /**
     * AND logical operator token type.
     */
    public const string AND = 'AND';

    /**
     * OR logical operator token type.
     */
    public const string OR = 'OR';

    /**
     * NOT logical operator token type.
     */
    public const string NOT = 'NOT';

    /**
     * ITEM token type representing a filter item expression.
     */
    public const string ITEM = 'ITEM';

    /**
     * End of file token type.
     */
    public const string EOF = 'EOF';

    /**
     * Create a new token.
     *
     * @param string $type  Token type identifier, one of the class constants (LPAREN, RPAREN,
     *                      AND, OR, NOT, ITEM, EOF). Determines the syntactic meaning of this
     *                      token in the LDAP filter grammar.
     * @param mixed  $value Token payload value whose meaning depends on the token type. For
     *                      ITEM tokens, contains the filter expression string. For operators
     *                      and delimiters, typically null or the matched character. For EOF,
     *                      always null.
     */
    public function __construct(
        public string $type,
        public mixed $value,
    ) {}
}
