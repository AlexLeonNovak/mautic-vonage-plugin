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

        $this->sendingPhoneNumber = $integration->getIntegrationSettings()->getFeatureSettings()['sending_phone_number'];
        if (empty($this->sendingPhoneNumber)) {
            throw new ConfigurationException();
        }

        $keys = $integration->getDecryptedApiKeys();
        if (empty($keys['key']) || empty($keys['secret'])) {
            throw new ConfigurationException();
        }

        $this->key = $keys['key'];
        $this->secret  = $keys['secret'];
    }
}
