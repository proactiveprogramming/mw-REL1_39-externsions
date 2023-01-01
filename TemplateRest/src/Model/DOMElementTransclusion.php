<?php
/**
 * Copyright (C) 2015 Andreas Jonsson <andreas.jonsson@kreablo.se>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */

namespace TemplateRest\Model;

/**
 * Implementation that directly maintains the DOMElement that represents the transclusion.
 */
class DOMElementTransclusion implements Transclusion
{

	private $target;

	private $domElement;

	private $id;

	private $partIndex;

	private $dirty = false;

	function __construct( $target, \DOMElement &$domElement, $id, $partIndex )
	{
		$this->target = $target;
		$this->domElement = $domElement;
		$this->id = $id;
		$this->partIndex = $partIndex;
	}

	/**
	 * @return object
	 */
	public function getParameters()
	{
		$dataMw = \json_decode($this->domElement->getAttribute('data-mw'), true);
		return $dataMw['parts'][$this->partIndex]['template']['params'];
	}

	/**
	 * Set the parameters.
	 *
	 * @param object $parameterData
	 */
	public function setParameters( $parameterData )
	{
		$dataMw = \json_decode($this->domElement->getAttribute('data-mw'), true);

		$dataMw['parts'][$this->partIndex]['template']['params'] = $parameterData;

		$this->domElement->setAttribute('data-mw', \json_encode($dataMw) );
	}

	/**
	 * Update the parameters listed, ignore other parameters.
	 *
	 * @param object $parameterData.
	 * @param array $removeParameters.
	 */
	public function patchParameters( $parameterData, $removeParameters = array() )
	{
		$dataMw = \json_decode($this->domElement->getAttribute('data-mw'));

		foreach ( get_object_vars( $parameterData ) as $paramName => $paramInfo ) {
			$dataMw->parts[$this->partIndex]->template->params->{$paramName} = $paramInfo;
		}

		foreach ($removeParameters as $remove) {
			unset($dataMw->parts[$this->partIndex]->template->params->{$remove});
		}

		$this->domElement->setAttribute('data-mw', \json_encode($dataMw) );
	}

	/**
	 * @return string The template title.
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @return int The id of this particular instance of the target template on the particular page.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Remove this transclusion.
	 */
	public function remove()
	{
		$dataMw = \json_decode($this->domElement->getAttribute('data-mw'));

		array_splice( $dataMw->parts, $this->partIndex, 1 );

		if ( count($dataMw->parts) === 0 ) {
			$about = $this->domElement->getAttribute( 'about' );
			$this->domElement->parentNode->removeChild( $this->domElement );
			if ( !empty( $about ) ) {
				$xpath = new \DOMXPath( $this->domElement->ownerDocument );
				$nodeList = $xpath->query( '//body//*[@about="' . $about . '"]' );
				for ( $i = 0; $i < $nodeList->length; $i++ ) {
					$node = $nodeList->item( $i );
					$node->parentNode->removeChild( $node );
				}
			}
		} else {
			$this->domElement->setAttribute( 'data-mw', \json_encode($dataMw) );
		}
	}
}