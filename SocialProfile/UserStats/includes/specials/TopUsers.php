<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class TopUsersPoints extends SpecialPage {

	public function __construct() {
		parent::__construct( 'TopUsers' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		global $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly, $wgUserLevels;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$linkRenderer = $this->getLinkRenderer();
		$out = $this->getOutput();
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// Load CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$out->setPageTitle( $this->msg( 'user-stats-alltime-title' )->plain() );

		$count = 100;
		$realcount = 50;

		$user_list = [];

		// Try cache
		$key = $cache->makeKey( 'user_stats', 'top', 'points', $realcount );
		$data = $cache->get( $key );

		if ( $data != '' ) {
			$logger->debug( "Got top users by points ({count}) from cache\n", [
				'count' => $count
			] );

			$user_list = $data;
		} else {
			$logger->debug( "Got top users by points ({count}) from DB\n", [
				'count' => $count
			] );

			$params = [];
			$params['ORDER BY'] = 'stats_total_points DESC';
			$params['LIMIT'] = $count;
			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				'user_stats',
				[ 'stats_actor', 'stats_total_points' ],
				[ 'stats_actor IS NOT NULL' ],
				__METHOD__,
				$params
			);

			$loop = 0;

			foreach ( $res as $row ) {
				$user = User::newFromId( $row->stats_actor );
				// Ensure that the user exists for real.
				// Otherwise we'll be happily displaying entries for users that
				// once existed by no longer do (account merging is a thing,
				// sadly), since user_stats entries for users are *not* purged
				// and/or merged during the account merge process (which is a
				// different bug with a different extension).
				// Also ignore flagged bot accounts, no point in showing those
				// in the top lists.
				$exists = $user->loadFromId();

				if ( $exists && !$user->getBlock() && !$user->isBot() ) {
					$user_list[] = [
						'actor' => $row->stats_actor,
						'points' => $row->stats_total_points
					];
					$loop++;
				}

				if ( $loop >= $realcount ) {
					break;
				}
			}

			$cache->set( $key, $user_list, 60 * 5 );
		}

		$recent_title = SpecialPage::getTitleFor( 'TopUsersRecent' );

		$output = '<div class="top-fan-nav">
			<h1>' . $this->msg( 'top-fans-by-points-nav-header' )->escaped() . '</h1>
			<p><b>' . $this->msg( 'top-fans-total-points-link' )->escaped() . '</b></p>';

		if ( $wgUserStatsTrackMonthly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=monthly' ) ) . '">' .
				$this->msg( 'top-fans-monthly-points-link' )->escaped() . '</a></p>';
		}

		if ( $wgUserStatsTrackWeekly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=weekly' ) ) . '">' .
				$this->msg( 'top-fans-weekly-points-link' )->escaped() . '</a></p>';
		}

		// Build nav of stats by category based on MediaWiki:Topfans-by-category
		$by_category_title = SpecialPage::getTitleFor( 'TopFansByStatistic' );

		$byCategoryMessage = $this->msg( 'topfans-by-category' )->inContentLanguage();

		if ( !$byCategoryMessage->isDisabled() ) {
			$output .= '<h1 style="margin-top:15px !important;">' .
				$this->msg( 'top-fans-by-category-nav-header' )->escaped() . '</h1>';

			$lines = explode( "\n", $byCategoryMessage->text() );
			foreach ( $lines as $line ) {
				if ( strpos( $line, '*' ) !== 0 ) {
					continue;
				} else {
					$line = explode( '|', trim( $line, '* ' ), 2 );
					$stat = $line[0];

					$link_text = $line[1];
					// Check if the link text is actually the name of a system
					// message (refs bug #30030)
					$msgObj = $this->msg( $link_text );
					if ( !$msgObj->isDisabled() ) {
						$link_text = $msgObj->text();
					}

					$output .= '<p> ';
					$output .= $linkRenderer->makeLink(
						$by_category_title,
						$link_text,
						[],
						[ 'stat' => $stat ]
					);
					$output .= '</p>';
				}
			}
		}

		$output .= '</div>';

		$x = 1;
		$output .= '<div class="top-users">';
		$last_level = '';

		foreach ( $user_list as $user ) {
			$u = User::newFromActorId( $user['actor'] );
			if ( !$u ) {
				continue;
			}
			$avatar = new wAvatar( $u->getId(), 'm' );
			$commentIcon = $avatar->getAvatarURL();

			// Break list into sections based on User Level if it's defined for this site
			if ( is_array( $wgUserLevels ) ) {
				$user_level = new UserLevel( number_format( $user['points'] ) );
				if ( $user_level->getLevelName() != $last_level ) {
					$output .= '<div class="top-fan-row"><div class="top-fan-level">
						' . htmlspecialchars( $user_level->getLevelName() ) . '
						</div></div>';
				}
				$last_level = $user_level->getLevelName();
			}

			$userLink = $linkRenderer->makeLink( $u->getUserPage(), $u->getName() );
			$output .= "<div class=\"top-fan-row\">
				<span class=\"top-fan-num\">{$x}.</span>
				<span class=\"top-fan\">
					{$commentIcon} {$userLink}
				</span>";

			$output .= '<span class="top-fan-points">' .
				$this->msg( 'top-fans-points' )->numParams( $user['points'] )->parse() . '</span>';
			$output .= '<div class="visualClear"></div>';
			$output .= '</div>';
			$x++;
		}

		$output .= '</div><div class="visualClear"></div>';
		$out->addHTML( $output );
	}
}
