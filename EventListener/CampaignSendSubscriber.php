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

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\CampaignTriggerEvent;
use MauticPlugin\MauticVonageBundle\Form\Type\SmsSendType;
use MauticPlugin\MauticVonageBundle\Model\MessagesModel;
use MauticPlugin\MauticVonageBundle\Sms\TransportChain;
use MauticPlugin\MauticVonageBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSendSubscriber implements EventSubscriberInterface
{
    /**
     * @var MessagesModel
     */
    private $messagesModel;

    /**
     * @var TransportChain
     */
    private $transportChain;

    public function __construct(
		MessagesModel $messagesModel,
        TransportChain $transportChain
    ) {
        $this->messagesModel       = $messagesModel;
        $this->transportChain = $transportChain;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD     => ['onCampaignBuild', 0],
            SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
//			CampaignEvents::CAMPAIGN_ON_TRIGGER => ['onCampaignTrigger', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
//        if (count($this->transportChain->getEnabledTransports()) > 0) {
		$event->addAction(
			'vonage.send_whatsapp',
			[
				'label'            => 'mautic.campaign.vonage.send_whatsapp',
//				'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
				'eventName'        => SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION,
				'formType'         => SmsSendType::class,
				'formTypeOptions'  => [
					'update_select' => 'campaignevent_properties_sms',
					'data' => ['message_type'	=> 'whatsapp']
				],
				'formTheme'        => 'MauticVonageBundle:FormTheme\SmsSendList',
				'channel'          => 'vonage',
				'channelIdField'   => 'vonage_whatsapp',
			]
		);
		$event->addAction(
			'vonage.send_sms',
			[
				'label'            => 'mautic.campaign.vonage.send_sms',
				//				'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
				'eventName'        => SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION,
				'formType'         => SmsSendType::class,
				'formTypeOptions'  => [
					'update_select' => 'campaignevent_properties_sms',
					'data' => ['message_type'	=> 'sms']
				],
				'formTheme'        => 'MauticVonageBundle:FormTheme\SmsSendList',
				'channel'          => 'vonage',
				'channelIdField'   => 'vonage_sms',
			]
		);
    }

	public function onCampaignTrigger(CampaignTriggerEvent $event)
	{
//		$lead  = $event->getLead();
	}

    /**
     * @return $this
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $lead  = $event->getLead();

        $smsId = (int) $event->getConfig()['sms'];
        $sms   = $this->messagesModel->getEntity($smsId);

        if (!$sms) {
            return $event->setFailed('mautic.sms.campaign.failed.missing_entity');
        }

        $result = $this->messagesModel->sendSms($sms, $lead, ['channel' => ['campaign.event', $event->getEvent()['id']]])[$lead->getId()];

        if ('Authenticate' === $result['status']) {
            // Don't fail the event but reschedule it for later
            return $event->setResult(false);
        }

        if (!empty($result['sent'])) {
            $event->setChannel('sms', $sms->getId());
            $event->setResult($result);
        } else {
            $result['failed'] = true;
            $result['reason'] = $result['status'];
            $event->setResult($result);
        }
    }
}
