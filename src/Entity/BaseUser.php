<?php /** @noinspection ALL */

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * BaseUser
 * @ORM\Entity
 * @ORM\Table(name="users", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"},
 *    indexes={
 *        @ORM\Index(name="user_role_id", columns={"user_role_id"}),
 *    }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\HasLifecycleCallbacks
 * @author Garry Childs <info@freedomwebservices.net>
 */
class BaseUser implements AuthUserInterface
{

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $userId = null;

    /**
     * identity_property in config
     * @var string|null
     *
     * @ORM\Column(name="email_address", type="text", nullable=false, unique=true)
     */
    protected ?string $emailAddress = null;

    /**
     * credential_property in config
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=256, nullable=true)
     */
    protected ?string $password = null;

    /**
     * 
     * @var string|null
     *
     * @ORM\Column(name="mobile_number", type="text", nullable=true)
     */
    protected ?string $mobileNumber = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="user_active", type="boolean", nullable=false, options={"default":0})
     */
    protected bool $userActive = false;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_created", type="datetime")
     */
    protected ?DateTimeInterface $dateCreated = null;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_modified", type="datetime")
     */
    protected ?DateTimeInterface $dateModified = null;

    /**
     * @var UserRole
     *
     * @ORM\ManyToOne(targetEntity="FwsDoctrineAuth\Entity\UserRole", cascade={"persist", "merge"}, fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_role_id", referencedColumnName="user_role_id")
     * })
     */
    protected ?UserRole $userRole = null;

    /**
     * @var PasswordReminder|null
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\PasswordReminder", mappedBy="user", orphanRemoval=true, cascade={"persist", "merge"})
     */
    protected ?PasswordReminder $passwordReminder = null;

    /**
     * @var Collection|null
     *
     * @ORM\OneToMany(targetEntity="FwsDoctrineAuth\Entity\TwoFactorAuthMethod", mappedBy="user", orphanRemoval=true, cascade={"persist", "merge"}, fetch="EAGER")
     */
    protected ?Collection $authMethods = null;

    /**
     * @var Collection|null
     *
     * @ORM\OneToMany(targetEntity="FwsDoctrineAuth\Entity\LoginLog", mappedBy="user", orphanRemoval=true, cascade={"persist", "merge"}, fetch="EAGER")
     */
    protected ?Collection $logins = null;

    public function __construct()
    {
        $this->authMethods = new ArrayCollection();
        $this->logins = new ArrayCollection();
    }

    /**
     * Get userId
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set emailAddress
     *
     * @param string $emailAddress
     * @return AuthUserInterface
     */
    public function setEmailAddress(string $emailAddress): AuthUserInterface
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get emailAddress
     *
     * @return string|null
     */
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    /**
     * Set password
     *
     * @param string|null $password
     * @return AuthUserInterface
     */
    public function setPassword(?string $password): AuthUserInterface
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     *
     * @param string|null $mobileNumber
     * @return AuthUserInterface
     */
    public function setMobileNumber(?string $mobileNumber): AuthUserInterface
    {
        $this->mobileNumber = $mobileNumber;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    /**
     * Set user active
     * @param bool $userActive
     * @return AuthUserInterface
     */
    public function setUserActive(bool $userActive): AuthUserInterface
    {
        $this->userActive = $userActive;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function isUserActive(): bool
    {
        return $this->userActive;
    }

    /**
     * 
     * @param DateTimeInterface $dateCreated
     * @return AuthUserInterface
     */
    public function setDateCreated(DateTimeInterface $dateCreated): AuthUserInterface
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * 
     * @return DateTimeInterface|null
     */
    public function getDateCreated(): ?DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * 
     * @param DateTimeInterface $dateModified
     * @return AuthUserInterface
     */
    public function setDateModified(DateTimeInterface $dateModified): AuthUserInterface
    {
        $this->dateModified = $dateModified;
        return $this;
    }

    /**
     * 
     * @return DateTimeInterface|null
     */
    public function getDateModified(): ?DateTimeInterface
    {
        return $this->dateModified;
    }

    /**
     * Set userRole
     *
     * @param UserRole $userRole
     * @return AuthUserInterface
     */
    public function setUserRole(UserRole $userRole): AuthUserInterface
    {
        $this->userRole = $userRole;
        return $this;
    }

    /**
     * Get userRole
     *
     * @return UserRole|null
     */
    public function getUserRole(): ?UserRole
    {
        return $this->userRole;
    }

    /**
     * 
     * @return bool
     */
    public function hasPasswordReminder(): bool
    {
        return (bool) $this->getPasswordReminder();
    }

    /**
     * 
     * @return PasswordReminder|null
     */
    public function getPasswordReminder(): ?PasswordReminder
    {
        return $this->passwordReminder;
    }

    /**
     * 
     * @param PasswordReminder|null $passwordReminder
     * @return AuthUserInterface
     */
    public function setPasswordReminder(?PasswordReminder $passwordReminder): AuthUserInterface
    {
        $this->passwordReminder = $passwordReminder;
        return $this;
    }

    /**
     * 
     * @param string $authMethod
     * @return TwoFactorAuthMethod|null
     */
    public function getAuthMethod(string $authMethod): ?TwoFactorAuthMethod
    {
        if ($this->authMethods === null || $this->authMethods->count() === 0) {
            return null;
        }

        $method = $this->authMethods->filter(function(TwoFactorAuthMethod $entity) use ($authMethod): bool {
            return $entity->getMethod() === $authMethod;
        })->first();

        return $method ? $method : null;
    }

    /**
     * User has 2FA methods set
     * @return bool
     */
    public function hasAuthMethods(): bool
    {
        return (bool) $this->countAuthMethods();
    }

    /**
     * Check if authentication method set
     * @param TwoFactorAuthMethod|string $authMethod
     * @return bool
     */
    public function hasAuthMethod(TwoFactorAuthMethod|string $authMethod): bool
    {
        if ($authMethod instanceof TwoFactorAuthMethod) {
            return $this->authMethods->contains($authMethod);
        }
        return (bool) $this->getAuthMethod($authMethod);
    }

    /**
     * Count number of authentication methods
     * @return int
     */
    public function countAuthMethods(): int
    {
        return $this->authMethods ? $this->authMethods->count() : 0;
    }

    /**
     * Get users 2FA authentication methods
     * @return Collection|null
     */
    public function getAuthMethods(): ?Collection
    {
        return $this->authMethods;
    }

    /**
     * Add auth methods collection
     * @param ArrayCollection $authMethods
     * @return AuthUserInterface
     */
    public function addAuthMethods(ArrayCollection $authMethods): AuthUserInterface
    {
        foreach ($authMethods as $authMethod) {
            if ($authMethod instanceof TwoFactorAuthMethod) {
                $this->addAuthMethod($authMethod);
            }
        }

        return $this;
    }

    /**
     * Add auth method to collection
     * @param TwoFactorAuthMethod $authMethod
     * @return AuthUserInterface
     */
    public function addAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface
    {
        $this->authMethods->add($authMethod);
        return $this;
    }

    /**
     * Remove auth methods from collection
     * @param ArrayCollection $authMethods
     * @return AuthUserInterface
     */
    public function removeAuthMethods(ArrayCollection $authMethods): AuthUserInterface
    {
        foreach ($authMethods as $authMethod) {
            if ($authMethod instanceof TwoFactorAuthMethod) {
                $this->removeAuthMethod($authMethod);
            }
        }

        return $this;
    }

    /**
     * Remove auth method from collection
     * @param TwoFactorAuthMethod $authMethod
     * @return AuthUserInterface
     */
    public function removeAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface
    {
        $this->authMethods->removeElement($authMethod);
        return $this;
    }

    /**
     * 
     * @return Collection|null
     */
    public function getLogins(): ?Collection
    {
        return $this->logins;
    }

    /**
     * Add logins collection
     * @param ArrayCollection $logins
     * @return AuthUserInterface
     */
    public function addLogins(ArrayCollection $logins): AuthUserInterface
    {
        foreach ($logins as $login) {
            if ($login instanceof LoginLog) {
                $this->addLogin($login);
            }
        }
        return $this;
    }

    /**
     * Add login to collection
     * @param LoginLog $login
     * @return AuthUserInterface
     */
    public function addLogin(LoginLog $login): AuthUserInterface
    {
        $this->logins->add($login);
        return $this;
    }

    /**
     * Remove logins collection
     * @param ArrayCollection $logins
     * @return AuthUserInterface
     */
    public function removeLogins(ArrayCollection $logins): AuthUserInterface
    {
        foreach ($logins as $login) {
            if ($login instanceof LoginLog) {
                $this->removeLogin($login);
            }
        }
        return $this;
    }

    /**
     * Remove login from collection
     * @param LoginLog $login
     * @return AuthUserInterface
     */
    public function removeLogin(LoginLog $login): AuthUserInterface
    {
        $this->logins->removeElement($login);
        return $this;
    }

    /**
     * @ORM\PrePersist
     * @return void
     */
    public function prePersist()
    {
        $this->dateCreated = new DateTimeImmutable();
        $this->dateModified = new DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     * @return void
     */
    public function preUpdate()
    {
        $this->dateModified = new DateTimeImmutable();
    }

    public function __serialize(): array
    {
        return [
            'userId' => $this->userId,
            'emailAddress' => $this->emailAddress,
            'mobileNumber' => $this->mobileNumber,
            'userActive' => $this->userActive,
            'dateCreated' => $this->dateCreated,
            'dateModified' => $this->dateModified,
            'userRole' => $this->userRole,
            'authMethods' => $this->authMethods ? $this->authMethods->toArray() : null,
            'logins' => $this->logins ? $this->logins->toArray() : null,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->userId = $data['userId'];
        $this->emailAddress = $data['emailAddress'];
        $this->mobileNumber = $data['mobileNumber'];
        $this->userActive = $data['userActive'];
        $this->dateCreated = $data['dateCreated'];
        $this->dateModified = $data['dateModified'];
        $this->userRole = $data['userRole'];
        $this->authMethods = $data['authMethods'] ? new ArrayCollection($data['authMethods']) : null;
        $this->logins = $data['logins'] ? new ArrayCollection($data['logins']) : null;
    }

}
