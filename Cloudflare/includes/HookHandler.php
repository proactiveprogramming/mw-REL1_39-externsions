<?php

namespace MediaWiki\Extension\Cloudflare;

use Cloudflare\API as Cloudflare_API;
use Config;
use File;
use MediaWiki\Hook\LocalFilePurgeThumbnailsHook;
use MediaWiki\Hook\TitleSquidURLsHook;
use Title;

class HookHandler implements
	TitleSquidURLsHook,
	LocalFilePurgeThumbnailsHook
{
	/** @var Config */
	public $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * TitleSquidURLs
	 * @param Title $title
	 * @param string[] &$urls
	 * @return bool|void
	 */
	public function onTitleSquidURLs( $title, &$urls ): void {
		// TODO: MobileFrontendの後に読み込まれる必要性あり
		if ( $this->config->get( 'CloudflarePurgePage' ) ) {
			$purge = $this->purge( $urls );
		}
	}

	/**
	 * LocalFilePurgeThumbnails
	 * @param File $file
	 * @param string | false $archiveName
	 * @param string[] $urls
	 * @return bool|void
	 */
	public function onLocalFilePurgeThumbnails( $file, $archiveName, $urls ): void {
		// TODO:画像だけ違うzoneの可能性ある??
		if ( $this->config->get( 'CloudflarePurgeFile' ) ) {
			$serve = $this->config->get( 'Server' );
			$purgeURL = array_map( static function ( string $path )use( $serve ){
				return $serve . $path;
			}, $urls );
			$purge = $this->purge( $purgeURL );
		}
	}

	/**
	 * Cloudflare purge
	 * @param array $urls
	 * @return bool
	 */
	public function purge( array $urls ): bool {
		$email = $this->config->get( 'CloudflareEmail' );
		$apiKey = $this->config->get( 'CloudflareAPIKey' );
		$zoneID = $this->config->get( 'CloudflareZoneID' );
		try {
			$key = new Cloudflare_API\Auth\APIKey( $email, $apiKey );
			$adapter = new Cloudflare_API\Adapter\Guzzle( $key );
			$zone = new Cloudflare_API\Endpoints\Zones( $adapter );
			return $zone->cachePurge( $zoneID, $urls );
		}catch ( Cloudflare_API\Endpoints\EndpointException | \Exception $e ) {
			# TODO:Cloudflare API: {exception} log
		}
		return false;
	}

}
