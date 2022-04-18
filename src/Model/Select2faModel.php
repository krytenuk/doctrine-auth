<?php

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Model\AbstractModel;
use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethods;
use FwsDoctrineAuth\Entity\GoogleAuth;
use Laminas\Session\Container;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use PragmaRX\Google2FA\Google2FA;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use DateTime;

/**
 * select2faModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class Select2faModel extends AbstractModel
{

    /**
     * Doctrine ORM entity manager
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * Authenticated user
     * @var BaseUsers|null
     */
    private ?BaseUsers $identity = null;

    /**
     * Authentication service
     * @var AuthenticationService
     */
    private AuthenticationService $authenticationService;

    /**
     * 2FA method
     * @var TwoFactorAuthMethods|null
     */
    private ?TwoFactorAuthMethods $methodEntity = null;

    /**
     * Configuration array
     * @var array
     */
    private array $config;

    /**
     * Google auth app secret
     * @var GoogleAuth|null
     */
    private ?GoogleAuth $googleAuthEntity;

    /**
     * Laminas session container
     * @var Container
     */
    private Container $authContainer;

    /**
     *
     * @var TwoFactorAuthModel
     */
    private TwoFactorAuthModel $twoFactorAuthModel;
    
    /**
     * 
     * @var Google2FA
     */
    private Google2FA $google2Fa;

    /**
     * Initialize class
     * @param TwoFactorAuthModel $twoFactorAuthModel
     * @param EntityManager $entityManager
     * @param AuthenticationService $authenticationService
     * @param array $config
     */
    public function __construct(
            TwoFactorAuthModel $twoFactorAuthModel,
            EntityManager $entityManager,
            AuthenticationService $authenticationService,
            Container $authContainer,
            array $config
    )
    {
        $this->twoFactorAuthModel = $twoFactorAuthModel;
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
        $this->identity = $authenticationService->getIdentity();
        $this->config = $config;
        $this->authContainer = $authContainer;
        
        $this->google2Fa = new Google2FA();
    }
    
    /**
     * Get session container
     * @return Container
     */
    public function getAuthContainer(): Container
    {
        return $this->authContainer;
    }
    
    /**
     * Get two factor authentication model
     * @return TwoFactorAuthModel
     */
    public function getTwoFactorAuthModel(): TwoFactorAuthModel
    {
        return $this->twoFactorAuthModel;
    }

    /**
     * Get 2FA method name
     * @param string $method
     * @return string
     */
    public static function getMethodTitle(string $method): string
    {
        if (array_key_exists($method, TwoFactorAuthModel::VALIDAUTHENTICATIONMETHODS)) {
            return TwoFactorAuthModel::VALIDAUTHENTICATIONMETHODS[$method];
        }
        return '';
    }

    public function getUser()
    {
        return $this->identity;
    }

    /**
     * Add new authentication method to auth user
     * @param string $method
     * @return bool
     */
    public function addMethod(string $method): bool
    {
        if (array_key_exists($method, TwoFactorAuthModel::VALIDAUTHENTICATIONMETHODS) === false) {
            return false;
        }

        /* Check if user has method already */
        if ($this->getMethod($method) instanceof TwoFactorAuthMethods) {
            return false;
        }

        /* Create user 2FA method */
        $authMethodEntity = new TwoFactorAuthMethods();
        $authMethodEntity->setDateCreated(new DateTime())
                ->setMethod($method)
                ->setUser($this->identity);

        if ($method === TwoFactorAuthModel::GOOGLEAUTHENTICATOR) {
            if ($this->authContainer->secret instanceof GoogleAuth === false) {
                return false;
            }
            $this->authContainer->secret->setAuthMethod($authMethodEntity);
            $authMethodEntity->setGoogleAuth($this->authContainer->secret);
        }

        $this->identity->addAuthMethod($authMethodEntity);
        $this->identity->setUse2fa(true);
        $this->entityManager->persist($this->identity);
        $saved = $this->flushEntityManager($this->entityManager);
        $this->entityManager->detach($this->identity);
        $this->authenticationService->clearIdentity();
        $this->authenticationService->getStorage()->write($this->identity);
        return $saved;
    }

    /**
     * Remove authentication method from auth user
     * @param string $method
     * @return bool
     */
    public function removeMethod(string $method): bool
    {
        $methodEntity = $this->getMethod($method);
        if ($methodEntity instanceof TwoFactorAuthMethods === false) {
            return false;
        }

        $this->identity->removeAuthMethod($methodEntity);
        if ($this->identity->getAuthMethods()->isEmpty()) {
            $this->identity->setUse2fa(false);
        }
        $this->authenticationService->clearIdentity();
        $this->entityManager->persist($this->identity);
        $saved = $this->flushEntityManager($this->entityManager);
        $this->entityManager->detach($this->identity);
        $this->authenticationService->clearIdentity();
        $this->authenticationService->getStorage()->write($this->identity);
        return $saved;
    }

    /**
     * Get 2FA method from database and store
     * @param string $method
     * @return TwoFactorAuthMethods|null
     */
    public function getMethod(string $method): ?TwoFactorAuthMethods
    {
        if ($this->methodEntity === null) {
            $this->methodEntity = $this->entityManager->getRepository(TwoFactorAuthMethods::class)->findOneBy(['method' => $method, 'user' => $this->identity]);
        }

        return $this->methodEntity;
    }

    public function getAllowedMethods()
    {
        return $this->config['doctrineAuth']['allowedTwoFactorAuthenticationMethods'];
    }

    /**
     * Get Google auth secret or generate new if not set
     * @return string
     */
    public function getGoogleAuthSecret(): string
    {
        if (isset($this->authContainer->secret) && $this->authContainer->secret instanceof GoogleAuth) {
            return $this->authContainer->secret->getSecret();
        }
        
        $googleAuthEntity = new GoogleAuth();
        $googleAuthEntity->setSecret($this->google2Fa->generateSecretKey(32));
        $this->authContainer->secret = $googleAuthEntity;
        return $googleAuthEntity->getSecret();
    }

    /**
     * Get QR code image data
     * @return string
     * @throws DoctrineAuthException
     */
    public function getQrCode(): string
    {
        $this->authContainer->authMethod = $this->twoFactorAuthModel::GOOGLEAUTHENTICATOR;
        if ($this->authContainer->secret instanceof GoogleAuth === false) {
            throw new DoctrineAuthException('Secret not generated');
        }

        if (isset($this->config['doctrineAuth']['siteName']) === false) {
            throw new DoctrineAuthException('siteName config key not set');
        }
        if ($this->authContainer->identity instanceof BaseUsers === false) {
            return null;
        }
                
        $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($this->google2Fa->getQRCodeUrl($this->config['doctrineAuth']['siteName'], $this->authContainer->identity->getEmailAddress(), $this->authContainer->secret->getSecret()))
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->build();
        
        return $result->getString();
    }

}
