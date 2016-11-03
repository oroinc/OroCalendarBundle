<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

use Oro\Component\Action\Action\AbstractAction;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;

/**
 * @internal
 *
 * {@inheritdoc}
 *
 * This basic REST request action supports generic parameters used in testing of REST API.
 *
 * Example of configuration in YML:
 * <code>
 *      @test_api_rest_request:
 *          request:                    # Parameters related to API request
 *              content:                # Will be used to form JSON request entity, in this case it will be '{"foo": "Bar"}'
 *                  foo: 'Bar'
 *              parameters: []          # Parameters will be passed to request
 *          auth:                       # Parameters related to authentication
 *              username: admin         # Username for authentication, by default {@see WebTestCase::USER_NAME}
 *              password: api_key       # API key for authentication, by default {@see WebTestCase::USER_PASSWORD}
 *          response:                   # Parameters related to expected API response
 *              content:                # Expected response content
 *                  bar: 'Baz'
 *              strictCompare: false    # by default false, `true` means content will be compared strictly,
 *                                      # `false` - only intersecting keys will be verified.
 *          entityReference: 'baz'      # Entity reference could be used to access reference repository
 * </code>
 */
class RequestAction extends AbstractAction
{
    const ALIAS = 'test_api_rest_request';

    /**
     * @var string
     */
    protected $requestRoute;

    /**
     * @var string
     */
    protected $requestMethod;

    /**
     * @var array
     */
    protected $requestParameters = [];

    /**
     * @var array
     */
    protected $requestContent = [];

    /**
     * @var int
     */
    protected $responseStatusCode = 200;

    /**
     * @var string
     */
    protected $responseContentType = 'application/json';

    /**
     * @var array
     */
    protected $responseContent = [];

    /**
     * @var bool
     */
    protected $responseContentStrictCompare = false;

    /**
     * @var string
     */
    protected $authUsername = WebTestCase::USER_NAME;

    /**
     * @var array
     */
    protected $authApiKey = WebTestCase::USER_PASSWORD;

    /**
     * @param ContextInterface $context
     */
    protected function executeAction($context)
    {
        $this->doRequest($context);
        $this->handleResponse($context);
    }

    /**
     * @param ContextInterface $context
     */
    protected function doRequest(ContextInterface $context)
    {
        WebTestCase::assertNotEmpty($this->requestRoute, 'Failed asserting request route is specified.');
        WebTestCase::assertContains(
            $this->requestMethod,
            ['POST', 'GET', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'],
            'Failed asserting request method is expected.'
        );

        $url = $context->getRouter()->generate($this->requestRoute, $this->requestParameters);

        $client = $context->getClient();
        $client->request(
            $this->requestMethod,
            $url,
            [],
            [],
            $this->generateWsseAuthHeader(),
            $this->requestContent
        );
    }

    /**
     * @param ContextInterface $context
     */
    protected function handleResponse(ContextInterface $context)
    {
        $client = $context->getClient();
        $this->assertResponseHeadersExpected($client);
        $response = $this->getResponseContent($client);
        $this->assertResponseContentExpected($client, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (isset($options['request']['route'])) {
            $this->requestRoute = $options['request']['route'];
        }

        if (isset($options['request']['method'])) {
            $this->requestMethod = strtoupper($options['request']['method']);
        }

        if (isset($options['request']['parameters'])) {
            $this->requestParameters = $options['request']['parameters'];
        }

        if (isset($options['request']['content'])) {
            $this->requestContent = $options['request']['content'];
            if (is_array($this->requestContent)) {
                $this->requestContent = json_encode($this->requestContent);
            }
        }

        if (isset($options['response']['statusCode'])) {
            $this->responseStatusCode = $options['response']['statusCode'];
        }

        if (isset($options['response']['contentType'])) {
            $this->responseContentType = $options['response']['contentType'];
        }

        if (isset($options['response']['content'])) {
            $this->responseContent = $options['response']['content'];
        }

        if (isset($options['response']['strictCompare'])) {
            $this->responseContentStrictCompare = $options['response']['strictCompare'];
        }

        if (isset($options['auth']['username'])) {
            $this->authUsername = $options['auth']['username'];
        }

        if (isset($options['auth']['apiKey'])) {
            $this->authApiKey = $options['auth']['apiKey'];
        }

        return $this;
    }

    /**
     * @param Client $client
     */
    protected function assertResponseHeadersExpected(Client $client)
    {
        WebTestCase::assertResponseStatusCodeEquals(
            $client->getResponse(),
            $this->responseStatusCode,
            sprintf(
                'Failed asserting %s %s has expected status code in response.',
                $client->getRequest()->getMethod(),
                $client->getRequest()->getRequestUri()
            )
        );
        WebTestCase::assertResponseContentTypeEquals(
            $client->getResponse(),
            $this->responseContentType,
            sprintf(
                'Failed asserting %s %s has expected content type in response.',
                $client->getRequest()->getMethod(),
                $client->getRequest()->getRequestUri()
            )
        );
    }

    /**
     * @param Client $client
     * @param array $actualResponseContent
     */
    protected function assertResponseContentExpected(Client $client, $actualResponseContent)
    {
        $expectedResponseContent = $this->responseContent;

        $message = sprintf(
            'Failed asserting %s %s has expected content in response.',
            $client->getRequest()->getMethod(),
            $client->getRequest()->getRequestUri()
        );

        if ($this->responseContentStrictCompare) {
            WebTestCase::assertEquals(
                $expectedResponseContent,
                $actualResponseContent,
                $message
            );
        } else {
            WebTestCase::assertArrayIntersectEquals(
                $expectedResponseContent,
                $actualResponseContent,
                $message
            );
        }
    }

    /**
     * @param Client $client
     * @return array
     */
    protected function getResponseContent(Client $client)
    {
        if ($this->responseContentType == 'application/json') {
            return WebTestCase::jsonToArray($client->getResponse()->getContent());
        } else {
            WebTestCase::fail(
                sprintf(
                    'Unsupported response content type "%s".',
                    $this->responseContentType
                )
            );
        }
    }

    /**
     * @return array
     */
    protected function generateWsseAuthHeader()
    {
        return WebTestCase::generateWsseAuthHeader($this->authUsername, $this->authApiKey);
    }
}
