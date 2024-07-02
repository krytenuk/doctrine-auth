<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\Repository\UserRoleRepository;
use FwsDoctrineAuth\Entity\UserRole;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\RegisterForm;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;

/**
 * Description of RegisterModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 *
 */
class RegisterModel extends AbstractModel
{

    private AuthUserInterface $userEntity;
    private ?string $callback = null;

    /**
     *
     * @param RegisterForm $form
     * @param EntityManagerInterface $entityManager
     * @param Acl $acl
     * @param LoginModel $loginModel
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
            protected RegisterForm $form,
            protected EntityManagerInterface $entityManager,
            protected Acl $acl,
            protected LoginModel $loginModel,
            protected array $config
    )
    {
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
     * @return RegisterForm
     */
    public function getForm(): RegisterForm
    {
        return $this->form;
    }

    /**
     * Process the registration form
     * @param Parameters $postData
     * @return boolean
     * @throws DoctrineAuthException
     */
    public function processForm(Parameters $postData): bool
    {
        /* Registration allowed in config */
        if (!$this->allowRegistration()) {
            return false;
        }

        $this->form->setData($postData);

        /* Register form invalid */
        if (!$this->form->isValid()) {
            return false;
        }

        /* userActiveAfterRegistration key not set in config */
        if (!isset($this->config['doctrineAuth']['userActiveAfterRegistration'])) {
            throw new DoctrineAuthException('"userActiveAfterRegistration" key not found in config');
        }

        /* useTwoFactorAuthentication key not set in config */
        if (!isset($this->config['doctrineAuth']['useTwoFactorAuthentication'])) {
            throw new DoctrineAuthException('useTwoFactorAuthentication key not found in config');
        }

        /* Set user fields not defined in form */
        $this->userEntity->setUserActive((bool) $this->config['doctrineAuth']['userActiveAfterRegistration']);

        /* credential_property not set in config */
        if (!isset($this->config['doctrine']['authentication']['orm_default']['credential_property'])) {
            throw new DoctrineAuthException('credential_property not found in config');
        }

        /* Get credential setter */
        $credentialSetter = 'set' . ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property']);
        $credentialGetter = 'get' . ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property']);
        /* Credential setter does not exist in user entity */
        if (!is_callable([$this->userEntity, $credentialSetter])) {
            throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialSetter, get_class($this->userEntity)));
        }
        if (!is_callable([$this->userEntity, $credentialGetter])) {
            throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialGetter, get_class($this->userEntity)));
        }

        $bcrypt = new Bcrypt();
        $this->userEntity->$credentialSetter($bcrypt->create($this->userEntity->$credentialGetter()));


        /* Default register role not set in config */
        if (!isset($this->config['doctrineAuthAcl']['defaultRegisterRole'])) {
            throw new DoctrineAuthException('defaultRegisterRole not found in config');
        }

        /**
         * Get default registration role id from ACL
         */
        $roleId = $this->acl->getDefaultRegistrationRole()->getRoleId();
        /* Role id set */
        if ($roleId) {
            /** @var UserRoleRepository $repository */
            $repository = $this->entityManager->getRepository(UserRole::class);
            /**
             * @var UserRole $role
             */
            $role = $repository->findOneByRole($roleId);
            if ($role instanceof UserRole) {
                /* Set user role */
                $this->userEntity->setUserRole($role);
            } else {
                throw new DoctrineAuthException(sprintf('Role "%s" not found on database. Have you run "$ vendor\bin\doctrine-module doctrine-auth:init"?', $this->config['doctrineAuthAcl']['defaultRegisterRole']));
            }
        }

        /* Run custom callback if set */
        if ($this->callback !== null && class_exists($this->callback)) {
            $callback = new $this->callback();
            $callback($this->userEntity, $this->form, $postData, $this->config);
        }

        /* Persist user entity and update database */
        $this->persistEntity($this->entityManager, $this->userEntity);
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Allow new users to register?
     *
     * @return boolean
     */
    public function allowRegistration(): bool
    {
        return (bool) $this->config['doctrineAuth']['allowRegistration'];
    }

    /**
     * Auto login after successful registration?
     *
     * @return bool
     */
    public function autoLogin(): bool
    {
        return (bool) $this->config['doctrineAuth']['autoRegistrationLogin'];
    }

    /**
     *
     * @return boolean
     * @throws DoctrineAuthException
     */
    public function login(): bool
    {
        $identityProperty = $this->config['doctrine']['authentication']['orm_default']['identity_property'];
        $credentialProperty = $this->config['doctrine']['authentication']['orm_default']['credential_property'];
        $identityPropertyGetter = 'get' . ucfirst($identityProperty);
        $credentialPropertyGetter = 'get' . ucfirst($credentialProperty);
        if (is_callable([$this->form->getData(), $identityPropertyGetter]) && is_callable([$this->form->getData(), $credentialPropertyGetter])) {
            return $this->loginModel->login([
                        $identityProperty => $this->form->getData()->$identityPropertyGetter(),
                        $credentialProperty => $this->form->getData()->$credentialPropertyGetter(),
            ]);
        }
        throw new DoctrineAuthException('Unable to get identity and/or credential value(s)');
    }

    /**
     * Return Laminas config
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the newly registered user
     * @return AuthUserInterface
     */
    public function getUser(): AuthUserInterface
    {
        return $this->userEntity;
    }

}
