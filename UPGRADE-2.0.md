UPGRADE FROM 1.10 to 2.0
========================

#SOAP API was removed
- removed all dependencies to the `besimple/soap-bundle` bundle. 
- removed SOAP annotations from the entities. Updated entities:
    - Oro\Bundle\CalendarBundle\Entity\Calendar
    - Oro\Bundle\CalendarBundle\Entity\CalendarProperty
- removed classes:
    - Oro\Bundle\CalendarBundle\Controller\Api\Soap\CalendarConnectionController
