<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Mapping as Auth;

#[ORM\MappedSuperclass]
abstract class BaseAccount
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(type: Types::STRING)]
        #[Auth\IdentityProperty]
        private string $username,
        #[ORM\Column(type: Types::STRING)]
        #[Auth\CredentialProperty]
        private string $secretHash,
    ) {
    }
}
