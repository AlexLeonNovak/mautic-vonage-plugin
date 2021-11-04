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

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\ChannelBundle\Form\Type\ChannelType;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\DataTransformer\SortableListTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\DynamicContentFilterType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\SortableValueLabelListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Form\Type\LeadFieldsType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Mautic\PageBundle\Form\Type\PageListType;
use Mautic\PluginBundle\Form\Type\FieldsType;
use MauticPlugin\MauticVonageBundle\Entity\Messages;
use MauticPlugin\MauticVonageBundle\Form\DataTransformer\AnswerListTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SmsType.
 */
class SmsType extends AbstractType
{
	/**
	 * @var EntityManager
	 */
	private $em;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html', 'customHtml' => 'html']));
		$builder->addEventSubscriber(new FormExitSubscriber('vonage.messages', $options));

		$builder->add(
			'name',
			TextType::class,
			[
				'label' => 'mautic.sms.form.internal.name',
				'label_attr' => ['class' => 'control-label'],
				'attr' => ['class' => 'form-control'],
			]
		);

		$builder->add(
			'senderId',
			TextType::class,
			[
				'label' => 'mautic.vonage.form.internal.sender_id',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control',
					'tooltip' => 'mautic.vonage.form.internal.sender_id.tooltip'
				],
				'required'    => false,
			]
		);



		$builder->add(
			'description',
			TextareaType::class,
			[
				'label' => 'mautic.sms.form.internal.description',
				'label_attr' => ['class' => 'control-label'],
				'attr' => ['class' => 'form-control'],
				'required' => false,
			]
		);

		$builder->add(
			'message',
			TextareaType::class,
			[
				'label' => 'mautic.sms.form.message',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control editor-builder-tokens', // editor editor-advanced editor-builder-tokens
					'rows' => 6,
					'data-token-callback'  => 'email:getBuilderTokens',
					'data-token-activator' => '{',
//					'data-token-visual'    => 'false',
				],
				'required' => false,
			]
		);


		$builder->add(
			'answers',
			AnswerListType::class,
			[
				'required' => false
			]
		);


		$builder->add(
			'field',
			AllFieldsType::class,
			[
				'label'                 => 'mautic.lead.campaign.event.field',
				'label_attr'            => ['class' => 'control-label'],
//				'multiple'              => false,
//				'placeholder'           => 'mautic.core.select',
				'attr'                  => [
					'class'    => 'form-control',
				],
				'required'    => false,
			]
		);

		$builder->add(
			'setValue',
			TextType::class,
			[
				'label'          => 'mautic.vonage.form.set_value',
				'error_bubbling' => true,
				'attr'           => ['class' => 'form-control'],
				'required'    => false,
			]
		);


		$builder->add('isPublished', YesNoButtonGroupType::class);

		//add lead lists
		$transformer = new IdToEntityModelTransformer($this->em, 'MauticLeadBundle:LeadList', 'id', true);
		$builder->add(
			$builder->create(
				'lists',
				AllFieldsType::class,
				[
					'label' => 'mautic.email.form.list',
					'label_attr' => ['class' => 'control-label'],
					'attr' => [
						'class' => 'form-control',
					],
					'multiple' => true,
					'expanded' => false,
					'required' => true,
				]
			)
				->addModelTransformer($transformer)
		);

		$builder->add(
			'publishUp',
			DateTimeType::class,
			[
				'widget' => 'single_text',
				'label' => 'mautic.core.form.publishup',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control',
					'data-toggle' => 'datetime',
				],
				'format' => 'yyyy-MM-dd HH:mm',
				'required' => false,
			]
		);

		$builder->add(
			'publishDown',
			DateTimeType::class,
			[
				'widget' => 'single_text',
				'label' => 'mautic.core.form.publishdown',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control',
					'data-toggle' => 'datetime',
				],
				'format' => 'yyyy-MM-dd HH:mm',
				'required' => false,
			]
		);

		//add category
		$builder->add(
			'category',
			CategoryListType::class,
			[
				'bundle' => 'messages',
			]
		);

		$builder->add(
			'whatsappTemplateNamespace',
			TextType::class,
			[
				'label' => 'mautic.sms.form.internal.whatsapp.namespace',
				'label_attr' => ['class' => 'control-label'],
				'attr' => ['class' => 'form-control'],
				'required' => false,
			]
		);

		$builder->add(
			'whatsappTemplateName',
			TextType::class,
			[
				'label' => 'mautic.sms.form.internal.whatsapp.name',
				'label_attr' => ['class' => 'control-label'],
				'attr' => ['class' => 'form-control'],
				'required' => false,
			]
		);

		$builder->add(
			$builder->create(
				'whatsappTemplateParameters',
				SortableListType::class,
				[
					'option_required' => false,
					'label' => 'mautic.sms.form.internal.whatsapp.params',
					'label_attr' => ['class' => 'control-label'],
					'attr' => ['class' => 'form-control'],
					'required' => false,
				]
			)
		);

		$builder->add(
			'whatsappTemplateButtonParameter',
			TextType::class,
			[
				'label' => 'mautic.vonage.form.internal.whatsapp.btn_param',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control',
					'tooltip' => 'mautic.vonage.form.internal.whatsapp.btn_param.tooltip'
				],
				'required'    => false,
			]
		);


		$transformer = new IdToEntityModelTransformer($this->em, 'MauticPageBundle:Page');

		$builder->add(
			$builder->create(
			'pageChangeField',
			PageListType::class,
			[
				'label'      => 'mautic.vonage.form.page.change_field',
				'label_attr' => ['class' => 'control-label'],
				'attr'       => [
					'class'   => 'form-control',
					'tooltip' => 'mautic.vonage.form.page.change_field.descr',
					'data-get-to-text' => ''
				],
				'multiple' => false,
				'required' => false,
			]
			)->addModelTransformer($transformer)
		);

		$builder->add(
			$builder->create(
			'pageUnsubscribe',
			PageListType::class,
			[
				'label'      => 'mautic.vonage.form.page.unsubscribe',
				'label_attr' => ['class' => 'control-label'],
				'attr'       => [
					'class'   => 'form-control',
					'tooltip' => 'mautic.vonage.form.page.unsubscribe.descr',
					'data-get-to-text' => ''
				],
				'multiple' => false,
				'required' => false,
			]
			)->addModelTransformer($transformer)
		);

		$builder->add(
			'language',
			LocaleType::class,
			[
				'label' => 'mautic.core.language',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control',
				],
				'required' => false,
			]
		);
		//        $builder->add('smsType', HiddenType::class);
		$builder->add(
			'smsType',
			ChoiceType::class,
			[
				'label' => 'mautic.vonage.messagetype',
				'label_attr' => ['class' => 'control-label'],
				'attr' => [
					'class' => 'form-control',
//					'tooltip' => 'mautic.sms.config.select_default_transport',
					'onchange' => 'Mautic.changeMessageType(this)'
				],
				'choices' => [
					'WhatsApp' => 'whatsapp',
					'WhatsApp Template' => 'whatsapp_template',
					'SMS' => 'sms',
				]
			]
		);
		$builder->add('buttons', FormButtonsType::class);

		if (!empty($options['update_select'])) {
			$builder->add(
				'buttons',
				FormButtonsType::class,
				[
					'apply_text' => false,
				]
			);
			$builder->add(
				'updateSelect',
				HiddenType::class,
				[
					'data' => $options['update_select'],
					'mapped' => false,
				]
			);
		} else {
			$builder->add(
				'buttons',
				FormButtonsType::class
			);
		}

		if (!empty($options['action'])) {
			$builder->setAction($options['action']);
		}
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(
			[
				'data_class' => Messages::class,
			]
		);

		$resolver->setDefined(['update_select']);
	}

	/**
	 * @return string
	 */
	public function getBlockPrefix()
	{
		return 'message';
	}
}
