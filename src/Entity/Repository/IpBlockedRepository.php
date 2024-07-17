<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity\Repository;

use DateTimeInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Description of IpBlockedRepository
 */
class IpBlockedRepository extends EntityRepository
{
    /**
     * Delete IP address from blocked list
     */
    public function deleteBlockedIpAddress(string $ipAddress, ?DateTimeInterface $date = null): void
    {
        $builder = $this->createQueryBuilder('ipb');
        $builder->delete()
                ->where($builder->expr()->eq('ipb.ipAddress', ':ipAddress'))
                ->setParameter('ipAddress', $ipAddress);

        if ($date !== null) {
            $builder->andWhere($builder->expr()->lt('ipb.dateBlocked', ':date'))
                    ->setParameter('date', $date);
        }

        try {
            $builder->getQuery()->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
        }
    }
}
