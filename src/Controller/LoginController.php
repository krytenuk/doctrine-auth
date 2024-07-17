<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Controller;

use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\LoginForm;
use FwsDoctrineAuth\Model;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;

use function _;

/**
 * IndexController
 *
 * @method string translate($message, $textDomain = null, $locale = null)
 * @method Response getAuthRedirect(AuthUserInterface $identity, bool $getDefault = false)
 * @method bool isIpBlocked()
 * @method bool blockIpAddress(string $emailEntered)
 * @method bool logFailedLoginAttempt(string $emailAddress)
 * @method bool logSuccessfulLogin(AuthUserInterface $identity, bool $used2fa)
 */
class LoginController extends AbstractActionController
{
    public function __construct(
        protected Model\LoginModel $loginModel,
        protected Model\RegisterModel $registerModel,
        protected Model\ForgotPasswordModel $forgotPasswordModel,
        protected Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel $select2faModel,
        protected Model\TwoFactorAuthentication\TwoFactorAuthenticationModel $twoFactorAuthModel
    ) {
    }

    /**
     * Redirect to login
     */
    public function indexAction(): Response
    {
        return $this->redirect()->toRoute('doctrine-auth/login');
    }

    /**
     * Login user
     *
     * @throws DoctrineAuthException
     */
    public function loginAction(): Response|ViewModel
    {
        /* Create view model */
        $viewModel = new ViewModel([
            'config' => $this->loginModel->getConfig(),
            'form' => $this->loginModel->getLoginForm(),
            'useForgotPassword' => $this->loginModel->useForgotPassword(),
            'errorMessage' => '',
        ]);

        /* Form NOT submitted */
        if (! $this->getRequest()->isPost()) {
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();

        /* Login form validation failed */
        if (! $this->loginModel->processForm($postData)) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            return $viewModel;
        }

        /* IP address blocked */
        if ($this->isIpBlocked()) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            $viewModel->setVariable('errorMessage', Model\LoginModel::$loginErrorMessages[Model\LoginModel::ERROR_IP_BLOCKED]);
            return $viewModel;
        }

        /* Login authentication failed */
        if (! $this->loginModel->login(null)) {
            $viewModel->setVariable('errorMessage', Model\LoginModel::$loginErrorMessages[Model\LoginModel::ERROR_INVALID_CREDENTIALS]);
            $emailAddress = $viewModel->form->getData()['emailAddress'];
            if ($this->logFailedLoginAttempt($emailAddress)) {
                if ($this->blockIpAddress($emailAddress)) {
                    $viewModel->setVariable('errorMessage', Model\LoginModel::$loginErrorMessages[Model\LoginModel::ERROR_BLOCK_IP_ADDRESS]);
                }
            }
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            return $viewModel;
        }

        /* Use 2FA */
        if ($this->loginModel->use2Fa()) {
            return $this->redirect()->toRoute('doctrine-auth/2fa/select-auth-method');
        }

        $identity = $this->loginModel->getIdentity();
        $this->logSuccessfulLogin($identity, false);
        return $this->getAuthRedirect($identity);
    }

    /**
     * Logout
     */
    public function logoutAction(): Response
    {
        $this->loginModel->logout();
        return $this->redirect()->toRoute('doctrine-auth/login');
    }

    /**
     * Register new user
     *
     * @throws DoctrineAuthException
     */
    public function registerAction(): Response|ViewModel
    {
        /* Registration NOT allowed tn config */
        if (! $this->registerModel->allowRegistration()) {
            return $this->redirect()->toRoute('doctrine-auth/login');
        }

        $viewModel = new ViewModel([
            'config' => $this->registerModel->getConfig(),
            'form' => $this->registerModel->getForm(),
        ]);

        /* Form NOT submitted */
        if (! $this->getRequest()->isPost()) {
            return $viewModel;
        }

        /* registration failed */
        if (! $this->registerModel->processForm($this->getRequest()->getPost())) {
            if ($this->registerModel->getForm()->isValid()) {
                $viewModel->setVariable('errorMessage', Model\RegisterModel::$loginErrorMessages[Model\RegisterModel::ERROR_REGISTRATION_FAILED]);
            } else {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
                $viewModel->setVariable('errorMessage', Model\RegisterModel::$loginErrorMessages[Model\RegisterModel::ERROR_FORM_INVALID]);
            }
            return $viewModel;
        }

        /* Auto login set in config */
        if ($this->registerModel->autoLogin()) {
            if ($this->registerModel->login()) {
                return $this->getAuthRedirect($this->registerModel->getUser());
            }
        }

        return $this->redirect()->toRoute('doctrine-auth/login');
    }

    /**
     * Reset password
     *
     * @throws Exception
     */
    public function passwordResetAction(): ViewModel
    {
        /* Setup view model */
        $viewModel         = new ViewModel();
        $viewModel->config = $this->forgotPasswordModel->getConfig();

        $request = $this->getRequest();
        /* Get code from url */
        $code = $this->params()->fromRoute('code');
        /* No code sent */
        if ($code === null) {
            /* Form not submitted, pass email address form to view */
            if (! $request->isPost()) {
                $viewModel->emailForm = $this->forgotPasswordModel->getEmailForm();
                return $viewModel;
            }
            /* Form submitted, process email form */
            return $this->processEmailForm($viewModel, $request->getPost());
        }

        /* Code sent */
        $viewModel->code        = $code;
        $viewModel->invalidLink = false;
        $user                   = $this->forgotPasswordModel->findUser($code);
        /* New password form not submitted */
        if (! $request->isPost()) {
            if ($user) {
                $viewModel->resetForm = $this->forgotPasswordModel->getResetPasswordForm();
            } else {
                $viewModel->invalidLink = true;
            }
            return $viewModel;
        }

        $postData = $request->getPost();
        if ($user) {
            return $this->processPasswordResetForm($viewModel, $postData);
        } else {
            $viewModel->invalidLink = true;
        }
        return $viewModel;
    }

    /**
     * Process forgot password email form
     *
     * @throws DoctrineAuthException
     */
    private function processEmailForm(ViewModel $viewModel, Parameters $postData): ViewModel
    {
        $viewModel->emailSent = false;
        if ($this->forgotPasswordModel->processEmailForm($postData)) {
            if ($this->forgotPasswordModel->sendEmail()) {
                $viewModel->emailSent = true;
            }
            return $viewModel;
        }

        if (! $this->forgotPasswordModel->isFormValid()) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $viewModel->emailForm = $this->forgotPasswordModel->getEmailForm();
            return $viewModel;
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
        return $viewModel;
    }

    /**
     * Process new password form
     *
     * @throws DoctrineAuthException
     */
    private function processPasswordResetForm(ViewModel $viewModel, Parameters $postData): ViewModel
    {
        $viewModel->passwordReset = false;
        if ($this->forgotPasswordModel->processResetForm($postData)) {
            $viewModel->passwordReset = true;
            return $viewModel;
        }

        if (! $this->forgotPasswordModel->isFormValid()) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $viewModel->resetForm = $this->forgotPasswordModel->getResetPasswordForm();
            return $viewModel;
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
        return $viewModel;
    }
}
