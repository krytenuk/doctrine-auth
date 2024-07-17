<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use FwsDoctrineAuth\Entity\UserRole;

/**
 * UserRoleRepository
 *
 * @method UserRole|null findOneByRole(string $roleId)
 */
class UserRoleRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findAllArray(): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.userRoleId, r.role')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        $returnArray = [];
        foreach ($result as $role) {
            $returnArray[$role['userRoleId']] = $role['role'];
        }
        return $returnArray;
    }
}
