<?php

namespace FwsDoctrineAuth\Model;

use Laminas\Http\PhpEnvironment\RemoteAddress;

class GetClientIpAddress
{
    private RemoteAddress $remoteAddress;

    public function __construct(private array $config)
    {
        $this->remoteAddress = new RemoteAddress();
        $proxyHeader = (string) ($this->config['doctrineAuth']['proxyHeader'] ?? '');

        $this->remoteAddress->setUseProxy((bool) ($this->config['doctrineAuth']['useHttpProxy'] ?? $this->remoteAddress->getUseProxy()));
        if ($proxyHeader) {
            $this->remoteAddress->setProxyHeader($proxyHeader);
        }

    }

    /**
     * Retrieve and filter the clients IP address
     *
     * @return bool|string
     */
    public function getClientIP(): bool|string
    {
        $ipAddress = $this->remoteAddress->getIpAddress();
        if (!$ipAddress) {
            return false;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP) !== false) {
            return $ipAddress;
        }

        return false;
    }


}