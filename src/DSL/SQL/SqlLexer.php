<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

use InvalidArgumentException;

use function ctype_alnum;
use function ctype_alpha;
use function ctype_digit;
use function in_array;
use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;
use function ord;
use function sprintf;

/**
 * Lexer for SQL WHERE clause expressions.
 *
 * Tokenizes SQL WHERE clause strings into a stream of tokens for parsing.
 * Handles keywords, identifiers, operators, strings, numbers, and punctuation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SqlLexer
{
    /**
     * SQL keywords recognized by the lexer (case-insensitive).
     *
     * @var array<int, string>
     */
    private const array KEYWORDS = [
        'AND', 'OR', 'NOT', 'IN', 'LIKE', 'BETWEEN', 'IS', 'NULL',
        'TRUE', 'FALSE',
    ];

    /**
     * SQL comparison operators recognized by the lexer.
     *
     * Two-character operators are checked before single-character ones
     * to ensure correct tokenization (e.g., != parsed as one token, not ! and =).
     *
     * @var array<int, string>
     */
    private const array OPERATORS = [
        '!=', '<>', '<=', '>=', '=', '<', '>',
    ];

    /**
     * The SQL input string being tokenized.
     */
    private string $input;

    /**
     * Current position in the input string (zero-based index).
     */
    private int $position = 0;

    /**
     * Total length of the input string in characters.
     */
    private int $length;

    /**
     * Tokenize SQL WHERE clause into array of tokens.
     *
     * Performs lexical analysis on the SQL input string, breaking it down into
     * a sequence of tokens (keywords, identifiers, operators, literals, etc.).
     * The token stream is terminated with an EOF token.
     *
     * @param string $sql SQL WHERE clause expression to tokenize. Should contain only
     *                    the WHERE clause content without the "WHERE" keyword itself.
     *
     * @throws InvalidArgumentException if invalid syntax is encountered, such as unterminated
     *                                  strings or unexpected characters that cannot be tokenized
     *
     * @return array<int, Token> Array of tokens in the order they appear in the input.
     *                           The last token is always Token::EOF to mark end of input.
     */
    public function tokenize(string $sql): array
    {
        $this->input = $sql;
        $this->position = 0;
        $this->length = mb_strlen($sql);

        $tokens = [];

        while ($this->position < $this->length) {
            $this->skipWhitespace();

            if ($this->position >= $this->length) {
                break;
            }

            $char = $this->currentChar();

            // String literal
            if ($char === "'") {
                $tokens[] = $this->readString();

                continue;
            }

            // Number
            if (ctype_digit($char) || ($char === '-' && $this->peekChar() !== null && ctype_digit($this->peekChar()))) {
                $tokens[] = $this->readNumber();

                continue;
            }

            // Parentheses
            if ($char === '(') {
                $tokens[] = new Token(Token::LPAREN, '(', $this->position);
                ++$this->position;

                continue;
            }

            if ($char === ')') {
                $tokens[] = new Token(Token::RPAREN, ')', $this->position);
                ++$this->position;

                continue;
            }

            // Comma
            if ($char === ',') {
                $tokens[] = new Token(Token::COMMA, ',', $this->position);
                ++$this->position;

                continue;
            }

            // Operators
            $operator = $this->readOperator();

            if ($operator instanceof Token) {
                $tokens[] = $operator;

                continue;
            }

            // Identifier or keyword
            if (ctype_alpha($char) || $char === '_') {
                $tokens[] = $this->readIdentifierOrKeyword();

                continue;
            }

            throw new InvalidArgumentException(sprintf('Unexpected character at position %d: %s', $this->position, $char));
        }

        $tokens[] = new Token(Token::EOF, null, $this->position);

        return $tokens;
    }

    /**
     * Get the character at the current position.
     *
     * @return string the character at the current position in the input string
     */
    private function currentChar(): string
    {
        return $this->input[$this->position];
    }

    /**
     * Peek at a character ahead of the current position without advancing.
     *
     * @param  null|int    $offset Number of positions to look ahead. Defaults to 1 (next character).
     * @return null|string the character at the offset position, or null if beyond end of input
     */
    private function peekChar(?int $offset = 1): ?string
    {
        $pos = $this->position + $offset;

        return $pos < $this->length ? $this->input[$pos] : null;
    }

    /**
     * Skip whitespace characters and advance position.
     *
     * Advances the position past all consecutive whitespace characters (spaces,
     * tabs, newlines, etc.) using ASCII values <= 32 as the whitespace test.
     */
    private function skipWhitespace(): void
    {
        while ($this->position < $this->length && ord($this->input[$this->position]) <= 32) {
            ++$this->position;
        }
    }

    /**
     * Read a string literal token.
     *
     * Parses a SQL string literal enclosed in single quotes. Handles escaped
     * quotes using the SQL convention where '' represents a literal single quote.
     *
     * @throws InvalidArgumentException if the string is not properly terminated before
     *                                  the end of input
     *
     * @return Token a STRING token containing the unescaped string value
     */
    private function readString(): Token
    {
        $start = $this->position;
        ++$this->position; // Skip opening quote

        $value = '';

        while ($this->position < $this->length) {
            $char = $this->currentChar();

            if ($char === "'") {
                // Check for escaped quote (SQL uses '' for ')
                if ($this->peekChar() === "'") {
                    $value .= "'";
                    $this->position += 2;

                    continue;
                }

                ++$this->position; // Skip closing quote

                return new Token(Token::STRING, $value, $start);
            }

            $value .= $char;
            ++$this->position;
        }

        throw new InvalidArgumentException(sprintf('Unterminated string starting at position %d', $start));
    }

    /**
     * Read a numeric literal token.
     *
     * Parses integer and floating-point numbers, including negative values.
     * Supports decimal notation with a single decimal point.
     *
     * @return Token a NUMBER token containing the parsed numeric value as int or float
     */
    private function readNumber(): Token
    {
        $start = $this->position;
        $value = '';

        // Handle negative sign
        if ($this->currentChar() === '-') {
            $value .= '-';
            ++$this->position;
        }

        $hasDecimal = false;

        while ($this->position < $this->length) {
            $char = $this->currentChar();

            if (ctype_digit($char)) {
                $value .= $char;
                ++$this->position;
            } elseif ($char === '.' && !$hasDecimal) {
                $hasDecimal = true;
                $value .= $char;
                ++$this->position;
            } else {
                break;
            }
        }

        $numValue = $hasDecimal ? (float) $value : (int) $value;

        return new Token(Token::NUMBER, $numValue, $start);
    }

    /**
     * Read a comparison operator token.
     *
     * Attempts to match two-character operators first (!=, <>, <=, >=), then
     * falls back to single-character operators (=, <, >). Returns null if no
     * operator is found at the current position.
     *
     * @return null|Token an OPERATOR token if an operator is found, null otherwise
     */
    private function readOperator(): ?Token
    {
        $start = $this->position;

        // Try two-character operators first
        if ($this->position + 1 < $this->length) {
            $twoChar = mb_substr($this->input, $this->position, 2);

            if (in_array($twoChar, self::OPERATORS, true)) {
                $this->position += 2;

                return new Token(Token::OPERATOR, $twoChar, $start);
            }
        }

        // Single-character operators
        $char = $this->currentChar();

        if (in_array($char, ['=', '<', '>'], true)) {
            ++$this->position;

            return new Token(Token::OPERATOR, $char, $start);
        }

        return null;
    }

    /**
     * Read an identifier or keyword token.
     *
     * Parses alphanumeric identifiers that may contain underscores and dots.
     * Checks if the identifier matches a SQL keyword (case-insensitive) and
     * returns the appropriate token type. Handles boolean literals TRUE/FALSE.
     *
     * @return Token a KEYWORD token if the identifier matches a SQL keyword or boolean,
     *               otherwise an IDENTIFIER token representing a field name
     */
    private function readIdentifierOrKeyword(): Token
    {
        $start = $this->position;
        $value = '';

        while ($this->position < $this->length) {
            $char = $this->currentChar();

            if (ctype_alnum($char) || $char === '_' || $char === '.') {
                $value .= $char;
                ++$this->position;
            } else {
                break;
            }
        }

        $upper = mb_strtoupper($value);

        // Check if it's a keyword (case-insensitive)
        // This includes TRUE and FALSE which are in the KEYWORDS array
        if (in_array($upper, self::KEYWORDS, true)) {
            return new Token(Token::KEYWORD, $upper, $start);
        }

        // It's an identifier (field name)
        return new Token(Token::IDENTIFIER, $value, $start);
    }
}
