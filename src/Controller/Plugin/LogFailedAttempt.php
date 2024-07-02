<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\EntityManagerTrait;
use FwsDoctrineAuth\Model\GetClientIpAddress;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Stdlib\ParametersInterface;

class LogFailedAttempt extends AbstractPlugin
{
    use EntityManagerTrait;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected GetClientIpAddress  $clientIpAddress
    )
    {
    }


    /**
     * Log failed login attempt
     * @param string $emailAddress
     * @return bool
     * @throws DoctrineAuthException
     */
    public function __invoke(string $emailAddress): bool
    {
        $clientsIp = $this->clientIpAddress->getClientIP();
        if (!$clientsIp) {
            throw new DoctrineAuthException('Client IP address not found');
        }

        $log = new FailedLoginAttemptsLog();
        $log
            ->setEmailAddress($emailAddress)
            ->setIpAddress($clientsIp);

        if ($this->persistEntity($this->entityManager, $log)) {
            return $this->flushEntityManager($this->entityManager);
        }

        return false;
    }
}