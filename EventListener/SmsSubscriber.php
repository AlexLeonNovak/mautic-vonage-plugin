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

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticVonageBundle\Event\SmsEvent;
use MauticPlugin\MauticVonageBundle\Helper\SmsHelper;
use MauticPlugin\MauticVonageBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SmsSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var TrackableModel
     */
    private $trackableModel;

    /**
     * @var PageTokenHelper
     */
    private $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    private $assetTokenHelper;

    /**
     * @var SmsHelper
     */
    private $smsHelper;
	/**
	 * @var UrlHelper
	 */
	private $urlHelper;

	public function __construct(
        AuditLogModel $auditLogModel,
        TrackableModel $trackableModel,
        PageTokenHelper $pageTokenHelper,
        AssetTokenHelper $assetTokenHelper,
        SmsHelper $smsHelper,
		UrlHelper $urlHelper
    ) {
        $this->auditLogModel    = $auditLogModel;
        $this->trackableModel   = $trackableModel;
        $this->pageTokenHelper  = $pageTokenHelper;
        $this->assetTokenHelper = $assetTokenHelper;
        $this->smsHelper        = $smsHelper;
		$this->urlHelper 		= $urlHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SmsEvents::SMS_POST_SAVE     => ['onPostSave', 0],
            SmsEvents::SMS_POST_DELETE   => ['onDelete', 0],
            SmsEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 0],
//			PageEvents::PAGE_ON_DISPLAY  => ['onPageDisplay', 0]
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPostSave(SmsEvent $event)
    {
        $entity = $event->getSms();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'   => 'sms',
                'object'   => 'sms',
                'objectId' => $entity->getId(),
                'action'   => ($event->isNew()) ? 'create' : 'update',
                'details'  => $details,
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onDelete(SmsEvent $event)
    {
        $entity = $event->getSms();
        $log    = [
            'bundle'   => 'sms',
            'object'   => 'sms',
            'objectId' => $entity->deletedId,
            'action'   => 'delete',
            'details'  => ['name' => $entity->getName()],
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onTokenReplacement(TokenReplacementEvent $event)
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();
		$this->debug($content);
        if ($content) {
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough)
            );
            $this->debug($tokens);

            // Disable trackable urls
            if (!$this->smsHelper->getDisableTrackableUrls()) {
                list($content, $trackables) = $this->trackableModel->parseContentForTrackables(
                    $content,
                    $tokens,
                    'message',
                    $clickthrough['channel'][1]
                );

                /**
                 * @var string
                 * @var Trackable $trackable
                 */
                foreach ($trackables as $token => $trackable) {
                    $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough, !isset($clickthrough['page']));
                }

            }
			if (!isset($clickthrough['page'])) {
				foreach ($tokens as $token => $value) {
					if (preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $value)) {
						$tokens[$token] = $this->urlHelper->buildShortUrl($value);
					}
				}
			}

			$this->debug($content);
			$this->debug($tokens);

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);

            $event->setContent($content);
        }
    }

	public function onPageDisplay(PageDisplayEvent $event)
	{
		$content = $event->getContent();
		$params = $event->getParams();
	}

	public function debug($message = null, $nl = true)
	{
		return;
		if (is_array($message) || is_object($message)) {
			$output = print_r($message, true);
		} elseif (is_bool($message)) {
			$output = '(bool) ' . ($message ? 'true' : 'false');
		} elseif (is_string($message)){
			if (trim($message)) {
				$output = $message;
			} else {
				$output = '(empty sring)';
			}
		} else {
			$output = '=======================';
		}
		if ($nl){
			$output .= PHP_EOL;
		}
		file_put_contents(__DIR__ . '/sms-subscriber.log', date('Y-m-d H:i:s') . " " . $output, FILE_APPEND);
	}

}
