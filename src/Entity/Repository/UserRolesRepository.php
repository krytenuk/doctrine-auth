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
    public function countRoles()
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select('COUNT(r)')
                ->from(UserRoles::class, 'r');

        return $builder->getQuery()->getSingleScalarResult();
    }
    
    public function hasRole($role)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select('COUNT(r)')
                ->from(UserRoles::class, 'r')
                ->where($builder->expr()->eq('r.role', ':role'))
                ->setParameter('role', $role);

        return (bool) $builder->getQuery()->getSingleScalarResult();
    }
}
