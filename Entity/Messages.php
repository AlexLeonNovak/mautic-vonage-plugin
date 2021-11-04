<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Sms.
 */
class Messages extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @var string
     */
    private $message;

	/**
	 * @var ArrayCollection
	 */
    private $answers;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $lists;

    /**
     * @var ArrayCollection
     */
    private $stats;

    /**
     * @var string
     */
    private $smsType = 'whatsapp';

    private $field;

    private $pageChangeField;

    private $pageUnsubscribe;

	/**
	 * @var string
	 */
    private $setValue;

	/**
	 * @var string
	 */
    private $senderId;

	/**
	 * @var string
	 */
    private $whatsappTemplateNamespace;

	/**
	 * @var string
	 */
    private $whatsappTemplateName;

	/**
	 * @var string
	 */
    private $whatsappTemplateButtonParameter = null;

	/**
	 * @var array
	 */
    private $whatsappTemplateParameters = [];

	/**
	 * @var array
	 */
    private $details = [];

    /**
     * @var int
     */
    private $pendingCount = 0;

    public function __clone()
    {
        $this->id        = null;
        $this->stats     = new ArrayCollection();
        $this->sentCount = 0;

        parent::__clone();
    }

    public function __construct()
    {
        $this->lists = new ArrayCollection();
        $this->stats = new ArrayCollection();
        $this->answers = new ArrayCollection();
    }

    /**
     * Clear stats.
     */
    public function clearStats()
    {
        $this->stats = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('vonage_messages')
            ->setCustomRepositoryClass('MauticPlugin\MauticVonageBundle\Entity\MessagesRepository');

        $builder->addIdColumns();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->createField('message', 'text')
			->nullable()
            ->build();

        $builder->createField('smsType', 'text')
            ->columnName('sms_type')
            ->nullable()
            ->build();

		$builder->createOneToMany('answers', 'MessageAnswers')
			->setIndexBy('id')
			->mappedBy('message')
			->cascadePersist()
			->fetchExtraLazy()
			->build();

		$builder->createField('field', 'string')
			->columnName('field')
			->nullable()
			->build();

		$builder->createField('setValue', 'string')
			->columnName('set_value')
			->nullable()
			->build();

		$builder->createField('senderId', 'string')
			->columnName('sender_id')
			->nullable()
			->build();

		$builder->createField('whatsappTemplateNamespace', 'string')
			->columnName('whatsapp_template_namespace')
			->nullable()
			->build();

		$builder->createField('whatsappTemplateName', 'string')
			->columnName('whatsapp_template_name')
			->nullable()
			->build();

		$builder->createField('whatsappTemplateParameters', 'json_array')
			->columnName('whatsapp_template_parameters')
			->nullable()
			->build();

		$builder->createField('whatsappTemplateButtonParameter', 'string')
			->columnName('whatsapp_template_button_parameter')
			->nullable()
			->build();

		$builder->addField('details', 'json_array');

        $builder->addPublishDates();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->addCategory();

        $builder->createManyToMany('lists', 'Mautic\LeadBundle\Entity\LeadList')
            ->setJoinTable('vonage_message_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('message_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

		$builder->createManyToOne('pageChangeField', 'Mautic\PageBundle\Entity\Page')
			->addJoinColumn('page_change_field', 'id', true, false, 'SET NULL')
			->build();

		$builder->createManyToOne('pageUnsubscribe', 'Mautic\PageBundle\Entity\Page')
			->addJoinColumn('page_unsubscribe', 'id', true, false, 'SET NULL')
			->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('messages')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();
    }

	/**
	 * @return mixed
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * @param mixed $field
	 */
	public function setField($field): self
	{
		$this->field = $field;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSetValue(): ?string
	{
		return $this->setValue;
	}

	/**
	 * @param string $setValue
	 */
	public function setSetValue(string $setValue): self
	{
		$this->setValue = $setValue;

		return $this;
	}

	/**
	 * @return Page
	 */
	public function getPageChangeField()
	{
		return $this->pageChangeField;
	}

	/**
	 * @param mixed $pageChangeField
	 */
	public function setPageChangeField(Page $pageChangeField): self
	{
		$this->pageChangeField = $pageChangeField;
		return $this;
	}

	/**
	 * @return Page
	 */
	public function getPageUnsubscribe()
	{
		return $this->pageUnsubscribe;
	}

	/**
	 * @param mixed $pageUnsubscribe
	 */
	public function setPageUnsubscribe(Page $pageUnsubscribe): self
	{
		$this->pageUnsubscribe = $pageUnsubscribe;
		return $this;
	}



    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addConstraint(new Callback([
            'callback' => function (Messages $sms, ExecutionContextInterface $context) {
                $type = $sms->getSmsType();
                if ('list' == $type) {
                    $validator = $context->getValidator();
                    $violations = $validator->validate(
                        $sms->getLists(),
                        [
                            new NotBlank(
                                [
                                    'message' => 'mautic.lead.lists.required',
                                ]
                            ),
                            new LeadListAccess(),
                        ]
                    );

                    if (count($violations) > 0) {
                        foreach ($violations as $violation) {
                            $context->buildViolation($violation->getMessage())
                                ->atPath('lists')
                                ->addViolation();
                        }
                    }
                }
            },
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('sms')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'message',
                    'language',
                    'category',
					'sms'
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                    'sentCount',
                ]
            )
            ->build();
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ('category' == $prop || 'list' == $prop) {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->isChanged('message', $message);
        $this->message = $message;
    }

	/**
	 * @return ArrayCollection
	 */
	public function getAnswers()
	{
		return $this->answers;
	}

	/**
	 * @param ArrayCollection $answers
	 * @return $this
	 */
	public function setAnswers(ArrayCollection $answers)
	{
		$this->answers = $answers;

		return $this;
	}

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param $publishDown
     *
     * @return $this
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param $publishUp
     *
     * @return $this
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @param $sentCount
     *
     * @return $this
     */
    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list.
     *
     * @return self
     */
    public function addList(LeadList $list)
    {
        $this->lists[] = $list;

        return $this;
    }

    /**
     * Remove list.
     */
    public function removeList(LeadList $list)
    {
        $this->lists->removeElement($list);
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return string
     */
    public function getSmsType()
    {
        return $this->smsType;
    }

    /**
     * @param string $smsType
     */
    public function setSmsType($smsType)
    {
        $this->isChanged('smsType', $smsType);
        $this->smsType = $smsType;
    }

    /**
     * @param int $pendingCount
     *
     * @return Messages
     */
    public function setPendingCount($pendingCount)
    {
        $this->pendingCount = $pendingCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getPendingCount()
    {
        return $this->pendingCount;
    }

	/**
	 * @param string $senderId
	 *
	 * @return Messages
	 */
	public function setSenderId($senderId)
	{
		$this->senderId = $senderId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSenderId()
	{
		return trim($this->senderId);
	}

	/**
	 * @return array
	 */
	public function getDetails()
	{
		return $this->details;
	}

	/**
	 * @param array $details
	 *
	 * @return Messages
	 */
	public function setDetails($details)
	{
		$this->details = $details;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getWhatsappTemplateNamespace(): ?string
	{
		return $this->whatsappTemplateNamespace;
	}

	/**
	 * @param string $whatsappTemplateNamespace
	 * @return Messages
	 */
	public function setWhatsappTemplateNamespace(string $whatsappTemplateNamespace): Messages
	{
		$this->whatsappTemplateNamespace = $whatsappTemplateNamespace;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getWhatsappTemplateName(): ?string
	{
		return $this->whatsappTemplateName;
	}

	/**
	 * @param string $whatsappTemplateName
	 * @return Messages
	 */
	public function setWhatsappTemplateName(string $whatsappTemplateName): Messages
	{
		$this->whatsappTemplateName = $whatsappTemplateName;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getWhatsappTemplateParameters(): array
	{
		return $this->whatsappTemplateParameters;
	}

	/**
	 * @param array $whatsappTemplateParameters
	 * @return Messages
	 */
	public function setWhatsappTemplateParameters(array $whatsappTemplateParameters): Messages
	{
		$this->whatsappTemplateParameters = $whatsappTemplateParameters;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getWhatsappTemplateButtonParameter(): ?string
	{
		return trim($this->whatsappTemplateButtonParameter);
	}

	/**
	 * @param string|null $whatsappTemplateButtonParameter
	 * @return Messages
	 */
	public function setWhatsappTemplateButtonParameter(?string $whatsappTemplateButtonParameter): Messages
	{
		$this->whatsappTemplateButtonParameter = $whatsappTemplateButtonParameter;
		return $this;
	}
}
