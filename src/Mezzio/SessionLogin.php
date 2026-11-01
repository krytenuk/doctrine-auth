<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mezzio;

use FwsDoctrineAuth\User\UserProviderInterface;
use Mezzio\Session\SessionInterface;

/**
 * Marks a session as logged in or logged out.
 */
final class SessionLogin
{
    public const SESSION_KEY = 'fws_doctrine_auth.user_id';

    public function __construct(private readonly UserProviderInterface $users)
    {
    }

    /**
     * The session ID changes at login. Otherwise anyone who planted a known ID in the browser beforehand would share
     * the logged-in session.
     *
     * @return SessionInterface The session to use from now on. SessionMiddleware's session updates itself, but other
     *                          implementations of regenerate() return a new instance
     */
    public function login(SessionInterface $session, object $user): SessionInterface
    {
        $session->clear();
        $session = $session->regenerate();
        $session->set(self::SESSION_KEY, $this->users->getId($user));

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
}
