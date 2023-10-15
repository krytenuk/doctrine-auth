<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;
use Laminas\ServiceManager\AbstractPluginManager;

class AdaptorPluginManager extends AbstractPluginManager
{
    protected $instanceOf = AbstractAdapter::class;
}