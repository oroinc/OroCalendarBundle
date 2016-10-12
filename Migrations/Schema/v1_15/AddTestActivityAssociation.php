<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;

class AddTestActivityAssociation implements Migration, ActivityExtensionAwareInterface
{
    const CALENDAR_EVENT_TABLE = 'oro_calendar_event';
    const TEST_ACTIVITY_TABLE = 'test_activity_target';

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addTestActivityToCalendarEvent($schema, $this->activityExtension);
    }

    /**
     * Add test activity to calendar event, if installing or migrating in test environment
     *
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    public static function addTestActivityToCalendarEvent(Schema $schema, ActivityExtension $activityExtension)
    {
        if ($schema->hasTable(self::TEST_ACTIVITY_TABLE)) {
            $activityExtension->addActivityAssociation($schema, self::CALENDAR_EVENT_TABLE, self::CALENDAR_EVENT_TABLE, true);
        }
    }
}
