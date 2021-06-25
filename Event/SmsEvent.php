<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticVonageBundle\Entity\Messages;

/**
 * Class SmsEvent.
 */
class SmsEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Messages $messages, $isNew = false)
    {
        $this->entity = $messages;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Sms entity.
     *
     * @return Messages
     */
    public function getMessage()
    {
        return $this->entity;
    }

    /**
     * Sets the Sms entity.
     */
    public function setSms(Messages $messages)
    {
        $this->entity = $messages;
    }
}
