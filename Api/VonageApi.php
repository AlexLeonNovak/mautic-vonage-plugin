<?php


namespace MauticPlugin\MauticVonageBundle\Api;

use GuzzleHttp\Client;

class VonageApi
{


	public function __construct()
	{

	}

	private function sendRequest($url, $method = 'get', $data = [])
	{
		$curl = new Client();
		$self = new self();
		$request = $curl->request(
			$method,
			rtrim($self->api_domain, '/') . $url,
			$data,
			Request::ENCODING_JSON
		)
			->setHeader('Authorization', "Bearer {$self->api_token}")
			->setHeader('Content-Type', 'application/json')
			->setOption(CURLOPT_SSL_VERIFYPEER, false)
			->setOption(CURLOPT_SSL_VERIFYHOST, false)
			->setOption(CURLOPT_FOLLOWLOCATION, true);
		$response = $request->send();
		if ($response->statusCode !== 200) {
			Yii::debug($response);
			throw new yii\base\InvalidRouteException($response->statusText);
		}
		return Json::decode($response->body);
	}
}
