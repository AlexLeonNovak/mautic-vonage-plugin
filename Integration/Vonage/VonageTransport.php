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

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticVonageBundle\Entity\Messages;
use MauticPlugin\MauticVonageBundle\Entity\Stat;
use MauticPlugin\MauticVonageBundle\Exception\ConfigurationException;
use MauticPlugin\MauticVonageBundle\Exception\VonageException;
use MauticPlugin\MauticVonageBundle\Sms\TransportInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

class VonageTransport implements TransportInterface
{
	const BASE_URL = 'https://messages-sandbox.nexmo.com/v0.1/'; //'https://api.nexmo.com/v0.1/'
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
     * VonageTransport constructor.
     */
    public function __construct(Configuration $configuration, LoggerInterface $logger)
    {
        $this->logger        = $logger;
        $this->configuration = $configuration;
    }

    /**
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $lead, $content, Stat $stat)
    {
        $number = $lead->getLeadPhoneNumber();

        if (null === $number) {
            return false;
        }
		$messageType = $stat->getMessage()->getSmsType();

        try {
            $this->configureClient();

			$bodyMessage = [
				'from' => [
					'type' => $messageType,
					'number' => $this->sendingPhoneNumber
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

			$requestMessage = $this->requestMessage($bodyMessage);

			return json_decode($requestMessage, true);

        } catch (NumberParseException $exception) {
            $this->logger->addWarning(
                $exception->getMessage(),
                ['exception' => $exception]
            );

            return $exception->getMessage();
        } catch (ConfigurationException $exception) {
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'mautic.sms.transport.Vonage.not_configured';
            $this->logger->addWarning(
                $message,
                ['exception' => $exception]
            );

            return $message;
        } catch (VonageException $exception) {
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
		]);
    }

    private function requestMessage(array $body)
	{
		$response = $this->client->request('POST', 'messages', [
			'body' => json_encode($body)
		]);
		return $response->getBody();
	}
}
