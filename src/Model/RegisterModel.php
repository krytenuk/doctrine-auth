<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Laminas\Form\FormInterface;
use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Entity\UserRoles;
use Laminas\Stdlib\Parameters;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Model\LoginModel;
use DateTime;
use Laminas\Crypt\Password\Bcrypt;
use FwsDoctrineAuth\Model\Crypt;

/**
 * Description of RegisterModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterModel extends AbstractModel
{

    /**
     *
     * @var BaseUsers
     */
    private $userEntity;

    /**
     *
     * @var RegisterForm
     */
    private $form;

    /**
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     *
     * @var string
     */
    private $callback;

    /**
     *
     * @var array
     */
    private $config;

    /**
     *
     * @var Acl
     */
    private $acl;

    /**
     *
     * @var LoginModel
     */
    private $loginModel;

    /**
     * 
     * @param FormInterface $form
     * @param EntityManager $entityManager
     * @param Acl $acl
     * @param LoginModel $loginModel
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
            FormInterface $form,
            EntityManager $entityManager,
            Acl $acl,
            LoginModel $loginModel,
            Array $config)
    {
        $this->form = $form;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->loginModel = $loginModel;
        $this->config = $config;
        if (isset($config['doctrineAuth']['registrationCallback'])) {
            $this->callback = $config['doctrineAuth']['registrationCallback'];
        }

        if (isset($config['doctrine']['authentication']['orm_default']['identity_class']) === false) {
            throw new DoctrineAuthException('identity_class not found in config');
        }
        $this->userEntity = new $config['doctrine']['authentication']['orm_default']['identity_class']();
        $this->form->bind($this->userEntity);
    }

    /**
     * Get the registration form
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * Process the registration form
     * @param Parameters $postData
     * @return boolean
     * @throws DoctrineAuthException
     */
    public function processForm(Parameters $postData)
    {
        /* Registration allowed in config */
        if ($this->allowRegistration() === false) {
            return false;
        }

        $this->form->setData($postData);

        /* Register form invalid */
        if ($this->form->isValid() === false) {
            return false;
        }

        /* userActiveAfterRegistration key not set in config */
        if (isset($this->config['doctrineAuth']['userActiveAfterRegistration']) === false) {
            throw new DoctrineAuthException('"userActiveAfterRegistration" key not found in config');
        }
        
        /* useTwoFactorAuthentication key not set in config */
        if (isset($this->config['doctrineAuth']['useTwoFactorAuthentication']) === false) {
            throw new DoctrineAuthException('useTwoFactorAuthentication key not found in config');
        }
        
        /* Set user fields not defined in form */
        $this->userEntity->setUserActive((bool) $this->config['doctrineAuth']['userActiveAfterRegistration']);
        $this->userEntity->setDateCreated(new DateTime());
        $this->userEntity->setDateModified(new DateTime());
        
        /* credential_property not set in config */
        if (!isset($this->config['doctrine']['authentication']['orm_default']['credential_property'])) {
            throw new DoctrineAuthException('credential_property not found in config');
        }

        /* Get credential setter */
        $credentialSetter = 'set' . ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property']);
        /* Credential setter does not exist in user entity */
        if (is_callable([$this->userEntity, $credentialSetter]) === false) {
            throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialSetter, get_class($this->userEntity)));
        }
        /* Encrypt user credential property */
        $this->userEntity->$credentialSetter(Crypt::bcrypytCreate($this->form->get($this->config['doctrine']['authentication']['orm_default']['credential_property'])->getValue()));

        /* Default register role not set in config */
        if (isset($this->config['doctrineAuthAcl']['defaultRegisterRole']) === false) {
            throw new DoctrineAuthException('defaultRegisterRole not found in config');
        }

        /**
         * Get default registration role id from ACL
         * @var string $roleId
         */
        $roleId = $this->acl->getDefaultRegistrationRole()->getRoleId();
        /* Role id set */
        if ($roleId) {
            /** 
             * Get user role from database 
             * @var UserRoles $role 
             */
            $role = $this->entityManager->getRepository(UserRoles::class)->findOneByRole($roleId);
            if ($role instanceof UserRoles) {
                /* Set user role */
                $this->userEntity->setUserRole($role);
            } else {
                throw new DoctrineAuthException(sprintf('Role "%s" not found on database. Have you run "$ vendor\bin\doctrine-module doctrine-auth:init"?', $this->config['doctrineAuthAcl']['defaultRegisterRole']));
            }
        }

        /* Run custom callback if set */
        if (class_exists($this->callback)) {
            $callback = new $this->callback();
            $callback($this->userEntity, $this->form, $postData);
        }

        /* Persist user entity and update database */
        $this->entityManager->persist($this->userEntity);
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Allow new users to register?
     *
     * @return boolean
     */
    public function allowRegistration()
    {
        return (bool) $this->config['doctrineAuth']['allowRegistration'];
    }

    /**
     * Auto login after successful registration?
     *
     * @return boolesn
     */
    public function autoLogin()
    {
        return (bool) $this->config['doctrineAuth']['autoRegistrationLogin'];
    }

    /**
     * Where to go if session container does not have redirect stored
     *
     * @return array
     */
    public function getDefaultRedirect(): Array
    {
        return $this->loginModel->getDefaultRedirect($this->userEntity);
    }

    /**
     *
     * @return boolean
     * @throws DoctrineAuthException
     */
    public function login()
    {
        $identitypropertyGetter = 'get' . ucfirst($this->config['doctrine']['authentication']['orm_default']['identity_property']);
        $credentialPropertyGetter = 'get' . ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property']);
        if (is_callable([$this->form->getData(), $identitypropertyGetter]) === true && is_callable([$this->form->getData(), $credentialPropertyGetter]) === true) {
            return $this->loginModel->login([
                        $this->config['doctrine']['authentication']['orm_default']['identity_property'] => $this->form->getData()->$identitypropertyGetter(),
                        $this->config['doctrine']['authentication']['orm_default']['credential_property'] => $this->form->getData()->$credentialPropertyGetter(),
            ]);
        }
        throw new DoctrineAuthException('Unable to get identity and/or credential value(s)');
    }

    /**
     * Return Laminas config
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

}
