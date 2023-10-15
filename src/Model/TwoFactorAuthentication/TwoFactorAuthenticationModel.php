<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use FwsDoctrineAuth\Form\TwoFactorAuthenticationCodeForm;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Authentication\AuthenticationService;
use Laminas\Stdlib\ParametersInterface;

/**
 * TwoFactorAuth
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthenticationModel
{

    use AdaptorTrait;

    private DateTimeInterface $currentDateTime;

    /**
     *
     * @param AdaptorPluginManager $adaptorPluginManager
     * @param SelectTwoFactorAuthMethodForm $selectAuthMethodForm
     * @param TwoFactorAuthenticationCodeForm $authCodeForm
     * @param AuthContainerStorage $authContainerStorage
     * @param AuthenticationService $authService
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected AdaptorPluginManager            $adaptorPluginManager,
        protected SelectTwoFactorAuthMethodForm   $selectAuthMethodForm,
        protected TwoFactorAuthenticationCodeForm $authCodeForm,
        protected AuthContainerStorage            $authContainerStorage,
        protected AuthenticationService           $authService,
        protected array                           $config,
    )
    {
        $this->initAdaptors();
        $this->selectAuthMethodForm->setAllowedMethods($this->allowedMethods);

        $this->currentDateTime = $datetime = new DateTimeImmutable('now');
        if (array_key_exists('timezone', $config)) {
            try {
                $this->currentDateTime->setTimezone(new DateTimeZone($config['timezone']));
            } catch (Exception) {
            }
        }
    }

    /**
     * @return AuthContainerStorage
     */
    public function getAuthContainerStorage(): AuthContainerStorage
    {
        return $this->authContainerStorage;
    }

    /**
     * @return AuthenticationService
     */
    public function getAuthService(): AuthenticationService
    {
        return $this->authService;
    }

    /**
     * Get user attempting authentication
     * @return AuthUserInterface|null
     */
    public function getIdentity(): ?AuthUserInterface
    {
        return $this->authContainerStorage->getIdentity();
    }

    /**
     * Set identity
     * @param AuthUserInterface $identity
     * @return TwoFactorAuthenticationModel
     */
    public function storeIdentity(AuthUserInterface $identity): TwoFactorAuthenticationModel
    {
        $this->authService->getStorage()->write($identity);
        return $this;
    }

    /**
     * Determine if the give authentication method is allowed
     * @param string|null $selectedAuthMethod
     * @return bool
     */
    public function isValidAuthMethod(?string $selectedAuthMethod): bool
    {
        if (!$selectedAuthMethod) {
            return false;
        }

        if (array_key_exists($selectedAuthMethod, $this->allowedMethods)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @return SelectTwoFactorAuthMethodForm
     */
    public function getSelectAuthMethodForm(): SelectTwoFactorAuthMethodForm
    {
        return $this->selectAuthMethodForm;
    }

    /**
     *
     * @return TwoFactorAuthenticationCodeForm
     */
    public function getAuthCodeForm(): TwoFactorAuthenticationCodeForm
    {
        return $this->authCodeForm;
    }

    /**
     * Process select authentication form
     * @param ParametersInterface $postData
     * @return bool
     */
    public function processSelectForm(ParametersInterface $postData): bool
    {
        $this->selectAuthMethodForm->setData($postData);
        if ($this->selectAuthMethodForm->isValid()) {
            return $this->setSelectedAuthMethod($this->selectAuthMethodForm->getData()['method']);
        }

        return false;
    }

    /**
     * Process authenticate code form
     * @param ParametersInterface $postData
     * @return bool
     */
    public function processAuthForm(ParametersInterface $postData): bool
    {
        $this->authCodeForm->setData($postData);
        return $this->authCodeForm->isValid();
    }

    /**
     * Generate the 2FA code to send to the user
     * @return TwoFactorAuthenticationModel
     * @throws DoctrineAuthException
     */
    public function generateCode(): TwoFactorAuthenticationModel
    {
        $this->getAdaptor()->generateCode();
        return $this;
    }

    /**
     * Send 2FA code to user
     * @param bool $force force sending of code
     * @return bool
     * @throws DoctrineAuthException
     */
    public function sendCode(bool $force = false): bool
    {
        if (!$this->authContainerStorage->getIdentity() instanceof AuthUserInterface) {
            return false;
        }

        if (!$this->getAdaptor()->sendCode()) {
            return false;
        }

        $this->authContainerStorage->setCodeSent($this->currentDateTime);
        return true;
    }

    /**
     * Has code been sent?
     * @return bool
     * @throws DoctrineAuthException
     */
    public function codeSent(): bool
    {
        return $this->getAdaptor()->codeSent();
    }

    /**
     * Has the code expired
     * @return bool
     * @throws DoctrineAuthException
     */
    public function codeExpired(): bool
    {
        return $this->getAdaptor()->codeExpired();
    }

    /**
     * Get the template to render on the authentication 2FA code page during the login process
     * @return string
     * @throws DoctrineAuthException
     */
    public function getAuthenticateTemplate(): string
    {
        return $this->getAdaptor()->getAuthenticateTemplate();
    }

    /**
     * Authenticate using 2FA code
     * @return bool
     * @throws DoctrineAuthException
     */
    public function authenticate(): bool
    {
        return $this->getAdaptor()->authenticate($this->authCodeForm->getData()['code']);
    }

    /**
     * Count number of user 2FA methods
     * @return int
     */
    public function countUserAuthMethods(): int
    {
        $identity = $this->authContainerStorage->getIdentity();
        if ($identity instanceof AuthUserInterface) {
            return $identity->countAuthMethods();
        }
        return 0;
    }

    /**
     * Return 2FA method if only one is set
     * @return TwoFactorAuthMethod|null
     */
    public function getSingleAuthMethod(): ?TwoFactorAuthMethod
    {
        if ($this->countUserAuthMethods() !== 1) {
            return null;
        }
        return $this->authContainerStorage->identity->getAuthMethods()->current();
    }

    /**
     * Set selected authentication method
     * @param string $method
     * @return bool
     */
    public function setSelectedAuthMethod(string $method): bool
    {
        if (!$this->isValidAuthMethod($method)) {
            return false;
        }

        $this->authContainerStorage->setAuthSelectedMethod($method);
        return true;
    }

    public function getSelectedAuthMethod(): ?string
    {
        return $this->authContainerStorage->getSelectedAuthMethod();
    }

}
