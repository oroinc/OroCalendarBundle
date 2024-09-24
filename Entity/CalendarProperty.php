<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCalendarBundle_Entity_CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarPropertyRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * This entity is used to store different kind of user's properties for a calendar.
 * The combination of calendarAlias and calendar is unique identifier of a calendar.
 *
 * @mixin OroCalendarBundle_Entity_CalendarProperty
 */
#[ORM\Entity(repositoryClass: CalendarPropertyRepository::class)]
#[ORM\Table(name: 'oro_calendar_property')]
#[ORM\UniqueConstraint(name: 'oro_calendar_prop_uq', columns: ['calendar_alias', 'calendar_id', 'target_calendar_id'])]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-cog'],
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class CalendarProperty implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class)]
    #[ORM\JoinColumn(name: 'target_calendar_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Calendar $targetCalendar = null;

    #[ORM\Column(name: 'calendar_alias', type: Types::STRING, length: 32)]
    protected ?string $calendarAlias = null;

    #[ORM\Column(name: 'calendar_id', type: Types::INTEGER)]
    protected ?int $calendar = null;

    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['default' => 0])]
    protected ?int $position = 0;

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $visible = true;

    #[ORM\Column(name: 'background_color', type: Types::STRING, length: 7, nullable: true)]
    protected ?string $backgroundColor = null;

    /**
     * Gets id of this set of calendar properties.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets user's calendar this set of calendar properties belong to
     *
     * @return Calendar
     */
    public function getTargetCalendar()
    {
        return $this->targetCalendar;
    }

    /**
     * Sets user's calendar this set of calendar properties belong to
     *
     * @param Calendar $targetCalendar
     *
     * @return self
     */
    public function setTargetCalendar($targetCalendar)
    {
        $this->targetCalendar = $targetCalendar;

        return $this;
    }

    /**
     * Gets an alias of the connected calendar
     *
     * @return string
     */
    public function getCalendarAlias()
    {
        return $this->calendarAlias;
    }

    /**
     * Sets an alias of the connected calendar
     *
     * @param string $calendarAlias
     *
     * @return self
     */
    public function setCalendarAlias($calendarAlias)
    {
        $this->calendarAlias = $calendarAlias;

        return $this;
    }

    /**
     * Gets an id of the connected calendar
     *
     * @return int
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets an id of the connected calendar
     *
     * @param int $calendar
     *
     * @return self
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;

        return $this;
    }

    /**
     * Gets a number indicates where the connected calendar should be displayed
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets a number indicates where the connected calendar should be displayed
     *
     * @param int $position
     *
     * @return self
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Gets a property indicates whether events of the connected calendar should be displayed or not
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Sets a property indicates whether events of the connected calendar should be displayed or not
     *
     * @param bool $visible
     *
     * @return self
     */
    public function setVisible($visible)
    {
        $this->visible = (bool)$visible;

        return $this;
    }

    /**
     * Gets a background color of the connected calendar events.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, for example F00 or FF0000 for a red color.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of the connected calendar events.
     *
     * @param string|null $backgroundColor The color in hex format, for example F00 or FF0000 for a red color.
     *                                     Set it to null to allow UI to calculate the background color automatically.
     *
     * @return self
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getId();
    }
}
