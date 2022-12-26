<?php
class EmailDeletedPages {
        /** Add our toggle
         * @param $user
         * @param &$preferences
         * @public
         */
        public static function togglify( $user, &$preferences )  {
                global $wgEmailAuthentication;
                $disableEmailPrefs = true;
                if ( $wgEmailAuthentication ) {
                        $emailauthenticationclass = 'mw-email-not-authenticated';
			if ( $user->getEmail() ) {
				if ( $user->getEmailAuthenticationTimestamp() ) {
                                        $disableEmailPrefs = false;
                                }
                        }
                }
                // A checkbox
                $preferences['emaildeletedpages'] = array(
                        'type' => 'toggle',
                        'label-message' => 'tog-emaildeletedpages', // a system message
                        'section' => 'personal/email',
                        'disabled' => $disableEmailPrefs
                );
                return true;
        }

        public static function onArticleDelete( &$article, &$user, &$reason, &$error ) {
                // TODO: What about the File: namespace?
                global $wgSitename;

                $creator = $article->getCreator();
                if ( !$creator->isEmailConfirmed() ) {
                        return true;
                }
                // If the creator hasn't asked that pages he creates be emailed to him when they're
                // deleted, send him nothing
                if ( !$creator->getBoolOption( 'emaildeletedpages' ) ) {
                        return true;
                }
                $userPageCanonicalURL = $user->getUserPage()->getCanonicalURL();
                $userName = $user->getName();
                $titlePrefixedDBKey = $article->getTitle()->getPrefixedDBKey();
                $body = wfMessage ( 'emaildeletedpages-email-body-intro' )->text();
                $content = $article->getContent ( Revision::FOR_THIS_USER, $creator );
                $revid = $article->getLatest();
                // If the most recent revision is unavailable to this user, fall back to the most
                // recent revision by this user
                if ( !$content ) {
                        $dbr = wfGetDB( DB_REPLICA );
                        $res = $dbr->selectRow( 'revision', 'rev_id', array (
                                        'rev_user' => $creator->getId(),
                                        'rev_page' => $article->getId()
                                ),
                                __METHOD__,
                                array ( 'ORDER BY' => 'rev_id DESC' )
                        );
                        if ( $res ) {
                                $revision = Revision::loadFromId ( $res->rev_id );
                                $text = $revision->getContent( Revision::FOR_THIS_USER, $creator );
                                if ( $text ) {
                                        $body = wfMessage (
                                                'emaildeletedpages-email-body-intro-fallback' )->text()
                                                . $text;
                                } else {
                                        if ( $res->rev_id == $revid ) {
                                                $body = wfMessage
                                                        ( 'emaildeletedpages-text-samerev-unavailable' )->text();
                                        } else {
                                                $body = wfMessage
                                                        ( 'emaildeletedpages-text-unavailable' )->text();
                                        }
                                }
                        }
                } else {
                        $body .= ContentHandler::getContentText( $content );
                }
                $opening = wfMessage ( 'emaildeletedpages-email-opening', $userName,
                        $userPageCanonicalURL, $titlePrefixedDBKey, $reason )->text();
                $subject = wfMessage ( 'emaildeletedpages-email-subject',
                        $titlePrefixedDBKey, $wgSitename )->text();
                $closing = wfMessage ( 'emaildeletedpages-email-closing' )->text();
                $creator->sendMail ( $subject, $opening . $body . $closing );
                return true;
        }
}
