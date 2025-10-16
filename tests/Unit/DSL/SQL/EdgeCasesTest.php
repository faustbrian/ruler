<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\SQL\SqlWhereRuleBuilder;

test('handles escaped quotes in strings', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("name = 'O''Brien'");

    expect($rule->evaluate(
        new Context(['name' => "O'Brien"]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'OBrien']),
        ))->toBeFalse();
});

test('handles whitespace correctly', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('  age   >=   18  ');

    expect($rule->evaluate(
        new Context(['age' => 20]),
    ))->toBeTrue();
});

test('handles case insensitive keywords', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule1 = $srb->parse("age >= 18 AND country = 'US'");
    $rule2 = $srb->parse("age >= 18 and country = 'US'");
    $rule3 = $srb->parse("age >= 18 AnD country = 'US'");

    $context = new Context(['age' => 25, 'country' => 'US']);

    expect($rule1->evaluate($context))->toBeTrue()
        ->and($rule2->evaluate($context))->toBeTrue()
        ->and($rule3->evaluate($context))->toBeTrue();
});

test('handles like with underscore wildcard', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("code LIKE 'A_C'");

    expect($rule->evaluate(
        new Context(['code' => 'ABC']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'ADC']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'ABBC']),
        ))->toBeFalse()
        ->and($rule->evaluate(
            new Context(['code' => 'AC']),
        ))->toBeFalse();
});

test('handles like with multiple wildcards', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("name LIKE '%John%Doe%'");

    expect($rule->evaluate(
        new Context(['name' => 'John Smith Doe']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'John Doe']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Jane Doe']),
        ))->toBeFalse();
});

test('handles like pattern at start', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("name LIKE 'John%'");

    expect($rule->evaluate(
        new Context(['name' => 'John Smith']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Smith John']),
        ))->toBeFalse();
});

test('handles like pattern at end', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("email LIKE '%@gmail.com'");

    expect($rule->evaluate(
        new Context(['email' => 'user@gmail.com']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['email' => 'user@yahoo.com']),
        ))->toBeFalse();
});

test('handles complex nested parentheses', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("((age > 18 AND age < 30) OR age > 65) AND status = 'active'");

    $context1 = new Context(['age' => 25, 'status' => 'active']);
    $context2 = new Context(['age' => 70, 'status' => 'active']);
    $context3 = new Context(['age' => 40, 'status' => 'active']);
    $context4 = new Context(['age' => 25, 'status' => 'inactive']);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeFalse()
        ->and($rule->evaluate($context4))->toBeFalse();
});

test('handles operator precedence without parentheses', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // Should parse as: a = 1 OR (b = 2 AND c = 3)
    $rule = $srb->parse('a = 1 OR b = 2 AND c = 3');

    $context1 = new Context(['a' => 1, 'b' => 0, 'c' => 0]);
    $context2 = new Context(['a' => 0, 'b' => 2, 'c' => 3]);
    $context3 = new Context(['a' => 0, 'b' => 2, 'c' => 0]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeFalse();
});

test('handles boolean literals', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule1 = $srb->parse('active = TRUE');
    $rule2 = $srb->parse('disabled = FALSE');

    expect($rule1->evaluate(
        new Context(['active' => true]),
    ))->toBeTrue()
        ->and($rule1->evaluate(
            new Context(['active' => false]),
        ))->toBeFalse()
        ->and($rule2->evaluate(
            new Context(['disabled' => false]),
        ))->toBeTrue()
        ->and($rule2->evaluate(
            new Context(['disabled' => true]),
        ))->toBeFalse();
});

test('handles negative numbers', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('temperature < -10');

    expect($rule->evaluate(
        new Context(['temperature' => -15]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['temperature' => -5]),
        ))->toBeFalse();
});

test('handles decimal numbers', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('price >= 99.99');

    expect($rule->evaluate(
        new Context(['price' => 100.50]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['price' => 99.98]),
        ))->toBeFalse();
});

test('handles in with mixed types', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("value IN (1, 'two', TRUE, NULL)");

    expect($rule->evaluate(
        new Context(['value' => 1]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 'two']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => true]),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => null]),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 'three']),
        ))->toBeFalse();
});

test('handles multiple not operators', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('NOT NOT age >= 18');

    $context1 = new Context(['age' => 25]);
    $context2 = new Context(['age' => 15]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeFalse();
});

test('handles complex and or precedence', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // Should parse as: (a AND b) OR (c AND d)
    $rule = $srb->parse('a = 1 AND b = 2 OR c = 3 AND d = 4');

    $context1 = new Context(['a' => 1, 'b' => 2, 'c' => 0, 'd' => 0]);
    $context2 = new Context(['a' => 0, 'b' => 0, 'c' => 3, 'd' => 4]);
    $context3 = new Context(['a' => 1, 'b' => 0, 'c' => 0, 'd' => 4]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeFalse();
});

test('handles special characters in like pattern', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("name LIKE '%[test]%'");

    expect($rule->evaluate(
        new Context(['name' => 'foo[test]bar']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'foobar']),
        ))->toBeFalse();
});

test('validate method returns true for valid sql', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect($srb->validate('age >= 18'))->toBeTrue()
        ->and($srb->validate("status = 'active'"))->toBeTrue()
        ->and($srb->validate("age >= 18 AND country = 'US'"))->toBeTrue();
});

test('validate method returns false for invalid sql', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect($srb->validate('age >='))->toBeFalse()
        ->and($srb->validate('AND'))->toBeFalse()
        ->and($srb->validate('age >= 18 AND'))->toBeFalse();
});

test('handles between with edge values', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('value BETWEEN 10 AND 20');

    expect($rule->evaluate(
        new Context(['value' => 10]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 15]),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 20]),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 9]),
        ))->toBeFalse()
        ->and($rule->evaluate(
            new Context(['value' => 21]),
        ))->toBeFalse();
});

test('throws exception for unexpected character', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse('age @ 18'))->toThrow(InvalidArgumentException::class);
});

test('throws exception for unterminated string', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse("name = 'John"))->toThrow(InvalidArgumentException::class);
});

test('handles escaped percent in like pattern', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("name LIKE 'test\\%value'");

    expect($rule->evaluate(
        new Context(['name' => 'test%value']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'testAvalue']),
        ))->toBeFalse();
});

test('handles escaped underscore in like pattern', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("code LIKE 'A\\_C'");

    expect($rule->evaluate(
        new Context(['code' => 'A_C']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'ABC']),
        ))->toBeFalse();
});

test('handles backslash followed by regular character in like pattern', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("text LIKE 'hello\\nworld'");

    expect($rule->evaluate(
        new Context(['text' => 'hello\\nworld']),
    ))->toBeTrue();
});

test('throws exception for unexpected token at end', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse('age >= 18 EXTRA'))->toThrow(InvalidArgumentException::class);
});

test('throws exception for NOT IN lookahead', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("status NOT IN ('banned', 'suspended')");

    expect($rule->evaluate(
        new Context(['status' => 'active']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'banned']),
        ))->toBeFalse();
});

test('throws exception for NOT LIKE lookahead', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse("email NOT LIKE '%@spam.com'");

    expect($rule->evaluate(
        new Context(['email' => 'user@example.com']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['email' => 'spammer@spam.com']),
        ))->toBeFalse();
});

test('throws exception for unexpected token in primary', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse('age = ,'))->toThrow(InvalidArgumentException::class);
});

test('throws exception for expected comma in value list', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse("status IN ('active' 'pending')"))->toThrow(InvalidArgumentException::class);
});

test('throws exception for missing closing paren in value list', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse("status IN ('active', 'pending'"))->toThrow(InvalidArgumentException::class);
});

test('throws exception for consume keyword mismatch', function (): void {
    $srb = new SqlWhereRuleBuilder();

    expect(fn (): Rule => $srb->parse('age BETWEEN 10'))->toThrow(InvalidArgumentException::class);
});

test('parse IS NOT NULL comparison', function (): void {
    $srb = new SqlWhereRuleBuilder();
    $rule = $srb->parse('email IS NOT NULL');

    expect($rule->evaluate(
        new Context(['email' => 'user@example.com']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'John']),
        ))->toBeFalse();
});

test('parse TRUE and FALSE literals in IN clause', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // This tests SqlLexer lines 355, 359 for TRUE/FALSE keyword handling
    $rule = $srb->parse('status IN (TRUE, FALSE)');

    expect($rule->evaluate(
        new Context(['status' => true]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => false]),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'active']),
        ))->toBeFalse();
});

test('parse NULL comparison without operator returns field value', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // This tests SqlParser line 242 - when a field IS NULL (special case)
    $rule = $srb->parse('deletedAt IS NULL');

    expect($rule->evaluate(
        new Context(['deletedAt' => null]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['deletedAt' => '2024-01-01']),
        ))->toBeFalse();
});

test('parse deeply nested parentheses in primary position', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // This tests SqlParser lines 266-269 - parenthesized expression in parsePrimary()
    // The key is that parentheses appear where a value is expected
    $rule = $srb->parse('status = (TRUE)');

    expect($rule->evaluate(
        new Context(['status' => true]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => false]),
        ))->toBeFalse();
});

test('parse NULL literal in comparison', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // This tests SqlParser line 287 - NULL literal handling
    $rule = $srb->parse('status = NULL');

    expect($rule->evaluate(
        new Context(['status' => null]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'active']),
        ))->toBeFalse();
});

test('parse FALSE literal in IN value list', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // This tests SqlParser line 328 - FALSE in value list
    $rule = $srb->parse('value IN (FALSE, 0)');

    expect($rule->evaluate(
        new Context(['value' => false]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 0]),
        ))->toBeTrue();
});

test('throws exception for identifier in IN value list', function (): void {
    $srb = new SqlWhereRuleBuilder();
    // This tests SqlParser line 332 - invalid literal in IN list
    expect(fn (): Rule => $srb->parse('status IN (active, pending)'))->toThrow(InvalidArgumentException::class);
});
