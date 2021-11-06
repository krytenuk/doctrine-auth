<?php

namespace FwsDoctrineAuth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use FwsDoctrineAuth\Model;
use Laminas\Mvc\I18n\Translator;
use FwsDoctrineAuth\Model\ForgotPasswordModel;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;

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
    protected $loginModel;

    /**
     *
     * @var Model\RegisterModel
     */
    protected $registerModel;

    /**
     *
     * @var ForgotPasswordModel
     */
    protected $forgotPasswordModel;

    /**
     *
     * @var Translator
     */
    protected $translator;

    /**
     * 
     * @param Model\LoginModel $loginModel
     * @param Model\RegisterModel $registerModel
     * @param Model\ForgotPasswordModel $forgotPasswordModel
     * @param Translator $translator
     */
    public function __construct(
            Model\LoginModel $loginModel,
            Model\RegisterModel $registerModel,
            Model\ForgotPasswordModel $forgotPasswordModel,
            Translator $translator
    )
    {
        $this->loginModel = $loginModel;
        $this->registerModel = $registerModel;
        $this->translator = $translator;
        $this->forgotPasswordModel = $forgotPasswordModel;
    }

    /**
     * Redirect to login
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
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
        $viewModel->form = $this->loginModel->getForm();
        $viewModel->useForgotPassword = $this->loginModel->useForgotPassword();

        /* Form NOT submitted */
        if ($this->getRequest()->isPost() === false) {
            return $viewModel;
        }

        /* Login form validation failed */
        if ($this->loginModel->processForm($this->getRequest()->getPost()) === false) {
            return $viewModel;
        }

        /* Authentication failed */
        if ($this->loginModel->login(null) === false) {
            $this->loginModel->getFormIdentityElement()->setMessages([$this->translator->translate('User not found')]);
            return $viewModel;
        }
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
     * Logout
     * @return Response
     */
    public function logoutAction()
    {
        $this->loginModel->logout();
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
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
            return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
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
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
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

    /**
     * Process forgot password email form
     * @param ViewModel $viewModel
     * @param Parameters $postData
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
     * @param ViewModel $viewModel
     * @param Parameters $postData
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

}
