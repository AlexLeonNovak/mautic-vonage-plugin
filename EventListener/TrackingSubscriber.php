<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\EventListener;

use Doctrine\ORM\EntityManager;
//use Mautic\AssetBundle\Helper\TokenHelper;
use DOMDocument;
use DOMXPath;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\PageHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticVonageBundle\Entity\Stat;
use MauticPlugin\MauticVonageBundle\Entity\StatRepository;
use MauticPlugin\MauticVonageBundle\Helper\ContactHelper;
use MauticPlugin\MauticVonageBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TrackingSubscriber implements EventSubscriberInterface
{
    /**
     * @var StatRepository
     */
    private $statRepository;
	/**
	 * @var EntityManager
	 */
	private $em;
	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var DoNotContactModel
	 */
	private $doNotContactModel;
	/**
	 * @var ModelFactory
	 */
	private $modelFactory;
	/**
	 * @var CorePermissions
	 */
	private $security;
	/**
	 * @var ContactTracker
	 */
	private $contactTracker;
	/**
	 * @var EventDispatcherInterface
	 */
	private $dispatcher;
	/**
	 * @var ContactHelper
	 */
	private $contactHelper;
	/**
	 * @var LeadModel
	 */
	private $leadModel;

	/**
     * TrackingSubscriber constructor.
     */
    public function __construct(
    	StatRepository $statRepository,
		EntityManager $em,
		RequestStack $request,
		DoNotContactModel $doNotContactModel,
		ModelFactory $modelFactory,
		CorePermissions $security,
		ContactTracker $contactTracker,
		EventDispatcherInterface $dispatcher,
		ContactHelper $contactHelper,
		LeadModel $leadModel
	)
    {
        $this->statRepository 		= $statRepository;
        $this->em 					= $em;
        $this->request 				= $request->getCurrentRequest();
		$this->doNotContactModel 	= $doNotContactModel;
		$this->modelFactory  		= $modelFactory;
		$this->security             = $security;
		$this->contactTracker       = $contactTracker;
		$this->dispatcher			= $dispatcher;
		$this->contactHelper		= $contactHelper;
		$this->leadModel 			= $leadModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION => ['onIdentifyContact', 0],
			PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 250]
        ];
    }

    public function onIdentifyContact(ContactIdentificationEvent $event)
    {
    	$this->debug('onIdentifyContact');
    	$this->debug($_SERVER['HTTP_USER_AGENT']);

//		file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " " . print_r($_SERVER, true) . PHP_EOL, FILE_APPEND);
		// basic crawler detection and block script (no legit browser should match this)
		if(!empty($_SERVER['HTTP_USER_AGENT']) &&
			preg_match('~(bot|crawl)~i', $_SERVER['HTTP_USER_AGENT'])){
			// this is a crawler and you should not show ads here
			return;
		}
		$this->debug(empty($_SERVER['HTTP_ACCEPT_LANGUAGE']));
		$this->debug($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$this->debug($_SERVER);
			return;
		}
//		if (empty($_SERVER['REDIRECT_SSL_SESSION_ID']) || empty($_SERVER['SSL_SESSION_ID'])){
//			return;
//		}


        $slug = $this->request->attributes->get('slug');
		$this->debug(['slug' => $slug]);
        if (!$slug) {
        	return;
		}
        $pageModel = $this->modelFactory->getModel('page');
        $page = $pageModel->getEntityBySlugs($slug);
		$this->debug(['page' => $page->getId()]);
        if (!$page) {
        	return;
		}

		$clickthrough = $event->getClickthrough();
        $this->debug('clickthrough');
        $this->debug($clickthrough);
        // Nothing left to identify by so stick to the tracked lead
        if (empty($clickthrough['channel']['message']) && empty($clickthrough['message_stat'])) {
            return;
        }



        /** @var Stat $stat */
        $stat = $this->statRepository->findOneBy(['trackingHash' => $clickthrough['message_stat']]);
		$this->debug(['stat' => (bool)$stat]);
        if (!$stat) {
            // Stat doesn't exist so use the tracked lead
            return;
        }
//		$page = $event->
		$message = $stat->getMessage();
		$this->debug(['message' => (bool)$message]);
		if ($message && (int) $message->getId() !== (int) $clickthrough['channel']['message']
		) {
            // ID mismatch - fishy so use tracked lead
            return;
        }

		if (!$contact = $stat->getLead()) {
			return;
		}
		$event->setIdentifiedContact($contact, 'message');
		$this->debug(['contact' => (bool)$contact]);

		if ($page === $message->getPageUnsubscribe()) {
			$number = $this->sanitizeNumber($contact->getLeadPhoneNumber());
			$contacts = $this->contactHelper->findContactsByNumber($number);
			foreach ($contacts as $_contact) {
				foreach (['message', 'email'] as $channel) {
					$this->doNotContactModel->addDncForContact(
						$_contact->getId(),
						$channel,
						DoNotContact::UNSUBSCRIBED
					);
					$event = new ChannelSubscriptionChange($_contact, $channel, DoNotContact::IS_CONTACTABLE,
						DoNotContact::UNSUBSCRIBED);
					$this->dispatcher->dispatch(LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED, $event);
				}
			}
		}
		$this->debug([
			'current_page' => $page->getId(),
			'unsubscribe page' => $message->getPageUnsubscribe()->getId(),
			'page change field' => $message->getPageChangeField()->getId(),
			'contact' => $contact->getId(),
			'field' => $message->getField(),
			'set value' => $message->getSetValue(),
			'contact field val' => $contact->getField($message->getField())
		]);
		if ($page !== $message->getPageChangeField()) {
			return;
		}


        $details = $stat->getDetails();
		$this->debug(['details' => $details]);
        if (($field = $message->getField()) && ($setValue = $message->getSetValue()) && !isset($details['setAnswer'])) {
        	$contact->addUpdatedField($field, $setValue);
        	$stat->setDetails([
				'setAnswer' => 'message',
				'field' => $field,
				'val' => $setValue
			]);
        	try {
				$this->leadModel->saveEntity($contact);
				$this->em->persist($stat);
				$this->em->flush();
			} catch (\Exception $exception) {
				$this->debug(['em error' => $exception->getMessage()]);
			}
		}
		$this->debug();

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
		file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " " . $output, FILE_APPEND);
	}

	public function onPageDisplay(PageDisplayEvent $event)
	{
		$content = $event->getContent();
		$page = $event->getPage();
		$ct = $this->request->get('ct', '');
		$clickthrough = ClickthroughHelper::decodeArrayFromUrl($ct);
		$lead = null;
		if (isset($clickthrough['lead']) && (int)$clickthrough['lead']) {
			$lead = $this->em->getRepository('MauticLeadBundle:Lead')->getEntity($clickthrough['lead']);
		}
		if (!$lead) {
			$lead = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;
		}

		if (!$lead) {
			return;
		}
		$tokens = TokenHelper::findLeadTokens($content, $lead->getProfileFields());
//		$this->debug($tokens);
		$remove_count = 0;
		foreach ($tokens as $token => $value) {
			if (empty($value)) {
				$content = str_replace($token, '{{%remove_it%}}', $content, $remove_count);
			} else {
				$content = str_replace($token, $value, $content);
			}
		}

		if ($clickthrough) {
			$clickthrough['page'] = $page->getId();
			$tokenEvent = $this->dispatcher->dispatch(
				SmsEvents::TOKEN_REPLACEMENT,
				new TokenReplacementEvent(
					$content,
					$lead,
					$clickthrough
				)
			);
			$content = $tokenEvent->getContent();
		}
		if ($remove_count) {
			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
			$xpath = new DOMXPath($dom);
			$nodes = $xpath->query("//*[text()[contains(.,'{{%remove_it%}}')]]");
			foreach ($nodes as $node) {
				$node->parentNode->removeChild($node);
				$content = $dom->saveHTML();
			}
			unset($nodes, $xpath, $dom);
		}

		$event->setContent($content);
	}


	private function sanitizeNumber($number)
	{
		if (substr($number, 0, 1) === '0') {
			$number = substr_replace($number, '+972', 0, 1);
		}
		$util   = PhoneNumberUtil::getInstance();
		$parsed = $util->parse($number, 'US');
		return $util->format($parsed, PhoneNumberFormat::E164);
	}
}
