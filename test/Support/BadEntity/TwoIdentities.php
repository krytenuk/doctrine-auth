<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support\BadEntity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Mapping as Auth;

#[ORM\Entity]
class TwoIdentities
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Auth\IdentityProperty]
    public string $email = 'a';

    #[ORM\Column(type: Types::STRING)]
    #[Auth\IdentityProperty]
    public string $username = 'a';

    #[ORM\Column(type: Types::STRING)]
    #[Auth\CredentialProperty]
    public string $password = 'a';
}
