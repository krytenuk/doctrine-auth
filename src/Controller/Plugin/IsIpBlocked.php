<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\Repository\IpBlockedRepository;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\GetClientIpAddress;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class IsIpBlocked extends AbstractPlugin
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected GetClientIpAddress     $clientIpAddress,
        protected array                  $config
    )
    {
    }


    /**
     * @return bool
     * @throws DoctrineAuthException
     */
    public function __invoke(): bool
    {
        $loginReleaseTime = $this->config['doctrineAuth']['loginReleaseTime'] ?? null;
        if ($loginReleaseTime === null) {
            throw new DoctrineAuthException('loginReleaseTime config key not set');
        }

        $clientIp = $this->clientIpAddress->getClientIP();
        if (!$clientIp) {
            throw new DoctrineAuthException('Client IP address not found');
        }

        /** @var IpBlockedRepository $repository */
        $repository = $this->entityManager->getRepository(IpBlocked::class);

        $loginReleaseTime = (int) $loginReleaseTime;
        if ($loginReleaseTime > 0) {
            $now = new DateTimeImmutable('now');
            $date = $now->sub(new DateInterval("PT{$loginReleaseTime}M"));
            $repository->deleteBlockedIpAddress($clientIp, $date);
        }

        return (bool) $repository->count(['ipAddress' => $clientIp]);
    }

}