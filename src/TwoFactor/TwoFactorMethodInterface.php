<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

/**
 * A second factor, such as an authenticator app, an emailed code or a text message. Each one is a separate package.
 *
 * Register a method in the doctrine-auth.two_factor.methods config, under the name your pages will use for it:
 *
 *     'two_factor' => ['methods' => ['app' => MyMethod::class]]
 */
interface TwoFactorMethodInterface
{
    /**
     * Whether the user has set this method up, so that it can be offered at login.
     */
    public function isEnabledFor(object $user): bool;

    /**
     * Called when the user picks this method at login. A method that sends a code sends it here, and should limit how
     * often it does. One that does not, such as an authenticator app, does nothing.
     */
    public function start(object $user): void;

    /**
     * Whether the code is right. Wrong codes are counted against the account by the caller, so do not count them here.
     */
    public function verify(object $user, string $code): bool;
}
