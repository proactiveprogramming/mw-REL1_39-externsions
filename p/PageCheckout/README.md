# PageCheckout

## Installation
Execute

    composer require hallowelt/pagecheckout dev-REL1_31
within MediaWiki root or add `hallowelt/pagecheckout` to the
`composer.json` file of your project

## Activation
Add

    wfLoadExtension( 'PageCheckout' );
to your `LocalSettings.php` or the appropriate `settings.d/` file.

## Activities

- PageCheckout (page_checkout)
Properties:
  - user => user for which to check the page out for
  - pageId => optional, ID of the page to checkout
  - pagename => optional, used if no `pageId` is specified, name of the page to checkout
  - force => options, 1|0. If true, will remove any existing checkout and create a new checkout
	
If neither `pageId` nor `pagename` is specified, global context page of the workflow will be used

- Page Checkin (page_checkin)
Properties:
	- user => user which has the checkout
	- pageId => optional, ID of the page to checkin
	- pagename => optional, used if no `pageId` is specified, name of the page to checkin
