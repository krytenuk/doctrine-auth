<?php

declare(strict_types=1);

use Laminas\Permissions\Acl\Acl as LaminasAcl;
use FwsDoctrineAuth\Entity\BaseUser;
use Laminas\Session\Validator as SessionValidator;
use Laminas\Session\Storage\SessionArrayStorage;
use FwsDoctrineAuth\Form;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter;
use Laminas\Stdlib\ArrayUtils;

return array_merge(
    include __DIR__ . '/../config/module.config.php',
    include __DIR__ . '/../config/doctrine.auth.config.local.php.dist',
    include __DIR__ . '/../config/doctrine.auth.acl.local.php.dist'
);