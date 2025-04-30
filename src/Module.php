<?php

declare(strict_types=1);

namespace FwsDoctrineAuth;

use FwsDoctrineAuth\Model\HashPassword;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Model\RegisterModel;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\LazyListenerAggregate;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\ModuleManagerInterface;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Symfony\Component\Console\Application;

class Module implements BootstrapListenerInterface
{
    protected ConfigProvider $configProvider;
    public function __construct()
    {
        $this->configProvider = new ConfigProvider();
    }


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

    public function getConfig(): array
    {
        return [
            'service_manager' => $this->configProvider->getDependenciesConfig(),
            'event_manager' => $this->configProvider->getEventManagerConfig(),
            'form_elements' => $this->configProvider->getFormElementConfig(),
            'controller_plugins' => $this->configProvider->getControllerPluginsConfig(),
            'view_manager' => $this->configProvider->getViewManagerConfig(),
            'doctrine' => $this->configProvider->getDoctrineConfig(),
            'view_helpers' => $this->configProvider->getViewHelpersConfig(),
            'controllers' => $this->configProvider->getMvcControllersConfig(),
            'router' => $this->configProvider->getLaminasRouterConfig(),
            ConfigAbstractFactory::class => $this->configProvider->getConfigAbstractFactoryConfig(),
        ];
    }

    protected function getMappingsConfig(): array
    {

    }

    /**
     * Add doctrine cli commands
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
