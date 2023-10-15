<?php

namespace FwsDoctrineAuth\Model;

use Exception;
use Laminas\Mail\Transport\Sendmail as SendmailTransport;
use Laminas\Mail\Transport\File as FileTransport;
use Laminas\Mail\Transport\FileOptions;
use Laminas\Mail\Message;
use FwsDoctrineAuth\Exception\DoctrineAuthException;

/**
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
trait SendMailTrait
{

    /**
     * Send email
     * @param Message $message
     * @return boolean
     * @throws DoctrineAuthException
     */
    protected function sendMail(Message $message): bool
    {
        if (!is_array($this->config)) {
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
            if (!$emailsFolder) {
                throw new DoctrineAuthException('emailsFolder configuration key not set');
            }
            $options = new FileOptions([
                'path' => rtrim("$emailsFolder", '/'),
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
