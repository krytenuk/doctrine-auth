<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\Repository\IpBlockedRepository;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\EntityManagerTrait;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Stdlib\ParametersInterface;

class IsIpBlocked extends AbstractPlugin
{
    use EntityManagerTrait;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ParametersInterface    $serverParams,
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

        if ($loginReleaseTime > 0) {
            $now = new DateTimeImmutable('now');
            $date = $now->sub(new DateInterval("PT{$loginReleaseTime}M"));
            /** @var IpBlockedRepository $repository */
            $repository = $this->entityManager->getRepository(IpBlocked::class);
            if (!$repository) {
                return false;
            }
            $repository->deleteBlockedIpAddress($this->serverParams->get('SERVER_ADDR'), $date);
        }

        return (bool)$this->getEntityRepository($this->entityManager, IpBlocked::class)->count(['ipAddress' => $this->serverParams->get('SERVER_ADDR')]);
    }

}