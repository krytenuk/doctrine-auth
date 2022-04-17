<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Form\ResetPasswordForm;
use FwsDoctrineAuth\Form\EmailForm;
use FwsDoctrineAuth\Entity\PasswordReminder;
use FwsDoctrineAuth\Entity\BaseUsers;
use DateTime;
use DateInterval;
use Laminas\Stdlib\Parameters;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Model\ViewModel;
use Laminas\Mail\Message;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Message as MimeMessage;

/**
 * ForgotPassword
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgotPasswordModel extends AbstractModel
{
    
    use SendMailTrait;

    /**
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     *
     * @var ForgotPasswordForm
     */
    private $emailForm;

    /**
     *
     * @var ResetPasswordForm
     */
    private $resetPasswordForm;

    /**
     *
     * @var BaseUsers
     */
    private $userEntity;

    /**
     *
     * @var PasswordReminder
     */
    private $resetEntity;

    /**
     *
     * @var array
     */
    private $config;

    /**
     *
     * @var Url
     */
    private $phpRenderer;

    /**
     *
     * @var boolean
     */
    private $formValid = FALSE;

    /**
     *
     * @var string
     */
    private $credentialProperty;

    /**
     * 
     * @param EntityManager $entityManager
     * @param ResetPasswordForm $resetPasswordForm
     * @param EmailForm $emailForm
     * @param PhpRenderer $phpRenderer
     * @param array $config
     */
    public function __construct(
            EntityManager $entityManager,
            ResetPasswordForm $resetPasswordForm,
            EmailForm $emailForm,
            PhpRenderer $phpRenderer,
            Array $config
    )
    {
        $this->entityManager = $entityManager;
        $this->resetPasswordForm = $resetPasswordForm;
        $this->emailForm = $emailForm;
        $this->config = $config;
        $this->phpRenderer = $phpRenderer;
        $this->credentialProperty = $this->config['doctrine']['authentication']['orm_default']['credential_property'];
    }

    /**
     * Find user by link code if valid
     * @param string $code
     * @return boolean
     */
    public function findUser($code)
    {
        /* Find password reset entity from database */
        $this->resetEntity = $this->entityManager->getRepository(PasswordReminder::class)->findOneBy(['code' => $code]);
        /* Password reset entity not found */
        if ($this->resetEntity instanceof PasswordReminder === false) {
            return false;
        }

        /* Calculate reset link expiry date/time */
        $today = new DateTime();
        $date = $this->resetEntity->getDateCreated()->add(new DateInterval(sprintf('PT%dH', $this->config['doctrineAuth']['passwordLinkActiveFor'])));
        /* Link valid */
        if ($date > $today) {
            /* Store user */
            $this->userEntity = $this->resetEntity->getUser();
            return true;
        }
        /* Link expired */
        $this->entityManager->remove($this->resetEntity);
        $this->flushEntityManager($this->entityManager);
        return false;
    }

    /**
     * Process user password reset form
     * @param Parameters $postData
     * @return bool
     */
    public function processEmailForm(Parameters $postData)
    {
        $this->emailForm->setData($postData);
        /* Email form not valid */
        if ($this->emailForm->isValid() === false) {
            return false;
        }
        $this->formValid = true;

        /* Find user entity */
        $this->userEntity = $this->entityManager->getRepository($this->config['doctrine']['authentication']['orm_default']['identity_class'])->findOneBy([$this->config['doctrine']['authentication']['orm_default']['identity_property'] => $this->emailForm->getData()[$this->emailForm->getIdentityName()]]);
        /* User not found */
        if ($this->userEntity instanceof BaseUsers === false) {
            return false;
        }

        /* Get or create password reset entity */
        if ($this->userEntity->hasPasswordReminder()) {
            $this->resetEntity = $this->userEntity->getPasswordReminder();
        } else {
            $this->resetEntity = new PasswordReminder();
        }

        /* Populate password reset entity and persist user entity */
        $this->resetEntity->setDateCreated(new DateTime())
                ->setUser($this->userEntity)
                ->setCode(uniqid());
        $this->userEntity->setPasswordReminder($this->resetEntity);
        $this->entityManager->persist($this->userEntity);
        /* Attempt to save user entity */
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Email request to set login details
     * @return boolean
     */
    public function sendEmail()
    {
        /* Render email body */
        $viewModel = new ViewModel();
        $viewModel->setTemplate('fws-doctrine-auth/emails/password-reset');
        $viewModel->siteName = $this->config['doctrineAuth']['siteName'];
        $viewModel->code = $this->resetEntity->getCode();
        $viewModel->passwordLinkActiveFor = $this->config['doctrineAuth']['passwordLinkActiveFor'];
        $emailHtmlBody = $this->phpRenderer->render($viewModel);

        $html = new MimePart($emailHtmlBody);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message = new Message();
        $message->setEncoding('utf-8');
        $message->setBody($body);
        $message->setFrom($this->config['doctrineAuth']['fromEmail'], $this->config['doctrineAuth']['siteName']);
        $message->addTo($this->userEntity->getEmailAddress());
        $message->setSubject(sprintf('%s password reset request', $this->config['doctrineAuth']['siteName']));

        return $this->sendMail($message);
    }

    /**
     * Process new password form
     * @param Parameters $postData
     * @return boolean
     * @throws DoctrineAuthException
     */
    public function processResetForm(Parameters $postData)
    {
        $this->resetPasswordForm->setData($postData);
        /* Form is invalid */
        if ($this->resetPasswordForm->isValid() === false) {
            return false;
        }
        $this->formValid = true;
        
        /* Get credential setter name and check it exists in user entity */
        $credentialSetter = 'set' . ucfirst($this->resetPasswordForm->getCredentialName());
        if (is_callable([$this->userEntity, $credentialSetter]) === false) {
            throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialSetter, get_class($this->userEntity)));
        }
        
        /* Encrypt credential */
        $bcrypt = new Bcrypt();
        $this->userEntity->$credentialSetter($bcrypt->create($this->resetPasswordForm->getData()[$this->resetPasswordForm->getCredentialName()]));
        /* Persist user entity */
        $this->entityManager->persist($this->userEntity);
        /* Remove password reset entity */
        $this->entityManager->remove($this->resetEntity);
        /* Save user entity to database */
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     *
     * @return EmailForm
     */
    public function getEmailForm(): EmailForm
    {
        return $this->emailForm;
    }

    /**
     *
     * @return \FwsDoctrineAuth\Model\ResetPasswordForm
     */
    public function getResetPasswordForm(): ResetPasswordForm
    {
        return $this->resetPasswordForm;
    }

    /**
     *
     * @return boolean
     */
    public function isFormValid()
    {
        return $this->formValid;
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
