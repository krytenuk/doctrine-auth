<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores a keyed hash of the identity rather than the text typed, which may be an address or a mistyped password.
 */
#[ORM\Entity]
#[ORM\Table(name: 'failed_login_attempts_log')]
#[ORM\Index(name: 'failed_login_identity', columns: ['identity_hash', 'attempted_at'])]
#[ORM\Index(name: 'failed_login_ip', columns: ['ip_address', 'attempted_at'])]
class FailedLoginAttemptsLog
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(name: 'identity_hash', type: Types::STRING, length: 64)]
        private string $identityHash,
        #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45)]
        private string $ipAddress,
        #[ORM\Column(name: 'attempted_at', type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $attemptedAt,
    ) {
    }

    public function getIdentityHash(): string
    {
        return $this->identityHash;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getAttemptedAt(): DateTimeImmutable
    {
        return $this->attemptedAt;
    }
}
