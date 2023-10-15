<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\SendMailTrait;
use Laminas\Mail\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

class EmailAdapter extends AbstractAdapter
{

    use SendMailTrait;

    /**
     * @inheritdoc
     */
    protected static string $name = 'email';

    /**
     * @inheritdoc
     */
    protected static string $title = 'Email';

    /**
     * @inheritdoc
     */
    protected static array $requiredProperties = ['emailAddress'];

    /**
     * Template to render the authentication 2FA code page during the login process
     * @var string
     */
    protected string $template = 'fws-doctrine-auth/2FA-templates/email-2fa';

    public function __construct(
        private PhpRenderer $phpRenderer
    )
    {}


    /**
     * Email address reply to and from email
     * Set in config @see config/autoload/doctrine.auth.config.local.php
     * @return string
     * @throws DoctrineAuthException
     */
    private function getFromEmail(): string
    {
        $fromEmail = (string) $this->config['doctrineAuth']['fromEmail'] ?? null;
        if (!$fromEmail) {
            throw new DoctrineAuthException('fromEmail config key not set');
        }

        return $fromEmail;
    }

    /**
     * Get 2FA email subject line
     * Set in config @see config/autoload/doctrine.auth.config.local.php
     * @return string
     * @throws DoctrineAuthException
     */
    private function getEmailSubject(): string
    {
        $emailSubject = (string) $this->config['doctrineAuth']['emailSubject'] ?? null;
        if (!$emailSubject) {
            throw new DoctrineAuthException('emailSubject config key not set');
        }

        return $emailSubject;
    }

    /**
     * @return bool
     * @throws DoctrineAuthException
     */
    public function sendCode(): bool
    {
        $siteName = $this->getSiteName();

        /* Render email body */
        $viewModel = new ViewModel();
        $viewModel->setTemplate('fws-doctrine-auth/emails/email-code');
        $viewModel->siteName = $siteName;
        $viewModel->code = $this->authContainerStorage->getCode();
        $viewModel->expires = $this->getCodeActiveFor();
        $emailHtmlBody = $this->phpRenderer->render($viewModel);

        $html = new MimePart($emailHtmlBody);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message = new Message();
        $message->setEncoding('utf-8');
        $message->setBody($body);
        $message->setFrom($this->getFromEmail(), $siteName);
        $message->addTo($this->authContainerStorage->getIdentity()->getEmailAddress());
        $message->setSubject($this->getEmailSubject());
        return $this->sendMail($message);
    }
}