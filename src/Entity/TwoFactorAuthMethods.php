<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * TwoFactorAuthMethods
 * @ORM\Entity
 * @ORM\Table(name="auth_methods", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthMethods implements EntityInterface
{

    /**
     * @var int|null
     * @ORM\Column(name="auth_method_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $authMethodId = null;

    /**
     *
     * @var string
     * @ORM\Column(name="method", type="string", length=30, nullable=false)
     */
    private string $method;

    /**
     * @var BaseUsers
     *
     * @ORM\ManyToOne(targetEntity="FwsDoctrineAuth\Entity\BaseUsers", inversedBy="authMethods")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="cascade")
     * })
     */
    private BaseUsers $user;

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
     */
    private ?GoogleAuth $googleAuth = null;

    public function __construct()
    {
        $this->dateCreated = new DateTimeImmutable();
    }

    /**
     * Get 2FA authentication method id
     * @return int
     */
    public function getAuthMethodId(): int
    {
        return $this->authMethodId;
    }

    /**
     * Get 2FA authentication method
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get user
     * @return BaseUsers
     */
    public function getUser(): BaseUsers
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
     * Get google auth secret entity
     * @return GoogleAuth|null
     */
    public function getGoogleAuth(): ?GoogleAuth
    {
        return $this->googleAuth;
    }

    /**
     * Get 2FA authentication method
     * @param string $method
     * @return TwoFactorAuthMethods
     */
    public function setMethod(string $method): TwoFactorAuthMethods
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set user
     * @param BaseUsers $user
     * @return TwoFactorAuthMethods
     */
    public function setUser(BaseUsers $user): TwoFactorAuthMethods
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set date created
     * @param DateTimeInterface $dateCreated
     * @return TwoFactorAuthMethods
     */
    public function setDateCreated(DateTimeInterface $dateCreated): TwoFactorAuthMethods
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * Set google auth secret entity
     * @param GoogleAuth|null $googleAuth
     * @return TwoFactorAuthMethods
     */
    public function setGoogleAuth(?GoogleAuth $googleAuth): TwoFactorAuthMethods
    {
        if ($this->method === TwoFactorAuthModel::GOOGLEAUTHENTICATOR) {
            $this->googleAuth = $googleAuth;
        }
        return $this;
    }

}
