<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mezzio;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\User\UserProviderInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_string;

/**
 * Lets mezzio-authentication's AuthenticationMiddleware recognise users logged in through SessionLogin.
 *
 * The user is loaded on every request, so disabling an account or changing its password ends its sessions straight away.
 */
final class SessionAuthentication implements AuthenticationInterface
{
    /**
     * @param callable(string, array, array): UserInterface $userFactory
     * @param string|null $loginPath Where to redirect unauthenticated requests, or null to respond with a 401
     */
    public function __construct(
        private readonly UserProviderInterface $users,
        private readonly SessionLogin $sessionLogin,
        private readonly RolesProviderInterface $roles,
        private readonly ResponseFactoryInterface $responseFactory,
        private $userFactory,
        private readonly ?string $loginPath = null,
    ) {
    }

    public function authenticate(ServerRequestInterface $request): ?UserInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (! $session instanceof SessionInterface) {
            throw new DoctrineAuthException('SessionMiddleware must run before the authentication middleware');
        }

        $id = $session->get(SessionLogin::SESSION_KEY);
        if (! is_string($id)) {
            return null;
        }

        $user = $this->users->findById($id);
        if ($user === null || ! $this->users->isEnabled($user) || ! $this->sessionLogin->isCurrent($session, $user)) {
            return null;
        }

        return ($this->userFactory)($this->users->getIdentity($user), $this->roles->getRoles($user), []);
    }

    public function unauthorizedResponse(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->loginPath === null) {
            return $this->responseFactory->createResponse(401);
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $this->loginPath);
    }
}
