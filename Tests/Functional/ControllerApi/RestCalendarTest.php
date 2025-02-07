<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\ControllerApi;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RestCalendarTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(array(), self::generateApiAuthHeader());
    }

    /**
     * Test get default calendar of user
     */
    public function testGetDefaultCalendarAction()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_calendar_default'));

        $result = $this->getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);

        $this->assertNotEmpty($result);
    }
}
