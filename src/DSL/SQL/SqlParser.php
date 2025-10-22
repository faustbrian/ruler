<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

use InvalidArgumentException;
use RuntimeException;

use function count;
use function in_array;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * Parser for SQL WHERE clause expressions.
 *
 * Parses token streams from SqlLexer into an Abstract Syntax Tree (AST).
 * Implements recursive descent parsing with proper operator precedence.
 *
 * Operator precedence (low to high):
 * 1. OR
 * 2. AND
 * 3. NOT
 * 4. Comparison operators (=, !=, <, >, <=, >=, LIKE, IN, BETWEEN, IS NULL)
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SqlParser
{
    /**
     * Token stream from lexer.
     *
     * @var array<int, Token>
     */
    private array $tokens;

    /**
     * Current position in token stream.
     */
    private int $position = 0;

    /**
     * Parse SQL WHERE clause into AST.
     *
     * @param string $sql SQL WHERE clause expression
     *
     * @throws InvalidArgumentException if syntax is invalid
     *
     * @return SqlNode Root node of the AST
     */
    public function parse(string $sql): SqlNode
    {
        $lexer = new SqlLexer();
        $this->tokens = $lexer->tokenize($sql);
        $this->position = 0;

        $ast = $this->parseOr();

        // Ensure we've consumed all tokens except EOF
        if (!$this->isAtEnd()) {
            $token = $this->current();
            $tokenValue = is_scalar($token->value) ? (string) $token->value : $token->type;

            throw new InvalidArgumentException(
                sprintf('Unexpected token at position %d: %s', $token->position, $tokenValue),
            );
        }

        return $ast;
    }

    /**
     * Parse OR expression (lowest precedence).
     *
     * Handles left-associative OR operations, building a chain of LogicalNode
     * instances for multiple OR conditions.
     *
     * @return SqlNode The parsed OR expression node
     */
    private function parseOr(): SqlNode
    {
        $left = $this->parseAnd();

        while ($this->matchKeyword('OR')) {
            $right = $this->parseAnd();
            $left = new LogicalNode('OR', [$left, $right]);
        }

        return $left;
    }

    /**
     * Parse AND expression.
     *
     * Handles left-associative AND operations with higher precedence than OR.
     * Builds LogicalNode chains for multiple AND conditions.
     *
     * @return SqlNode The parsed AND expression node
     */
    private function parseAnd(): SqlNode
    {
        $left = $this->parseNot();

        while ($this->matchKeyword('AND')) {
            $right = $this->parseNot();
            $left = new LogicalNode('AND', [$left, $right]);
        }

        return $left;
    }

    /**
     * Parse NOT expression.
     *
     * Distinguishes between logical NOT and multi-word operators like NOT IN
     * and NOT LIKE through lookahead. Implements right-associative NOT parsing
     * for nested negations.
     *
     * @return SqlNode The parsed NOT expression or comparison node
     */
    private function parseNot(): SqlNode
    {
        // Check for NOT as logical operator (not part of NOT IN, NOT LIKE, IS NOT NULL)
        if ($this->checkKeyword('NOT')) {
            // Lookahead to see if this is part of a multi-word operator
            $nextToken = $this->peek(1);

            if ($nextToken instanceof Token && $nextToken->type === Token::KEYWORD) {
                $nextKeyword = $nextToken->value;

                if (in_array($nextKeyword, ['IN', 'LIKE'], true)) {
                    // This is NOT IN or NOT LIKE, let parseComparison handle it
                    // @codeCoverageIgnoreStart
                    return $this->parseComparison();
                    // @codeCoverageIgnoreEnd
                }
            }

            // This is a logical NOT
            $this->advance(); // consume NOT
            $operand = $this->parseNot(); // NOT is right-associative

            return new LogicalNode('NOT', [$operand]);
        }

        return $this->parseComparison();
    }

    /**
     * Parse comparison expression.
     *
     * Handles comparison operators (=, !=, <, >, <=, >=), special operators
     * (IS NULL, IS NOT NULL, BETWEEN, IN, NOT IN, LIKE, NOT LIKE), and
     * parenthesized expressions. This is the highest precedence level.
     *
     * @return SqlNode The parsed comparison or primary node
     */
    private function parseComparison(): SqlNode
    {
        // Handle parentheses
        if ($this->match(Token::LPAREN)) {
            $node = $this->parseOr();
            $this->consume(Token::RPAREN, 'Expected closing parenthesis');

            return $node;
        }

        $left = $this->parsePrimary();

        // IS NULL / IS NOT NULL
        if ($this->matchKeyword('IS')) {
            $negated = $this->matchKeyword('NOT');
            $this->consumeKeyword('NULL', 'Expected NULL after IS');

            return new NullNode($left, $negated);
        }

        // BETWEEN
        if ($this->matchKeyword('BETWEEN')) {
            $min = $this->parsePrimary();
            $this->consumeKeyword('AND', 'Expected AND in BETWEEN expression');
            $max = $this->parsePrimary();

            return new BetweenNode($left, $min, $max);
        }

        // IN / NOT IN
        if ($this->checkKeyword('NOT')) {
            $nextToken = $this->peek(1);

            if ($nextToken instanceof Token && $nextToken->type === Token::KEYWORD) {
                if ($nextToken->value === 'IN') {
                    $this->advance();
                    // consume NOT
                    $this->advance();
                    // consume IN
                    $values = $this->parseValueList();

                    return new InNode($left, $values, true);
                }

                if ($nextToken->value === 'LIKE') {
                    $this->advance();
                    // consume NOT
                    $this->advance();
                    // consume LIKE
                    $pattern = $this->consumeString('Expected string pattern after LIKE');

                    return new LikeNode($left, $pattern, true);
                }
            }
        }

        if ($this->matchKeyword('IN')) {
            $values = $this->parseValueList();

            return new InNode($left, $values, false);
        }

        if ($this->matchKeyword('LIKE')) {
            $pattern = $this->consumeString('Expected string pattern after LIKE');

            return new LikeNode($left, $pattern, false);
        }

        // Comparison operators
        if ($this->check(Token::OPERATOR)) {
            $token = $this->advance();

            /** @var string $operator */
            $operator = is_string($token->value) ? $token->value : throw new RuntimeException('Operator must be string');
            $right = $this->parsePrimary();

            return new ComparisonNode($operator, $left, $right);
        }

        // @codeCoverageIgnoreStart
        // Defensive: If no comparison operator found, return the parsed primary
        // This path is theoretically unreachable in valid SQL WHERE clauses
        return $left;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Parse primary expression (field, literal, or parenthesized expression).
     *
     * Handles atomic values including numbers, strings, booleans (TRUE/FALSE),
     * NULL, identifiers (field names), and recursively parses parenthesized
     * subexpressions.
     *
     * @throws InvalidArgumentException When encountering an unexpected token
     *
     * @return SqlNode The parsed primary node
     */
    private function parsePrimary(): SqlNode
    {
        // Handle parentheses
        if ($this->match(Token::LPAREN)) {
            $node = $this->parseOr();
            $this->consume(Token::RPAREN, 'Expected closing parenthesis');

            return $node;
        }

        // Number literal
        if ($this->check(Token::NUMBER)) {
            return new LiteralNode($this->advance()->value);
        }

        // String literal
        if ($this->check(Token::STRING)) {
            return new LiteralNode($this->advance()->value);
        }

        // Boolean literals
        if ($this->matchKeyword('TRUE')) {
            return new LiteralNode(true);
        }

        if ($this->matchKeyword('FALSE')) {
            return new LiteralNode(false);
        }

        // NULL literal
        if ($this->matchKeyword('NULL')) {
            return new LiteralNode(null);
        }

        // Identifier (field name)
        if ($this->check(Token::IDENTIFIER)) {
            $token = $this->advance();

            /** @var string $fieldName */
            $fieldName = is_string($token->value) ? $token->value : throw new RuntimeException('Field name must be string');

            return new FieldNode($fieldName);
        }

        $token = $this->current();
        $tokenValue = is_scalar($token->value) ? (string) $token->value : $token->type;

        throw new InvalidArgumentException(
            sprintf('Unexpected token at position %d: %s', $token->position, $tokenValue),
        );
    }

    /**
     * Parse value list for IN operator: (value1, value2, value3).
     *
     * @return array<int, mixed>
     */
    private function parseValueList(): array
    {
        $this->consume(Token::LPAREN, 'Expected opening parenthesis for value list');

        $values = [];

        do {
            // Parse literal value
            if ($this->check(Token::STRING)) {
                $values[] = $this->advance()->value;
            } elseif ($this->check(Token::NUMBER)) {
                $values[] = $this->advance()->value;
            } elseif ($this->matchKeyword('TRUE')) {
                $values[] = true;
            } elseif ($this->matchKeyword('FALSE')) {
                $values[] = false;
            } elseif ($this->matchKeyword('NULL')) {
                $values[] = null;
            } else {
                throw new InvalidArgumentException('Expected literal value in IN list');
            }
        } while ($this->match(Token::COMMA));

        $this->consume(Token::RPAREN, 'Expected closing parenthesis for value list');

        return $values;
    }

    /**
     * Check if current token matches type without consuming.
     *
     * @param  string $type The token type constant (e.g., Token::STRING, Token::NUMBER)
     * @return bool   True if current token matches the type, false otherwise
     */
    private function check(string $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->current()->type === $type;
    }

    /**
     * Check if current token is a keyword with specific value.
     *
     * @param  string $keyword The keyword value to match (e.g., 'AND', 'OR', 'NULL')
     * @return bool   True if current token is the specified keyword, false otherwise
     */
    private function checkKeyword(string $keyword): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        $token = $this->current();

        return $token->type === Token::KEYWORD && $token->value === $keyword;
    }

    /**
     * Match and consume token if type matches.
     *
     * @param  string $type The token type to match
     * @return bool   True if token was matched and consumed, false otherwise
     */
    private function match(string $type): bool
    {
        if ($this->check($type)) {
            $this->advance();

            return true;
        }

        return false;
    }

    /**
     * Match and consume keyword token if value matches.
     *
     * @param  string $keyword The keyword value to match
     * @return bool   True if keyword was matched and consumed, false otherwise
     */
    private function matchKeyword(string $keyword): bool
    {
        if ($this->checkKeyword($keyword)) {
            $this->advance();

            return true;
        }

        return false;
    }

    /**
     * Consume token of specific type or throw error.
     *
     * @param string $type    The required token type
     * @param string $message Error message to use if token doesn't match
     *
     * @throws InvalidArgumentException When current token doesn't match expected type
     *
     * @return Token The consumed token
     */
    private function consume(string $type, string $message): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        $token = $this->current();

        throw new InvalidArgumentException(
            sprintf('%s at position %d', $message, $token->position),
        );
    }

    /**
     * Consume keyword token or throw error.
     *
     * @param string $keyword The required keyword value
     * @param string $message Error message to use if keyword doesn't match
     *
     * @throws InvalidArgumentException When current token is not the expected keyword
     *
     * @return Token The consumed keyword token
     */
    private function consumeKeyword(string $keyword, string $message): Token
    {
        if ($this->checkKeyword($keyword)) {
            return $this->advance();
        }

        $token = $this->current();

        throw new InvalidArgumentException(
            sprintf('%s at position %d', $message, $token->position),
        );
    }

    /**
     * Consume string token and return its value.
     *
     * @param string $message Error message to use if current token is not a string
     *
     * @throws InvalidArgumentException When current token is not a string
     *
     * @return string The string value from the consumed token
     */
    private function consumeString(string $message): string
    {
        $token = $this->consume(Token::STRING, $message);

        return is_string($token->value) ? $token->value : throw new RuntimeException('String token value must be string');
    }

    /**
     * Get current token without consuming.
     *
     * @return Token The token at current position
     */
    private function current(): Token
    {
        return $this->tokens[$this->position];
    }

    /**
     * Peek ahead at token at offset from current position.
     *
     * @param  int        $offset Number of positions ahead to peek (1 for next token)
     * @return null|Token The token at the offset position, or null if beyond stream end
     */
    private function peek(int $offset): ?Token
    {
        $pos = $this->position + $offset;

        return $pos < count($this->tokens) ? $this->tokens[$pos] : null;
    }

    /**
     * Advance to next token and return current.
     *
     * Increments position pointer and returns the token that was current
     * before advancing. Does not advance past EOF token.
     *
     * @return Token The token at current position before advancing
     */
    private function advance(): Token
    {
        $token = $this->current();

        if (!$this->isAtEnd()) {
            ++$this->position;
        }

        return $token;
    }

    /**
     * Check if we're at the end of token stream.
     *
     * @return bool True if current token is EOF, false otherwise
     */
    private function isAtEnd(): bool
    {
        return $this->current()->type === Token::EOF;
    }
}
