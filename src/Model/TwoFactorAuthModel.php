<?php

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Entity\GoogleAuth;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use FwsDoctrineAuth\Form\TwoFactorAuthCodeForm;
use Laminas\Session\Container;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Stdlib\ParametersInterface;
use DateTimeImmutable;
use DateInterval;
use Laminas\View\Model\ViewModel;
use Laminas\Mail\Message;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Message as MimeMessage;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Http\Client;
use Laminas\Json\Json;
use Laminas\Filter\StripTags;
use Laminas\Filter\PregReplace;
use Laminas\Filter\FilterChain;
use PragmaRX\Google2FA\Google2FA;

/**
 * TwoFactorAuth
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthModel
{

    use SendMailTrait;

    /**
     * 2FA methods
     */
    const GOOGLEAUTHENTICATOR = 'google-auth';
    const EMAIL = 'email';
    const SMS = 'sms';
    const BULKSMS_API_BASE_URL = 'https://api.bulksms.com/v1/';
    const BULKSMS_API_SUCCESS_STATUS_CODE = 201;
    const SEND_SMS_MAX_ATTEMPTS = 10;
    const ENC_JSON = 'application/json';
    const GOOGLE_AUTHENTICATOR_BASE_URL = 'https://www.authenticatorapi.com/api.asmx/ValidatePin';

    /**
     * Valid 2FA methods
     */
    const VALIDAUTHENTICATIONMETHODS = [
        self::GOOGLEAUTHENTICATOR => 'Google authenticator app',
        self::EMAIL => 'Email',
        self::SMS => 'Text message',
    ];

    /**
     * 
     * @var SelectTwoFactorAuthMethodForm
     */
    private SelectTwoFactorAuthMethodForm $selectAuthMethodForm;

    /**
     * 
     * @var TwoFactorAuthForm
     */
    private TwoFactorAuthCodeForm $authForm;

    /**
     * Laminas config
     * @var array
     */
    private array $config;

    /**
     * Laminas session container
     * @var Container
     */
    private Container $authContainer;

    /**
     * 
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $currentDateTime;

    /**
     * 
     * @var PhpRenderer
     */
    private PhpRenderer $phpRenderer;

    /**
     * 
     * @param SelectTwoFactorAuthMethodForm $selectAuthMethodForm
     * @param TwoFactorAuthCodeForm $authForm
     * @param Container $authContainer
     * @param DateTimeImmutable $currentDateTime
     * @param PhpRenderer $phpRenderer
     * @param array $config
     */
    public function __construct(
            SelectTwoFactorAuthMethodForm $selectAuthMethodForm,
            TwoFactorAuthCodeForm $authForm,
            Container $authContainer,
            DateTimeImmutable $currentDateTime,
            PhpRenderer $phpRenderer,
            array $config
    )
    {
        $this->selectAuthMethodForm = $selectAuthMethodForm;
        $this->authForm = $authForm;
        $this->config = $config;
        $this->authContainer = $authContainer;
        $this->currentDateTime = $currentDateTime;
        $this->phpRenderer = $phpRenderer;
    }

    /**
     * 
     * @return SelectTwoFactorAuthMethodForm
     */
    public function getSelectAuthMethodForm(): SelectTwoFactorAuthMethodForm
    {
        return $this->selectAuthMethodForm;
    }

    /**
     * 
     * @return TwoFactorAuthForm
     */
    public function getAuthForm(): TwoFactorAuthCodeForm
    {
        return $this->authForm;
    }

    /**
     * Process select authentication form
     * @param ParametersInterface $postData
     * @return bool
     */
    public function processSelectForm(ParametersInterface $postData): bool
    {
        $this->selectAuthMethodForm->setData($postData);
        return $this->selectAuthMethodForm->isValid();
    }

    /**
     * Process authenticate code form
     * @param ParametersInterface $postData
     * @return bool
     */
    public function processAuthForm(ParametersInterface $postData): bool
    {
        $this->authForm->setData($postData);
        return $this->authForm->isValid();
    }

    /**
     * Generate code for SMS and email authentication
     * @return TwoFactorAuthModel
     */
    public function generateCode(): TwoFactorAuthModel
    {
        $this->authContainer->code = (int) mt_rand(100000, 999999);
        return $this;
    }

    /**
     * Send code
     * @return bool
     */
    public function sendCode(): bool
    {
        if ($this->authContainer->identity instanceof BaseUsers === false) {
            return false;
        }
        switch ($this->authContainer->authMethod) {
            case self::GOOGLEAUTHENTICATOR:
                $this->authContainer->codeSent = $this->currentDateTime;
                return true;
            case self::EMAIL:
                if ($this->sendEmail() === true) {
                    $this->authContainer->codeSent = $this->currentDateTime;
                    return true;
                }
                break;
            case self::SMS:
                if ($this->authContainer->codeSentAttempts >= self::SEND_SMS_MAX_ATTEMPTS) {
                    return false;
                }
                if ($this->sendSms() === true) {
                    $this->authContainer->codeSent = $this->currentDateTime;
                }
                break;
        }

        return false;
    }

    /**
     * 
     * @return bool
     * @throws DoctrineAuthException
     */
    public function codeExpired(): bool
    {
        if ($this->authContainer->authMethod === self::GOOGLEAUTHENTICATOR) {
            return false;
        }

        if (isset($this->config['doctrineAuth']['twoFactorCodeActiveFor']) === false) {
            throw new DoctrineAuthException('twoFactorCodeActiveFor config key not set');
        }

        /**
         * Check if code expired
         */
        $expires = $this->authContainer->codeSent->add(new DateInterval("PT{$this->config['doctrineAuth']['twoFactorCodeActiveFor']}M"));
        return $this->currentDateTime > $expires;
    }

    /**
     * Authenticate using 2FA codes
     * 
     * @param TwoFactorAuthForm $authForm
     * @return bool
     */
    public function authenticate(): bool
    {
        if (isset($this->authContainer->code) === false) {
            $this->authContainer->code = null;
        }

        switch ($this->authContainer->authMethod) {
            case self::GOOGLEAUTHENTICATOR:
                $secret = $this->getSecret();
                if ($secret === null) {
                    return false;
                }
                $google2Fa = new Google2FA();
                return $google2Fa->verifyKey($secret, (string)$this->authForm->getData()['code']);
            case self::EMAIL:
            case self::SMS:
                return $this->authForm->getData()['code'] === $this->authContainer->code;
        }
        return false;
    }
    
    /**
     * Count number of user 2FA methods
     * @return int
     */
    public function countUserAuthMethods(): int
    {
        if ($this->authContainer->identity instanceof BaseUsers) {
            return $this->authContainer->identity->countAuthMethods();
        }
        return 0;
    }
    
    public function getSingleAuthMethod()
    {
        if ($this->countUserAuthMethods() !== 1) {
            return null;
        }
        return $this->authContainer->identity->getAuthMethods()->current();
    }

    /**
     * Get google auth secret
     * @return string|null
     */
    private function getSecret(): ?string
    {
        if (isset($this->authContainer->identity) && $this->authContainer->identity instanceof BaseUsers) {
            $authMethod = $this->authContainer->identity->getAuthMethod(self::GOOGLEAUTHENTICATOR);
            if ($authMethod !== null) {
                return $authMethod->getGoogleAuth()->getSecret();
            }
        }
        if ($this->authContainer->secret instanceof GoogleAuth) {
            return $this->authContainer->secret->getSecret();
        }
        return null;
    }

    /**
     * Set selected authentication method
     * @param string $method
     * @return bool
     */
    public function setTwoFactorAuthMethod(string $method): bool
    {
        if (array_key_exists($method, TwoFactorAuthModel::VALIDAUTHENTICATIONMETHODS) === false) {
            $this->authContainer->authMethod = null;
            return false;
        }

        $this->authContainer->authMethod = $method;
        return true;
    }

    /**
     * Email 2fa code
     * @return boolean
     */
    public function sendEmail()
    {
        if (isset($this->config['doctrineAuth']['twoFactorCodeActiveFor']) === false) {
            throw new DoctrineAuthException('twoFactorCodeActiveFor config key not set');
        }

        if (isset($this->config['doctrineAuth']['siteName']) === false) {
            throw new DoctrineAuthException('siteName config key not set');
        }

        /* Render email body */
        $viewModel = new ViewModel();
        $viewModel->setTemplate('fws-doctrine-auth/emails/email-code');
        $viewModel->siteName = $this->config['doctrineAuth']['siteName'];
        $viewModel->code = $this->authContainer->code;
        $viewModel->expires = $this->config['doctrineAuth']['twoFactorCodeActiveFor'];
        $emailHtmlBody = $this->phpRenderer->render($viewModel);

        $html = new MimePart($emailHtmlBody);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message = new Message();
        $message->setEncoding('utf-8');
        $message->setBody($body);
        $message->setFrom($this->config['doctrineAuth']['fromEmail'], $this->config['doctrineAuth']['siteName']);
        $message->addTo($this->authContainer->identity->getEmailAddress());
        $message->setSubject(sprintf('%s login authentication request', $this->config['doctrineAuth']['siteName']));

        return $this->sendMail($message);
    }

    public function sendSms()
    {
        if (isset($this->config['doctrineAuth']['siteName']) === false) {
            throw new DoctrineAuthException('siteName config key not set');
        }

        if (isset($this->config['doctrineAuth']['twoFactorCodeActiveFor']) === false) {
            throw new DoctrineAuthException('twoFactorCodeActiveFor config key not set');
        }

        /* Render sms body */
        $viewModel = new ViewModel();
        $viewModel->setTemplate('fws-doctrine-auth/sms/text-code');
        $viewModel->siteName = $this->config['doctrineAuth']['siteName'];
        $viewModel->code = $this->authContainer->code;
        $viewModel->expires = $this->config['doctrineAuth']['twoFactorCodeActiveFor'];
        $smsBody = $this->phpRenderer->render($viewModel);

        $pregReplace = new PregReplace([
            'pattern' => '/\\n/',
            'replacement' => ' ',
        ]);
        $filterChain = new FilterChain();
        $filterChain->attach(new StripTags())
                ->attach($pregReplace);
        $message = [
            'to' => $this->authContainer->identity->getMobileNumber(),
            'from' => $this->config['doctrineAuth']['siteName'],
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
        $client->setAuth($this->config['doctrineAuth']['bulkSmsApiTokenId'], $this->config['doctrineAuth']['bulkSmsApiTokenSecret'], Client::AUTH_BASIC);
        $client->setMethod('POST');
        $client->setRawBody(Json::encode($message));

        try {
            $response = $client->send();
        } catch (Exception $exception) {
            return false;
        }

        if ($response->getStatusCode() === self::BULKSMS_API_SUCCESS_STATUS_CODE) {
            $this->authContainer->codeSentAttempts++;
            return true;
        }
        return false;
    }

}
