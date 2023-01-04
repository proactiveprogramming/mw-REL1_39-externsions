<?php
/**
 * AdvancedBacklinks
 * Copyright (C) 2019  Ostrzyciel
 *
 * Code for indexing wikilinks and imagelinks during parsing.
 * This file contains code from the Parser.php file in MediaWiki core.
 *
 * This code is weird, adventurous and crazy. That's what happens when you mess with the parser.
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

use MediaWiki\MediaWikiServices;

/**
 * If you were looking for hell...
 *
 * THIS IS IT.
 *
 * AND I RELEASE THIS UNDER GPL SO YOU CAN GO TO HELL WITH ME. TOGETHER. FOREVER.
 * FUN.
 */
class PPFrameHooks {

	// stolen from Parser.php
	private const EXT_LINK_ADDR = '(?:[0-9.]+|\\[(?i:[0-9a-f:.]+)\\]|[^][<>"\\x00-\\x20\\x7F\p{Zs}\x{FFFD}])';

	/**
	 * @var bool|PageProps
	 */
	static $pageProps = false;

	/**
	 * @var null|string
	 */
	static $extLinkBracketedRegex = null;

	/**
	 * An ugly hack that's supposed to get around Parser::$mExtLinkBracketedRegex being private
	 *
	 * @param string $urlProtocols
	 *
	 * @return null
	 */
	private static function getExtLinkBracketedRegex( string $urlProtocols ) {
		if ( self::$extLinkBracketedRegex ) {
			return self::$extLinkBracketedRegex;
		}

		self::$extLinkBracketedRegex = '/\[(((?i)' . $urlProtocols . ')' .
			self::EXT_LINK_ADDR .
			Parser::EXT_LINK_URL_CLASS . '*)\p{Zs}*([^\]\\x00-\\x08\\x0a-\\x1F\\x{FFFD}]*?)\]/Su';
		return self::$extLinkBracketedRegex;
	}

	/**
	 * @param Parser $parser
	 */
	public static function onParserClearState( Parser &$parser ) {
		$parser->abUsedMark = false;
		$parser->abExpansionDepth = 0;
		$parser->abInner = false;
	}

	/**
	 * @param Parser $parser
	 * @param string $text
	 * @param $stripState
	 */
	public static function onInternalParseBeforeLinks( Parser &$parser, string &$text, $stripState ) {
		$markerRegex = '/' . Parser::MARKER_PREFIX . "-ablink-(\d+)-" . Parser::MARKER_SUFFIX . '/';

		do {
			$change = false;
			$text = preg_replace_callback(
				$markerRegex,
				function ( $matches ) use ( &$parser, &$change ) {
					$change = true;
					$index = $matches[1];
					if ( $index >= sizeof( $parser->abLookup ) ) {
						wfDebugLog( 'AdvancedBacklinks', 'Missing entry in ablink lookup table' );
						return '';
					}
					return $parser->abLookup[$index];
				},
				$text
			);
		} while ( $change );
	}

	/**
	 * @param PPFrame $frame
	 * @param $root
	 */
	public static function onPPFrameBeforeExpansion( PPFrame &$frame, &$root ) {
		$parser = $frame->parser;
		++$parser->abExpansionDepth;
		if ( $parser->abExpansionDepth == 1 ) {
			//reset the mark, just in case
			$parser->abUsedMark = false;
			$frame->abMark = 0;
			return;
		}

		if ( isset( $parser->abInnerCounter ) && $parser->abInnerCounter > 0 )
			--$parser->abInnerCounter;

		if ( $root instanceof PPNode ) {
			if ( $root->getName() === 'inner' ) {
				$parser->abInner = $parser->abExpansionDepth;
				$parser->abInnerCounter = 2;
			} else if ( $parser->abInner && $root->getName() === 'root' ) {
				if ( $parser->abInner == $parser->abExpansionDepth && $parser->abInnerCounter == 1 ) {
					//we've got an extension tag here, we should mark it
					$root->abExtension = true;
					$parser->abInner = false;
				} else {
					//the tag's contents weren't parsed for some crazy reason, we are at a template now
					$parser->abInner = false;
				}
			}
		}
		if ( $parser->abUsedMark || isset( $frame->abMark ) ) {
			//we already met a marking PPFrame, or visited this PPFrame
			return;
		}
		if ( !( $root instanceof PPNode ) ||
			!( $frame instanceof PPTemplateFrame_Hash )
		) {
			//not a template, we don't have to worry about it
			return;
		}

		$title = $frame->getTitle();
		if ( !$title || $title->isSpecialPage() ) {
			//not a valid title, or a special page or something
			$frame->abMark = 0;
			return;
		}
		if ( !self::$pageProps ) {
			self::$pageProps = PageProps::getInstance();
		}
		$props = self::$pageProps->getProperties( $title, [ 'directlink', 'redlinkallergicthrough' ] );
		if ( sizeof( $props ) > 0 &&
			is_int( array_keys( $props )[0] )
		) {
			$props = array_values( $props )[0];
		}

		if ( isset( $props['redlinkallergicthrough'] ) ) {
			$frame->abAllergicThrough = true;
		}
		if ( isset( $props['directlink'] ) ) {
			//this page has the __DIRECTLINK__ switch
			$frame->abDirectLink = true;
			$frame->abMark = 0;
			return;
		}

		//mark the frame
		$frame->abMark = $parser->abExpansionDepth;
		$parser->abUsedMark = true;
	}

	/**
	 * @param PPFrame $frame
	 * @param $root
	 * @param string $text
	 * @throws MWException
	 */
	public static function onPPFrameAfterExpansion( PPFrame &$frame, &$root, string &$text ) {
		$parser = $frame->parser;
		if ( !( $root instanceof PPNode ) ) {
			//we don't have to worry about this one
			--$parser->abExpansionDepth;
			return;
		}

		$through = $frame->getTitle();
		if ( $through->getDBkey() == $parser->getPage()->getDBkey() &&
			$through->getNamespace() == $parser->getPage()->getNamespace() ) {
			$through = null;
		}

		$nomark = !isset( $frame->abMark );

		if ( !$nomark && $parser->abExpansionDepth !== 1 ) {
			if ( isset( $root->abExtension ) ) {
				// GT instead of inequal because extension tags get covered by UNIQs so they won't be reconsidered
				// when after expansion event is triggered for the parent marking template.
				$nomark = $frame->abMark > $parser->abExpansionDepth;
			} else {
				$nomark = $frame->abMark !== $parser->abExpansionDepth;
			}
		}

		// allergic through and DIRECTLINK
		if ( $nomark && isset( $frame->abDirectLink ) && $through && isset( $frame->abAllergicThrough ) ) {
			self::parseInternalLinks( $parser, $text, $through, false, true );
			self::parseExternalLinks( $parser, $text, $through, false, true );
			--$parser->abExpansionDepth;
			return;
		}

		//contents of an extension tag (probably, who knows really)
		if ( isset( $root->abExtension ) ) {
			//parse the inside of an extension tag without substitution, it will be covered by an UNIQ ext tag later anyway
			self::parseInternalLinks( $parser, $text, $nomark ? null : $through, false );
			self::parseExternalLinks( $parser, $text, $nomark ? null : $through, false );
			--$parser->abExpansionDepth;
			return;
		}
		if ( $nomark ) {
			--$parser->abExpansionDepth;
			return;
		}

		if ( $parser->abExpansionDepth > 1 ) {
			$text = self::parseInternalLinks( $parser, $text, $through, true );
			$text = self::parseExternalLinks( $parser, $text, $through, true );
		} else {
			self::parseInternalLinks( $parser, $text, null, false );
			self::parseExternalLinks( $parser, $text, null, false );
			self::onInternalParseBeforeLinks( $parser, $text, null );
		}

		$parser->abUsedMark = false;
		--$parser->abExpansionDepth;
	}

	/**
	 * @param Parser $parser
	 * @param string $s
	 * @param $through
	 * @param bool $doSubstitution
	 * @param bool $hidden
	 *
	 * @return false|string
	 * @throws MWException
	 */
	private static function parseInternalLinks( Parser &$parser, string $s, $through, bool $doSubstitution, bool $hidden = false ) {
		static $tc = false, $e1, $e1_img;
		# the % is needed to support urlencoded titles as well
		if ( !$tc ) {
			$tc = Title::legalChars() . '#%';
			# Match a link having the form [[namespace:link|alternate]]trail
			$e1 = "/^([$tc]+)(?:\\|(.+?))?]](.*)\$/sD";
			# Match cases where there is no "]]", which might still be images
			$e1_img = "/^([$tc]+)\\|(.*)\$/sD";
		}

		//!!!
		if ( !isset( $parser->getOutput()->abSet ) ) {
			$parser->getOutput()->abSet = new AdvancedLinkSet();
			$parser->abLookup = [];
		}

		# split the entire text string on occurrences of [[
		$a = StringUtils::explode( '[[', ' ' . $s );
		# get the first element (all text up to first [[), and remove the space we added
		$s = $a->current();
		$a->next();
		$line = $a->current(); # Workaround for broken ArrayIterator::next() that returns "void"
		$s = substr( $s, 1 );

		$useLinkPrefixExtension = $parser->getTargetLanguage()->linkPrefixExtension();
		$e2 = null;
		if ( $useLinkPrefixExtension ) {
			# Match the end of a line for a word that's not followed by whitespace,
			# e.g. in the case of 'The Arab al[[Razi]]', 'al' will be matched
			$charset = $parser->getContentLanguage()->linkPrefixCharset();
			$e2 = "/^((?>.*[^$charset]|))(.+)$/sDu";
		}

		if ( $useLinkPrefixExtension ) {
			$m = [];
			if ( preg_match( $e2, $s, $m ) ) {
				$first_prefix = $m[2];
			} else {
				$first_prefix = false;
			}
		} else {
			$prefix = '';
		}

		# Some namespaces don't allow subpages
		$useSubpages = MediaWikiServices::getInstance()->getNamespaceInfo()->hasSubpages(
			$parser->getPage()->getNamespace()
		);

		# Loop for each link
		for ( ; $line !== false && $line !== null; $a->next(), $line = $a->current() ) {

			if ( $useLinkPrefixExtension ) {
				if ( preg_match( $e2, $s, $m ) ) {
					list( , $s, $prefix ) = $m;
				} else {
					$prefix = '';
				}
				# first link
				if ( $first_prefix ) {
					$prefix = $first_prefix;
					$first_prefix = false;
				}
			}

			$might_be_img = false;
			$original = $prefix . '[[' . $line;

			if ( preg_match( $e1, $line, $m ) ) { # page with normal text or alt
				$text = $m[2];
				# If we get a ] at the beginning of $m[3] that means we have a link that's something like:
				# [[Image:Foo.jpg|[http://example.com desc]]] <- having three ] in a row fucks up,
				# the real problem is with the $e1 regex
				# See T1500.
				# Still some problems for cases where the ] is meant to be outside punctuation,
				# and no image is in sight. See T4095.
				if ( $text !== ''
					&& substr( $m[3], 0, 1 ) === ']'
					&& strpos( $text, '[' ) !== false
				) {
					$text .= ']'; # so that replaceExternalLinks($text) works later
					$m[3] = substr( $m[3], 1 );
				}
				# fix up urlencoded title texts
				if ( strpos( $m[1], '%' ) !== false ) {
					# Should anchors '#' also be rejected?
					$m[1] = str_replace( [ '<', '>' ], [ '&lt;', '&gt;' ], rawurldecode( $m[1] ) );
				}
				$trail = $m[3];
			} elseif ( preg_match( $e1_img, $line, $m ) ) {
				# Invalid, but might be an image with a link in its caption
				$might_be_img = true;
				$text = $m[2];
				if ( strpos( $m[1], '%' ) !== false ) {
					$m[1] = str_replace( [ '<', '>' ], [ '&lt;', '&gt;' ], rawurldecode( $m[1] ) );
				}
				$trail = "";
			} else { # Invalid form; output directly
				$s .= $original;
				continue;
			}

			$origLink = ltrim( $m[1], ' ' );

			# Don't allow internal links to pages containing
			# PROTO: where PROTO is a valid URL protocol; these
			# should be external links.
			if ( preg_match( '/^(?i:' . $parser->getUrlProtocols() . ')/', $origLink ) ) {
				$s .= $original;
				continue;
			}

			# Make subpage if necessary
			if ( $useSubpages ) {
				$link = Linker::normalizeSubpageLink(
					$parser->getTitle(), $origLink, $text
				);
			} else {
				$link = $origLink;
			}

			// \x7f isn't a default legal title char, so most likely strip
			// markers will force us into the "invalid form" path above.  But,
			// just in case, let's assert that xmlish tags aren't valid in
			// the title position.
			$unstrip = $parser->getStripState()->killMarkers( $link );
			$noMarkers = ( $unstrip === $link );

			$nt = $noMarkers ? Title::newFromText( $link ) : null;
			if ( $nt === null ) {
				$s .= $original;
				continue;
			}

			$ns = $nt->getNamespace();
			$iw = $nt->getInterwiki();

			$noforce = ( substr( $origLink, 0, 1 ) !== ':' );

			if ( $might_be_img ) { # if this is actually an invalid link
				if ( $ns == NS_FILE && $noforce ) { # but might be an image
					$found = false;
					while ( true ) {
						# look at the next 'line' to see if we can close it there
						$a->next();
						$next_line = $a->current();
						if ( $next_line === false || $next_line === null ) {
							break;
						}
						$m = explode( ']]', $next_line, 3 );
						if ( count( $m ) == 3 ) {
							# the first ]] closes the inner link, the second the image
							$found = true;
							$text .= "[[{$m[0]}]]{$m[1]}";
							$trail = $m[2];
							break;
						} elseif ( count( $m ) == 2 ) {
							# if there's exactly one ]] that's fine, we'll keep looking
							$text .= "[[{$m[0]}]]{$m[1]}";
						} else {
							# if $next_line is invalid too, we need look no further
							$text .= '[[' . $next_line;
							break;
						}
					}
					if ( !$found ) {
						# we couldn't find the end of this imageLink, so output it raw
						# but don't ignore what might be perfectly normal links in the text we've examined
						$text = self::parseInternalLinks( $parser, $text, $through, $doSubstitution );
						$s .= "{$prefix}[[$link|$text";
						# note: no $trail, because without an end, there *is* no trail
						continue;
					}
				} else { # it's not an image, so output it raw
					$s .= $original;
					# note: no $trail, because without an end, there *is* no trail
					continue;
				}
			}

			$wasblank = ( $text == '' );
			if ( $wasblank ) {
				$text = $link;
				if ( !$noforce ) {
					# Strip off leading ':'
					$text = substr( $text, 1 );
				}
			} else {
				# T6598 madness. Handle the quotes only if they come from the alternate part
				# [[Lista d''e paise d''o munno]] -> <a href="...">Lista d''e paise d''o munno</a>
				# [[Criticism of Harry Potter|Criticism of ''Harry Potter'']]
				#    -> <a href="Criticism of Harry Potter">Criticism of <i>Harry Potter</i></a>
				$text = $parser->doQuotes( $text );
			}

			# Link not escaped by : , create the various objects
			if ( $noforce && !$nt->wasLocalInterwiki() ) {

				if ( $ns == NS_FILE ) {
					if ( $wasblank ) {
						# if no parameters were passed, $text
						# becomes something like "File:Foo.png",
						# which we don't want to pass on to the
						# image generator
						$text = '';
					} else {
						# recursively parse links inside the image caption
						# actually, this will parse them in any other parameters, too,
						# but it might be hard to fix that, and it doesn't matter ATM
						self::parseExternalLinks( $parser, $text, $through, false );
					}

					if ( preg_match(
						'#(^|\|)(\s*link\s*=\s*)(.*?)(\s*)(\||$)#', $text, $matches ) ) {

						if ( !preg_match( "/^((?i)" . $parser->getUrlProtocols() . ")/", $matches[3] ) ) {
							$pageTitle = trim( $matches[3] );
							$link = Title::newFromText( $pageTitle );
							if ( $link && !$link->isSpecialPage() &&
								!( $link->getNamespace() === $parser->getPage()->getNamespace() &&
									$link->getDBkey() === $parser->getPage()->getDBkey() ) ) {
								//insert the wikilink
								$parser->getOutput()->abSet->addLink( new AdvancedWikilink(
									$parser->getTitle(),
									$link,
									$through,
									$hidden
								) );
							}
						}
					}

					//insert the imagelink
					$parser->getOutput()->abSet->addLink( new AdvancedImagelink(
						$parser->getTitle(),
						$nt,
						$through
					) );

					if ( $doSubstitution ) {
						$lookupId = sizeof( $parser->abLookup );
						$parser->abLookup[] = $original;

						$s .= Parser::MARKER_PREFIX . "-ablink-$lookupId-" . Parser::MARKER_SUFFIX;
					} else {
						$s .= $original;
					}
					continue;

				} elseif ( $ns == NS_CATEGORY ) {
					$s .= $original;
					continue;
				}
			}

			# NS_MEDIA is a pseudo-namespace for linking directly to a file
			if ( $ns == NS_MEDIA ) {
				$s .= $original;
				continue;
			}

			if ( $iw != '' ) {
				$s .= $original;
			} else {
				//we ignore special pages... at least for now
				//ignore links to self
				if ( !$nt->isSpecialPage() &&
					!( $nt->getNamespace() === $parser->getTitle()->getNamespace() &&
						$nt->getDBkey() === $parser->getTitle()->getDBkey() )
				) {
					//insert the wikilink
					$parser->getOutput()->abSet->addLink( new AdvancedWikilink(
						$parser->getTitle(),
						$nt,
						$through,
						$hidden
					) );
				}

				if ( $doSubstitution ) {
					$lookupId = sizeof( $parser->abLookup );
					$parser->abLookup[] = $original;

					$s .= Parser::MARKER_PREFIX . "-ablink-$lookupId-" . Parser::MARKER_SUFFIX;
				} else {
					$s .= $original;
				}
				}
			}

		return $s;
	}

	/**
	 * @param Parser $parser
	 * @param string $text
	 * @param $through
	 * @param bool $doSubstitution
	 * @param bool $hidden
	 *
	 * @return mixed|string
	 */
	private static function parseExternalLinks( Parser &$parser, string $text, $through, bool $doSubstitution, bool $hidden = false ) {
		global $wgServer, $wgArticlePath, $wgAdvancedBacklinksTrackExtlinks;

		if ( !$doSubstitution && !$wgAdvancedBacklinksTrackExtlinks )
			return $text;

		static $apPrefix = false, $apPreg1, $apPreg2;
		if ( !$apPrefix ) {
			$apPrefix = str_replace( '$1', '', $wgArticlePath );
			$apPrefix = preg_quote( $apPrefix );
			$apPreg1 = "-(?<=$apPrefix).*?(?=(\?|#))-";
			$apPreg2 = "-(?<=$apPrefix).*-";
		}
		$bits = preg_split(
			self::getExtLinkBracketedRegex( $parser->getUrlProtocols() ),
			$text,
			-1,
			PREG_SPLIT_DELIM_CAPTURE
		);
		$s = array_shift( $bits );

		$i = 0;
		while ( $i < count( $bits ) ) {
			$url = $bits[$i++];
			$i++;   //protocol
			$text = $bits[$i++];
			$trail = $bits[$i++];
			$original = '[' . $url . ' ' . $text . ']' . $trail;

			# The characters '<' and '>' (which were escaped by
			# removeHTMLtags()) should not be included in
			# URLs, per RFC 2396.
			$m2 = [];
			if ( preg_match( '/&(lt|gt);/', $url, $m2, PREG_OFFSET_CAPTURE ) ) {
				$text = substr( $url, $m2[0][1] ) . ' ' . $text;
				$url = substr( $url, 0, $m2[0][1] );
			}

			$url = Sanitizer::cleanUrl( $url );

			if ( $wgAdvancedBacklinksTrackExtlinks ) {
				$url = Parser::normalizeLinkUrl( $url );
				if ( ParserOutput::isLinkInternal( $wgServer, $url ) ) {
					$titleText = '';
					if ( preg_match( '-index\.php-', $url ) ) {
						$titleM = [];
						if ( preg_match( '-(?<=title=).*?(?=(&|#))-', $url, $titleM ) ) {
							$titleText = $titleM[0];
						} else if ( preg_match( '-(?<=title=).*-', $url, $titleM ) ) {
							$titleText = $titleM[0];
						}
					} else if ( preg_match( $apPrefix, $url ) ) {
						$titleM = [];
						if ( preg_match( $apPreg1, $url, $titleM ) ) {
							$titleText = $titleM[0];
						} else if ( preg_match( $apPreg2, $url, $titleM ) ) {
							$titleText = $titleM[0];
						}
					} else {
						wfDebugLog( 'AdvancedBacklinks', "Unrecognized internal extlink: $url" );
					}
					$titleText = urldecode( $titleText );

					$title = Title::newFromURL( $titleText );
					//we ignore special pages... at least for now
					//ignore links to self
					if ( !$title || $title->isSpecialPage() ||
						( $title->getDBkey() === $parser->getTitle()->getDBkey() &&
							$title->getNamespace() === $parser->getTitle()->getNamespace() )
					) {
						$s .= $original;
						continue;
					}
					$parser->getOutput()->abSet->addLink( new AdvancedWikilink(
						$parser->getTitle(),
						$title,
						$through,
						$hidden
					) );
				}
			}

			if ( $doSubstitution ) {
				$lookupId = sizeof( $parser->abLookup );
				$parser->abLookup[] = '[' . $url . ' ' . $text . ']';
				$s .= Parser::MARKER_PREFIX . "-ablink-$lookupId-" . Parser::MARKER_SUFFIX . $trail;
			} else {
				$s .= $original;
			}
		}

		return $s;
	}
}
