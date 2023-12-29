<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCommentAssociation implements Migration, CommentExtensionAwareInterface
{
    use CommentExtensionAwareTrait;

    const CALENDAR_EVENT_TABLE = 'oro_calendar_event';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addCalendarEventToComment($schema, $this->commentExtension);
    }

    /**
     * Add calendar event to comment
     */
    public static function addCalendarEventToComment(Schema $schema, CommentExtension $commentExtension)
    {
        if (!$commentExtension->hasCommentAssociation($schema, self::CALENDAR_EVENT_TABLE)) {
            $commentExtension->addCommentAssociation($schema, self::CALENDAR_EVENT_TABLE);
        }
    }
}
