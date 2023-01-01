<?php
/**
 * Class declaration for mediawiki extension OpauthLogin
 *
 * @file OpauthLogin.class.php
 * @ingroup OpauthLogin
 */

class OpauthLogin {

    public static function addUidLink( $uid, $provider, $user_id )
    {
        $dbw = wfGetDB(DB_MASTER);
        $dbw->insert(
            'opauth_login',
            array(
                'uid' => $uid,
                'provider' => $provider,
                'user_id' => $user_id
            )
        );
        $dbw->commit();
    }

    /**
     * Checks if external UID already linked to some user account
     * @param string $uid
     * @param string $provider
     * @return bool
     */
    public static function isUidLinked( $uid, $provider )
    {
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->select(
            'opauth_login',
            '*',
            array(
                'uid' => $uid,
                'provider' => $provider
            )
        );
        if( $result && $result->numRows() ) {
            return true;
        }

        return false;
    }

    /**
     * Fetch Mediawiki User from external uid and provider pair
     * @param string $uid
     * @param string $provider
     * @return bool|User
     */
    public static function getUidUser( $uid, $provider )
    {
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow(
            'opauth_login',
            '*',
            array(
                'uid' => $uid,
                'provider' => $provider
            )
        );
        if( $result ) {
            $user = User::newFromId( $result->user_id );
            if( $user && ( $user->getId() != 0 ) ) {
                return $user;
            }
        }
        return false;
    }

    /**
     * @param integer $user_id
     * @return bool|integer
     */
    public static function getUserUid( $user_id )
    {
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow(
            'opauth_login',
            '*',
            array(
                'user_id' => $user_id
            )
        );
        if( $result ) {
            return $result->uid;
        }
        return false;
    }

    /**
     * @param integer $user_id
     * @return bool|integer
     */
    public static function getUserProvider( $user_id )
    {
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow(
            'opauth_login',
            '*',
            array(
                'user_id' => $user_id
            )
        );
        if( $result ) {
            return $result->provider;
        }
        return false;
    }

    /**
     * @param integer $user_id
     * @return bool|integer
     */
    public static function isUserExists( $user_id )
    {
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow(
            'opauth_login',
            '*',
            array(
                'user_id' => $user_id
            )
        );
        if( $result ) {
            return true;
        }
        return false;
    }

    /**
     * Checks if there is already user with same email exists in Mediawiki database
     * @param string $email
     * @return bool
     */
    public static function isEmailCollate( $email )
    {
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow(
            'user',
            'user_email',
            array(
                'user_email' => $email
            )
        );
        if( $result ) {
            return true;
        }
        return false;
    }

	/**
	 * Returns markup for login buttons. This method is quite limited to the bootstrap skin
	 * but fortunately there is a hook which allows to alter this markup
	 *
	 * @return string
	 */
	public static function getButtonsMarkup( $withText = true ) {

		global $wgOpauthConfig;

		$html = '';

		if( array_key_exists('Strategy', $wgOpauthConfig ) && count($wgOpauthConfig['Strategy']) ) {

			foreach ($wgOpauthConfig['Strategy'] as $name => $strategy) {

				$buttonHtml = '';

				$lowerName = strtolower($name);

				$classes = 'btn btn-sm';
				$icon = 'fa fa-'.$lowerName;

				if( $lowerName == 'facebook' ) {
					$classes .= ' btn-info';
					$icon = 'fa fa-facebook-official';
				}

				if( $lowerName == 'google' ) {
					$classes .= ' btn-danger';
					$icon = 'fa fa-google';
				}

				$buttonHtml .= '<a href="'.OpauthHelper::getLoginLink($lowerName).'" class="'.$classes.'">';
				if( $withText ) {
					$buttonHtml .= wfMessage( 'opauthlogin-link-login-via-text' )->params( $name )->plain();
				}
				$buttonHtml .= '&nbsp;<i class="'.$icon.'" aria-hidden="true"></i>';
				$buttonHtml .= '</a>&nbsp;';

				// Allow extensions to customize buttons markup for each strategy
				wfRunHooks('OpauthLoginButtonsMarkup', array( $name, $strategy, &$buttonHtml, $withText ) );

				$html .= $buttonHtml;

			}

		}

		return $html;

	}

}