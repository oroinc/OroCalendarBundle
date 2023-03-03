<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\CalendarBundle\Entity\Attendee as BaseAttendee;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class Attendee extends BaseAttendee
{
    /**
     * @var AbstractEnumValue|null
     */
    protected $status;

    /**
     * @var AbstractEnumValue|null
     */
    protected $type;

    /**
     * @param integer|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @param AbstractEnumValue|null $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return null|AbstractEnumValue
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return null|AbstractEnumValue
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param AbstractEnumValue|null $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
