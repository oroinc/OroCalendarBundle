<?php

namespace Oro\Bundle\CalendarBundle\Test;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;
use Oro\Bundle\CalendarBundle\Test\Action\ActionAssembler;

/**
 * @internal
 */
class TestCase
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $sourceFileName;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var TestCaseStep[]
     */
    protected $steps = [];

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return TestCase
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TestCase
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourceFileName()
    {
        return $this->sourceFileName;
    }

    /**
     * @param string $sourceFileName
     * @return TestCase
     */
    public function setSourceFileName($sourceFileName)
    {
        $this->sourceFileName = $sourceFileName;
        return $this;
    }

    /**
     * Returns a string in format "%file_name%:%line_number%" if file name and line number is available.
     *
     * @return string|null
     */
    public function getSourceFileLocator()
    {
        $result = null;

        $fileName = $this->getSourceFileName();

        if (!$fileName) {
            return $result;
        }

        $lineNumber = $this->getSourceFileLineNumber();
        $lineNumber = $lineNumber ? : 0;

        return sprintf('%s:%s', $fileName, $lineNumber);
    }

    /**
     * @return int|null
     */
    public function getSourceFileLineNumber()
    {
        $result = null;

        $fileName = $this->getSourceFileName();
        if (!$fileName || !file_exists($fileName)) {
            return $result;
        }
        $lines = file($fileName);

        foreach ($lines as $number => $line) {
            if (preg_match("/^\\s+({$this->getName()})\\s*\\:/", $line)) {
                $result = $number;
                break;
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return TestCase
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return TestCaseStep[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param TestCaseStep[] $steps
     * @return TestCase
     */
    public function setSteps(array $steps)
    {
        $this->steps = $steps;
        return $this;
    }

    /**
     * @param TestCaseStep $step
     * @return TestCaseStep
     */
    public function addStep(TestCaseStep $step)
    {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * Execute all steps.
     *
     * @param ActionAssembler $actionAssembler
     * @param ContextInterface $context
     * @throws \PHPUnit_Framework_Exception
     */
    public function execute(ActionAssembler $actionAssembler, ContextInterface $context)
    {
        foreach ($this->getSteps() as $step) {
            try {
                $step->execute($actionAssembler, $context);
            } catch (\Exception $exception) {
                $message = <<<MSG
Test case failed.
Title: {$this->getTitle()}
Name: {$this->getName()}
MSG;
                $sourceFileLocator = $this->getSourceFileLocator();
                if ($sourceFileLocator) {
                    $message .= PHP_EOL . 'Source: ' . $sourceFileLocator;
                }

                if ($this->getDescription()) {
                    $message .= PHP_EOL . 'Description:' . PHP_EOL . $this->getDescription();
                }

                $message .= PHP_EOL;

                throw new \PHPUnit_Framework_Exception($message, null, $exception);
            }
        }
    }

    /**
     * @param array $config
     * @return TestCase
     */
    public static function createFromConfig(array $config)
    {
        $result = new static();

        if (isset($config['name'])) {
            $result->setName($config['name']);
        }

        if (isset($config['title'])) {
            $result->setTitle($config['title']);
        }

        if (isset($config['description'])) {
            $result->setDescription($config['description']);
        }

        if (isset($config['steps'])) {
            foreach ($config['steps'] as $stepIndex => $stepConfig) {
                $step = TestCaseStep::createFromConfig($stepConfig)->setIndex($stepIndex + 1);
                $result->addStep($step);
            }
        }

        return $result;
    }

    /**
     * @param array $config
     * @return TestCase[]
     */
    public static function createListFromConfig(array $config)
    {
        $result = [];

        foreach ($config['test_cases'] as $index => $config) {
            $testCase = TestCase::createFromConfig($config)->setName($index);
            $result[$index] = $testCase;
        }

        return $result;
    }

    /**
     * @param string $filePath
     * @return array Array of arrays with TestCases
     */
    public static function createListFromYmlFile($filePath)
    {
        $result = TestCase::createListFromConfig(Yaml::parse($filePath));

        foreach ($result as $testCase) {
            $testCase->setSourceFileName($filePath);
        }

        return $result;
    }
}
