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
Now compatable with PHP 8.0+