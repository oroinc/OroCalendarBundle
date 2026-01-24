<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Provides common functionality for calendar data providers.
 *
 * This base class offers helper methods for retrieving and filtering entity fields
 * that are supported by calendar implementations. Subclasses should implement the
 * {@see CalendarProviderInterface} methods to provide calendar-specific data.
 */
abstract class AbstractCalendarProvider implements CalendarProviderInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function getSupportedFields($className)
    {
        $classMetadata = $this->doctrineHelper->getEntityMetadata($className);

        return $classMetadata->fieldNames;
    }

    /**
     * @param        $extraFields
     *
     * @param string $class
     *
     * @return array
     */
    protected function filterSupportedFields($extraFields, $class)
    {
        $extraFields = !empty($extraFields)
            ? array_intersect($extraFields, $this->getSupportedFields($class))
            : [];

        return $extraFields;
    }
}
