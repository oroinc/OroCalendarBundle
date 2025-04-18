<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Update type and status for attended data.
 */
class UpdateAttendeeData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return ['Oro\Bundle\CalendarBundle\Migrations\Data\ORM\LoadAttendeeData'];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->updateStatus($manager);
        $this->updateType($manager);
    }

    protected function updateStatus(EntityManagerInterface $em)
    {
        $connection = $em->getConnection();
        if (!in_array(
            'invitation_status',
            array_keys($connection->getSchemaManager()->listTableColumns('oro_calendar_event'))
        )) {
            return;
        }

        $connection->executeQuery(
            <<<SQL
UPDATE
    oro_calendar_event_attendee AS a
SET
    serialized_data = jsonb_set(serialized_data::jsonb, '{status}',
        (SELECT
            CASE
                WHEN ce.invitation_status = 'accepted' OR ce.invitation_status = 'declined' THEN ce.invitation_status
                WHEN ce.invitation_status = 'tentatively_accepted' THEN 'tentative'
                WHEN ce.invitation_status = 'not_responded' THEN 'none'
                ELSE 'accepted'
            END
        FROM
            oro_calendar_event ce
        WHERE
            ce.related_attendee_id = a.id
    )::jsonb)
SQL
        );
        $connection->executeQuery('ALTER TABLE oro_calendar_event DROP COLUMN invitation_status');
    }

    protected function updateType(EntityManagerInterface $em)
    {
        $connection = $em->getConnection();

        $query = <<<SQL
UPDATE
    oro_calendar_event_attendee AS a
SET
  serialized_data = jsonb_set(serialized_data::jsonb, '{type}', (
        SELECT
            CASE
                WHEN ce.parent_id IS NOT NULL THEN 'optional'
                ELSE 'organizer'
            END
        FROM
            oro_calendar_event ce
        WHERE
            ce.related_attendee_id = a.id
    )::jsonb)
SQL;
        $connection->executeQuery($query);
    }
}
