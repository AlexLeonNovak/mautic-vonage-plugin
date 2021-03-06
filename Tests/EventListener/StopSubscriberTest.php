<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use MauticPlugin\MauticVonageBundle\Event\ReplyEvent;
use MauticPlugin\MauticVonageBundle\EventListener\StopSubscriber;

class StopSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoNotContact
     */
    private $doNotContactModel;

    protected function setUp(): void
    {
        $this->doNotContactModel = $this->createMock(DoNotContactModel::class);
    }

    public function testLeadAddedToDNC()
    {
        $lead = new Lead();
        $lead->setId(1);
        $event = new ReplyEvent($lead, 'stop');

        $this->doNotContactModel->expects($this->once())
        ->method('addDncForContact')
        ->with(1, 'sms', DoNotContact::UNSUBSCRIBED);

        $this->StopSubscriber()->onReply($event);
    }

    /**
     * @return StopSubscriber
     */
    private function StopSubscriber()
    {
        return new StopSubscriber($this->doNotContactModel);
    }
}
