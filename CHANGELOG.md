0.1.11

Fixed bug in reset password model
Removed dependency on jQuery

0.1.14
Added unique constraint to password reminder entity
Added index annotations on entities
Added use statement for DoctrineAuthException in ForgotPasswordModelFactory
Set strict comparisons in ACL and Navigation Listener
Added use statement for FwsDoctrineAuth\Entity\BaseUsers to AuthListener and NavigationListener

0.1.15
Fixed issue where Auth listener was causing phpunit to fail silently

0.1.16
Various updates
Now using doctrine/doctrine-orm-module v4

0.1.17
Changed requirement to PHP 7.1 or greater
Added PHP 7 type hinting to doctrine entities
Fixed auto-login after registration bug
Refactored code to improve readability and performance

0.2.0
Changed BaseUsers entity nullable annotation to false in emailAddress and password fields
Now compatable with PHP 8.0 & 8.1
Changed abandoned Container Interop package for PSR Container

0.2.1
Changed user active to accept boolean or integer value
Added user active getter so Doctrine object and Laminas class methods hydration work correctly

0.2.2
Allow passwords to be null

0.2.3
Updated dependancy versions

0.3.0
Added two factor authentication
Added user database encryption
Added max login attempts and user block entity
Added login log entity
Added translation config entry

0.3.1
Fixed bug with selecting Google 2FA

0.3.2
Fixed bug where Google 2FA did not work with leading zero

0.3.3
Fixed command line error when no encryption set
Changed email/sms code type to string

0.3.4 Changed auto generated id's to unsigned int (may break database)

0.3.5 Changed type hint for PasswordReminder::setDateCreated() to DateTimeInterface
