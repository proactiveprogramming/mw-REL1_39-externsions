<?php

namespace BlueSpice\Player\Hook\BSInsertMagicAjaxGetData;

use BlueSpice\InsertMagic\Hook\BSInsertMagicAjaxGetData;

class AddDescription extends BSInsertMagicAjaxGetData {

	protected function doProcess() {
		if ( $this->type != 'tags' ) {
			return true;
		}

		$this->response->result[] = [
			'id' => 'showtime',
			'type' => 'tag',
			'name' => 'showtime',
			'desc' => $this->getContext()->msg( 'bs-player-tag-showtime-desc' )->plain(),
			'code' => '<bs:showtime width="320" height="240" >Placeholder.mp4</bs:showtime>',
			'helplink' => 'https://en.wiki.bluespice.com/wiki/Reference:Player'
		];

		return true;
	}
}
