<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface AuthUserInterface extends EntityInterface
{
    public function getUserId(): int|null;

    public function setEmailAddress(string $emailAddress): AuthUserInterface;

    public function getEmailAddress(): string|null;

    public function setPassword(string|null $password): AuthUserInterface;

    public function getPassword(): string|null;

    public function setUserRole(UserRole $userRole): AuthUserInterface;

    public function getUserRole(): UserRole|null;

    public function setMobileNumber(string|null $mobileNumber): AuthUserInterface;

    public function getMobileNumber(): string|null;

    public function setUserActive(bool $userActive): AuthUserInterface;

    /**
     * Is the user active and able to log in?
     */
    public function isUserActive(): bool;

    /**
     * Set the date and time the user was created
     */
    public function setDateCreated(DateTimeInterface $dateCreated): AuthUserInterface;

    /**
     * Get the date and time the user was created
     */
    public function getDateCreated(): DateTimeInterface|null;

    /**
     * User has 2FA methods set
     */
    public function hasAuthMethods(): bool;

    /**
     * Check if authentication method set
     */
    public function hasAuthMethod(TwoFactorAuthMethod|string $authMethod): bool;

    /**
     * Count number of authentication methods
     */
    public function countAuthMethods(): int;

    /**
     * Get the users 2FA methods
     */
    public function getAuthMethods(): Collection|null;

    /**
     * Add auth method to collection
     */
    public function addAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface;

    /**
     * Remove auth methods from collection
     */
    public function removeAuthMethods(ArrayCollection $authMethods): AuthUserInterface;

    /**
     * Remove auth method from collection
     */
    public function removeAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface;

    public function getLogins(): Collection|null;

    /**
     * Add logins collection
     */
    public function addLogins(ArrayCollection $logins): AuthUserInterface;

    /**
     * Add login to collection
     */
    public function addLogin(LoginLog $login): AuthUserInterface;

    /**
     * Remove logins collection
     */
    public function removeLogins(ArrayCollection $logins): AuthUserInterface;

    /**
     * Remove login from collection
     */
    public function removeLogin(LoginLog $login): AuthUserInterface;
}
