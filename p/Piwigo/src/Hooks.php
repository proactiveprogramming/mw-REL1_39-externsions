<?php
/**
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
 *
 * @file
 */

namespace MediaWiki\Extension\Piwigo;

use FormatJson;
use Parser;
use PPFrame;

class Hooks implements
	\MediaWiki\Hook\ParserFirstCallInitHook
{
	/**
	 * Register parser hooks to add the piwigo keyword
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 * @see https://www.mediawiki.org/wiki/Manual:Parser_functions
	 * @param Parser $parser
	 * @throws \MWException
	 */
	public function onParserFirstCallInit( $parser ) {

		// Add the following to a wiki page to see how it works:
		// <piwigo tags=1 count=5 /> or <piwigo category=1 count=5 />
		$parser->setHook( 'piwigo', [ self::class, 'parserKeywordPiwigo' ] );

		// Add the following to a wiki page to see how it works:
		// {{#piwigo: tags=1 | tags=2 | count = 10 }}
		$parser->setFunctionHook( 'piwigo', [ self::class, 'parserFunctionPiwigo' ] );

		return true;
	}

	/**
	 * Implements tag function, <piwigo/>, which enables
	 * the piwigo gallery on a page.
	 *
	 * @param string $input input between the tags (ignored)
	 * @param array $args tag arguments
	 * @param Parser $parser the parser
	 * @param PPFrame $frame the parent frame
	 * @return string to replace tag with
	 */
	public static function parserKeywordPiwigo( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parser->getOutput()->addModules( 'ext.piwigo' );
		$parser->getOutput()->addModules( 'ext.baguetteBox' );

		if (empty($GLOBALS['wgPiwigoURL']))
		{
			return '<p>Please add <code>$wgPiwigoURL</code> to your LocalSettings.php</p>';
		}

		$piwigoParams = [];
		$piwigoParams[ 'wgPiwigoURL' ] = $GLOBALS['wgPiwigoURL'];
        $piwigoParams[ 'wgPiwigoGalleryLayout' ] = $GLOBALS['wgPiwigoGalleryLayout'] ?? 'fluid';

		$parser->getOutput()->addJsConfigVars( 'Piwigo', $piwigoParams );

		$ret = self::getGalleryTag($args);

		return $ret;
	}

	/**
	 * Parser function handler for {{#piwigo: .. | .. }}
	 *
	 * @param Parser $parser
	 * @param string $value
	 * @param string ...$args
	 * @return string HTML to insert in the page.
	 */
	public static function parserFunctionPiwigo( Parser $parser, string $value, ...$args ) {

		$parser->getOutput()->addModules( 'ext.piwigo' );
		$parser->getOutput()->addModules( 'ext.baguetteBox' );

		$args[] = $value;

		// Format the parameters in a nice array
		$parameters = array();
		foreach ($args as $argpair)
		{
			$parts = array();
			if (preg_match('@^([^=]+)=?(.*)$@', $argpair, $parts))
			{
				$k = strtolower(trim($parts[1]));
				if ($k != 'search')
					$value = trim(preg_replace('@-.*$@', '', $parts[2]));
				else
					$value = $parts[2];

				if (isset($parameters[$k]))
				{
					if (!is_array($parameters[$k]))
					{
						$v = $parameters[$k];
						unset($parameters[$k]);

						$parameters[$k][] = $v;
					}

					$parameters[$k][] = $value;
				}
				else
					$parameters[$k] = $value;
			}
		}

		if (empty($GLOBALS['wgPiwigoURL']))
		{
			return '<p>Please add <code>$wgPiwigoURL</code> to your LocalSettings.php</p>';
		}

		$piwigoParams = [];
		$piwigoParams[ 'wgPiwigoURL' ] = $GLOBALS['wgPiwigoURL'];
		$piwigoParams[ 'wgPiwigoGalleryLayout' ] = $GLOBALS['wgPiwigoGalleryLayout'] ?? 'fluid';

		$parser->getOutput()->addJsConfigVars( 'Piwigo', $piwigoParams );

		$ret = self::getGalleryTag($parameters);

		return $ret;
	}

	private static function getGalleryTag($parameters)
	{
		// Add some synonymes for robustness:
		if (!empty($parameters['images']))
			$parameters['count'] = $parameters['images'];
		unset($parameters['images']);

		if (!empty($parameters['tag']))
			$parameters['tags'] = $parameters['tag'];
		unset($parameters['tag']);

		if (!empty($parameters['album']))
			$parameters['category'] = $parameters['album'];
		unset($parameters['album']);

		$data = array();
		foreach ($parameters as $k => $v)
		{
			if (is_array($v))
				$data[] = 'data-'.$k.'_multiple="'.htmlspecialchars(implode(',', $v)).'"';
			else
				$data[] = 'data-'.$k.'="'.htmlspecialchars($v).'"';
		}

		return '<div class="showPiwigo tz-gallery" '.implode(' ', $data).'></div>';
	}
}
