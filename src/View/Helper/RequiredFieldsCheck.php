<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\View\Helper;

use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\View\Helper\AbstractHelper;

use function method_exists;
use function sprintf;
use function ucfirst;

class RequiredFieldsCheck extends AbstractHelper
{
    public function __invoke(AuthUserInterface|null $identity, string|null $adaptor = null)
    {
    }

    /**
     * Check the adapters required fields are not falsy
     *
     * @throws DoctrineAuthException
     *
     * @todo Figure out what this is!!
     */
    private function checkAdaptorRequiredFields(AuthUserInterface $authUserEntity, string $adaptor): bool
    {
        $found          = true;
        $requiredFields = $adaptor::getRequiredFields();
        foreach ($requiredFields as $field) {
            $found &= $this->hasValue($authUserEntity, $field);
        }
        return $found;
    }

    /**
     * Get property value of the given entity
     *
     * @throws DoctrineAuthException
     */
    private function hasValue(EntityInterface $entity, string $propertyName): bool
    {
        $getter = 'get' . ucfirst($propertyName);
        if (method_exists($entity, $getter)) {
            return (bool) $entity->$getter;
        }

        $isser = 'is' . ucfirst($propertyName);
        if (method_exists($entity, $isser)) {
            return (bool) $entity->$isser;
        }

        throw new DoctrineAuthException(
            sprintf(
                'Property (%s) in (%s) is not accessible. You should implement %s::%s() or %s::%s()',
                $propertyName,
                $entity::class,
                $entity::class,
                $getter,
                $entity::class,
                $isser
            )
        );
    }
}
