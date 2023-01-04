<?php

/**
 * A tags input field.
 */

namespace Ainut;

use HTMLFormField;
use XmlSelect;

class HTMLTagsField extends HTMLFormField {
	public function validate( $value, $alldata ) {
		$p = parent::validate( $value, $alldata );

		if ( $p !== true ) {
			return $p;
		}

		if ( !is_array( $value ) ) {
			return false;
		}

		# If all options are valid, array_intersect of the valid options
		# and the provided options will return the provided options.
		$validOptions = HTMLFormField::flattenOptions( $this->getOptions() );

		$validValues = array_intersect( $value, $validOptions );
		if ( count( $validValues ) == count( $value ) ) {
			return true;
		} else {
			return $this->msg( 'htmlform-select-badoption' );
		}
	}

	public function filterDataForSubmit( $data ) {
		$data = HTMLFormField::forceToStringRecursive( $data );
		$options = HTMLFormField::flattenOptions( $this->getOptions() );

		$res = [];
		foreach ( $options as $opt ) {
			$res["$opt"] = in_array( $opt, $data, true );
		}

		return $res;
	}

	public function getInputHTML( $value ) {
		$this->mParent->getOutput()->addModules( [ 'ext.ainut.form' ] );

		$select = new XmlSelect( $this->mName . '[]', $this->mID, $value );

		if ( !empty( $this->mParams['disabled'] ) ) {
			$select->setAttribute( 'disabled', 'disabled' );
		}

		$allowedParams = [ 'tabindex', 'size', 'multiple' ];
		$customParams = $this->getAttributes( $allowedParams );
		foreach ( $customParams as $name => $value ) {
			$select->setAttribute( $name, $value );
		}

		$select->setAttribute( 'class', 'mw-ainut-tags' );
		$select->setAttribute( 'style', 'width: 100%' );

		$select->addOptions( $this->getOptions() );

		return $select->getHTML();
	}

	public function getInputOOUI( $value ) {
		return $this->getInputHTML( $value );
	}

	public function loadDataFromRequest( $request ) {
		// BC MW 1.26 (isSubmitAttempt)
		if ( $request->getCheck( 'wpEditToken' ) || $request->getCheck( 'wpFormIdentifier' ) ) {
			return $request->getArray( $this->mName, [] );
		} else {
			// That's ok, the user has not yet submitted the form, so show the defaults
			return $this->getDefault();
		}
	}

	public function getDefault() {
		if ( isset( $this->mDefault ) ) {
			return $this->mDefault;
		} else {
			return [];
		}
	}
}
