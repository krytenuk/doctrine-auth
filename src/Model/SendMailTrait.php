<?php

namespace FwsDoctrineAuth\Model;

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
     */
    protected function sendMail(Message $message)
    {
        if (is_array($this->config) === false) {
            new DoctrineAuthException('Config not found');
        }

        if (isset($this->config['doctrineAuth']['sendEmails']) === false) {
            new DoctrineAuthException('sendEmails configuration key not set');
        }

        if ($this->config['doctrineAuth']['sendEmails'] === true) {
            $transport = new SendmailTransport();
        } else {
            if (isset($this->config['doctrineAuth']['emailsFolder']) === false) {
                new DoctrineAuthException('emailsFolder configuration key not set');
            }
            $options = new FileOptions([
                'path' => "{$this->config['doctrineAuth']['emailsFolder']}/",
                'callback' => function (FileTransport $transport) {
                    return 'Message_' . microtime(true) . '_' . mt_rand() . '.eml';
                },
            ]);
            $transport = new FileTransport($options);
        }

        try {
            $transport->send($message);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

}
