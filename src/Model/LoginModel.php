<?php

namespace FwsDoctrineAuth\Model;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Entity\Repository\FailedLoginAttemptsLogRepository;
use FwsDoctrineAuth\Entity\Repository\IpBlockedRepository;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\LoginForm;
use Laminas\Authentication\AuthenticationService;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ParametersInterface;

/** * LoginModel
 *
 * @author Garry Childs (Freedom Web Services)
 */
class LoginModel extends AbstractModel
{

    private ?BaseUser $identity = null;
    private ?string $callback = null;

    /**
     *  Set model dependencies
     *
     * @param LoginForm $loginForm
     * @param AuthenticationService $authService
     * @param EntityManagerInterface $entityManager
     * @param AuthContainerStorage $authContainerStorage
     * @param SessionManager $sessionManager
     * @param Acl $acl
     * @param array $config
     */
    public function __construct(
            protected LoginForm $loginForm,
            protected AuthenticationService $authService,
            protected EntityManagerInterface $entityManager,
            protected AuthContainerStorage $authContainerStorage,
            protected SessionManager $sessionManager,
            protected Acl $acl,
            protected array $config
    )
    {
        /* Store login callback if set */
        if (isset($config['doctrineAuth']['loginCallback'])) {
            $this->callback = $config['doctrineAuth']['loginCallback'];
        }
    }

    /**
     *
     * @return LoginForm
     */
    public function getLoginForm(): LoginForm
    {
        return $this->loginForm;
    }

    /**
     * Validate the login/auth code form
     * @param ParametersInterface $postData
     * @return bool
     */
    public function processForm(ParametersInterface $postData): bool
    {
        $this->loginForm->setData($postData);
        return $this->loginForm->isValid();
    }

    /**
     * Attempt to login user
     * @param array|null $data
     * @return boolean
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
        if (!$authResult->isValid()) {
            return false;
        }

        /* Get user identity */
        $this->identity = $authResult->getIdentity();

        /* User not active */
        if (!$this->identity->isUserActive()) {
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
        if (!$this->flushEntityManager($this->entityManager)) {
            return false;
        }

        $this->refresh($this->entityManager, $this->identity);
        return true;
    }

    /**
     * Check if using 2FA
     * @return bool
     * @throws DoctrineAuthException
     */
    public function use2Fa(): bool
    {
        if (!isset($this->config['doctrineAuth']['useTwoFactorAuthentication'])) {
            throw new DoctrineAuthException('useTwoFactorAuthentication setting not found in config');
        }

        if (!$this->config['doctrineAuth']['useTwoFactorAuthentication']) {
            return false;
        }

        return ($this->getIdentity() instanceof BaseUser && $this->identity->hasAuthMethods());
    }

    /**
     * Set identity
     * @param AuthUserInterface $identity
     * @return LoginModel
     */
    public function setIdentity(AuthUserInterface $identity): LoginModel
    {
        $this->identity = $identity;
        $this->authService->getStorage()->write($identity);
        return $this;
    }

    /**
     * Get identity
     * @return AuthUserInterface|null
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
     * @return void
     */
    public function logout(): void
    {
        $this->authService->clearIdentity();
        $this->sessionManager->destroy();
    }

    /**
     * Set form identity element error message
     *
     * @param string $message
     * @return LoginModel
     */
    public function setFormIdentityMessage(string $message): LoginModel
    {
        $this->loginForm->get($this->config['doctrine']['authentication']['orm_default']['identity_property'])->setMessages([$message]);
        return $this;
    }

    /**
     * Use forgot password link
     * @return bool
     */
    public function useForgotPassword(): bool
    {
        return (isset($this->config['doctrineAuth']['allowPasswordReset']) && $this->config['doctrineAuth']['allowPasswordReset']);
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

    /**
     * 
     * @return Container
     */
    public function getAuthContainer(): Container
    {
        return $this->authContainerStorage;
    }

}
