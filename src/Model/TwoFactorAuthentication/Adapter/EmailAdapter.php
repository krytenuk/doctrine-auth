<?php

declare(strict_types=1);

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

    /** @inheritDoc */
    protected static string $name = 'email';

    /** @inheritDoc */
    protected static string $title = 'Email';

    /** @inheritDoc */
    protected static array $requiredProperties = ['emailAddress'];

    /**
     * Template to render the authentication 2FA code page during the login process
     */
    protected string $template = 'fws-doctrine-auth/2FA-templates/email-2fa';

    public function __construct(
        private PhpRenderer $phpRenderer
    )
    {
    }

    /**
     * Email address reply to and from email
     * Set in config @throws DoctrineAuthException
     * @see config/autoload/doctrine.auth.config.local.php
     *
     */
    private function getFromEmail(): string
    {
        $fromEmail = (string)$this->config['doctrineAuth']['fromEmail'] ?? null;
        if (!$fromEmail) {
            throw new DoctrineAuthException('fromEmail config key not set');
        }

        return $fromEmail;
    }

    /**
     * Get 2FA email subject line
     * Set in config @throws DoctrineAuthException
     * @see config/autoload/doctrine.auth.config.local.php
     *
     */
    private function getEmailSubject(): string
    {
        $emailSubject = (string)$this->config['doctrineAuth']['emailSubject'] ?? null;
        if (!$emailSubject) {
            throw new DoctrineAuthException('emailSubject config key not set');
        }

        return $emailSubject;
    }

    /**
     * @throws DoctrineAuthException
     */
    public function sendCode(): bool
    {
        $siteName = $this->getSiteName();

        /* Render email body */
        $viewModel = new ViewModel([
            'siteName' => $siteName,
            'code' => $this->authContainerStorage->getCode(),
            'expires' => $this->getCodeActiveFor(),
        ]);
        $viewModel->setTemplate('fws-doctrine-auth/emails/email-code');
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
