<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethods;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * GoogleAuth
 * @ORM\Entity
 * @ORM\Table(name="google_auth", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 */
class GoogleAuth implements EntityInterface
{
    /**
     * Base 32 characters
     * @var string
     */
    protected string $base32Chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
    
    /**
     *
     * @var string
     * @ORM\Column(name="secret", type="string", length=40, nullable=false, unique=true)
     * @ORM\Id
     */
    private string $secret;
    
    /**
     * @var TwoFactorAuthMethods
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\TwoFactorAuthMethods", inversedBy="googleAuth")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="auth_method_id", referencedColumnName="auth_method_id", onDelete="cascade")
     * })
     */
    private TwoFactorAuthMethods $authMethod;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTimeInterface $dateCreated;
    
    public function __construct()
    {
        $this->dateCreated = new DateTimeImmutable();
    }
    
    /**
     * Get secret
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
    
    /**
     * Get auth method
     * @return TwoFactorAuthMethods
     */
    public function getAuthMethod(): TwoFactorAuthMethods
    {
        return $this->authMethod;
    }

    /**
     * Get date created
     * @return DateTimeInterface
     */
    public function getDateCreated(): DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * Set secret
     * @param string $secret
     * @return GoogleAuth
     */
    public function setSecret(string $secret): GoogleAuth
    {
        $this->secret = $secret;
        return $this;
    }
    
    /**
     * Set authentication method
     * @param TwoFactorAuthMethods $authMethod
     * @return GoogleAuth
     */
    public function setAuthMethod(TwoFactorAuthMethods $authMethod): GoogleAuth
    {
        $this->authMethod = $authMethod;
        return $this;
    }

    /**
     * Set date created
     * @param DateTimeInterface $dateCreated
     * @return GoogleAuth
     */
    public function setDateCreated(DateTimeInterface $dateCreated): GoogleAuth
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

}
