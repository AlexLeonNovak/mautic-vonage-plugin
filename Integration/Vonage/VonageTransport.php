<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Integration\Vonage;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use MauticPlugin\MauticVonageBundle\Entity\Messages;
use MauticPlugin\MauticVonageBundle\Entity\Stat;
use MauticPlugin\MauticVonageBundle\Exception\ConfigurationException;
use MauticPlugin\MauticVonageBundle\Exception\VonageException;
use MauticPlugin\MauticVonageBundle\Sms\TransportInterface;
use MauticPlugin\MauticVonageBundle\SmsEvents;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class VonageTransport implements TransportInterface
{
	const BASE_URL = 'https://api.nexmo.com/v0.1/';
//	const BASE_URL = 'https://messages-sandbox.nexmo.com/v0.1/';
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $sendingPhoneNumber;
	/**
	 * @var EventDispatcherInterface
	 */
	private $dispatcher;

	/**
     * VonageTransport constructor.
     */
    public function __construct(
    	Configuration $configuration,
		LoggerInterface $logger,
		EventDispatcherInterface $dispatcher
	)
    {
        $this->logger        = $logger;
        $this->configuration = $configuration;
        $this->dispatcher    = $dispatcher;
    }

    /**
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $lead, $content, Stat $stat)
    {
    	$this->debug();
        $number = $lead->getLeadPhoneNumber();

        if (null === $number) {
            return false;
        }
        $message = $stat->getMessage();
		$messageType = $message->getSmsType();

		try {
            $this->configureClient();
			if ($messageType === 'whatsapp_template') {
				$bodyMessage = [
					'from' => [
						'type' => 'whatsapp',
						'number' => $this->sendingPhoneNumber
					],
					'to' => [
						'type' => 'whatsapp',
						'number' => $this->sanitizeNumber($number)
					],
					'message' => [
						'content' => [
							'type' => 'custom',
							'custom' => [
								'type' => 'template',
								'template' => [
									'namespace' => $message->getWhatsappTemplateNamespace(),
									'name' => $message->getWhatsappTemplateName(),
									'language' => [
										'code' => $message->getLanguage(),
										'policy' => 'deterministic',
									]
								]
							],
						]
					]
				];

				$parameters = [];

				foreach ($message->getWhatsappTemplateParameters()['list'] as $parameter) {
					$tokenEvent = $this->dispatcher->dispatch(
						SmsEvents::TOKEN_REPLACEMENT,
						new TokenReplacementEvent(
							$parameter,
							$lead,
							[
								'channel' => [
									'message',          // Keep BC pre 2.14.1
									$message->getId(),  // Keep BC pre 2.14.1
									'message' => $message->getId(),
								],
								'message_stat'    => $stat->getTrackingHash(),
							]
						)
					);
					$parameters[] = [
						'type' => 'text',
						'text' => TokenHelper::findLeadTokens($tokenEvent->getContent(), $lead->getProfileFields(), true)
					];
				}

				if ($parameters) {
					$bodyMessage['message']['content']['custom']['template']['components'][] = [
						'type' => 'body',
						'parameters' => $parameters
					];
				}

				if (($btn_parameter = $message->getWhatsappTemplateButtonParameter())) {
					$bodyMessage['message']['content']['custom']['template']['components'][] = [
						'type' => 'button',
						'index' => 0,
						'sub_type' => 'url',
						'parameters' => [
							[
								'type' => 'text',
								'text' => TokenHelper::findLeadTokens($btn_parameter, $lead->getProfileFields(), true)
							]
						]
					];
				}
			} else {
				$bodyMessage = [
					'from' => [
						'type' => $messageType,
						'number' => $message->getSenderId() ?: $this->sendingPhoneNumber
					],
					'to' => [
						'type' => $messageType,
						'number' => $this->sanitizeNumber($number)
					],
					'message' => [
						'content' => [
							'type' => 'text',
							'text' => $content
						]
					]
				];
			}
			$this->debug($bodyMessage);

			$requestMessage = $this->requestMessage($bodyMessage);

			$this->debug(json_decode($requestMessage, true));

			return json_decode($requestMessage, true);

        } catch (ConfigurationException $exception) {
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'mautic.sms.transport.Vonage.not_configured';
            $this->logger->addWarning(
                $message,
                ['exception' => $exception]
            );
			$this->debug($exception);

            return $message;
        } catch (\Exception $exception) {
			$this->debug($exception);
			$this->logger->addWarning(
				$exception->getMessage(),
				['exception' => $exception]
			);
			return $exception->getMessage();
		}
	}

    /**
     * @param string $number
     *
     * @return string
     *
     * @throws NumberParseException
     */
    private function sanitizeNumber($number)
    {
    	if (substr($number, 0, 1) === '0') {
			$number = substr_replace($number, '+972', 0, 1);
		}
        $util   = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'US');
		$formatted = $util->format($parsed, PhoneNumberFormat::E164);


        return str_replace('+', '', $formatted);
    }

    /**
     * @throws ConfigurationException
     */
    private function configureClient()
    {
        if ($this->client) {
            // Already configured
            return;
        }

		$stack = HandlerStack::create();
		// my middleware
		$stack->push(Middleware::mapRequest(function (RequestInterface $request) {
			$contentsRequest = (string) $request->getBody();
			//var_dump($contentsRequest);
			$this->debug($contentsRequest);
			return $request;
		}));

        $this->sendingPhoneNumber = $this->configuration->getSendingNumber();
        $this->client = new Client([
        	'base_uri' => self::BASE_URL,
			'timeout' => 2.0,
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			],
			'auth' => [
				$this->configuration->getKey(),
				$this->configuration->getSecret()
			],
			'handler' => $stack
		]);
    }

    private function requestMessage(array $body)
	{
		$response = $this->client->request('POST', 'messages', [
			'body' => json_encode($body),
		]);

		return $response->getBody();
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
