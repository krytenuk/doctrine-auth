<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter;

use DateInterval;
use DateTime;
use DateTimeInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Filter\Word\SeparatorToSeparator;

use function array_merge;
use function mt_rand;

abstract class AbstractAdapter
{
    /** @var string Name of this adapter (saved on database table, no spaces) */
    protected static string $name;

    /** @var string Title of this adapter (used in rendering) */
    protected static string $title;

    /**
     * The user entity's properties that must not be falsy
     * For example the SMS (text) adaptor requires a mobile phone property to be non falsy in order to use it
     * This is used in the 2FA selection screen during the login process
     *
     * @var string[]
     */
    protected static array $requiredProperties = [];

    /**
     * Route array for adding 2FA method
     * Here you can add custom code to set-up your authentication method
     *
     * @var array{name: string, defaults: array, query: array}
     */
    protected static array $add2faMethodRoute = [
        'name'     => 'doctrine-auth/2fa/add-method',
        'defaults' => [],
        'query'    => [],
    ];

    /**
     * Route array for adding 2FA method endpoint
     * Here you can add custom code to remove your authentication method
     *
     * @var array{name: string, defaults: array, query: array}
     */
    protected static array $remove2faMethodRoute = [
        'name'     => 'doctrine-auth/2fa/remove-method',
        'defaults' => [],
        'query'    => [],
    ];

    /**
     * Template to render the authentication 2FA code page during the login process
     */
    protected string $template;

    /**
     * Authentication session container
     */
    protected AuthContainerStorage $authContainerStorage;

    /**
     * Laminas config
     *
     * @var array
     */
    protected array $config;

    protected ?int $codeActiveFor = null;

    protected array $errors = [];

    /**
     * This is required in your adaptor class
     * Sends the 2FA code to the user
     * Send the 2FA code
     */
    abstract public function sendCode(): bool;

    public static function getName(): string
    {
        $stripSpacesFilter = new SeparatorToSeparator(' ', '');
        return $stripSpacesFilter->filter(static::$name);
    }

    public static function getTitle(): string
    {
        return static::$title;
    }

    /**
     * Get the required properties in the user entity
     *
     * @see AuthUserInterface
     * @see BaseUser
     *
     * @return string[]
     */
    public static function getRequiredProperties(): array
    {
        return static::$requiredProperties;
    }

    /**
     * Get the add 2FA method endpoint route
     *
     * @return array{name: string, defaults: array, query: array}
     */
    public static function getAdd2FaMethodRoute(): array
    {
        $route             = static::$add2faMethodRoute;
        $route['defaults'] = array_merge($route['defaults'], [
            'method' => static::getName(),
        ]);

        return $route;
    }

    /**
     * Get the remove 2FA method endpoint route
     *
     * @return array{name: string, defaults: array, query: array}
     */
    public static function getRemove2faMethodRoute(): array
    {
        $route             = static::$remove2faMethodRoute;
        $route['defaults'] = array_merge($route['defaults'], [
            'method' => static::getName(),
        ]);

        return $route;
    }

    /**
     * Add CSRF hash value to set/remove 2fA routes
     */
    public static function setHash(string $hash): void
    {
        static::$add2faMethodRoute['defaults'] = array_merge(static::$add2faMethodRoute['defaults'], [
            'hash' => $hash,
        ]);

        static::$remove2faMethodRoute['defaults'] = array_merge(static::$remove2faMethodRoute['defaults'], [
            'hash' => $hash,
        ]);
    }

    /**
     * Set laminas config
     *
     * @param array $config
     */
    public function setConfig(array $config): AbstractAdapter
    {
        $this->config = $config;
        return $this;
    }

    public function setAuthContainerStorage(AuthContainerStorage $authContainerStorage): AbstractAdapter
    {
        $this->authContainerStorage = $authContainerStorage;
        return $this;
    }

    /**
     * Get the website name
     * Set in config @see config/autoload/doctrine.auth.config.local.php
     *
     * @throws DoctrineAuthException
     */
    public function getSiteName(): string
    {
        $siteName = (string) $this->config['doctrineAuth']['siteName'];
        if (! $siteName) {
            throw new DoctrineAuthException('siteName config key not set');
        }

        return $siteName;
    }

    /**
     * Generate 6 digit 2FA code
     */
    public function generateCode(): AbstractAdapter
    {
        $this->authContainerStorage->setCode(mt_rand(100000, 999999));
        return $this;
    }

    /**
     * Has code been sent?
     */
    public function codeSent(): bool
    {
        return (bool) $this->authContainerStorage->getCodeSent();
    }

    /**
     * Check if the generated code has expired
     *
     * @throws DoctrineAuthException
     */
    public function codeExpired(): bool
    {
        if (! $this->codeSent()) {
            throw new DoctrineAuthException('The 2FA code has not been sent');
        }

        $codeSentDate = $this->authContainerStorage->getCodeSent();
        if (! $codeSentDate instanceof DateTimeInterface) {
            throw new DoctrineAuthException('The 2FA code sent date is not an instance of DateTimeInterface');
        }

        $currentDateTime = new DateTime('now');
        $expires         = $codeSentDate->add(new DateInterval("PT{$this->getCodeActiveFor()}M"));
        return $currentDateTime > $expires;
    }

    public function getCodeActiveFor(): ?int
    {
        if ($this->codeActiveFor) {
            return $this->codeActiveFor;
        }

        $this->codeActiveFor = (int) $this->config['doctrineAuth']['twoFactorCodeActiveFor'] ?? null;
        return $this->codeActiveFor;
    }

    /**
     * Get the template (view script) to render on the authentication 2FA code page during the login process
     */
    public function getAuthenticateTemplate(): string
    {
        return $this->template;
    }

    /**
     * Compare given code against generated code
     */
    public function authenticate(string $codeEntered): bool
    {
        return $codeEntered === $this->authContainerStorage->getCode();
    }

    /**
     * Log an error message
     */
    protected function error(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Fetch error messages
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Has error messages
     */
    public function hasErrors(): bool
    {
        return (bool) $this->getErrors();
    }
}
