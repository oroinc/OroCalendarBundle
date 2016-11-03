<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\ConfigurationPass;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;

/**
 * Passes through configuration array and replaces expressions with evaluated values.
 *
 * @see ExpressionLanguage
 */
class EvaluateExpressionsConfigurationPass implements ConfigurationPassInterface
{
    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @param ExpressionLanguage $expressionLanguage
     * @param ContextInterface $context
     */
    public function __construct(ExpressionLanguage $expressionLanguage, ContextInterface $context)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function passConfiguration(array $data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($this->isExpressionString($key)) {
                $key = $this->evaluateExpression($key);
            }

            if ($this->isExpressionString($value)) {
                $value = $this->evaluateExpression($value);
            } elseif (is_array($value)) {
                $value = $this->passConfiguration($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param string $value
     * @return bool
     */
    protected function isExpressionString($value)
    {
        return is_string($value) && (preg_match('/^context\./', $value) || false !== strpos($value, '('));
    }

    /**
     * @param string $value
     * @return mixed
     */
    protected function evaluateExpression($value)
    {
        try {
            $result = $this->expressionLanguage->evaluate(
                $value,
                [
                    'context' => $this->context
                ]
            );
        } catch (SyntaxError $exception) {
            $result = $value;
        }

        return $result;
    }
}
