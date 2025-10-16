<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

use InvalidArgumentException;

use function in_array;
use function mb_strlen;
use function mb_substr;
use function mb_trim;
use function preg_match;
use function sprintf;

/**
 * Lexer for LDAP filter syntax.
 *
 * Tokenizes LDAP filter strings according to RFC 4515 specification.
 * Handles parentheses, logical operators (&, |, !), and comparison items.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LDAPLexer
{
    /** @var string The input filter string being tokenized */
    private string $input;

    /** @var int Current position in the input string */
    private int $position = 0;

    /**
     * Create a new LDAP lexer.
     *
     * @param string $input LDAP filter string to tokenize. Whitespace is trimmed
     *                      from both ends before processing begins.
     */
    public function __construct(string $input)
    {
        $this->input = mb_trim($input);
    }

    /**
     * Tokenizes the LDAP filter string into tokens.
     *
     * Scans through the input character by character, identifying token types
     * and creating Token objects. Handles parentheses, logical operators,
     * whitespace, and comparison items.
     *
     * @throws InvalidArgumentException If invalid syntax is encountered
     *
     * @return array<int, Token> Array of tokens including EOF marker
     */
    public function tokenize(): array
    {
        $tokens = [];

        while ($this->position < mb_strlen($this->input)) {
            $char = $this->input[$this->position];

            if ($char === '(') {
                $tokens[] = new Token(Token::LPAREN, '(');
                ++$this->position;
            } elseif ($char === ')') {
                $tokens[] = new Token(Token::RPAREN, ')');
                ++$this->position;
            } elseif ($char === '&') {
                $tokens[] = new Token(Token::AND, '&');
                ++$this->position;
            } elseif ($char === '|') {
                $tokens[] = new Token(Token::OR, '|');
                ++$this->position;
            } elseif ($char === '!') {
                $tokens[] = new Token(Token::NOT, '!');
                ++$this->position;
            } elseif (in_array($char, [' ', "\t", "\n", "\r"], true)) {
                // Skip whitespace
                ++$this->position;
            } else {
                // Read comparison item
                $tokens[] = $this->readItem();
            }
        }

        $tokens[] = new Token(Token::EOF, null);

        return $tokens;
    }

    /**
     * Parses a comparison item string into a structured token.
     *
     * Uses regex to extract attribute name, operator, and value from the item string.
     * Supports all LDAP comparison operators: =, >=, <=, ~=, !=, >, <.
     *
     * @param string $item The comparison item string to parse
     *
     * @throws InvalidArgumentException If the item doesn't match expected format
     *
     * @return Token ITEM token with attribute, operator, and value fields
     */
    private static function parseItem(string $item): Token
    {
        $item = mb_trim($item);

        // Match: attribute operator value
        // Support operators: =, >=, <=, ~=, !=, >, <
        // Allow optional whitespace around operator
        if (preg_match('/^([a-zA-Z0-9._-]+)\s*(>=|<=|~=|!=|=|>|<)\s*(.*)$/', $item, $matches)) {
            return new Token(Token::ITEM, [
                'attribute' => $matches[1],
                'operator' => $matches[2],
                'value' => mb_trim($matches[3]),
            ]);
        }

        throw new InvalidArgumentException(sprintf('Invalid filter item: %s', $item));
    }

    /**
     * Reads a comparison item (attribute operator value).
     *
     * Scans forward from the current position until reaching a closing parenthesis
     * at depth 0, tracking nested parentheses along the way.
     *
     * @return Token ITEM token containing parsed attribute, operator, and value
     */
    private function readItem(): Token
    {
        $start = $this->position;
        $depth = 0;

        // Read until we hit a closing paren at depth 0
        while ($this->position < mb_strlen($this->input)) {
            $char = $this->input[$this->position];

            if ($char === '(' && $this->position !== $start) {
                ++$depth;
            } elseif ($char === ')' && $depth === 0) {
                break;
            } elseif ($char === ')') {
                --$depth;
            }

            ++$this->position;
        }

        $item = mb_substr($this->input, $start, $this->position - $start);

        return self::parseItem($item);
    }
}
