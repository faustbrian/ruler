<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

use RuntimeException;

use function count;
use function sprintf;
use function str_contains;
use function throw_if;

/**
 * Parser for LDAP filter syntax.
 *
 * Implements recursive descent parsing for LDAP filters (RFC 4515).
 * Converts token stream from LDAPLexer into an Abstract Syntax Tree (AST).
 *
 * Grammar:
 * filter     ::= "(" filtercomp ")"
 * filtercomp ::= and | or | not | item
 * and        ::= "&" filterlist
 * or         ::= "|" filterlist
 * not        ::= "!" filter
 * filterlist ::= filter+
 * item       ::= simple | present | substring
 * simple     ::= attr filtertype value
 * filtertype ::= "=" | "~=" | ">=" | "<=" | ">" | "<" | "!="
 * present    ::= attr "=*"
 * substring  ::= attr "=" value (contains *)
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LDAPParser
{
    /** @var array<int, Token> Token stream from lexer */
    private array $tokens;

    /** @var int Current position in the token stream */
    private int $position = 0;

    /**
     * Parses LDAP filter string into AST.
     *
     * Tokenizes the input filter string and then recursively parses the token
     * stream to build an Abstract Syntax Tree.
     *
     * @param string $filter LDAP filter expression following RFC 4515 syntax
     *
     * @throws RuntimeException If the filter syntax is invalid
     *
     * @return LDAPNode Root node of the AST representing the entire filter
     */
    public function parse(string $filter): LDAPNode
    {
        $lexer = new LDAPLexer($filter);
        $this->tokens = $lexer->tokenize();
        $this->position = 0;

        return $this->parseFilter();
    }

    /**
     * Parses a filter: "(" filtercomp ")".
     *
     * Expects opening parenthesis, delegates to appropriate parser based on token
     * type, then expects closing parenthesis.
     *
     * @throws RuntimeException If unexpected token is encountered
     *
     * @return LDAPNode The parsed filter node
     */
    private function parseFilter(): LDAPNode
    {
        $this->expect(Token::LPAREN);

        $token = $this->current();

        $node = match ($token->type) {
            Token::AND => $this->parseAnd(),
            Token::OR => $this->parseOr(),
            Token::NOT => $this->parseNot(),
            Token::ITEM => $this->parseItem(),
            default => throw new RuntimeException(sprintf('Unexpected token: %s', $token->type)),
        };

        $this->expect(Token::RPAREN);

        return $node;
    }

    /**
     * Parses AND operator: "&" filterlist.
     *
     * Consumes the '&' token and then parses all following filters until
     * reaching a closing parenthesis.
     *
     * @throws RuntimeException If no conditions are provided
     *
     * @return LogicalNode LogicalNode representing AND operation
     */
    private function parseAnd(): LogicalNode
    {
        $this->advance(); // consume '&'

        $conditions = [];

        while ($this->current()->type !== Token::RPAREN) {
            $conditions[] = $this->parseFilter();
        }

        throw_if($conditions === [], RuntimeException::class, 'AND operator requires at least one condition');

        return new LogicalNode('and', $conditions);
    }

    /**
     * Parses OR operator: "|" filterlist.
     *
     * Consumes the '|' token and then parses all following filters until
     * reaching a closing parenthesis.
     *
     * @throws RuntimeException If no conditions are provided
     *
     * @return LogicalNode LogicalNode representing OR operation
     */
    private function parseOr(): LogicalNode
    {
        $this->advance(); // consume '|'

        $conditions = [];

        while ($this->current()->type !== Token::RPAREN) {
            $conditions[] = $this->parseFilter();
        }

        throw_if($conditions === [], RuntimeException::class, 'OR operator requires at least one condition');

        return new LogicalNode('or', $conditions);
    }

    /**
     * Parses NOT operator: "!" filter.
     *
     * Consumes the '!' token and then parses the single negated filter.
     *
     * @return LogicalNode LogicalNode representing NOT operation
     */
    private function parseNot(): LogicalNode
    {
        $this->advance(); // consume '!'

        return new LogicalNode('not', [$this->parseFilter()]);
    }

    /**
     * Parses comparison item: simple | present | substring.
     *
     * Determines the specific type of comparison based on the operator and value,
     * creating the appropriate node type (PresenceNode, WildcardNode,
     * ApproximateNode, or ComparisonNode).
     *
     * @return LDAPNode The parsed comparison node
     */
    private function parseItem(): LDAPNode
    {
        $item = $this->current()->value;
        $this->advance();

        /** @var array{attribute: string, operator: string, value: string} $item */
        $attribute = $item['attribute'];
        $operator = $item['operator'];
        $value = $item['value'];

        // Handle presence check: field=*
        if ($value === '*' && $operator === '=') {
            return new PresenceNode($attribute);
        }

        // Handle wildcards: field=*value*, field=prefix*, field=*suffix
        if (str_contains((string) $value, '*')) {
            return new WildcardNode($attribute, $value);
        }

        // Handle approximate match
        if ($operator === '~=') {
            return new ApproximateNode($attribute, $value);
        }

        // Regular comparison
        return new ComparisonNode($operator, $attribute, $value);
    }

    /**
     * Retrieves current token without consuming it.
     *
     * @throws RuntimeException If at end of token stream
     *
     * @return Token The current token
     */
    private function current(): Token
    {
        throw_if($this->position >= count($this->tokens), RuntimeException::class, 'Unexpected end of input');

        return $this->tokens[$this->position];
    }

    /**
     * Advances to the next token.
     *
     * Increments the position counter to move forward in the token stream.
     */
    private function advance(): void
    {
        ++$this->position;
    }

    /**
     * Expects specific token type and consumes it.
     *
     * Validates that the current token matches the expected type before consuming.
     *
     * @param string $type Expected token type constant from Token class
     *
     * @throws RuntimeException If current token doesn't match expected type
     */
    private function expect(string $type): void
    {
        $token = $this->current();
        throw_if($token->type !== $type, RuntimeException::class, sprintf('Expected %s, got %s', $type, $token->type));

        $this->advance();
    }
}
