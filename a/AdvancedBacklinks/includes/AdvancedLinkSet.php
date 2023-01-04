<?php
/**
 * AdvancedBacklinks
 * Copyright (C) 2019  Ostrzyciel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class AdvancedLinkSet {

	/**
	 * @var AdvancedWikilink[]
	 */
	public $wikilinks = [];

	/**
	 * @var AdvancedImagelink[]
	 */
	public $imagelinks = [];

	/**
	 * @var bool[][][][]
	 */
	protected $wikilinkIdSet = [];

	/**
	 * @var bool[][][]
	 */
	protected $imagelinkIdSet = [];

	/**
	 * Adds a link to the set. Returns true if the link was added, false if the link is already in the set or was invalid.
	 *
	 * @param AdvancedLink $link
	 *
	 * @return bool
	 */
	public function addLink( AdvancedLink $link ) {
		$fromID = $link->getFromID();
		$throughID = $link->getThroughID();
		$targetTitle = $link->target->getDBkey();

		//invalid title or something
		if ( !$targetTitle ) {
			return false;
		}
		if ( $link instanceof AdvancedWikilink ) {
			$targetNS = $link->target->getNamespace();

			if ( isset( $this->wikilinkIdSet[$fromID][$throughID][$targetNS][$targetTitle] ) ) {
				return false;
			} else {
				$this->wikilinkIdSet[$fromID][$throughID][$targetNS][$targetTitle] = true;
				$this->wikilinks[] = $link;
				return true;
			}
		} else if ( $link instanceof AdvancedImagelink ) {
			if ( isset( $this->imagelinkIdSet[$fromID][$throughID][$targetTitle] ) ) {
				return false;
			} else {
				$this->imagelinkIdSet[$fromID][$throughID][$targetTitle] = true;
				$this->imagelinks[] = $link;
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * In some cases (such as redirect pages) wikilinks can get "lost" by the AdvancedBacklinks parser, so this is a
	 * method that tries to fill any gaps that may have been left by it, by comparing its results with MediaWiki's.
	 *
	 * @param Title $fromTitle
	 * @param int $ns
	 * @param string $dbKey
	 * @return bool
	 */
	public function maybeAddMissingWikilink( Title $fromTitle, int $ns, string $dbKey ) {
		$fromID = $fromTitle->getArticleID();
		if ( sizeof( $this->wikilinkIdSet ) === 1 ) {
			//for new pages we assume page ID 0
			$fromID = array_key_first( $this->wikilinkIdSet );
		}
		if ( isset( $this->wikilinkIdSet[$fromID] ) ) {
			foreach ( $this->wikilinkIdSet[$fromID] as $subSet ) {
				if ( isset( $subSet[$ns][$dbKey] ) ) {
					return false;
				}
			}
		}

		$this->wikilinkIdSet[$fromID][0][$ns][$dbKey] = true;
		$this->wikilinks[] = new AdvancedWikilink( $fromTitle, Title::newFromText( $dbKey, $ns ) );
		return true;
	}

	/**
	 * The same as maybeAddMissingLink, just for imagelinks
	 *
	 * @param Title $fromTitle
	 * @param string $dbKey
	 * @return bool
	 */
	public function maybeAddMissingImagelink( Title $fromTitle, string $dbKey ) {
		$fromID = $fromTitle->getArticleID();
		if ( sizeof( $this->imagelinkIdSet ) === 1 ) {
			//for new pages we assume page ID 0
			$fromID = array_key_first( $this->imagelinkIdSet );
		}
		if ( isset( $this->imagelinkIdSet[$fromID] ) ) {
			foreach ( $this->imagelinkIdSet[$fromID] as $subSet ) {
				if ( isset( $subSet[$dbKey] ) ) {
					return false;
				}
			}
		}

		$this->imagelinkIdSet[$fromID][0][$dbKey] = true;
		$this->imagelinks[] = new AdvancedImagelink( $fromTitle, Title::newFromText( $dbKey, 6 ) );
		return true;
	}

	/**
	 * Performs database update of this set of links.
	 * @param Title $title
	 * @throws Exception
	 */
	public function updateLinksFromPageInDB( Title $title ) {
		if ( $title->getArticleID() < 0 ) {
			throw new Exception( 'No such page' );
		}
		if ( sizeof( $this->wikilinkIdSet ) > 1 || sizeof( $this->imagelinkIdSet ) > 1 ) {
			throw new Exception( 'This link set contains links from multiple pages: ' .
				json_encode( [ 'wikilinks' => $this->wikilinkIdSet, 'imagelinks' => $this->imagelinkIdSet ] ) );
		}

		$this->updateWikilinksFromPage( $title );
		$this->updateImagelinksFromPage( $title );
	}

	private function updateWikilinksFromPage( Title $title ) {
		$foundNewLinks = array_fill( 0, sizeof( $this->wikilinks ), false );

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->startAtomic( __METHOD__ );
		$res = $dbw->select( 'ab_links',
			[
				'abl_namespace',
				'abl_title',
				'abl_from',
				'abl_through',
				'abl_hidden_through'
			],
			[ 'abl_from' => $title->getArticleID() ],
			__METHOD__
		);

		foreach ( $res as $dbLink ) {
			$oldLink = AdvancedWikilink::newFromDBrow( $dbLink );
			if ( !$oldLink ) {
				//invalid link, continue
				continue;
			}
			$oldFound = false;

			for ( $i = 0; $i < sizeof( $this->wikilinks ); $i++ ) {
				if ( $foundNewLinks[$i] ) {
					continue;
				}

				if ( $oldLink->isEqualTo( $this->wikilinks[$i] ) ) {
					$oldFound = true;
					$foundNewLinks[$i] = true;
					break;
				}
			}

			if ( !$oldFound ) {
				//this link is no longer present in the article, we have to remove it
				wfDebugLog(
					'AdvancedBacklinks',
					"Deleting {$oldLink->getTextForLogs()}"
				);

				$dbw->delete( 'ab_links',
					[
						'abl_from' => $dbLink->abl_from,
						'abl_title' => $dbLink->abl_title,
						'abl_namespace' => $dbLink->abl_namespace,
						'abl_through' => $dbLink->abl_through,
						'abl_hidden_through' => $dbLink->abl_hidden_through
					],
					__METHOD__ );
			}
		}

		//now for the links that we have to insert...
		for ( $i = 0; $i < sizeof( $this->wikilinks ); $i++ ) {
			if ( $foundNewLinks[$i] )
				continue;

			//this must be a new link, it's not present in the DB or the page has been moved or some other wild stuff
			$link = $this->wikilinks[$i];
			wfDebugLog(
				'AdvancedBacklinks',
				"Inserting {$link->getTextForLogs()}"
			);

			$dbw->insert( 'ab_links',
				[
					'abl_from' => $title->getArticleID(),
					'abl_from_namespace' => $title->getNamespace(),
					'abl_namespace' => $link->target->getNamespace(),
					'abl_title' => $link->target->getDBkey(),
					'abl_through' => $link->getThroughID(),
					'abl_hidden_through' => $link->getHiddenThroughID()
				],
				__METHOD__
			);
		}

		$dbw->endAtomic( __METHOD__ );
	}

	private function updateImagelinksFromPage( Title $title ) {

		$foundNewLinks = array_fill( 0, sizeof( $this->imagelinks ), false );

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->startAtomic( __METHOD__ );
		$res = $dbw->select( 'ab_images',
			[
				'abi_title',
				'abi_from',
				'abi_through'
			],
			[ 'abi_from' => $title->getArticleID() ],
			__METHOD__
		);

		foreach ( $res as $dbLink ) {
			$oldLink = AdvancedImagelink::newFromDBrow( $dbLink );
			if ( !$oldLink ) {
				//invalid link, continue
				continue;
			}
			$oldFound = false;

			for ( $i = 0; $i < sizeof( $this->imagelinks ); $i++ ) {
				if ( $foundNewLinks[$i] ) {
					continue;
				}

				if ( $oldLink->isEqualTo( $this->imagelinks[$i] ) ) {
					$oldFound = true;
					$foundNewLinks[$i] = true;
					break;
				}
			}

			if ( !$oldFound ) {
				// this link is no longer present in the article, we have to remove it
				wfDebugLog(
					'AdvancedBacklinks',
					"Deleting {$oldLink->getTextForLogs()}"
				);

				$dbw->delete( 'ab_images',
					[
						'abi_from' => $dbLink->abi_from,
						'abi_title' => $dbLink->abi_title,
						'abi_through' => $dbLink->abi_through
					],
					__METHOD__ );
			}
		}

		// now for the links that we have to insert...
		for ( $i = 0; $i < sizeof( $this->imagelinks ); $i++ ) {
			if ( $foundNewLinks[$i] )
				continue;

			//this must be a new link, it's not present in the DB or the page has been moved or some other wild stuff
			$link = $this->imagelinks[$i];
			wfDebugLog(
				'AdvancedBacklinks',
				"Inserting {$link->getTextForLogs()}"
			);

			$dbw->insert( 'ab_images',
				[
					'abi_from' => $title->getArticleID(),
					'abi_from_namespace' => $title->getNamespace(),
					'abi_title' => $link->target->getDBkey(),
					'abi_through' => $link->getThroughID()
				],
				__METHOD__
			);
		}

		$dbw->endAtomic( __METHOD__ );
	}
}