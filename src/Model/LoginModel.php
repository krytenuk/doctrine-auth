<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Laminas\Form\FormInterface;
use DateTime;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\Parameters;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Entity\BaseUsers;
use Laminas\Form\ElementInterface;

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
    private $form;

    /**
     *
     * @var AuthenticationService
     */
    private $authService;

    /**
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     *
     * @var DateTime
     */
    private $currentDateTime;

    /**
     *
     * @var BaseUsers
     */
    private $identity;

    /**
     *
     * @var Container
     */
    private $authContainer;

    /**
     *
     * @var SessionManager
     */
    private $sessionManager;

    /**
     *
     * @var Acl
     */
    private $acl;

    /**
     *
     * @var Array
     */
    private $config;

    /**
     *
     * @var string
     */
    private $callback;

    /**
     * Set model dependencies
     * 
     * @param FormInterface $form
     * @param AuthenticationService $authService
     * @param EntityManager $entityManager
     * @param DateTime $currentDateTime
     * @param Container $authContainer
     * @param SessionManager $sessionManager
     * @param Acl $acl
     * @param array $config
     */
    public function __construct(
            FormInterface $form,
            AuthenticationService $authService,
            EntityManager $entityManager,
            DateTime $currentDateTime,
            Container $authContainer,
            SessionManager $sessionManager,
            Acl $acl,
            Array $config)
    {
        $this->form = $form;
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->currentDateTime = $currentDateTime;
        $this->authContainer = $authContainer;
        $this->sessionManager = $sessionManager;
        $this->acl = $acl;
        $this->config = $config;

        /* Store login callback if set */
        if (isset($config['doctrineAuth']['loginCallback'])) {
            $this->callback = $config['doctrineAuth']['loginCallback'];
        }
    }

    /**
     *
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     *
     * @return Users
     */
    public function getIdentity(): BaseUsers
    {
        return $this->identity;
    }

    /**
     * Validate the login form
     * @param Parameters $postData
     * @return boolean
     */
    public function processForm(Parameters $postData)
    {
        $this->form->setData($postData);
        return $this->form->isValid();
    }

    /**
     * Attempt to login user
     * @param array|null $data
     * @return boolean
     */
    public function login(?Array $data)
    {
        if ($data === null) {
            $data = $this->form->getData();
        }

        /* Authenticate user */
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
            return false;
        }
        
        /* Execute login callback if exists */
        if (class_exists($this->callback)) {
            $callback = new $this->callback();
            $callback($this->identity, $this->form, $data);
        }
        
        /* Store identity and update user on database */
        $this->authService->getStorage()->write($this->identity);
        $this->entityManager->persist($this->identity);
        return $this->flushEntityManager($this->entityManager);
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
     * Set identity
     * @param BaseUsers $identity
     * @return $this
     */
    public function setIdentity(BaseUsers $identity)
    {
        $this->identity = $identity;
        return $this;
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
     * Get form identity element
     * @return ElementInterface
     */
    public function getFormIdentityElement(): ElementInterface
    {
        return $this->form->get($this->config['doctrine']['authentication']['orm_default']['identity_property']);
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
    public function getConfig()
    {
        return $this->config;
    }

}
