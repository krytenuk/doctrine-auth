<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'member')]
class Member extends BaseAccount
{
}
