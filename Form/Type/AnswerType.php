<?php


namespace MauticPlugin\MauticVonageBundle\Form\Type;


use Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryType;
use Mautic\LeadBundle\Form\Type\FilterType;
use Mautic\LeadBundle\Form\Type\LeadFieldsType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnswerType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', HiddenType::class);
		$builder->add(
			'answer',
			TextType::class,
			[
				'label'          => 'mautic.vonage.answer',
				'error_bubbling' => true,
				'attr'           => ['class' => 'form-control'],
			]
		);

		$builder->add(
			'field',
			LeadFieldsType::class,
			[
				'label'                 => 'mautic.lead.campaign.event.field',
				'label_attr'            => ['class' => 'control-label'],
				'multiple'              => false,
				'with_company_fields'   => false,
				'with_tags'             => false,
				'with_utm'              => false,
				'placeholder'           => 'mautic.core.select',
				'attr'                  => [
					'class'    => 'form-control',
//					'tooltip'  => 'mautic.lead.campaign.event.field_descr',
//					'onchange' => 'Mautic.updateLeadFieldValues(this)',
				],
				'required'    => true,
				'constraints' => [
					new NotBlank(
						['message' => 'mautic.core.value.required']
					),
				],
			]
		);

		$builder->add(
			'set_value',
			TextType::class,
			[
				'label'          => 'mautic.core.value',
				'error_bubbling' => true,
				'attr'           => ['class' => 'form-control'],
			]
		);
	}

	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		parent::buildView($view, $form, $options);

		$view->vars['preaddonAttr']  = (isset($options['attr']['preaddon_attr'])) ? $options['attr']['preaddon_attr'] : [];
		$view->vars['postaddonAttr'] = (isset($options['attr']['postaddon_attr'])) ? $options['attr']['postaddon_attr'] : [];
		$view->vars['preaddon']      = (isset($options['attr']['preaddon'])) ? $options['attr']['preaddon'] : [];
		$view->vars['postaddon']     = (isset($options['attr']['postaddon'])) ? $options['attr']['postaddon'] : [];
	}
}
