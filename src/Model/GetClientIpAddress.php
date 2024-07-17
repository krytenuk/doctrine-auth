<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use Laminas\Http\PhpEnvironment\RemoteAddress;

use function filter_var;

use const FILTER_VALIDATE_IP;

class GetClientIpAddress
{
    private RemoteAddress $remoteAddress;

    public function __construct(private readonly array $config)
    {
        $this->remoteAddress = new RemoteAddress();
        $proxyHeader         = (string) ($this->config['doctrineAuth']['proxyHeader'] ?? '');

        $this->remoteAddress->setUseProxy((bool) ($this->config['doctrineAuth']['useHttpProxy'] ?? $this->remoteAddress->getUseProxy()));
        if ($proxyHeader) {
            $this->remoteAddress->setProxyHeader($proxyHeader);
        }
    }

    /**
     * Retrieve and filter the clients IP address
     */
    public function getClientIP(): string|null
    {
        $ipAddress = $this->remoteAddress->getIpAddress();
        if (! $ipAddress) {
            return null;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP) !== false) {
            return $ipAddress;
        }

        return null;
    }
}
