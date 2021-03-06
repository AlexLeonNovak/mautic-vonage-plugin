<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticVonageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use MauticPlugin\MauticVonageBundle\Model\MessagesModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class SmsApiController.
 */
class SmsApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var MessagesModel
     */
    protected $model;

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->getModel('vonage.messages');
        $this->entityClass     = 'MauticPlugin\MauticVonageBundle\Entity\Sms';
        $this->entityNameOne   = 'sms';
        $this->entityNameMulti = 'smses';

        parent::initialize($event);
    }

    /**
     * @param $id
     * @param $contactId
     *
     * @return JsonResponse|Response
     */
    public function sendAction($id, $contactId)
    {
        if (!$this->get('mautic.vonage.transport_chain')->getEnabledTransports()) {
            return new JsonResponse(json_encode(['error' => ['message' => 'SMS transport is disabled.', 'code' => Response::HTTP_EXPECTATION_FAILED]]));
        }

        $message = $this->model->getEntity((int) $id);

        if (is_null($message)) {
            return $this->notFound();
        }

        $contact = $this->checkLeadAccess($contactId, 'edit');

        if ($contact instanceof Response) {
            return $this->accessDenied();
        }

        $this->get('monolog.logger.mautic')
             ->addDebug("Sending SMS #{$id} to contact #{$contactId}", ['originator' => 'api']);

        try {
            $response = $this->model->sendSms($message, $contact, ['channel' => 'api'])[$contact->getId()];
        } catch (\Exception $e) {
            $this->get('monolog.logger.mautic')->addError($e->getMessage(), ['error' => (array) $e]);

            return new Response('Interval server error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $success = !empty($response['sent']);

        if (!$success) {
            $this->get('monolog.logger.mautic')->addError('Failed to send SMS.', ['error' => $response['status']]);
        }

        $view = $this->view(
            [
                'success' => $success,
                'status'  => $this->get('translator')->trans($response['status']),
                'result'  => $response,
                'errors'  => $success ? [] : [['message' => $response['status']]],
            ],
            Response::HTTP_OK  //  200 - is legacy, we cannot change it yet
        );

        return $this->handleView($view);
    }
}
