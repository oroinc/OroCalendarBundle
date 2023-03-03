<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Base recurrence aware calendar provider.
 */
abstract class AbstractRecurrenceAwareCalendarProvider extends AbstractCalendarProvider
{
    /**
     * @var Model\Recurrence
     */
    protected $recurrenceModel;

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(DoctrineHelper $doctrineHelper, Model\Recurrence $recurrence)
    {
        parent::__construct($doctrineHelper);
        $this->recurrenceModel = $recurrence;
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

    /**
     * Returns transformed and expanded list with respected recurring events based on unprocessed events in $rawItems
     * and date range.
     *
     * @param array $rawItems
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    protected function getExpandedRecurrences(array $rawItems, \DateTime $start, \DateTime $end)
    {
        $regularEvents = $this->filterRegularEvents($rawItems);
        $recurringExceptionEvents = $this->filterRecurringExceptionEvents($rawItems);
        $recurringOccurrenceEvents = $this->filterRecurringOccurrenceEvents($rawItems, $start, $end);

        return $this->mergeRegularAndRecurringEvents(
            $regularEvents,
            $recurringOccurrenceEvents,
            $recurringExceptionEvents
        );
    }

    /**
     * Returns list of all regular and not recurring events. Filters processed events from $items.
     *
     * @param array $items
     *
     * @return array
     */
    protected function filterRegularEvents(array &$items)
    {
        $events = [];
        foreach ($items as $index => $item) {
            if (empty($item['recurrence']) && empty($item['recurringEventId'])) {
                $events[] = $item;
                unset($items[$index]);
            }
        }

        return $events;
    }

    /**
     * Returns list of all events which represent exception of recurring event. Filters processed events from $items.
     *
     * @param array $items
     *
     * @return array
     */
    protected function filterRecurringExceptionEvents(array &$items)
    {
        $exceptions = [];
        foreach ($items as $index => $item) {
            if (empty($item['recurrence']) &&
                !empty($item['recurringEventId']) &&
                !empty($item['originalStart'])
            ) {
                unset($items[$index]);
                $exceptions[] = $item;
            }
        }

        return $exceptions;
    }

    /**
     * For each recurring event creates records representing events of recurring occurrence for [$start, $end] range.
     * Returns merged list of all such events. Filters processed events from $items.
     *
     * @param array $items
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    protected function filterRecurringOccurrenceEvents(array &$items, \DateTime $start, \DateTime $end)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $occurrences = [];
        $dateFields = ['startTime', 'endTime', 'calculatedEndTime'];
        foreach ($items as $index => $item) {
            if (!empty($item['recurrence']) && empty($item['recurringEventId'])) {
                unset($items[$index]);
                $recurrence = new Entity\Recurrence();
                foreach ($item['recurrence'] as $field => $value) {
                    $value = in_array($field, $dateFields, true) && $value !== null
                        ? new \DateTime($value, new \DateTimeZone('UTC'))
                        : $value;
                    if ($field !== 'id') {
                        $propertyAccessor->setValue($recurrence, $field, $value);
                    }
                }
                unset($item['recurrence']['calculatedEndTime']);

                //set timezone for all datetime values,
                // to make sure all occurrences are calculated in the time zone in which the recurrence is created
                $recurrenceTimezone = new \DateTimeZone($recurrence->getTimeZone());
                $recurrence->getStartTime()->setTimezone($recurrenceTimezone);
                $recurrence->getCalculatedEndTime()->setTimezone($recurrenceTimezone);
                $start->setTimezone($recurrenceTimezone);
                $end->setTimezone($recurrenceTimezone);

                $occurrenceDates = $this->recurrenceModel->getOccurrences($recurrence, $start, $end);
                $newStartDate = new \DateTime($item['start']);
                $calendarEvent = new Entity\CalendarEvent();
                $calendarEvent->setStart(clone $newStartDate);
                $recurrence->setCalendarEvent($calendarEvent);
                $newStartDate->setTimezone($recurrenceTimezone);
                $itemEndDate = new \DateTime($item['end']);
                $itemEndDate->setTimezone($recurrenceTimezone);
                $duration = $itemEndDate->diff($newStartDate);
                $timeZone = new \DateTimeZone('UTC');
                foreach ($occurrenceDates as $occurrenceDate) {
                    $newItem = $item;
                    $newStartDate->setTimezone($recurrenceTimezone);
                    $newStartDate->setDate(
                        $occurrenceDate->format('Y'),
                        $occurrenceDate->format('m'),
                        $occurrenceDate->format('d')
                    );
                    $newStartDate->setTimezone($timeZone);
                    $newItem['start'] = $newStartDate->format('c');
                    $newItem['recurrencePattern'] = $this->recurrenceModel->getTextValue($recurrence);
                    $endDate = new \DateTime(
                        sprintf(
                            '+%s minute +%s hour +%s day +%s month +%s year %s',
                            $duration->format('%i'),
                            $duration->format('%h'),
                            $duration->format('%d'),
                            $duration->format('%m'),
                            $duration->format('%y'),
                            $newStartDate->format('c')
                        ),
                        $timeZone
                    );

                    $this->adjustEndDateWithDST($endDate, $newStartDate, $recurrenceTimezone);
                    $newItem['end'] = $endDate->format('c');
                    $newItem['startEditable'] = false;
                    $newItem['durationEditable'] = false;
                    $occurrences[] = $newItem;
                }
            }
        }

        return $occurrences;
    }

    /**
     * Adjusts end date to +/- 1 hour if start and end date are in different daylight saving time
     */
    protected function adjustEndDateWithDST(\DateTime $endDate, \DateTime $startDate, \DateTimeZone $timeZone)
    {
        $end = clone $endDate;
        $start = clone $startDate;

        $end->setTimezone($timeZone);
        $start->setTimezone($timeZone);

        if ($start->format('I') === "0" && $end->format('I') === "1") {
            $endDate->modify('-1 hour');
        } elseif ($start->format('I') === "1" && $end->format('I') === "0") {
            $endDate->modify('+1 hour');
        }
    }

    /**
     * Merges all previously filtered events.
     *
     * Result will contain:
     * $regularEvents + ($recurringOccurrenceEvents - $recurringExceptionEvents) + $recurringExceptionEvents
     *
     * @param array $regularEvents
     * @param array $recurringOccurrenceEvents
     * @param array $recurringExceptionEvents
     *
     * @return array
     */
    protected function mergeRegularAndRecurringEvents(
        array $regularEvents,
        array $recurringOccurrenceEvents,
        array $recurringExceptionEvents
    ) {
        $recurringEvents = [];

        foreach ($recurringOccurrenceEvents as $occurrence) {
            $exceptionFound = false;
            foreach ($recurringExceptionEvents as $key => $exception) {
                $originalStartTime = new \DateTime($exception['originalStart']);
                $start = new \DateTime($occurrence['start']);
                if ((int)$exception['recurringEventId'] === (int)$occurrence['id'] &&
                    $originalStartTime->getTimestamp() === $start->getTimestamp()
                ) {
                    $exceptionFound = true;
                    if (empty($exception['isCancelled'])) {
                        $exception['recurrencePattern'] = $occurrence['recurrencePattern'];
                        $recurringEvents[] = $exception;
                    }
                    unset($recurringExceptionEvents[$key]);
                }
            }

            if (!$exceptionFound) {
                $recurringEvents[] = $occurrence;
            }
        }

        return array_merge($regularEvents, $recurringEvents, $recurringExceptionEvents);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
