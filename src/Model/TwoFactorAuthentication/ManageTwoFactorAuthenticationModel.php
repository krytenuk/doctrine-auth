<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication;

use DateTime;
use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\GoogleAuth;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\AbstractModel;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Authentication\AuthenticationService;
use Laminas\Validator\Csrf;
use PragmaRX\Google2FA\Google2FA;

/**
 * select2faModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ManageTwoFactorAuthenticationModel extends AbstractModel
{
    use AdaptorTrait;

    private Csrf $csrfValidator;
    private ?AuthUserInterface $identity;
    private ?TwoFactorAuthMethod $methodEntity = null;
    private ?string $methodTitle = null;

    /**
     * Initialize class
     * @param EntityManagerInterface $entityManager
     * @param AuthenticationService $authenticationService
     * @param AuthContainerStorage $authContainerStorage
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManagerInterface       $entityManager,
        protected AuthenticationService        $authenticationService,
        protected AuthContainerStorage         $authContainerStorage,
        protected array                        $config
    )
    {
        $this->initAdaptors();
        $this->identity = $authenticationService->getIdentity();
    }

    /**
     * Check the adapters required fields are not falsy
     * @return string[]
     * @throws DoctrineAuthException
     */
    public function getAllowedAuthenticationMethods(): array
    {
        if (!$this->allowedMethods) {
            throw new DoctrineAuthException('No 2FA methods found');
        }

        $hydrator = new DoctrineHydrator($this->entityManager);
        /**
         * @var AuthUserInterface $identity
         */
        $identity = $this->authenticationService->getIdentity();
        $properties = array_keys($hydrator->extract($identity));
        $returnMethods = [];
        $hash = (new Csrf(['session' => $this->authContainerStorage]))->getHash();
        foreach ($this->allowedMethods as $methodName => $adaptor) {
            if (!method_exists($adaptor, 'getRequiredProperties')) {
                throw new DoctrineAuthException(sprintf('Method getRequiredProperties not found in adaptor %s', $adaptor));
            }

            $missingProperties = [];
            $requiredProperties = (array)$adaptor::getRequiredProperties();
            foreach ($requiredProperties as $requiredProperty) {
                if (!in_array($requiredProperty, $properties)) {
                    $missingProperties[] = $requiredProperty;
                }
            }
            if ($missingProperties) {
                throw new DoctrineAuthException(sprintf(_('Adaptor %s has required properties not found in %s (%s)'),
                    $adaptor,
                    $identity::class,
                    implode(', ', $missingProperties)
                ));
            }
            $adaptor::setHash($hash);
            $returnMethods[$methodName] = [
                'adaptor' => $adaptor,
                'isSet' => $this->identity->hasAuthMethod($methodName),
            ];
        }

        return $returnMethods;
    }

    public function getUser()
    {
        return $this->identity;
    }

    /**
     * Add new authentication method to auth user
     * @param string $method
     * @param array $settings
     * @return bool
     * @throws DoctrineAuthException
     */
    public function addMethod(string $method, array $settings = []): bool
    {
        $allowedMethods = $this->getAllowedAuthenticationMethods();
        if (!array_key_exists($method, $allowedMethods)) {
            return false;
        }

        /* Check if user has method already */
        if ($this->findMethod($method)) {
            return false;
        }

        /* Create user 2FA method */
        $authMethodEntity = new TwoFactorAuthMethod();
        $authMethodEntity
            ->setDateCreated(new DateTime())
            ->setMethod($method)
            ->setUser($this->identity)
            ->setSettings($settings);

        $this->identity->addAuthMethod($authMethodEntity);
        if (!$this->persistEntity($this->entityManager, $this->identity)) {
            $this->identity->removeAuthMethod($authMethodEntity);
            return false;
        }

        if ($this->flushEntityManager($this->entityManager)) {
            $this->updateIdentity();
            $this->methodTitle = $allowedMethods[$method]['adaptor']::getTitle();
            return true;
        }
        return false;
    }

    /**
     * Remove authentication method from auth user
     * @param string $method
     * @return bool
     */
    public function removeMethod(string $method): bool
    {
        $allowedMethods = $this->getAllowedAuthenticationMethods();
        if (!array_key_exists($method, $allowedMethods)) {
            return false;
        }

        $authMethodEntity = $this->findMethod($method);
        /* Check if user has method already */
        if (!$authMethodEntity) {
            return false;
        }

        $authMethodEntity = $this->entityManager->getRepository(TwoFactorAuthMethod::class)->findOneBy(['method' => $method, 'user' => $this->identity]);
        if (!$authMethodEntity) {
            return false;
        }

        $this->identity->removeAuthMethod($authMethodEntity);

        if ($this->flushEntityManager($this->entityManager)) {
            $this->updateIdentity();
            $this->methodTitle = $allowedMethods[$method]['adaptor']::getTitle();
            return true;
        }
        return false;
    }

    public function getMethodTitle(): ?string
    {
        return $this->methodTitle;
    }

    /**
     * Get 2FA method from database and store
     * @param string $method
     * @return TwoFactorAuthMethod|null
     */
    public function findMethod(string $method): ?TwoFactorAuthMethod
    {
        if (!$method) {
            return null;
        }

        $repository = $this->entityManager->getRepository(TwoFactorAuthMethod::class);
        $this->methodEntity = $repository->findOneBy(['method' => $method, 'user' => $this->identity]);
        return $this->methodEntity;
    }

    /**
     * @return TwoFactorAuthMethod|null
     */
    public function getMethod(): ?TwoFactorAuthMethod
    {
        return $this->methodEntity;
    }

    /**
     * Update the stored user identity
     * @return void
     */
    private function updateIdentity(): void
    {
        $this->entityManager->detach($this->identity);
        $this->authenticationService->clearIdentity();
        $this->authenticationService->getStorage()->write($this->identity);
    }

}
