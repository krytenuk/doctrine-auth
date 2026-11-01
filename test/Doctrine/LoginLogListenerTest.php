<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Doctrine;

use FwsDoctrineAuth\Doctrine\LoginLogListener;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Event\LoginSucceeded;
use FwsDoctrineAuthTest\Support\DoctrineTestCase;
use FwsDoctrineAuthTest\Support\FakeClock;
use stdClass;

final class LoginLogListenerTest extends DoctrineTestCase
{
    public function testWritesTheLogin(): void
    {
        $clock = new FakeClock();

        (new LoginLogListener($this->entityManager, $clock))(new LoginSucceeded(new stdClass(), '42', '203.0.113.5'));
        $this->entityManager->clear();

        $log = $this->entityManager->getRepository(LoginLog::class)->findAll();
        self::assertCount(1, $log);
        self::assertSame('42', $log[0]->getUserId());
        self::assertSame('203.0.113.5', $log[0]->getIpAddress());
        self::assertEquals($clock->now(), $log[0]->getLoggedInAt());
    }
}
