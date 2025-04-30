<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\TestAsset;

use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * @method Response getRedirect(AuthUserInterface $identity, bool $getDefault = false)
 * @method bool isIpBlocked()
 * @method bool blockIpAddress(string $emailEntered)
 * @method bool logFailedLoginAttempt(string $emailAddress)
 * @method bool logSuccessfulLogin(AuthUserInterface|null $identity, bool $used2fa)
 * @method ValidateHash validateHash(string $hash = null);
 */
class SampleController extends AbstractActionController implements SampleInterface
{
}
