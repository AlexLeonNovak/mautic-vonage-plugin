<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use MauticPlugin\MauticVonageBundle\Event\ReplyEvent;
use MauticPlugin\MauticVonageBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StopSubscriber implements EventSubscriberInterface
{
    /**
     * @var DoNotContactModel
     */
    private $doNotContactModel;

    /**
     * StopSubscriber constructor.
     */
    public function __construct(DoNotContactModel $doNotContactModel)
    {
        $this->doNotContactModel         = $doNotContactModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SmsEvents::ON_REPLY => ['onReply', 0],
        ];
    }

    public function onReply(ReplyEvent $event)
    {
        $message = $event->getMessage();

        if ('stop' === strtolower($message)) {
            // Unsubscribe the contact
            $this->doNotContactModel->addDncForContact($event->getContact()->getId(), 'sms', DoNotContact::UNSUBSCRIBED);
        }
    }
}
