<?php

namespace FwsDoctrineAuth\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use DateTimeInterface;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;

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
     * @return int
     */
    public function countFailedAttempts(string $ipAddress, DateTimeInterface $date): int
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select($builder->expr()->count('la'))
                ->from(FailedLoginAttemptsLog::class, 'la')
                ->where($builder->expr()->eq('la.ipAddress', ':ipAddress'))
                ->setParameter('ipAddress', $ipAddress)
                ->andWhere($builder->expr()->gt('la.dateLogged', ':date'))
                ->setParameter('date', $date);

        return $builder->getQuery()->getSingleScalarResult();
    }

}
