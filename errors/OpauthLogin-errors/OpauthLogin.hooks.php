<?php
/**
 * Hooks class declaration for mediawiki extension OpauthLogin
 *
 * @file OpauthLogin.hooks.php
 * @ingroup OpauthLogin
 */

class OpauthLoginHooks {

    public static function onOpauthUserAuthorized( $provider, $uid, $info, $raw )
    {

        global $wgOpauthLoginRequestUsername;

	    if( OpauthLogin::isUidLinked( $uid, $provider ) ) {
		    // Login existing user into system

		    $user = OpauthLogin::getUidUser( $uid, $provider );
		    wfRunHooks('OpauthLoginUserAuthorized', array( $user, $provider, $uid, $info ) );
		    self::loginUser( $user );

	    }else{

	    	// Based on $wgOpauthLoginRequestUsername option we should either ask user to provide username
		    // or just proceed with user creation process

		    if( $wgOpauthLoginRequestUsername ) {
			    self::requestUsername( $provider, $uid, $info, $raw );
		    }else{
			    self::authenticateCreateUser( $provider, $uid, $info, $raw );
		    }
		    
	    }

        return true;
    }

    public static function authenticateCreateUser( $provider, $uid, $info, $raw ) {

	    // Called when user was successfully authenticated from Opauth

	    // This function should compare UID with internal storage and decide to create new account for this user
	    // or load existing user from database

	    // Create new user from external data, $info refers to https://github.com/opauth/opauth/wiki/Auth-response

	    // Lets try to prepare username for wiki
	    // try to convert given input into canonical name without any validation
	    $canonicalName = User::getCanonicalName( $info['name'], 'valid' );

	    // Lets try to create with original username first and
	    // iterate until we will find available name

	    if( $canonicalName && User::isCreatableName( $canonicalName ) ) {

		    // First check if this name can be used at all
		    // Then check if there are users with same name exists

		    $testUser = User::newFromName( $canonicalName );
		    $suffix = 0;

		    while( $testUser->getId() !== 0 ) {
			    $suffix++;
			    // We're free to add suffix since base part of the name was already checked for validity
			    $testUser = User::newFromName( $canonicalName.' '.$suffix );
		    }

		    // Use found available name
		    $user = $testUser;

	    }else{

		    /**
		     * We set UID based string as user name in mediawiki to avoid
		     * user nicknames override and collisions problems. We store external user name into
		     * "real name" field of user object. This should be supported in skin.
		     */
		    $user = User::newFromName( md5( $provider.$uid ) . '_' . $uid, false  );

	    }

	    $user->setRealName( $info['name'] );
	    if( array_key_exists('email', $info) ) {
		    if( !OpauthLogin::isEmailCollate( $info['email'] ) ) {
			    $user->setEmail( $info['email'] );
		    }
	    }
	    $user->setPassword( md5( $info['name'] . time() ) );
	    $user->setToken();
	    $user->confirmEmail(); // Mark email address as confirmed by default
	    $user->addToDatabase(); // Commit changes to database

	    OpauthLogin::addUidLink( $uid, $provider, $user->getId() );

	    // Update site stats
	    $ssUpdate = new SiteStatsUpdate(0, 0, 0, 0, 1);
	    $ssUpdate->doUpdate();

	    // Run AddNewAccount hook for proper handling
	    wfRunHooks( 'AddNewAccount', array( $user, false, 'opauth' ) );

	    wfRunHooks('OpauthLoginUserCreated', array( $user, $provider, $info, $uid ) );

	    self::loginUser( $user, true );

    }

	/**
	 * Logins the user and runs necessary hooks
	 *
	 * @param User $user
	 * @param bool $wasCreated
	 */
    public static function loginUser( $user, $wasCreated = false ) {

    	global $wgOut, $wgUser;

	    // Replace current user with new one
	    $wgUser = $user;
	    $wgUser->setCookies( null, null, true );

	    $redirectTarget = Title::newMainPage()->getFullURL();

	    if( array_key_exists('opauth_returnto', $_SESSION) && isset($_SESSION['opauth_returnto']) ) {
		    $returnToTitle = Title::newFromText( $_SESSION['opauth_returnto'] );
		    unset($_SESSION['opauth_returnto']);
		    $redirectTarget = $returnToTitle->getFullURL();
	    }

	    // Allow extensions to modify final redirect
	    // $redirectTarget - URL to redirect user
	    // $user - authenticated User object
	    // $wasCreated - flag indicates if user was created or just authenticated during the session
	    wfRunHooks('OpauthLoginFinalRedirect', array( &$redirectTarget, $user, $wasCreated ) );

	    $wgOut->redirect( $redirectTarget );

    }

	/**
	 * Initiates additional information request
	 * called after successful authorization via provider
	 *
	 * @param string $provider
	 * @param int $uid
	 * @param array $info
	 * @param array $raw
	 */
    public static function requestUsername( $provider, $uid, $info, $raw ) {

    	global $wgOut;

		// Pack necessary information into session storage
	    if( session_id() == '' ) {
	    	wfSetupSession();
	    }

	    $_SESSION['opauth_login_provider'] = $provider;
	    $_SESSION['opauth_login_uid'] = $uid;
	    $_SESSION['opauth_login_info'] = $info;

	    // Redirect user to the special page
	    $wgOut->redirect( SpecialPage::getTitleFor('OpauthLoginInfo')->getFullURL() );

    }

    public static function onUserLoadFromSession( $user, &$result ) {

        // Called when user was loaded (or not) from session

        return true;
    }

    /**
     * @param DatabaseUpdater $updater
     */
    public static function onLoadExtensionSchemaUpdates( $updater ) {
        global $wgOpauthLoginDir;
        $updater->addExtensionTable(
            'opauth_login',
            $wgOpauthLoginDir .'/schema/opauth_login.sql'
        );
    }

	/**
	 * @param UsercreateTemplate $template
	 */
	public static function onUserCreateForm( &$template ) {
		global $wgOpauthLoginEnableButtons;
		if( $wgOpauthLoginEnableButtons ) {
			$template->set( 'header', OpauthLogin::getButtonsMarkup() );
		}
	}

	/**
	 * @param UserloginTemplate $template
	 */
	public static function onUserLoginForm( &$template ) {
		global $wgOpauthLoginEnableButtons;
		if( $wgOpauthLoginEnableButtons ) {
			$template->set( 'header', OpauthLogin::getButtonsMarkup() );
		}
	}

}