<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Password;

use FwsDoctrineAuth\Password\NativePasswordHasher;
use PHPUnit\Framework\TestCase;

final class NativePasswordHasherTest extends TestCase
{
    public function testHashVerifies(): void
    {
        $hasher = new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 4]);
        $hash   = $hasher->hash('secret');

        self::assertTrue($hasher->verify('secret', $hash));
        self::assertFalse($hasher->verify('wrong', $hash));
    }

    /**
     * A hash made by laminas-crypt's Bcrypt in version 1, so existing passwords keep working.
     */
    public function testExistingBcryptHashesVerify(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT, ['cost' => 4]);

        self::assertStringStartsWith('$2y$', $hash);
        self::assertTrue((new NativePasswordHasher())->verify('secret', $hash));
    }

    public function testNeedsRehashWhenTheCostChanges(): void
    {
        $hash = (new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 4]))->hash('secret');

        self::assertFalse((new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 4]))->needsRehash($hash));
        self::assertTrue((new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 5]))->needsRehash($hash));
    }
}
