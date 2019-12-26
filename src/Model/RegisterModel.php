<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Zend\Form\FormInterface;
use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Entity\UserRoles;
use Zend\Stdlib\Parameters;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Model\LoginModel;
use DateTime;
use Zend\Crypt\Password\Bcrypt;

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

        if (!isset($config['doctrine']['authentication']['orm_default']['identity_class'])) {
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
        if (!$this->allowRegistration()) {
            return FALSE;
        }

        $this->form->setData($postData);
        if ($this->form->isValid()) {
            if (!isset($this->config['doctrineAuth']['userActiveAfterRegistration'])) {
                throw new DoctrineAuthException('"userActiveAfterRegistration" key not found in config');
            }
            $this->userEntity->setUserActive((int) $this->config['doctrineAuth']['userActiveAfterRegistration']);
            $this->userEntity->setDateCreated(new DateTime());
            $this->userEntity->setDateModified(new DateTime());

            /*
             * Encrypt credential property prior to writing to database
             */
            if (!isset($this->config['doctrine']['authentication']['orm_default']['credential_property'])) {
                throw new DoctrineAuthException('credential_property not found in config');
            }
            $bcrypt = new Bcrypt();
            $credentialSetter = 'set' . ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property']);
            if (!is_callable([$this->userEntity, $credentialSetter])) {
                throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialSetter, get_class($this->userEntity)));
            }
            $this->userEntity->$credentialSetter($bcrypt->create($this->form->get($this->config['doctrine']['authentication']['orm_default']['credential_property'])->getValue()));

            if (!isset($this->config['doctrineAuthAcl']['defaultRegisterRole'])) {
                throw new DoctrineAuthException('defaultRegisterRole not found in config');
            }
            if ($roleId = $this->acl->getDefaultRegistrationRole()->getRoleId()) {
                $role = $this->entityManager->getRepository(UserRoles::class)->findOneByRole($roleId);
                if ($role instanceof UserRoles) {
                    $this->userEntity->setUserRole($role);
                } else {
                    throw new DoctrineAuthException(sprintf('Role "%s" not found on database. Have you run "$ vendor\bin\doctrine-module doctrine-auth:init"?', $this->config['doctrineAuthAcl']['defaultRegisterRole']));
                }
            }

            if (class_exists($this->callback)) {
                $callback = new $this->callback();
                $callback($this->userEntity, $this->form, $postData);
            }

            $this->entityManager->persist($this->userEntity);
            return $this->flushEntityManager($this->entityManager);
        }
        return FALSE;
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
        if (is_callable([$this->form->getData(), $identitypropertyGetter]) && is_callable([$this->form->getData(), $credentialPropertyGetter])) {
            return $this->loginModel->login([
                        $this->config['doctrine']['authentication']['orm_default']['identity_property'] => $this->form->getData()->$identitypropertyGetter(),
                        $this->config['doctrine']['authentication']['orm_default']['credential_property'] => $this->form->getData()->$credentialPropertyGetter(),
            ]);
        }
        throw new DoctrineAuthException('Unable to get identity and/or credential value(s)');
    }

}
