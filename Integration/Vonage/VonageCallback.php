<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Integration\Vonage;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticVonageBundle\Callback\CallbackInterface;
use MauticPlugin\MauticVonageBundle\Entity\MessageAnswers;
use MauticPlugin\MauticVonageBundle\Exception\ConfigurationException;
use MauticPlugin\MauticVonageBundle\Exception\NumberNotFoundException;
use MauticPlugin\MauticVonageBundle\Helper\ContactHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VonageCallback
{
    /**
     * @var ContactHelper
     */
    private $contactHelper;

    /**
     * @var Configuration
     */
    private $configuration;
	/**
	 * @var string|null
	 */
	private $status = null;
	/**
	 * @var \DateTime
	 */
	private $datetime;
	/**
	 * @var bool
	 */
	private $isStatusMessage;
	/**
	 * @var string| null
	 */
	private $message = null;
	/**
	 * @var string
	 */
	private $messageUuid;
	/**
	 * @var array
	 */
	private $from;
	/**
	 * @var array
	 */
	private $to;
	/**
	 * @var EntityManager
	 */
	private $em;
	/**
	 * @var array
	 */
	private $messageTypes = [];
	/**
	 * @var LeadModel
	 */
	private $leadModel;

	/**
     * VonageCallback constructor.
     */
    public function __construct(
    	ContactHelper $contactHelper,
		Configuration $configuration,
		RequestStack $requestStack,
		EntityManager $em,
		LeadModel $leadModel
	)
    {
        $this->contactHelper = $contactHelper;
        $this->configuration = $configuration;
        $this->validateRequest($requestStack->getCurrentRequest());
        $this->em = $em;
		$this->leadModel = $leadModel;
    }

    /**
     * @return string
     */
    public function getTransportName()
    {
        return 'Vonage';
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     *
     * @throws NumberNotFoundException
     */
    public function getContacts()
    {
        $number = $this->isStatusMessage
			? $this->to['number']
			: $this->from['number'];

        return $this->contactHelper->findContactsByNumber($number);
    }

	/**
	 * @return \MauticPlugin\MauticVonageBundle\Entity\Stat[]
	 * @throws NumberNotFoundException
	 */
    public function getStats()
	{
		/** @var \MauticPlugin\MauticVonageBundle\Entity\StatRepository $statRepository */
		$statRepository = $this->em->getRepository('MauticVonageBundle:Stat');
		$ids = $this->getContacts()->getKeys();
		return $statRepository->getLeadsStats($ids);
	}

    /**
     * @return string|null
     */
    public function getMessage()
    {
		if ($this->isStatusMessage) {
			return null;
		}
		return strtolower(trim($this->message));
    }

    public function getMessageUuid(): string
	{
		return $this->messageUuid;
	}

	public function getDateTime()
	{
		return $this->datetime;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function processField()
	{
		$this->debug();
		$stats = $this->getStats();
		foreach ($stats as $stat) {
			$this->debug($stat->getId());
			$details = $stat->getDetails();
			if (isset($details['setAnswer'])) {
				continue;
			}
			/** @var MessageAnswers[] $answers */
			$message = $stat->getMessage();
			if (!in_array($message->getSmsType(), $this->messageTypes)){
				continue;
			}
			$answers = $message->getAnswers();
			foreach ($answers as $answer) {
				if ($answer->getAnswer() === $this->getMessage()) {
					$lead = $stat->getLead();
					$lead->addUpdatedField($answer->getField(), $answer->getSetValue());
					$stat->setDetails([
						'setAnswer' => $message->getSmsType(),
						'field' => $answer->getField(),
						'val' => $answer->getSetValue(),
						'answerId' => $answer->getId()
					]);
					$this->leadModel->saveEntity($lead);
					$this->em->persist($stat);
					$this->em->flush();
				}
			}
		}
		$this->debug();
	}

    private function validateRequest(Request $request)
    {
        try {
            $key = $this->configuration->getKey();
            $secret = $this->configuration->getSecret();
        } catch (ConfigurationException $exception) {
            // Not published or not configured
            throw new NotFoundHttpException();
        }

        $authorizationHeader = $request->headers->get('Authorization');
        [, $token] = explode(' ', $authorizationHeader);

        // Validate this is a request from Vonage
        if (!$token) {
            throw new BadRequestHttpException();
        }
        //TODO: add checking token

		$content = json_decode($request->getContent(), true);
        //Status message
		//Array
		//(
		//    [message_uuid] => c4ddc64a-ce79-433b-a170-d16fa35191ff
		//    [from] => Array
		//        (
		//            [type] => whatsapp
		//            [number] => 14157386170
		//        )
		//
		//    [to] => Array
		//        (
		//            [type] => whatsapp
		//            [number] => 380672265958
		//        )
		//
		//    [timestamp] => 2021-06-11T12:40:35.614Z
		//    [status] => read
		//)

		//Inbound
		//Array
		//(
		//    [message_uuid] => f2b7be42-8bb8-4f08-8c0a-7c0b755fac89
		//    [from] => Array
		//        (
		//            [type] => whatsapp
		//            [number] => 380672265958
		//        )
		//
		//    [to] => Array
		//        (
		//            [type] => whatsapp
		//            [number] => 14157386170
		//        )
		//
		//    [message] => Array
		//        (
		//            [content] => Array
		//                (
		//                    [type] => text
		//                    [text] => Gggggg
		//                )
		//
		//        )
		//
		//    [timestamp] => 2021-06-11T12:43:58.067Z
		//)

		$this->datetime = new \DateTime($content['timestamp']);
		$this->isStatusMessage = isset($content['status']);
		if ($this->isStatusMessage) {
			$this->status = $content['status'];
		} else {
			switch($content['message']['content']['type']) {
				case 'text':
					$this->message = $content['message']['content']['text'];
					break;
				case 'button':
					$this->message = $content['message']['content']['button']['text'];
					break;
				default:
					$this->message = null;
			}
		}
		$this->messageUuid = $content['message_uuid'];
		$this->from = $content['from'];
		$this->to = $content['to'];
		$this->messageTypes[] = $content['to']['type'];
		if ($content['to']['type'] === 'whatsapp') {
			$this->messageTypes[] = 'whatsapp_template';
		}

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
}
