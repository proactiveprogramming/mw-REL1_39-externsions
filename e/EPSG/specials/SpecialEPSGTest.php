<?php
/**
 * Test SpecialPage for EPSG extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialEPSGTest extends SpecialPage {
	public function __construct() {
		parent::__construct( 'EPSGTest' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:EPSGTest/subpage]].
	 */
	public function execute( $sub ) {
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'epsg-test' ) );

		$out->addHelpLink( 'How to become a MediaWiki hacker' );

		$out->addWikiMsg( 'epsg-test-intro' );



		# Do stuff
		# ...
		$wikitext = '
		{|class="wikitable" style="width:100%"
		!Target value: ||x = 120700.723 m y = 487525.501 m
		|-
		|<nowiki>{{#epsg:4.88352559,52.37453253,0||7415}}</nowiki>||{{#epsg:4.88352559,52.37453253,0||7415}}
		|-
		|<nowiki>{{#epsg:4.88352559,52.37453253,0|4326|7415}}</nowiki>||{{#epsg:4.88352559,52.37453253,0|4326|7415}}
		|-
		|<nowiki>{{#epsg:4.88352559,52.37453253||7415}}</nowiki>||{{#epsg:4.88352559,52.37453253||7415}}
		|-
		|<nowiki>{{#epsg:4.88352559,52.37453253|4326|7415}}</nowiki>||{{#epsg:4.88352559,52.37453253|4326|7415}}
		|-
		|<nowiki>{{#wgs84_2epsg:52.37453253,4.88352559,0|7415}}</nowiki>||{{#wgs84_2epsg:52.37453253,4.88352559,0|7415}}
		|-
		|<nowiki>{{#wgs84_2epsg:52.37453253,4.88352559|7415}}</nowiki>||{{#wgs84_2epsg:52.37453253,4.88352559|7415}}
		|-
		!Target value: ||4.88352559 &deg;E 52.37453253&deg;N
		|-
		|<nowiki>{{#epsg:120700.723,487525.501|7415}}</nowiki>||{{#epsg:120700.723,487525.501|7415}}
		|-
		!Target value: || 52.37453253&deg;N 4.88352559 &deg;E
		|-
		|<nowiki>{{#epsg_2wgs84:120700.723,487525.501|7415}}</nowiki>||{{#epsg_2wgs84:120700.723,487525.501|7415}}
		|}

		{|class="wikitable" style="width:100%"
		!colspan=5| RD (The Netherlands)
		|-
		|colspan=3|EPSG:7415
		|<nowiki>{{#wgs84_2epsg:x,y,z|7415}}</nowiki>
		|<nowiki>{{#epsg_2wgs84:x,y,z|7415}}</nowiki>
		|-
		|Amsterdam (Westertoren)||52.37453253,4.88352559||x = 120700.723 m y = 487525.501 m||{{#wgs84_2epsg:52.37453253,4.88352559|7415}}||{{#epsg_2wgs84:120700.723,487525.501|7415}}
		|-
		|Groningen (Martinitoren)||53.21938317,6.56820053||x = 233883.131 m y = 582065.167 m||{{#wgs84_2epsg:53.21938317,6.56820053|7415}}||{{#epsg_2wgs84:233883.131,582065.167|7415}}
		|-
		!colspan=5| LB08 (Belgium)
		|-
		|colspan=3| EPSG:3812
		|<nowiki>{{#wgs84_2epsg:x,y,z|3812}}</nowiki>
		|<nowiki>{{#epsg_2wgs84:x,y,z|3812}}</nowiki>
		|-
		|Brussel (Paleizenplein)||50.842442,4.3643||x = 649686.07 m y = 670226.23 m ||{{#wgs84_2epsg:50.842442,4.3643|3812}}||{{#epsg_2wgs84:649686.07,670226.23|3812}}
		|-
		|Arlon (Butte Saint-Donat)||49.685034,5.816257||x = 754469.25 m y = 542520.00 m||{{#wgs84_2epsg:49.685034,5.816257|3812}}||{{#epsg_2wgs84:754469.25,542520.00|3812}}
		|}
   ';
    		$out->addWikiTextAsInterface( $wikitext );
    	}


    	protected function getGroupName() {
    		return 'other';
    	}
    }
