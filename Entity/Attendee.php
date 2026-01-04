<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCalendarBundle_Entity_Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents calendar event's attendee and holds information about related user and calendar event.
 *
 * @method EnumOptionInterface getType()
 * @method Attendee setType(EnumOptionInterface $value)
 * @method EnumOptionInterface getStatus()
 * @method Attendee setStatus(EnumOptionInterface $value)
 * @mixin OroCalendarBundle_Entity_Attendee
 */
#[ORM\Entity(repositoryClass: AttendeeRepository::class)]
#[ORM\Table(name: 'oro_calendar_event_attendee')]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-info-circle'], 'activity' => ['immutable' => true]])]
class Attendee implements EmailHolderInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const STATUS_ENUM_CODE = 'ce_attendee_status';
    public const TYPE_ENUM_CODE = 'ce_attendee_type';

    public const STATUS_NONE = 'none';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_TENTATIVE = 'tentative';

    public const TYPE_ORGANIZER = 'organizer';
    public const TYPE_OPTIONAL  = 'optional';
    public const TYPE_REQUIRED  = 'required';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(name: 'display_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $displayName = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    /**
     * NOTE: The column supports NULL intentionally. Doctrine inserts record into "oro_calendar_event_attendee" first
     * before record of "oro_calendar_event" is inserted, so NULL has to be supported to not trigger DB violatation.
     */
    #[ORM\ManyToOne(targetEntity: CalendarEvent::class, inversedBy: 'attendees')]
    #[ORM\JoinColumn(name: 'calendar_event_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CalendarEvent $calendarEvent = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Returns invitation status code of the attendee based on related status. If there is no related status
     * then returns "none" status (@see Attendee::STATUS_NONE).
     *
     * @return string Status id (@see Attendee::STATUS_*)
     */
    public function getStatusCode()
    {
        $status = $this->getStatus();

        return $status ? $status->getInternalId() : Attendee::STATUS_NONE;
    }

    /**
     * @param string $email
     *
     * @return Attendee
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return Attendee
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return Attendee
     */
    public function setUser(?User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return CalendarEvent
     */
    public function getCalendarEvent()
    {
        return $this->calendarEvent;
    }

    /**
     * @param CalendarEvent $calendarEvent
     *
     * @return Attendee
     */
    public function setCalendarEvent(CalendarEvent $calendarEvent)
    {
        $this->calendarEvent = $calendarEvent;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return Attendee
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return Attendee
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Invoked before the entity is updated.
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string) $this->displayName;
    }

    /**
     * Compares instance with another instance. Returns TRUE if instances have the same user, email and display name.
     *
     * @param Attendee|null $other
     *
     * @return bool
     */
    public function isEqual($other)
    {
        if (!$other instanceof Attendee) {
            return false;
        }

        return
            $this->isUserEqual($other->getUser()) &&
            $this->isEmailEqual($other->getEmail()) &&
            0 === strcmp($this->getDisplayName(), $other->getDisplayName());
    }

    /**
     * Will return TRUE if both users exist and if they are referencing the same users or if both users are not exist.
     *
     * @param User|null $user
     * @return bool
     */
    public function isUserEqual(?User $user = null)
    {
        $actualUser = $this->getUser();
        return $actualUser === $user || ($user && $actualUser && $actualUser->getId() == $user->getId());
    }

    /**
     * Case-insensitive compares of attendees email. Will return TRUE if both emails exist and if they are
     * case-insensitive equal or if both emails are not exist.
     *
     * @param string|null $email
     * @return bool
     */
    public function isEmailEqual($email)
    {
        $actualEmail = $this->getEmail();
        return $actualEmail === $email || 0 === strcasecmp($actualEmail, $email);
    }
}
