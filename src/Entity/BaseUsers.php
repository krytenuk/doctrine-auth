<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\UserRoles;
use FwsDoctrineAuth\Entity\PasswordReminder;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethods;
use FwsDoctrineAuth\Entity\LoginLog;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * BaseUsers
 * @ORM\Entity
 * @ORM\Table(name="users", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"},
 *    indexes={
 *        @ORM\Index(name="user_role_id", columns={"user_role_id"}),
 *    }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @author Garry Childs <info@freedomwebservices.net>
 */
class BaseUsers implements EntityInterface
{

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $userId = null;

    /**
     * identity_property in config
     * @var string
     *
     * @ORM\Column(name="email_address", type="text", nullable=false)
     */
    private string $emailAddress = '';

    /**
     * credential_property in config
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=256, nullable=true)
     */
    private ?string $password = null;

    /**
     * 
     * @var string|null
     *
     * @ORM\Column(name="mobile_number", type="text", nullable=true)
     */
    private ?string $mobileNumber = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="user_active", type="boolean", nullable=false, options={"default":0})
     */
    private bool $userActive = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="encrypted", type="boolean", nullable=false, options={"default":0})
     */
    private bool $encrypted = false;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_created", type="datetime")
     */
    private DateTimeInterface $dateCreated;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_modified", type="datetime")
     */
    private DateTimeInterface $dateModified;

    /**
     * @var UserRoles
     *
     * @ORM\ManyToOne(targetEntity="FwsDoctrineAuth\Entity\UserRoles", cascade={"persist", "merge"}, fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_role_id", referencedColumnName="user_role_id")
     * })
     */
    private UserRoles $userRole;

    /**
     * @var PasswordReminder|null
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\PasswordReminder", mappedBy="user", orphanRemoval=true, cascade={"persist", "merge"})
     */
    private ?PasswordReminder $passwordReminder = null;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="FwsDoctrineAuth\Entity\TwoFactorAuthMethods", mappedBy="user", orphanRemoval=true, cascade={"persist", "merge"}, fetch="EAGER")
     */
    private Collection $authMethods;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="FwsDoctrineAuth\Entity\LoginLog", mappedBy="user", orphanRemoval=true, cascade={"persist", "merge"}, fetch="EAGER")
     */
    private Collection $logins;

    public function __construct()
    {
        $this->dateCreated = new DateTimeImmutable();
        $this->dateModified = new DateTimeImmutable();
        $this->userRole = new UserRoles();
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
     * @param string|null $emailAddress
     * @return BaseUsers
     */
    public function setEmailAddress(?string $emailAddress): BaseUsers
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
     * @return BaseUsers
     */
    public function setPassword(?string $password): BaseUsers
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
     * @return string|null
     */
    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    /**
     * 
     * @param string|null $mobileNumber
     * @return BaseUsers
     */
    public function setMobileNumber(?string $mobileNumber): BaseUsers
    {
        $this->mobileNumber = $mobileNumber;
        return $this;
    }

    /**
     * Set user active
     * @param bool|int $userActive
     * @return BaseUsers
     */
    public function setUserActive($userActive): BaseUsers
    {
        $this->userActive = (bool) $userActive;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function isUserActive(): bool
    {
        return (bool) $this->userActive;
    }

    /**
     * 
     * @return bool
     */
    public function getUserActive(): bool
    {
        return (bool) $this->userActive;
    }

    /**
     * Set is user encrypted
     * @param bool $encrypted
     * @return BaseUsers
     */
    public function setEncrypted(bool $encrypted): BaseUsers
    {
        $this->encrypted = $encrypted;
        return $this;
    }

    public function isEncrypted(): bool
    {
        return $this->getEncrypted();
    }

    /**
     * Get is user data encrypted
     * @return bool
     */
    public function getEncrypted(): bool
    {
        return (bool) $this->encrypted;
    }

    /**
     * 
     * @param DateTimeInterface $dateCreated
     * @return BaseUsers
     */
    public function setDateCreated(DateTimeInterface $dateCreated): BaseUsers
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
     * @return BaseUsers
     */
    public function setDateModified(DateTimeInterface $dateModified): BaseUsers
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
     * @param UserRoles $userRole
     * @return BaseUsers
     */
    public function setUserRole(?UserRoles $userRole): BaseUsers
    {
        $this->userRole = $userRole;
        return $this;
    }

    /**
     * Get userRole
     *
     * @return UserRoles
     */
    public function getUserRole(): ?UserRoles
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
     * @return BaseUsers
     */
    public function setPasswordReminder(?PasswordReminder $passwordReminder): BaseUsers
    {
        $this->passwordReminder = $passwordReminder;
        return $this;
    }

    /**
     * 
     * @param string $authMethod
     * @return TwoFactorAuthMethods|null
     */
    public function getAuthMethod($authMethod): ?TwoFactorAuthMethods
    {
        if (array_key_exists($authMethod, TwoFactorAuthModel::VALIDAUTHENTICATIONMETHODS) === false) {
            return null;
        }

        if ($this->authMethods->count() === 0) {
            return null;
        }

        foreach ($this->authMethods as $method) {
            if ($method->getMethod() === $authMethod) {
                return $method;
            }
        }
        return null;
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
     * Count number of authentication methods
     * @return int
     */
    public function countAuthMethods(): int
    {
        return $this->authMethods->count();
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
     * @return BaseUsers
     */
    public function addAuthMethods(ArrayCollection $authMethods): BaseUsers
    {
        foreach ($authMethods as $authMethod) {
            if ($authMethod instanceof TwoFactorAuthMethods) {
                $this->addAuthMethod($authMethod);
            }
        }

        return $this;
    }

    /**
     * Add auth method to collection
     * @param TwoFactorAuthMethods $authMethod
     * @return BaseUsers
     */
    public function addAuthMethod(TwoFactorAuthMethods $authMethod): BaseUsers
    {
        $this->authMethods->add($authMethod);
        return $this;
    }

    /**
     * Remove auth methods from collection
     * @param ArrayCollection $authMethods
     * @return BaseUsers
     */
    public function removeAuthMethods(ArrayCollection $authMethods): BaseUsers
    {
        foreach ($authMethods as $authMethod) {
            if ($authMethod instanceof TwoFactorAuthMethods) {
                $this->removeAuthMethod($authMethod);
            }
        }

        return $this;
    }

    /**
     * Remove auth method from collection
     * @param TwoFactorAuthMethods $authMethod
     * @return BaseUsers
     */
    public function removeAuthMethod(TwoFactorAuthMethods $authMethod): BaseUsers
    {
        $this->authMethods->removeElement($authMethod);
        return $this;
    }

    /**
     * 
     * @return Collection
     */
    public function getLogins(): Collection
    {
        return $this->logins;
    }

    /**
     * Add logins collection
     * @param ArrayCollection $logins
     * @return BaseUsers
     */
    public function addLogins(ArrayCollection $logins): BaseUsers
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
     * @return BaseUsers
     */
    public function addLogin(LoginLog $login): BaseUsers
    {
        $this->logins->add($login);
        return $this;
    }

    /**
     * Remove logins collection
     * @param ArrayCollection $logins
     * @return BaseUsers
     */
    public function removeLogins(ArrayCollection $logins): BaseUsers
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
     * @return BaseUsers
     */
    public function removeLogin(LoginLog $login): BaseUsers
    {
        $this->logins->removeElement($login);
        return $this;
    }

    public function __serialize()
    {
        return [
            'userId' => $this->userId,
            'emailAddress' => $this->emailAddress,
            'mobileNumber' => $this->mobileNumber,
            'userActive' => $this->userActive,
            'dateCreated' => $this->dateCreated,
            'dateModified' => $this->dateModified,
            'userRole' => $this->userRole,
            'authMethods' => $this->authMethods->toArray(),
            'logins' => $this->logins->toArray(),
        ];
    }

    public function __unserialize(array $data)
    {
        $this->userId = $data['userId'];
        $this->emailAddress = $data['emailAddress'];
        $this->mobileNumber = $data['mobileNumber'];
        $this->userActive = $data['userActive'];
        $this->dateCreated = $data['dateCreated'];
        $this->dateModified = $data['dateModified'];
        $this->userRole = $data['userRole'];
        $this->authMethods = new ArrayCollection($data['authMethods']);
        $this->logins = new ArrayCollection($data['logins']);
    }

}
