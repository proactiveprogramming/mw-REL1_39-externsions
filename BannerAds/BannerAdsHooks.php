<?php

class BannerAdsHooks {

	public static function onRecordClick( $params ) {
		if ( $params['track_app'] == "banner_ads" ) {
			$dbr = wfGetDB( DB_SLAVE );
			$dbw = wfGetDB( DB_MASTER );
			$row = $dbr->selectRow(
				"ba_ad_stats",
				"*",
				[ 
					"camp_id" => $params['camp_id'],
					"ad_id" => $params['ad_id'],
					"page_id" => $params['page_id']
				],
				__METHOD__
			);
			if ( !$row ) {
				$dbw->insert(
					'ba_ad_stats',
					[ 
						"camp_id" => $params['camp_id'],
						"ad_id" => $params['ad_id'],
						"page_id" => $params['page_id'],
						"counter" => 1
					],
					__METHOD__,
					array( 'IGNORE' )
				);
				$dbw->commit();
			} else {
				$dbw->update(
					'ba_ad_stats',
					[ 
						"counter" => $row->counter + 1
					],
					[ 'id' => $row->id ],
					__METHOD__,
					array( 'IGNORE' )
				);
				$dbw->commit();
			}
		}
	}

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		global $wgScriptPath;

		$out->addModuleStyles( 'ext.banner_ads.main' );

		$ad_data = BannerAdsProcessor::getAd( BannerAdsProcessor::AD_TYPE_MOBILE_SPLASH );
		if ( !empty( $ad_data ) ) {
			$splash_data = [ 
				'img' => $ad_data[2],
				'url' => $wgScriptPath .'/api.php?action=track_clicks&track_app=banner_ads&camp_id='. $ad_data[0] .'&ad_id='. $ad_data[1] .'&page_id='. $ad_data[4] .'&external_url='. $ad_data[3]
			];

			$out->addScript( '
				<script id="ba_splash_ad" type="text/json">
					'. json_encode( $splash_data ) .'
				</script>
			' );
			$out->addModules( "ext.banner_ads.splash" );
		}
	}

	/**
	 * Add banner to skins which output banners into the site notice area.
	 * @param string|bool &$siteNotice of the page.
	 * @param Skin $skin being used.
	 */
	public static function onSiteNoticeAfter( &$siteNotice, Skin $skin ) {
		global $wgScriptPath;

		$ad_data = BannerAdsProcessor::getAd( BannerAdsProcessor::AD_TYPE_MOBILE_TOP );

		if ( !empty( $ad_data ) ) {
			$siteNotice = '
				<div class="ba_mobile_ad">
					<a href="'. $wgScriptPath .'/api.php?action=track_clicks&track_app=banner_ads&camp_id='. $ad_data[0] .'&ad_id='. $ad_data[1] .'&page_id='. $ad_data[4] .'&external_url='. $ad_data[3] .'">
						<img src="'. $ad_data[2] .'" border="0" width="970" height="90" alt="" class="img_ad">
					</a>
				</div>
			';
		}
		$ad_data = BannerAdsProcessor::getAd( BannerAdsProcessor::AD_TYPE_MOBILE_BOT_STICKY );

		if ( !empty( $ad_data ) ) {
			$siteNotice .= '
				<div class="ba_mobile_ad ba_fixed_bottom">
					<a href="'. $wgScriptPath .'/api.php?action=track_clicks&track_app=banner_ads&camp_id='. $ad_data[0] .'&ad_id='. $ad_data[1] .'&page_id='. $ad_data[4] .'&external_url='. $ad_data[3] .'">
						<img src="'. $ad_data[2] .'" border="0" width="970" height="90" alt="" class="img_ad">
					</a>
				</div>
			';
		}
	}

	function onLoadExtensionSchemaUpdate( $updater ) {
		$updater->addExtensionTable( 'ba_campaign',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_campaign_pages',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_adset',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_ad',
			__DIR__ . '/bannerads.sql', true );
		$updater->addExtensionTable( 'ba_ad_stats',
			__DIR__ . '/bannerads.sql', true );
		return true;
	}
}