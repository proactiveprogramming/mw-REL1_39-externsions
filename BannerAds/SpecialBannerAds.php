<?php

class SpecialBannerAds extends SpecialPage {

	public function __construct() {
		parent::__construct( 'BannerAds' );
	}

	function execute( $subpage ) {
		global $wgUser;
		if ( !$wgUser->isLoggedIn() ) {
			$this->getOutput()->redirect( SpecialPage::getTitleFor( 'Userlogin' )->getFullURL( 'returnto=Special:BannerAds' ) );
			return;
		}
		if ( !in_array( 'sysop', $wgUser->getEffectiveGroups() ) ) {
			$this->getOutput()->addHTML( 'You do not have the necessary permissions to view this page.' );
			return;
		}

		$html = '
<div class="" style="">
	<ul id="tabs" class="nav nav-tabs" role="tablist">
	  <li role="presentation" class="nav-item"><a class="nav-link active" aria-controls="campaigns" role="tab" href="#campaigns" data-toggle="tabs">Campaigns</a></li>
	  <li role="presentation" class="nav-item"><a class="nav-link" aria-controls="ads" role="tab" href="#ads" data-toggle="tabs">Ads</a></li>
	  <li role="presentation" class="nav-item"><a class="nav-link" aria-controls="ad_target" role="tab" href="#ad_target" data-toggle="tabs">Ad Targeting</a></li>
	  <li role="presentation" class="nav-item"><a class="nav-link" aria-controls="stats" role="tab" href="#stats" data-toggle="tabs">Stats</a></li>
	</ul>
	<div class="tab-content card panel-default">
		<div role="tabpanel" class="tab-pane active card-body" id="campaigns">
			<button type="button" class="btn btn-primary" id="create_camp">Create Campaign</button>
			<div id="camp_list" style="margin-top:10px;"></div>
		</div>
		<div role="tabpanel" class="tab-pane card-body" id="ads">
			<button type="button" class="btn btn-primary" id="create_ad">Create Ad</button>
			<div id="ads_list" style="margin-top:10px;"></div>
		</div>
		<div role="tabpanel" class="tab-pane card-body" id="ad_target">
			<button type="button" class="btn btn-primary" id="add_target">Ad New Target</button>
			<div id="ad_target_list" style="margin-top:10px;"></div>
		</div>
		<div role="tabpanel" class="tab-pane card-body" id="stats">
			<div class="dropdown btn-group">
			  <button class="btn btn-default dropdown-toggle" type="button" id="camp_selector" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
				Select Campaign
			  </button>
			  <ul class="dropdown-menu" aria-labelledby="camp_selector">
			  </ul>
			</div>
			<div id="stats_list" style="margin-top:10px;"></div>
		</div>
	</div>
</div>
		';

		$this->getOutput()->addHTML( $html );
		$this->getOutput()->addModules( 'ext.bootstrap' );
		$this->getOutput()->addModules( 'ext.jquery_confirm' );
		$this->getOutput()->addModules( 'ext.banner_ads.special' );
	}
}
