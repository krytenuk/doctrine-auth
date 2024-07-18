<?php

declare(strict_types=1);

/**
 * GoogleAuth Entity
 *
 * @deprecated  Using TwoFactorAuthMethod::settings instead to store auth secret
 *
 * @noinspection ALL
 */

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;

use JetBrains\PhpStorm\Deprecated;
use function trigger_deprecation;

#[Deprecated]
#[ORM\Entity(readOnly: false)]
#[ORM\Table(
    name: "google_auth",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ]
)]
#[ORM\UniqueConstraint(
    name: "auth_method_id",
    columns: ["auth_method_id"]
)]
class GoogleAuth implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "secret",
            type: Types::STRING,
            length: 40,
            unique: true,
            nullable: false
        )]
    private string|null $secret = null;

    #[ORM\OneToOne(
        inversedBy: "googleAuth",
        targetEntity: TwoFactorAuthMethod::class
    )]
    #[ORM\JoinColumn(
        name: "auth_method_id",
        referencedColumnName: "auth_method_id",
        unique: true,
        nullable: false,
        onDelete: "CASCADE"
    )]
    private TwoFactorAuthMethod|null $authMethod;

    #[ORM\Column(
        name: "date_created",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    private DateTimeInterface $dateCreated;

    public function __construct()
    {
        trigger_deprecation(self::class, '1.0', 'This class has been replaced with the %s adaptor. Use the %s::setSettings() method to set the secret.', AuthenticationAppAdapter::class, TwoFactorAuthMethod::class);
        $this->dateCreated = new DateTime();
    }

    /**
     * Get secret
     */
    public function getSecret(): string|null
    {
        return $this->secret;
    }

    /**
     * Get auth method
     */
    public function getAuthMethod(): TwoFactorAuthMethod|null
    {
        return $this->authMethod;
    }

    /**
     * Get date created
     */
    public function getDateCreated(): DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * Set secret
     */
    public function setSecret(string $secret): GoogleAuth
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Set authentication method
     */
    public function setAuthMethod(TwoFactorAuthMethod $authMethod): GoogleAuth
    {
        $this->authMethod = $authMethod;
        return $this;
    }

    /**
     * Set date created
     */
    public function setDateCreated(DateTimeInterface $dateCreated): GoogleAuth
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }
}
