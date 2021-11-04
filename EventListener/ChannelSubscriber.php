<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Model\ReportModel;
use MauticPlugin\MauticVonageBundle\Form\Type\SmsListType;
//use MauticPlugin\MauticVonageBundle\Model\MessagesModel;
use MauticPlugin\MauticVonageBundle\Sms\TransportChain;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelSubscriber implements EventSubscriberInterface
{
    /**
     * @var TransportChain
     */
    private $transportChain;

    public function __construct(TransportChain $transportChain)
    {
        $this->transportChain = $transportChain;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 90],
        ];
    }

    public function onAddChannel(ChannelEvent $event)
    {
//        if (count($this->transportChain->getEnabledTransports()) > 0) {
            $event->addChannel(
                'message',
                [
                    MessageModel::CHANNEL_FEATURE => [
                        'campaignAction'             => 'vonage.send_message',
                        'campaignDecisionsSupported' => [
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType' => SmsListType::class,
                        'repository'     => 'MauticVonageBundle:Messages',
                    ],
                    LeadModel::CHANNEL_FEATURE   => [],
                    ReportModel::CHANNEL_FEATURE => [
                        'table' => 'vonage_messages',
                    ],
                ]
            );
    }
}
