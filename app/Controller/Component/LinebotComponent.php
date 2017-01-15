<?php

App::uses('Component', 'Controller');

use LINE\LINEBot;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;

class LinebotComponent extends Component {
	public $components = ['Mecab', 'ApiCall'];

	public function buildReplyMessage($events) {
		switch (Hash::get($events, 'events.0.message.type')) {
			case 'location':
				$replyMessage = $this->__locationReplyMessage($events);
				break;

			case 'text':
				if (Hash::get($events, 'events.0.message.text') === 'カルーセル') {
					$replyMessage = $this->__carouselReplyMessage($events);
				} else {
					$replyMessage = $this->__textReplyMessage($events);
				}
				break;

			case 'carousel':
				$replyMessage = $this->__carouselReplyMessage($events);
				break;

			default:
				if (Hash::get($events, 'events.0.type') === 'postback') {
					$replyMessage = $this->__postbackReplyMessage($events);
				}
				break;
		}

		return $replyMessage;
	}

	private function __textReplyMessage($events) {
		$textMessageBuilder = new TextMessageBuilder(Hash::get($events, 'events.0.message.type') . ':' . Hash::get($events, 'events.0.message.text'));

		return $textMessageBuilder;
	}

	private function __carouselReplyMessage($events) {
		$results = $this->ApiCall->getStoreInfo(null, null);	//アドレス, ジャンルを引数に渡せばでる
		$columns = [];

		foreach ($results['results']['shop'] as $result) {
			$detail = new PostbackTemplateActionBuilder('詳細', 'action=detail');
			$browser = new UriTemplateActionBuilder('Open in Browser', $result['urls']['pc']);
			$maps = new PostbackTemplateActionBuilder('地図を見る', 'action=map&address=' . $result['address'] . '&lat=' . $result['lat'] . '&lng=' . $result['lng']);
			$column = new CarouselColumnTemplateBuilder($result['name'], $result['catch'], $result['photo']['mobile']['l'], [$detail, $browser, $maps]);
			$columns[] = $column;
		}

		$carousel = new CarouselTemplateBuilder($columns);
		$carousel_message = new TemplateMessageBuilder("PCからの表示は対応していません", $carousel);

		return $carousel_message;
	}

	private function __postbackReplyMessage($events) {
		$this->log('POSTBACK', 'debug');
		$this->log($events, 'debug');
		$query = Hash::get($events, 'events.0.postback.data');
                parse_str($query, $data);

		switch ($data['action']) {
			case 'map':
				$postback = new LocationMessageBuilder('お店の地図を表示します', $data['address'], $data['lat'], $data['lng']);
				break;

			case 'detail':
				$postback = new TextMessageBuilder('予算とか細かいの載せる');
				break;
		}

		return $postback;
	}
}