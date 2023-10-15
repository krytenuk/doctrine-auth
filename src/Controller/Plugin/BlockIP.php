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
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Stdlib\ParametersInterface;

class BlockIP extends AbstractPlugin
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
     *
     * @param string $emailEntered Email address entered by the user during failed login attempts
     * @return bool
     * @throws DoctrineAuthException
     */
    public function __invoke(string $emailEntered): bool
    {
        $maxLoginAttemptsTime = $this->config['doctrineAuth']['maxLoginAttemptsTime'] ?? null;
        if (!$maxLoginAttemptsTime) {
            throw new DoctrineAuthException('maxLoginAttemptsTime config key not set');
        }

        $maxLoginAttempts = $this->config['doctrineAuth']['maxLoginAttempts'] ?? null;
        if ($maxLoginAttempts === null) {
            throw new DoctrineAuthException('maxLoginAttempts config key not set');
        }

        if ($maxLoginAttempts === 0) {
            return false;
        }

        $ipAddress = $this->serverParams->get('REMOTE_ADDR');
        $now = new DateTimeImmutable('now');
        $date = $now->sub(new DateInterval("PT{$maxLoginAttemptsTime}M"));
        /** @var FailedLoginAttemptsLogRepository $repository */
        $repository = $this->getEntityRepository($this->entityManager, FailedLoginAttemptsLog::class);
        if ($repository === null) {
            throw new DoctrineAuthException('Unable to get repository for FailedLoginAttemptsLog::class');
        }

        $failedAttempts = $repository->countFailedAttempts($ipAddress, $date);
        if ($failedAttempts !== null && $failedAttempts >= $maxLoginAttempts) {
            $ipBlocked = new IpBlocked();
            $ipBlocked
                ->setIpAddress($ipAddress)
                ->setEmailAddress($emailEntered);
            $this->entityManager->persist($ipBlocked);
            if (!$this->flushEntityManager($this->entityManager)) {
                throw new DoctrineAuthException('Unable to block IP address');
            }
        }
        return true;
    }

}