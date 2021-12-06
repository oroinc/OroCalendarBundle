<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadSystemCalendarData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class SystemCalendarEventControllerTest extends WebTestCase
{
    private const TITLE = 'System Calendar Event Title';
    private const DESCRIPTION = 'System Calendar Event Description';

    /** @var ManagerRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadSystemCalendarData::class
            ]
        );

        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testCreateAction()
    {
        /** @var SystemCalendar $systemCalendar */
        $systemCalendar = $this->getReference(LoadSystemCalendarData::SYSTEM_CALENDAR_PUBLIC);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_system_calendar_event_create', ['id' => $systemCalendar->getId()])
        );

        $user1 = $this->getReference('oro_calendar:user:system_user_1');
        $user2 = $this->getReference('oro_calendar:user:system_user_2');

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_calendar_event_form[title]'] = self::TITLE;
        $form['oro_calendar_event_form[description]'] = self::DESCRIPTION;
        $form['oro_calendar_event_form[start]'] = '2018-03-08T12:00:00Z';
        $form['oro_calendar_event_form[end]'] = '2018-03-08T20:00:00Z';
        $form['oro_calendar_event_form[attendees]'] = implode(
            ContextsToViewTransformer::SEPARATOR,
            [
                json_encode(['entityClass' => User::class, 'entityId' => $user1->getId()], JSON_THROW_ON_ERROR),
                json_encode(['entityClass' => User::class, 'entityId' => $user2->getId()], JSON_THROW_ON_ERROR),
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('Calendar event saved', $crawler->html());
        $this->assertCount(2, $this->registry->getRepository(Attendee::class)->findAll());

        $mainEvent = $this->getCalendarEvent();
        $repository = $this->registry->getRepository(CalendarEvent::class);

        $this->assertCount(2, $repository->findBy(['title' => self::TITLE, 'parent' => $mainEvent]));
    }

    /**
     * @depends testCreateAction
     */
    public function testViewAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_system_calendar_event_view', ['id' => $this->getCalendarEvent()->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString(self::TITLE, $crawler->html());
        self::assertStringContainsString(self::DESCRIPTION, $crawler->html());
        self::assertStringContainsString('Mar 8, 2018, 12:00 PM', $crawler->html());
        self::assertStringContainsString('Mar 8, 2018, 8:00 PM', $crawler->html());
    }

    /**
     * @depends testCreateAction
     */
    public function testInfoAction()
    {
        $user1 = $this->getReference('oro_calendar:user:system_user_1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_system_calendar_event_widget_info',
                [
                    'id' => $this->getCalendarEvent()->getId(),
                    'targetActivityClass' => User::class,
                    'targetActivityId' => $user1->getId(),
                    '_wid' => 'test-uuid',
                    '_widgetContainer' => 'dialog',
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString(self::TITLE, $crawler->html());
        self::assertStringContainsString(self::DESCRIPTION, $crawler->html());
        self::assertStringContainsString('Mar 8, 2018, 12:00 PM', $crawler->html());
        self::assertStringContainsString('Mar 8, 2018, 8:00 PM', $crawler->html());
    }

    /**
     * @depends testCreateAction
     */
    public function testUpdateAction()
    {
        $calendarEvent = $this->getCalendarEvent();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_system_calendar_event_update', ['id' => $calendarEvent->getId()])
        );

        $attendees = [];
        foreach ($calendarEvent->getAttendees() as $attendee) {
            $attendees[] = json_encode(
                ['entityClass' => Attendee::class, 'entityId' => $attendee->getId()],
                JSON_THROW_ON_ERROR
            );
        }

        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = 'save_and_close';
        $form['oro_calendar_event_form[title]'] = 'Updated ' . self::TITLE;
        $form['oro_calendar_event_form[description]'] = 'Updated ' . self::DESCRIPTION;
        $form['oro_calendar_event_form[attendees]'] = implode(ContextsToViewTransformer::SEPARATOR, $attendees);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('Calendar event saved', $crawler->html());
        self::assertStringContainsString('Updated ' . self::TITLE, $crawler->html());
        self::assertStringContainsString('Updated ' . self::DESCRIPTION, $crawler->html());
        $this->assertCount(2, $this->registry->getRepository(Attendee::class)->findAll());
    }

    private function getCalendarEvent(): CalendarEvent
    {
        $calendarEvent = $this->registry->getRepository(CalendarEvent::class)
            ->findOneBy(['title' => self::TITLE, 'parent' => null]);
        $this->assertNotNull($calendarEvent);

        return $calendarEvent;
    }
}
