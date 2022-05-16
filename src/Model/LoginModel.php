<?php

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Model\TwoFactorAuthModel;
use FwsDoctrineAuth\Form\LoginForm;
use Doctrine\ORM\EntityManager;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\ParametersInterface;
use DateTimeImmutable;
use DateInterval;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\LoginLog;

/**
 * LoginModel
 *
 * @author Garry Childs (Freedom Web Services)
 */
class LoginModel extends AbstractModel
{

    /**
     *
     * @var LoginForm
     */
    private LoginForm $loginForm;

    /**
     * 
     * @var TwoFactorAuthModel
     */
    private TwoFactorAuthModel $twoFactorAuthModel;

    /**
     *
     * @var AuthenticationService
     */
    private AuthenticationService $authService;

    /**
     *
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     *
     * @var BaseUsers|null
     */
    private ?BaseUsers $identity = null;

    /**
     *
     * @var Container
     */
    private Container $authContainer;

    /**
     *
     * @var SessionManager
     */
    private SessionManager $sessionManager;

    /**
     *
     * @var Acl
     */
    private Acl $acl;

    /**
     * 
     * @var ParametersInterface
     */
    private ParametersInterface $serverParams;

    /**
     *
     * @var array
     */
    private array $config;

    /**
     *
     * @var string
     */
    private string $callback = '';

    /**
     *  Set model dependencies
     * 
     * @param TwoFactorAuthModel $twoFactorAuthModel
     * @param LoginForm $loginForm
     * @param AuthenticationService $authService
     * @param EntityManager $entityManager
     * @param Container $authContainer
     * @param SessionManager $sessionManager
     * @param Acl $acl
     * @param ParametersInterface $serverParams
     * @param array $config
     */
    public function __construct(
            TwoFactorAuthModel $twoFactorAuthModel,
            LoginForm $loginForm,
            AuthenticationService $authService,
            EntityManager $entityManager,
            Container $authContainer,
            SessionManager $sessionManager,
            Acl $acl,
            ParametersInterface $serverParams,
            Array $config)
    {
        $this->twoFactorAuthModel = $twoFactorAuthModel;
        $this->loginForm = $loginForm;
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->authContainer = $authContainer;
        $this->sessionManager = $sessionManager;
        $this->acl = $acl;
        $this->serverParams = $serverParams;
        $this->config = $config;

        /* Store login callback if set */
        if (isset($config['doctrineAuth']['loginCallback'])) {
            $this->callback = $config['doctrineAuth']['loginCallback'];
        }

        if (isset($this->authContainer->authMethod) === false) {
            $this->authContainer->authMethod = null;
        }

        if (isset($this->authContainer->codeSentAttempts) === false) {
            $this->authContainer->codeSentAttempts = 0;
        }
    }

    /**
     *
     * @return FormInterface
     */
    public function getLoginForm(): FormInterface
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
     */
    public function login(?Array $data)
    {
        if ($data === null) {
            $data = $this->loginForm->getData();
        }

        $adapter = $this->authService->getAdapter();
        $adapter->setIdentity($data[$this->config['doctrine']['authentication']['orm_default']['identity_property']]);
        $adapter->setCredential($data[$this->config['doctrine']['authentication']['orm_default']['credential_property']]);
        $authResult = $this->authService->authenticate();

        /* Authentication failed */
        if ($authResult->isValid() === false) {
            return false;
        }

        /* Get user identity */
        $this->identity = $authResult->getIdentity();

        /* User not active */
        if ($this->identity->isUserActive() === false) {
            $this->authService->clearIdentity();
            return false;
        }

        /* Execute login callback if exists */
        if (class_exists($this->callback)) {
            $callback = new $this->callback();
            $callback($this->identity, $this->loginForm, $data);
        }

        /* Use 2FA */
        $this->authContainer->identity = $this->identity;
        if ($this->use2Fa() === true) {
            $this->authService->clearIdentity();
            $this->authContainer->codeSent = false;
            $this->authContainer->codeSentAttempts = 0;
            $this->twoFactorAuthModel->generateCode();
        }
        /* Update user on database */
        $this->entityManager->persist($this->identity);
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Check if using 2FA
     * @param BaseUsers|null $identity
     * @return bool
     * @throws DoctrineAuthException
     */
    public function use2Fa(): bool
    {
        /* useTwoFactorAuthentication key not found in config */
        if (isset($this->config['doctrineAuth']['useTwoFactorAuthentication']) === false) {
            throw new DoctrineAuthException('useTwoFactorAuthentication setting not found in config');
        }

        if ($this->config['doctrineAuth']['useTwoFactorAuthentication'] === false) {
            return false;
        }

        return $this->getIdentity() instanceof BaseUsers ? $this->identity->hasAuthMethods() : false;
    }

    /**
     * Get 2FA model
     * @return TwoFactorAuthModel
     */
    public function getTwoFactorAuthModel(): TwoFactorAuthModel
    {
        return $this->twoFactorAuthModel;
    }

    /**
     * Set identity
     * @param BaseUsers $identity
     * @return LoginModel
     */
    public function setIdentity(BaseUsers $identity): LoginModel
    {
        $this->identity = $identity;
        $this->authService->getStorage()->write($identity);
        return $this;
    }

    /**
     * Get identity
     * @return BaseUsers|null
     */
    public function getIdentity(): ?BaseUsers
    {
        if ($this->identity instanceof BaseUsers) {
            return $this->identity;
        }

        if ($this->authContainer->identity instanceof BaseUsers) {
            $this->identity = $this->authContainer->identity;
            return $this->identity;
        }
        return null;
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
     * Determine if redirect exists
     * @return bool
     */
    public function hasRedirect(): bool
    {
        return isset($this->authContainer->redirect) && is_array($this->authContainer->redirect);
    }

    /**
     * Can user go to redirect resource
     * @return bool
     */
    public function canRedirect(): bool
    {
        return $this->acl->isAllowed($this->identity->getUserRole()->getRole(), $this->authContainer->redirect['controller'], $this->authContainer->redirect['action']);
    }

    /**
     * Get url for redirect
     * @return string
     */
    public function getRedirectUrl(): string
    {
        $url = $this->authContainer->redirect['url'];
        $this->clearRedirect();
        return $url;
    }

    /**
     * Remove redirect from session container
     * @return void
     */
    private function clearRedirect(): void
    {
        unset($this->authContainer->redirect);
    }

    /**
     * Where to go if session container does not have redirect stored
     * 
     * @return array
     * @throws DoctrineAuthException
     */
    public function getDefaultRedirect(?BaseUsers $userEntity): Array
    {
        if ($userEntity === null) {
            $userEntity = $this->identity;
        }
        $redirect = $this->acl->getRedirect($userEntity->getUserRole()->getRole());
        if ($redirect) {
            return $redirect;
        }
        throw new DoctrineAuthException('Unable to redirect, nowhere to go!');
    }

    /**
     * Set form identity element error message
     * 
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
        return isset($this->config['doctrineAuth']['allowPasswordReset']) && $this->config['doctrineAuth']['allowPasswordReset'] === true;
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
        return $this->authContainer;
    }

    /**
     * Log failed login attempt
     * @param string $emailAddress
     * @return bool
     */
    public function logFailedAttempt(string $emailAddress): bool
    {
        $log = new FailedLoginAttemptsLog();
        $log->setEmailAddress($emailAddress)
                ->setIpAddress($this->serverParams->get('SERVER_ADDR'));

        $this->entityManager->persist($log);
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Check and block IP address if required
     * @return bool
     * @throws DoctrineAuthException
     */
    public function blockIp(): bool
    {
        if (isset($this->config['doctrineAuth']['maxLoginAttemptsTime']) === false) {
            throw new DoctrineAuthException('maxLoginAttemptsTime config key not set');
        }

        if (isset($this->config['doctrineAuth']['maxLoginAttempts']) === false) {
            throw new DoctrineAuthException('maxLoginAttempts config key not set');
        }

        $ipAddress = $this->serverParams->get('SERVER_ADDR');
        $emailAddress = $this->identity instanceof BaseUsers ? $this->identity->getEmailAddress() : $this->loginForm->getData()['emailAddress'];
        $now = new DateTimeImmutable('now');
        $date = $now->sub(new DateInterval("PT{$this->config['doctrineAuth']['maxLoginAttemptsTime']}M"));
        $failedAttempts = $this->entityManager->getRepository(FailedLoginAttemptsLog::class)->countFailedAttempts($ipAddress, $date);

        if ($this->config['doctrineAuth']['maxLoginAttempts'] === null) {
            return false;
        }
        if ($failedAttempts >= $this->config['doctrineAuth']['maxLoginAttempts']) {
            $ipBlocked = new IpBlocked();
            $ipBlocked->setIpAddress($ipAddress)
                    ->setEmailAddress($emailAddress);
            $this->entityManager->persist($ipBlocked);
            return $this->flushEntityManager($this->entityManager);
        }

        return false;
    }

    /**
     * Check if IP address is blocked
     * @return bool
     */
    public function isIpBlocked(): bool
    {
        if (isset($this->config['doctrineAuth']['loginReleaseTime']) === false) {
            throw new DoctrineAuthException('loginReleaseTime config key not set');
        }

        if ($this->config['doctrineAuth']['loginReleaseTime'] > 0) {
            $now = new DateTimeImmutable('now');
            $date = $now->sub(new DateInterval("PT{$this->config['doctrineAuth']['loginReleaseTime']}M"));
            $this->entityManager->getRepository(IpBlocked::class)->deleteBlockedIpAddress($this->serverParams->get('SERVER_ADDR'), $date);
        }

        return (bool) $this->entityManager->getRepository(IpBlocked::class)->count(['ipAddress' => $this->serverParams->get('SERVER_ADDR')]);
    }
    
    /**
     * 
     * @param bool $used2fa
     * @return void
     */
    public function logSuccessfulLogin(bool $used2fa): void
    {
        $loginLog = new LoginLog();
        $loginLog->setUser($this->entityManager->getRepository(BaseUsers::class)->findOneBy(['userId' => $this->identity->getUserId()]))
                ->setUsed2fa($used2fa);
        $this->entityManager->persist($loginLog);
        $this->flushEntityManager($this->entityManager);
        
        $this->identity->addLogin($loginLog);
        $this->authService->getStorage()->write($this->identity);
    }

}
