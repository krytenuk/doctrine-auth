<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\TwoFactor;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\TwoFactor\EnabledMethodsPolicy;
use FwsDoctrineAuth\TwoFactor\TwoFactorMethods;
use FwsDoctrineAuthTest\Support\FakeTwoFactorMethod;
use FwsDoctrineAuthTest\Support\FakeUser;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TwoFactorMethodsTest extends TestCase
{
    private function methods(array $config, array $services): TwoFactorMethods
    {
        return new TwoFactorMethods(new ServiceManager(['services' => $services]), $config);
    }

    public function testListsOnlyTheMethodsAUserHasSetUp(): void
    {
        $methods = $this->methods(
            ['app' => 'app-service', 'email' => 'email-service'],
            ['app-service' => new FakeTwoFactorMethod(['7']), 'email-service' => new FakeTwoFactorMethod(['8'])]
        );

        self::assertSame(['app', 'email'], $methods->names());
        self::assertSame(['app'], $methods->enabledFor(new FakeUser('7', 'a@example.com', 'hash')));
        self::assertSame([], $methods->enabledFor(new FakeUser('9', 'b@example.com', 'hash')));
    }

    public function testANullServiceRemovesAMethod(): void
    {
        $methods = $this->methods(['app' => null], []);

        self::assertSame([], $methods->names());
        self::assertNull($methods->get('app'));
    }

    public function testAnUnknownMethodIsNull(): void
    {
        self::assertNull($this->methods([], [])->get('app'));
    }

    public function testAServiceThatIsNotAMethodFailsClearly(): void
    {
        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('"app"');

        $this->methods(['app' => 'app-service'], ['app-service' => new stdClass()])->get('app');
    }

    public function testThePolicyAsksOnlyUsersWithAMethodSetUp(): void
    {
        $policy = new EnabledMethodsPolicy(
            $this->methods(['app' => 'app-service'], ['app-service' => new FakeTwoFactorMethod(['7'])])
        );

        self::assertTrue($policy->requiresTwoFactor(new FakeUser('7', 'a@example.com', 'hash')));
        self::assertFalse($policy->requiresTwoFactor(new FakeUser('8', 'b@example.com', 'hash')));
    }

    public function testWithNoMethodsInstalledNobodyIsAsked(): void
    {
        $policy = new EnabledMethodsPolicy($this->methods([], []));

        self::assertFalse($policy->requiresTwoFactor(new FakeUser('7', 'a@example.com', 'hash')));
    }
}
