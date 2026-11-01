<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Event\LoginSucceeded;
use Psr\Clock\ClockInterface;

final class LoginLogListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(LoginSucceeded $event): void
    {
        $this->entityManager->persist(new LoginLog($event->userId, $event->ipAddress, $this->clock->now()));
        $this->entityManager->flush();
    }
}
