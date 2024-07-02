<?php

namespace FwsDoctrineAuth\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use DateTimeInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * FailedLoginAttemptsLogRepository
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class FailedLoginAttemptsLogRepository extends EntityRepository
{

    /**
     * Get number of login attempts for given IP address
     * @param string $ipAddress
     * @param DateTimeInterface $date Get login attempts between $date and now
     * @return int|null
     */
    public function countFailedAttempts(string $ipAddress, DateTimeInterface $date): ?int
    {
        $builder = $this->createQueryBuilder('la');
        $builder->select($builder->expr()->count('la'))
                ->where($builder->expr()->eq('la.ipAddress', ':ipAddress'))
                ->setParameter('ipAddress', $ipAddress)
                ->andWhere($builder->expr()->gt('la.dateLogged', ':date'))
                ->setParameter('date', $date);

        try {
            return $builder->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
            return null;
        }
    }

}
