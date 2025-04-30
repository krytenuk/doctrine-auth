<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use Exception;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Entity\GoogleAuth;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

#[CoversClass(TwoFactorAuthMethod::class)]
class TwoFactorAuthMethodTest extends AbstractTestCase
{
    /**
     * Test TwoFactorAuthMethod entity
     *
     * @group doctrine-entities
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function testTwoFactorAuthenticationEntity()
    {
        $testTwoFactorAuthMethodId     = 1;
        $testTwoFactorAuthMethod       = 'google-auth';
        $testTwoFactorAuthSettings     = ['test-setting'];
        $testUser                      = new BaseUser();
        $testGoogleAuthEntity          = new GoogleAuth();
        $testTwoFactorAuthMethodEntity = new TwoFactorAuthMethod();
        $dateTimeString                = '1970-01-01 00:00:00';
        $testDateCreated               = new DateTime($dateTimeString);

        $this->assertInstanceOf(TwoFactorAuthMethod::class, $testTwoFactorAuthMethodEntity);
        $this->assertInstanceOf(EntityInterface::class, $testTwoFactorAuthMethodEntity);

        $this->testId($testTwoFactorAuthMethodEntity, 'authMethodId', 'getAuthMethodId', $testTwoFactorAuthMethodId);

        $this->assertNull($testTwoFactorAuthMethodEntity->getMethod());
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $testTwoFactorAuthMethodEntity->setMethod($testTwoFactorAuthMethod));
        $this->assertEquals($testTwoFactorAuthMethod, $testTwoFactorAuthMethodEntity->getMethod());

        $this->assertNull($testTwoFactorAuthMethodEntity->getUser());
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $testTwoFactorAuthMethodEntity->setUser($testUser));
        $this->assertInstanceOf(BaseUser::class, $testTwoFactorAuthMethodEntity->getUser());

        $this->assertInstanceOf(DateTimeInterface::class, $testTwoFactorAuthMethodEntity->getDateCreated());
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $testTwoFactorAuthMethodEntity->setDateCreated($testDateCreated));
        $this->assertInstanceOf(DateTimeInterface::class, $testTwoFactorAuthMethodEntity->getDateCreated());
        $this->assertEquals($dateTimeString, $testTwoFactorAuthMethodEntity->getDateCreated()->format('Y-m-d H:i:s'));

        $this->assertIsArray($testTwoFactorAuthMethodEntity->getSettings());
        $this->assertEmpty($testTwoFactorAuthMethodEntity->getSettings());
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $testTwoFactorAuthMethodEntity->setSettings($testTwoFactorAuthSettings));
        $this->assertEquals($testTwoFactorAuthSettings, $testTwoFactorAuthMethodEntity->getSettings());

        $this->assertNull($testTwoFactorAuthMethodEntity->getGoogleAuth());
        $this->assertInstanceOf(TwoFactorAuthMethod::class, $testTwoFactorAuthMethodEntity->setGoogleAuth($testGoogleAuthEntity));
        $this->assertInstanceOf(GoogleAuth::class, $testTwoFactorAuthMethodEntity->getGoogleAuth());
    }
}
