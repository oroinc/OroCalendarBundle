<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateFormTypeForExtendDescription implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                SystemCalendar::class,
                'extend_description',
                'form',
                'type',
                OroResizeableRichTextType::class,
                'oro_resizeable_rich_text'
            )
        );
    }
}
