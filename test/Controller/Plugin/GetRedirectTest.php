<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\Plugin;

use FwsDoctrineAuth\Controller\Plugin\GetRedirect;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\UserRole;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use FwsDoctrineAuthTest\Controller\TestAsset\SampleController;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Literal as LiteralRoute;
use Laminas\Router\SimpleRouteStack;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetRedirect::class)]
class GetRedirectTest extends TestCase
{
    protected Acl $aclMock;
    protected AuthUserInterface $identity;
    protected SampleController $controller;
    protected GetRedirect $getRedirect;
    protected AuthContainerStorage $authStorageContainer;
    protected array $defaultRoute;

    public function setUp(): void
    {
        parent::setUp();

        $this->aclMock = $this->getMockBuilder(Acl::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getRedirect',
                'isAllowed',
            ])
            ->getMock();

        $this->response = new Response();

        $this->defaultRoute = [
            'route'   => 'default-route',
            'params'  => ['test-param' => 'test-value'],
            'options' => ['test-option' => 'test-option-value'],
        ];

        $router = new SimpleRouteStack();
        $router->addRoute($this->defaultRoute['route'], LiteralRoute::factory([
            'route'    => '/' . $this->defaultRoute['route'],
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $this->router = $router;

        $event = new MvcEvent();
        $event->setRouter($router);
        $event->setResponse($this->response);
        $this->event = $event;

        $this->identity = new BaseUser();
        $this->identity->setEmailAddress('test@example.com');

        $role = new UserRole();
        $role->setRole('Test Role');
        $this->identity->setUserRole($role);

        $this->authStorageContainer = new AuthContainerStorage();

        $this->controller = new SampleController();
        $this->controller->setEvent($event);
        $this->controller->getPluginManager()->setService('getRedirect', new GetRedirect($this->aclMock, $this->authStorageContainer));

        $this->redirectPlugin = $this->controller->plugin('redirect');
        $this->getRedirect    = $this->controller->plugin('getRedirect');
    }

    /**
     * Test Get Redirect Controller plugin, redirect not set in session storage container, no default route
     *
     * @group controller-plugins
     * @group get-redirect-plugin
     * @return void
     */
    public function testGetRedirectRedirectNotSetNoDefault()
    {
        unset($this->authStorageContainer->redirect);
        $this->aclMock->expects($this->once())->method('getRedirect')->with($this->identity->getUserRole()->getRole())->willReturn([]);

        $this->aclMock->expects($this->never())->method('isAllowed');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('Unable to redirect, nowhere to go!');
        $this->controller->getRedirect($this->identity);
    }

    /**
     * Test Get Redirect Controller plugin, redirect not set in session storage container, get default route
     *
     * @group controller-plugins
     * @group get-redirect-plugin
     * @return void
     * @throws DoctrineAuthException
     */
    public function testGetRedirectRedirectNotSetGetDefault()
    {
        unset($this->authStorageContainer->redirect);
        $getRedirectPlugin = $this->getRedirect;

        $this->aclMock->expects($this->once())->method('getRedirect')->with($this->identity->getUserRole()->getRole())->willReturn($this->defaultRoute);

        $this->aclMock->expects($this->never())->method('isAllowed');

        $response = $this->controller->getRedirect($this->identity);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/' . $this->defaultRoute['route'], $location->getFieldValue());
    }

    /**
     * Test Get Redirect Controller plugin, user cannot redirect
     *
     * @group controller-plugins
     * @group get-redirect-plugin
     * @return void
     * @throws DoctrineAuthException
     */
    public function testGetRedirectNotAllowed()
    {
        $redirectController                   = SampleController::class;
        $redirectAction                       = 'test';
        $this->authStorageContainer->redirect = [
            'controller' => $redirectController,
            'action'     => $redirectAction,
        ];
        $this->aclMock->expects($this->once())->method('getRedirect')->with($this->identity->getUserRole()->getRole())->willReturn($this->defaultRoute);
        $this->aclMock->expects($this->once())->method('isAllowed')->with($this->identity->getUserRole()->getRole(), $redirectController, $redirectAction)->willReturn(false);

        $response = $this->controller->getRedirect($this->identity);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals($this->router->getRoute($this->defaultRoute['route'])->assemble(), $location->getFieldValue());
    }

    /**
     * Test Get Redirect Controller plugin, user can redirect
     *
     * @group controller-plugins
     * @group get-redirect-plugin
     * @return void
     * @throws DoctrineAuthException
     */
    public function testGetRedirectAllowed()
    {
        $redirectArray                        = [
            'controller' => SampleController::class,
            'action'     => 'test',
            'url'        => 'https://www.example.com',
        ];
        $this->authStorageContainer->redirect = $redirectArray;
        $this->aclMock->expects($this->once())->method('isAllowed')->with($this->identity->getUserRole()->getRole(), $redirectArray['controller'], $redirectArray['action'])->willReturn(true);

        $this->aclMock->expects($this->never())->method('getRedirect');

        $response = $this->controller->getRedirect($this->identity);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals($redirectArray['url'], $location->getFieldValue());
        $this->assertNull($this->authStorageContainer->redirect);
    }

    /**
     * Test Get Redirect Controller plugin, user can redirect but force return default route
     *
     * @group controller-plugins
     * @group get-redirect-plugin
     * @return void
     * @throws DoctrineAuthException
     */
    public function testGetRedirectForceDefault()
    {
        $redirectArray                        = [
            'controller' => SampleController::class,
            'action'     => 'test',
            'url'        => 'https://www.example.com',
        ];
        $this->authStorageContainer->redirect = $redirectArray;
        $this->aclMock->expects($this->once())->method('getRedirect')->with($this->identity->getUserRole()->getRole())->willReturn($this->defaultRoute);
        $this->aclMock->expects($this->once())->method('isAllowed')->with($this->identity->getUserRole()->getRole(), $redirectArray['controller'], $redirectArray['action'])->willReturn(true);

        $response = $this->controller->getRedirect($this->identity, true);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals($this->router->getRoute($this->defaultRoute['route'])->assemble(), $location->getFieldValue());
        $this->assertIsArray($this->authStorageContainer->redirect);
    }
}
