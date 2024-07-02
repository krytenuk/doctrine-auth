<?php

namespace FwsDoctrineAuth\Model;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\PasswordReminder;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\ForgottenPasswordForm;
use FwsDoctrineAuth\Form\ResetPasswordForm;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Mail\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

/**
 * ForgotPassword
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgotPasswordModel extends AbstractModel
{
    use SendMailTrait;

    private ?AuthUserInterface $userEntity = null;
    private ?PasswordReminder $resetEntity = null;
    private bool $formValid = false;
    private ?string $identityClass;
    private ?string $identityProperty;

    /**
     *
     * @param EntityManagerInterface $entityManager
     * @param ResetPasswordForm $resetPasswordForm
     * @param ForgottenPasswordForm $emailForm
     * @param PhpRenderer $phpRenderer
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ResetPasswordForm      $resetPasswordForm,
        protected ForgottenPasswordForm  $emailForm,
        protected PhpRenderer            $phpRenderer,
        protected array                  $config
    )
    {
        $this->identityProperty = (string) $this->config['doctrine']['authentication']['orm_default']['identity_property'] ?? '';
        if (!$this->identityProperty) {
            throw new DoctrineAuthException('identity_property not set in config');
        }

        $this->identityClass = (string) $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? '';
        if (!$this->identityClass) {
            throw new DoctrineAuthException('identity_class not set in config');
        }
        if (!class_exists($this->identityClass)) {
            throw new DoctrineAuthException(sprintf('Identity class %s not does not exist', $this->identityClass));
        }
    }

    /**
     * Find user by link code if valid
     * @param string $code
     * @return boolean
     * @throws Exception
     */
    public function findUser(string $code): bool
    {
        /** Find password reset entity from database */
        $repository = $this->entityManager->getRepository(PasswordReminder::class);
        $this->resetEntity = $repository->findOneBy(['code' => $code]);
        /** Password reset entity not found */
        if (!$this->resetEntity instanceof PasswordReminder) {
            return false;
        }

        /** Calculate reset link expiry date/time */
        $today = new DateTime();
        $date = $this->resetEntity->getDateCreated()->add(new DateInterval(sprintf('PT%dH', $this->config['doctrineAuth']['passwordLinkActiveFor'])));
        /** Link valid */
        if ($date > $today) {
            /* Store user */
            $this->userEntity = $this->resetEntity->getUser();
            return true;
        }
        /* Link expired */
        if (!$this->removeEntity($this->entityManager, $this->resetEntity)) {
            return false;
        }
        $this->flushEntityManager($this->entityManager);
        return false;
    }

    /**
     * Process user password reset form
     * @param Parameters $postData
     * @return bool
     */
    public function processEmailForm(Parameters $postData): bool
    {
        $this->emailForm->setData($postData);
        /** Email form not valid */
        if (!$this->emailForm->isValid()) {
            return false;
        }
        $this->formValid = true;

        /** Find user entity */
        $repository = $this->entityManager->getRepository($this->identityClass);
        $this->userEntity = $repository->findOneBy(['emailAddress' => $this->emailForm->getData()['emailAddress']]);
        /** User not found on database */
        if (!$this->userEntity instanceof BaseUser) {
            return false;
        }

        /** Get or create password reset entity */
        if ($this->userEntity->hasPasswordReminder()) {
            $this->resetEntity = $this->userEntity->getPasswordReminder();
        } else {
            $this->resetEntity = new PasswordReminder();
            $this->resetEntity->setUser($this->userEntity);
            $this->userEntity->setPasswordReminder($this->resetEntity);
        }

        /** Populate password reset entity and persist user entity */
        $this->resetEntity
            ->setDateCreated(new DateTime())
            ->setCode(uniqid());

        /** Save user entity */
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Email request to set login details
     * @return boolean
     * @throws DoctrineAuthException
     */
    public function sendEmail(): bool
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
    public function processResetForm(Parameters $postData): bool
    {
        $this->resetPasswordForm->setData($postData);
        /* Form is invalid */
        if (!$this->resetPasswordForm->isValid()) {
            return false;
        }
        $this->formValid = true;

        /* Get credential setter name and check it exists in user entity */
        $credentialSetter = 'set' . ucfirst($this->resetPasswordForm->getCredentialName());
        if (!is_callable([$this->userEntity, $credentialSetter])) {
            throw new DoctrineAuthException(sprintf('Method "%s" not found in "%s"', $credentialSetter, get_class($this->userEntity)));
        }

        /* Hash password */
        $crypt = new Bcrypt();
        $this->userEntity->$credentialSetter($crypt->create($this->resetPasswordForm->getData()[$this->resetPasswordForm->getCredentialName()]));
        /* Remove password reset entity */
        if (!$this->removeEntity($this->entityManager, $this->resetEntity)) {
            return false;
        }
        /* Save user entity to database */
        return $this->flushEntityManager($this->entityManager);
    }

    /**
     * Get email password form
     * @return ForgottenPasswordForm
     */
    public function getEmailForm(): ForgottenPasswordForm
    {
        return $this->emailForm;
    }

    /**
     * Get reset password form
     * @return ResetPasswordForm
     */
    public function getResetPasswordForm(): ResetPasswordForm
    {
        return $this->resetPasswordForm;
    }

    /**
     *
     * @return boolean
     */
    public function isFormValid(): bool
    {
        return $this->formValid;
    }

    /**
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

}
