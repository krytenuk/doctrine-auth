<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;
use Laminas\ServiceManager\AbstractPluginManager;

class AdaptorPluginManager extends AbstractPluginManager
{
    protected $instanceOf = AbstractAdapter::class;
}
