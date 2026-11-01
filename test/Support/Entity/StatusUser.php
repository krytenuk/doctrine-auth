<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Mapping as Auth;

#[ORM\Entity]
#[ORM\Table(name: 'status_user')]
class StatusUser
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Auth\IdentityProperty]
    public string $email = 'a@example.com';

    #[ORM\Column(type: Types::STRING)]
    #[Auth\CredentialProperty]
    public string $password = 'hash';

    #[ORM\Column(type: Types::STRING)]
    #[Auth\IsActiveProperty('live')]
    public string $status = 'live';

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Auth\IsDeletedProperty(true)]
    public bool $removed = false;
}
