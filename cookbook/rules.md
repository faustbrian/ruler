# Working with Rules

## Combining Rules

```php
// Create a Rule with an $a == $b condition
$aEqualsB = $rb->create($a->equalTo($b));

// Create another Rule with an $a != $b condition
$aDoesNotEqualB = $rb->create($a->notEqualTo($b));

// Combine them into composite rules
// (Rules are also Propositions, enabling complex rule composition)
$eitherOne = $rb->create($rb->logicalOr($aEqualsB, $aDoesNotEqualB));

// Just to mix things up, we'll populate our evaluation context with completely
// random values...
$context = new Context([
    'a' => rand(),
    'b' => rand(),
]);

// Hint: this is always true!
$eitherOne->evaluate($context);
```

## Logical Operators

```php
$rb->logicalNot($aEqualsB);                  // The same as $aDoesNotEqualB :)
$rb->logicalAnd($aEqualsB, $aDoesNotEqualB); // True if both conditions are true
$rb->logicalOr($aEqualsB, $aDoesNotEqualB);  // True if either condition is true
$rb->logicalXor($aEqualsB, $aDoesNotEqualB); // True if only one condition is true
```

## Evaluating Rules

`evaluate()` a Rule with Context to figure out whether it is true.

```php
$context = new Context([
    'userName' => fn() => $_SESSION['userName'] ?? null,
]);

$userIsLoggedIn = $rb->create($rb['userName']->notEqualTo(null));

if ($userIsLoggedIn->evaluate($context)) {
    // Do something special for logged in users!
}
```

## Executing Rules

If a Rule has an action, you can `execute()` it directly and save yourself a couple of lines of code.

```php
$hiJustin = $rb->create(
    $rb['userName']->equalTo('bobthecow'),
    function() {
        echo "Hi, Justin!";
    }
);

$hiJustin->execute($context);  // "Hi, Justin!"
```

## Executing Multiple Rules with RuleSet

```php
$hiJon = $rb->create(
    $rb['userName']->equalTo('jwage'),
    function() {
        echo "Hey there Jon!";
    }
);

$hiEveryoneElse = $rb->create(
    $rb->logicalAnd(
        $rb->logicalNot($rb->logicalOr($hiJustin, $hiJon)), // The user is neither Justin nor Jon
        $userIsLoggedIn                                     // ... but a user nonetheless
    ),
    function() use ($context) {
        echo sprintf("Hello, %s", $context['userName']);
    }
);

$rules = new RuleSet([$hiJustin, $hiJon, $hiEveryoneElse]);

// Let's add one more Rule, so non-authenticated users have a chance to log in
$redirectForAuthentication = $rb->create($rb->logicalNot($userIsLoggedIn), function() {
    header('Location: /login');
    exit;
});

$rules->addRule($redirectForAuthentication);

// Execute all true Rules
// Note: These rules are mutually exclusive, so at most one will evaluate to true
$rules->executeRules($context);
```
