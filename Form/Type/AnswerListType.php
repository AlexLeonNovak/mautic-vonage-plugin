<?php


namespace MauticPlugin\MauticVonageBundle\Form\Type;


use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\DataTransformer\SortableListTransformer;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\SortableValueLabelListType;
use MauticPlugin\MauticVonageBundle\Form\DataTransformer\AnswerListTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnswerListType extends SortableListType
{
	private $em;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
//		$transformer = new IdToEntityModelTransformer($this->em, 'MauticPlugin\MauticVonageBundle\Entity\MessageAnswers', 'id', true);
		$builder->add(
			$builder->create(
				'list',
				CollectionType::class,
				[
					'label'          => false,
					'entry_type'     => AnswerType::class,
					'entry_options'  => [
						'label'          => false,
						'required'       => false,
						'attr'           => [
							'class'         => 'form-control',
							'preaddon'      => $options['remove_icon'],
							'preaddon_attr' => [
								'onclick' => $options['remove_onclick'],
							],
							'postaddon'     => $options['sortable'],
						],
						'constraints'    => $options['option_constraint'],
						'error_bubbling' => true,
					],
					'allow_add'      => true,
					'allow_delete'   => true,
					'prototype'      => true,
					'constraints'    => [],
					'error_bubbling' => false,
				]
			)//->addModelTransformer($transformer)
		)->addModelTransformer(new AnswerListTransformer($this->em));
	}


	public function getBlockPrefix()
	{
		return 'answerlist';
	}
}
