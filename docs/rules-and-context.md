---
title: Rules and Context
description: Working with rules, rule sets, and dynamic context for complex business logic.
---

## Working with Rules

### Combining Rules

Rules are also Propositions, enabling complex rule composition:

```php
// Create individual rules
$aEqualsB = $rb->create($a->equalTo($b));
$aDoesNotEqualB = $rb->create($a->notEqualTo($b));

// Combine into composite rules
$eitherOne = $rb->create($rb->logicalOr($aEqualsB, $aDoesNotEqualB));

$context = new Context([
    'a' => rand(),
    'b' => rand(),
]);

// This is always true!
$eitherOne->evaluate($context);
```

### Evaluating Rules

Use `evaluate()` to get a boolean result:

```php
$context = new Context([
    'userName' => fn() => $_SESSION['userName'] ?? null,
]);

$userIsLoggedIn = $rb->create($rb['userName']->notEqualTo(null));

if ($userIsLoggedIn->evaluate($context)) {
    // Do something for logged-in users
}
```

### Executing Rules with Actions

If a Rule has an action, use `execute()` to run it when the rule evaluates to true:

```php
$hiJustin = $rb->create(
    $rb['userName']->equalTo('bobthecow'),
    fn() => echo "Hi, Justin!"
);

$hiJustin->execute($context);  // "Hi, Justin!"
```

### RuleSet: Executing Multiple Rules

```php
$hiJon = $rb->create(
    $rb['userName']->equalTo('jwage'),
    fn() => echo "Hey there Jon!"
);

$hiEveryoneElse = $rb->create(
    $rb->logicalAnd(
        $rb->logicalNot($rb->logicalOr($hiJustin, $hiJon)),
        $userIsLoggedIn
    ),
    function() use ($context) {
        echo sprintf("Hello, %s", $context['userName']);
    }
);

$rules = new RuleSet([$hiJustin, $hiJon, $hiEveryoneElse]);

// Add more rules dynamically
$redirectForAuth = $rb->create(
    $rb->logicalNot($userIsLoggedIn),
    function() {
        header('Location: /login');
        exit;
    }
);
$rules->addRule($redirectForAuth);

// Execute all matching rules
$rules->executeRules($context);
```

## Working with Context

### Dynamic Context Population

Context supports both static values and lazy evaluation:

```php
$context = new Context;

// Static values
$context['reallyAnnoyingUsers'] = ['bobthecow', 'jwage'];

// Lazy evaluation
$context['userName'] = fn() => $_SESSION['userName'] ?? null;

// Dependent lazy values
$context['user'] = function() use ($em, $context) {
    if ($userName = $context['userName']) {
        return $em->getRepository('Users')->findByUserName($userName);
    }
};

$context['orderCount'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $em->getRepository('Orders')->findByUser($user)->count();
    }
    return 0;
};
```

### Complex Business Logic

Build rules based on lazy context values:

```php
// Free shipping for loyal customers (excluding problem users)
$rb->create(
    $rb->logicalAnd(
        $rb['orderCount']->greaterThanOrEqualTo(5),
        $rb['reallyAnnoyingUsers']->doesNotContain($rb['userName'])
    ),
    function() use ($shipManager, $context) {
        $shipManager->giveFreeShippingTo($context['user']);
    }
);
```

## Variable Properties

Access properties, methods, and offsets on context values directly:

```php
// Define context with user roles
$context['userRoles'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $user->roles();
    }
    return ['anonymous'];
};

$context['userFullName'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $user->fullName;
    }
};

// Log admin activity
$rb->create(
    $rb->logicalAnd(
        $userIsLoggedIn,
        $rb['userRoles']->contains('admin')
    ),
    function() use ($context, $logger) {
        $logger->info(sprintf("Admin user %s did a thing!", $context['userFullName']));
    }
);
```

### Direct Property Access

Instead of creating separate context variables, use VariableProperties:

```php
// Set defaults on VariableProperty
$rb['user']['roles'] = ['anonymous'];

$rb->create(
    $rb->logicalAnd(
        $userIsLoggedIn,
        $rb['user']['roles']->contains('admin')
    ),
    function() use ($context, $logger) {
        $logger->info(sprintf("Admin user %s did a thing!", $context['user']['fullName']));
    }
);
```

### Property Resolution Order

When the parent Variable resolves to an **object**, VariableProperty looks up:
1. A method with the property name
2. A public property with the property name
3. ArrayAccess + offsetExists

When the parent resolves to an **array**:
1. Array index with the property name

If none match, returns the default value (if set).
