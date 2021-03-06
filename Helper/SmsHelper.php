<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Helper;

use Doctrine\ORM\EntityManager;
use libphonenumber\PhoneNumberFormat;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\DoNotContact as DoNotContactEntity;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticVonageBundle\Model\MessagesModel;

class SmsHelper
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var PhoneNumberHelper
     */
    protected $phoneNumberHelper;

    /**
     * @var MessagesModel
     */
    protected $messagesModel;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    public function __construct(
        EntityManager $em,
        LeadModel $leadModel,
        PhoneNumberHelper $phoneNumberHelper,
        MessagesModel $messagesModel,
        IntegrationHelper $integrationHelper,
        DoNotContact $doNotContact
    ) {
        $this->em                   = $em;
        $this->leadModel            = $leadModel;
        $this->phoneNumberHelper    = $phoneNumberHelper;
        $this->messagesModel             = $messagesModel;
        $this->integrationHelper    = $integrationHelper;
        $this->doNotContact         = $doNotContact;
    }

    public function unsubscribe($number)
    {
        $number = $this->phoneNumberHelper->format($number, PhoneNumberFormat::E164);

        /** @var LeadRepository $repo */
        $repo = $this->em->getRepository('MauticLeadBundle:Lead');

        $args = [
            'filter' => [
                'force' => [
                    [
                        'column' => 'mobile',
                        'expr'   => 'eq',
                        'value'  => $number,
                    ],
                ],
            ],
        ];

        $leads = $repo->getEntities($args);

        if (!empty($leads)) {
            $lead = array_shift($leads);
        } else {
            // Try to find the lead based on the given phone number
            $args['filter']['force'][0]['column'] = 'phone';

            $leads = $repo->getEntities($args);

            if (!empty($leads)) {
                $lead = array_shift($leads);
            } else {
                return false;
            }
        }

        return $this->doNotContact->addDncForContact($lead->getId(), 'sms', DoNotContactEntity::UNSUBSCRIBED);
    }

    /**
     * @return bool
     */
    public function getDisableTrackableUrls()
    {
        $integration = $this->integrationHelper->getIntegrationObject('Vonage');
        $settings    = $integration->getIntegrationSettings()->getFeatureSettings();

        return !empty($settings['disable_trackable_urls']) ? true : false;
    }
}
