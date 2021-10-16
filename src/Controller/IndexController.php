<?php

namespace FwsDoctrineAuth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use FwsDoctrineAuth\Model;
use Laminas\Mvc\I18n\Translator;
use FwsDoctrineAuth\Model\ForgotPasswordModel;

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
     * @return ViewModel
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
    }

    /**
     * Login user
     * 
     * @return ViewModel
     */
    public function loginAction()
    {
        $viewModel = new ViewModel();
        $viewModel->config = $this->loginModel->getConfig();
        if ($this->getRequest()->isPost()) {
            if ($this->loginModel->processForm($this->getRequest()->getPost())) {
                if ($this->loginModel->login()) {
                    if ($this->loginModel->hasRedirect() && $this->loginModel->canRedirect()) {
                        return $this->redirect()->toUrl($this->loginModel->getRedirectUrl());
                    } else {
                        $redirect = $this->loginModel->getDefaultRedirect();
                        return $this->redirect()->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
                    }
                } else {
                    $this->loginModel->getFormIdentityElement()->setMessages([$this->translator->translate('User not found')]);
                }
            }
        }

        $viewModel->form = $this->loginModel->getForm();
        $viewModel->useForgotPassword = $this->loginModel->useForgotPassword();
        return $viewModel;
    }

    public function logoutAction()
    {
        $this->loginModel->logout();
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
    }

    public function registerAction()
    {
        if ($this->registerModel->allowRegistration()) {
            $viewModel = new ViewModel();
            $viewModel->config = $this->registerModel->getConfig();
            $viewModel->config = $this->forgotPasswordModel->getConfig();
            if ($this->getRequest()->isPost()) {
                if ($this->registerModel->processForm($this->getRequest()->getPost())) {
                    if ($this->registerModel->autoLogin()) {
                        if ($this->registerModel->login()) {
                            $redirect = $this->registerModel->getDefaultRedirect();
                            return $this->redirect()->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
                        }
                    }
                    return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
                } else {
                    if ($this->registerModel->getForm()->isValid()) {
                        $viewModel->errorMessage = 'Unable to register you at this time, please try again later.';
                    } else {
                        $viewModel->errorMessage = 'There is a problem with the form you submitted, please correct errors highlighted.';
                    }
                }
            }
            $viewModel->form = $this->registerModel->getForm();
            return $viewModel;
        }
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
    }

    public function passwordResetAction()
    {
        $viewModel = new ViewModel();
        $viewModel->config = $this->forgotPasswordModel->getConfig();
        $request = $this->getRequest();
        if ($code = $this->params()->fromRoute('code', FALSE)) {
            $viewModel->code = $code;
            if ($request->isPost()) {
                $postData = $request->getPost();
                if ($this->forgotPasswordModel->findUser($code)) {
                    if ($this->forgotPasswordModel->processResetForm($postData)) {
                        $viewModel->passwordReset = TRUE;
                    } else {
                        $viewModel->passwordReset = FALSE;
                    }
                } else {
                    $viewModel->invalidLink = TRUE;
                }
            } else {
                if ($this->forgotPasswordModel->findUser($code)) {
                    $viewModel->resetForm = $this->forgotPasswordModel->getResetPasswordForm();
                } else {
                    $viewModel->invalidLink = TRUE;
                }
            }
            return $viewModel;
        } else {
            if ($request->isPost()) {
                $postData = $request->getPost();
                if ($this->forgotPasswordModel->processEmailForm($postData)) {
                    if ($this->forgotPasswordModel->sendEmail()) {
                        $viewModel->emailSent = TRUE;
                        return $viewModel;
                    }
                } else {
                    if ($this->forgotPasswordModel->isFormValid()) {
                        $viewModel->emailSent = FALSE;
                        return $viewModel;
                    }
                }
            }
            $viewModel->emailForm = $this->forgotPasswordModel->getEmailForm();
        }
        return $viewModel;
    }

}
