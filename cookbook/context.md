# Working with Context

## Dynamic Context Population

Context acts as a ViewModel for rule evaluation, supporting both static values and lazy evaluation for dynamic data:

```php
$context = new Context;

// Some static values...
$context['reallyAnnoyingUsers'] = ['bobthecow', 'jwage'];

// You'll remember this one from before
$context['userName'] = fn() => $_SESSION['userName'] ?? null;

// Let's pretend you have an EntityManager named `$em`...
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

With dynamic context values, you can build rules based on complex business logic. For example, a shipping price calculator:

> If the current user has placed 5 or more orders and isn't in the restricted list, provide free shipping.

```php
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

As an added bonus, Ruler lets you access properties, methods and offsets on your Context Variable values. This can come in really handy.

Say we wanted to log the current user's name if they are an administrator:

```php
// Reusing our $context from the last example...

// We'll define a few context variables for determining what roles a user has,
// and their full name:

$context['userRoles'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $user->roles();
    } else {
        // return a default "anonymous" role if there is no current user
        return ['anonymous'];
    }
};

$context['userFullName'] = function() use ($em, $context) {
    if ($user = $context['user']) {
        return $user->fullName;
    }
};


// Now we'll create a rule to write the log message

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

Instead of creating separate context variables for every property, use VariableProperties for direct access:

```php
// Set default values on the VariableProperty itself:

$rb['user']['roles'] = ['anonymous'];

$rb->create(
    $rb->logicalAnd(
        $userIsLoggedIn,
        $rb['user']['roles']->contains('admin')
    ),
    function() use ($context, $logger) {
        $logger->info(sprintf("Admin user %s did a thing!", $context['user']['fullName']);
    }
);
```

If the parent Variable resolves to an object, and this VariableProperty name is "bar", it will do a prioritized lookup for:

  1. A method named `bar`
  2. A public property named `bar`
  3. ArrayAccess + offsetExists named `bar`

If the Variable resolves to an array it will return:

  1. Array index `bar`

If none of the above are true, it will return the default value for this VariableProperty.
