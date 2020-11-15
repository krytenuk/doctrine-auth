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
use Laminas\Mail\Transport\Sendmail;

/**
 * ForgotPassword
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgotPasswordModel extends AbstractModel
{

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
    private $reminderEntity;

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

    public function findUser($code)
    {
        $this->reminderEntity = $this->entityManager->getRepository(PasswordReminder::class)->findOneBy(['code' => $code]);
        if ($this->reminderEntity instanceof PasswordReminder) {
            $today = new DateTime();
            $date = $this->reminderEntity->getDateCreated()->add(new DateInterval(sprintf('PT%dH', $this->config['doctrineAuth']['passwordLinkActiveFor'])));
            if ($date > $today) {
                $this->userEntity = $this->reminderEntity->getUser();
                return TRUE;
            }
        }
        return FALSE;
    }

    public function processEmailForm(Parameters $postData)
    {
        $this->userEntity = $this->entityManager->getRepository(BaseUsers::class)->findOneBy(['emailAddress' => $postData[$this->emailForm->getIdentityName()]]);
        if ($this->userEntity instanceof BaseUsers) {
            $this->emailForm->setData($postData);
            if ($this->emailForm->isValid()) {
                if ($this->userEntity->hasPaswordReminder()) {
                    $this->reminderEntity = $this->userEntity->getPasswordReminder();
                } else {
                    $this->reminderEntity = new PasswordReminder();
                }
                $this->reminderEntity->setDateCreated(new DateTime())
                        ->setUser($this->userEntity)
                        ->setCode(uniqid());
                $this->userEntity->setPasswordReminder($this->reminderEntity);
                $this->entityManager->persist($this->userEntity);
                return $this->flushEntityManager($this->entityManager);
            }
        }
        return FALSE;
    }

    /**
     * Email request to set login details
     * @return boolean
     */
    public function sendEmail()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('fws-doctrine-auth/emails/password-reset.phtml');
        $viewModel->siteName = $this->config['doctrineAuth']['siteName'];
        $viewModel->code = $this->reminderEntity->getCode();
        $emailHtmlBody = $this->phpRenderer->render($viewModel);

        $html = new MimePart($emailHtmlBody);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts(array($html));

        $message = new Message();
        $message->setBody($body);
        $message->setFrom($this->config['doctrineAuth']['fromEmail'], $this->config['doctrineAuth']['siteName']);
        $message->addTo($this->userEntity->getEmailAddress(), $this->userEntity->getEmailAddress());
        $message->setSubject(sprintf('%s password reset request', $this->config['doctrineAuth']['siteName']));

        return $this->sendMail($message);
    }

    /**
     *
     * @param Message $message
     * @return boolean
     */
    protected function sendMail(Message $message)
    {
        $transport = new Sendmail();
        try {
            $transport->send($message);
            return TRUE;
        } catch (Exception $exception) {
            ErrorLogger::write('Unable to send email');
            ErrorLogger::vardump($exception);
            EmailLogger::write('Unable to send email');
            EmailLogger::vardump($exception);
            return FALSE;
        }
    }

    public function processResetForm(Parameters $postData)
    {
        $this->resetPasswordForm->setData($postData);
        if ($this->resetPasswordForm->isValid()) {
            $this->formValid = TRUE;
            $bcrypt = new Bcrypt();
            $credentialSetter = 'set' . ucfirst($this->resetPasswordForm->getCredentialName());
            if (!is_callable([$this->userEntity, $credentialSetter])) {
                throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialSetter, get_class($this->userEntity)));
            }
            $this->userEntity->$credentialSetter($bcrypt->create($this->resetPasswordForm->get($this->resetPasswordForm->getCredentialName())->getValue()));
            $this->entityManager->persist($this->userEntity);
            $this->entityManager->remove($this->reminderEntity);
            return $this->flushEntityManager($this->entityManager);
        }
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
