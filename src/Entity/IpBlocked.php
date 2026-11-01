<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ip_blocked')]
#[ORM\Index(name: 'ip_blocked_ip', columns: ['ip_address'])]
class IpBlocked
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @param DateTimeImmutable|null $expiresAt Null keeps the address blocked until the row is deleted
     */
    public function __construct(
        #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45)]
        private string $ipAddress,
        #[ORM\Column(name: 'blocked_at', type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $blockedAt,
        #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
        private ?DateTimeImmutable $expiresAt,
    ) {
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getBlockedAt(): DateTimeImmutable
    {
        return $this->blockedAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
