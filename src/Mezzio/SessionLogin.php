<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mezzio;

use FwsDoctrineAuth\User\UserProviderInterface;
use Mezzio\Session\SessionInterface;

use function hash;
use function hash_equals;
use function is_string;

/**
 * Marks a session as logged in or logged out.
 */
final class SessionLogin
{
    public const SESSION_KEY = 'fws_doctrine_auth.user_id';
    public const CREDENTIAL_KEY = 'fws_doctrine_auth.credential';

    public function __construct(private readonly UserProviderInterface $users)
    {
    }

    /**
     * The session ID changes at login. Otherwise anyone who planted a known ID in the browser beforehand would share
     * the logged-in session.
     *
     * A fingerprint of the password hash is kept in the session too, so changing a password ends every other session.
     * After changing a password for a user who stays logged in, call this again to update their own session.
     *
     * @return SessionInterface The session to use from now on. SessionMiddleware's session updates itself, but other
     *                          implementations of regenerate() return a new instance
     */
    public function login(SessionInterface $session, object $user): SessionInterface
    {
        $session->clear();
        $session = $session->regenerate();
        $session->set(self::SESSION_KEY, $this->users->getId($user));
        $session->set(self::CREDENTIAL_KEY, $this->credentialFingerprint($user));

        return $session;
    }

    /**
     * @return SessionInterface The session to use from now on. See login()
     */
    public function logout(SessionInterface $session): SessionInterface
    {
        $session->clear();

        return $session->regenerate();
    }

    /**
     * Whether a session's fingerprint still matches the user's password.
     */
    public function isCurrent(SessionInterface $session, object $user): bool
    {
        $fingerprint = $session->get(self::CREDENTIAL_KEY);

        return is_string($fingerprint) && hash_equals($this->credentialFingerprint($user), $fingerprint);
    }

    /**
     * A fingerprint of the user's password hash, which changes when the password does.
     */
    public function credentialFingerprint(object $user): string
    {
        return hash('sha256', (string) $this->users->getPasswordHash($user));
    }
}
