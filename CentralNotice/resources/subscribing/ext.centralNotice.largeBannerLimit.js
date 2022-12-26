/**
 * Large banner limit mixin. Shows readers a large banner once, then switches
 * to small banners for a configurable number of days.
 *
 * Requires a campaign with four buckets. Buckets A and B are assumed to contain
 * large banners, and buckets C and D small banners.
 *
 * Replaces the following scripts:
 * https://meta.wikimedia.org/wiki/MediaWiki:FR2014/Resources/ShowHideCheckFullscreen.js
 * https://meta.wikimedia.org/wiki/MediaWiki:FR2014/Resources/ChangeBucket-AtoC-BtoD.js
 */
( function () {
	'use strict';

	var identifier, days, multiStorageOption,
		cn = mw.centralNotice,
		forced = mw.util.getParamValue( 'force' ),
		mixin = new cn.Mixin( 'largeBannerLimit' ),

		// We also check global identifiers from the banner sequence mixin.
		// Coordinate both storage keys with ext.centralNotice.bannerSequence.js.
		// TODO Maybe find a better way to do this?
		STORAGE_KEY = 'large_banner_limit',
		BANNER_SEQUENCE_FLAG_STORAGE_KEY = 'banner_sequence_step_seen';

	function isLarge() {
		return ( cn.getDataProperty( 'reducedBucket' ) <= 1 );
	}

	/**
	 * Check for a large-banner-seen flag in a legacy cookie. If one is found,
	 * remove the cookie and store a non-legacy flag.
	 */
	function possiblyMigrateLegacyCookie() {

		// Legacy cookie required an identifier
		if ( !identifier ) {
			return;
		}

		if ( mw.cookie.get( identifier, '' ) ) {

			cn.setDebugInfo( 'lbl: setting flag from legacy cookie' );
			setFlag();

			// Remove the legacy cookie
			mw.cookie.set( identifier, null, {
				path: '/',
				prefix: ''
			} );
		}
	}

	/**
	 * Check storage for a large-banner-seen flag
	 *
	 * @return {boolean} true if there's a flag
	 */
	function checkFlag() {

		// Also check flags set by banner sequence (only global context check needed)
		// TODO Refactor?
		if ( identifier ) {
			return Boolean( cn.kvStore.getItem(
				STORAGE_KEY + '_' + identifier,
				cn.kvStore.contexts.GLOBAL,
				multiStorageOption

			) ) || Boolean( cn.kvStore.getItem(
				BANNER_SEQUENCE_FLAG_STORAGE_KEY + '_' + identifier,
				cn.kvStore.contexts.GLOBAL,
				multiStorageOption
			) );
		}

		return Boolean( cn.kvStore.getItem(
			STORAGE_KEY,
			cn.kvStore.contexts.CATEGORY,
			multiStorageOption
		) );
	}

	/**
	 * Set a flag to remember the reader has just seen a large banner
	 */
	function setFlag() {

		// Compact timestamp by removing ms
		var nowTS = Math.round( Date.now() / 1000 );

		if ( identifier ) {

			cn.kvStore.setItem(
				STORAGE_KEY + '_' + identifier,
				nowTS,
				cn.kvStore.contexts.GLOBAL,
				days,
				multiStorageOption
			);

		} else {

			cn.kvStore.setItem(
				STORAGE_KEY,
				nowTS,
				cn.kvStore.contexts.CATEGORY,
				days,
				multiStorageOption
			);
		}
	}

	mixin.setPreBannerHandler( function ( mixinParams ) {

		var switchToHigherBucket = false;

		// Forced URL param. If we're showing a banner, it'll be the one for
		// whichever bucket we're already in. No changes to storage.
		if ( forced ) {
			return;
		}

		identifier = mixinParams.identifier;
		days = mixinParams.days;

		// Check if and how we can store a flag saying a large banner was shown
		multiStorageOption = cn.kvStore.getMultiStorageOption(
			cn.getDataProperty( 'campaignCategoryUsesLegacy' ) );

		// In all cases, check for a legacy cookie and try to migrate
		// if one was found
		possiblyMigrateLegacyCookie();

		// No need to switch if the banner's already hidden or we're already
		// on a small banner bucket
		if ( cn.isCampaignFailed() ) {
			cn.setDebugInfo( 'lbl: hidden' );
			return;
		}

		if ( !isLarge() ) {
			cn.setDebugInfo( 'lbl: previously switched' );
			return;
		}

		// If we can't store a flag, or if there is a flag, go to a small banner

		if ( multiStorageOption === cn.kvStore.multiStorageOptions.NO_STORAGE ) {
			cn.setDebugInfo( 'lbl: no storage, switching' );
			switchToHigherBucket = true;

		} else if ( checkFlag() ) {

			// Note: if there was a legacy cookie flag, either it was migrated (in
			// which case checkFlag() will return true) or it was deleted and couldn't
			// be set on the current system, due to having no storage options (in
			// which case we'll always switch to small banners).

			cn.setDebugInfo( 'lbl: flag found, switching' );
			switchToHigherBucket = true;
		}

		if ( switchToHigherBucket ) {
			if ( mixinParams.randomize ) {
				cn.setBucket( Math.floor( Math.random() * 2 ) + 2 );
			} else {
				cn.setBucket( cn.getDataProperty( 'bucket' ) + 2 );
			}
		}

		// Otherwise, a large banner can be shown! We'll check if it really
		// gets shown, and set a flag, in the post banner handler.
	} );

	mixin.setPostBannerOrFailHandler( function () {

		// If a large banner was shown, but not forced, set a flag to remember
		// the reader has seen a large banner. The next time they might
		// otherwise see one, they will be moved to a small banner bucket by
		// the pre-banner handler.
		if (
			isLarge() &&
			!forced &&
			cn.isBannerShown()
		) {
			cn.setDebugInfo( 'lbl: setting flag' );
			setFlag();
		}
	} );

	// Register the mixin
	cn.registerCampaignMixin( mixin );

}() );
