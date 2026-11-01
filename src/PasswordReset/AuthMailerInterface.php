<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\PasswordReset;

use DateTimeImmutable;

/**
 * Your application implements this, so that it controls the email, its template and the link.
 *
 * Queue the email rather than sending it during the request if you can. Sending only happens for real accounts, so the
 * extra time it takes can reveal which addresses have one.
 */
interface AuthMailerInterface
{
    /**
     * @param string $token Put this in a link to your password reset page. It is only available here, as just its hash is stored
     */
    public function sendPasswordReset(object $user, string $token, DateTimeImmutable $expiresAt): void;
}
