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

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class StatRepository.
 */
class StatRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * @param $trackingHash
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSmsStatus($trackingHash)
    {
        $q = $this->createQueryBuilder('s');
        $q->select('s')
            ->leftJoin('s.lead', 'l')
            ->leftJoin('s.sms', 'e')
            ->where(
                $q->expr()->eq('s.trackingHash', ':hash')
            )
            ->setParameter('hash', $trackingHash);

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? $result[0] : null;
    }

    /**
     * @param      $smsId
     * @param null $listId
     *
     * @return array
     */
    public function getSentStats($smsId, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'vonage_messages_stats', 's')
            ->where('s.message_id = :sms')
            ->setParameter('sms', $smsId);

        if ($listId) {
            $q->andWhere('s.list_id = :list')
                ->setParameter('list', $listId);
        }

        $result = $q->execute()->fetchAll();

        //index by lead
        $stats = [];
        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r['lead_id'];
        }

        unset($result);

        return $stats;
    }

    /**
     * @param int|array $smsIds
     * @param int       $listId
     *
     * @return int
     */
    public function getSentCount($smsIds = null, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'vonage_message_stats', 's');

        if ($smsIds) {
            if (!is_array($smsIds)) {
                $smsIds = [(int) $smsIds];
            }
            $q->where(
                $q->expr()->in('s.message_id', $smsIds)
            );
        }

        if ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        $q->andWhere('s.is_failed = :false')
            ->setParameter('false', false, 'boolean');

        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
    }

    /**
     * Get a lead's email stat.
     *
     * @param int $leadId
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'vonage_message_stats', 's')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'vonage_messages', 'e', 's.message_id = e.id');

        if ($leadId) {
            $query->andWhere(
                $query->expr()->eq('s.lead_id', (int) $leadId)
            );
        }

        if (!empty($options['basic_select'])) {
            $query->select(
                's.message_id, s.id, s.date_sent as dateSent, e.name, e.name as sms_name,  s.is_failed as isFailed'
            );
        } else {
            $query->select(
                's.message_id, s.id, s.date_sent as dateSent, e.name, e.name as sms_name, s.is_failed as isFailed, s.list_id, l.name as list_name, s.tracking_hash as idHash, s.lead_id, s.details'
            )
                ->leftJoin('s', MAUTIC_TABLE_PREFIX.'lead_lists', 'l', 's.list_id = l.id');
        }

        if (isset($options['state'])) {
            $state = $options['state'];
            if ('failed' == $state) {
                $query->andWhere(
                    $query->expr()->eq('s.is_failed', 1)
                );
            }
        }
        $state = 'sent';

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->orX(
                    $query->expr()->like('e.name', $query->expr()->literal('%'.$options['search'].'%'))
                )
            );
        }

        if (isset($options['fromDate']) && $options['fromDate']) {
            $dt = new DateTimeHelper($options['fromDate']);
            $query->andWhere(
                $query->expr()->gte('s.date_sent', $query->expr()->literal($dt->toUtcString()))
            );
        }

        return $this->getTimelineResults(
            $query,
            $options,
            'e.name',
            's.date_'.$state
        );
    }


    public function getLeadsStats(array $leadIds)
	{
		$query = $this->getEntityManager()->getConnection()->createQueryBuilder();
		$query->select('s.id')
			->from(MAUTIC_TABLE_PREFIX.'vonage_message_stats', 's');

		if ($leadIds) {
			$query->where('s.lead_id IN (:leadIds)')
				->setParameter('leadIds', $leadIds, Connection::PARAM_STR_ARRAY)
			;
		}

		$leads = $query->execute()->fetchAll();
		$ids = array_column($leads, 'id');
		return $this->getEntities(['ids' => $ids]);
	}

	public function debug($message = null, $nl = true)
	{
		return;
		if (is_array($message) || is_object($message)) {
			$output = print_r($message, true);
		} elseif (is_bool($message)) {
			$output = '(bool) ' . ($message ? 'true' : 'false');
		} elseif (is_string($message)){
			if (trim($message)) {
				$output = $message;
			} else {
				$output = '(empty sring)';
			}
		} else {
			$output = '=======================';
		}
		if ($nl){
			$output .= PHP_EOL;
		}
		file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " " . $output, FILE_APPEND);
	}

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'vonage_message_stats')
            ->set('message_id', (int) $toLeadId)
            ->where('message_id = '.(int) $fromLeadId)
            ->execute();
    }

    public function updateField(Lead $lead)
	{

	}

    /**
     * Delete a stat.
     *
     * @param $id
     */
    public function deleteStat($id)
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX.'vonage_message_stats', ['id' => (int) $id]);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }
}
