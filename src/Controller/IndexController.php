<?php

namespace FwsDoctrineAuth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use FwsDoctrineAuth\Model;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;
use FwsDoctrineAuth\Entity\BaseUsers;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;

/**
 * IndexController
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class IndexController extends AbstractActionController
{

    /**
     * @var Model\LoginModel
     */
    protected Model\LoginModel $loginModel;

    /**
     *
     * @var Model\RegisterModel
     */
    protected Model\RegisterModel $registerModel;

    /**
     *
     * @var Model\ForgotPasswordModel
     */
    protected Model\ForgotPasswordModel $forgotPasswordModel;

    /**
     * 
     * @var Model\Select2faModel
     */
    protected Model\Select2faModel $select2faModel;

    /**
     * 
     * @param Model\LoginModel          $loginModel
     * @param Model\RegisterModel       $registerModel
     * @param Model\ForgotPasswordModel $forgotPasswordModel
     */
    public function __construct(
            Model\LoginModel $loginModel,
            Model\RegisterModel $registerModel,
            Model\ForgotPasswordModel $forgotPasswordModel,
            Model\Select2faModel $select2faModel,
    )
    {
        $this->loginModel = $loginModel;
        $this->registerModel = $registerModel;
        $this->select2faModel = $select2faModel;
        $this->forgotPasswordModel = $forgotPasswordModel;
    }

    /**
     * Redirect to login
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'login']);
    }

    /**
     * Login user
     * 
     * @return ViewModel|Response
     */
    public function loginAction()
    {
        /* Create view model */
        $viewModel = new ViewModel();
        $viewModel->config = $this->loginModel->getConfig();
        $viewModel->form = $this->loginModel->getLoginForm();
        $viewModel->useForgotPassword = $this->loginModel->useForgotPassword();

        /* Form NOT submitted */
        if ($this->getRequest()->isPost() === false) {
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();

        /* Login form validation failed */
        if ($this->loginModel->processForm($postData) === false) {
            return $viewModel;
        }

        if ($this->loginModel->isIpBlocked() === true) {
            $this->loginModel->setFormIdentityMessage(_('Sorry your IP address is blocked'));
            return $viewModel;
        }

        /* Login authentication failed */
        if ($this->loginModel->login(null) === false) {
            $this->loginModel->setFormIdentityMessage(_('User not found'));
            if ($this->loginModel->logFailedAttempt($viewModel->form->getData()['emailAddress']) === true) {
                $this->blockIp($viewModel->form->getData()['emailAddress']);
                return $viewModel;
            }
        }

        /* Use 2FA */
        if ($this->loginModel->use2Fa() === true) {
            return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'select-auth-method']);
        }

        $this->loginModel->logSuccessfulLogin(false);
        return $this->getRedirect();
    }

    /**
     * Let the user choose their 2FA method 
     * @return ViewModel
     */
    public function selectAuthMethodAction()
    {
        if ($this->loginModel->getIdentity() instanceof BaseUsers === false) {
            return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'login']);
        }

        $viewModel = new ViewModel();
        $viewModel->userAuthMethodsForm = $this->loginModel->getTwoFactorAuthModel()->getSelectAuthMethodForm();

        $authMethod = $this->loginModel->getTwoFactorAuthModel()->getSingleAuthMethod();
        if ($authMethod !== null) {
            if ($this->loginModel->getTwoFactorAuthModel()->setTwoFactorAuthMethod($authMethod->getMethod()) === false) {
                $this->loginModel->getTwoFactorAuthModel()->getSelectAuthMethodForm()->get('method')->setMessages([_('Authentication method not found')]);
                return $viewModel;
            }
            return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'authenticate']);
        }

        if ($this->getRequest()->isPost() === false) {
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if ($this->loginModel->getTwoFactorAuthModel()->processSelectForm($postData) === false) {
            $this->loginModel->getTwoFactorAuthModel()->getSelectAuthMethodForm()->get('method')->setMessages([_('You must select your authentication method')]);
            return $viewModel;
        }

        if ($this->loginModel->getTwoFactorAuthModel()->setTwoFactorAuthMethod($this->loginModel->getTwoFactorAuthModel()->getSelectAuthMethodForm()->getData()['method']) === false) {
            $this->loginModel->getTwoFactorAuthModel()->getSelectAuthMethodForm()->get('method')->setMessages([_('Authentication method not found')]);
            return $viewModel;
        }

        return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'authenticate']);
    }

    /**
     * 2FA authenticate user
     * @return ViewModel
     */
    public function authenticateAction()
    {
        if ($this->loginModel->getIdentity() instanceof BaseUsers === false) {
            return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'login']);
        }

        $viewModel = new ViewModel();
        $viewModel->authCodeForm = $this->loginModel->getTwoFactorAuthModel()->getAuthForm();
        $viewModel->user = $this->loginModel->getIdentity();
        $viewModel->authMethod = $this->loginModel->getAuthContainer()->authMethod;
        $viewModel->partial = 'fws-doctrine-auth/partials/' . $this->loginModel->getAuthContainer()->authMethod . '-2fa';

        if ($this->getRequest()->isPost() === false) {
            if ($this->loginModel->getAuthContainer()->codeSent === false) {
                $this->loginModel->getTwoFactorAuthModel()->sendCode();
            }
            $viewModel->codeSent = $this->loginModel->getAuthContainer()->codeSent;
            return $viewModel;
        }

        if ($this->loginModel->isIpBlocked() === true) {
            $this->loginModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([_('Sorry your IP address is blocked')]);
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if ($this->loginModel->getTwoFactorAuthModel()->processAuthForm($postData) === false) {
            $this->loginModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([_('There is a problem with the code you entered')]);
            return $viewModel;
        }

        if ($this->loginModel->getTwoFactorAuthModel()->codeExpired() === true) {
            $this->loginModel->getTwoFactorAuthModel()->generateCode();
            $this->loginModel->getTwoFactorAuthModel()->sendCode();
            $this->loginModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([_('Your code has expired, a new code has been sent')]);
            return $viewModel;
        }

        if ($this->loginModel->getTwoFactorAuthModel()->authenticate() === false) {
            if ($this->loginModel->logFailedAttempt($this->loginModel->getAuthContainer()->identity->getEmailAddress()) === true) {
                $this->blockIp($this->loginModel->getAuthContainer()->identity->getEmailAddress());
            }
            $this->loginModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([_('Incorrect code entered')]);
            return $viewModel;
        }

        $this->loginModel->setIdentity($this->loginModel->getAuthContainer()->identity);
        $this->loginModel->logSuccessfulLogin(true);
        return $this->getRedirect();
    }

    public function resendCodeAction()
    {
        if ($this->loginModel->getAuthContainer()->codeSentAttempts < TwoFactorAuthModel::SEND_SMS_MAX_ATTEMPTS) {
            $this->loginModel->getTwoFactorAuthModel()->sendCode();
        }
        return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'authenticate']);
    }

    /**
     * Logout
     *
     * @return Response
     */
    public function logoutAction()
    {
        $this->loginModel->logout();
        return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'login']);
    }

    /**
     * Register new user
     * 
     * @return ViewModel|Response
     */
    public function registerAction()
    {
        /* Registration NOT allowed tn config */
        if ($this->registerModel->allowRegistration() === false) {
            return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'login']);
        }

        /* Create viewmodel */
        $viewModel = new ViewModel();
        $viewModel->config = $this->registerModel->getConfig();
        $viewModel->config = $this->forgotPasswordModel->getConfig();
        $viewModel->form = $this->registerModel->getForm();

        /* Form NOT submitted */
        if ($this->getRequest()->isPost() === false) {
            return $viewModel;
        }

        /* registration failed */
        if ($this->registerModel->processForm($this->getRequest()->getPost()) === false) {
            if ($this->registerModel->getForm()->isValid()) {
                $viewModel->errorMessage = 'Unable to register you at this time, please try again later.';
            } else {
                $viewModel->errorMessage = 'There is a problem with the form you submitted, please correct errors highlighted.';
            }
            return $viewModel;
        }

        /* Auto login set in config */
        if ($this->registerModel->autoLogin()) {
            if ($this->registerModel->login()) {
                $redirect = $this->registerModel->getDefaultRedirect();
                return $this->redirect()->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
            }
        }
        return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'login']);
    }

    /**
     * Reset password
     * 
     * @return ViewModel
     */
    public function passwordResetAction()
    {
        /* Setup view model */
        $viewModel = new ViewModel();
        $viewModel->config = $this->forgotPasswordModel->getConfig();

        $request = $this->getRequest();
        /* Get code from url */
        $code = $this->params()->fromRoute('code', false);
        /* No code sent */
        if ($code === false) {
            /* Form not submitted, pass email address form to view */
            if ($request->isPost() === false) {
                $viewModel->emailForm = $this->forgotPasswordModel->getEmailForm();
                return $viewModel;
            }
            /* Form submitted, process email form */
            return $this->processEmailForm($viewModel, $request->getPost());
        }

        /* Code sent */
        $viewModel->code = $code;
        /* New password form not submitted */
        if ($request->isPost() === false) {
            if ($this->forgotPasswordModel->findUser($code) === true) {
                $viewModel->resetForm = $this->forgotPasswordModel->getResetPasswordForm();
            } else {
                $viewModel->invalidLink = true;
            }
            return $viewModel;
        }

        $postData = $request->getPost();
        if ($this->forgotPasswordModel->findUser($code)) {
            return $this->processPasswordResetForm($viewModel, $postData);
        } else {
            $viewModel->invalidLink = true;
        }
        return $viewModel;
    }

    public function selectTwoFactorAuthenticationAction()
    {
        $viewModel = new ViewModel();

        $method = $this->params()->fromQuery('method');
        $viewModel->methodAdded = '';
        $viewModel->methodRemoved = '';
        if ($method) {
            if ($this->select2faModel->getMethod($method)) {
                $viewModel->methodRemoved = $this->select2faModel->removeMethod($method) ? Model\Select2faModel::getMethodTitle($method) : '';
            } else {
                $viewModel->methodAdded = $this->select2faModel->addMethod($method) ? Model\Select2faModel::getMethodTitle($method) : '';
            }

            if (empty($viewModel->methodAdded) === false || empty($viewModel->methodRemoved) === false) {
                return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'select-two-factor-authentication']);
            }
        }

        $viewModel->authMethods = Model\TwoFactorAuthModel::VALIDAUTHENTICATIONMETHODS;
        $viewModel->user = $this->select2faModel->getUser();
        $viewModel->allowedMethods = $this->select2faModel->getAllowedMethods();
        return $viewModel;
    }

    public function setGoogleAuthenticationAction()
    {
        $viewModel = new ViewModel();

        $this->select2faModel->getGoogleAuthSecret();
        $viewModel->authCodeForm = $this->select2faModel->getTwoFactorAuthModel()->getAuthForm();
        $viewModel->qrCode = $this->select2faModel->getQrCode();

        if ($this->getRequest()->isPost() === false) {
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if ($this->select2faModel->getTwoFactorAuthModel()->processAuthForm($postData) === false) {
            $this->select2faModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([_('There is a problem with the code you entered')]);
            return $viewModel;
        }

        if ($this->select2faModel->getTwoFactorAuthModel()->authenticate() === false) {
            $this->select2faModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([_('Incorrect code entered')]);
            return $viewModel;
        }

        $this->select2faModel->addMethod(TwoFactorAuthModel::GOOGLEAUTHENTICATOR);
        $this->loginModel->setIdentity($this->select2faModel->getAuthContainer()->identity);
        $this->loginModel->logSuccessfulLogin(true);
        return $this->getRedirect();
    }

    public function regenerateGoogleSecretAction()
    {
        $this->select2faModel->getAuthContainer()->secret = null;
        return $this->redirect()->toRoute('doctrine-auth/default', ['action' => 'set-google-authentication']);
    }

    /**
     * Process forgot password email form
     *
     * @param  ViewModel  $viewModel
     * @param  Parameters $postData
     * @return ViewModel
     */
    private function processEmailForm(ViewModel $viewModel, Parameters $postData): ViewModel
    {
        $viewModel->emailSent = false;
        if ($this->forgotPasswordModel->processEmailForm($postData)) {
            if ($this->forgotPasswordModel->sendEmail()) {
                $viewModel->emailSent = true;
            }
        } else {
            if ($this->forgotPasswordModel->isFormValid() === false) {
                $viewModel->emailForm = $this->forgotPasswordModel->getEmailForm();
            }
        }
        return $viewModel;
    }

    /**
     * Process new password form
     *
     * @param  ViewModel  $viewModel
     * @param  Parameters $postData
     * @return ViewModel
     */
    private function processPasswordResetForm(ViewModel $viewModel, Parameters $postData): ViewModel
    {
        $viewModel->passwordReset = false;
        if ($this->forgotPasswordModel->processResetForm($postData)) {
            $viewModel->passwordReset = true;
        } else {
            if ($this->forgotPasswordModel->isFormValid() === false) {
                $viewModel->resetForm = $this->forgotPasswordModel->getResetPasswordForm();
            }
        }
        return $viewModel;
    }

    /**
     * Get post login redirect
     *
     * @return Response
     */
    private function getRedirect(): Response
    {
        /* HTTP 302 redirect */
        if ($this->loginModel->hasRedirect() && $this->loginModel->canRedirect()) {
            /* Redirect to requested page */
            return $this->redirect()->toUrl($this->loginModel->getRedirectUrl());
        } else {
            /* Redirect to default page */
            $redirect = $this->loginModel->getDefaultRedirect(null);
            return $this->redirect()->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
        }
    }

    /**
     * Block ip address if login attempts >= max allowed
     * @param string $emailAddress
     * @return void
     */
    private function blockIp(string $emailAddress): void
    {
        if ($this->loginModel->blockIp($emailAddress)) {
            $method = debug_backtrace()[1]['function'];
            $message = _('Sorry your IP address has been blocked');
            if ($method === 'authenticateAction') {
                $this->loginModel->getTwoFactorAuthModel()->getAuthForm()->get('code')->setMessages([$message]);
            } elseif ($method === 'loginAction') {
                $this->loginModel->setFormIdentityMessage($message);
            }
        }
    }

}
