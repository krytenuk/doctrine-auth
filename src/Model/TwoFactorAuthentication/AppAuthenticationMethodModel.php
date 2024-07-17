<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\TwoFactorAuthenticationCodeForm;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;
use PragmaRX\Google2FA\Google2FA;

class AppAuthenticationMethodModel
{
    private Google2FA $google2FA;
    private AuthContainerStorage $authContainerStorage;

    /**
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected ManageTwoFactorAuthenticationModel $selectTwoFactorAuthenticationModel,
        private TwoFactorAuthenticationModel $twoFactorAuthenticationModel,
        private array $config
    ) {
        $this->google2FA = new Google2FA();
        $this->twoFactorAuthenticationModel->setAdaptor(AuthenticationAppAdapter::getName());
        $this->authContainerStorage = $this->twoFactorAuthenticationModel->getAuthContainerStorage();
    }

    public function getTwoFactorAuthenticationModel(): TwoFactorAuthenticationModel
    {
        return $this->twoFactorAuthenticationModel;
    }

    /**
     * Add authentication method
     *
     * @throws DoctrineAuthException
     */
    public function addMethod(): bool
    {
        return $this->selectTwoFactorAuthenticationModel->addMethod(AuthenticationAppAdapter::getName(), ['secret' => $this->authContainerStorage->secret]);
    }

    /**
     * Get Google auth secret or generate new if not set
     *
     * @param bool $regenerate Create new secret
     */
    public function getSecret(bool $regenerate = false): ?string
    {
        if ($this->authContainerStorage->secret && ! $regenerate) {
            return $this->authContainerStorage->secret;
        }

        try {
            $this->authContainerStorage->secret = $this->google2FA->generateSecretKey(32);
        } catch (Exception) {
            return null;
        }

        return $this->authContainerStorage->secret;
    }

    public function getAuthCodeForm(): TwoFactorAuthenticationCodeForm
    {
        return $this->twoFactorAuthenticationModel->getAuthCodeForm();
    }

    /**
     * Get QR code image data
     *
     * @throws DoctrineAuthException
     */
    public function getQrCode(): ?string
    {
        $siteName = $this->config['doctrineAuth']['siteName'] ?? null;
        if (! $siteName) {
            throw new DoctrineAuthException('siteName config key not set');
        }

        $this->authContainerStorage->setAuthSelectedMethod(AuthenticationAppAdapter::getName());
        $secret = $this->getSecret();
        if (! $secret) {
            return null;
        }

        $identity = $this->twoFactorAuthenticationModel->getAuthService()->getIdentity();
        if (! $identity instanceof AuthUserInterface) {
            return null;
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($this->google2FA->getQRCodeUrl($siteName, $identity->getEmailAddress(), $this->getSecret()))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return $result->getString();
    }
}
