<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\CalendarBundle\Entity\Calendar as BaseCalendar;

class Calendar extends BaseCalendar
{
    /**
     * @param integer|null $id
     */
    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
    }
}
