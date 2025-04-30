<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller;

use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase as LaminasAbstractHttpControllerTestCase;

class AbstractHttpControllerTestCase extends LaminasAbstractHttpControllerTestCase
{
    protected array $config;
    protected string $identityProperty;
    protected string $credentialProperty;

    protected AuthUserInterface $identity;
    protected Request $request;
    protected Response|null $response;
    protected RouteMatch $routeMatch;
    protected MvcEvent $event;
    protected SharedEventManager $sharedEvents;

    /**
     * @throws DoctrineAuthException
     */
    protected function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../TestConfig.php');

        $this->config             = $this->getApplicationConfig();
        $this->identityProperty   = $this->config['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        $this->credentialProperty = $this->config['doctrine']['authentication']['orm_default']['credential_property'] ?? null;
        if (! ($this->identityProperty && $this->credentialProperty)) {
            throw new DoctrineAuthException('identity_property and/or credential_property not set in test config');
        }
        $this->identity = new BaseUser();
        $this->identity->setEmailAddress('test@example.com');

        $this->request    = new Request();
        $this->response   = null;
        $this->routeMatch = new RouteMatch(['action' => 'index']);
        $routeStack       = TreeRouteStack::factory($this->config['router']);
        $this->event      = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);
        $this->event->setRouter($routeStack);

        $this->sharedEvents = new SharedEventManager();
        $this->events       = $this->createEventManager($this->sharedEvents);

        parent::setUp();
    }

    protected function createEventManager(SharedEventManagerInterface $sharedManager): EventManager
    {
        return new EventManager($sharedManager);
    }
}
