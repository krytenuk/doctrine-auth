<?php

/**
 * BaseUser Entity
 *
 * @todo Document schema changes
 * ALTER TABLE users CHANGE email_address email_address VARCHAR(100) NOT NULL, CHANGE password password VARCHAR(100) DEFAULT NULL, CHANGE mobile_number mobile_number VARCHAR(30) DEFAULT NULL;
 * ALTER TABLE auth_methods CHANGE user_id user_id INT UNSIGNED NOT NULL;
 * ALTER TABLE login_log CHANGE user_id user_id INT UNSIGNED NOT NULL;
 * ALTER TABLE google_auth CHANGE auth_method_id auth_method_id INT UNSIGNED NOT NULL;
 * ALTER TABLE password_reminder CHANGE user_id user_id INT UNSIGNED NOT NULL;
 * ALTER TABLE login_attempts CHANGE email_address email_address VARCHAR(256) NOT NULL;
 */

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: false), ORM\HasLifecycleCallbacks]
#[ORM\Table(
    name: "users",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine"  => "InnoDB",
    ]
)]
#[ORM\Index(
    columns: ["user_role_id"],
    name: "user_role_id"
)]
#[ORM\UniqueConstraint(
    name: "email_address",
    columns: ["email_address"]
)]
#[ORM\InheritanceType("SINGLE_TABLE"),
    ORM\DiscriminatorColumn(
        name: "type",
        type: "string"
    )
]
#[ORM\MappedSuperclass]
class BaseUser implements AuthUserInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "user_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    protected int|null $userId = null;

    /**
     * identity_property in config
     *
     * @todo Document text to string datatype change, write convert script?
     */
    #[ORM\Column(
        name: "email_address",
        type: Types::STRING,
        length: 100,
        nullable: false
    )]
    protected string|null $emailAddress = null;

    /**
     * credential_property in config
     */
    #[ORM\Column(
        name: "password",
        type: Types::STRING,
        length: 100,
        nullable: true
    )]
    protected string|null $password  = null;

    /** @todo Document text to string datatype change, write convert script? */
    #[ORM\Column(
        name: "mobile_number",
        type: Types::STRING,
        length: 30,
        nullable: true
    )]
    protected string|null $mobileNumber = null;

    #[ORM\Column(
        name: "user_active",
        type: Types::BOOLEAN,
        nullable: false,
        options: ["default" => 0]
    )]
    protected bool $userActive = false;

    #[ORM\Column(
        name: "date_created",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    protected DateTimeInterface|null $dateCreated = null;

    #[ORM\Column(
        name: "date_modified",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    protected DateTimeInterface|null $dateModified = null;

    #[ORM\ManyToOne(
        targetEntity: UserRole::class,
        cascade: ["PERSIST"],
        fetch: "EAGER",
        inversedBy: "users"
    )]
    #[ORM\JoinColumn(
        name: "user_role_id",
        referencedColumnName: "user_role_id",
        onDelete: "RESTRICT"
    )]
    protected UserRole|null $userRole = null;

    #[ORM\OneToOne(
        mappedBy: "user",
        targetEntity: PasswordReminder::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true
    )]
    protected PasswordReminder|null $passwordReminder = null;

    #[ORM\OneToMany(
        mappedBy: "user",
        targetEntity: TwoFactorAuthMethod::class,
        cascade: ["PERSIST", "REMOVE"],
        fetch: "EAGER",
        orphanRemoval: true
    )]
    protected Collection|null $authMethods = null;

    #[ORM\OneToMany(
        mappedBy: "user",
        targetEntity: LoginLog::class,
        cascade: ["persist", "remove"],
        fetch: "LAZY",
        orphanRemoval: true
    )]
    protected Collection|null $logins = null;

    public function __construct()
    {
        $this->authMethods  = new ArrayCollection();
        $this->logins       = new ArrayCollection();
        $this->dateCreated  = new DateTime();
        $this->dateModified = new DateTime();
    }

    /**
     * Get userId
     */
    public function getUserId(): int|null
    {
        return $this->userId;
    }

    /**
     * Set emailAddress
     */
    public function setEmailAddress(string $emailAddress): AuthUserInterface
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get emailAddress
     */
    public function getEmailAddress(): string|null
    {
        return $this->emailAddress;
    }

    /**
     * Set password
     */
    public function setPassword(string|null $password): AuthUserInterface
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     */
    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function setMobileNumber(string|null $mobileNumber): AuthUserInterface
    {
        $this->mobileNumber = $mobileNumber;
        return $this;
    }

    public function getMobileNumber(): string|null
    {
        return $this->mobileNumber;
    }

    /**
     * Set user active
     */
    public function setUserActive(bool $userActive): AuthUserInterface
    {
        $this->userActive = $userActive;
        return $this;
    }

    public function isUserActive(): bool
    {
        return $this->userActive;
    }

    public function setDateCreated(DateTimeInterface $dateCreated): AuthUserInterface
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateCreated(): ?DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateModified(DateTimeInterface $dateModified): AuthUserInterface
    {
        $this->dateModified = $dateModified;
        return $this;
    }

    public function getDateModified(): ?DateTimeInterface
    {
        return $this->dateModified;
    }

    /**
     * Set userRole
     */
    public function setUserRole(UserRole $userRole): AuthUserInterface
    {
        $this->userRole = $userRole;
        return $this;
    }

    /**
     * Get userRole
     */
    public function getUserRole(): ?UserRole
    {
        return $this->userRole;
    }

    public function hasPasswordReminder(): bool
    {
        return (bool) $this->getPasswordReminder();
    }

    public function getPasswordReminder(): ?PasswordReminder
    {
        return $this->passwordReminder;
    }

    public function setPasswordReminder(?PasswordReminder $passwordReminder): AuthUserInterface
    {
        $this->passwordReminder = $passwordReminder;
        return $this;
    }

    public function getAuthMethod(string $authMethod): ?TwoFactorAuthMethod
    {
        if ($this->authMethods === null || $this->authMethods->count() === 0) {
            return null;
        }

        $method = $this->authMethods->filter(function (TwoFactorAuthMethod $entity) use ($authMethod): bool {
            return $entity->getMethod() === $authMethod;
        })->first();

        return $method ?: null;
    }

    /**
     * User has 2FA methods set
     */
    public function hasAuthMethods(): bool
    {
        return (bool) $this->countAuthMethods();
    }

    /**
     * Check if authentication method set
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
     */
    public function countAuthMethods(): int
    {
        return $this->authMethods ? $this->authMethods->count() : 0;
    }

    /**
     * Get users 2FA authentication methods
     */
    public function getAuthMethods(): ?Collection
    {
        return $this->authMethods;
    }

    /**
     * Add auth methods collection
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
     */
    public function addAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface
    {
        $this->authMethods->add($authMethod);
        return $this;
    }

    /**
     * Remove auth methods from collection
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
     */
    public function removeAuthMethod(TwoFactorAuthMethod $authMethod): AuthUserInterface
    {
        $this->authMethods->removeElement($authMethod);
        return $this;
    }

    public function getLogins(): ?Collection
    {
        return $this->logins;
    }

    /**
     * Add logins collection
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
     */
    public function addLogin(LoginLog $login): AuthUserInterface
    {
        $this->logins->add($login);
        return $this;
    }

    /**
     * Remove logins collection
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
     */
    public function removeLogin(LoginLog $login): AuthUserInterface
    {
        $this->logins->removeElement($login);
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->dateCreated  = new DateTime();
        $this->dateModified = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->dateModified = new DateTime();
    }

    public function __serialize(): array
    {
        return [
            'userId'       => $this->userId,
            'emailAddress' => $this->emailAddress,
            'mobileNumber' => $this->mobileNumber,
            'userActive'   => $this->userActive,
            'dateCreated'  => $this->dateCreated,
            'dateModified' => $this->dateModified,
            'userRole'     => $this->userRole,
            'authMethods'  => $this->authMethods?->toArray(),
            'logins'       => $this->logins?->toArray(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->userId       = $data['userId'];
        $this->emailAddress = $data['emailAddress'];
        $this->mobileNumber = $data['mobileNumber'];
        $this->userActive   = $data['userActive'];
        $this->dateCreated  = $data['dateCreated'];
        $this->dateModified = $data['dateModified'];
        $this->userRole     = $data['userRole'];
        $this->authMethods  = $data['authMethods'] ? new ArrayCollection($data['authMethods']) : null;
        $this->logins       = $data['logins'] ? new ArrayCollection($data['logins']) : null;
    }
}
