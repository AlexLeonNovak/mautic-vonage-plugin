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
	 * @var mixed
	 */
	private $messageType;

	/**
     * VonageCallback constructor.
     */
    public function __construct(
    	ContactHelper $contactHelper,
		Configuration $configuration,
		RequestStack $requestStack,
		EntityManager $em
	)
    {
        $this->contactHelper = $contactHelper;
        $this->configuration = $configuration;
        $this->validateRequest($requestStack->getCurrentRequest());
        $this->em = $em;
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
		return $statRepository->getEntities(['lead_id' => $ids, 'ignore_paginator' => true]);
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
		$answersRepository = $this->em->getRepository('MauticVonageBundle:MessageAnswers');
		$stats = $this->getStats();
		foreach ($stats as $stat) {
			$details = $stat->getDetails();
			if (isset($details['setAnswer'])) {
				continue;
			}
			/** @var MessageAnswers[] $answers */
			$message = $stat->getMessage();
			if ($message->getSmsType() !== $this->messageType){
				continue;
			}
			$answers = $answersRepository->getEntities([
				'message_id' => $message->getId(),
				'ignore_paginator' => true,
			]);
			foreach ($answers as $answer) {
				if ($answer->getAnswer() === $this->getMessage()) {
					$lead = $stat->getLead();
					$lead->addUpdatedField($answer->getField(), $answer->getSetValue());
					$this->em->persist($lead);
					$stat->setDetails(['setAnswer' => $answer->getId()]);
					$this->em->persist($stat);

					$this->em->flush();
				}
			}
		}
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
		} elseif ($content['message']['content']['type'] === 'text') {
			$this->message = $content['message']['content']['text'];
		}
		$this->messageUuid = $content['message_uuid'];
		$this->from = $content['from'];
		$this->to = $content['to'];
		$this->messageType = $content['to']['type'];

    }
}
