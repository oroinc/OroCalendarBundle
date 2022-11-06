<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractValidationErrorTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The test covers validation errors triggered in calendar events form available on separate page.
 *
 * Operations covered:
 * - create new event with invalid data required data
 *
 * Resources used:
 * - create event (oro_calendar_event_create)
 */
class ValidationFailedTest extends AbstractValidationErrorTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Create recurring calendar event with invalid fields of recurrence.
     *
     * Verify expected validation errors of recurrence fields in the response.
     *
     * @dataProvider recurrenceValidationFailedDataProvider
     */
    public function testRecurrenceValidationFailed(array $recurrence, array $errors)
    {
        $formData = [
            'title' => 'Recurring event',
            'start' => '2016-10-14T22:00:00+00:00',
            'end' => '2016-10-14T23:00:00+00:00',
            'recurrence' => $recurrence,
            'calendar'  => 1
        ];

        // Request is made directly without using crawler to be able to pass invalid values to the form.
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_calendar_event_create'),
            [
                'oro_calendar_event_form' => $formData
            ],
            [],
            []
        );

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $calendarEvent = $this->getEntityRepository(CalendarEvent::class)
            ->findOneBy(['title' => $formData['title']]);
        $this->assertNull($calendarEvent, 'Failed asserting the event was not created due to validation error.');

        $regularFieldsValidationErrors = $this->getFormFieldsValidationErrors(
            $crawler,
            'oro_calendar_event_form',
            'oro_calendar_event_form_',
            [
                'title',
                'description',
                'start',
                'end',
                'allDay',
                'backgroundColor',
            ]
        );

        $this->assertEmpty(
            $regularFieldsValidationErrors,
            'Failed asserting regular fields of event don\'t have validation errors.'
        );

        $recurrenceFieldsValidationErrors = $this->getRecurrenceErrors($crawler);

        $this->sortArrayByKeyRecursively($recurrenceFieldsValidationErrors);
        $this->sortArrayByKeyRecursively($errors);

        $this->assertEquals(
            $errors,
            $recurrenceFieldsValidationErrors,
            'Failed asserting recurrence validation errors are expected.'
        );
    }

    /**
     * Returns a list of errors in the form found for given $fieldNames
     */
    private function getFormFieldsValidationErrors(
        Crawler $crawler,
        string $formId,
        string $fieldIdPrefix,
        array $fieldNames
    ): array {
        $result = [];

        foreach ($fieldNames as $fieldName) {
            $fieldIdContains = $fieldIdPrefix . $fieldName;
            $errors = $this->getFormFieldValidationErrors($crawler, $formId, $fieldIdContains);
            if ($errors) {
                $result[$fieldName] = $errors;
            }
        }

        return $result;
    }

    /**
     * Returns array of validation error of the field.
     */
    private function getFormFieldValidationErrors(
        Crawler $crawler,
        string $formIdContains,
        string $fieldIdContains
    ): array {
        $formXPath = sprintf('//form[contains(@id, "%s")]', $formIdContains);

        $fieldLabelXPath = sprintf(
            '//div[contains(@class, "control-label")]/label[contains(@for, "%s")]',
            $fieldIdContains
        );

        $fieldLabelCrawler = $crawler->filterXPath($formXPath . $fieldLabelXPath);

        if (1 !== $fieldLabelCrawler->count()) {
            $this->fail(
                sprintf(
                    'Failed to find field with id containing "%s" of form with id containing "%s"',
                    $fieldIdContains,
                    $formIdContains
                )
            );
        }

        $validationErrorsXPath = $formXPath . $fieldLabelXPath .
            '/../../div[contains(@class, "validation-error")]/span[contains(@class, "validation-failed")]';

        $validationErrorsCrawler = $crawler->filterXPath($validationErrorsXPath);

        $result = [];

        $validationErrorsCrawler->each(
            function (Crawler $validationErrorNode) use (&$result) {
                $result[] = $validationErrorNode->text();
            }
        );

        return $result;
    }

    /**
     * Returns array of validation errors of the 'recurrence' fields.
     */
    private function getRecurrenceErrors(Crawler $crawler): array
    {
        $component = 'orocalendar/js/app/components/calendar-event-recurrence-component';
        $errorsXPath = sprintf(
            '//div[contains(@data-page-component-module, "%s")]/@data-page-component-options',
            $component
        );

        $componentOptions = json_decode($crawler->filterXPath($errorsXPath)->text(), true, 512, JSON_THROW_ON_ERROR);

        $errors = $componentOptions['errors'];

        if (count($errors) == 0) {
            return [];
        }

        $result = [];
        foreach ($errors as $error) {
            $result[$error['name']] = $error['messages'];
        }

        return $result;
    }
}
