<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareInterface;
use Oro\Bundle\CommentBundle\Migration\Extension\CommentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCommentAssociation implements Migration, CommentExtensionAwareInterface
{
    use CommentExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$this->commentExtension->hasCommentAssociation($schema, 'oro_calendar_event')) {
            $this->commentExtension->addCommentAssociation($schema, 'oro_calendar_event');
        }
    }
}
