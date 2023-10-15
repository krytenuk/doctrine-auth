<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter;

use Exception;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StripTags;
use Laminas\Http\Client;
use Laminas\Json\Json;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

class BulkSmsAdapter extends AbstractAdapter
{

    const BULKSMS_API_BASE_URL = 'https://api.bulksms.com/v1/';
    const BULKSMS_API_SUCCESS_STATUS_CODE = 201;
    const ENC_JSON = 'application/json';

    /**
     * @inheritdoc
     */
    protected static string $name = 'sms';

    /**
     * @inheritdoc
     */
    protected static string $title = 'Text message';

    /**
     * @inheritdoc
     */
    protected static array $requiredProperties = ['mobileNumber'];

    /**
     * Template to render the authentication 2FA code page during the login process
     * @var string
     */
    protected string $template = 'fws-doctrine-auth/2FA-templates/sms-2fa';

    public function __construct(
        private PhpRenderer $phpRenderer
    )
    {}

    /**
     * Get the bulk sms token id
     * @return mixed
     * @throws DoctrineAuthException
     */
    public function getBulkSmsApiTokenId(): string
    {
        $bulkSmsApiTokenId = (string) $this->config['doctrineAuth']['bulkSmsApiTokenId'] ?? '';
        if (!$bulkSmsApiTokenId) {
            throw new DoctrineAuthException('bulkSmsApiTokenId config key not set');
        }

        return$bulkSmsApiTokenId;
    }

    /**
     * Get the bulk sms token secret
     * @return string
     * @throws DoctrineAuthException
     */
    public function getBulkSmsApiTokenSecret(): string
    {
        $bulkSmsApiTokenSecret = (string) $this->config['doctrineAuth']['bulkSmsApiTokenSecret'] ?? null;
        if (!$bulkSmsApiTokenSecret) {
            throw new DoctrineAuthException('bulkSmsApiTokenSecret config key not set');
        }

        return $bulkSmsApiTokenSecret;
    }

    /**
     * Get the SMS from text
     * Shows who sent the text to the user, max 11 characters
     * @return string
     * @throws DoctrineAuthException
     */
    public function getSmsFrom(): string
    {
        $smsFromName = (string) $this->config['doctrineAuth']['smsFrom'];
        if (!$smsFromName) {
            throw new DoctrineAuthException('siteName config key not set');
        }

        return substr($smsFromName, 0, 11);
    }

    /**
     * @inheritDoc
     * @throws DoctrineAuthException
     */
    public function sendCode(): bool
    {
        $siteName = $this->getSiteName();

        /* Render sms body */
        $viewModel = new ViewModel();
        $viewModel->setTemplate('fws-doctrine-auth/sms/text-code');
        $viewModel->siteName = $siteName;
        $viewModel->code = $this->authContainerStorage->getCode();
        $viewModel->expires = $this->getCodeActiveFor();
        $smsBody = $this->phpRenderer->render($viewModel);

        $pregReplace = new PregReplace([
            'pattern' => '/\\n/',
            'replacement' => ' ',
        ]);
        $filterChain = new FilterChain();
        $filterChain->attach(new StripTags())
            ->attach($pregReplace);
        $message = [
            'to' => $this->authContainerStorage->getIdentity()->getMobileNumber(),
            'from' => $this->getSmsFrom(),
            'body' => $filterChain->filter($smsBody),
        ];

        $client = new Client(self::BULKSMS_API_BASE_URL . 'messages', [
            'adapter' => Client\Adapter\Curl::class,
            'curloptions' => [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
            ],
        ]);
        $client->setEncType(self::ENC_JSON);
        $client->setAuth($this->getBulkSmsApiTokenId(), $this->getBulkSmsApiTokenSecret());
        $client->setMethod('POST');
        $client->setRawBody(Json::encode($message));

        try {
            $response = $client->send();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            return false;
        }

        if ($response->getStatusCode() === self::BULKSMS_API_SUCCESS_STATUS_CODE) {
            $this->authContainerStorage->increaseCodeSentAttempts();
            return true;
        }

        $responseBody = json_decode($response->getBody());
        if ($responseBody && property_exists($responseBody, 'detail')) {
            $this->error($responseBody->detail);
        }

        return false;
    }
}