<?php

namespace FwsDoctrineAuth\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use DateTimeInterface;
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
     * @return bool
     */
    public function deleteBlockedIpAddress(string $ipAddress, ?DateTimeInterface $date = null): bool
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->delete()
                ->from(IpBlocked::class, 'ipb')
                ->where($builder->expr()->eq('ipb.ipAddress', ':ipAddress'))
                ->setParameter('ipAddress', $ipAddress);

        if ($date !== null) {
            $builder->andWhere($builder->expr()->lt('ipb.dateBlocked', ':date'))
                    ->setParameter('date', $date);
        }

        return (bool) $builder->getQuery()->getSingleScalarResult();
    }

}
