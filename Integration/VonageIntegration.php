<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Mautic\LeadBundle\Form\Type\ListType;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class VonageIntegration.
 */
class VonageIntegration extends AbstractIntegration
{
    /**
     * @var bool
     */
    protected $coreIntegration = true;


	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function __construct(
		EventDispatcherInterface $eventDispatcher,
		CacheStorageHelper $cacheStorageHelper,
		EntityManager $entityManager,
		Session $session,
		RequestStack $requestStack,
		Router $router,
		TranslatorInterface $translator,
		Logger $logger,
		EncryptionHelper $encryptionHelper,
		LeadModel $leadModel,
		CompanyModel $companyModel,
		PathsHelper $pathsHelper,
		NotificationModel $notificationModel,
		FieldModel $fieldModel,
		IntegrationEntityModel $integrationEntityModel,
		DoNotContact $doNotContact
	) {
		$this->entityManager = $entityManager;

		parent::__construct(
			$eventDispatcher,
			$cacheStorageHelper,
			$entityManager,
			$session,
			$requestStack,
			$router,
			$translator,
			$logger,
			$encryptionHelper,
			$leadModel,
			$companyModel,
			$pathsHelper,
			$notificationModel,
			$fieldModel,
			$integrationEntityModel,
			$doNotContact
		);
	}

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Vonage';
    }

    public function getIcon()
    {
        return 'plugins/MauticVonageBundle/Assets/img/Vonage.png';
    }

    public function getSecretKeys()
    {
        return ['secret'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'key' => 'mautic.vonage.config.form.apikey',
            'secret' => 'mautic.vonage.config.form.apisecret',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' == $formArea) {
            $builder->add(
                'sending_phone_number',
                TextType::class,
                [
                    'label'      => 'mautic.sms.config.form.sms.sending_phone_number',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.sms.config.form.sms.sending_phone_number.tooltip',
                    ],

                ]
            );

			$builder->add(
				'test_mode',
				YesNoButtonGroupType::class,
				[
					'label' => 'Test mode',
					'label_attr' => ['class' => 'control-label'],
					'required'   => false,
					'attr'       => [
						'class'   => 'form-control',
					],
					'data'=> $data['test_mode'] ?? false,
				]
			);

			$leads = $this->entityManager
				->getConnection()
				->createQueryBuilder()
				->select('l.id, l.email')
				->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
				->andWhere('l.is_published = 1')
				->execute()
				->fetchAll();

			$choices = [];
			foreach ($leads as $lead) {
				$choices[$lead['email']] = $lead['id'];
			}
			$builder->add(
				'test_contacts',
				ChoiceType::class,
				[
					'multiple'          => true,
//					'error_bubbling' => false,
					'choices' => $choices,
					'label' => 'Contacts for test mode (Leads)',
					'label_attr' => ['class' => 'control-label'],
					'required'   => false,
					'attr'       => [
						'class'   => 'form-control',
					],
//					'with_labels'     => false,
//					'key_value_pairs' => false,
//					'option_required' => false,
				]
			);

//            $builder->add(
//                'disable_trackable_urls',
//                YesNoButtonGroupType::class,
//                [
//                    'label' => 'mautic.sms.config.form.sms.disable_trackable_urls',
//                    'attr'  => [
//                        'tooltip' => 'mautic.sms.config.form.sms.disable_trackable_urls.tooltip',
//                    ],
//                    'data'=> !empty($data['disable_trackable_urls']) ? true : false,
//                ]
//            );
//            $builder->add('frequency_number', NumberType::class,
//                [
//                    'scale'      => 0,
//                    'label'      => 'mautic.sms.list.frequency.number',
//                    'label_attr' => ['class' => 'control-label'],
//                    'required'   => false,
//                    'attr'       => [
//                        'class' => 'form-control frequency',
//                    ],
//                ]);
//            $builder->add('frequency_time', ChoiceType::class,
//                [
//                    'choices' => [
//                        'day'   => 'DAY',
//                        'week'  => 'WEEK',
//                        'month' => 'MONTH',
//                    ],
//                    'label'             => 'mautic.lead.list.frequency.times',
//                    'label_attr'        => ['class' => 'control-label'],
//                    'required'          => false,
//                    'multiple'          => false,
//                    'attr'              => [
//                        'class' => 'form-control frequency',
//                    ],
//                ]);
        }
    }
}
