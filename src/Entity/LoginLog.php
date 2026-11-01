<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The user ID is a plain string rather than a foreign key, so the module works with any user entity and ID type.
 */
#[ORM\Entity]
#[ORM\Table(name: 'login_log')]
#[ORM\Index(name: 'login_log_user', columns: ['user_id'])]
class LoginLog
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(name: 'user_id', type: Types::STRING, length: 64)]
        private string $userId,
        #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45)]
        private string $ipAddress,
        #[ORM\Column(name: 'logged_in_at', type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $loggedInAt,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getLoggedInAt(): DateTimeImmutable
    {
        return $this->loggedInAt;
    }
}
