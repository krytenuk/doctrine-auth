FwsDoctrineAuth
===============

Login for Doctrine ORM users in Mezzio applications. The module has no pages, forms, routes or email templates: it checks
login details and reports the outcome, and your application decides what to show.

**This is a work in progress.** Login, throttling, the login log, password reset and the framework for two factor
authentication are done. Each way of getting a second factor is a separate package:

- [`krytenuk/doctrine-auth-app-2fa`](https://github.com/krytenuk/doctrine-auth-app-2fa): authenticator apps
- Email and SMS codes: still to come

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

Two factor authentication
-------------------------

Install a two factor package, then users who set it up are asked for a code after their password. Without one, nobody is
asked. Handle `requiresTwoFactor()` on your login page, and use `FwsDoctrineAuth\Mezzio\TwoFactorLogin` on your code page:

```php
// The login page
$result = $loginService->attempt($email, $password, $clientIpAddress);
if ($result->requiresTwoFactor()) {
    $twoFactorLogin->begin($session, $result->user);
    // Redirect to your code page
}

// The code page. If more than one method is set up, let the user choose
$methods = $twoFactorLogin->availableMethods($session); // Such as ['app'], or [] to send them back to the login page
$twoFactorLogin->start($session, $method); // When they choose. A method that sends a code sends it now

// The code is submitted
$result = $twoFactorLogin->verify($session, $method, $code, $clientIpAddress);
match ($result->status) {
    TwoFactorStatus::Success        => /* logged in, redirect the user */,
    TwoFactorStatus::InvalidCode    => /* show the form again */,
    TwoFactorStatus::Throttled      => /* too many attempts, see $result->retryAfter */,
    TwoFactorStatus::NoPendingLogin => /* took too long or never logged in, back to the login page */,
};
```

- **Nobody is logged in** until the code is right. The session only remembers who is waiting, for 5 minutes. Change this
  with `doctrine-auth.two_factor.lifetime`.
- **Wrong codes count as failed logins,** for the account and the address, so the limits below apply to them too.
- **Changing the password or disabling the account** ends a login that is waiting for a code.
- The `LoginSucceeded` event, and so the login log, happens when the code is right. Wrong codes dispatch `TwoFactorFailed`.

To write your own method, implement `TwoFactor\TwoFactorMethodInterface` and add it to the config:

```php
'doctrine-auth' => [
    'two_factor' => [
        'methods' => ['my-method' => MyMethod::class], // A null service removes a method
    ],
],
```

**Work out the client's address yourself.** Only your application knows which proxies it can trust. Trusting a forwarded
header that a client can set would let anyone get around the limits, or block someone else.

Protect pages by adding `Mezzio\Authentication\AuthenticationMiddleware` to a route, after `SessionMiddleware`. The user is
then in the `Mezzio\Authentication\UserInterface` request attribute. Users are loaded on every request, so disabling an
account or changing its password ends its sessions straight away. To use `mezzio-authorization`, point
`FwsDoctrineAuth\Mezzio\RolesProviderInterface` at your own class that supplies each user's roles.

Forgotten passwords
-------------------

Your application provides the pages and the email. Implement `FwsDoctrineAuth\PasswordReset\AuthMailerInterface` and alias
that name to your class in the container:

```php
public function sendPasswordReset(object $user, string $token, DateTimeImmutable $expiresAt): void
{
    // Render your own template, with a link to your own page containing $token
}
```

Then use `FwsDoctrineAuth\PasswordReset\PasswordResetService`:

```php
// The "forgotten password" form. Say the same thing whether or not the account exists
$passwordReset->request($email, $clientIpAddress);

// The page the link goes to. Decide whether to show the form
$user = $passwordReset->findUser($token);

// The form is submitted. Check the password against your own policy first
match ($passwordReset->reset($token, $newPassword, $clientIpAddress)) {
    PasswordResetStatus::Success              => /* ask them to log in */,
    PasswordResetStatus::UnacceptablePassword => /* show the form again, the link still works */,
    PasswordResetStatus::InvalidToken         => /* the link is wrong, used or expired */,
};
```

- **Links** work once, for an hour. Requesting another stops the earlier link working. Only a hash of the token is stored, so
  a database leak gives nobody a working link.
- **After a reset,** all of the user's sessions end, and so does any failed login lockout. Listen for the `PasswordChanged`
  event to tell the user by email, in case it was not them.
- **Emails are limited** to 3 per account and 10 per address each hour, without telling the requester. Change this in
  `doctrine-auth.password_reset`.
- **Mail failures** do not reach the caller, as that would reveal the account exists. Listen for the
  `PasswordResetDeliveryFailed` event, or you will not find out about them.

**Queue the email if you can.** Mail is only sent for real accounts, so the time it takes during a request can reveal which
addresses have one. **Send a `Referrer-Policy: no-referrer` header on your reset page,** so that the token in its address
does not reach other sites through links or images on the page.

If a logged-in user changes their own password, call `$sessionLogin->login($session, $user)` afterwards to keep them logged in.

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

The module dispatches `LoginSucceeded`, `LoginFailed`, `LoginThrottled`, `TwoFactorFailed`, `PasswordResetRequested`,
`PasswordResetDeliveryFailed` and `PasswordChanged`. Add listeners to the config, as callable services:

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

Each of these has an alias in the container that you can point at your own class:

| Interface | Default |
|---|---|
| `User\UserProviderInterface` | Reads the attributes on your user entity |
| `Password\PasswordHasherInterface` | PHP's `password_hash()` |
| `Throttle\LoginThrottleInterface` | The limits above, stored with Doctrine |
| `PasswordReset\PasswordResetStoreInterface` | Reset tokens, stored with Doctrine |
| `TwoFactor\TwoFactorPolicyInterface` | Requires two factor authentication from users who have set a method up |
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
