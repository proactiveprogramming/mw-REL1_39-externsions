# Mediawiki-Opauth
Opauth integration module for Mediawiki engine. 
This extension integrates Opauth with 4 predefined strategies ( Facebook, Google, LinkedIn and Twitter ). 
It provides Special Page entrypoint & endpoint for authentication handling via Opaith library and provides hook for external handling.

# Installation

1. Put **Opauth** folder into **extensions** folder
2. Add these lines into **LocalSettings.php**:
```php
require_once "$IP/extensions/Opauth/Opauth.php";
$wgOpauthConfig['security_salt'] = 'YOUR_RANDOM_SALT_STRING';
$wgOpauthConfig['Strategy'] = array(

	'Facebook' => array(
		'app_id' => 'YOUR_APP_ID',
		'app_secret' => 'YOUR_APP_SECRET',
		'scope' => 'public_profile,email'
	),

	'Google' => array(
		'client_id' => 'YOUR_APP_ID',
		'client_secret' => 'YOUR_APP_SECRET'
	),

	'Twitter' => array(
		'key' => 'YOUR_APP_ID',
		'secret' => 'YOUR_APP_SECRET'
	),

	'LinkedIn' => array(
		'api_key' => 'YOUR_APP_ID',
		'secret_key' => 'YOUR_APP_SECRET'
	)

);
```
3. For more configuration information please see https://github.com/opauth/opauth
5. Use code below to fetch social-auth url:
```php
OpauthHelper::getLoginLink('facebook'); // where 'facebook' is provider name
```
4. Create your own extension which listen **OpauthUserAuthorized** hook. This hook will be called with parameters listed below in sample callback function:
```php
public static function onOpauthUserAuthorized( $provider, $uid, $info, $raw ) { ... }
```
