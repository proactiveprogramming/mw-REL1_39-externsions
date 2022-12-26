<?php

namespace CommonsMetadata;

use DOMElement;
use DOMNode;

/**
 * Class to parse metadata from commons formatted wiki pages.
 * Relies on the attributes set by {{Information}} and similar templates - see
 * https://commons.wikimedia.org/wiki/Commons:Machine-readable_data
 */
class TemplateParser {
	public const COORDINATES_KEY = 'coordinates';
	public const LICENSES_KEY = 'licenses';
	public const INFORMATION_FIELDS_KEY = 'informationFields';
	public const DELETION_KEY = 'deletion';
	public const RESTRICTIONS_KEY = 'restrictions';

	/**
	 * HTML element class name => metadata field name mapping for license data.
	 * @var array
	 */
	protected static $licenseFieldClasses = [
		'licensetpl_short' => 'LicenseShortName',
		'licensetpl_long' => 'UsageTerms',
		'licensetpl_attr_req' => 'AttributionRequired',
		'licensetpl_attr' => 'Attribution',
		// 'licensetpl_link_req',
		'licensetpl_link' => 'LicenseUrl',
		'licensetpl_nonfree' => 'NonFree',
	];

	/**
	 * HTML element class/id => metadata field name mapping for information template data.
	 * @var array
	 */
	protected static $informationFieldClasses = [
		'fileinfotpl_desc' => 'ImageDescription',
		# For date: Open question - should we parse the commons
		# date field better to deal with templates like
		# {{Taken on}} et al. along with extracting a time stamp
		# from the human readable field?
		'fileinfotpl_date' => 'DateTimeOriginal',
		'fileinfotpl_aut' => 'Artist',
		# For "source" field of {{information}} there are two closely
		# related fields we could map it to. Credit (iptc 2:110) is
		# "Identifies the provider of the media, not necessarily the
		# owner/creator." Source (iptc 2:115) "Identifies the
		# original owner of the intellectual content of the media. This
		# could be an agency, a member of an agency or an individual."
		# I think "Credit" fits much more closely to the commons notion
		# of source than "Source" does.
		'fileinfotpl_src' => 'Credit',
		'fileinfotpl_art_title' => 'ObjectName',
		'fileinfotpl_perm' => 'Permission',
		'fileinfotpl_credit' => 'Attribution',
	];

	/**
	 * Classnames identifying {{Information}}-like templates, ordered from highest to lowest
	 * priority. Higher priority means that template is more likely to be about the image
	 * (as opposed to e.g. some object visible on the image), data in higher-priority templates
	 * will be preferred. The classes should be on the <table> element (for templates using the
	 * deprecated id-based fieldname markup) or on the same element which has the "fileinfotpl"
	 * class (for templates with the class-based markup).
	 * @var array
	 */
	protected static $infoTemplateClasses = [
		'fileinfotpl-type-photograph',
		'fileinfotpl-type-information',
		'fileinfotpl-type-artwork',
	];

	/**
	 * List for templates which should not have handled like {{Information}} even if they have
	 * fields matching $informationFieldClasses. Elements of this array refer to the same kind of
	 * classnames as $infoTemplateClasses.
	 * @var array
	 */
	protected static $infoTemplateExclusion = [
		'fileinfotpl-type-book',
	];

	/**
	 * preg_replace patterns which will be used to clean up parsed HTML code.
	 * @var array
	 */
	protected static $cleanupPatterns = [
		// trim leading or trailing whitespace
		'/^\s+|\s+$/' => '',
		// clean paragraph with no styling - usually generated by MediaWiki
		'/^<p>(.*)<\/p>$/' => '\1',
	];

	/** @var array */
	protected $priorityLanguages = [ 'en' ];

	/** @var bool */
	protected $multiLanguage = false;

	/**
	 * When parsing multi-language text, use the first available language from this array.
	 * (Order matters - try to use the first element, if not available the second etc.)
	 * When set to false, will return all languages.
	 * @param array $priorityLanguages
	 */
	public function setPriorityLanguages( $priorityLanguages ) {
		$this->priorityLanguages = $priorityLanguages;
	}

	/**
	 * When true, the parser will ignore $priorityLanguages and return all available languages.
	 * @param bool $multiLanguage
	 */
	public function setMultiLanguage( $multiLanguage ) {
		$this->multiLanguage = $multiLanguage;
	}

	/**
	 * Parse an html string for metadata.
	 *
	 * This is the main entry point to the class.
	 *
	 * @param string $html The html to parse
	 * @return array The properties extracted from the page.
	 */
	public function parsePage( $html ) {
		if ( !$html ) { // DOMDocument does not like empty strings
			return [];
		}

		$domNavigator = new DomNavigator( $html );

		return array_filter( [
			self::COORDINATES_KEY => $this->parseCoordinates( $domNavigator ),
			self::INFORMATION_FIELDS_KEY => $this->parseInformationFields( $domNavigator ),
			self::LICENSES_KEY => $this->parseLicenses( $domNavigator ),
			self::DELETION_KEY => $this->parseNuke( $domNavigator ),
			self::RESTRICTIONS_KEY => $this->parseRestrictions( $domNavigator ),
		] );
	}

	/**
	 * Parses geocoded coordinates.
	 * @param DomNavigator $domNavigator
	 * @return array
	 */
	protected function parseCoordinates( DomNavigator $domNavigator ) {
		$data = [];
		foreach ( $domNavigator->findElementsWithClass( 'span', 'geo' ) as $geoNode ) {
			$coordinateData = [];
			$coords = explode( ';', $geoNode->textContent );
			if ( count( $coords ) == 2 && is_numeric( $coords[0] ) && is_numeric( $coords[1] ) ) {
				$coordinateData['GPSLatitude'] = trim( $coords[0] );
				$coordinateData['GPSLongitude'] = trim( $coords[1] );
				$coordinateData['GPSMapDatum'] = 'WGS-84';
			}
			$data[] = $coordinateData;
		}
		return $data;
	}

	/**
	 * Parses the {{Information}} templates (and anything using the same metadata notation,
	 * like {{Artwork}})
	 * @param DomNavigator $domNavigator
	 * @return array an array if information(-like) templates:
	 *   array( 0 => array( 'ImageDescription' => ... ) ... )
	 */
	protected function parseInformationFields( DomNavigator $domNavigator ) {
		$attributePrefix = 'fileinfotpl_';
		$data = [];
		$labelFields = $domNavigator->findElementsWithIdPrefix( [ 'td', 'th' ], $attributePrefix );
		foreach ( $labelFields as $labelField ) {
			$informationField = $domNavigator->nextElementSibling( $labelField );
			if ( !$informationField ) {
				continue;
			}
			$id = $labelField->getAttribute( 'id' );
			$group = $domNavigator->closest( $informationField, 'table' );
			$this->parseInformationField( $domNavigator, $informationField, $group, $id, $data );
		}
		foreach ( $domNavigator->findElementsWithClass( '*', 'fileinfotpl' ) as $group ) {
			$informationFields = $domNavigator->findElementsWithClassPrefix(
				'*', $attributePrefix, $group );
			foreach ( $informationFields as $informationField ) {
				$class = $domNavigator->getFirstClassWithPrefix(
					$informationField, $attributePrefix );
				$this->parseInformationField(
					$domNavigator, $informationField, $group, $class, $data );
			}
		}

		$this->pruneInfoTemplateData( $data );
		$this->sortInformationGroups( $data );
		// using node paths to identify tables is an internal detail, hide it
		return array_values( $data );
	}

	/**
	 * Helper function for the inner loop of parseInformationFields
	 * @param DomNavigator $domNavigator
	 * @param DOMElement $informationField the node holding the data
	 * @param DOMElement|null $group the top node containing all fields of this type; expected (but
	 *  not required) to have one of the $informationFieldClasses.
	 * @param string $idOrClass id or class identifying the field, per $informationFieldClasses Node
	 *  is ignored if this is not a key of $informationFieldClasses. Also ignored if this is null.
	 * @param array[] &$data
	 */
	protected function parseInformationField(
		DomNavigator $domNavigator, DOMElement $informationField, $group, $idOrClass, array &$data
	) {
		if ( !isset( self::$informationFieldClasses[$idOrClass] ) ) {
			return;
		}
		$fieldName = self::$informationFieldClasses[$idOrClass];

		// group fields coming from the same template
		$groupName = $groupType = '-';
		if ( $group ) {
			$groupName = $group->getNodePath() ?? '-';
			$groupType =
				$domNavigator->getFirstClassWithPrefix( $group, 'fileinfotpl-type-' ) ?: '-';
		}

		if ( isset( $data[$groupName][$fieldName] ) ) {
			// don't parse the same field multiple times if it has both id and classes; also
			// ignore a second field of the same type in the same template
			return;
		}

		$method = 'parseField' . $fieldName;
		if ( !method_exists( $this, $method ) ) {
			$method = 'parseContents';
		}

		$data[$groupName][$fieldName] = $this->{$method}( $domNavigator, $informationField );
		$data[$groupName]['_type'] = $groupType;
	}

	/**
	 * Sorts info template data groups according to $informationFieldClasses, highest priority first
	 * Also removes the _type helper keys.
	 * @param array[] &$data info template data, as returned by parseInformationFields()
	 */
	protected function sortInformationGroups( array &$data ) {
		// PHP 5.3 does not like class references in closures
		$infoTemplateClasses = self::$infoTemplateClasses;

		uasort( $data, static function ( $template1, $template2 ) use ( $infoTemplateClasses ) {
			$priority1 = array_search( $template1['_type'], $infoTemplateClasses );
			$priority2 = array_search( $template2['_type'], $infoTemplateClasses );

			// preserve the order of unknown templates; known precedes unknown
			if ( $priority2 === false ) {
				return -1;
			} elseif ( $priority1 === false ) {
				return 1;
			}

			// $pri1 is smaller -> $template1['_type'] comes first in
			// $informationFieldClasses -> should return negative so $template1 comes first
			return $priority1 - $priority2;
		} );

		foreach ( $data as &$group ) {
			unset( $group['_type'] );
		}
	}

	/**
	 * Prunes template data
	 * Removes blacklisted templates if they are not alone
	 * @param array[] &$data info template data
	 */
	protected function pruneInfoTemplateData( array &$data ) {
		foreach ( $data as $key => &$group ) {
			if ( in_array( $group['_type'], self::$infoTemplateExclusion )
				&& count( $data ) !== 1
			) {
				unset( $data[$key] );
			}
		}
	}

	/**
	 * Parses the artist, which might be an hCard
	 * @param DomNavigator $domNavigator
	 * @param DOMNode $node
	 * @return string
	 */
	protected function parseFieldArtist( DomNavigator $domNavigator, DOMNode $node ) {
		$field = $this->extractHCardProperty( $domNavigator, $node, 'fn' );
		if ( $field ) {
			return $this->cleanedInnerHtml( $field );
		}

		return $this->parseContents( $domNavigator, $node );
	}

	/**
	 * @param DomNavigator $domNavigator
	 * @param DOMNode $node
	 * @return string
	 */
	protected function parseFieldCredit( DomNavigator $domNavigator, DOMNode $node ) {
		$field = $this->extractHCardProperty( $domNavigator, $node, 'fn' );
		if ( $field ) {
			return $this->cleanedInnerHtml( $field );
		}

		return $this->parseContents( $domNavigator, $node );
	}

	/**
	 * Parses the DateTimeOriginal - finds <time> tag and returns the value of datetime attribute
	 * @param DomNavigator $domNavigator
	 * @param DOMNode $node
	 * @return string
	 */
	protected function parseFieldDateTimeOriginal( DomNavigator $domNavigator, DOMNode $node ) {
		$nodes = $domNavigator->findElementsWithAttribute( 'time', 'datetime', $node );
		foreach ( $nodes as $time ) {
			return $time->getAttribute( 'datetime' );
		}

		return $this->parseContents( $domNavigator, $node );
	}

	/**
	 * Extracts an hCard property from a DOMNode that contains an hCard
	 * @param DomNavigator $domNavigator
	 * @param DOMNode $node
	 * @param string $property hCard property to be extracted
	 * @return DOMNode
	 */
	protected function extractHCardProperty(
		DomNavigator $domNavigator, DOMNode $node, $property
	) {
		foreach ( $domNavigator->findElementsWithClass( '*', 'vcard', $node ) as $vcard ) {
			foreach ( $domNavigator->findElementsWithClass( '*', $property, $vcard ) as $name ) {
				return $name;
			}
		}
	}

	/**
	 * @param DomNavigator $domNavigator
	 * @return array an array of licenses: array( 0 => array( 'LincenseShortName' => ... ) ... )
	 */
	protected function parseLicenses( DomNavigator $domNavigator ) {
		$data = [];
		foreach ( $domNavigator->findElementsWithClass( '*', 'licensetpl' ) as $licenseNode ) {
			$licenseData = $this->parseLicenseNode( $domNavigator, $licenseNode );
			if ( isset( $licenseData['UsageTerms'] ) ) {
				$licenseData['Copyrighted'] = ( $licenseData['UsageTerms'] === 'Public domain' )
					? 'False' : 'True';
			}
			$data[] = $licenseData;
		}

		return $data;
	}

	/**
	 * @param DomNavigator $domNavigator
	 * @param DOMNode $licenseNode
	 * @return array
	 */
	protected function parseLicenseNode( DomNavigator $domNavigator, DOMNode $licenseNode ) {
		$data = [];
		foreach ( self::$licenseFieldClasses as $class => $fieldName ) {
			foreach ( $domNavigator->findElementsWithClass( '*', $class, $licenseNode ) as $node ) {
				$data[$fieldName] = $this->cleanedInnerHtml( $node );
				break;
			}
		}
		return $data;
	}

	/**
	 * Parse and return deletion reason from the {{Nuke}} template
	 * ( https://commons.wikimedia.org/wiki/Template:Nuke )
	 * @param DomNavigator $domNavigator
	 * @return array
	 */
	protected function parseNuke( DomNavigator $domNavigator ) {
		$deletions = [];

		foreach ( $domNavigator->findElementsWithClass( '*', 'nuke' ) as $nukeNode ) {
			$nukeLink = $nukeNode->firstChild;
			if ( $nukeLink
				&& $nukeLink instanceof DOMElement && $nukeLink->hasAttribute( 'href' )
			) {
				$urlBits = wfParseUrl( $nukeLink->getAttribute( 'href' ) );
				if ( isset( $urlBits['query'] ) ) {
					$params = wfCgiToArray( $urlBits['query'] );
					if ( isset( $params['action'] ) && $params['action'] === 'delete'
						&& isset( $params['wpReason'] )
					) {
						$deletions[] = [ 'DeletionReason' => $params['wpReason'] ];
					}
				}
			}
		}
		return $deletions;
	}

	/**
	 * Parses file restrictions i.e. trademark, insignia, etc.
	 * @param DomNavigator $domNavigator
	 * @return array
	 */
	protected function parseRestrictions( DomNavigator $domNavigator ) {
		$restrictionPrefix = 'restriction-';
		$restrictions = [];
		foreach (
			$domNavigator->findElementsWithClassPrefix( '*', $restrictionPrefix ) as $element
		) {
			$classes = explode( ' ', $element->getAttribute( 'class' ) );
			foreach ( $classes as $class ) {
				if ( strpos( $class, $restrictionPrefix ) === 0 ) {
					$restrictionType = substr( $class, strlen( $restrictionPrefix ) );
					$restrictions[] = $restrictionType;
				}
			}
		}
		return [ [ 'Restrictions' => implode( '|', array_unique( $restrictions ) ) ] ];
	}

	/**
	 * Get the text of a node. The result might be a string, or an array of strings if the node has
	 * multiple languages (resulting from {{en}} and similar templates).
	 * @param DomNavigator $domNavigator
	 * @param DOMNode $node
	 * @return string|array
	 */
	protected function parseContents( DomNavigator $domNavigator, DOMNode $node ) {
		$languageNodes = $domNavigator->findElementsWithClassAndLang( 'div', 'description', $node );
		if ( !$languageNodes->length ) { // no language templates at all
			return $this->cleanedInnerHtml( $node );
		}
		$languages = [];
		foreach ( $languageNodes as $node ) {
			$node = $this->removeLanguageName( $domNavigator, $node );
			$languageCode = $node->getAttribute( 'lang' );
			$languages[$languageCode] = $node;
		}
		if ( !$this->multiLanguage ) {
			return $this->cleanedInnerHtml( $this->selectLanguage( $languages ) );
		} else {
			$languages = array_map( [ $this, 'cleanedInnerHtml' ], $languages );
			$languages['_type'] = 'lang';
			return $languages;
		}
	}

	/**
	 * Language templates like {{en}} put the language name at the beginning of the text;
	 * this function removes it.
	 * @param DomNavigator $domNavigator
	 * @param DOMElement $node
	 * @return DOMElement a clone of the input node, with the language name removed
	 */
	protected function removeLanguageName( DomNavigator $domNavigator, DOMElement $node ) {
		$node = $node->cloneNode( true );
		$languageNames = $domNavigator->findElementsWithClass( 'span', 'language', $node );
		foreach ( $languageNames as $languageName ) {
			if ( !$node->isSameNode( $languageName->parentNode ) ) {
				continue; // language names are direct children
			}
			$node->removeChild( $languageName );
		}
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType cloneNode returns `static`
		return $node;
	}

	/**
	 * Takes an array indexed with language codes, and returns the best match.
	 * @param array $languages
	 * @return mixed
	 */
	protected function selectLanguage( array $languages ) {
		foreach ( $this->priorityLanguages as $languageCode ) {
			if ( array_key_exists( $languageCode, $languages ) ) {
				return $languages[$languageCode];
			}
		}
		return reset( $languages );
	}

	/**
	 * Turns a node into a HTML string
	 * @param DOMNode $node
	 * @return string
	 */
	protected function toHtml( DOMNode $node ) {
		return $node->ownerDocument->saveHTML( $node );
	}

	/**
	 * Turns a node into plain text
	 * @param DOMNode $node
	 * @return string
	 */
	protected function toText( DOMNode $node ) {
		return trim( $node->textContent );
	}

	/**
	 * Turns a node into HTML, except for the enclosing tag.
	 * @param DOMNode $node
	 * @return string
	 */
	protected function innerHtml( DOMNode $node ) {
		if ( !$node instanceof DOMElement ) {
			return $this->toHtml( $node );
		}

		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $this->toHtml( $child );
		}
		return $html;
	}

	/**
	 * Turns a node into HTML, except for the enclosing tag.
	 * Cleans up the contents by removing enclosing whitespace and some HTML elements.
	 * @param DOMNode $node
	 * @return string
	 */
	protected function cleanedInnerHtml( DOMNode $node ) {
		$html = $this->innerHtml( $node );
		do {
			$oldHtml = $html;
			foreach ( static::$cleanupPatterns as $pattern => $replacement ) {
				$html = preg_replace( $pattern, $replacement, $html );
			}
		} while ( $oldHtml !== $html );
		return $html;
	}

	/**
	 * Switch rows and columns. Usually it is easier to collect data grouped by source template,
	 * but the extmetadata API needs grouping by field name, this function turns around the grouping
	 * @param array $data
	 * @return array
	 */
	protected function arrayTranspose( array $data ) {
		$transposedData = [];
		foreach ( $data as $groupName => $group ) {
			foreach ( $group as $fieldName => $value ) {
				$transposedData[$fieldName][$groupName] = $value;
			}
		}
		return $transposedData;
	}
}