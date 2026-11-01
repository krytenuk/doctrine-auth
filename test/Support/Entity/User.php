<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Mapping as Auth;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class User
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Auth\IsActiveProperty]
    public bool $active = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Auth\IsDeletedProperty]
    public ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        #[ORM\Column(type: Types::STRING)]
        #[Auth\IdentityProperty]
        private string $email,
        #[ORM\Column(type: Types::STRING, nullable: true)]
        #[Auth\CredentialProperty]
        private ?string $password,
    ) {
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
