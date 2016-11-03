<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Test\TestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

class SimpleEventTestCasesTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class], true);
    }

    /**
     * @dataProvider basicCrudDataProvider
     * @param TestCase $testCase
     */
    public function testBasicCrud(TestCase $testCase)
    {
        $testCase->execute($this->getActionAssembler(), $this->getContext());
    }

    /**
     * @return array
     */
    public function basicCrudDataProvider()
    {
        return $this->getTestCaseDataFromYamlConfigFile('basic_crud.yml');
    }

    /**
     * @dataProvider basicAttendeeDataProvider
     * @param TestCase $testCase
     */
    public function testBasicAttendee(TestCase $testCase)
    {
        $testCase->execute($this->getActionAssembler(), $this->getContext());
    }

    /**
     * @return array
     */
    public function basicAttendeeDataProvider()
    {
        return $this->getTestCaseDataFromYamlConfigFile('basic_attendee.yml');

    }

    /**
     * @dataProvider basicInvitationStatusDataProvider
     * @param TestCase $testCase
     */
    public function testBasicInvitationStatus(TestCase $testCase)
    {
        $testCase->execute($this->getActionAssembler(), $this->getContext());
    }

    /**
     * @return array
     */
    public function basicInvitationStatusDataProvider()
    {
        return $this->getTestCaseDataFromYamlConfigFile('basic_invitation_status.yml');

    }

    /**
     * {@inheritdoc}
     */
    protected function getTestCaseDataFromYamlConfigFile($filePath, array $filterTestCases = [])
    {
        return parent::getTestCaseDataFromYamlConfigFile(
            implode(DIRECTORY_SEPARATOR, [__DIR__, 'test_cases', 'simple_event', $filePath]),
            $filterTestCases
        );
    }
}
