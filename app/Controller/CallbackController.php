<?php

App::uses('AppController', 'Controller');

class CallbackController extends AppController {

	public function index() {
		$this->autoRender = false;
		$this->response->type('json');

		$headers = [
			"Content-Type: application/json; charset=UTF-8",
			"Authorization: Bearer <TOKEN>"
		];

		$events = $this->request->input('json_decode', true);

		$tmp =  [
				'replyToken' => Hash::get($events, 'events.0.replyToken'),
				'messages' => [
					 [
						'type' => Hash::get($events, 'events.0.message.type'),
						'text' => Hash::get($events, 'events.0.message.type')
					]
				]
		];
		$replyMessage = json_encode($tmp);

		$curl = curl_init('https://api.line.me/v2/bot/message/reply');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $replyMessage);
		$output = curl_exec($curl);
		$this->log($output, 'debug');
		return $this->response->statusCode(200);
	}
}
