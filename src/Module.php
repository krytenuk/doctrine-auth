<?php

declare(strict_types=1);

namespace FwsDoctrineAuth;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Command;
use FwsDoctrineAuth\Model\HashPassword;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Model\RegisterModel;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\LazyListenerAggregate;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\ModuleManagerInterface;
use Symfony\Component\Console\Application;

class Module implements BootstrapListenerInterface
{
    public function onBootstrap(EventInterface $e): void
    {
        $eventManager   = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();

        $config = $serviceManager->get('config');

        /** Add lazy listeners from config */
        $aggregate = new LazyListenerAggregate(
            $config['event_manager']['lazy_listeners'],
            $serviceManager
        );
        $aggregate->attach($eventManager);

        HashPassword::setConfig($config);
        LoginModel::setErrorMessages();
        RegisterModel::setErrorMessages();
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Add doctrine cli command
     */
    public function init(ModuleManagerInterface $moduleManager): void
    {
        $events = $moduleManager->getEventManager();

        $events->getSharedManager()->attach('doctrine', 'loadCli.post', function (EventInterface $event) {
            /** @var Application $cli */
            $cli = $event->getTarget();
            $cli->addCommands([
                $event->getParam('ServiceManager')->get(Command\InitCommand::class),
                $event->getParam('ServiceManager')->get(Command\CreateUserCommand::class),
                $event->getParam('ServiceManager')->get(Command\UpdateUserPasswordCommand::class),
            ]);
        });
    }
}
