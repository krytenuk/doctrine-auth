<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\Repository\FailedLoginAttemptsLogRepository;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\EntityManagerTrait;
use FwsDoctrineAuth\Model\GetClientIpAddress;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class BlockIP extends AbstractPlugin
{
    use EntityManagerTrait;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected GetClientIpAddress     $clientIpAddress,
        protected array                  $config
    )
    {
    }

    /**
     *
     * @param string $emailEntered Email address entered by the user during failed login attempts
     * @return bool
     * @throws DoctrineAuthException
     */
    public function __invoke(string $emailEntered): bool
    {
        $maxLoginAttemptsTime = $this->config['doctrineAuth']['maxLoginAttemptsTime'] ?? 0;
        if (!$maxLoginAttemptsTime) {
            throw new DoctrineAuthException('maxLoginAttemptsTime config key not set');
        }
        $maxLoginAttemptsTime = (int) $maxLoginAttemptsTime;
        if  (!$maxLoginAttemptsTime) {
            throw new DoctrineAuthException('maxLoginAttemptsTime config must contain an integer greater than zero');
        }

        $maxLoginAttempts = $this->config['doctrineAuth']['maxLoginAttempts'] ?? null;
        if ($maxLoginAttempts === null) {
            throw new DoctrineAuthException('maxLoginAttempts config key not set');
        }
        $maxLoginAttempts = (int) $maxLoginAttempts;
        if (!$maxLoginAttempts) {
            return false;
        }

        $clientIp = $this->clientIpAddress->getClientIP();
        if (!$clientIp) {
            throw new DoctrineAuthException('Client IP address not found');
        }

        $now = new DateTimeImmutable('now');
        $date = $now->sub(new DateInterval("PT{$maxLoginAttemptsTime}M"));
        /** @var FailedLoginAttemptsLogRepository $repository */
        $repository = $this->entityManager->getRepository(FailedLoginAttemptsLog::class);
        $failedAttempts = $repository->countFailedAttempts($clientIp, $date);
        if ($failedAttempts !== null && $failedAttempts >= $maxLoginAttempts) {
            $ipBlocked = new IpBlocked();
            $ipBlocked
                ->setIpAddress($clientIp)
                ->setEmailAddress($emailEntered);
            if (!$this->persistEntity($this->entityManager, $ipBlocked)) {
                throw new DoctrineAuthException(sprintf('Unable to persist %s', $ipBlocked::class));
            }
            if (!$this->flushEntityManager($this->entityManager)) {
                throw new DoctrineAuthException('Unable to block IP address');
            }
        }
        return true;
    }

}