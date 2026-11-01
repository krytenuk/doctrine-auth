<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Doctrine;

use DateTimeImmutable;
use FwsDoctrineAuth\Doctrine\AttributeUserProvider;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuthTest\Support\BadEntity\NoCredential;
use FwsDoctrineAuthTest\Support\BadEntity\TwoIdentities;
use FwsDoctrineAuthTest\Support\DoctrineTestCase;
use FwsDoctrineAuthTest\Support\Entity\IndexedUser;
use FwsDoctrineAuthTest\Support\Entity\Member;
use FwsDoctrineAuthTest\Support\Entity\StatusUser;
use FwsDoctrineAuthTest\Support\Entity\User;

final class AttributeUserProviderTest extends DoctrineTestCase
{
    private function provider(string $class = User::class, ?\Closure $indexer = null): AttributeUserProvider
    {
        return new AttributeUserProvider($this->entityManager, $class, $indexer);
    }

    private function save(object $entity): object
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    public function testFindsByIdentityAndReadsProperties(): void
    {
        $user     = $this->save(new User('a@example.com', 'the-hash'));
        $provider = $this->provider();

        self::assertSame([$user], [...$provider->findByIdentity('a@example.com')]);
        self::assertSame([], [...$provider->findByIdentity('b@example.com')]);
        self::assertSame((string) $user->id, $provider->getId($user));
        self::assertSame('a@example.com', $provider->getIdentity($user));
        self::assertSame('the-hash', $provider->getPasswordHash($user));
    }

    public function testFindsById(): void
    {
        $user = $this->save(new User('a@example.com', 'hash'));

        self::assertSame($user, $this->provider()->findById((string) $user->id));
        self::assertNull($this->provider()->findById('999'));
    }

    public function testSavesANewPasswordHash(): void
    {
        $user = $this->save(new User('a@example.com', 'old'));

        $this->provider()->setPasswordHash($user, 'new');
        $this->entityManager->clear();

        self::assertSame('new', $this->entityManager->find(User::class, $user->id)->getPassword());
    }

    public function testActiveAndDeletedDecideWhetherAUserIsEnabled(): void
    {
        $user     = $this->save(new User('a@example.com', 'hash'));
        $provider = $this->provider();
        self::assertTrue($provider->isEnabled($user));

        $user->active = false;
        self::assertFalse($provider->isEnabled($user));

        $user->active    = true;
        $user->deletedAt = new DateTimeImmutable();
        self::assertFalse($provider->isEnabled($user));
    }

    public function testActiveAndDeletedValuesCanBeCustomised(): void
    {
        $user     = $this->save(new StatusUser());
        $provider = $this->provider(StatusUser::class);
        self::assertTrue($provider->isEnabled($user));

        $user->status = 'suspended';
        self::assertFalse($provider->isEnabled($user));

        $user->status  = 'live';
        $user->removed = true;
        self::assertFalse($provider->isEnabled($user));
    }

    public function testPrivatePropertiesOfAParentClassAreFound(): void
    {
        $member   = $this->save(new Member('someone', 'hash'));
        $provider = $this->provider(Member::class);

        self::assertSame([$member], [...$provider->findByIdentity('someone')]);
        self::assertSame('hash', $provider->getPasswordHash($member));
    }

    public function testBlindIndexLookupOnlyKeepsUsersWithThatIdentity(): void
    {
        $wanted = $this->save(new IndexedUser('a@example.com', 'collision'));
        $this->save(new IndexedUser('b@example.com', 'collision'));
        $this->save(new IndexedUser('c@example.com', 'different'));

        $calls    = [];
        $provider = $this->provider(
            IndexedUser::class,
            static function (string $class, string $property, string $value, string $index) use (&$calls): string {
                $calls[] = [$class, $property, $value, $index];

                return 'collision';
            }
        );

        self::assertSame([$wanted], array_values([...$provider->findByIdentity('A@example.com')]));
        self::assertSame([[IndexedUser::class, 'email', 'A@example.com', 'emailIndex']], $calls);
    }

    public function testBlindIndexWithoutAnIndexerFailsClearly(): void
    {
        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('krytenuk/doctrine-crypt');

        $this->provider(IndexedUser::class)->findByIdentity('a@example.com');
    }

    public function testMissingAttributeFailsClearly(): void
    {
        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('CredentialProperty');

        $this->provider(NoCredential::class)->findByIdentity('a@example.com');
    }

    public function testRepeatedAttributeFailsClearly(): void
    {
        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('more than one');

        $this->provider(TwoIdentities::class)->findByIdentity('a@example.com');
    }
}
