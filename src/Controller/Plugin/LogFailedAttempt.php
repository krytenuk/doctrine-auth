<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Model\EntityManagerTrait;
use Laminas\Stdlib\ParametersInterface;

class LogFailedAttempt extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    use EntityManagerTrait;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ParametersInterface  $serverParams
    )
    {
    }


    /**
     * Log failed login attempt
     * @param string $emailAddress
     * @return bool
     */
    public function __invoke(string $emailAddress): bool
    {
        $log = new FailedLoginAttemptsLog();
        $log
            ->setEmailAddress($emailAddress)
            ->setIpAddress($this->serverParams->get('SERVER_ADDR'));

        if ($this->persistEntity($this->entityManager, $log)) {
            return $this->flushEntityManager($this->entityManager);
        }

        return false;
    }
}