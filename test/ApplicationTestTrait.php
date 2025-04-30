<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest;

use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ApplicationTestTrait
{
    protected null|ApplicationInterface $application;

    /**
     * Initialize MVC application
     */
    public function getApplication(): ApplicationInterface
    {
        if ($this->application) {
            return $this->application;
        }

        $this->application = Application::init(
            $this->getConfig()
        );

        return $this->application;
    }

    /**
     * Get the service manager
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getApplicationServiceLocator(): ServiceLocatorInterface
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $this->getConfig());
        $serviceManager->get('FormElementManager')->configure($this->getConfig()['form_elements']);
        return $serviceManager;
    }

    public function getConfig(): array
    {
        return include __DIR__ . '/TestConfig.php';
    }
}
