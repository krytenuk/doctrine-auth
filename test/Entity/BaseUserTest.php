<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Entity\PasswordReminder;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use FwsDoctrineAuth\Entity\UserRole;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\BulkSmsAdapter;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\EmailAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionException;

use function serialize;
use function sprintf;
use function unserialize;

#[CoversClass(BaseUser::class)]
class BaseUserTest extends AbstractTestCase
{
    /**
     * Test BaseUser entity
     *
     * @group doctrine-entities
     * @return void
     * @throws ReflectionException|Exception
     */
    public function testUserEntity()
    {
        $testUserId           = 1;
        $testEmail            = 'test@example.com';
        $testPassword         = 'test-password';
        $testMobileNumber     = '012345678901';
        $testDateCreated      = new DateTime('now');
        $testDateModified     = new DateTime('now');
        $testUserRole         = new UserRole();
        $testPasswordReminder = new PasswordReminder();

        $testAuthMethodEmail = new TwoFactorAuthMethod();
        $testAuthMethodEmail->setMethod(EmailAdapter::getName());
        $testAuthMethodSms = new TwoFactorAuthMethod();
        $testAuthMethodSms->setMethod(BulkSmsAdapter::getName());
        $testAuthMethodApp = new TwoFactorAuthMethod();
        $testAuthMethodApp->setMethod(AuthenticationAppAdapter::getName());
        $testAuthMethods = new ArrayCollection([
            $testAuthMethodEmail,
            $testAuthMethodSms,
        ]);

        $testLoginLog1 = new LoginLog();
        $testLoginLog1->setDateLogged(new DateTime('now'));
        $testLoginLog2 = clone $testLoginLog1;
        $testLoginLog3 = clone $testLoginLog1;
        $reflection    = new ReflectionClass($testLoginLog1);
        $property      = $reflection->getProperty('logId');
        $property->setValue($testLoginLog1, 1);
        $property->setAccessible(false);
        $reflection = new ReflectionClass($testLoginLog2);
        $property   = $reflection->getProperty('logId');
        $property->setValue($testLoginLog2, 2);
        $property->setAccessible(false);
        $reflection = new ReflectionClass($testLoginLog3);
        $property   = $reflection->getProperty('logId');
        $property->setValue($testLoginLog3, 3);
        $property->setAccessible(false);
        $testLoginLogs = new ArrayCollection([
            $testLoginLog1,
            $testLoginLog2,
        ]);

        $user = new BaseUser();

        $this->assertInstanceOf(AuthUserInterface::class, $user);
        $this->assertInstanceOf(EntityInterface::class, $user);

        $this->testId($user, 'userId', 'getUserId', $testUserId);

        $this->assertNull($user->getEmailAddress());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setEmailAddress($testEmail));
        $this->assertEquals($testEmail, $user->getEmailAddress());

        $this->assertNull($user->getPassword());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setPassword($testPassword));
        $this->assertEquals($testPassword, $user->getPassword());

        $this->assertNull($user->getMobileNumber());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setMobileNumber($testMobileNumber));
        $this->assertEquals($testMobileNumber, $user->getMobileNumber());

        $this->assertFalse($user->isUserActive());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setUserActive(true));
        $this->assertTrue($user->isUserActive());

        $this->assertNull($user->getDateCreated());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setDateCreated($testDateCreated));
        $this->assertEquals($testDateCreated, $user->getDateCreated());

        $this->assertNull($user->getDateModified());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setDateModified($testDateModified));
        $this->assertEquals($testDateModified, $user->getDateModified());

        $this->assertNull($user->getUserRole());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setUserRole($testUserRole));
        $this->assertEquals($testUserRole, $user->getUserRole());

        $this->assertFalse($user->hasPasswordReminder());
        $this->assertNull($user->getPasswordReminder());
        $this->assertInstanceOf(AuthUserInterface::class, $user->setPasswordReminder($testPasswordReminder));
        $this->assertTrue($user->hasPasswordReminder());
        $this->assertEquals($testPasswordReminder, $user->getPasswordReminder());

        $this->assertFalse($user->hasAuthMethods());
        $this->assertFalse($user->hasAuthMethod(EmailAdapter::getName()));
        $this->assertNull($user->getAuthMethod(EmailAdapter::getName()));
        $this->assertEquals(0, $user->countAuthMethods(), 'countAuthMethods did not return 0');

        $this->assertInstanceOf(AuthUserInterface::class, $user->addAuthMethods($testAuthMethods));
        $this->assertTrue($user->hasAuthMethods());
        $this->assertTrue($user->hasAuthMethod(EmailAdapter::getName()));
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $user->getAuthMethod(EmailAdapter::getName()));
        $this->assertEquals($testAuthMethods->count(), $user->countAuthMethods(), sprintf('countAuthMethods method did not not equal %d', $testAuthMethods->count()));
        $this->assertFalse($user->hasAuthMethod(AuthenticationAppAdapter::getName()));
        $this->assertNull($user->getAuthMethod(AuthenticationAppAdapter::getName()));

        $this->assertInstanceOf(AuthUserInterface::class, $user->addAuthMethod($testAuthMethodApp));
        $this->assertTrue($user->hasAuthMethods());
        $this->assertEquals($testAuthMethods->count() + 1, $user->countAuthMethods(), sprintf('countAuthMethods method did not return %d', $testAuthMethods->count() + 1));
        $this->assertTrue($user->hasAuthMethod(AuthenticationAppAdapter::getName()));
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $user->getAuthMethod(AuthenticationAppAdapter::getName()));

        $this->assertInstanceOf(AuthUserInterface::class, $user->removeAuthMethod($testAuthMethodApp));
        $this->assertTrue($user->hasAuthMethods());
        $this->assertEquals($testAuthMethods->count(), $user->countAuthMethods(), sprintf('countAuthMethods method did not return %d', $testAuthMethods->count()));
        $this->assertFalse($user->hasAuthMethod(AuthenticationAppAdapter::getName()));
        $this->assertNull($user->getAuthMethod(AuthenticationAppAdapter::getName()));

        $this->assertInstanceOf(AuthUserInterface::class, $user->removeAuthMethods($testAuthMethods));
        $this->assertFalse($user->hasAuthMethods());
        $this->assertFalse($user->hasAuthMethod(EmailAdapter::getName()));
        $this->assertNull($user->getAuthMethod(EmailAdapter::getName()));
        $this->assertEquals(0, $user->countAuthMethods(), 'countAuthMethods did not return 0');

        $this->assertEquals(0, $user->getLogins()->count());
        $this->assertInstanceOf(AuthUserInterface::class, $user->addLogins($testLoginLogs));
        $loginsLogs = $user->getLogins();
        $this->assertEquals($testLoginLogs, $loginsLogs);
        $this->assertEquals($testLoginLogs->count(), $loginsLogs->count(), sprintf('getLogins count method not equal to %d', $testLoginLogs->count()));
        $this->assertEquals($testLoginLog1->getLogId(), $loginsLogs->get(0)->getLogId(), sprintf('getLogins #0 logId not equal to %d', $testLoginLog1->getLogId()));
        $this->assertEquals($testLoginLog2->getLogId(), $loginsLogs->get(1)->getLogId(), sprintf('getLogins #1 logId not equal to %d', $testLoginLog2->getLogId()));

        $this->assertInstanceOf(AuthUserInterface::class, $user->addLogin($testLoginLog3));
        $loginsLogs = $user->getLogins();
        $this->assertEquals($testLoginLogs->count() + 1, $loginsLogs->count(), sprintf('getLogins count method not equal to %d', $testLoginLogs->count() + 1));
        $this->assertInstanceOf(ArrayCollection::class, $loginsLogs);
        $this->assertEquals($testLoginLog1->getLogId(), $loginsLogs->get(0)->getLogId(), sprintf('getLogins #0 logId not equal to %d', $testLoginLog1->getLogId()));
        $this->assertEquals($testLoginLog2->getLogId(), $loginsLogs->get(1)->getLogId(), sprintf('getLogins #1 logId not equal to %d', $testLoginLog2->getLogId()));
        $this->assertEquals($testLoginLog3->getLogId(), $loginsLogs->get(2)->getLogId(), sprintf('getLogins #2 logId not equal to %d', $testLoginLog3->getLogId()));

        $this->assertInstanceOf(AuthUserInterface::class, $user->removeLogin($testLoginLog3));
        $loginsLogs = $user->getLogins();
        $this->assertEquals($testLoginLogs, $loginsLogs);
        $this->assertEquals($testLoginLogs->count(), $loginsLogs->count(), sprintf('getLogins count method not equal to %d', $testLoginLogs->count()));
        $this->assertEquals($testLoginLog1->getLogId(), $loginsLogs->get(0)->getLogId(), sprintf('getLogins #0 logId not equal to %d', $testLoginLog1->getLogId()));
        $this->assertEquals($testLoginLog2->getLogId(), $loginsLogs->get(1)->getLogId(), sprintf('getLogins #1 logId not equal to %d', $testLoginLog2->getLogId()));

        $this->assertInstanceOf(AuthUserInterface::class, $user->removeLogins($testLoginLogs));
        $loginsLogs = $user->getLogins();
        $this->assertEquals(0, $user->getLogins()->count());

        $user->prePersist();
        $dateCreatedPrePersist = $user->getDateCreated();
        $this->assertInstanceOf(DateTimeInterface::class, $dateCreatedPrePersist);
        $this->assertNotEquals($testDateCreated, $dateCreatedPrePersist);
        $dateModifiedPrePersist = $user->getDateModified();
        $this->assertInstanceOf(DateTimeInterface::class, $dateModifiedPrePersist);
        $this->assertNotEquals($testDateModified, $dateModifiedPrePersist);

        $user->preUpdate();
        $dateModifiedPreUpdate = $user->getDateModified();
        $this->assertInstanceOf(DateTimeInterface::class, $dateModifiedPrePersist);
        $this->assertNotEquals($dateModifiedPrePersist, $dateModifiedPreUpdate);

        $serializedUser = serialize($user);
        $this->assertIsString($serializedUser);
        /** @var BaseUser $unserializedUser */
        $unserializedUser = unserialize($serializedUser);
        $this->assertInstanceOf(AuthUserInterface::class, $unserializedUser);
        $this->assertEquals($testUserId, $unserializedUser->getUserId());
        $this->assertEquals($testEmail, $unserializedUser->getEmailAddress());
        $this->assertNull($unserializedUser->getPassword());
        $this->assertEquals($testMobileNumber, $unserializedUser->getMobileNumber());
        $this->assertTrue($unserializedUser->isUserActive());
        $this->assertInstanceOf(DateTimeInterface::class, $unserializedUser->getDateCreated());
        $this->assertEquals($dateCreatedPrePersist, $unserializedUser->getDateCreated());
        $this->assertInstanceOf(DateTimeInterface::class, $unserializedUser->getDateModified());
        $this->assertEquals($dateModifiedPreUpdate, $unserializedUser->getDateModified());
        $this->assertEquals($testUserRole, $unserializedUser->getUserRole());
        $this->assertNull($unserializedUser->getPasswordReminder());
        $this->assertNull($unserializedUser->getAuthMethods());
        $this->assertNull($unserializedUser->getLogins());
    }
}
