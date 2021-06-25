<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
	'name'        => 'Vonage connector',
	'description' => 'Vonage connector',
	'version'     => '1.0',
	'author'      => 'Alex Leon',
    'services' => [
        'events' => [
            'mautic.vonage.lead.subscriber' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'translator',
                    'router',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.vonage.broadcast.subscriber' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\BroadcastSubscriber::class,
                'arguments' => [
                    'mautic.vonage.broadcast.executioner',
                ],
            ],
            'mautic.vonage.campaignbundle.subscriber.send' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\CampaignSendSubscriber::class,
                'arguments' => [
                    'mautic.vonage.model.messages',
                    'mautic.vonage.transport_chain',
                ],
                'alias' => 'mautic.vonage.campaignbundle.subscriber',
            ],
            'mautic.vonage.campaignbundle.subscriber.reply' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\CampaignReplySubscriber::class,
                'arguments' => [
                    'mautic.vonage.transport_chain',
                    'mautic.campaign.executioner.realtime',
                ],
            ],
            'mautic.vonage.smsbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\SmsSubscriber::class,
                'arguments' => [
                    'mautic.core.model.auditlog',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.vonage.helper.sms',
                ],
            ],
            'mautic.vonage.channel.subscriber' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\ChannelSubscriber::class,
                'arguments' => [
                    'mautic.vonage.transport_chain',
                ],
            ],
            'mautic.vonage.message_queue.subscriber' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\MessageQueueSubscriber::class,
                'arguments' => [
                    'mautic.vonage.model.messages',
                ],
            ],
            'mautic.vonage.stats.subscriber' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.vonage.configbundle.subscriber' => [
                'class' => MauticPlugin\MauticVonageBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.vonage.subscriber.contact_tracker' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\TrackingSubscriber::class,
                'arguments' => [
                    'mautic.vonage.repository.stat',
                ],
            ],
            'mautic.vonage.subscriber.stop' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\StopSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.vonage.subscriber.reply' => [
                'class'     => \MauticPlugin\MauticVonageBundle\EventListener\ReplySubscriber::class,
                'arguments' => [
                    'translator',
                    'mautic.lead.repository.lead_event_log',
                ],
            ],
        ],
        'forms' => [
            'mautic.vonage.form.type.sms' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Form\Type\SmsType::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
			'mautic.vonage.answerlist.type' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Form\Type\AnswerListType::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.vonage.form.type.smsconfig' => [
                'class' => \MauticPlugin\MauticVonageBundle\Form\Type\ConfigType::class,
            ],
            'mautic.vonage.form.type.smssend_list' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Form\Type\SmsSendType::class,
                'arguments' => 'router',
            ],
            'mautic.vonage.form.type.sms_list' => [
                'class' => \MauticPlugin\MauticVonageBundle\Form\Type\SmsListType::class,
            ],
            'mautic.vonage.form.type.sms.config.form' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Form\Type\ConfigType::class,
                'arguments' => ['mautic.vonage.transport_chain', 'translator'],
            ],
            'mautic.vonage.form.type.sms.campaign_reply_type' => [
                'class' => \MauticPlugin\MauticVonageBundle\Form\Type\CampaignReplyType::class,
            ],
        ],
        'helpers' => [
            'mautic.vonage.helper.sms' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Helper\SmsHelper::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead',
                    'mautic.helper.phone_number',
                    'mautic.vonage.model.messages',
                    'mautic.helper.integration',
                    'mautic.lead.model.dnc',
                ],
                'alias' => 'sms_helper',
            ],
        ],
        'other' => [
            'mautic.vonage.transport_chain' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Sms\TransportChain::class,
                'arguments' => [
                    '%mautic.vonage_transport%',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.vonage.callback_handler_container' => [
                'class' => \MauticPlugin\MauticVonageBundle\Callback\HandlerContainer::class,
            ],
            'mautic.vonage.helper.contact' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Helper\ContactHelper::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'doctrine.dbal.default_connection',
                    'mautic.helper.phone_number',
                ],
            ],
//            'mautic.vonage.helper.reply' => [
//                'class'     => \MauticPlugin\MauticVonageBundle\Helper\ReplyHelper::class,
//                'arguments' => [
//                    'event_dispatcher',
//                    'monolog.logger.mautic',
//                    'mautic.tracker.contact',
//                ],
//            ],
			'mautic.vonage.helper.callback' => [
				'class'     => \MauticPlugin\MauticVonageBundle\Helper\CallbackHelper::class,
				'arguments' => [
					'event_dispatcher',
					'monolog.logger.mautic',
					'mautic.tracker.contact',
				],
			],
            'mautic.vonage.configuration' => [
                'class'        => \MauticPlugin\MauticVonageBundle\Integration\Vonage\Configuration::class,
                'arguments'    => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.vonage.transport' => [
                'class'        => \MauticPlugin\MauticVonageBundle\Integration\Vonage\VonageTransport::class,
                'arguments'    => [
                    'mautic.vonage.configuration',
                    'monolog.logger.mautic',
                ],
                'tag'          => 'mautic.vonage_transport',
                'tagArguments' => [
                    'integrationAlias' => 'Vonage',
                ],
                'serviceAliases' => [
                    'sms_api',
                    'mautic.vonage.api',
                ],
            ],
            'mautic.vonage.callback' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Integration\Vonage\VonageCallback::class,
                'arguments' => [
                    'mautic.vonage.helper.contact',
                    'mautic.vonage.configuration',
					'request_stack',
					'doctrine.orm.entity_manager',
                ],
                'tag'   => 'mautic.vonage_callback_handler',
            ],

            // @deprecated - this should not be used; use `mautic.vonage.Vonage.transport` instead.
            // Only kept as BC in case someone is passing the service by name in 3rd party
            'mautic.vonage.transport.vonage' => [
                'class'        => \MauticPlugin\MauticVonageBundle\Api\VonageApi::class,
                'arguments'    => [
                    'mautic.vonage.configuration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.vonage.broadcast.executioner' => [
                'class'        => \MauticPlugin\MauticVonageBundle\Broadcast\BroadcastExecutioner::class,
                'arguments'    => [
                    'mautic.vonage.model.messages',
                    'mautic.vonage.broadcast.query',
                    'translator',
                ],
            ],
            'mautic.vonage.broadcast.query' => [
                'class'        => \MauticPlugin\MauticVonageBundle\Broadcast\BroadcastQuery::class,
                'arguments'    => [
                    'doctrine.orm.entity_manager',
                    'mautic.vonage.model.messages',
                ],
            ],
        ],
        'models' => [
            'mautic.vonage.model.messages' => [
                'class'     => 'MauticPlugin\MauticVonageBundle\Model\MessagesModel',
                'arguments' => [
                    'mautic.page.model.trackable',
                    'mautic.lead.model.lead',
                    'mautic.channel.model.queue',
                    'mautic.vonage.transport_chain',
                    'mautic.helper.cache_storage',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.vonage' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Integration\VonageIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
        'repositories' => [
            'mautic.vonage.repository.stat' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\MauticVonageBundle\Entity\Stat::class,
                ],
            ],
        ],
        'controllers' => [
            'mautic.vonage.controller.reply' => [
                'class'     => \MauticPlugin\MauticVonageBundle\Controller\ReplyController::class,
                'arguments' => [
                    'mautic.vonage.callback_handler_container',
                    'mautic.vonage.helper.callback',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
			'mautic.vonage.controller.callback' => [
				'class'     => \MauticPlugin\MauticVonageBundle\Controller\CallbackController::class,
				'arguments' => [
//					'mautic.vonage.callback_handler_container',
					'mautic.vonage.helper.callback',
					'mautic.vonage.callback'
				],
//				'methodCalls' => [
//					'setContainer' => [
//						'@service_container',
//					],
//				],
			],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_sms_index' => [
                'path'       => '/sms/{page}',
                'controller' => 'MauticVonageBundle:Sms:index',
            ],
            'mautic_sms_action' => [
                'path'       => '/sms/{objectAction}/{objectId}',
                'controller' => 'MauticVonageBundle:Sms:execute',
            ],
            'mautic_sms_contacts' => [
                'path'       => '/sms/view/{objectId}/contact/{page}',
                'controller' => 'MauticVonageBundle:Sms:contacts',
            ],
        ],
        'public' => [
            'mautic_sms_callback' => [
                'path'       => '/sms/{transport}/callback',
                'controller' => 'MauticVonageBundle:Reply:callback',
            ],
            /* @deprecated as this was Vonage specific */
            'mautic_receive_sms' => [
                'path'       => '/sms/receive',
                'controller' => 'MauticVonageBundle:Reply:callback',
                'defaults'   => [
                    'transport' => 'Vonage',
                ],
            ],
			'mautic_vonage_message_status' => [
				'path'       => '/vonage/cb_message_status',
				'controller' => 'MauticVonageBundle:Callback:messageStatus',
			],
			'mautic_vonage_inbound_message' => [
				'path'       => '/vonage/cb_inbound_message',
				'controller' => 'MauticVonageBundle:Callback:inboundMessage',
			],
        ],
        'api' => [
            'mautic_api_smsesstandard' => [
                'standard_entity' => true,
                'name'            => 'smses',
                'path'            => '/smses',
                'controller'      => 'MauticVonageBundle:Api\SmsApi',
            ],
            'mautic_api_smses_send' => [
                'path'       => '/smses/{id}/contact/{contactId}/send',
                'controller' => 'MauticVonageBundle:Api\SmsApi:send',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.vonage.smses' => [
                    'route'  => 'mautic_sms_index',
                    'access' => ['sms:smses:viewown', 'sms:smses:viewother'],
                    'parent' => 'mautic.core.channels',
                    'checks' => [
                        'integration' => [
                            'Vonage' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'priority' => 70,
                ],
            ],
        ],
    ],
    'parameters' => [
        'sms_enabled'              => false,
        'sms_username'             => null,
        'sms_password'             => null,
        'sms_sending_phone_number' => null,
        'sms_frequency_number'     => 0,
        'sms_frequency_time'       => 'DAY',
        'vonage_transport'            => 'mautic.vonage.transport',
    ],
];
