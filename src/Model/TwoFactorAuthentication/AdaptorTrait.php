<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;

trait AdaptorTrait
{
    /**
     * @var string[]
     */
    private array $allowedMethods = [];

    /**
     * @var string[]
     */
    private array $allowedAdaptors = [];
    private ?AbstractAdapter $adapter = null;

    /**
     * Initialize 2FA adaptors
     * @return void
     * @throws DoctrineAuthException
     */
    public function initAdaptors(): void
    {
        if (!$this->allowedMethods) {
            $this->allowedAdaptors = (array)$this->config['doctrineAuth']['allowedTwoFactorAuthenticationAdaptors'] ?? [];
            if (!$this->allowedAdaptors) {
                throw new DoctrineAuthException('allowedTwoFactorAuthenticationAdaptors config key not set');
            }
            foreach ($this->allowedAdaptors as $adaptor) {
                $this->allowedMethods[$adaptor::getName()] = $adaptor;
            }
        }
    }

    /**
     * @param string $adaptorClass
     * @return void
     * @throws DoctrineAuthException
     */
    public function setAdaptor(string $adaptorClass): void
    {
        if (array_key_exists($adaptorClass, $this->allowedAdaptors)) {
            throw new DoctrineAuthException('%s not a valid 2FA adaptor', $adaptorClass);
        }

        $this->adapter = $this->adaptorPluginManager->get($adaptorClass);
        if (!$this->adapter) {
            throw new DoctrineAuthException(sprintf('Unable to get 2FA adaptor %s from the adaptor plugin manager', $adaptorClass));
        }

        $this->adapter->setConfig($this->config);
        $this->adapter->setAuthContainerStorage($this->authContainerStorage);
    }

    /**
     * Get the 2FA adaptor
     * @throws DoctrineAuthException
     */
    public function getAdaptor(?string $adaptorClass = null): ?AbstractAdapter
    {
        if ($this->adapter) {
            return $this->adapter;
        }

        if (!$adaptorClass) {
            if (!(property_exists($this, 'authContainerStorage') && $this->authContainerStorage instanceof AuthContainerStorage)) {
                throw new DoctrineAuthException('Property authContainerStorage doe not exist or is not an instance of AuthContainerStorage::class');
            }

            $authMethod = $this->authContainerStorage->getSelectedAuthMethod();
            $adaptorClass = $this->allowedMethods[$authMethod] ?? null;
            if (!$adaptorClass) {
                throw new DoctrineAuthException(sprintf('Adaptor for 2FA method %s not found', $authMethod));
            }
        }

        $this->setAdaptor($adaptorClass);

        return $this->adapter;
    }
}