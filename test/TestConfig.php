<?php

declare(strict_types=1);

use FwsDoctrineAuth\Module;

$moduleConfig = (new Module())->getConfig();
return array_merge(
    include $moduleConfig,
    include __DIR__ . '/../config/doctrine.auth.config.local.php.dist',
    include __DIR__ . '/../config/doctrine.auth.acl.local.php.dist',
    [
        'modules'                 => [
            'Laminas\Router',
            'Laminas\Form',
        ],
        'module_listener_options' => [],
    ]
);
