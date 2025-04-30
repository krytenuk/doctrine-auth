<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller;

use FwsDoctrineAuth\Controller\LoginController;
use FwsDoctrineAuth\Controller\Plugin\BlockIP;
use FwsDoctrineAuth\Controller\Plugin\GetRedirect;
use FwsDoctrineAuth\Controller\Plugin\IsIpBlocked;
use FwsDoctrineAuth\Controller\Plugin\LogFailedAttempt;
use FwsDoctrineAuth\Controller\Plugin\LogSuccessfulLogin;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\ForgottenPasswordForm;
use FwsDoctrineAuth\Form\LoginForm;
use FwsDoctrineAuth\Form\RegisterForm;
use FwsDoctrineAuth\Form\ResetPasswordForm;
use FwsDoctrineAuth\Model\ForgotPasswordModel;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Model\RegisterModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Http\Header\Location;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(LoginController::class)]
class LoginControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = false;

    protected LoginController $controller;
    protected LoginModel|MockObject $loginModelMock;
    protected RegisterModel|MockObject $registerModelMock;
    protected ForgotPasswordModel|MockObject $forgotPasswordModelMock;
    protected ManageTwoFactorAuthenticationModel|MockObject $manage2faModelMock;
    protected TwoFactorAuthenticationModel|MockObject $twoFactorAuthModelMock;

    protected LoginForm|MockObject $loginFormMock;
    private RegisterForm|MockObject $registerFormMock;
    private ForgottenPasswordForm|MockObject $forgotPasswordFormMock;
    private ResetPasswordForm|MockObject $resetPasswordFormMock;

    protected IsIpBlocked|MockObject $isIpBlockedMock;
    protected LogFailedAttempt|MockObject $logFailedLoginAttemptMock;
    protected BlockIP|MockObject $blockIpAddressMock;
    private LogSuccessfulLogin|MockObject $logSuccessfulLoginMock;
    private GetRedirect|MockObject $getAuthRedirectMock;
    private Params|MockObject $paramsMock;

    /**
     * @throws DoctrineAuthException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockModels();

        $this->controller = new LoginController(
            $this->loginModelMock,
            $this->registerModelMock,
            $this->forgotPasswordModelMock,
            $this->manage2faModelMock,
            $this->twoFactorAuthModelMock
        );
        $this->controller->setEvent($this->event);
        $this->controller->setEventManager($this->events);

        $this->mockForms();
        $this->mockControllerPlugins();
    }

    /**
     * Create model mocks
     */
    protected function mockModels(): void
    {
        $this->loginModelMock = $this->getMockBuilder(LoginModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getConfig',
                'getLoginForm',
                'useForgotPassword',
                'processForm',
                'setFormIdentityMessage',
                'login',
                'use2Fa',
                'getIdentity',
                'logout',
            ])
            ->getMock();

        $this->registerModelMock = $this
            ->getMockBuilder(RegisterModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'allowRegistration',
                'getConfig',
                'getForm',
                'processForm',
                'autoLogin',
                'login',
                'getUser',
            ])
            ->getMock();

        $this->forgotPasswordModelMock = $this
            ->getMockBuilder(ForgotPasswordModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getConfig',
                'getEmailForm',
                'processEmailForm',
                'getResetPasswordForm',
                'processResetForm',
                'findUser',
                'isFormValid',
                'sendEmail',
            ])
            ->getMock();

        $this->manage2faModelMock = $this
            ->getMockBuilder(ManageTwoFactorAuthenticationModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->twoFactorAuthModelMock = $this
            ->getMockBuilder(TwoFactorAuthenticationModel::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function mockForms()
    {
        $this->loginFormMock = $this
            ->getMockBuilder(LoginForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getData',
            ])
            ->getMock();

        $this->registerFormMock = $this
            ->getMockBuilder(RegisterForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isValid',
            ])
            ->getMock();

        $this->forgotPasswordFormMock = $this
            ->getMockBuilder(ForgottenPasswordForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isValid',
            ])
            ->getMock();

        $this->resetPasswordFormMock = $this
            ->getMockBuilder(ResetPasswordForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isValid',
                'getCredentialName',
                'getRetypeCredentialName',
            ])
            ->getMock();
    }

    /**
     * Create and add mocked controller plugins
     */
    protected function mockControllerPlugins(): void
    {
        $this->isIpBlockedMock           = $this
            ->getMockBuilder(IsIpBlocked::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();
        $this->logFailedLoginAttemptMock = $this
            ->getMockBuilder(LogFailedAttempt::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();
        $this->blockIpAddressMock        = $this
            ->getMockBuilder(BlockIP::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();
        $this->logSuccessfulLoginMock    = $this
            ->getMockBuilder(LogSuccessfulLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();
        $this->getAuthRedirectMock       = $this
            ->getMockBuilder(GetRedirect::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();
        $this->paramsMock                = $this
            ->getMockBuilder(Params::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                '__invoke',
                'fromRoute',
            ])
            ->getMock();

        $this->controller
            ->getPluginManager()
            ->setService('isIpBlocked', $this->isIpBlockedMock);
        $this->controller
            ->getPluginManager()
            ->setService('logFailedLoginAttempt', $this->logFailedLoginAttemptMock);
        $this->controller
            ->getPluginManager()
            ->setService('blockIpAddress', $this->blockIpAddressMock);
        $this->controller
            ->getPluginManager()
            ->setService('logSuccessfulLogin', $this->logSuccessfulLoginMock);
        $this->controller
            ->getPluginManager()
            ->setService('getAuthRedirect', $this->getAuthRedirectMock);
        $this->controller
            ->getPluginManager()
            ->setService('params', $this->paramsMock);
    }

    /**
     * Test index action redirects to the log in endpoint
     */
    public function testIndexActionRedirects(): void
    {
        $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Check the login action returns the form with a GET method
     *
     * @group authenticate_user
     */
    public function testLoginActionGetForm(): void
    {
        $this->initLoginAction();
        $this->routeMatch->setParam('action', 'login');
        $this->request->setMethod(Request::METHOD_GET);

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(LoginForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertTrue($view->useForgotPassword);
    }

    /**
     * Initiate login action defaults
     */
    protected function initLoginAction(): void
    {
        $this->routeMatch->setParam('action', 'login');
        $this->loginModelMock->expects($this->once())->method('getConfig')->willReturn($this->config);
        $this->loginModelMock->expects($this->once())->method('getLoginForm')->willReturn($this->loginFormMock);
        $this->loginModelMock->expects($this->once())->method('useForgotPassword')->willReturn(true);
    }

    /**
     * Test login action when invalid credentials are sent ($form->isValid() === false)
     *
     * @group authenticate_user
     */
    public function testLoginActionInvalidCredentials(): void
    {
        $this->initLoginAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->loginModelMock->expects($this->once())->method('processForm')->willReturn(false);

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(LoginForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertTrue($view->useForgotPassword);
    }

    /**
     * Test login fails and the logging of the fai8led attempt also fails
     *
     * @group authenticate_user
     */
    public function testLoginActionLoginFailedLogAttemptFailed(): void
    {
        $this->initLoginAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->loginModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->loginModelMock->expects($this->once())->method('login')->willReturn(false);
        $this->logFailedLoginAttemptMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->loginFormMock->expects($this->once())->method('getData')->willReturn(['emailAddress' => 'test@example.com']);

        $this->loginModelMock->expects($this->once())->method('setFormIdentityMessage')->willReturn($this->loginModelMock);

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(LoginForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertTrue($view->useForgotPassword);
    }

    /**
     * Test login fails and the logging of the failed attempt is successful but the IP block check fails
     *
     * @group authenticate_user
     */
    public function testLoginActionLoginFailedBlockIpFailed(): void
    {
        $this->initLoginAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->loginModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->loginModelMock->expects($this->once())->method('login')->willReturn(false);
        $this->loginFormMock->expects($this->once())->method('getData')->willReturn(['emailAddress' => 'test@example.com']);
        $this->logFailedLoginAttemptMock->expects($this->once())->method('__invoke')->willReturn(true);
        $this->blockIpAddressMock->expects($this->once())->method('__invoke')->willReturn(false);

        $this->loginModelMock->expects($this->once())->method('setFormIdentityMessage')->willReturn($this->loginModelMock);

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(LoginForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertTrue($view->useForgotPassword);
    }

    /**
     * Test login fails and the logging of the failed attempt and the IP block check is successful
     *
     * @group authenticate_user
     */
    public function testLoginActionLoginFailedBlockIpSuccess(): void
    {
        $this->initLoginAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->loginModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->loginModelMock->expects($this->once())->method('login')->willReturn(false);
        $this->loginFormMock->expects($this->once())->method('getData')->willReturn(['emailAddress' => 'test@example.com']);
        $this->logFailedLoginAttemptMock->expects($this->once())->method('__invoke')->willReturn(true);
        $this->blockIpAddressMock->expects($this->once())->method('__invoke')->willReturn(true);

        $this->loginModelMock->expects($this->exactly(2))->method('setFormIdentityMessage')->willReturn($this->loginModelMock);

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_401, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(LoginForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertTrue($view->useForgotPassword);
    }

    /**
     * Test login successful and use 2FA is enabled
     *
     * @group authenticate_user
     * @return void
     */
    public function testLoginActionLoginSuccessUse2FA()
    {
        $this->initLoginAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->loginModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->loginModelMock->expects($this->once())->method('login')->willReturn(true);
        $this->loginFormMock->expects($this->never())->method('getData');
        $this->logFailedLoginAttemptMock->expects($this->never())->method('__invoke');
        $this->blockIpAddressMock->expects($this->never())->method('__invoke');
        $this->loginModelMock->expects($this->once())->method('use2Fa')->willReturn(true);

        $this->loginModelMock->expects($this->never())->method('setFormIdentityMessage');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/2fa/select-auth-method', $location->getUri());
    }

    /**
     * Test login successful and use 2FA is not enabled
     *
     * @group authenticate_user
     * @return void
     */
    public function testLoginActionLoginSuccessNo2FA()
    {
        $this->initLoginAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->loginModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->isIpBlockedMock->expects($this->once())->method('__invoke')->willReturn(false);
        $this->loginModelMock->expects($this->once())->method('login')->willReturn(true);
        $this->loginFormMock->expects($this->never())->method('getData');
        $this->logFailedLoginAttemptMock->expects($this->never())->method('__invoke');
        $this->blockIpAddressMock->expects($this->never())->method('__invoke');
        $this->loginModelMock->expects($this->once())->method('use2Fa')->willReturn(false);
        $this->loginModelMock->expects($this->never())->method('setFormIdentityMessage');
        $this->loginModelMock->expects($this->once())->method('getIdentity')->willReturn($this->identity);
        $this->logSuccessfulLoginMock->expects($this->once())->method('__invoke');
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_302);
        $this->getAuthRedirectMock->expects($this->once())->method('__invoke')->willReturn($response);

        /** @var Response $response */
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
    }

    /**
     * Test logout action
     *
     * @group logout
     * @return void
     */
    public function testLogoutAction()
    {
        $this->routeMatch->setParam('action', 'logout');
        $this->request->setMethod(Request::METHOD_GET);
        $this->loginModelMock->expects($this->once())->method('logout');

        /** @var Response $response */
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Initiate register action defaults
     */
    protected function initRegisterAction(): void
    {
        $this->routeMatch->setParam('action', 'register');
        $this->registerModelMock->expects($this->once())->method('allowRegistration')->willReturn(true);
        $this->registerModelMock->expects($this->once())->method('getConfig')->willReturn($this->config);
    }

    /**
     * Test register action when registration is not allowed
     *
     * @group register_user
     * @return void
     */
    public function testRegisterActionNotAllowedRegistration()
    {
        $this->routeMatch->setParam('action', 'register');
        $this->request->setMethod(Request::METHOD_GET);
        $this->registerModelMock->expects($this->once())->method('allowRegistration')->willReturn(false);

        /** @var Response $response */
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Test register action returns form from get request
     *
     * @group register_user
     * @return void
     */
    public function testRegisterActionGetForm()
    {
        $this->initRegisterAction();
        $this->request->setMethod(Request::METHOD_GET);
        $this->registerModelMock->expects($this->once())->method('getForm')->willReturn($this->registerFormMock);

        $this->registerModelMock->expects($this->never())->method('processForm');
        $this->registerFormMock->expects($this->never())->method('isValid');
        $this->registerModelMock->expects($this->never())->method('autoLogin');
        $this->registerModelMock->expects($this->never())->method('login');
        $this->getAuthRedirectMock->expects($this->never())->method('__invoke');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(RegisterForm::class, $view->form);
        $this->assertIsArray($view->config);
    }

    /**
     * Test register action when form is not valid
     *
     * @group register_user
     * Incorrect email/password combination
     * @return void
     */
    public function testRegisterActionFormInvalid()
    {
        $this->initRegisterAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->registerModelMock->expects($this->exactly(2))->method('getForm')->willReturn($this->registerFormMock);
        $this->registerModelMock->expects($this->once())->method('processForm')->willReturn(false);
        $this->registerFormMock->expects($this->once())->method('isValid')->willReturn(false);

        $this->registerModelMock->expects($this->never())->method('autoLogin');
        $this->registerModelMock->expects($this->never())->method('login');
        $this->getAuthRedirectMock->expects($this->never())->method('__invoke');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_400, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(RegisterForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertEquals('There is a problem with the form you submitted, please correct errors highlighted.', $view->errorMessage);
    }

    /**
     * Test registration action failed
     *
     * @group register_user
     * @return void
     */
    public function testRegisterActionFailed()
    {
        $this->initRegisterAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->registerModelMock->expects($this->exactly(2))->method('getForm')->willReturn($this->registerFormMock);
        $this->registerModelMock->expects($this->once())->method('processForm')->willReturn(false);
        $this->registerFormMock->expects($this->once())->method('isValid')->willReturn(true);

        $this->registerModelMock->expects($this->never())->method('autoLogin');
        $this->registerModelMock->expects($this->never())->method('login');
        $this->getAuthRedirectMock->expects($this->never())->method('__invoke');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(RegisterForm::class, $view->form);
        $this->assertIsArray($view->config);
        $this->assertEquals('Unable to register you at this time, please try again later.', $view->errorMessage);
    }

    /**
     * Test register action succeeds with auto login enabled
     *
     * @group register_user
     * @return void
     */
    public function testRegisterActionSuccessAutoLoginSucceeds()
    {
        $this->initRegisterAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->registerModelMock->expects($this->once())->method('getForm')->willReturn($this->registerFormMock);
        $this->registerModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->registerFormMock->expects($this->never())->method('isValid');
        $this->registerModelMock->expects($this->once())->method('autoLogin')->willReturn(true);
        $this->registerModelMock->expects($this->once())->method('login')->willReturn(true);
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_302);
        $this->getAuthRedirectMock->expects($this->once())->method('__invoke')->willReturn($response);
        $this->registerModelMock->expects($this->once())->method('getUser');

        /** @var Response $response */
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
    }

    /**
     * Test register action succeeds with auto login enabled
     *
     * @group register_user
     * @return void
     */
    public function testRegisterActionSuccessAutoLoginFails()
    {
        $this->initRegisterAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->registerModelMock->expects($this->once())->method('getForm')->willReturn($this->registerFormMock);
        $this->registerModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->registerFormMock->expects($this->never())->method('isValid');
        $this->registerModelMock->expects($this->once())->method('autoLogin')->willReturn(true);
        $this->registerModelMock->expects($this->once())->method('login')->willReturn(false);

        $this->getAuthRedirectMock->expects($this->never())->method('__invoke');
        $this->registerModelMock->expects($this->never())->method('getUser');

        /** @var Response $response */
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Test register action succeeds with auto login enabled
     *
     * @group register_user
     * @return void
     */
    public function testRegisterActionSuccessNoAutoLogin()
    {
        $this->initRegisterAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->registerModelMock->expects($this->once())->method('getForm')->willReturn($this->registerFormMock);
        $this->registerModelMock->expects($this->once())->method('processForm')->willReturn(true);
        $this->registerFormMock->expects($this->never())->method('isValid');
        $this->registerModelMock->expects($this->once())->method('autoLogin')->willReturn(false);

        $this->registerModelMock->expects($this->never())->method('login');
        $this->getAuthRedirectMock->expects($this->never())->method('__invoke');
        $this->registerModelMock->expects($this->never())->method('getUser');

        /** @var Response $response */
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals(Response::STATUS_CODE_302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $location = $response->getHeaders()->get('Location');
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('/auth/login', $location->getUri());
    }

    /**
     * Initiate reset password action defaults
     */
    protected function initResetPasswordAction(): void
    {
        $this->routeMatch->setParam('action', 'password-reset');
        $this->forgotPasswordModelMock->expects($this->once())->method('getConfig')->willReturn($this->config);
    }

    /**
     * Test reset password action with no code in URL, get email form
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionNoCodeGetRequest()
    {
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_GET);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn(null);
        $this->forgotPasswordModelMock->expects($this->once())->method('getEmailForm')->willReturn($this->forgotPasswordFormMock);

        $this->forgotPasswordModelMock->expects($this->never())->method('processEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('getResetPasswordForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('processResetForm');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(ForgottenPasswordForm::class, $view->emailForm);
        $this->assertIsArray($view->config);
        $this->assertNull($view->resetForm);
    }

    /**
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionProcessEmailFormFailedEmailFormInvalid()
    {
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn(null);
        $this->forgotPasswordModelMock->expects($this->once())->method('processEmailForm')->willReturn(false);
        $this->forgotPasswordModelMock->expects($this->once())->method('isFormValid')->willReturn(false);
        $this->forgotPasswordModelMock->expects($this->once())->method('getEmailForm')->willReturn($this->forgotPasswordFormMock);

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_400, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertInstanceOf(ForgottenPasswordForm::class, $view->emailForm);
        $this->assertIsArray($view->config);
        $this->assertFalse($view->emailSent);
        $this->assertNull($view->resetForm);
    }

    /**
     * Test reset password action when email form sent but validation fails (invalid credentials)
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionProcessEmailFormFailedEmailFormValid()
    {
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn(null);
        $this->forgotPasswordModelMock->expects($this->once())->method('processEmailForm')->willReturn(false);
        $this->forgotPasswordModelMock->expects($this->once())->method('isFormValid')->willReturn(true);

        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_500, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertNull($view->emailForm);
        $this->assertIsArray($view->config);
        $this->assertFalse($view->emailSent);
        $this->assertNull($view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password action when email form sent and valid but email sending fails
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionProcessEmailFormSucceedsEmailSendFails()
    {
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn(null);
        $this->forgotPasswordModelMock->expects($this->once())->method('processEmailForm')->willReturn(true);
        $this->forgotPasswordModelMock->expects($this->once())->method('sendEmail')->willReturn(false);

        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertIsArray($view->config);
        $this->assertFalse($view->emailSent);
        $this->assertNull($view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password action when email form sent and valid but email sending fails
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionProcessEmailFormSucceedsEmailSendSucceeds()
    {
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn(null);
        $this->forgotPasswordModelMock->expects($this->once())->method('processEmailForm')->willReturn(true);
        $this->forgotPasswordModelMock->expects($this->once())->method('sendEmail')->willReturn(true);

        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertIsArray($view->config);
        $this->assertTrue($view->emailSent);
        $this->assertNull($view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password with valid code sent with get request
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionCodeSentGetRequestValidCode()
    {
        $code = 'valid_code';
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_GET);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn($code);
        $this->forgotPasswordModelMock->expects($this->once())->method('findUser')->with($code)->willReturn(true);
        $this->forgotPasswordModelMock->expects($this->once())->method('getResetPasswordForm')->willReturn($this->resetPasswordFormMock);

        $this->forgotPasswordModelMock->expects($this->never())->method('processEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('sendEmail');
        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('isFormValid');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($code, $view->code);
        $this->assertFalse($view->invalidLink);
        $this->assertIsArray($view->config);
        $this->assertNull($view->emailSent);
        $this->assertInstanceOf(ResetPasswordForm::class, $view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password with invalid code sent with get request
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionCodeSentGetRequestInvalidCode()
    {
        $code = 'invalid_code';
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_GET);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn($code);
        $this->forgotPasswordModelMock->expects($this->once())->method('findUser')->with($code)->willReturn(false);

        $this->forgotPasswordModelMock->expects($this->never())->method('getResetPasswordForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('processEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('sendEmail');
        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('isFormValid');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($code, $view->code);
        $this->assertTrue($view->invalidLink);
        $this->assertIsArray($view->config);
        $this->assertNull($view->emailSent);
        $this->assertNull($view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password with valid password reset form
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionCodeSentResetSucceedsValidForm()
    {
        $postData = new Parameters([
            'password'       => 'password',
            'retypePassword' => 'password',
        ]);
        $this->request->setPost($postData);
        $code = 'invalid_code';
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn($code);
        $this->forgotPasswordModelMock->expects($this->once())->method('findUser')->with($code)->willReturn(true);
        $this->forgotPasswordModelMock->expects($this->once())->method('processResetForm')->with($postData)->willReturn(true);

        $this->forgotPasswordModelMock->expects($this->never())->method('getResetPasswordForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('processEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('sendEmail');
        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('isFormValid');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($code, $view->code);
        $this->assertFalse($view->invalidLink);
        $this->assertIsArray($view->config);
        $this->assertNull($view->emailSent);
        $this->assertTrue($view->passwordReset);
        $this->assertNull($view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password with invalid password reset form
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionCodeSentResetFailsInvalidForm()
    {
        $postData = new Parameters([
            'password'       => 'password',
            'retypePassword' => 'password',
        ]);
        $this->request->setPost($postData);
        $code = 'invalid_code';
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn($code);
        $this->forgotPasswordModelMock->expects($this->once())->method('findUser')->with($code)->willReturn(true);
        $this->forgotPasswordModelMock->expects($this->once())->method('processResetForm')->with($postData)->willReturn(false);
        $this->forgotPasswordModelMock->expects($this->once())->method('isFormValid')->willReturn(false);
        $this->forgotPasswordModelMock->expects($this->once())->method('getResetPasswordForm')->willReturn($this->resetPasswordFormMock);

        $this->forgotPasswordModelMock->expects($this->never())->method('processEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('sendEmail');
        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_400, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($code, $view->code);
        $this->assertFalse($view->invalidLink);
        $this->assertIsArray($view->config);
        $this->assertNull($view->emailSent);
        $this->assertFalse($view->passwordReset);
        $this->assertInstanceOf(ResetPasswordForm::class, $view->resetForm);
        $this->assertNull($view->emailForm);
    }

    /**
     * Test reset password with invalid password reset form
     *
     * @group password_reset
     * @return void
     */
    public function testPasswordResetActionCodeSentResetFailsValidForm()
    {
        $postData = new Parameters([
            'password'       => 'password',
            'retypePassword' => 'password',
        ]);
        $this->request->setPost($postData);
        $code = 'invalid_code';
        $this->initResetPasswordAction();
        $this->request->setMethod(Request::METHOD_POST);
        $this->paramsMock->expects($this->once())->method('__invoke')->willReturn($this->paramsMock);
        $this->paramsMock->expects($this->once())->method('fromRoute')->with('code')->willReturn($code);
        $this->forgotPasswordModelMock->expects($this->once())->method('findUser')->with($code)->willReturn(true);
        $this->forgotPasswordModelMock->expects($this->once())->method('processResetForm')->with($postData)->willReturn(false);
        $this->forgotPasswordModelMock->expects($this->once())->method('isFormValid')->willReturn(true);

        $this->forgotPasswordModelMock->expects($this->never())->method('getResetPasswordForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('processEmailForm');
        $this->forgotPasswordModelMock->expects($this->never())->method('sendEmail');
        $this->forgotPasswordModelMock->expects($this->never())->method('getEmailForm');

        $view     = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(Response::STATUS_CODE_500, $response->getStatusCode());
        $this->assertInstanceOf(ViewModel::class, $view);
        $this->assertEquals($code, $view->code);
        $this->assertFalse($view->invalidLink);
        $this->assertIsArray($view->config);
        $this->assertNull($view->emailSent);
        $this->assertFalse($view->passwordReset);
        $this->assertNull($view->emailForm);
        $this->assertNull($view->resetForm);
    }
}
