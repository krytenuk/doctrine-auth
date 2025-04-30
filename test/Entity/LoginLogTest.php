<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use Exception;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Entity\LoginLog;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

#[CoversClass(LoginLog::class)]
class LoginLogTest extends AbstractTestCase
{
    /**
     * Test LoginLog entity
     *
     * @group doctrine-entities
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function testFailedLoginAttemptsLogEntity()
    {
        $testLogId      = 1;
        $testUser       = new BaseUser();
        $loginLog       = new LoginLog();
        $dateTimeString = '1970-01-01 00:00:00';
        $testDateLogged = new DateTime($dateTimeString);

        $this->assertInstanceOf(LoginLog::class, $loginLog);
        $this->assertInstanceOf(EntityInterface::class, $loginLog);

        $this->testId($loginLog, 'logId', 'getLogId', $testLogId);

        $this->assertNull($loginLog->getUser());
        $this->assertInstanceOf(LoginLog::class, $loginLog->setUser($testUser));
        $this->assertInstanceOf(BaseUser::class, $loginLog->getUser());

        $this->assertFalse($loginLog->getUsed2fa());
        $this->assertInstanceOf(LoginLog::class, $loginLog->setUsed2fa(true));
        $this->assertTrue($loginLog->getUsed2fa());

        $this->assertInstanceOf(DateTimeInterface::class, $loginLog->getDateLogged());
        $this->assertInstanceOf(LoginLog::class, $loginLog->setDateLogged($testDateLogged));
        $this->assertInstanceOf(DateTimeInterface::class, $loginLog->getDateLogged());
        $this->assertEquals($dateTimeString, $loginLog->getDateLogged()->format('Y-m-d H:i:s'));
    }
}
