<?php

Namespace FwsDoctrineAuth\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use FwsDoctrineAuth\Entity\UserRoles;

/**
 * UserRolesRepository
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class UserRolesRepository extends EntityRepository
{

    /**
     * Count user roles
     * @return integer
     */
    public function countRoles(): int
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select('COUNT(r)')
                ->from(UserRoles::class, 'r');

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select('COUNT(r)')
                ->from(UserRoles::class, 'r')
                ->where($builder->expr()->eq('r.role', ':role'))
                ->setParameter('role', $role);

        return (bool) $builder->getQuery()->getSingleScalarResult();
    }

}
