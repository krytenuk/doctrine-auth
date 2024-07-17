<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\LoginForm;
use Laminas\Authentication\AuthenticationService;
use Laminas\Form\FormElementManager;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ParametersInterface;
use FwsDoctrineAuth\Form\Service\DoctrineAuthFormFactory;

use function class_exists;

/** * LoginModel
 */
class LoginModel extends AbstractModel
{
    const ERROR_IP_BLOCKED = 'ipBlocked';
    const ERROR_INVALID_CREDENTIALS = 'invalidCredentials';
    const ERROR_BLOCK_IP_ADDRESS = 'blockIpAddress';

    private ?BaseUser $identity = null;
    private ?string $callback   = null;
    private LoginForm $loginForm;
    public static array $loginErrorMessages = [];

    /**
     * @todo Document error messages
     */
    public static function setErrorMessages(): void
    {
        self::$loginErrorMessages = [
            self::ERROR_IP_BLOCKED => _('Sorry your IP address is blocked'),
            self::ERROR_INVALID_CREDENTIALS => _('Your login credentials are invalid'),
            self::ERROR_BLOCK_IP_ADDRESS => _('Too many login attempts, your IP address is blocked'),
        ];
    }

    /**
     *  Set model dependencies
     *
     * @param FormElementManager $formElementManager
     * @param AuthenticationService $authService
     * @param EntityManagerInterface $entityManager
     * @param AuthContainerStorage $authContainerStorage
     * @param SessionManager $sessionManager
     * @param Acl $acl
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        FormElementManager $formElementManager,
        protected AuthenticationService $authService,
        protected EntityManagerInterface $entityManager,
        protected AuthContainerStorage $authContainerStorage,
        protected SessionManager $sessionManager,
        protected Acl $acl,
        protected array $config
    ) {
        /* Fetch login form from config value */
        $this->loginForm = $formElementManager->get(DoctrineAuthFormFactory::LOGIN_FORM);

        /* Store login callback if set */
        if (isset($config['doctrineAuth']['loginCallback'])) {
            $this->callback = $config['doctrineAuth']['loginCallback'];
        }
    }

    public function getLoginForm(): LoginForm
    {
        return $this->loginForm;
    }

    /**
     * Validate the login/auth code form
     */
    public function processForm(ParametersInterface $postData): bool
    {
        $this->loginForm->setData($postData);
        return $this->loginForm->isValid();
    }

    /**
     * Attempt to login user
     *
     * @param array|null $data
     * @throws DoctrineAuthException
     */
    public function login(?array $data): bool
    {
        if ($data === null) {
            $data = $this->loginForm->getData();
        }

        /** @var ObjectRepository $adapter */
        $adapter = $this->authService->getAdapter();
        $adapter->setIdentity($data[$this->config['doctrine']['authentication']['orm_default']['identity_property']]);
        $adapter->setCredential($data[$this->config['doctrine']['authentication']['orm_default']['credential_property']]);
        $authResult = $this->authService->authenticate($adapter);
        /* Authentication failed */
        if (! $authResult->isValid()) {
            return false;
        }

        /* Get user identity */
        $this->identity = $authResult->getIdentity();

        /* User not active */
        if (! $this->identity->isUserActive()) {
            $this->authService->clearIdentity();
            return false;
        }

        /* Execute login callback if exists */
        if ($this->callback !== null && class_exists($this->callback)) {
            $callback = new $this->callback();
            $callback($this->identity, $this->loginForm, $data);
        }

        /* Use 2FA */
        if ($this->use2Fa()) {
            $this->authService->clearIdentity();
            $this->authContainerStorage->clear();
        }
        $this->authContainerStorage->setIdentity($this->identity);

        /* Update user on database and reload user entity */
        if (! $this->flushEntityManager($this->entityManager)) {
            return false;
        }

        $this->refresh($this->entityManager, $this->identity);
        return true;
    }

    /**
     * Check if using 2FA
     *
     * @throws DoctrineAuthException
     */
    public function use2Fa(): bool
    {
        if (! isset($this->config['doctrineAuth']['useTwoFactorAuthentication'])) {
            throw new DoctrineAuthException('useTwoFactorAuthentication setting not found in config');
        }

        if (! $this->config['doctrineAuth']['useTwoFactorAuthentication']) {
            return false;
        }

        return $this->getIdentity() instanceof BaseUser && $this->identity->hasAuthMethods();
    }

    /**
     * Set identity
     */
    public function setIdentity(AuthUserInterface $identity): LoginModel
    {
        $this->identity = $identity;
        $this->authService->getStorage()->write($identity);
        return $this;
    }

    /**
     * Get identity
     */
    public function getIdentity(): ?AuthUserInterface
    {
        if ($this->identity instanceof AuthUserInterface) {
            return $this->identity;
        }

        $this->identity = $this->authContainerStorage->getIdentity();

        return $this->identity;
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $this->authService->clearIdentity();
        $this->sessionManager->destroy();
    }

    /**
     * Set form identity element error message
     */
    public function setFormIdentityMessage(string $message): LoginModel
    {
        $this->loginForm->get($this->config['doctrine']['authentication']['orm_default']['identity_property'])->setMessages([$message]);
        return $this;
    }

    /**
     * Use forgot password link
     */
    public function useForgotPassword(): bool
    {
        return isset($this->config['doctrineAuth']['allowPasswordReset']) && $this->config['doctrineAuth']['allowPasswordReset'];
    }

    /**
     * Get Laminas config
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getAuthContainer(): Container
    {
        return $this->authContainerStorage;
    }
}
