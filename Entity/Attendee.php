<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents calendar event's attendee and holds information about related user and calendar event.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository")
 * @ORM\Table(name="oro_calendar_event_attendee")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-info-circle"
 *          },
 *          "activity"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @method AbstractEnumValue getType()
 * @method Attendee setType(AbstractEnumValue $value)
 * @method AbstractEnumValue getStatus()
 * @method Attendee setStatus(AbstractEnumValue $value)
 */
class Attendee implements EmailHolderInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    const STATUS_ENUM_CODE = 'ce_attendee_status';
    const TYPE_ENUM_CODE = 'ce_attendee_type';

    const STATUS_NONE = 'none';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_TENTATIVE = 'tentative';

    const TYPE_ORGANIZER = 'organizer';
    const TYPE_OPTIONAL  = 'optional';
    const TYPE_REQUIRED  = 'required';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     */
    protected $displayName;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var CalendarEvent
     *
     * NOTE: The column supports NULL intentionally. Doctrine inserts record into "oro_calendar_event_attendee" first
     * before record of "oro_calendar_event" is inserted, so NULL has to be supported to not trigger DB violatation.
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\CalendarEvent",
     *     inversedBy="attendees"
     * )
     * @ORM\JoinColumn(name="calendar_event_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $calendarEvent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

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

        return $status ? $status->getId() : Attendee::STATUS_NONE;
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
     * @param User $user
     *
     * @return Attendee
     */
    public function setUser(User $user = null)
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
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Invoked before the entity is updated.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
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
    public function isUserEqual(User $user = null)
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
