<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use Exception;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Entity\PasswordReminder;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

use function uniqid;

#[CoversClass(PasswordReminder::class)]
class PasswordReminderTest extends AbstractTestCase
{
    /**
     * Test Password Reminder entity
     *
     * @group doctrine-entities
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function testPasswordReminderEntity()
    {
        $testPasswordReminderId = 1;
        $testUser               = new BaseUser();
        $testCode               = uniqid();
        $passwordReminderEntity = new PasswordReminder();
        $dateTimeString         = '1970-01-01 00:00:00';
        $testDateCreated        = new DateTime($dateTimeString);

        $this->assertInstanceOf(PasswordReminder::class, $passwordReminderEntity);
        $this->assertInstanceOf(EntityInterface::class, $passwordReminderEntity);

        $this->testId($passwordReminderEntity, 'passwordReminderId', 'getPasswordReminderId', $testPasswordReminderId);

        $this->assertNull($passwordReminderEntity->getCode());
        $this->assertInstanceOf(PasswordReminder::class, $passwordReminderEntity->setCode($testCode));
        $this->assertEquals($testCode, $passwordReminderEntity->getCode());

        $this->assertNull($passwordReminderEntity->getUser());
        $this->assertInstanceOf(PasswordReminder::class, $passwordReminderEntity->setUser($testUser));
        $this->assertInstanceOf(BaseUser::class, $passwordReminderEntity->getUser());

        $this->assertInstanceOf(DateTimeInterface::class, $passwordReminderEntity->getDateCreated());
        $this->assertInstanceOf(PasswordReminder::class, $passwordReminderEntity->setDateCreated($testDateCreated));
        $this->assertInstanceOf(DateTimeInterface::class, $passwordReminderEntity->getDateCreated());
        $this->assertEquals($dateTimeString, $passwordReminderEntity->getDateCreated()->format('Y-m-d H:i:s'));
    }
}
