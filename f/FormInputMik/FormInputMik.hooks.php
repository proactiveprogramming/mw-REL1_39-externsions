<?php
/**
 * Hooks for FormInputMik extension
 *
 * @file
 * @ingroup Extensions
 */

// FormInputMik hooks
class FormInputMikHooks {
	// Initialization
	public static function register( Parser &$parser ) {
		// Register the hook with the parser
		$parser->setHook( 'forminputmik', array( 'FormInputMikHooks', 'render' ) );

		// Continue
		return true;
	}

	// Render the form input
	public static function render( $input, $args, Parser $parser ) {
		// Create FormInputMik
		$formInputMik = new FormInputMik( $parser );
		// Configure FormInputMik
		$formInputMik ->extractOptions( $parser->replaceVariables( $input ) );
		// Return output
		return $formInputMik ->render();
	}
	
	/**
	 * <forminputmik type=create...> checks if exists a page correspondint to input text
	 * and sends it to either forminput (edit) or formlink (add)
	 * @param $output OutputPage
	 * @param $article Article
	 * @param $title Title
	 * @param $user User
	 * @param $request WebRequest 
	 * @param $wiki MediaWiki
	 * @return bool
	 */
	public static function onMediaWikiPerformAction( 
		$output, 
		$article, 
		$title, 
		$user, 
		$request, 
		$wiki )
	{

		if( $wiki->getAction( $request ) !== 'edit' ){
			# not our problem
			return true;
		}
		if( $request->getText( 'prefix', '' ) === '' ){
			# Fine
			return true;
		}
		
		$params = $request->getValues();
		print_r($params);
		$title = $params['prefix'];
		if ( isset( $params['title'] ) ){
			$title .= $params['title'];
		}
		
		unset( $params['prefix'] );
		$params['title'] = $title;
		
		global $wgScript;
		$output->redirect( wfAppendQuery( $wgScript, $params ), '301' );
		return false;
	}
}
