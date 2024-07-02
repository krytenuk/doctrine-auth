<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use DateTimeInterface;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;

/**
 * TwoFactorAuthMethod
 * @ORM\Entity
 * @ORM\Table(name="auth_methods", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthMethod implements EntityInterface
{

    /**
     * @var int|null
     * @ORM\Column(name="auth_method_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $authMethodId = null;

    /**
     *
     * @var string|null
     * @ORM\Column(name="method", type="string", length=30, nullable=false)
     */
    private ?string $method = null;

    /**
     * @var BaseUser|null
     *
     * @ORM\ManyToOne(targetEntity="FwsDoctrineAuth\Entity\BaseUser", inversedBy="authMethods")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="cascade")
     * })
     */
    private ?BaseUser $user = null;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTimeInterface $dateCreated;

    /**
     * @var GoogleAuth|null
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\GoogleAuth", mappedBy="authMethod", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     * @deprecated
     */
    private ?GoogleAuth $googleAuth = null;

    /**
     * @var array
     *
     * @ORM\Column(name="settings", type="json", nullable=true)
     */
    private array $settings = [];

    public function __construct()
    {
        $this->dateCreated = new DateTimeImmutable();
    }

    /**
     * Get 2FA authentication method id
     * @return int
     */
    public function getAuthMethodId(): ?int
    {
        return $this->authMethodId;
    }

    /**
     * Get 2FA authentication method
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get user
     * @return BaseUser|null
     */
    public function getUser(): ?BaseUser
    {
        return $this->user;
    }

    /**
     * Date created
     * @return DateTimeInterface
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
     * @return GoogleAuth|null
     * @deprecated Using TwoFactorAuthMethod::settings instead to store auth secret
     */
    public function getGoogleAuth(): ?GoogleAuth
    {
        return $this->googleAuth;
    }

    /**
     * Get 2FA authentication method
     * @param string $method
     * @return TwoFactorAuthMethod
     */
    public function setMethod(string $method): TwoFactorAuthMethod
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set user
     * @param BaseUser $user
     * @return TwoFactorAuthMethod
     */
    public function setUser(BaseUser $user): TwoFactorAuthMethod
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set date created
     * @param DateTimeInterface $dateCreated
     * @return TwoFactorAuthMethod
     */
    public function setDateCreated(DateTimeInterface $dateCreated): TwoFactorAuthMethod
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @param array $settings
     * @return TwoFactorAuthMethod
     */
    public function setSettings(array $settings): TwoFactorAuthMethod
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Set google auth secret entity
     * @param GoogleAuth|null $googleAuth
     * @return TwoFactorAuthMethod
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
