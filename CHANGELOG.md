### 0.1.11
Fixed bug in reset password model  
Removed dependency on jQuery  

#### 0.1.14
Added unique constraint to password reminder entity  
Added index annotations on entities  
Added use statement for DoctrineAuthException in ForgotPasswordModelFactory  
Set strict comparisons in ACL and Navigation Listener  
Added use statement for FwsDoctrineAuth\Entity\BaseUser to AuthListener and NavigationListener

#### 0.1.15
Fixed issue where Auth listener was causing phpunit to fail silently  

#### 0.1.16
Various updates  
Now using doctrine/doctrine-orm-module v4  

#### 0.1.17
Changed requirement to PHP 7.1 or greater  
Added PHP 7 type hinting to doctrine entities  
Fixed auto-login after registration bug  
Refactored code to improve readability and performance  

### 0.2.0
Changed BaseUser entity nullable annotation to false in emailAddress and password fields  
Now compatible with PHP 8.0 & 8.1  
Changed abandoned Container Interop package for PSR Container  

#### 0.2.1
Changed user active to accept boolean or integer value
Added user active getter so Doctrine object and Laminas class methods hydration work correctly  

#### 0.2.2
Allow passwords to be null  

#### 0.2.3
Updated dependency versions  

#### 0.3.0
Added two factor authentication  
Added user database encryption  
Added max login attempts and user block entity  
Added login log entity  
Added translation config entry  

#### 0.3.1
Fixed bug with selecting Google 2FA  

#### 0.3.2
Fixed bug where Google 2FA did not work with leading zero  

#### 0.3.3
Fixed command line error when no encryption set  
Changed email/sms code type to string  

#### 0.3.4
Changed auto generated id's to unsigned int (may break database)

### 1.0.0
Changed minimum PHP version to 8.1
Removed support for Doctrine Module v4  
Updated to first major release, about time! :)  
Moved database encryption to separate module  
Changed type hint for PasswordReminder::setDateCreated() to DateTimeInterface  
Set emailAddress in BaseUser entity to unique  
added .gitignore  
Added catch to clearEntityManagerMethod in AbstractModel  
Changed $defaultRole and $defaultRegistrationRole to nullable and set null by default  
Corrected various type hints and updates various doc blocks  
Changed TwoFactorAuthModel::GOOGLEAUTHENTICATOR constant to TwoFactorAuthModel::AUTHENTICATOR_APP  
Removed $encrypted property from BaseUser  
Split 2FA authentication methods into individual client classes 
Changed Doctrine entity names from plural to singular
TwoFactorAuthMethod::getGoogleAuth() has been deprecated
TwoFactorAuthMethod::setGoogleAuth() has been deprecated
Now using [Doctrine Module version 6](https://github.com/doctrine/DoctrineModule)

#### Configuration
**Added**
- doctrineAuth.emailSubject

**Changed**
- doctrineAuth.allowedTwoFactorAuthenticationMethods to doctrineAuth.allowedTwoFactorAuthenticationAdaptors
- doctrineAuthAcl.resources doctrine resource entry from
```
  [
    'module' => 'doctrine-auth',
      'controllers' => [
          FwsDoctrineAuth\Controller\IndexController::class,
    ],
],
```
to
```
[
    'module' => 'doctrine-auth',
      'controllers' => [
          FwsDoctrineAuth\Controller\LoginController::class,
          FwsDoctrineAuth\Controller\SetTwoFactorAuthenticationController::class,
          FwsDoctrineAuth\Controller\TwoFactorAuthenticationController::class,
    ],
],
```
- doctrineAuthAcl.permissions doctrine permissions entries from
```
[
    'type' => LaminasAcl::TYPE_ALLOW,
    'role' => 'guest',
    'resource' => 'doctrine-auth', // allow guest access to login
    'actions' => [],
],
[
    'type' => LaminasAcl::TYPE_DENY,
    'role' => 'guest',
    'resource' => FwsDoctrineAuth\Controller\IndexController::class, // deny guest to logout (guests have not yet logged in!)
    'actions' => ['logout'],
],
[
    'type' => LaminasAcl::TYPE_DENY,
    'role' => 'guest',
    'resource' => FwsDoctrineAuth\Controller\IndexController::class, // deny guest access to select two factor authentication
    'actions' => ['select-two-factor-authentication', 'set-google-authentication'],
],
[
    'type' => LaminasAcl::TYPE_ALLOW,
    'role' => 'user', // <- change if required
    'resource' => FwsDoctrineAuth\Controller\IndexController::class, // allow users & admins etc to select two factor authentication
    'actions' => ['select-two-factor-authentication', 'set-google-authentication'],
],
[
    'type' => LaminasAcl::TYPE_ALLOW,
    'role' => 'user', // <- change if required
    'resource' => FwsDoctrineAuth\Controller\IndexController::class, // allow users & admins etc to logout
    'actions' => ['logout'],
],
[
    'type' => LaminasAcl::TYPE_DENY,
    'role' => 'user', // <- change if required
    'resource' => FwsDoctrineAuth\Controller\IndexController::class, // deny users & admins etc to login or register as they already have
    'actions' => ['login', 'register'],
],
```
to
```
[
    'type' => LaminasAcl::TYPE_ALLOW,
    'role' => 'guest',
    'resource' => 'doctrine-auth', // allow guest access to login
    'actions' => [],
],
[
    'type' => LaminasAcl::TYPE_DENY,
    'role' => 'guest',
    'resource' => FwsDoctrineAuth\Controller\LoginController::class, // deny guest to log out (guests have not yet logged in)
    'actions' => ['logout'],
],
[
    'type' => LaminasAcl::TYPE_DENY,
    'role' => 'guest',
    'resource' => FwsDoctrineAuth\Controller\SetTwoFactorAuthenticationController::class, // deny guest access to select two factor authentication
    'actions' => ['select-two-factor-authentication', 'set-google-authentication'],
],
[
    'type' => LaminasAcl::TYPE_ALLOW,
    'role' => 'user', // <- change if required
    'resource' => FwsDoctrineAuth\Controller\SetTwoFactorAuthenticationController::class, // allow users & admins to logout
    'actions' => ['select-two-factor-authentication', 'set-google-authentication'],
],
[
    'type' => LaminasAcl::TYPE_ALLOW,
    'role' => 'user', // <- change if required
    'resource' => FwsDoctrineAuth\Controller\LoginController::class, // allow users & admins to logout
    'actions' => ['logout'],
],
[
    'type' => LaminasAcl::TYPE_DENY,
    'role' => 'user', // <- change if required
    'resource' => FwsDoctrineAuth\Controller\LoginController::class, // deny users & admins to login and register as they already have
    'actions' => ['login', 'register'],
],
```

**TODO**
- document how to change the name of the users table
- Add routes to documentation
- Document CheckHashTrait
- Add Doctrine Auth PWA push authentication app