FwsDoctrineAuth
===============

Login for Doctrine ORM users in Mezzio applications. The module has no pages, forms, routes or email templates: it checks
login details and reports the outcome, and your application decides what to show.

**This is a work in progress.** Login, throttling and the login log are done. Password reset and two factor authentication
are still to come.

Installation
------------

Add these to your config aggregator, if the component installer has not done so already:

- `FwsDoctrineAuth\ConfigProvider`
- `Mezzio\Authentication\ConfigProvider`
- `Mezzio\Session\ConfigProvider`, with a session persistence such as `mezzio/mezzio-session-ext`

Copy `config/doctrine-auth.local.php.dist` to your application's `config/autoload/doctrine-auth.local.php` and fill it in.
**Keep the secret out of version control.**

The entity manager is looked up as `Doctrine\ORM\EntityManagerInterface`, so alias that name to your entity manager or set
`doctrine-auth.entity_manager`. With `roave/psr-container-doctrine`, the module adds its entities to the `orm_default`
attribute driver. Otherwise, add `src/Entity` to your mapping paths. Then update your database schema.

Your user entity
----------------

```php
use FwsDoctrineAuth\Mapping as Auth;

#[ORM\Column(type: Types::STRING)]
#[Auth\IdentityProperty]
private string $email;

#[ORM\Column(type: Types::STRING)]
#[Auth\CredentialProperty]
private string $password;

#[ORM\Column(type: Types::BOOLEAN)]
#[Auth\IsActiveProperty] // Optional
private bool $active = true;

#[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
#[Auth\IsDeletedProperty] // Optional
private ?DateTimeImmutable $deletedAt = null;
```

`IsActiveProperty` and `IsDeletedProperty` take the value to compare with, such as `#[Auth\IsActiveProperty('live')]` or
`#[Auth\IsDeletedProperty(true)]`. Hash passwords with `FwsDoctrineAuth\Password\PasswordHasherInterface`.

If the identity is encrypted with `krytenuk/doctrine-crypt`, it cannot be searched directly, so name its blind index:

```php
#[ORM\Column(type: Types::STRING, length: 512)]
#[Auth\IdentityProperty(blindIndex: 'emailIndex')]
#[Crypt\EncryptedField]
#[Crypt\IndexableField(name: 'email', filterBits: 32, property: 'emailIndex')]
private string $email;
```

Logging in
----------

```php
$result = $loginService->attempt($email, $password, $clientIpAddress);

if ($result->isSuccess()) {
    $sessionLogin->login($request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE), $result->user);
    // Redirect the user
} elseif ($result->isThrottled()) {
    // Too many attempts. $result->retryAfter is in seconds, or null if the address is blocked with no end
} else {
    // Show one message for everything else, so the page does not reveal which accounts exist
}
```

`SessionLogin` changes the session ID, as this has to happen at login. Call `$sessionLogin->logout($session)` to log out.

**Work out the client's address yourself.** Only your application knows which proxies it can trust. Trusting a forwarded
header that a client can set would let anyone get around the limits, or block someone else.

Protect pages by adding `Mezzio\Authentication\AuthenticationMiddleware` to a route, after `SessionMiddleware`. The user is
then in the `Mezzio\Authentication\UserInterface` request attribute. Users are loaded on every request, so disabling an
account ends its sessions straight away. To use `mezzio-authorization`, point
`FwsDoctrineAuth\Mezzio\RolesProviderInterface` at your own class that supplies each user's roles.

Failed login limits
-------------------

- **Per account:** after 5 failures in 15 minutes, the account is refused from every address until they age out. This stops
  attacks that rotate addresses. It is temporary, so nobody can lock a user out for good.
- **Per address:** after 50 failures in 15 minutes, the address is blocked for an hour. A `block_for` of 0 blocks it until
  you delete its `ip_blocked` row.

Set either `max_attempts` to 0 to turn that limit off. A correct password does not get past either limit. The counts are
not atomic, so a few extra attempts can get through under many simultaneous requests.

Events
------

The module dispatches `LoginSucceeded`, `LoginFailed` and `LoginThrottled`. Add listeners to the config, as callable services:

```php
'doctrine-auth' => [
    'listeners' => [
        LoginFailed::class => ['my-listener' => MyListener::class],
        LoginSucceeded::class => ['login-log' => null], // Turns off the default login log
    ],
],
```

Replacing parts
---------------

Everything is behind an interface, with an alias in the container that you can point at your own class:

| Interface | Default |
|---|---|
| `User\UserProviderInterface` | Reads the attributes on your user entity |
| `Password\PasswordHasherInterface` | PHP's `password_hash()` |
| `Throttle\LoginThrottleInterface` | The limits above, stored with Doctrine |
| `TwoFactor\TwoFactorPolicyInterface` | Never requires two factor authentication |
| `Mezzio\RolesProviderInterface` | No roles |

Upgrading from version 1
------------------------

Existing password hashes keep working, and are upgraded at each user's next login. This version is otherwise a rewrite: the
controllers, forms, views, routes, ACL and registration are gone. The login tables have changed too.

Testing
-------

Some tests need `pdo_sqlite`, and are skipped without it. To run them all with Docker:

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD":/app -w /app php:8.4-cli vendor/bin/phpunit
```
