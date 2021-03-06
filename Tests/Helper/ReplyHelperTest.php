<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use MauticPlugin\MauticVonageBundle\Callback\CallbackInterface;
use MauticPlugin\MauticVonageBundle\Callback\ResponseInterface;
use MauticPlugin\MauticVonageBundle\Exception\NumberNotFoundException;
use MauticPlugin\MauticVonageBundle\Helper\ReplyHelper;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplyHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var NullLogger
     */
    private $logger;

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactTracker;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger          = new NullLogger();
        $this->contactTracker  = $this->createMock(ContactTracker::class);
    }

    public function testFoundContactsDispatchEvent()
    {
        $handler = $this->createMock(CallbackInterface::class);
        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->contactTracker->expects($this->once())
            ->method('setSystemContact');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->getHelper()->handleRequest($handler, new Request());
    }

    public function testHandlerResponseIsReturnedIfResponseInterface()
    {
        $handler = new class() implements CallbackInterface, ResponseInterface {
            public function getResponse()
            {
                return new Response('hi');
            }

            public function getContacts(Request $request)
            {
                return new ArrayCollection([new Lead()]);
            }

            public function getMessage(Request $request)
            {
                return '';
            }

            public function getTransportName()
            {
                return '';
            }
        };

        $response = $this->getHelper()->handleRequest($handler, new Request());

        $this->assertEquals(new Response('hi'), $response);
    }

    public function testContactsNotFoundDoesNotDispatchEvent()
    {
        $handler = $this->createMock(CallbackInterface::class);
        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturnCallback(
                function () {
                    throw new NumberNotFoundException('');
                }
            );

        $this->contactTracker->expects($this->never())
            ->method('setSystemContact');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->getHelper()->handleRequest($handler, new Request());
    }

    /**
     * @return ReplyHelper
     */
    private function getHelper()
    {
        return new ReplyHelper($this->eventDispatcher, $this->logger, $this->contactTracker);
    }
}
