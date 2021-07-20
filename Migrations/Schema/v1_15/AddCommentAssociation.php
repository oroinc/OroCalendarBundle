<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtension;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCommentAssociation implements Migration, CommentExtensionAwareInterface
{
    const CALENDAR_EVENT_TABLE = 'oro_calendar_event';

    /** @var CommentExtension */
    protected $commentExtension;

    public function setCommentExtension(CommentExtension $commentExtension)
    {
        $this->commentExtension = $commentExtension;
    }

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
