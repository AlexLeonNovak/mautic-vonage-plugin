<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SmsListType.
 */
class SmsListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_sms_action',
                'modal_header'        => 'mautic.sms.header.new',
                'model'               => 'vonage.messages',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => function (Options $options) {
					$mt = $options['message_type'] ?: 'sms';
                    return [
                        'type'    => SmsType::class,
                        'filter'  => '$data',
                        'limit'   => 0,
                        'start'   => 0,
                        'options' => [
                            'message_type' => $mt,
                        ],
                    ];
                },
                'ajax_lookup_action' => function (Options $options) {
                    $query = [
//                        'sms_type' => $options['data']['message_type'],
                    ];

                    return 'sms:getLookupChoiceList&'.http_build_query($query);
                },
                'multiple' => true,
                'required' => false,
            ]
        );

		$resolver->setDefined(['message_type']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sms_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityLookupType::class;
    }
}
