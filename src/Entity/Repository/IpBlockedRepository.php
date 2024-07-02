<?php

namespace FwsDoctrineAuth\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use DateTimeInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use FwsDoctrineAuth\Entity\IpBlocked;

/**
 * Description of IpBlockedRepository
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class IpBlockedRepository extends EntityRepository
{

    /**
     * Delete IP address from blocked list
     * @param string $ipAddress
     * @param DateTimeInterface|null $date
     * @return void
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
        } catch (NoResultException|NonUniqueResultException) {}
    }

}
