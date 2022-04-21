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

use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticVonageBundle\Exception\ConfigurationException;

class Configuration
{
    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var string
     */
    private $sendingPhoneNumber;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

	/**
	 * @var bool
	 */
	private $testMode = false;

	/**
	 * @var array
	 */
	private $testContacts = [];

    /**
     * Configuration constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getSendingNumber()
    {
        $this->setConfiguration();

        return $this->sendingPhoneNumber;
    }

	/**
	 * @return array
	 *
	 * @throws ConfigurationException
	 */
	public function getTestContacts(): array
	{
		$this->setConfiguration();

		return $this->testContacts;
	}

	/**
	 * @return bool
	 * @throws ConfigurationException
	 */
	public function isTestMode()
	{
		$this->setConfiguration();

		return $this->testMode;
	}

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getKey()
    {
        $this->setConfiguration();

        return $this->key;
    }

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getSecret()
    {
        $this->setConfiguration();

        return $this->secret;
    }

    /**
     * @throws ConfigurationException
     */
    private function setConfiguration()
    {
        if ($this->key) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject('Vonage');

        if (!$integration || !$integration->getIntegrationSettings()->getIsPublished()) {
            throw new ConfigurationException();
        }
		$feature = $integration->getIntegrationSettings()->getFeatureSettings();
        $this->sendingPhoneNumber = $feature['sending_phone_number'];
        if (empty($this->sendingPhoneNumber)) {
            throw new ConfigurationException();
        }

        $keys = $integration->getDecryptedApiKeys();
        if (empty($keys['key']) || empty($keys['secret'])) {
            throw new ConfigurationException();
        }

        $this->key = $keys['key'];
        $this->secret  = $keys['secret'];

		$this->testMode = $feature['test_mode'] ?: false;
		$this->testContacts = $feature['test_contacts'] ?: [];
    }
}
