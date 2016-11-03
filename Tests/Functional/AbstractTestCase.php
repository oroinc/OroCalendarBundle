<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Test\Action\ActionAssembler;
use Oro\Bundle\CalendarBundle\Test\Context\Context;
use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;

use Oro\Bundle\CalendarBundle\Test\TestCase;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AbstractTestCase extends WebTestCase
{
    /**
     * @var ActionAssembler
     */
    protected $actionAssembler;

    /**
     * @var ContextInterface
     */
    protected $context;

    protected function setUp()
    {
        $this->initClient([]);
        $this->startTransaction();
    }

    protected function tearDown()
    {
        $this->rollbackTransaction();
    }

    /**
     * @return ContextInterface
     */
    protected function getContext()
    {
        if (!$this->context) {
            $this->context = new Context(
                $this->getContainer(),
                $this->getClient(),
                $this->getReferenceRepository()
            );
        }
        return $this->context;
    }

    /**
     * @return ActionAssembler
     */
    protected function getActionAssembler()
    {
        if (!$this->actionAssembler) {
            $this->actionAssembler = ActionAssembler::create($this->getContext());
        }

        return $this->actionAssembler;
    }

    /**
     * @param string $filePath
     * @param array $filterTestCases List of test cases names to filter
     * @return array Array of arrays with TestCases
     */
    protected function getTestCaseDataFromYamlConfigFile($filePath, array $filterTestCases = [])
    {
        self::assertFileExists($filePath);

        $result = TestCase::createListFromYmlFile($filePath);

        if ($filterTestCases) {
            $result = array_filter(
                $result,
                function (TestCase $testCase) use ($filterTestCases) {
                    return in_array($testCase->getName(), $filterTestCases);
                }
            );
        }

        foreach ($result as $name => $value) {
            $result[$name] = [$value];
        }

        return $result;
    }
}
