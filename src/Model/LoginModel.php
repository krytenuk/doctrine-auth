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
     *
     * @return FormInterface
     */
    function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     *
     * @return Users
     */
    public function getIdentity()
    {
        return $this->identity;
    }

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

        if (isset($config['doctrineAuth']['loginCallback'])) {
            $this->callback = $config['doctrineAuth']['loginCallback'];
        }
    }

    public function processForm(Parameters $postData)
    {
        $this->form->setData($postData);
        return $this->form->isValid();
    }

    /**
     * Attempt to login user
     * @param array $data
     * @return boolean
     */
    public function login(Array $data = NULL)
    {
        if ($data === NULL) {
            $data = $this->form->getData();
        }

        $adapter = $this->authService->getAdapter();
        $adapter->setIdentity($data[$this->config['doctrine']['authentication']['orm_default']['identity_property']]);
        $adapter->setCredential($data[$this->config['doctrine']['authentication']['orm_default']['credential_property']]);
        $authResult = $this->authService->authenticate();

        if ($authResult->isValid()) {
            $this->identity = $authResult->getIdentity();

            if ($this->identity->isUserActive()) {
                if (class_exists($this->callback)) {
                    $callback = new $this->callback();
                    $callback($this->identity, $this->form, $data);
                }
                $this->authService->getStorage()->write($this->identity);
                $this->entityManager->persist($this->identity);
                return $this->flushEntityManager($this->entityManager);
            }
        }
        return FALSE;
    }

    public function logout()
    {
        $this->authService->clearIdentity();
        $this->sessionManager->destroy();
    }
    
    /**
     * 
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
     * @return boolean
     */
    public function hasRedirect()
    {
        return isset($this->authContainer->redirect) && is_array($this->authContainer->redirect);
    }

    /**
     * Can user go to redirect rescource
     * @return boolean
     */
    public function canRedirect()
    {
        if ($this->acl->isAllowed($this->identity->getUserRole()->getRole(), $this->authContainer->redirect['controller'], $this->authContainer->redirect['action'])) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Get url for redirect
     * @return string
     */
    public function getRedirectUrl()
    {
        $url = $this->authContainer->redirect['url'];
        $this->clearRedirect();
        return $url;
    }

    /**
     * Remove redirect from session container
     */
    private function clearRedirect()
    {
        unset($this->authContainer->redirect);
    }

    /**
     * Where to go if session container does not have redirect stored
     * 
     * @return array
     * @throws DoctrineAuthException
     */
    public function getDefaultRedirect(BaseUsers $userEntity = NULL): Array
    {
        if ($userEntity === NULL) {
            $userEntity = $this->identity;
        }
        if ($redirect = $this->acl->getRedirect($userEntity->getUserRole()->getRole())) {
            return $redirect;
        }
        throw new DoctrineAuthException('Unable to redirect, nowhere to go!');
    }

    public function getFormIdentityElement()
    {
        return $this->form->get($this->config['doctrine']['authentication']['orm_default']['identity_property']);
    }
    
    public function useForgotPassword()
    {
        return isset($this->config['doctrineAuth']['allowPasswordReset']) && $this->config['doctrineAuth']['allowPasswordReset'] == TRUE;
    }
    
    /**
     * 
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

}
