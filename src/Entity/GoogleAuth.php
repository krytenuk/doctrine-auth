<?php /** @noinspection ALL */

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use DateTimeImmutable;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;

/**
 * GoogleAuth
 * @ORM\Entity
 * @ORM\Table(name="google_auth", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 * @deprecated  Using TwoFactorAuthMethod::settings instead to store auth secret
 */
class GoogleAuth implements EntityInterface
{
    /**
     *
     * @var string|null
     * @ORM\Column(name="secret", type="string", length=40, nullable=false, unique=true)
     * @ORM\Id
     */
    private string|null $secret = null;

    /**
     * @var TwoFactorAuthMethod|null
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\TwoFactorAuthMethod", inversedBy="googleAuth")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="auth_method_id", referencedColumnName="auth_method_id", onDelete="cascade")
     * })
     */
    private TwoFactorAuthMethod|null $authMethod = null;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTimeInterface $dateCreated;

    public function __construct()
    {
        trigger_deprecation(GoogleAuth::class, '1.0', 'This class has been replaced with the %s adaptor. Use the %s::setSettings() method to set the secret.', AuthenticationAppAdapter::class, TwoFactorAuthMethod::class);
        $this->dateCreated = new DateTimeImmutable();
    }
    
    /**
     * Get secret
     * @return string|null
     */
    public function getSecret(): string|null
    {
        return $this->secret;
    }
    
    /**
     * Get auth method
     * @return TwoFactorAuthMethod|null
     */
    public function getAuthMethod(): TwoFactorAuthMethod|null
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
     * @param TwoFactorAuthMethod $authMethod
     * @return GoogleAuth
     */
    public function setAuthMethod(TwoFactorAuthMethod $authMethod): GoogleAuth
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
