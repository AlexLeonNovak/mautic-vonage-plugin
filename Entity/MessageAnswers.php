<?php


namespace MauticPlugin\MauticVonageBundle\Entity;


use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Doctrine\ORM\Mapping as ORM;

class MessageAnswers
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var Messages
	 */
	private $message;


	/**
	 * @var string
	 */
	private $answer;

	/**
	 * @var string
	 */
	private $field;

	/**
	 * @var string
	 */
	private $set_value;

	public static function loadMetadata(ORM\ClassMetadata $metadata)
	{
		$builder = new ClassMetadataBuilder($metadata);

		$builder->setTable('vonage_message_answers')
			->setCustomRepositoryClass('MauticPlugin\MauticVonageBundle\Entity\MessageAnswersRepository');

		$builder->addBigIntIdField();

		$builder->createManyToOne('message', 'Messages')
			->inversedBy('answers')
			->addJoinColumn('message_id', 'id', true, false, 'SET NULL')
			->build();

		$builder->addField('answer', 'string');
		$builder->addField('field', 'string');
		$builder->addField('set_value', 'string');
	}

	/**
	 * Prepares the metadata for API usage.
	 *
	 * @param $metadata
	 */
	public static function loadApiMetadata(ApiMetadataDriver $metadata)
	{
		$metadata->setGroupPrefix('messageAnswers')
			->addProperties(
				[
					'id',
					'answer',
					'field',
					'set_value',
				]
			)
			->build();
	}



	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return Messages
	 */
	public function getMessage(): Messages
	{
		return $this->message;
	}

	/**
	 * @param Messages $message
	 */
	public function setMessage(Messages $message): void
	{
		$this->message = $message;
	}


	/**
	 * @return string
	 */
	public function getAnswer(): string
	{
		return strtolower(trim($this->answer));
	}

	/**
	 * @param string $answer
	 */
	public function setAnswer(string $answer): void
	{
		$this->answer = $answer;
	}

	/**
	 * @return string
	 */
	public function getField(): string
	{
		return $this->field;
	}

	/**
	 * @param string $field
	 */
	public function setField(string $field): void
	{
		$this->field = $field;
	}

	/**
	 * @return string
	 */
	public function getSetValue(): string
	{
		return $this->set_value;
	}

	/**
	 * @param string $set_value
	 */
	public function setSetValue(string $set_value): void
	{
		$this->set_value = $set_value;
	}
}
