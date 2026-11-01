<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\User;

/**
 * How the module reads and updates your users. The module's default implementation is driven by attributes on your
 * user entity, so you only need to implement this if your users are not stored that way.
 */
interface UserProviderInterface
{
    /**
     * Users that may match the identity. More than one is possible, as a short blind index can collide.
     *
     * @return iterable<object>
     */
    public function findByIdentity(string $identity): iterable;

    public function findById(string $id): ?object;

    public function getId(object $user): string;

    public function getIdentity(object $user): string;

    public function getPasswordHash(object $user): ?string;

    /**
     * Save a new password hash for the user.
     */
    public function setPasswordHash(object $user, string $hash): void;

    /**
     * Whether the user is allowed to log in, so active and not deleted.
     */
    public function isEnabled(object $user): bool;
}
