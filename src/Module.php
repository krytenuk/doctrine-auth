<?php

namespace FwsDoctrineAuth;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\EventManager\LazyListenerAggregate;
use Zend\ModuleManager\ModuleManagerInterface;
use FwsDoctrineAuth\Command\InitCommand;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use FwsDoctrineAuth\Model\HashPassword;

class Module implements BootstrapListenerInterface
{

    public function onBootstrap(EventInterface $event)
    {
        $eventManager = $event->getApplication()->getEventManager();
        $serviceManager = $event->getApplication()->getServiceManager();

        $config = $serviceManager->get('config');

        $aggregate = new LazyListenerAggregate(
                $config['event_manager']['lazy_listeners'], $serviceManager
        );
        $aggregate->attach($eventManager);
        
        HashPassword::setConfig($config);
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Zend\Authentication\AuthenticationService' => function($serviceManager) {
                    return $serviceManager->get('doctrine.authenticationservice.orm_default');
                },
            )
        );
    }

    public function init(ModuleManagerInterface $e)
    {
        $events = $e->getEventManager()->getSharedManager();
        // Attach to helper set event and load the entity manager helper.
        $events->attach('doctrine', 'loadCli.post', function (EventInterface $event) {
            /* @var $cli \Symfony\Component\Console\Application */
            $cli = $event->getTarget();
            /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
            $entityManager = $cli->getHelperSet()->get('em')->getEntityManager();
            $config = $event->getParam('ServiceManager')->get('config');
            $initCommand = new InitCommand($entityManager, $config);
            ConsoleRunner::addCommands($cli);
            $cli->addCommands(array(
                $initCommand
            ));
        });
    }

}
