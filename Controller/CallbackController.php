<?php


namespace MauticPlugin\MauticVonageBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticVonageBundle\Callback\HandlerContainer;
use MauticPlugin\MauticVonageBundle\Exception\CallbackHandlerNotFound;
use MauticPlugin\MauticVonageBundle\Helper\CallbackHelper;
use MauticPlugin\MauticVonageBundle\Integration\Vonage\VonageCallback;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CallbackController extends CommonController
{

	/**
	 * @var CallbackHelper
	 */
	private $callbackHelper;
	/**
	 * @var VonageCallback
	 */
	private $callback;


	public function __construct(CallbackHelper $callbackHelper, VonageCallback $callback)
	{
		$this->callbackHelper = $callbackHelper;
		$this->callback = $callback;
	}

	public function messageStatusAction(Request $request): Response
	{
		var_dump($_POST);
		if (!$request->isMethod('POST')) {
			return new Response('ERROR', 400);
		}

		$message = $this->callback->getMessage();


		return new Response('OK');

	}

	public function inboundMessageAction(Request $request): Response
	{
		if (!$request->isMethod('POST')) {
			return new Response('ERROR', 400);
		}

		$this->callback->processField();

		return new Response('OK');
	}
}
