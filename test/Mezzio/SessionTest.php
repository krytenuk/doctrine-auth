<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Mezzio;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Mezzio\NoRolesProvider;
use FwsDoctrineAuth\Mezzio\RolesProviderInterface;
use FwsDoctrineAuth\Mezzio\SessionAuthentication;
use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuthTest\Support\FakeUser;
use FwsDoctrineAuthTest\Support\InMemoryUserProvider;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Authentication\DefaultUser;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\LazySession;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionPersistenceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SessionTest extends TestCase
{
    private FakeUser $user;
    private InMemoryUserProvider $users;

    protected function setUp(): void
    {
        $this->user  = new FakeUser('7', 'user@example.com', 'hash');
        $this->users = new InMemoryUserProvider($this->user);
    }

    private function authentication(?string $loginPath = null, ?RolesProviderInterface $roles = null): SessionAuthentication
    {
        return new SessionAuthentication(
            $this->users,
            $roles ?? new NoRolesProvider(),
            new ResponseFactory(),
            static fn (string $identity, array $roles, array $details): UserInterface
                => new DefaultUser($identity, $roles, $details),
            $loginPath
        );
    }

    /**
     * The session that SessionMiddleware gives to the application.
     */
    private function lazySession(array $data = []): LazySession
    {
        $persistence = new class ($data) implements SessionPersistenceInterface {
            public function __construct(private readonly array $data)
            {
            }

            public function initializeSessionFromRequest(ServerRequestInterface $request): SessionInterface
            {
                return new Session($this->data, 'original-id');
            }

            public function persistSession(SessionInterface $session, ResponseInterface $response): ResponseInterface
            {
                return $response;
            }
        };

        return new LazySession($persistence, new ServerRequest());
    }

    private function request(SessionInterface $session): ServerRequest
    {
        return (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);
    }

    public function testLoginRegeneratesTheSessionAndDropsItsOldContents(): void
    {
        $session = $this->lazySession(['from-before-login' => 'value']);

        $returned = (new SessionLogin($this->users))->login($session, $this->user);

        self::assertSame($session, $returned);
        self::assertTrue($session->isRegenerated());
        self::assertSame([SessionLogin::SESSION_KEY => '7'], $session->toArray());
    }

    /**
     * Session::regenerate() returns a clone, unlike LazySession. Ignoring that would lose the new session.
     */
    public function testLoginWorksWhenRegenerateReturnsANewInstance(): void
    {
        $returned = (new SessionLogin($this->users))->login(new Session(['old' => 'value']), $this->user);

        self::assertTrue($returned->isRegenerated());
        self::assertSame([SessionLogin::SESSION_KEY => '7'], $returned->toArray());
    }

    public function testLogoutRegeneratesTheSessionAndEmptiesIt(): void
    {
        $session = $this->lazySession([SessionLogin::SESSION_KEY => '7', 'other' => 'value']);

        (new SessionLogin($this->users))->logout($session);

        self::assertTrue($session->isRegenerated());
        self::assertSame([], $session->toArray());
    }

    public function testRecognisesALoggedInSession(): void
    {
        $session = $this->lazySession();
        (new SessionLogin($this->users))->login($session, $this->user);

        $user = $this->authentication()->authenticate($this->request($session));

        self::assertSame('user@example.com', $user?->getIdentity());
    }

    public function testRolesComeFromTheProvider(): void
    {
        $roles = new class implements RolesProviderInterface {
            public function getRoles(object $user): array
            {
                return ['admin'];
            }
        };

        $user = $this->authentication(roles: $roles)
            ->authenticate($this->request($this->lazySession([SessionLogin::SESSION_KEY => '7'])));

        self::assertSame(['admin'], [...$user->getRoles()]);
    }

    public function testAnEmptySessionIsNotLoggedIn(): void
    {
        self::assertNull($this->authentication()->authenticate($this->request($this->lazySession())));
    }

    public function testAUserWhoNoLongerExistsIsNotLoggedIn(): void
    {
        $session = $this->lazySession([SessionLogin::SESSION_KEY => '999']);

        self::assertNull($this->authentication()->authenticate($this->request($session)));
    }

    public function testDisablingAUserEndsTheirSessionStraightAway(): void
    {
        $session = $this->lazySession([SessionLogin::SESSION_KEY => '7']);
        self::assertNotNull($this->authentication()->authenticate($this->request($session)));

        $this->user->enabled = false;

        self::assertNull($this->authentication()->authenticate($this->request($session)));
    }

    public function testMissingSessionMiddlewareFailsClearly(): void
    {
        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('SessionMiddleware');

        $this->authentication()->authenticate(new ServerRequest());
    }

    public function testUnauthorizedRequestsGetA401WithoutALoginPath(): void
    {
        self::assertSame(401, $this->authentication()->unauthorizedResponse(new ServerRequest())->getStatusCode());
    }

    public function testUnauthorizedRequestsRedirectToTheLoginPath(): void
    {
        $response = $this->authentication('/login')->unauthorizedResponse(new ServerRequest());

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
    }
}
