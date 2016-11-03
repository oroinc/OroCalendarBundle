<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Test\TestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class AjaxCalendarEventControllerTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class], true);
    }
    /**
     * @dataProvider changeInvitationStatusDataProvider
     * @param TestCase $testCase
     */
    public function testChangeInvitationStatus(TestCase $testCase)
    {
        $testCase->execute($this->getActionAssembler(), $this->getContext());
    }

    /**
     * @return array
     */
    public function changeInvitationStatusDataProvider()
    {
        return $this->getTestCaseDataFromYamlConfigFile('change_invitation_status.yml');

    }

    /**
     * {@inheritdoc}
     */
    protected function getTestCaseDataFromYamlConfigFile($filePath, array $filterTestCases = [])
    {
        return parent::getTestCaseDataFromYamlConfigFile(
            implode(DIRECTORY_SEPARATOR, [__DIR__, 'test_cases', 'ajax_calendar_event_controller', $filePath]),
            $filterTestCases
        );
    }
}
