<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller;

use FwsDoctrineAuth\Controller\AppAuthenticationController;
use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Form\TwoFactorAuthenticationCodeForm;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AppAuthenticationMethodModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Form\Element\Text;
use Laminas\Http\Header\Location;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

use function _;

#[CoversClass(AppAuthenticationController::class)]
class AppAuthenticationControllerTest extends AbstractHttpControllerTestCase
{
    protected AppAuthenticationMethodModel|MockObject $appAuthenticationMethodModelMock;
    protected TwoFactorAuthenticationModel|MockObject $twoFactorAuthModelMock;
    protected ValidateHash|MockObject $validateHash;
    protected string $qrCode;
    protected string $testHash;
    private TwoFactorAuthenticationCodeForm|MockObject $twoFactorAuthCodeFormMock;
    protected Text $textElement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appAuthenticationMethodModelMock = $this->getMockBuilder(AppAuthenticationMethodModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getAuthCodeForm',
                'getQrCode',
                'getTwoFactorAuthenticationModel',
                'addMethod',
                'getSecret',
            ])
            ->getMock();

        $this->twoFactorAuthModelMock = $this->getMockBuilder(TwoFactorAuthenticationModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'processAuthForm',
                'authenticate',
            ])
            ->getMock();

        $this->textElement = new Text();

        $this->controller = new AppAuthenticationController($this->appAuthenticationMethodModelMock);
        $this->controller->setEvent($this->event);
        $this->controller->setEventManager($this->events);

        $this->mockControllerPlugins();
    }

    protected function initAddMethodAction(): void
    {
        $this->routeMatch->setParam('action', 'add-method');
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

    protected function initAddAppAuthenticationMethodAction(): void
    {
        $this->routeMatch->setParam('action', 'add-app-authentication-method');

        $this->twoFactorAuthCodeFormMock = $this->getMockBuilder(TwoFactorAuthenticationCodeForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get',
            ])
            ->getMock();

        $this->qrCode   = 'test-qr-code';
        $this->testHash = 'test-hash';

        $this->appAuthenticationMethodModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthCodeFormMock);
        $this->appAuthenticationMethodModelMock->expects($this->once())->method('getQrCode')->willReturn($this->qrCode);
        $this->validateHash->expects($this->once())->method('getHash')->willReturn($this->testHash);
    }

    /**
     * Show add 2FA authentication app method form, no hash sent
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testAddAppAuthenticationMethodActionShowFormNoHash()
    {
        $this->initAddAppAuthenticationMethodAction();

        $this->validateHash->expects($this->exactly(2))->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with('')->willReturn(false);

        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getTwoFactorAuthenticationModel');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('addMethod');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getSecret');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');

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
     * Show add 2FA authentication app method form, invalid hash
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testAddAppAuthenticationMethodActionShowFormInvalidHash()
    {
        $this->initAddAppAuthenticationMethodAction();
        $badHash = 'bad-hash';
        $this->routeMatch->setParam('hash', $badHash);

        $this->validateHash->expects($this->exactly(2))->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($badHash)->willReturn(false);

        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getTwoFactorAuthenticationModel');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('addMethod');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getSecret');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');

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
     * Show add 2FA authentication app method form
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testAddAppAuthenticationMethodActionShowForm()
    {
        $this->initAddAppAuthenticationMethodAction();
        $this->routeMatch->setParam('hash', $this->testHash);

        $this->validateHash->expects($this->exactly(2))->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('isValid')->with($this->testHash)->willReturn(true);

        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getTwoFactorAuthenticationModel');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('addMethod');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getSecret');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');

        $this->request->setMethod(Request::METHOD_GET);
        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $this->assertEquals($this->qrCode, $view->qrCode);
        $this->assertEquals($this->testHash, $view->hash);
    }

    /**
     * Process add 2FA authentication app method form, invalid form
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testAddAppAuthenticationMethodActionAddMethodInvalidForm()
    {
        $this->initAddAppAuthenticationMethodAction();
        $this->routeMatch->setParam('hash', $this->testHash);
        $testPostData = new Parameters([
            'code' => 'bad-code',
            'csrf' => $this->testHash,
        ]);
        $this->request->setPost($testPostData);
        $errorMessage = _('There is a problem with the code you entered');

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->appAuthenticationMethodModelMock->expects($this->once())->method('getTwoFactorAuthenticationModel')->willReturn($this->twoFactorAuthModelMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($testPostData)->willReturn(false);
        $this->twoFactorAuthCodeFormMock->expects($this->once())->method('get')->with('code')->willReturn($this->textElement);

        $this->validateHash->expects($this->never())->method('isValid');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('addMethod');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getSecret');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');

        $this->request->setMethod(Request::METHOD_POST);
        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $this->assertEquals($this->qrCode, $view->qrCode);
        $this->assertEquals($this->testHash, $view->hash);
        $messages = $this->textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($messages[0], $errorMessage);
    }

    /**
     * Process add 2FA authentication app method form, invalid form
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testAddAppAuthenticationMethodActionAddMethodAuthenticateCheckFailed()
    {
        $this->initAddAppAuthenticationMethodAction();
        $this->routeMatch->setParam('hash', $this->testHash);
        $testPostData = new Parameters([
            'code' => 'good-code',
            'csrf' => $this->testHash,
        ]);
        $this->request->setPost($testPostData);
        $errorMessage = _('There is a problem with the code you entered');

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->appAuthenticationMethodModelMock->expects($this->exactly(2))->method('getTwoFactorAuthenticationModel')->willReturn($this->twoFactorAuthModelMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($testPostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('authenticate')->willReturn(false);
        $this->twoFactorAuthCodeFormMock->expects($this->once())->method('get')->with('code')->willReturn($this->textElement);

        $this->validateHash->expects($this->never())->method('isValid');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('addMethod');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getSecret');

        $this->request->setMethod(Request::METHOD_POST);
        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $this->assertEquals($this->qrCode, $view->qrCode);
        $this->assertEquals($this->testHash, $view->hash);
        $messages = $this->textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($messages[0], $errorMessage);
    }

    /**
     * Add 2FA authentication app method succeeds
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testAddAppAuthenticationMethodActionAddMethodSucceeds()
    {
        $this->initAddAppAuthenticationMethodAction();
        $this->routeMatch->setParam('hash', $this->testHash);
        $testPostData = new Parameters([
            'code' => 'good-code',
            'csrf' => $this->testHash,
        ]);
        $this->request->setPost($testPostData);
        $errorMessage = _('There is a problem with the code you entered');

        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->appAuthenticationMethodModelMock->expects($this->exactly(2))->method('getTwoFactorAuthenticationModel')->willReturn($this->twoFactorAuthModelMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($testPostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('authenticate')->willReturn(true);
        $this->appAuthenticationMethodModelMock->expects($this->once())->method('addMethod');

        $this->twoFactorAuthCodeFormMock->expects($this->never())->method('get');
        $this->validateHash->expects($this->never())->method('isValid');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getSecret');

        $this->request->setMethod(Request::METHOD_POST);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/list-methods', $location->getUri());
    }

    /**
     * Add 2FA authentication app method regenerate secret
     *
     * @group app-authentication-controller
     * @return void
     */
    public function testRegenerateAppSecretAction()
    {
        $this->routeMatch->setParam('action', 'regenerate-app-secret');
        $testHash = 'test-hash';

        $this->appAuthenticationMethodModelMock->expects($this->once())->method('getSecret')->with(true);
        $this->validateHash->expects($this->once())->method('__invoke')->willReturn($this->validateHash);
        $this->validateHash->expects($this->once())->method('getHash')->willReturn($testHash);

        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getAuthCodeForm');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getQrCode');
        $this->validateHash->expects($this->never())->method('isValid');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('getTwoFactorAuthenticationModel');
        $this->appAuthenticationMethodModelMock->expects($this->never())->method('addMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');

        $this->request->setMethod(Request::METHOD_POST);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/add-method/app/' . $testHash, $location->getUri());
    }
}
