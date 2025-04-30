<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use DateTime;
use DateTimeInterface;
use FwsDoctrineAuth\Entity\GoogleAuth;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GoogleAuth::class)]
class GoogleAuthTest extends AbstractTestCase
{
    /**
     * Test GoogleAuth entity (deprecated)
     *
     * @group doctrine-entities
     * @return void
     */
    public function testGoogleAuthEntity()
    {
        $testSecret     = 'test-secret';
        $testAuthMethod = new TwoFactorAuthMethod();
        $testDateLogged = new DateTime('now');

        $googleAuth = new GoogleAuth();

        $this->assertNull($googleAuth->getSecret());
        $this->assertInstanceOf(GoogleAuth::class, $googleAuth->setSecret($testSecret));
        $this->assertEquals($testSecret, $googleAuth->getSecret());

        $this->assertNull($googleAuth->getAuthMethod());
        $this->assertInstanceOf(GoogleAuth::class, $googleAuth->setAuthMethod($testAuthMethod));
        $this->assertEquals($testAuthMethod, $googleAuth->getAuthMethod());

        $this->assertInstanceOf(DateTimeInterface::class, $googleAuth->getDateCreated());
        $this->assertInstanceOf(GoogleAuth::class, $googleAuth->setDateCreated($testDateLogged));
        $this->assertEquals($testDateLogged, $googleAuth->getDateCreated());
    }
}
