<?php

namespace FwsDoctrineAuth\View\Helper;

use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\View\Helper\AbstractHelper;

class RequiredFieldsCheck extends AbstractHelper
{
    public function __invoke(?AuthUserInterface $identity, ?string $adaptor = null)
    {

    }



    /**
     * Check the adapters required fields are not falsy
     * @param AuthUserInterface $authUserEntity
     * @param string $adaptor
     * @return bool
     * @throws DoctrineAuthException
     */
    private function checkAdaptorRequiredFields(AuthUserInterface $authUserEntity, string $adaptor): bool
    {
        $found = true;
        $requiredFields = $adaptor::getRequiredFields();
        foreach ($requiredFields as $field) {
            $found = $found & $this->hasValue($authUserEntity, $field);
        }
        return $found;
    }

    /**
     * Get property value of the given entity
     * @param EntityInterface $entity
     * @param string $propertyName
     * @return bool
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
                get_class($entity),
                get_class($entity),
                $getter,
                get_class($entity),
                $isser
            )
        );
    }

}