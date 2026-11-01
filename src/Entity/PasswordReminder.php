<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Rows stay after use until they are purged, as they are what the request limits count.
 */
#[ORM\Entity]
#[ORM\Table(name: 'password_reminder')]
#[ORM\UniqueConstraint(name: 'password_reminder_token', columns: ['token_hash'])]
#[ORM\Index(name: 'password_reminder_user', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'password_reminder_ip', columns: ['ip_address', 'created_at'])]
class PasswordReminder
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'consumed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $consumedAt = null;

    public function __construct(
        #[ORM\Column(name: 'token_hash', type: Types::STRING, length: 64)]
        private string $tokenHash,
        #[ORM\Column(name: 'user_id', type: Types::STRING, length: 64)]
        private string $userId,
        #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45)]
        private string $ipAddress,
        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $expiresAt,
    ) {
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getConsumedAt(): ?DateTimeImmutable
    {
        return $this->consumedAt;
    }
}
