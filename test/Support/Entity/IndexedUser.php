<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Mapping as Auth;

#[ORM\Entity]
#[ORM\Table(name: 'indexed_user')]
class IndexedUser
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(type: Types::STRING)]
        #[Auth\IdentityProperty(blindIndex: 'emailIndex')]
        public string $email,
        #[ORM\Column(type: Types::STRING)]
        public string $emailIndex,
        #[ORM\Column(type: Types::STRING)]
        #[Auth\CredentialProperty]
        public string $password = 'hash',
    ) {
    }
}
