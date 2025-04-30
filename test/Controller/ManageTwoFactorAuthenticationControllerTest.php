<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller;

use FwsDoctrineAuth\Controller\ManageTwoFactorAuthenticationController;
use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use Laminas\Http\Header\Location;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

use function sprintf;
use function uniqid;

#[CoversClass(ManageTwoFactorAuthenticationController::class)]
class ManageTwoFactorAuthenticationControllerTest extends AbstractHttpControllerTestCase
{
    private ManageTwoFactorAuthenticationModel|MockObject $manageTwoFactorAuthenticationModelMock;

    private ValidateHash|MockObject $validateHash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manageTwoFactorAuthenticationModelMock = $this->getMockBuilder(ManageTwoFactorAuthenticationModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getAllowedAuthenticationMethods',
                'getUser',
                'addMethod',
                'getMethodTitle',
                'removeMethod',
            ])
            ->getMock();

        $this->controller = new ManageTwoFactorAuthenticationController($this->manageTwoFactorAuthenticationModelMock);
        $this->controller->setEvent($this->event);
        $this->controller->setEventManager($this->events);

        $this->mockControllerPlugins();
    }

    protected function mockControllerPlugins(): void
    {
        $this->validateHash = $this
            ->getMockBuilder(ValidateHash::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                '__invoke',
                'getHash',
                'isValid',
            ])
            ->getMock();

        $this->controller
            ->getPluginManager()
            ->setService('flashMessenger', new FlashMessenger());

        $this->controller
            ->getPluginManager()
            ->setService('validateHash', $this->validateHash);
    }

    /**
     * Create dummy 2FA adaptor
     */
    protected function getAdaptor(): AbstractAdapter
    {
        return new class extends AbstractAdapter
        {
            public function sendCode(): bool
            {
                return false;
            }

            public function setErrors(array $errors): void
            {
                $this->errors = $errors;
            }
        };
    }

    /**
     * Test list 2FA methods
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testListMethodsAction()
    {
        $this->routeMatch->setParam('action', 'list-methods');

        $allowedAuthMethods = ['TestAdaptor' => $this->getAdaptor()];
        $testHash           = uniqid();
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('getAllowedAuthenticationMethods')->willReturn($allowedAuthMethods);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('getUser')->willReturn($this->identity);
        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('getHash')->willReturn($testHash);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertIsArray($view->allowedMethods);
        $this->assertArrayHasKey('TestAdaptor', $view->allowedMethods);
        $this->assertInstanceOf(AbstractAdapter::class, $view->allowedMethods['TestAdaptor']);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($testHash, $view->hash);
    }

    protected function initAddMethodAction(): void
    {
        $this->routeMatch->setParam('action', 'add-method');
    }

    /**
     * Add 2FA method, method not found
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testAddMethodActionMethodNotSpecified()
    {
        $this->initAddMethodAction();

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('__invoke');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
    }

    /**
     * Add 2FA method, hash not sent
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testAddMethodActionNoHash()
    {
        $this->initAddMethodAction();
        $this->routeMatch->setParam('method', 'test-method');
        $errorMessage = 'The request could not be validated, please try again';

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with(null)->willReturn(false);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentErrorMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($errorMessage, $messages[0]);
    }

    /**
     * Add 2FA method, invalid hash
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testAddMethodActionInvalidHash()
    {
        $this->initAddMethodAction();
        $badHash = 'bad-hash';
        $this->routeMatch->setParam('method', 'test-method');
        $this->routeMatch->setParam('hash', $badHash);
        $errorMessage = 'The request could not be validated, please try again';

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($badHash)->willReturn(false);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentErrorMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($errorMessage, $messages[0]);
    }

    /**
     * Add 2FA method, method added
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testAddMethodActionSuccess()
    {
        $this->initAddMethodAction();
        $testHash       = 'test-hash';
        $method         = 'test-method';
        $methodTitle    = 'Test Method';
        $successMessage = sprintf('The %s authentication method has been added', $methodTitle);
        $this->routeMatch->setParam('method', $method);
        $this->routeMatch->setParam('hash', $testHash);

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($testHash)->willReturn(true);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('addMethod')->with($method)->willReturn(true);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('getMethodTitle')->willReturn($methodTitle);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentSuccessMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($successMessage, $messages[0]);
    }

    /**
     * Add 2FA method, failed to add method
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testAddMethodActionFails()
    {
        $this->initAddMethodAction();
        $testHash       = 'test-hash';
        $method         = 'test-method';
        $successMessage = 'Unable to add authentication method';
        $this->routeMatch->setParam('method', $method);
        $this->routeMatch->setParam('hash', $testHash);

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($testHash)->willReturn(true);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('addMethod')->with($method)->willReturn(false);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentErrorMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($successMessage, $messages[0]);
    }

    protected function initRemoveMethodAction(): void
    {
        $this->routeMatch->setParam('action', 'remove-method');
    }

    /**
     * Remove 2FA method, method not sent
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testRemoveMethodActionMethodNotSent()
    {
        $this->initRemoveMethodAction();

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('__invoke');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
    }

    /**
     * Add 2FA method, hash not sent
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testRemoveMethodActionNoHash()
    {
        $this->initRemoveMethodAction();
        $this->routeMatch->setParam('method', 'test-method');
        $errorMessage = 'The request could not be validated, please try again';

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with('')->willReturn(false);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentErrorMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($errorMessage, $messages[0]);
    }

    /**
     * Remove 2FA method, invalid hash
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testRemoveMethodActionInvalidHash()
    {
        $this->initRemoveMethodAction();
        $badHash = 'bad-hash';
        $this->routeMatch->setParam('method', 'test-method');
        $this->routeMatch->setParam('hash', $badHash);
        $errorMessage = 'The request could not be validated, please try again';

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($badHash)->willReturn(false);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('removeMethod');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentErrorMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($errorMessage, $messages[0]);
    }

    /**
     * Add 2FA method, method added
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testRemoveMethodActionSuccess()
    {
        $this->initRemoveMethodAction();
        $testHash       = 'test-hash';
        $method         = 'test-method';
        $methodTitle    = 'Test Method';
        $successMessage = sprintf('The %s authentication method has been removed', $methodTitle);
        $this->routeMatch->setParam('method', $method);
        $this->routeMatch->setParam('hash', $testHash);

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($testHash)->willReturn(true);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('removeMethod')->with($method)->willReturn(true);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('getMethodTitle')->willReturn($methodTitle);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentSuccessMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($successMessage, $messages[0]);
    }

    /**
     * Add 2FA method, failed to add method
     *
     * @group manage-two-factor-authentication
     * @return void
     */
    public function testRemoveMethodActionFails()
    {
        $this->initRemoveMethodAction();
        $testHash       = 'test-hash';
        $method         = 'test-method';
        $successMessage = 'Unable to remove authentication method';
        $this->routeMatch->setParam('method', $method);
        $this->routeMatch->setParam('hash', $testHash);

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($testHash)->willReturn(true);
        $this->manageTwoFactorAuthenticationModelMock->expects($this->once())->method('removeMethod')->with($method)->willReturn(false);

        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getAllowedAuthenticationMethods');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getUser');
        $this->validateHash->expects($this->never())->method('getHash');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('getMethodTitle');
        $this->manageTwoFactorAuthenticationModelMock->expects($this->never())->method('addMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
        $messages = $this->controller->flashMessenger()->getCurrentErrorMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($successMessage, $messages[0]);
    }
}
