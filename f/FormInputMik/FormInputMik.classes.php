<?php
/**
 * Classes for FormInputMik extension
 *
 * @file
 * @ingroup Extensions
 */

// FormInputMik class
class FormInputMik {
	
	/* Fields */

	private $mParser;
	private $mType = '';
	private $mWidth = 50;
	private $mBR = 'yes';
	private $mPlaceholderText = '';
	private $mBGColor = 'transparent';
	private $mButtonLabel = '';
	private $mHidden = '';
	private $mNamespaces = '';
	private $mID = '';
	private $mDir = '';
	private $mNewWin = '';
	private $mRemoteAutocompletion = '';

	private $inValue = '';

	/* Functions */
	
	public function __construct( $parser ) {
		$this->mParser = $parser;
		// Default value for dir taken from the page language (bug 37018)
		$this->mDir = $this->mParser->getTargetLanguage()->getDir();
		// Split caches by language, to make sure visitors do not see a cached
		// version in a random language (since labels are in the user language)
		$this->mParser->getOptions()->getUserLangObj();
	}

	public function render() {
		// Handle various types
		switch( $this->mType ) {
			case 'create':
				return $this->getCreateForm();
			case 'createedit':
				return $this->getCreateEditForm();
			default:
				return Xml::tags( 'div', null,
					Xml::element( 'strong',
						array(
							'class' => 'error'
						),
						strlen( $this->mType ) > 0
						? wfMessage( 'forminputmik-error-bad-type', $this->mType )->text()
						: wfMessage( 'forminputmik-error-no-type' )->text()
					)
				);
		}
	}
	
	/**
	 * Generate create page form
	 */
	
	public function getCreateForm() {
		global $wgScript;
		global $wgHtml5;
		global $wgScript;
		if ( !$this->mButtonLabel ) {
			$this->mButtonLabel = wfMessage( 'createarticle' )->text();
		}
	
		$fs = SpecialPageFactory::getPage( 'FormEdit' );
		$classStr = 'popupforminput';
		$fs_url = $fs->getTitle()->getLocalURL();
		$formInputAttrs = array( 'size' => $this->mWidth );
		if ( $wgHtml5 ) {
			$formInputAttrs['placeholder'] = $this->mPlaceholderText;
		}
		// Now apply the necessary settings and Javascript, depending
		// on whether or not there's autocompletion (and whether the
		// autocompletion is local or remote).
		$input_num = 1;
		$inNamespace = $this->mNamespaces;
		if ( !isset( $inNamespace ) || $inNamespace == '' ) {
			//if ( empty( $inAutocompletionSource ) ) {
			$formInputAttrs['class'] = 'formInput';
		} else {
			SFParserFunctions::$num_autocompletion_inputs++;
			$input_num = SFParserFunctions::$num_autocompletion_inputs;
			// place the necessary Javascript on the page, and
			// disable the cache (so the Javascript will show up) -
			// if there's more than one autocompleted #forminput
			// on the page, we only need to do this the first time
			if ( $input_num == 1 ) {
				$this->mParser->disableCache();
				SFUtils::addJavascriptAndCSS( $this->mParser );
			}
			$inputID = 'input_' . $input_num;
			$inputIDform = $inputID."form";
			$formInputAttrs['id'] = $inputID;
			//$formInputAttrs['namespace'] = $inNamespace;
			$formInputAttrs['class'] = 'autocompleteInput createboxInput formInput';
			$mRemoteAutocompletion = $this->mRemoteAutocompletion;
			if ( $mRemoteAutocompletion ) {
				$formInputAttrs['autocompletesettings'] = $inNamespace;
				$formInputAttrs['autocompletedatatype'] = 'namespace';
			} else {
				$autocompletion_values = SFUtils::getAutocompleteValues( $inNamespace, 'namespace' );
				global $sfgAutocompleteValues;
				$sfgAutocompleteValues[$inputID] = $autocompletion_values;
				$formInputAttrs['autocompletesettings'] = $inputID;
			}
		}
		/*$htmlOut = <<<END
		 <form id="$inputIDform" name="createbox" action="$fs_url" method="post" class="$classStr">
		<p>
		END;*/
		//$htmlOut = "<p style='margin-left: auto; margin-right: auto; text-align: center; background-color:'" . $this->mBGColor."'>";
		$inBGColor = $this->mBGColor;
		$htmlOut = Xml::openElement( 'div',
				array('style' => 'margin-left: auto; margin-right: auto; text-align: center; background-color:$inBGColor')
		);
		/*
			$htmlOut = Xml::openElement( 'div',
					array('style' => 'text-align: center; background-color:' . $this->mBGColor)
			);
		*/
		if ( isset( $this->inValue ) ) {
			$inValue = $this->inValue;
		}
		if ( is_null($inValue) ) {
			$inValue = '';
		}
		
		$htmlOut .= Html::input( 'page_name', $inValue, 'text', $formInputAttrs ) ;
	
		// if the form start URL looks like "index.php?title=Special:FormStart"
		// (i.e., it's in the default URL style), add in the title as a
		// hidden value
		if ( ( $pos = strpos( $fs_url, "title=" ) ) > - 1 ) {
			$htmlOut .= Html::hidden( "title", urldecode( substr( $fs_url, $pos + 6 ) ) );
		}
		$inFormName = $inValue = $inButtonStr = $inQueryStr = '';
		$inButtonStr = $this->mButtonLabel;
		$inFormName = $this->mForm;
		//$inFormAttrs = array( 'id' => $inputID.'_namespace' );
		//$inFormAttrs['type'] = 'hidden';
		//$inFormAttrs['value'] = $inFormName;
		$inputID_namespace = $inputID."_namespace";
		$htmlOut .= Html::hidden( $inputID_namespace, $inFormName );
	
		if ( $inFormName == '' ) {
			$htmlOut .= SFUtils::formDropdownHTML();
		} else {
			$htmlOut .= Html::hidden( "form", $inFormName );
		}
		// Recreate the passed-in query string as a set of hidden variables.
		if ( !empty( $inQueryArr ) ) {
			// query string has to be turned into hidden inputs.
			$query_components = explode( '&', http_build_query( $inQueryArr, '', '&' ) );
			foreach ( $query_components as $query_component ) {
				$var_and_val = explode( '=', $query_component, 2 );
				if ( count( $var_and_val ) == 2 ) {
					$htmlOut .= Html::hidden( urldecode( $var_and_val[0] ), urldecode( $var_and_val[1] ) );
				}
			}
		}
		$mBR = $this->mBR;
		$htmlOut .= $mBR;
		$button_str = ( $inButtonStr != '' ) ? $inButtonStr : wfMessage( 'sf_formstart_createoredit' )->escaped();
		$inNewWin = 'false';
		if ( isset( $this->mNewWin ) && ( $this->mNewWin == 'true')) {
			$inNewWin = 'true';
		}
		
		$htmlOut .= <<<END
<input type="button" value="$button_str" onclick="var a='$fs_url';if(($.inArray($inputID.value,mw.config.get('sfgAutocompleteValues').$inputID)) < 0){a='$fs_url/$inFormName?&namespace=$inNamespace';}else{a='$wgScript/$inNamespace:'+$inputID.value.replace(/ /g,'_');};if($inNewWin){window.open(a);}else{window.location=a;}"/>
END;
		//</form
		if ( ! empty( $inNamespace ) ) {
			//$htmlOut .= "\t\t\t" .
			Html::element( 'div',
			array(
			'class' => 'page_name_auto_complete',
			'id' => "div_$input_num",
			),
			// it has to be <div></div>, not
			// <div />, to work properly - stick
			// in a space as the content
			' '
					) ;
		}
		$htmlOut .= Xml::closeElement( 'div' );
		// hack to remove newline from beginning of output, thanks to
		// http://jimbojw.com/wiki/index.php?title=Raw_HTML_Output_from_a_MediaWiki_Parser_Function
		return $htmlOut;
		//return $this->mParser->insertStripItem( $htmlOut, $this->mParser->mStripState );
		// Return HTML
		//return $htmlOut;
	}
	

	public function getCreateEditForm() {
		global $wgScript;
		global $wgHtml5;
	
		if ( !$this->mButtonLabel ) {
			$this->mButtonLabel = wfMessage( 'createarticle' )->text();
		}
	
		$fs = SpecialPageFactory::getPage( 'FormEdit' );
		$classStr = 'popupforminput';
		$fs_url = $fs->getTitle()->getLocalURL();
		$formInputAttrs = array( 'size' => $this->mWidth );
		if ( $wgHtml5 ) {
			$formInputAttrs['placeholder'] = $this->mPlaceholderText;
		}
		// Now apply the necessary settings and Javascript, depending
		// on whether or not there's autocompletion (and whether the
		// autocompletion is local or remote).
		$input_num = 1;
		$inNamespace = $this->mNamespaces;
		if ( !isset( $inNamespace ) || $inNamespace == '' ) {
			//if ( empty( $inAutocompletionSource ) ) {
			$formInputAttrs['class'] = 'formInput';
		} else {
			SFParserFunctions::$num_autocompletion_inputs++;
			$input_num = SFParserFunctions::$num_autocompletion_inputs;
			// place the necessary Javascript on the page, and
			// disable the cache (so the Javascript will show up) -
			// if there's more than one autocompleted #forminput
			// on the page, we only need to do this the first time
			if ( $input_num == 1 ) {
				$this->mParser->disableCache();
				SFUtils::addJavascriptAndCSS( $this->mParser );
			}
			$inputID = 'input_' . $input_num;
			$inputIDform = $inputID."form";
			$formInputAttrs['id'] = $inputID;
			//$formInputAttrs['namespace'] = $inNamespace;
			$formInputAttrs['class'] = 'autocompleteInput createboxInput formInput';
			$mRemoteAutocompletion = $this->mRemoteAutocompletion;
			if ( $mRemoteAutocompletion ) {
				$formInputAttrs['autocompletesettings'] = $inNamespace;
				$formInputAttrs['autocompletedatatype'] = 'namespace';
			} else {
				$autocompletion_values = SFUtils::getAutocompleteValues( $inNamespace, 'namespace' );
				global $sfgAutocompleteValues;
				$sfgAutocompleteValues[$inputID] = $autocompletion_values;
				$formInputAttrs['autocompletesettings'] = $inputID;
			}
		}
		/*$htmlOut = <<<END
		 <form id="$inputIDform" name="createbox" action="$fs_url" method="post" class="$classStr">
		<p>
		END;*/
		//$htmlOut = "<p style='margin-left: auto; margin-right: auto; text-align: center; background-color:'" . $this->mBGColor."'>";
		$inBGColor = $this->mBGColor;
		$htmlOut = Xml::openElement( 'div',
				array('style' => 'margin-left: auto; margin-right: auto; text-align: center; background-color:$inBGColor')
		);
		/*
			$htmlOut = Xml::openElement( 'div',
					array('style' => 'text-align: center; background-color:' . $this->mBGColor)
			);
		*/
		$htmlOut .= Html::input( 'page_name', $inValue, 'text', $formInputAttrs ) ;
	
		// if the form start URL looks like "index.php?title=Special:FormStart"
		// (i.e., it's in the default URL style), add in the title as a
		// hidden value
		if ( ( $pos = strpos( $fs_url, "title=" ) ) > - 1 ) {
			$htmlOut .= Html::hidden( "title", urldecode( substr( $fs_url, $pos + 6 ) ) );
		}
		$inFormName = $inValue = $inButtonStr = $inQueryStr = '';
		$inButtonStr = $this->mButtonLabel;
		$inFormName = $this->mForm;
		//$inFormAttrs = array( 'id' => $inputID.'_namespace' );
		//$inFormAttrs['type'] = 'hidden';
		//$inFormAttrs['value'] = $inFormName;
		$inputID_namespace = $inputID."_namespace";
		$htmlOut .= Html::hidden( $inputID_namespace, $inFormName );
	
		if ( $inFormName == '' ) {
			$htmlOut .= SFUtils::formDropdownHTML();
		} else {
			$htmlOut .= Html::hidden( "form", $inFormName );
		}
		// Recreate the passed-in query string as a set of hidden variables.
		if ( !empty( $inQueryArr ) ) {
			// query string has to be turned into hidden inputs.
			$query_components = explode( '&', http_build_query( $inQueryArr, '', '&' ) );
			foreach ( $query_components as $query_component ) {
				$var_and_val = explode( '=', $query_component, 2 );
				if ( count( $var_and_val ) == 2 ) {
					$htmlOut .= Html::hidden( urldecode( $var_and_val[0] ), urldecode( $var_and_val[1] ) );
				}
			}
		}
		$mBR = $this->mBR;
		$htmlOut .= $mBR;
		$button_str = ( $inButtonStr != '' ) ? $inButtonStr : wfMessage( 'sf_formstart_createoredit' )->escaped();
		$inNewWin = 'false';
		if ( isset( $this->mNewWin ) && ( $this->mNewWin == 'true')) {
			$inNewWin = 'true';
		}
		$htmlOut .= <<<END
<input type="button" value="$button_str" onclick="var a='$fs_url';if(($.inArray($inputID.value,mw.config.get('sfgAutocompleteValues').$inputID)) < 0){a='$fs_url/$inFormName?&namespace=$inNamespace';}else{a='$fs_url/$inFormName/$inNamespace:'+$inputID.value.replace(/ /g,'_');};if($inNewWin){window.open(a);}else{window.location=a;}"/>
END;
		//</form
		if ( ! empty( $inNamespace ) ) {
			//$htmlOut .= "\t\t\t" .
			Html::element( 'div',
			array(
			'class' => 'page_name_auto_complete',
			'id' => "div_$input_num",
			),
			// it has to be <div></div>, not
			// <div />, to work properly - stick
			// in a space as the content
			' '
					) ;
		}
		$htmlOut .= Xml::closeElement( 'div' );
		// hack to remove newline from beginning of output, thanks to
		// http://jimbojw.com/wiki/index.php?title=Raw_HTML_Output_from_a_MediaWiki_Parser_Function
		return $htmlOut;
		//return $this->mParser->insertStripItem( $htmlOut, $this->mParser->mStripState );
		// Return HTML
		//return $htmlOut;
	}
	
	
	/**
	 * Extract options from a blob of text
	 *
	 * @param string $text Tag contents
	 */
	 
	public function extractOptions( $text ) {
		wfProfileIn( __METHOD__ );
		// Parse all possible options
		$values = array();
		foreach ( explode( "\n", $text ) as $line ) {
			if ( strpos( $line, '=' ) === false )
				continue;
			list( $name, $value ) = explode( '=', $line, 2 );
			$values[ strtolower( trim( $name ) ) ] = Sanitizer::decodeCharReferences( trim( $value ) );
		}
		// Validate the dir value.
		if ( isset( $values['dir'] ) && !in_array( $values['dir'], array( 'ltr', 'rtl' ) ) ) {
			unset( $values['dir'] );
		}
		// Build list of options, with local member names
		$options = array(
		  'type' => 'mType',
			'width' => 'mWidth',
			'page' => 'mPage',
			'editintro' => 'mEditIntro',
			'summary' => 'mSummary',
			'nosummary' => 'mNosummary',
			'minor' => 'mMinor',
			'break' => 'mBR',
			'placeholder' => 'mPlaceholderText',
			'bgcolor' => 'mBGColor',
			'form' => 'mForm',
			'buttonlabel' => 'mButtonLabel',
			'search on namespace' => 'mNamespaces',
			'remote autocompletition' => 'mRemoteAutocompletion',
			'hidden' => 'mHidden',
			'id' => 'mID',
			'newwin' => 'mNewWin', 
		);
		foreach ( $options as $name => $var ) {
			if ( isset( $values[$name] ) ) {
				$this->$var = $values[$name];
			}
		}
		// Insert a line break if configured to do so
		$this->mBR = ( strtolower( $this->mBR ) == "no" ) ? ' ' : '<br />';
		// Validate the width; make sure it's a valid, positive integer
		$this->mWidth = intval( $this->mWidth <= 0 ? 50 : $this->mWidth );
		// Validate background color
		if ( !$this->isValidColor( $this->mBGColor ) ) {
			$this->mBGColor = 'transparent';
		}
		wfProfileOut( __METHOD__ );
	}
	
	/**
	 * Do a security check on the bgcolor parameter
	 */
	
	public function isValidColor( $color ) {
		$regex = <<<REGEX
			/^ (
				[a-zA-Z]* |       # color names
				\# [0-9a-f]{3} |  # short hexadecimal
				\# [0-9a-f]{6} |  # long hexadecimal
				rgb \s* \( \s* (
					\d+ \s* , \s* \d+ \s* , \s* \d+ |    # rgb integer
					[0-9.]+% \s* , \s* [0-9.]+% \s* , \s* [0-9.]+%   # rgb percent
				) \s* \)
			) $ /xi
REGEX;
		return (bool) preg_match( $regex, $color );
	}
	
}
