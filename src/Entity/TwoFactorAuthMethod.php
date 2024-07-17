<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;
use JetBrains\PhpStorm\Deprecated;

use function trigger_deprecation;

#[ORM\Entity(readOnly: false)]
#[ORM\Table(
    name: "auth_methods",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ]
)]
#[ORM\Index(columns: ["user_id"], name: "user_id")]
class TwoFactorAuthMethod implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "auth_method_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    private int|null $authMethodId = null;

    #[ORM\Column(
        name: "method",
        type: Types::STRING,
        length: 30,
        nullable: false
    )]
    private string|null $method;

    #[ORM\ManyToOne(
        targetEntity: BaseUser::class,
        inversedBy: "authMethods"
    )]
    #[ORM\JoinColumn(
        name: "user_id",
        referencedColumnName: "user_id",
        nullable: false,
        onDelete: "CASCADE"
    )]
    private AuthUserInterface|null $user;

    #[ORM\Column(
        name: "date_created",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    protected DateTimeInterface|null $dateCreated;

    #[Deprecated]
    #[ORM\OneToOne(
        mappedBy: "authMethod",
        targetEntity: GoogleAuth::class,
        cascade: ["PERSIST", "REMOVE"],
        orphanRemoval: true
    )]
    private ?GoogleAuth $googleAuth = null;

    #[ORM\Column(
        name: "settings",
        type: Types::JSON,
        nullable: true,
    )]
    private array $settings = [];

    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    /**
     * Get 2FA authentication method id
     */
    public function getAuthMethodId(): int|null
    {
        return $this->authMethodId;
    }

    /**
     * Get 2FA authentication method
     */
    public function getMethod(): string|null
    {
        return $this->method;
    }

    /**
     * Get user
     */
    public function getUser(): AuthUserInterface|null
    {
        return $this->user;
    }

    /**
     * Date created
     */
    public function getDateCreated(): DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get google auth secret entity
     *
     * @deprecated Using TwoFactorAuthMethod::settings instead to store auth secret
     */
    public function getGoogleAuth(): ?GoogleAuth
    {
        return $this->googleAuth;
    }

    /**
     * Get 2FA authentication method
     */
    public function setMethod(string $method): TwoFactorAuthMethod
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set user
     */
    public function setUser(BaseUser $user): TwoFactorAuthMethod
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set date created
     */
    public function setDateCreated(DateTimeInterface $dateCreated): TwoFactorAuthMethod
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): TwoFactorAuthMethod
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Set google auth secret entity
     *
     * @deprecated Using TwoFactorAuthMethod::settings instead to store auth secret
     */
    public function setGoogleAuth(?GoogleAuth $googleAuth): TwoFactorAuthMethod
    {
        trigger_deprecation(self::class, '1.0', 'Use TwoFactorAuthMethod::settings instead to store auth app secret');

        if ($this->method === AuthenticationAppAdapter::getName()) {
            $this->googleAuth = $googleAuth;
        }
        return $this;
    }
}
