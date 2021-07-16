<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Abstract class for converters of calendar events.
 */
abstract class AbstractCalendarEventNormalizer
{
    /**
     * @var CalendarEventManager
     */
    protected $calendarEventManager;

    /**
     * @var AttendeeManager
     */
    protected $attendeeManager;

    /**
     * @var ReminderManager
     */
    protected $reminderManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var HtmlTagHelper
     */
    protected $htmlTagHelper;

    /**
     * @var array
     */
    protected $currentCalendarEventIds;

    /**
     * @var integer
     */
    protected $currentCalendarId;

    /**
     * @var array
     */
    protected $currentAttendeeLists;

    public function __construct(
        CalendarEventManager $calendarEventManager,
        AttendeeManager $attendeeManager,
        ReminderManager $reminderManager,
        AuthorizationCheckerInterface $authorizationChecker,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->calendarEventManager = $calendarEventManager;
        $this->attendeeManager = $attendeeManager;
        $this->reminderManager = $reminderManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * Converts calendar events returned by the given query to form that can be used in API
     *
     * @param int $calendarId The target calendar id
     * @param AbstractQuery $query The query that should be used to get events
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, AbstractQuery $query)
    {
        $rawData = $query->getArrayResult();

        $result = $this->transformList($rawData);
        $this->applyListData($result, $calendarId);

        return $result;
    }

    /**
     * Converts values of entity fields to form that can be used in API
     *
     * @param array $rawData List of raw data of calendar events returned by the query.
     *
     * @return array
     */
    protected function transformList(array $rawData)
    {
        $result = [];

        foreach ($rawData as $rawDataItem) {
            $result[] = $this->transformEntity($rawDataItem);
        }

        return $result;
    }

    /**
     * Converts values of entity fields to form that can be used in API
     *
     * @param array $entity
     *
     * @return array
     */
    protected function transformEntity($entity)
    {
        $result = [];
        foreach ($entity as $field => $value) {
            $this->transformEntityField($value);

            if ($field === 'description') {
                $value = $this->htmlTagHelper->sanitize($value);
            }

            $result[$field] = $value;
        }

        return $result;
    }

    /**
     * Prepares entity field for serialization
     *
     * @param mixed $value
     */
    protected function transformEntityField(&$value)
    {
        if ($value instanceof Proxy && method_exists($value, '__toString')) {
            $value = (string)$value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        } elseif (is_array($value)) {
            $value = $this->transformEntity($value);
        }
    }

    /**
     * Applies data  properties to the given list of calendar events.
     * The list of additional properties depends on a calendar event type.
     *
     * @param array $items Calendar events, each element is a data array.
     * @param int   $calendarId Id of calendar applicable for all events.
     */
    protected function applyListData(&$items, $calendarId)
    {
        $this->beforeApplyListData($items, $calendarId);
        $this->onApplyListData($items);
        $this->afterApplyListData($items);
    }

    /**
     * This method can be used to modify the list data before it will be returned to the client.
     *
     * @param array $items Calendar events, each element is a data array.
     */
    protected function onApplyListData(&$items)
    {
        foreach ($items as &$item) {
            $this->applyItemData($item);
        }
    }

    /**
     * Triggered at the beginning of method applyListData.
     *
     * It can be used to modify the data or initialize state related to the list of calendar events.
     *
     * @param array $items Calendar events, each element is a data array.
     * @param integer $calendarId
     */
    protected function beforeApplyListData(array &$items, $calendarId)
    {
        $this->initCurrentListData($items, $calendarId);
    }

    /**
     * Triggered at the end of method applyListData.
     *
     * It can be used to modify the data or reset state related to the list of calendar events.
     *
     * @param array $items Calendar events, each element is a data array.
     */
    protected function afterApplyListData(array &$items)
    {
        $this->applyListReminders($items);
        $this->resetCurrentListData();
    }

    /**
     * Initiates state related to current list of calendar events data.
     *
     * @param array $items
     * @param int $calendarId
     */
    protected function initCurrentListData(array $items, $calendarId)
    {
        $this->currentCalendarEventIds = $this->getCalendarEventIds($items);
        $this->currentCalendarId = $calendarId;
        $this->currentAttendeeLists = null;
    }

    /**
     * Returns ids of calendar events in the given list.
     *
     * @param array $items Element of array represents an event and should have key with name "id".
     * @return array
     */
    protected function getCalendarEventIds(array $items)
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = $item['id'];
        }

        return $result;
    }

    /**
     * Adds data of reminders related data to the list of calendar events data.
     *
     * @param array $items List of calendar events data, each element is an array representing calendar event data.
     */
    protected function applyListReminders(array &$items)
    {
        $this->reminderManager->applyReminders($items, CalendarEvent::class);
    }

    /**
     * Resets state related to current list of calendar events data.
     */
    protected function resetCurrentListData()
    {
        $this->currentCalendarEventIds = null;
        $this->currentCalendarId = null;
        $this->currentAttendeeLists = null;
    }

    /**
     * Get ids of current calendar events list. Initiated when method applyListData is called.
     *
     * @return array
     */
    protected function getCurrentCalendarEventIds()
    {
        if (null === $this->currentCalendarEventIds) {
            throw new \BadMethodCallException(
                'Calendar event id is not initiated. The method should be called only with method applyListData.'
            );
        }
        return $this->currentCalendarEventIds;
    }

    /**
     * Get id of current calendar. Initiated when method applyListData is called.
     *
     * @return int
     */
    protected function getCurrentCalendarId()
    {
        if (null === $this->currentCalendarEventIds) {
            throw new \BadMethodCallException(
                'Calendar id is not initiated. The method should be called only with method applyListData.'
            );
        }

        return $this->currentCalendarId;
    }

    /**
     * Modifies item in the list of calendar event.
     *
     * This method can be used to adjust the data of calendar event item before it will be returned to the client.
     *
     * @param array $item Calendar event data array.
     */
    protected function applyItemData(array &$item)
    {
        $this->beforeApplyItemData($item);
        $this->onApplyItemData($item);
        $this->afterApplyItemData($item);
    }

    /**
     * Modifies item in the list of calendar event. Called before method onApplyItemData.
     *
     * This method can be used to adjust the data of calendar event item before it will be returned to the client.
     *
     * @param array $item Calendar event data array.
     */
    protected function beforeApplyItemData(array &$item)
    {
    }

    /**
     * Modifies item in the list of calendar event. Called after method beforeApplyItemData and before method
     * afterApplyItemData.
     *
     * This method can be used to adjust the data of calendar event item before it will be returned to the client.
     *
     * @param array $item Calendar event data array.
     */
    protected function onApplyItemData(array &$item)
    {
        $this->applyItemAttendees($item);
        $this->applyItemRecurrence($item);
    }

    /**
     * Modifies item in the list of calendar event. Called after method onApplyItemData.
     *
     * This method can be used to adjust the data of calendar event item before it will be returned to the client.
     *
     * @param array $item Calendar event data array.
     */
    protected function afterApplyItemData(array &$item)
    {
        $this->applyItemPermissionsData($item);
    }

    /**
     * Adds data of attendees to item in the list of calendar events data.
     *
     * @param array $item Calendar events data array.
     */
    protected function applyItemAttendees(array &$item)
    {
        if (!isset($item['attendees'])) {
            // Attendees is not prepared in the item, then it should be fetched and set.
            $attendeeLists = $this->getCurrentAttendeeList();
            $attendees = isset($attendeeLists[$item['id']]) ? $attendeeLists[$item['id']] : [];
            $item['attendees'] = $this->transformEntity($attendees);
        }
        $this->sortAttendees($item['attendees']);
    }

    /**
     * Uses lazy loading to get attendees to the list of current event ids. Values are associated by ids
     *
     * @return array
     */
    protected function getCurrentAttendeeList()
    {
        if (null === $this->currentAttendeeLists) {
            $this->currentAttendeeLists = $this->attendeeManager->getAttendeeListsByCalendarEventIds(
                $this->getCurrentCalendarEventIds()
            );
        }

        return $this->currentAttendeeLists;
    }

    /**
     * Attendees should be returned in a specific order sorting by displayName field.
     */
    protected function sortAttendees(array &$attendees)
    {
        usort(
            $attendees,
            function ($first, $second) {
                return strcmp($first['displayName'], $second['displayName']);
            }
        );
    }

    /**
     * Shrink all fields related to recurrence data into a single field. This operation is required because repository
     * will return a query with all fields of recurrence represented as separate keys.
     *
     * @see \Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository
     *
     * @param array $item Calendar event data as an array.
     */
    protected function applyItemRecurrence(&$item)
    {
        if (isset($item['recurrence'])) {
            // Recurrence field has already a value, no additional action is required
            return;
        }

        $recurrence = [];
        $recurrenceFieldPrefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $isEmpty = true;
        foreach ($item as $field => $value) {
            if (substr($field, 0, strlen($recurrenceFieldPrefix)) === $recurrenceFieldPrefix) {
                unset($item[$field]);
                $recurrence[lcfirst(substr($field, strlen($recurrenceFieldPrefix)))] = $value;
                $isEmpty = empty($value) ? $isEmpty : false;
            }
        }

        if (!$isEmpty) {
            $item['recurrence'] = $recurrence;
        }
    }

    /**
     * Adds permissions data to the item in the list of calendar events data.
     *
     * This method will be called in the last moment when all other data was already prepared.
     *
     * {@see \Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface::getCalendarEvents}
     *
     * @param array $item Calendar events data array.
     */
    abstract protected function applyItemPermissionsData(array &$item);
}
