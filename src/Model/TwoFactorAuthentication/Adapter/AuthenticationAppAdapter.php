<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter;

use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use PragmaRX\Google2FA\Google2FA;

class AuthenticationAppAdapter extends AbstractAdapter
{

    /**
     * @inheritdoc
     */
    protected static string $name = 'google-auth';

    /**
     * @inheritdoc
     */
    protected static string $title = 'Authenticator app';

    /**
     * @inheritdoc
     */
    protected static array $add2faMethodRoute = [
        'name' => 'doctrine-auth/2fa/add-app-method',
        'defaults' => [],
        'query' => [],
    ];

    /**
     * Template to render the authentication 2FA code page during the login process
     * @var string
     */
    protected string $template = 'fws-doctrine-auth/2FA-templates/app-auth-2fa';

    /**
     * Not required for app authentication
     * @inheritdoc
     */
    public function codeExpired(): bool
    {
        return false;
    }

    /**
     * Not required for app authentication
     * @inheritDoc
     */
    public function sendCode(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function authenticate(string $codeEntered): bool
    {
        $secret = $this->getSecret();
        if ($secret === null) {
            return false;
        }
        $google2Fa = new Google2FA();
        try {
            return (bool) $google2Fa->verifyKey($secret, $codeEntered);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get google auth secret
     * @return string|null
     */
    private function getSecret(): ?string
    {
        $identity = $this->authContainerStorage->getIdentity();
        if ($identity instanceof AuthUserInterface) {
            $authMethod = $identity->getAuthMethod(self::$name);
            if ($authMethod !== null) {
                return $authMethod->getSettings()['secret'] ?? null;
            }
        }
        if ($this->authContainerStorage->secret) {
            return $this->authContainerStorage->secret;
        }
        return null;
    }
}