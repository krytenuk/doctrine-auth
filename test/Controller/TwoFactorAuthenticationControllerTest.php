<?php

namespace FwsDoctrineAuthTest\Controller;

use FwsDoctrineAuth\Controller\Plugin\BlockIP;
use FwsDoctrineAuth\Controller\Plugin\GetRedirect;
use FwsDoctrineAuth\Controller\Plugin\IsIpBlocked;
use FwsDoctrineAuth\Controller\Plugin\LogFailedAttempt;
use FwsDoctrineAuth\Controller\Plugin\LogSuccessfulLogin;
use FwsDoctrineAuth\Controller\TwoFactorAuthenticationController;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use FwsDoctrineAuth\Form\TwoFactorAuthenticationCodeForm;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Form\Element\Text;
use Laminas\Http\Header\Location;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TwoFactorAuthenticationController::class)]
class TwoFactorAuthenticationControllerTest extends AbstractHttpControllerTestCase
{
    protected TwoFactorAuthenticationModel|MockObject $twoFactorAuthModelMock;
    protected TwoFactorAuthMethod|MockObject $twoFactorAuthMethodEntityMock;

    protected SelectTwoFactorAuthMethodForm|MockObject $selectTwoFactorAuthMethodFormMock;
    private TwoFactorAuthenticationCodeForm|MockObject $twoFactorAuthenticationCodeFormMock;
    protected Text $textElement;

    protected IsIpBlocked|MockObject $isIpBlockedMock;
    private LogSuccessfulLogin|MockObject $logSuccessfulLoginMock;
    private GetRedirect|MockObject $getAuthRedirectMock;
    private LogFailedAttempt|MockObject $logFailedLoginAttemptMock;
    private BlockIP|MockObject $blockIpAddressMock;

    protected Parameters $authMethodPostData;
    protected Parameters $codePostData;

    /**
     * @return void
     * @throws DoctrineAuthException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoFactorAuthModelMock = $this->getMockBuilder(TwoFactorAuthenticationModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIdentity',
                'getSelectAuthMethodForm',
                'getSingleAuthMethod',
                'setSelectedAuthMethod',
                'processSelectForm',
                'getSelectedAuthMethod',
                'getAuthenticateTemplate',
                'getAuthCodeForm',
                'processAuthForm',
                'codeExpired',
                'codeSent',
                'generateCode',
                'sendCode',
                'getAdaptor',
                'authenticate',
                'storeIdentity',
            ])
            ->getMock();

        $this->twoFactorAuthMethodEntityMock = $this->getMockBuilder(TwoFactorAuthMethod::class)
            ->onlyMethods([
                'getMethod',
            ])
            ->getMock();

        $this->textElement = new Text('method');

        $this->controller = new TwoFactorAuthenticationController($this->twoFactorAuthModelMock);
        $this->controller->setEvent($this->event);
        $this->controller->setEventManager($this->events);

        $this->mockControllerPlugins();
    }

    protected function mockControllerPlugins(): void
    {
        $this->isIpBlockedMock = $this
            ->getMockBuilder(IsIpBlocked::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $this->logSuccessfulLoginMock = $this
            ->getMockBuilder(LogSuccessfulLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $this->getAuthRedirectMock = $this
            ->getMockBuilder(GetRedirect::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $this->logFailedLoginAttemptMock = $this
            ->getMockBuilder(LogFailedAttempt::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $this->blockIpAddressMock = $this
            ->getMockBuilder(BlockIP::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $this->controller
            ->getPluginManager()
            ->setService('isIpBlocked', $this->isIpBlockedMock);

        $this->controller
            ->getPluginManager()
            ->setService('logSuccessfulLogin', $this->logSuccessfulLoginMock);

        $this->controller
            ->getPluginManager()
            ->setService('getAuthRedirect', $this->getAuthRedirectMock);

        $this->controller
            ->getPluginManager()
            ->setService('logFailedLoginAttempt', $this->logFailedLoginAttemptMock);

        $this->controller
            ->getPluginManager()
            ->setService('blockIpAddress', $this->blockIpAddressMock);
    }

    /**
     * Initiate login action defaults
     * @return void
     */
    protected function initSelectAuthMethodAction(): void
    {
        $this->selectTwoFactorAuthMethodFormMock = $this->getMockBuilder(SelectTwoFactorAuthMethodForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get',
            ])
            ->getMock();

        $this->routeMatch->setParam('action', 'select-auth-method');

        $this->authMethodPostData = new Parameters(['method' => 'valid_2fa_method']);
    }

    /**
     * Test select 2FA method when no login identity found
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionNoIdentity(): void
    {
        $this->initSelectAuthMethodAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn(null);

        $this->twoFactorAuthModelMock->expects($this->never())->method('getSelectAuthMethodForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getSingleAuthMethod');
        $this->twoFactorAuthMethodEntityMock->expects($this->never())->method('getMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('setSelectedAuthMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processSelectForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getSelectedAuthMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Test select 2FA method when there is only one method set for user and it is not an allowed method
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionSingleAuthMethodNotAllowed(): void
    {
        $authMethod = 'invalid_2fa_method';
        $errorMessage = 'Authentication method not found';
        $this->initSelectAuthMethodAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSingleAuthMethod')->willReturn($this->twoFactorAuthMethodEntityMock);
        $this->twoFactorAuthMethodEntityMock->expects($this->once())->method('getMethod')->willReturn($authMethod);
        $this->twoFactorAuthModelMock->expects($this->once())->method('setSelectedAuthMethod')->with($authMethod)->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectAuthMethodForm')->willReturn($this->selectTwoFactorAuthMethodFormMock);
        $this->selectTwoFactorAuthMethodFormMock->expects($this->once())->method('get')->with('method')->willReturn($this->textElement);

        $this->twoFactorAuthModelMock->expects($this->never())->method('processSelectForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getSelectedAuthMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_403, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(SelectTwoFactorAuthMethodForm::class, $view->userAuthMethodsForm);
        $messages = $this->textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($messages[0], $errorMessage);
    }

    /**
     * Test select 2FA method when there is only one method set for user, and it is an allowed method
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionSingleAuthMethodIsAllowed(): void
    {
        $authMethod = 'valid_2fa_method';
        $this->initSelectAuthMethodAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectAuthMethodForm')->willReturn($this->selectTwoFactorAuthMethodFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSingleAuthMethod')->willReturn($this->twoFactorAuthMethodEntityMock);
        $this->twoFactorAuthMethodEntityMock->expects($this->once())->method('getMethod')->willReturn($authMethod);
        $this->twoFactorAuthModelMock->expects($this->once())->method('setSelectedAuthMethod')->with($authMethod)->willReturn(true);

        $this->selectTwoFactorAuthMethodFormMock->expects($this->never())->method('get');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processSelectForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getSelectedAuthMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/authenticate', $location->getUri());
    }

    /**
     * Test select 2FA method when there are multiple 2FA methods for user, get method
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionMultiAuthMethodsGetMethod(): void
    {
        $this->initSelectAuthMethodAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectAuthMethodForm')->willReturn($this->selectTwoFactorAuthMethodFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSingleAuthMethod')->willReturn(null);

        $this->twoFactorAuthMethodEntityMock->expects($this->never())->method('getMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('setSelectedAuthMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processSelectForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getSelectedAuthMethod');

        $this->request->setMethod(Request::METHOD_GET);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(SelectTwoFactorAuthMethodForm::class, $view->userAuthMethodsForm);
    }

    /**
     * Test select 2FA method when there are multiple 2FA methods for user, invalid form or 2FA method selected
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionMultiAuthMethodsSelectFailed(): void
    {
        $errorMessage = 'You must select your authentication method';
        $this->initSelectAuthMethodAction();

        $this->request->setPost($this->authMethodPostData);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectAuthMethodForm')->willReturn($this->selectTwoFactorAuthMethodFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSingleAuthMethod')->willReturn(null);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processSelectForm')->with($this->authMethodPostData)->willReturn(false);
        $this->selectTwoFactorAuthMethodFormMock->expects($this->once())->method('get')->with('method')->willReturn($this->textElement);

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_400, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(SelectTwoFactorAuthMethodForm::class, $view->userAuthMethodsForm);
        $messages = $this->textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($messages[0], $errorMessage);
    }

    /**
     * Test select 2FA method when there are multiple 2FA methods for user, 2FA method not found
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionMultiAuthMethodsSelectedNotFound(): void
    {
        $errorMessage = 'Authentication method not found';
        $this->initSelectAuthMethodAction();
        $this->request->setPost($this->authMethodPostData);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectAuthMethodForm')->willReturn($this->selectTwoFactorAuthMethodFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSingleAuthMethod')->willReturn(null);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processSelectForm')->with($this->authMethodPostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn(null);
        $this->selectTwoFactorAuthMethodFormMock->expects($this->once())->method('get')->with('method')->willReturn($this->textElement);

        $this->twoFactorAuthMethodEntityMock->expects($this->never())->method('getMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('setSelectedAuthMethod');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_400, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(SelectTwoFactorAuthMethodForm::class, $view->userAuthMethodsForm);
        $messages = $this->textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals($messages[0], $errorMessage);
    }

    /**
     * Test select 2FA method when there are multiple 2FA methods for user, 2FA method not found
     * @group two-factor-authentication
     * @return void
     */
    public function testSelectAuthMethodActionMultiAuthMethodsSuccess(): void
    {
        $authMethod = 'valid_2fa_method';
        $this->initSelectAuthMethodAction();
        $this->request->setPost($this->authMethodPostData);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectAuthMethodForm')->willReturn($this->selectTwoFactorAuthMethodFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSingleAuthMethod')->willReturn(null);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processSelectForm')->with($this->authMethodPostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);

        $this->twoFactorAuthMethodEntityMock->expects($this->never())->method('getMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('setSelectedAuthMethod');
        $this->selectTwoFactorAuthMethodFormMock->expects($this->never())->method('get');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/authenticate', $location->getUri());
    }

    /**
     * Initiate 2FA authenticate action defaults
     * @return void
     */
    protected function initAuthenticateAction(): void
    {
        $this->twoFactorAuthenticationCodeFormMock = $this->getMockBuilder(TwoFactorAuthenticationCodeForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get',
            ])
            ->getMock();

        $this->routeMatch->setParam('action', 'authenticate');

        $this->codePostData = new Parameters([
            'code' => '123456',
        ]);
    }

    /**
     * Create dummy 2FA adaptor
     *
     * @return AbstractAdapter
     */
    protected function getAdaptor(): AbstractAdapter
    {
        return new class extends AbstractAdapter {

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
     * Test authenticate action, no identity set
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionNoIdentity(): void
    {
        $this->initAuthenticateAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn(null);

        $this->twoFactorAuthModelMock->expects($this->never())->method('getAuthenticateTemplate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAuthCodeForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getSelectedAuthMethod');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeExpired');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_GET);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Test authenticate action, get method, 2FA code not sent
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionGetMethodCodeNotSent(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $authAdapterErrors = ['Adapter test error'];
        $this->initAuthenticateAction();

        $abstractTestAdapter = $this->getAdaptor();
        $abstractTestAdapter->setErrors($authAdapterErrors);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeSent')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAdaptor')->willReturn($abstractTestAdapter);
        $this->twoFactorAuthModelMock->expects($this->once())->method('sendCode')->willReturn(false);

        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeExpired');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_GET);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($view->getTemplate(), $authenticationTemplate);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
        $this->assertFalse($view->codeSent);
        $this->assertEquals($authAdapterErrors, $view->adaptorErrors);
    }

    /**
     * Test authenticate action, get method, 2FA code sent
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionGetMethodCodeSent(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $this->initAuthenticateAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeSent')->willReturn(true);

        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeExpired');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_GET);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($view->getTemplate(), $authenticationTemplate);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
        $this->assertTrue($view->codeSent);
    }

    /**
     * Test authenticate action, ip address blocked
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionIpBlocked(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $this->initAuthenticateAction();

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(true);

        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');
        $this->twoFactorAuthModelMock->expects($this->never())->method('processAuthForm');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeExpired');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $messages = $textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('Sorry your IP address is blocked', $messages[0]);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
    }

    /**
     * Test authenticate action, process code fails
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionProcessCodeFormFails(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(false);

        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeExpired');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_400, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $messages = $textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('There is a problem with the code you entered', $messages[0]);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
    }

    /**
     * Test authenticate action, code expired and send new code succeeds
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionCodeExpiredSendNewCodeSucceeds(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $authAdapterErrors = ['Adapter test error'];
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeExpired')->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->once())->method('sendCode')->willReturn(true);

        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $messages = $textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('Your code has expired, a new code has been sent', $messages[0]);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
        $this->assertTrue($view->codeSent);
    }

    /**
     * Test authenticate action, code expired and send new code succeeds
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionCodeExpiredSendNewCodeFails(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $authAdapterErrors = ['Adapter test error'];
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $abstractTestAdapter = $this->getAdaptor();
        $abstractTestAdapter->setErrors($authAdapterErrors);

        $this->twoFactorAuthModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeExpired')->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->once())->method('sendCode')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAdaptor')->willReturn($abstractTestAdapter);

        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('authenticate');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_500, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
        $this->assertFalse($view->codeSent);
        $this->assertIsArray($view->adaptorErrors);
        $this->assertEquals($authAdapterErrors, $view->adaptorErrors);
    }

    /**
     * Test authenticate action, 2fa succeeds
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionAuthenticateSucceeds(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $authAdapterErrors = ['Adapter test error'];
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $this->twoFactorAuthModelMock->expects($this->exactly(2))->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->once())->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeExpired')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('authenticate')->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('storeIdentity')->with($this->identity);
        $this->logSuccessfulLoginMock->expects($this->once())->method('__invoke')->with($this->identity, true);
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_302);
        $this->getAuthRedirectMock->expects($this->once())->method('__invoke')->with($this->identity)->willReturn($response);

        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');

        $this->request->setMethod(Request::METHOD_POST);
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
    }

    /**
     * Test authenticate action, 2fa fails, block IP fails
     * @group two-factor-authentication
     * @return void
     */
    public function testcleAuthenticateActionAuthenticateFailsLogAttemptFails(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $this->twoFactorAuthModelMock->expects($this->exactly(2))->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeExpired')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('authenticate')->willReturn(false);
        $this->logFailedLoginAttemptMock->expects($this->once())->method('__invoke')->with($this->identity->getEmailAddress())->willReturn(false);

        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $messages = $textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('Incorrect code entered', $messages[0]);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
    }

    /**
     * Test authenticate action, 2fa fails, block IP fails
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionAuthenticateBlockIpFails(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $this->twoFactorAuthModelMock->expects($this->exactly(2))->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeExpired')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('authenticate')->willReturn(false);
        $this->logFailedLoginAttemptMock->expects($this->once())->method('__invoke')->with($this->identity->getEmailAddress())->willReturn(true);
        $this->blockIpAddressMock->expects($this->once())->method('__invoke')->with($this->identity->getEmailAddress())->willReturn(false);

        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $messages = $textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('Incorrect code entered', $messages[0]);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
    }

    /**
     * Test authenticate action, 2fa fails, block IP fails
     * @group two-factor-authentication
     * @return void
     */
    public function testAuthenticateActionAuthenticateBlockIpSucceeds(): void
    {
        $authenticationTemplate = 'test/template';
        $authMethod = 'testAuthMethod';
        $this->initAuthenticateAction();
        $this->request->setPost($this->codePostData);

        $this->twoFactorAuthModelMock->expects($this->exactly(2))->method('getIdentity')->willReturn($this->identity);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthenticateTemplate')->willReturn($authenticationTemplate);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getAuthCodeForm')->willReturn($this->twoFactorAuthenticationCodeFormMock);
        $this->twoFactorAuthModelMock->expects($this->once())->method('getSelectedAuthMethod')->willReturn($authMethod);
        $this->twoFactorAuthenticationCodeFormMock->expects($this->exactly(2))->method('get')->with('code')->willReturn($this->textElement);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('processAuthForm')->with($this->codePostData)->willReturn(true);
        $this->twoFactorAuthModelMock->expects($this->once())->method('codeExpired')->willReturn(false);
        $this->twoFactorAuthModelMock->expects($this->once())->method('authenticate')->willReturn(false);
        $this->logFailedLoginAttemptMock->expects($this->once())->method('__invoke')->with($this->identity->getEmailAddress())->willReturn(true);
        $this->blockIpAddressMock->expects($this->once())->method('__invoke')->with($this->identity->getEmailAddress())->willReturn(true);

        $this->twoFactorAuthModelMock->expects($this->never())->method('generateCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('sendCode');
        $this->twoFactorAuthModelMock->expects($this->never())->method('codeSent');
        $this->twoFactorAuthModelMock->expects($this->never())->method('storeIdentity');
        $this->twoFactorAuthModelMock->expects($this->never())->method('getAdaptor');

        $this->request->setMethod(Request::METHOD_POST);
        $view = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(TwoFactorAuthenticationCodeForm::class, $view->authCodeForm);
        $textElement = $view->authCodeForm->get('code');
        $this->assertInstanceOf(Text::class, $textElement);
        $messages = $textElement->getMessages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('Sorry your IP address is blocked', $messages[0]);
        $this->assertInstanceOf(AuthUserInterface::class, $view->user);
        $this->assertEquals($authMethod, $view->authMethod);
    }
}