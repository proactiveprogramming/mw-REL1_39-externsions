# BlueSpiceProDistributionConnector

## Installation
Execute

    composer require bluespice/prodistributionconnector dev-REL1_35
within MediaWiki root or add `bluespice/prodistributionconnector` to the
`composer.json` file of your project

## Activation
Add

    wfLoadExtension( 'BlueSpiceProDistributionConnector' );
to your `LocalSettings.php` or the appropriate `settings.d/` file.