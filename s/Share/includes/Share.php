<?php

use MediaWiki\MediaWikiServices;

class ShareHooks {
	public static function onSidebarBeforeOutput( Skin $skin, &$sidebar ) {
		global $wgExtensionAssetsPath, $wgShareFacebook, $wgShareTwitter,
			$wgShareLinkedIn, $wgShareTumblr, $wgShareReddit,
			$wgShareEmail, $wgShareUseBasicButtons,
			$wgShareUsePlainLinks;

		// Get title
		$query = $skin->getRequest()->getQueryValues();
		if ( isset( $query['title'] ) ) {
			unset( $query['title'] );
		}

		// Allows us to get the full URL path without having to use $_SERVER and such
		$currenturl = $skin->getTitle()->getFullURL( $query, false, PROTO_CANONICAL );

		// Allow us to store preferences on users so that users can enable or disable the sidebar and to check if user has permission to see sidebar links
		$services = MediaWikiServices::getInstance();
		$user = $skin->getUser();
		$permissionManager = $services->getPermissionManager();
		$userOptionsLookup = $services->getUserOptionsLookup();

		// Check if the user has the right to see the links
		if ( $permissionManager->userHasRight( $user, 'viewsharelinks' ) ) {
			// If Share is disabled in the User preferences then don't show (Default is enabled)
			if ( !$userOptionsLookup->getOption( $user, 'sharesidebar', 0 ) ) {
				// 'Full' Mode - Displays buttons straight from each platform's social plugin library
				if ( !$wgShareUseBasicButtons && !$wgShareUsePlainLinks ) {
					if ( $wgShareFacebook ) {
						$sidebar['share-header'][] = [
							'html' => '<iframe src="https://www.facebook.com/plugins/share_button.php?href=https%3A%2F%2F' . urlencode( $currenturl ) . '&layout=button&size=small&width=67&height=20&appId" width="67" height="20" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>',
						];
					}

					if ( $wgShareTwitter ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="https://twitter.com/share" class="twitter-share-button" rel="nofollow" data-dnt="true" data-show-count="false">Tweet</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>',
						];
					}

					if ( $wgShareLinkedIn ) {
						$sidebar['share-header'][] = [
							'html' => '<script src="https://platform.linkedin.com/in.js" type="text/javascript">lang: en_US</script> <script type="IN/Share" data-url="https://www.linkedin.com"></script>',
						];
					}

					if ( $wgShareTumblr ) {
						$sidebar['share-header'][] = [
							'html' => '<a class="tumblr-share-button" href="https://www.tumblr.com/share"></a><script id="tumblr-js" async src="https://assets.tumblr.com/share-button.js"></script>',
						];
					}
				}

				// 'Sidebar images' mode - Doesn't load the button from each platform's social plugin library but instead displays images saying "Share"
				if ( $wgShareUseBasicButtons && !$wgShareUsePlainLinks ) {
					if ( $wgShareEmail ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="mailto:%20?body=' . urlencode( $currenturl ) . '"><img src="' . $wgExtensionAssetsPath . '/Share/resources/images/email.png" alt="' . $skin->msg( 'share-email' ) . '" width="90" height="30"></a>',
						];
					}

					if ( $wgShareFacebook ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode( $currenturl ) . '"><img src="' . $wgExtensionAssetsPath . '/Share/resources/images/facebook.png" alt="' . $skin->msg( 'share-facebook' ) . '" width="90" height="30"></a>',
						];
					}

					if ( $wgShareLinkedIn ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode( $currenturl ) . '"><img src="' . $wgExtensionAssetsPath . '/Share/resources/images/linkedin.png" alt="' . $skin->msg( 'share-linkedin' ) . '" width="90" height="30"></a>',
						];
					}

					if ( $wgShareReddit ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="https://www.reddit.com/submit?url=' . urlencode( $currenturl ) . '"><img src="' . $wgExtensionAssetsPath . '/Share/resources/images/reddit.png" alt="' . $skin->msg( 'share-redit' ) . '" width="90" height="30"></a>',
						];
					}

					if ( $wgShareTumblr ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="https://www.tumblr.com/share/link?url=' . urlencode( $currenturl ) . '"><img src="' . $wgExtensionAssetsPath . '/Share/resources/images/tumblr.png" alt="' . $skin->msg( 'share-tumblr' ) . '" width="90" height="30"></a>',
						];
					}

					if ( $wgShareTwitter ) {
						$sidebar['share-header'][] = [
							'html' => '<a href="https://www.twitter.com/share?url=' . urlencode( $currenturl ) . '"><img src="' . $wgExtensionAssetsPath . '/Share/resources/images/twitter.png" alt="' . $skin->msg( 'share-twitter' ) . '" width="90" height="30"></a>',
						];
					}
				}

				// 'Plain Sidebar Links' mode - Displays all "Share" buttons as plain sidebar links as if they were any other link in the sidebar
				if ( !$wgShareUseBasicButtons && $wgShareUsePlainLinks ) {
					if ( $wgShareFacebook ) {
						$sidebar['share-header'][] = [
							'text' => $skin->msg( 'share-facebook' ),
							'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode( $currenturl ),
							'title' => $skin->msg( 'share-facebook' ),
							'id' => 'n-facebookshare',
						];
					}

					if ( $wgShareTwitter ) {
						$sidebar['share-header'][] = [
							'text' => $skin->msg( 'share-twitter' ),
							'href' => 'https://www.twitter.com/share?url=' . urlencode( $currenturl ),
							'title' => $skin->msg( 'share-twitter' ),
							'id' => 'n-twittershare',
						];
					}

					if ( $wgShareEmail ) {
						$sidebar['share-header'][] = [
							'text' => $skin->msg( 'share-email' ),
							'href' => 'mailto:%20?body=' . urlencode( $currenturl ),
							'title' => $skin->msg( 'share-email' ),
							'id' => 'n-emailshare',
						];
					}

					if ( $wgShareLinkedIn ) {
						$sidebar['share-header'][] = [
							'text' => $skin->msg( 'share-linkedin' ),
							'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode( $currenturl ),
							'title' => $skin->msg( 'share-linkedin' ),
							'id' => 'n-linkedinshare',
						];
					}

					if ( $wgShareReddit ) {
						$sidebar['share-header'][] = [
							'text' => $skin->msg( 'share-reddit' ),
							'href' => 'https://www.reddit.com/submit?url=' . urlencode( $currenturl ),
							'title' => $skin->msg( 'share-reddit' ),
							'id' => 'n-redditshare',
						];
					}

					if ( $wgShareTumblr ) {
						$sidebar['share-header'][] = [
							'text' => $skin->msg( 'share-tumblr' ),
							'href' => 'https://www.tumblr.com/share/link?url=' . urlencode( $currenturl ),
							'title' => $skin->msg( 'share-tumblr' ),
							'id' => 'n-tumblrshare',
						];
					}
				}
			}
		}
	}

	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['sharesidebar'] = [
			'type' => 'toggle',
			'label-message' => 'share-preftoggle',
			'section' => 'rendering',
		];
	}
}
