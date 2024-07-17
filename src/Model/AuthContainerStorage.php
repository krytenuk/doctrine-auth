<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use DateTimeInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;
use Laminas\Session\Container;

/**
 * Stores values in the laminas session container
 * As this class extends the Laminas session container you can store and retrieve custom values by using the magic __get method in your custom adaptors
 * $authContainer->yourValueKey = $yourValue;
 * $yourValue = $authContainer->yourValueKey;
 *
 * @see Container
 * @see AbstractAdapter
 */
class AuthContainerStorage extends Container
{
    /**
     * Retrieve user attempting to authenticate
     */
    public function getIdentity(): ?AuthUserInterface
    {
        return $this->offsetGet('identity');
    }

    /**
     * Store user attempting to authenticate
     */
    public function setIdentity(?AuthUserInterface $identity): AuthContainerStorage
    {
        $this->offsetSet('identity', $identity);
        return $this;
    }

    /**
     * Store the DateTime the 2FA code was sent
     */
    public function getCodeSent(): ?DateTimeInterface
    {
        return $this->offsetGet('codeSent');
    }

    /**
     * Store the DateTime the code was sent
     */
    public function setCodeSent(?DateTimeInterface $codeSent): AuthContainerStorage
    {
        $this->offsetSet('codeSent', $codeSent);
        return $this;
    }

    /**
     * Retrieve the number of times the 2FA code has been sent
     */
    public function getCodeSentAttempts(): int
    {
        return (int) $this->offsetGet('codeSentAttempts');
    }

    /**
     * Clear the number of times the 2FA code has been sent
     */
    public function clearCodeSentAttempts(): void
    {
        $this->offsetSet('codeSentAttempts', 0);
    }

    /**
     * Add 1 to the number of times the 2FA code has been sent
     */
    public function increaseCodeSentAttempts(): AuthContainerStorage
    {
        $codeSentAttempts = (int) $this->offsetGet('codeSentAttempts');
        $this->offsetSet('codeSentAttempts', $codeSentAttempts++);
        return $this;
    }

    /**
     * Store the selected 2FA method
     *
     * @return $this
     */
    public function setAuthSelectedMethod(?string $authMethod): AuthContainerStorage
    {
        $this->offsetSet('authMethod', $authMethod);
        return $this;
    }

    /**
     * Retrieve the selected 2FA auth method
     */
    public function getSelectedAuthMethod(): ?string
    {
        return $this->offsetGet('authMethod');
    }

    /**
     * Store the 2FA code
     *
     * @param mixed $code
     * @return $this
     */
    public function setCode(string|int $code): AuthContainerStorage
    {
        $this->offsetSet('code', (string) $code);
        return $this;
    }

    /**
     * Retrieve the 2FA code
     */
    public function getCode(): ?string
    {
        return $this->offsetGet('code');
    }

    public function clear(): void
    {
        $this->offsetSet('codeSent', null);
        $this->offsetSet('codeSentAttempts', 0);
        $this->offsetSet('identity', null);
    }
}
