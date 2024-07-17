<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use FwsDoctrineAuth\Form\Service\DoctrineAuthFormFactory;
use FwsDoctrineAuth\Form\TwoFactorAuthenticationCodeForm;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Authentication\AuthenticationService;
use Laminas\Form\FormElementManager;
use Laminas\Stdlib\ParametersInterface;

use function array_key_exists;

/**
 * TwoFactorAuth
 */
class TwoFactorAuthenticationModel
{
    use AdaptorTrait;

    private DateTimeInterface $currentDateTime;
    private SelectTwoFactorAuthMethodForm $selectAuthMethodForm;
    private TwoFactorAuthenticationCodeForm $authCodeForm;

    /**
     * @param AdaptorPluginManager $adaptorPluginManager
     * @param FormElementManager $formElementManager
     * @param AuthContainerStorage $authContainerStorage
     * @param AuthenticationService $authService
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected AdaptorPluginManager $adaptorPluginManager,
        FormElementManager $formElementManager,
        protected AuthContainerStorage $authContainerStorage,
        protected AuthenticationService $authService,
        protected array $config,
    ) {
        $this->selectAuthMethodForm = $formElementManager->get(DoctrineAuthFormFactory::SELECT_2FA_METHODS_FORM);
        $this->authCodeForm = $formElementManager->get(DoctrineAuthFormFactory::TWO_FACTOR_AUTHENTICATION_CODE_FORM);

        $this->initAdaptors();
        $this->selectAuthMethodForm->setAllowedMethods($this->allowedMethods);

        $this->currentDateTime = $datetime = new DateTime('now');
        if (array_key_exists('timezone', $config)) {
            try {
                $this->currentDateTime->setTimezone(new DateTimeZone($config['timezone']));
            } catch (Exception) {
            }
        }
    }

    public function getAuthContainerStorage(): AuthContainerStorage
    {
        return $this->authContainerStorage;
    }

    public function getAuthService(): AuthenticationService
    {
        return $this->authService;
    }

    /**
     * Get user attempting authentication
     */
    public function getIdentity(): ?AuthUserInterface
    {
        return $this->authContainerStorage->getIdentity();
    }

    /**
     * Set identity
     */
    public function storeIdentity(AuthUserInterface $identity): TwoFactorAuthenticationModel
    {
        $this->authService->getStorage()->write($identity);
        return $this;
    }

    /**
     * Determine if the give authentication method is allowed
     */
    public function isValidAuthMethod(?string $selectedAuthMethod): bool
    {
        if (! $selectedAuthMethod) {
            return false;
        }

        if (array_key_exists($selectedAuthMethod, $this->allowedMethods)) {
            return true;
        }
        return false;
    }

    public function getSelectAuthMethodForm(): SelectTwoFactorAuthMethodForm
    {
        return $this->selectAuthMethodForm;
    }

    public function getAuthCodeForm(): TwoFactorAuthenticationCodeForm
    {
        return $this->authCodeForm;
    }

    /**
     * Process select authentication form
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
     */
    public function processAuthForm(ParametersInterface $postData): bool
    {
        $this->authCodeForm->setData($postData);
        return $this->authCodeForm->isValid();
    }

    /**
     * Generate the 2FA code to send to the user
     *
     * @throws DoctrineAuthException
     */
    public function generateCode(): TwoFactorAuthenticationModel
    {
        $this->getAdaptor()->generateCode();
        return $this;
    }

    /**
     * Send 2FA code to user
     *
     * @param bool $force force sending of code
     * @throws DoctrineAuthException
     */
    public function sendCode(bool $force = false): bool
    {
        if (! $this->authContainerStorage->getIdentity() instanceof AuthUserInterface) {
            return false;
        }

        if (! $this->getAdaptor()->sendCode()) {
            return false;
        }

        $this->authContainerStorage->setCodeSent($this->currentDateTime);
        return true;
    }

    /**
     * Has code been sent?
     *
     * @throws DoctrineAuthException
     */
    public function codeSent(): bool
    {
        return $this->getAdaptor()->codeSent();
    }

    /**
     * Has the code expired
     *
     * @throws DoctrineAuthException
     */
    public function codeExpired(): bool
    {
        return $this->getAdaptor()->codeExpired();
    }

    /**
     * Get the template to render on the authentication 2FA code page during the login process
     *
     * @throws DoctrineAuthException
     */
    public function getAuthenticateTemplate(): string
    {
        return $this->getAdaptor()->getAuthenticateTemplate();
    }

    /**
     * Authenticate using 2FA code
     *
     * @throws DoctrineAuthException
     */
    public function authenticate(): bool
    {
        return $this->getAdaptor()->authenticate($this->authCodeForm->getData()['code']);
    }

    /**
     * Count number of user 2FA methods
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
     */
    public function setSelectedAuthMethod(string $method): bool
    {
        if (! $this->isValidAuthMethod($method)) {
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
