<?php

declare(strict_types=1);

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ConfigBundle\Migration\DeleteConfigQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationsByDomainAndKeyPrefixQuery;

/**
 * Removes entity field and configs of HangoutsCallBundle added fields.
 */
class RemoveHangoutCalendarEventFields implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $calendarEventClass = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';
        $fieldName = 'use_hangout';

        $table = $schema->getTable('oro_calendar_event');

        // Undo $table->addColumn('use_hangout', 'boolean', ....);
        if ($table->hasColumn($fieldName)) {
            $table->dropColumn($fieldName);
        }

        // Cleanup Entity Field configs
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field '
                . 'WHERE entity_id IN (SELECT id FROM oro_entity_config WHERE class_name = :class) '
                . 'AND field_name = :fieldName',
                ['class' => $calendarEventClass, 'fieldName' => $fieldName],
                ['class' => Types::STRING, 'fieldName' => Types::STRING]
            )
        );

        // Cleanup Translations
        $queries->addQuery(
            new DeleteTranslationsByDomainAndKeyPrefixQuery('jsmessages', 'oro.hangoutscall.')
        );
        $queries->addQuery(
            new DeleteTranslationsByDomainAndKeyPrefixQuery('messages', 'oro.hangoutscall.')
        );
        $queries->addQuery(new DeleteTranslationKeysQuery('messages', [
            'oro.calendar.calendarevent.use_hangout.label',
            'oro.calendar.calendarevent.use_hangout.tooltip',
        ]));

        // Cleanup System configuration
        $queries->addQuery(new DeleteConfigQuery('enable_google_hangouts_for_email', 'oro_hangouts_call'));
        $queries->addQuery(new DeleteConfigQuery('enable_google_hangouts_for_phone', 'oro_hangouts_call'));
    }
}
