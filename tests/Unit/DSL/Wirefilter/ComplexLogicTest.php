<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\DSL\Wirefilter\StringRuleBuilder;

test('complex nested AND OR with multiple levels', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((age >= 18 and age < 65) and (country == "US" or country == "CA")) and not (status == "banned")',
    );

    $validContext = new Context(['age' => 30, 'country' => 'US', 'status' => 'active']);
    $bannedContext = new Context(['age' => 30, 'country' => 'US', 'status' => 'banned']);
    $youngContext = new Context(['age' => 16, 'country' => 'US', 'status' => 'active']);
    $wrongCountryContext = new Context(['age' => 30, 'country' => 'UK', 'status' => 'active']);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($bannedContext))->toBeFalse()
        ->and($rule->evaluate($youngContext))->toBeFalse()
        ->and($rule->evaluate($wrongCountryContext))->toBeFalse();
});

test('complex XOR logic', function (): void {
    $srb = new StringRuleBuilder();

    // XOR requires exactly one condition to be true
    $rule = $srb->parse('(isPremium == true) xor (credits > 100)');

    $premiumWithCredits = new Context(['isPremium' => true, 'credits' => 150]);
    $premiumNoCredits = new Context(['isPremium' => true, 'credits' => 50]);
    $noPremiumWithCredits = new Context(['isPremium' => false, 'credits' => 150]);
    $noPremiumNoCredits = new Context(['isPremium' => false, 'credits' => 50]);

    expect($rule->evaluate($premiumWithCredits))->toBeFalse()  // Both true = false
        ->and($rule->evaluate($premiumNoCredits))->toBeTrue()   // One true = true
        ->and($rule->evaluate($noPremiumWithCredits))->toBeTrue()  // One true = true
        ->and($rule->evaluate($noPremiumNoCredits))->toBeFalse();  // Both false = false
});

test('deeply nested parentheses with mixed operators', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '(((a == 1 or b == 2) and (c == 3 or d == 4)) or ((e == 5 and f == 6) or (g == 7 and h == 8)))',
    );

    // First group true: a=1, c=3
    $context1 = new Context(['a' => 1, 'b' => 0, 'c' => 3, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0]);
    // Second group true: e=5, f=6
    $context2 = new Context(['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 5, 'f' => 6, 'g' => 0, 'h' => 0]);
    // Third group true: g=7, h=8
    $context3 = new Context(['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 7, 'h' => 8]);
    // None true
    $context4 = new Context(['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeTrue()
        ->and($rule->evaluate($context4))->toBeFalse();
});

test('NOT with deeply nested conditions', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        'not ((age < 18 or age > 65) or (status == "banned" or status == "suspended"))',
    );

    $validContext = new Context(['age' => 30, 'status' => 'active']);
    $youngContext = new Context(['age' => 16, 'status' => 'active']);
    $oldContext = new Context(['age' => 70, 'status' => 'active']);
    $bannedContext = new Context(['age' => 30, 'status' => 'banned']);
    $suspendedContext = new Context(['age' => 30, 'status' => 'suspended']);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($youngContext))->toBeFalse()
        ->and($rule->evaluate($oldContext))->toBeFalse()
        ->and($rule->evaluate($bannedContext))->toBeFalse()
        ->and($rule->evaluate($suspendedContext))->toBeFalse();
});

test('complex mathematical expressions in conditionals', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((price * quantity) - discount > 1000) and ((price + shipping) < 5000)',
    );

    $validContext = new Context(['price' => 100, 'quantity' => 15, 'discount' => 200, 'shipping' => 50]);
    $lowTotalContext = new Context(['price' => 50, 'quantity' => 10, 'discount' => 200, 'shipping' => 50]);
    $highPriceContext = new Context(['price' => 1_000, 'quantity' => 15, 'discount' => 200, 'shipping' => 4_500]);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($lowTotalContext))->toBeFalse()  // First condition fails
        ->and($rule->evaluate($highPriceContext))->toBeFalse();  // Second condition fails
});

test('mixed AND OR NOT with mathematical operators', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '(price + shipping > 100 or discount > 50) and not (quantity < 1)',
    );

    $highPriceContext = new Context(['price' => 80, 'shipping' => 30, 'discount' => 10, 'quantity' => 5]);
    $highDiscountContext = new Context(['price' => 50, 'shipping' => 20, 'discount' => 60, 'quantity' => 3]);
    $zeroQuantityContext = new Context(['price' => 120, 'shipping' => 30, 'discount' => 10, 'quantity' => 0]);
    $failsAllContext = new Context(['price' => 50, 'shipping' => 20, 'discount' => 10, 'quantity' => 5]);

    expect($rule->evaluate($highPriceContext))->toBeTrue()
        ->and($rule->evaluate($highDiscountContext))->toBeTrue()
        ->and($rule->evaluate($zeroQuantityContext))->toBeFalse()  // quantity < 1
        ->and($rule->evaluate($failsAllContext))->toBeFalse();  // First condition fails
});

test('complex precedence without parentheses', function (): void {
    $srb = new StringRuleBuilder();
    // AND has higher precedence than OR
    $rule = $srb->parse('a == 1 or b == 2 and c == 3');

    // Should evaluate as: a == 1 OR (b == 2 AND c == 3)
    $aTrue = new Context(['a' => 1, 'b' => 0, 'c' => 0]);
    $bcTrue = new Context(['a' => 0, 'b' => 2, 'c' => 3]);
    $bTrueOnly = new Context(['a' => 0, 'b' => 2, 'c' => 0]);
    $nonTrue = new Context(['a' => 0, 'b' => 0, 'c' => 0]);

    expect($rule->evaluate($aTrue))->toBeTrue()
        ->and($rule->evaluate($bcTrue))->toBeTrue()
        ->and($rule->evaluate($bTrueOnly))->toBeFalse()
        ->and($rule->evaluate($nonTrue))->toBeFalse();
});

test('multiple NOT operators chained', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse('not (not (age >= 18))');

    // Double negation should equal original condition
    $adultContext = new Context(['age' => 25]);
    $minorContext = new Context(['age' => 15]);

    expect($rule->evaluate($adultContext))->toBeTrue()
        ->and($rule->evaluate($minorContext))->toBeFalse();
});

test('complex real-world eligibility check', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((age >= 18 and age <= 65) and (country == "US" or country == "CA")) and '.
        '((income > 50000 and creditScore >= 700) or (hasGuarantor == true and guarantorCreditScore >= 750)) and '.
        'not (hasBankruptcy == true or hasForeclosure == true)',
    );

    $qualifiedContext = new Context([
        'age' => 30,
        'country' => 'US',
        'income' => 60_000,
        'creditScore' => 720,
        'hasGuarantor' => false,
        'guarantorCreditScore' => 0,
        'hasBankruptcy' => false,
        'hasForeclosure' => false,
    ]);

    $qualifiedWithGuarantorContext = new Context([
        'age' => 25,
        'country' => 'CA',
        'income' => 40_000,
        'creditScore' => 650,
        'hasGuarantor' => true,
        'guarantorCreditScore' => 780,
        'hasBankruptcy' => false,
        'hasForeclosure' => false,
    ]);

    $bankruptcyContext = new Context([
        'age' => 30,
        'country' => 'US',
        'income' => 80_000,
        'creditScore' => 750,
        'hasGuarantor' => false,
        'guarantorCreditScore' => 0,
        'hasBankruptcy' => true,
        'hasForeclosure' => false,
    ]);

    $lowIncomeContext = new Context([
        'age' => 30,
        'country' => 'US',
        'income' => 30_000,
        'creditScore' => 650,
        'hasGuarantor' => false,
        'guarantorCreditScore' => 0,
        'hasBankruptcy' => false,
        'hasForeclosure' => false,
    ]);

    expect($rule->evaluate($qualifiedContext))->toBeTrue()
        ->and($rule->evaluate($qualifiedWithGuarantorContext))->toBeTrue()
        ->and($rule->evaluate($bankruptcyContext))->toBeFalse()
        ->and($rule->evaluate($lowIncomeContext))->toBeFalse();
});

test('stress test with very deep nesting', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((((a == 1 and b == 2) or (c == 3 and d == 4)) and '.
        '((e == 5 and f == 6) or (g == 7 and h == 8))) or '.
        '(((i == 9 and j == 10) or (k == 11 and l == 12)) and '.
        '((m == 13 and n == 14) or (o == 15 and p == 16))))',
    );

    $matchFirstPath = new Context([
        'a' => 1, 'b' => 2, 'c' => 0, 'd' => 0,
        'e' => 5, 'f' => 6, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0, 'o' => 0, 'p' => 0,
    ]);

    $matchSecondPath = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 9, 'j' => 10, 'k' => 0, 'l' => 0,
        'm' => 13, 'n' => 14, 'o' => 0, 'p' => 0,
    ]);

    $matchNone = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0, 'o' => 0, 'p' => 0,
    ]);

    expect($rule->evaluate($matchFirstPath))->toBeTrue()
        ->and($rule->evaluate($matchSecondPath))->toBeTrue()
        ->and($rule->evaluate($matchNone))->toBeFalse();
});

test('extreme depth with 12 levels of nesting', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((((((((((((a == 1) and (b == 2)) or (c == 3)) and (d == 4)) or (e == 5)) and (f == 6)) or (g == 7)) and (h == 8)) or (i == 9)) and (j == 10)) or (k == 11)) and (l == 12))',
    );

    // Match all conditions
    $allTrue = new Context([
        'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4,
        'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8,
        'i' => 9, 'j' => 10, 'k' => 11, 'l' => 12,
    ]);

    // Only last condition true
    $onlyLast = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 11, 'l' => 12,
    ]);

    // None true
    $noneTrue = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
    ]);

    expect($rule->evaluate($allTrue))->toBeTrue()
        ->and($rule->evaluate($onlyLast))->toBeTrue()
        ->and($rule->evaluate($noneTrue))->toBeFalse();
});

test('deeply nested mathematical expressions with conditionals', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '(((((a + b) * (c - d)) / (e + f)) > ((g * h) - (i / j))) and '.
        '(((k + l) * (m - n)) <= ((o * p) + (q / r))))',
    );

    $validContext = new Context([
        'a' => 10, 'b' => 5, 'c' => 20, 'd' => 5,
        'e' => 3, 'f' => 2, 'g' => 4, 'h' => 3,
        'i' => 8, 'j' => 2, 'k' => 2, 'l' => 3,
        'm' => 10, 'n' => 4, 'o' => 5, 'p' => 6,
        'q' => 20, 'r' => 2,
    ]);

    $invalidContext = new Context([
        'a' => 1, 'b' => 1, 'c' => 1, 'd' => 1,
        'e' => 1, 'f' => 1, 'g' => 100, 'h' => 100,
        'i' => 1, 'j' => 1, 'k' => 1, 'l' => 1,
        'm' => 1, 'n' => 1, 'o' => 1, 'p' => 1,
        'q' => 1, 'r' => 1,
    ]);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($invalidContext))->toBeFalse();
});

test('extreme nesting with all logical operators combined', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((((a == 1 and b == 2) or (c == 3 xor d == 4)) and '.
        'not ((e == 5 or f == 6) and (g == 7 xor h == 8))) or '.
        '((not (i == 9 and j == 10)) xor ((k == 11 or l == 12) and (m == 13 and n == 14))))',
    );

    // First complex branch true: (a and b) or (c xor d) = true, and not(...) = true
    $context1 = new Context([
        'a' => 1, 'b' => 2, 'c' => 0, 'd' => 4,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0,
    ]);

    // Second complex branch true: not(i and j) = true, (k or l) and (m and n) = true, true xor true = false, false or false = false
    // Need to make XOR work: one side true, one side false
    $context2 = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 9, 'j' => 10, 'k' => 0, 'l' => 0,  // not(true) = false, right side = false, false xor false = false
        'm' => 0, 'n' => 0,
    ]);

    // Both branches false: first stays false, second XOR has both sides true (true xor true = false)
    $context3 = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 11, 'l' => 0,
        'm' => 13, 'n' => 14,
    ]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeFalse()
        ->and($rule->evaluate($context3))->toBeFalse();
});

test('deeply nested access control with complex business rules', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '((((user.role == "admin" or user.role == "moderator") and '.
        '(user.permissions.canEdit == true and user.permissions.canDelete == true)) or '.
        '((user.isOwner == true and resource.isPublic == false) and '.
        'not (resource.isLocked == true or resource.isArchived == true))) and '.
        '((user.accountStatus == "active" and user.emailVerified == true) and '.
        '((user.subscriptionLevel == "premium" or user.credits > 100) and '.
        'not (user.isBanned == true or user.isSuspended == true)))) and '.
        '(((currentTime > resource.publishedAt and currentTime < resource.expiresAt) or '.
        'resource.neverExpires == true) and '.
        '(resource.region in ["US", "EU", "APAC"] and resource.status == "published"))',
    );

    $adminWithAccessContext = new Context([
        'user' => [
            'role' => 'admin',
            'permissions' => ['canEdit' => true, 'canDelete' => true],
            'isOwner' => false,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'premium',
            'credits' => 50,
            'isBanned' => false,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => true,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 9_999,
            'neverExpires' => false,
            'region' => 'US',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    $ownerWithAccessContext = new Context([
        'user' => [
            'role' => 'user',
            'permissions' => ['canEdit' => false, 'canDelete' => false],
            'isOwner' => true,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'free',
            'credits' => 150,
            'isBanned' => false,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => false,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 9_999,
            'neverExpires' => false,
            'region' => 'EU',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    $bannedUserContext = new Context([
        'user' => [
            'role' => 'admin',
            'permissions' => ['canEdit' => true, 'canDelete' => true],
            'isOwner' => true,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'premium',
            'credits' => 200,
            'isBanned' => true,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => true,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 9_999,
            'neverExpires' => false,
            'region' => 'US',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    $expiredResourceContext = new Context([
        'user' => [
            'role' => 'admin',
            'permissions' => ['canEdit' => true, 'canDelete' => true],
            'isOwner' => false,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'premium',
            'credits' => 50,
            'isBanned' => false,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => true,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 3_000,
            'neverExpires' => false,
            'region' => 'US',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    expect($rule->evaluate($adminWithAccessContext))->toBeTrue()
        ->and($rule->evaluate($ownerWithAccessContext))->toBeTrue()
        ->and($rule->evaluate($bannedUserContext))->toBeFalse()
        ->and($rule->evaluate($expiredResourceContext))->toBeFalse();
});

test('maximum depth stress test with 15 nested levels', function (): void {
    $srb = new StringRuleBuilder();
    $rule = $srb->parse(
        '(((((((((((((((a == 1) or (b == 2)) and (c == 3)) or (d == 4)) and (e == 5)) or (f == 6)) and (g == 7)) or (h == 8)) and (i == 9)) or (j == 10)) and (k == 11)) or (l == 12)) and (m == 13)) or (n == 14)) and (o == 15))',
    );

    // For alternating OR/AND to stay true, AND conditions must match
    $deepMatch = new Context([
        'a' => 1, 'b' => 0, 'c' => 3, 'd' => 0,
        'e' => 5, 'f' => 0, 'g' => 7, 'h' => 0,
        'i' => 9, 'j' => 0, 'k' => 11, 'l' => 0,
        'm' => 13, 'n' => 0, 'o' => 15,
    ]);

    $noMatch = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0, 'o' => 0,
    ]);

    expect($rule->evaluate($deepMatch))->toBeTrue()
        ->and($rule->evaluate($noMatch))->toBeFalse();
});
