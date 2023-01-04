'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class NewPagesFeed extends Page {

	get listview() { return $( '#mwe-pt-list-view' ); }

	open() {
		super.openTitle( 'Special:NewPagesFeed' );
	}
}

module.exports = new NewPagesFeed();