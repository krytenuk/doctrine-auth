<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use Exception;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\File as FileTransport;
use Laminas\Mail\Transport\FileOptions;
use Laminas\Mail\Transport\Sendmail as SendmailTransport;

use function is_array;
use function microtime;
use function mt_rand;
use function rtrim;

trait SendMailTrait
{
    /**
     * Send email
     *
     * @throws DoctrineAuthException
     */
    protected function sendMail(Message $message): bool
    {
        if (! is_array($this->config)) {
            throw new DoctrineAuthException('Config not found');
        }

        $sendEmails = $this->config['doctrineAuth']['sendEmails'] ?? null;
        if ($sendEmails === null) {
            throw new DoctrineAuthException('sendEmails configuration not set');
        }

        if ($sendEmails) {
            $transport = new SendmailTransport();
        } else {
            $emailsFolder = $this->config['doctrineAuth']['emailsFolder'] ?? null;
            if (! $emailsFolder) {
                throw new DoctrineAuthException('emailsFolder configuration key not set');
            }
            $options   = new FileOptions([
                'path'     => rtrim("$emailsFolder", '/'),
                'callback' => function () {
                    return 'Message_' . microtime(true) . '_' . mt_rand() . '.eml';
                },
            ]);
            $transport = new FileTransport($options);
        }

        try {
            $transport->send($message);
            return true;
        } catch (Exception) {
            return false;
        }
    }
}
