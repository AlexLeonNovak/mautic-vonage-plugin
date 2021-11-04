<?php


namespace MauticPlugin\MauticVonageBundle\Form\Type;


use Mautic\LeadBundle\Field\FieldList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AllFieldsType extends AbstractType
{

	/**
	 * @var FieldList
	 */
	private $fieldList;

	public function __construct(FieldList $fieldList)
	{
		$this->fieldList = $fieldList;
	}
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'choices' => $this->flipSubarrays(
				$this->fieldList->getFieldList(
					true,
					true,
					['isPublished' => true, 'object' => false]
				)
			),
			'global_only'         => false,
			'required'            => false,
			'with_company_fields' => false,
			'with_tags'           => false,
			'with_utm'            => false,
		]);
	}

	/**
	 * @return string|\Symfony\Component\Form\FormTypeInterface|null
	 */
	public function getParent()
	{
		return ChoiceType::class;
	}


	private function flipSubarrays(array $masterArrays): array
	{
		return array_map(
			function (array $subArray) {
				return array_flip($subArray);
			},
			$masterArrays
		);
	}
}
