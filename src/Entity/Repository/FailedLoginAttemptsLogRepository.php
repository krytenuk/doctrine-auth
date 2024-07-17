<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity\Repository;

use DateTimeInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * FailedLoginAttemptsLogRepository
 */
class FailedLoginAttemptsLogRepository extends EntityRepository
{
    /**
     * Get number of login attempts for given IP address
     *
     * @param DateTimeInterface $date Get login attempts between $date and now
     */
    public function countFailedAttempts(string $ipAddress, DateTimeInterface $date): int|null
    {
        $builder = $this->createQueryBuilder('la');
        $builder->select($builder->expr()->count('la'))
                ->where($builder->expr()->eq('la.ipAddress', ':ipAddress'))
                ->setParameter('ipAddress', $ipAddress)
                ->andWhere($builder->expr()->gt('la.dateLogged', ':date'))
                ->setParameter('date', $date);

        try {
            return $builder->getQuery()->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return null;
        }
    }
}
