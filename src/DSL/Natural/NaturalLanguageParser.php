<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Natural;

use InvalidArgumentException;

use function array_map;
use function count;
use function explode;
use function in_array;
use function is_numeric;
use function mb_substr;
use function mb_trim;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

/**
 * Parses human-readable rule expressions into abstract syntax trees.
 *
 * Converts natural language rule expressions like "age is greater than 18 and
 * status equals active" into structured AST nodes that can be compiled into
 * executable propositions. Supports logical operators (and, or), comparison
 * operators, range checks (between), list membership (one of), and string
 * operations (contains, starts with, ends with).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NaturalLanguageParser
{
    /**
     * Parse a natural language expression into an AST.
     *
     * @param string $text Human-readable rule expression to parse
     *
     * @throws InvalidArgumentException If expression cannot be parsed
     *
     * @return array<string, mixed> AST root node representing the parsed expression
     */
    public function parse(string $text): array
    {
        $text = self::normalize($text);

        return $this->parseExpression($text);
    }

    /**
     * Normalize input text by trimming and collapsing whitespace.
     *
     * @param  string $text Raw input text to normalize
     * @return string Normalized text with single spaces between words
     */
    private static function normalize(string $text): string
    {
        $text = mb_trim($text);

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    /**
     * Parse a single condition (non-logical expression).
     *
     * Attempts to match the text against known condition patterns in order
     * of specificity, trying string operations first, then comparisons.
     *
     * @param string $text Condition text to parse
     *
     * @throws InvalidArgumentException If no pattern matches the condition
     *
     * @return array<string, mixed> AST node for the condition
     */
    private static function parseCondition(string $text): array
    {
        // Try patterns in order of specificity
        // Note: between and list membership already tried in parseExpression
        if (($node = self::tryParseStringOperation($text)) !== null && ($node = self::tryParseStringOperation($text)) !== []) {
            return $node;
        }

        if (($node = self::tryParseComparison($text)) !== null && ($node = self::tryParseComparison($text)) !== []) {
            return $node;
        }

        throw new InvalidArgumentException('Could not parse condition: '.$text);
    }

    /**
     * Attempt to parse a "between" range expression.
     *
     * Matches patterns like "age is between 18 and 65" or "score is from 0 to 100".
     *
     * @param  string                    $text Expression text to parse
     * @return null|array<string, mixed> AST node if matched, null otherwise
     */
    private static function tryParseBetween(string $text): ?array
    {
        $pattern = '/^(\w+(?:\.\w+)*)\s+is\s+(?:between|from)\s+([^\s]+)\s+(?:and|to)\s+([^\s]+)$/';

        if (preg_match($pattern, $text, $matches)) {
            return [
                'type' => 'between',
                'field' => $matches[1],
                'min' => self::parseValue(mb_trim($matches[2])),
                'max' => self::parseValue(mb_trim($matches[3])),
            ];
        }

        return null;
    }

    /**
     * Attempt to parse a list membership expression.
     *
     * Matches patterns like "status is either active or pending" or
     * "color is one of red, green, blue" or "type is not one of admin, moderator".
     *
     * @param  string                    $text Expression text to parse
     * @return null|array<string, mixed> AST node if matched, null otherwise
     */
    private static function tryParseListMembership(string $text): ?array
    {
        // "X is either A or B" - must come first to avoid conflict with "one of"
        $pattern = '/^(\w+(?:\.\w+)*)\s+is\s+either\s+(.+?)\s+or\s+(.+?)$/';

        if (preg_match($pattern, $text, $matches)) {
            return [
                'type' => 'in',
                'field' => $matches[1],
                'values' => [
                    self::parseValue(mb_trim($matches[2])),
                    self::parseValue(mb_trim($matches[3])),
                ],
                'negated' => false,
            ];
        }

        // "X is one of A, B, C"
        $pattern = '/^(\w+(?:\.\w+)*)\s+is\s+(not\s+)?one\s+of\s+(.+)$/';

        if (preg_match($pattern, $text, $matches)) {
            $values = array_map(
                fn ($v): mixed => self::parseValue(mb_trim($v)),
                explode(',', $matches[3]),
            );

            return [
                'type' => 'in',
                'field' => $matches[1],
                'values' => $values,
                'negated' => !in_array(mb_trim($matches[2]), ['', '0'], true),
            ];
        }

        return null;
    }

    /**
     * Attempt to parse a string operation expression.
     *
     * Matches patterns like "name contains John", "email starts with admin",
     * or "filename ends with .pdf". Supports both quoted and unquoted values.
     *
     * @param  string                    $text Expression text to parse
     * @return null|array<string, mixed> AST node if matched, null otherwise
     */
    private static function tryParseStringOperation(string $text): ?array
    {
        $operations = [
            'contains' => 'contains',
            'includes' => 'contains',
            'starts with' => 'startsWith',
            'begins with' => 'startsWith',
            'ends with' => 'endsWith',
        ];

        foreach ($operations as $phrase => $operation) {
            // Try with quotes first
            $pattern = '/^(\w+(?:\.\w+)*)\s+'.preg_quote($phrase, '/').'\s+"([^"]+)"$/';

            if (preg_match($pattern, $text, $matches)) {
                return [
                    'type' => 'string',
                    'operation' => $operation,
                    'field' => $matches[1],
                    'value' => $matches[2],
                ];
            }

            // Try without quotes (unquoted string)
            $pattern = '/^(\w+(?:\.\w+)*)\s+'.preg_quote($phrase, '/').'\s+(.+)$/';

            if (preg_match($pattern, $text, $matches)) {
                return [
                    'type' => 'string',
                    'operation' => $operation,
                    'field' => $matches[1],
                    'value' => mb_trim($matches[2], '"'),
                ];
            }
        }

        return null;
    }

    /**
     * Attempt to parse a comparison expression.
     *
     * Matches patterns like "age is greater than 18", "count is at least 5",
     * or "status is not active". Handles both standard and negated comparisons.
     *
     * @param  string                    $text Expression text to parse
     * @return null|array<string, mixed> AST node if matched, null otherwise
     */
    private static function tryParseComparison(string $text): ?array
    {
        // Handle negated comparisons first (more specific)
        $negatedOperators = [
            'is not less than or equal to' => 'gt',
            'is not less than' => 'gte',
            'is not greater than or equal to' => 'lt',
            'is not greater than' => 'lte',
            'is not at least' => 'lt',
            'is not at most' => 'gt',
            'is not more than' => 'lte',
        ];

        foreach ($negatedOperators as $phrase => $operator) {
            $pattern = '/^(\w+(?:\.\w+)*)\s+'.preg_quote($phrase, '/').'\s+(.+)$/';

            if (preg_match($pattern, $text, $matches)) {
                return [
                    'type' => 'comparison',
                    'operator' => $operator,
                    'field' => $matches[1],
                    'value' => self::parseValue($matches[2]),
                ];
            }
        }

        // Then handle standard comparisons
        $operators = [
            'is at least' => 'gte',
            'is greater than or equal to' => 'gte',
            'is more than' => 'gt',
            'is greater than' => 'gt',
            'is at most' => 'lte',
            'is less than or equal to' => 'lte',
            'is less than' => 'lt',
            'is not' => 'ne',
            'does not equal' => 'ne',
            'equals' => 'eq',
            'is' => 'eq',
        ];

        foreach ($operators as $phrase => $operator) {
            $pattern = '/^(\w+(?:\.\w+)*)\s+'.preg_quote($phrase, '/').'\s+(.+)$/';

            if (preg_match($pattern, $text, $matches)) {
                return [
                    'type' => 'comparison',
                    'operator' => $operator,
                    'field' => $matches[1],
                    'value' => self::parseValue($matches[2]),
                ];
            }
        }

        return null;
    }

    /**
     * Parse a string value into its appropriate PHP type.
     *
     * Recognizes booleans (true/false, yes/no), null, quoted strings,
     * numbers (int/float), and defaults to unquoted strings.
     *
     * @param  string $value String value to parse
     * @return mixed  Parsed value in appropriate PHP type
     */
    private static function parseValue(string $value): mixed
    {
        $value = mb_trim($value);

        // Boolean
        if ($value === 'true' || $value === 'yes') {
            return true;
        }

        if ($value === 'false' || $value === 'no') {
            return false;
        }

        // Null
        if ($value === 'null') {
            return null;
        }

        // String (remove quotes)
        if (preg_match('/^"([^"]*)"$/', $value, $matches)) {
            return $matches[1];
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Default to string
        return $value;
    }

    /**
     * Split text by a logical operator, respecting parenthesis depth.
     *
     * Splits on "and" or "or" keywords only when they appear at the top level
     * (outside parentheses), preserving grouped expressions.
     *
     * @param  string             $text     Text to split
     * @param  string             $operator Logical operator keyword to split on ("and" or "or")
     * @return array<int, string> Array of split parts, or single-element array if no split occurred
     */
    private static function splitByLogical(string $text, string $operator): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        $words = explode(' ', $text);

        foreach ($words as $word) {
            if ($word === '(') {
                ++$depth;
            }

            if ($word === ')') {
                --$depth;
            }

            if ($word === $operator && $depth === 0) {
                if (!in_array(mb_trim($current), ['', '0'], true)) {
                    $parts[] = mb_trim($current);
                }

                $current = '';
            } else {
                $current .= $word.' ';
            }
        }

        if (!in_array(mb_trim($current), ['', '0'], true)) {
            $parts[] = mb_trim($current);
        }

        return count($parts) > 1 ? $parts : [$text];
    }

    /**
     * Check if text has matching parentheses at the outermost level.
     *
     * @param  string $text Text to check
     * @return bool   True if text starts with '(' and ends with ')'
     */
    private static function hasTopLevelParentheses(string $text): bool
    {
        return str_starts_with($text, '(') && str_ends_with($text, ')');
    }

    /**
     * Parse an expression, handling logical operators and parentheses.
     *
     * Processes expressions recursively, respecting operator precedence
     * (parentheses > special patterns > OR > AND > conditions). Special
     * patterns like "between" and "one of" are parsed before logical
     * operator splitting to avoid conflicts with embedded "and"/"or" keywords.
     *
     * @param string $text Normalized expression text to parse
     *
     * @throws InvalidArgumentException If expression cannot be parsed
     *
     * @return array<string, mixed> AST node for the expression
     */
    private function parseExpression(string $text): array
    {
        // Handle parentheses first
        if (self::hasTopLevelParentheses($text)) {
            $inner = mb_substr($text, 1, -1);

            return $this->parseExpression($inner);
        }

        // Try special patterns that use "and"/"or" keywords internally (like "between")
        // These must be tried BEFORE splitting by logical operators
        if (($node = self::tryParseBetween($text)) !== null && ($node = self::tryParseBetween($text)) !== []) {
            return $node;
        }

        if (($node = self::tryParseListMembership($text)) !== null && ($node = self::tryParseListMembership($text)) !== []) {
            return $node;
        }

        // Split by OR (lowest precedence)
        $orParts = self::splitByLogical($text, 'or');

        if (count($orParts) > 1) {
            return [
                'type' => 'logical',
                'operator' => 'or',
                'conditions' => array_map(fn ($part): array => $this->parseExpression($part), $orParts),
            ];
        }

        // Split by AND (higher precedence)
        $andParts = self::splitByLogical($text, 'and');

        if (count($andParts) > 1) {
            return [
                'type' => 'logical',
                'operator' => 'and',
                'conditions' => array_map(fn ($part): array => $this->parseExpression($part), $andParts),
            ];
        }

        // Parse single condition
        return self::parseCondition($text);
    }
}
