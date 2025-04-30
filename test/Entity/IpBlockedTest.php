<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Entity\IpBlocked;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

#[CoversClass(IpBlocked::class)]
class IpBlockedTest extends AbstractTestCase
{
    /**
     * Test IpBlocked entity
     *
     * @group doctrine-entities
     * @return void
     * @throws ReflectionException
     */
    public function testFailedLoginAttemptsLogEntity()
    {
        $testBlockedId           = 1;
        $testBlockedIpAddress    = '127.0.0.1';
        $testBlockedEmailAddress = 'test@example.com';
        $testDateBlocked         = new DateTime('1970-01-01');
        $ipBlocked               = new IpBlocked();

        $this->assertInstanceOf(IpBlocked::class, $ipBlocked);
        $this->assertInstanceOf(EntityInterface::class, $ipBlocked);

        $this->testId($ipBlocked, 'blockId', 'getBlockId', $testBlockedId);

        $this->assertNull($ipBlocked->getIpAddress());
        $this->assertInstanceOf(IpBlocked::class, $ipBlocked->setIpAddress($testBlockedIpAddress));
        $this->assertEquals($testBlockedIpAddress, $ipBlocked->getIpAddress());

        $this->assertNull($ipBlocked->getEmailAddress());
        $this->assertInstanceOf(IpBlocked::class, $ipBlocked->setEmailAddress($testBlockedEmailAddress));
        $this->assertEquals($testBlockedEmailAddress, $ipBlocked->getEmailAddress());

        $this->assertInstanceOf(DateTimeInterface::class, $ipBlocked->getDateBlocked());
        $this->assertInstanceOf(IpBlocked::class, $ipBlocked->setDateBlocked($testDateBlocked));
        $this->assertEquals($testDateBlocked, $ipBlocked->getDateBlocked());
    }
}
