<?php

namespace BlueSpice\EchoConnector\Notifier;

use MailAddress;
use MediaWiki\Block\AbstractBlock;
use MediaWiki\MediaWikiServices;
use User;

/**
 * Override of default EchoNotifier - copy-paste from there
 * All this because Echo uses hard-coded formatters for mails
 */
class EchoNotifier extends \EchoNotifier {

	/**
	 * @param \User $user
	 * @param \EchoEvent $event
	 */
	public static function notifyWithNotification( $user, $event ) {
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BlueSpiceEchoConnectorNotifyBeforeSend', [ &$event, $user, 'web' ]
		);

		parent::notifyWithNotification( $user, $event );
	}

	/**
	 *
	 * @param \User $user
	 * @param \EchoEvent $event
	 * @return bool
	 */
	public static function notifyWithEmail( $user, $event ) {
		global $wgEnableEmail, $wgBlockDisablesLogin;

		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		if (
			// Email is globally disabled
			!$wgEnableEmail ||
			// User does not have a valid and confirmed email address
			!$user->isEmailConfirmed() ||
			// User has disabled Echo emails
			$userOptionsLookup->getOption( $user, 'echo-email-frequency' ) < 0 ||
			// User is blocked and cannot log in (T199993)
			( $wgBlockDisablesLogin && $user->getBlock( true ) instanceof AbstractBlock )
		) {
			return false;
		}

		// Final check on whether to send email for this user & event
		if (
			!$services->getHookContainer()->run(
				'EchoAbortEmailNotification', [ $user, $event ]
			)
		) {
			return false;
		}

		$attributeManager = \EchoAttributeManager::newFromGlobalVars();
		$userEmailNotifications = $attributeManager->getUserEnabledEvents( $user, 'email' );
		// See if the user wants to receive emails for this category or the user is
		// eligible to receive this email
		if ( in_array( $event->getType(), $userEmailNotifications ) ) {
			global $wgEchoEnableEmailBatch, $wgEchoNotifications, $wgPasswordSender,
					$wgNoReplyAddress;

			$priority = $attributeManager->getNotificationPriority( $event->getType() );

			$bundleString = $bundleHash = '';

			// We should have bundling for email digest as long as either web or email bundling is on,
			// for example, talk page email bundling is off, but if a user decides to receive email
			// digest, we should bundle those messages
			if ( !empty( $wgEchoNotifications[$event->getType()]['bundle']['web'] ) ||
				!empty( $wgEchoNotifications[$event->getType()]['bundle']['email'] )
			) {
				$services->getHookContainer()->run(
					'EchoGetBundleRules', [ $event, &$bundleString ]
				);
			}
			// @phan-suppress-next-line PhanImpossibleCondition May be set by hook
			if ( $bundleString ) {
				$bundleHash = md5( $bundleString );
			}

			\MWEchoEventLogging::logSchemaEcho( $user, $event, 'email' );

			$services->getHookContainer()->run(
				'BlueSpiceEchoConnectorNotifyBeforeSend',
				[
					&$event,
					$user,
					'email'
				]
			);

			$extra = $event->getExtra();
			$sendImmediately = isset( $extra['immediate-email'] )
				&& $extra['immediate-email'] == true;

			// email digest notification ( weekly or daily )
			if (
				$wgEchoEnableEmailBatch &&
				$userOptionsLookup->getOption( $user, 'echo-email-frequency' ) > 0 &&
				!$sendImmediately
			) {
				// always create a unique event hash for those events don't support bundling
				// this is mainly for group by
				if ( !$bundleHash ) {
					$bundleHash = md5( $event->getType() . '-' . $event->getId() );
				}
				\MWEchoEmailBatch::addToQueue( $user->getId(), $event->getId(), $priority, $bundleHash );

				return true;
			}

			// instant email notification
			$toAddress = MailAddress::newFromUser( $user );
			$fromAddress = new MailAddress(
				$wgPasswordSender,
				wfMessage( 'emailsender' )->inContentLanguage()->text()
			);
			$replyAddress = new MailAddress( $wgNoReplyAddress );
			// Since we are sending a single email, should set the bundle hash to null
			// if it is set with a value from somewhere else
			$event->setBundleHash( null );
			$email = self::generateEmail( $event, $user );
			if ( !$email ) {
				return false;
			}

			$subject = $email['subject'];
			$body = $email['body'];
			$options = [ 'replyTo' => $replyAddress ];

			\UserMailer::send( $toAddress, $fromAddress, $subject, $body, $options );
			\MWEchoEventLogging::logSchemaEchoMail( $user, 'single' );
		}

		return true;
	}

	/**
	 * @param \EchoEvent $event
	 * @param User $user
	 * @return bool|array An array of 'subject' and 'body', or false if things went wrong
	 */
	private static function generateEmail( \EchoEvent $event, \User $user ) {
		$emailFormat = \MWEchoNotifUser::newFromUser( $user )->getEmailFormat();
		$services = MediaWikiServices::getInstance();
		$lang = $services->getLanguageFactory()->getLanguage(
			$services->getUserOptionsLookup()->getOption( $user, 'language' )
		);

		$factory = $services->getService( 'BSEchoConnectorFormatterFactory' );
		$textFormatter = $factory->getForFormat( \EchoEmailFormat::PLAIN_TEXT, true, [ $user, $lang ] );
		$htmlFormatter = $factory->getForFormat( \EchoEmailFormat::HTML, true, [ $user, $lang ] );

		$content = $textFormatter->format( $event );
		if ( !$content ) {
			return false;
		}

		if ( $emailFormat === \EchoEmailFormat::HTML ) {
			$htmlContent = $htmlFormatter->format( $event );
			$multipartBody = [
				'text' => $content['body'],
				'html' => $htmlContent['body']
			];
			$content['body'] = $multipartBody;
		}

		return $content;
	}
}
