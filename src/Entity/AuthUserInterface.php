<?php

namespace FwsDoctrineAuth\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface AuthUserInterface extends EntityInterface
{
    /**
     * Get userId
     *
     * @return int|null
     */
    public function getUserId(): ?int;

    /**
     * Set emailAddress
     *
     * @param string $emailAddress
     * @return BaseUser
     */
    public function setEmailAddress(string $emailAddress): AuthUserInterface;

    /**
     * Get emailAddress
     *
     * @return string|null
     */
    public function getEmailAddress(): ?string;

    /**
     * Set password
     *
     * @param string|null $password
     * @return BaseUser
     */
    public function setPassword(?string $password): AuthUserInterface;

    /**
     * Get password
     *
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     *
     * @param string|null $mobileNumber
     * @return BaseUser
     */
    public function setMobileNumber(?string $mobileNumber): AuthUserInterface;

    /**
     *
     * @return string|null
     */
    public function getMobileNumber(): ?string;

    /**
     * Set user active
     * @param bool $userActive
     * @return BaseUser
     */
    public function setUserActive(bool $userActive): AuthUserInterface;

    /**
     * Is the user active and able to log in?
     *
     * @return bool
     */
    public function isUserActive(): bool;

    /**
     * Set the date and time the user was created
     *
     * @param DateTimeInterface $dateCreated
     * @return BaseUser
     */
    public function setDateCreated(DateTimeInterface $dateCreated): AuthUserInterface;

    /**
     * Get the date and time the user was created
     *
     * @return DateTimeInterface|null
     */
    public function getDateCreated(): ?DateTimeInterface;

    /**
     * User has 2FA methods set
     * @return bool
     */
    public function hasAuthMethods(): bool;

    /**
     * Check if authentication method set
     * @param TwoFactorAuthMethod|string $authMethod
     * @return bool
     */
    public function hasAuthMethod(TwoFactorAuthMethod|string $authMethod): bool;

    /**
     * Count number of authentication methods
     * @return int
     */
    public function countAuthMethods(): int;

    /**
     * Get the users 2FA methods
     * @return Collection|null
     */
    public function getAuthMethods(): ?Collection;

    /**
     * Add auth method to collection
     * @param TwoFactorAuthMethod $authMethod
     * @return AuthUserInterface
     */
    public function addAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface;

    /**
     * Remove auth methods from collection
     * @param ArrayCollection $authMethods
     * @return AuthUserInterface
     */
    public function removeAuthMethods(ArrayCollection $authMethods): AuthUserInterface;

    /**
     * Remove auth method from collection
     * @param TwoFactorAuthMethod $authMethod
     * @return AuthUserInterface
     */
    public function removeAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface;

    /**
     *
     * @return Collection|null
     */
    public function getLogins(): ?Collection;

    /**
     * Add logins collection
     * @param ArrayCollection $logins
     * @return AuthUserInterface
     */
    public function addLogins(ArrayCollection $logins): AuthUserInterface;

    /**
     * Add login to collection
     * @param LoginLog $login
     * @return AuthUserInterface
     */
    public function addLogin(LoginLog $login): AuthUserInterface;

    /**
     * Remove logins collection
     * @param ArrayCollection $logins
     * @return AuthUserInterface
     */
    public function removeLogins(ArrayCollection $logins): AuthUserInterface;

    /**
     * Remove login from collection
     * @param LoginLog $login
     * @return AuthUserInterface
     */
    public function removeLogin(LoginLog $login): AuthUserInterface;


}