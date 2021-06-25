<?php


namespace MauticPlugin\MauticVonageBundle\Form\DataTransformer;



use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class AnswerListTransformer implements DataTransformerInterface
{
	private $repo;

	public function __construct(EntityManager $em)
	{
		$this->repo = $em->getRepository('MauticVonageBundle:MessageAnswers');
	}

	public function transform($value)
	{
		if (null === $value) {
			return new ArrayCollection();
		}
		if ($value instanceof \Doctrine\ORM\PersistentCollection) {
			$message = $value->getOwner();
			$answers = $message->getAnswers();
			$array = [];
			foreach ($answers as $answer) {
				$array[] = [
					'id' => $answer->getId(),
					'answer' => $answer->getAnswer(),
					'field' => $answer->getField(),
					'set_value' => $answer->getSetValue(),
				];
			}
			$value = ['list' => $array];//new ArrayCollection(['list' => $array]);
		}
		return $value;

	}

	public function reverseTransform($value)
	{
		if (empty($value) || !is_array($value) || !isset($value['list'])) {
			return new ArrayCollection();
		}

		$result = new ArrayCollection();
		foreach ($value['list'] as $key => $array) {
			$ans = new \MauticPlugin\MauticVonageBundle\Entity\MessageAnswers();
			$ans->setAnswer($array['answer']);
			$ans->setField($array['field']);
			$ans->setSetValue($array['set_value']);
			$result->add($ans);
		}
		return $result;
	}
}
