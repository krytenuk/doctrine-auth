<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

#[CoversClass(FailedLoginAttemptsLog::class)]
class FailedLoginAttemptsLogTest extends AbstractTestCase
{
    /**
     * Test FailedLoginAttemptsLog entity
     *
     * @group doctrine-entities
     * @return void
     * @throws ReflectionException
     */
    public function testFailedLoginAttemptsLogEntity()
    {
        $testLogId      = 1;
        $testEmail      = 'test@example.com';
        $testIPAddress  = '127.0.0.1';
        $testDateLogged = new DateTime('now');

        $failedLog = new FailedLoginAttemptsLog();

        $this->assertInstanceOf(FailedLoginAttemptsLog::class, $failedLog);
        $this->assertInstanceOf(EntityInterface::class, $failedLog);

        $this->testId($failedLog, 'loginAttemptId', 'getLoginAttemptId', $testLogId);

        $this->assertNull($failedLog->getEmailAddress());
        $this->assertInstanceOf(FailedLoginAttemptsLog::class, $failedLog->setEmailAddress($testEmail));
        $this->assertEquals($testEmail, $failedLog->getEmailAddress());

        $this->assertNull($failedLog->getIpAddress());
        $this->assertInstanceOf(FailedLoginAttemptsLog::class, $failedLog->setIpAddress($testIPAddress));
        $this->assertEquals($testIPAddress, $failedLog->getIpAddress());

        $this->assertInstanceOf(DateTimeInterface::class, $failedLog->getDateLogged());
        $this->assertInstanceOf(FailedLoginAttemptsLog::class, $failedLog->setDateLogged($testDateLogged));
        $this->assertEquals($testDateLogged, $failedLog->getDateLogged());
    }
}
