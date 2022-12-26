<?php

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class InspectNotifications extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'BlueSpiceEchoConnector' );
	}

	public function execute() {
		$this->outputStart();
		$registeredNotifications = $this->getConfig()->get( 'EchoNotifications' );
		ksort( $registeredNotifications );
		foreach ( $registeredNotifications as $notifKey => $notifConf ) {
			$this->outputRow( $notifKey, $notifConf );
		}
		$this->outputEnd();
	}

	private function outputStart() {
		$this->output( "{| class=\"wikitable sortable\"\n" );
		$this->output( "! Key\n" );
		$this->output( "! Is BlueSpice?\n" );
		$this->output( "! Category\n" );
		$this->output( "! Group\n" );
		$this->output( "! Section\n" );
		$this->output( "! Presentation model\n" );
		$this->output( "! Bundle.Web\n" );
		$this->output( "! Bundle.Mail\n" );
		$this->output( "! Bundle.Expandable\n" );
		$this->output( "! Has user locators?\n" );
		$this->output( "! Can notify agent?\n" );
		$this->output( "! Message\n" );
		$this->output( "! When emitted?\n" );
		$this->output( "|-\n" );
	}

	private function outputEnd() {
		$this->output( "|}\n" );
	}

	/**
	 * @param string $notifKey
	 * @param string $notifConf
	 * @return void
	 */
	private function outputRow( $notifKey, $notifConf ) {
		$isBlueSpice = strpos( $notifKey, 'bs-' ) !== false;
		$isBlueSpiceText = $isBlueSpice ? 'Yes' : 'No';

		$categoryText = $notifConf['category'];
		$groupText = $notifConf['group'];
		$sectionText = $notifConf['section'];
		$presentationModelText = $notifConf['presentation-model'];

		$hasUserLocators = isset( $notifConf['user-locators'] );
		$hasUserLocatorsText = $hasUserLocators ? 'Yes' : 'No';

		$canNotifyAgent = $notifConf['canNotifyAgent'] ?? false;
		$canNotifyAgentText = $canNotifyAgent ? 'Yes' : 'No';

		$bundleWeb = $notifConf['bundle']['web'] ?? false;
		$bundleMail = $notifConf['bundle']['mail'] ?? false;
		$bundleExpandable = $notifConf['bundle']['expandable'] ?? false;

		$bundleWebText = $bundleWeb ? 'Yes' : 'No';
		$bundleMailText = $bundleMail ? 'Yes' : 'No';
		$bundleExpandableText = $bundleExpandable ? 'Yes' : 'No';

		$messageKeys = [
			// BlueSpice
			"$notifKey-subject",
			"$notifKey-email-body",
			"$notifKey-web-body",

			// Echo
			"notification-header-$notifKey",
			"notification-$notifKey-email-subject",
			"notification-$notifKey-email-subject2",
			"notification-body-$notifKey"
		];
		$languages = [ 'de', 'de-formal', 'en' ];
		$messageText = '';
		foreach ( $languages as $language ) {
			$messageText .= "\n'''$language'''\n\n";
			foreach ( $messageKeys as $messageKey ) {
				$currentMessage = wfMessage( $messageKey )->inLanguage( $language );
				if ( !$currentMessage->exists() ) {
					continue;
				}
				$currentMessageText = $currentMessage->plain();

				$messageText .= "\n'''-> <code>$messageKey</code>''' \n";
				$messageText .= "<pre><nowiki>$currentMessageText</nowiki></pre> \n";
			}
		}

		$this->output( "| <code>$notifKey</code>\n" );
		$this->output( "| $isBlueSpiceText\n" );
		$this->output( "| <code>$categoryText</code>\n" );
		$this->output( "| <code>$groupText</code>\n" );
		$this->output( "| <code>$sectionText</code>\n" );
		$this->output( "| <code>$presentationModelText</code>\n" );
		$this->output( "| $bundleWebText\n" );
		$this->output( "| $bundleMailText\n" );
		$this->output( "| $bundleExpandableText\n" );
		$this->output( "| $hasUserLocatorsText\n" );
		$this->output( "| $canNotifyAgentText\n" );
		$this->output( "|\n$messageText\n" );
		$this->output( "| ???\n" );
		$this->output( "|-\n" );
	}
}

$maintClass = InspectNotifications::class;
require_once RUN_MAINTENANCE_IF_MAIN;
