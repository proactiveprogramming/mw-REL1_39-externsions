<?php
if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**
 * Extension to show testimonials
 *
 * @file
 * @author Nischay Nahata <nischayn22@gmail.com>
 * @ingroup Extensions
 * @licence GNU GPL v3 or later
 */

define( 'Testimonials_VERSION', '0.1' );

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Testimonials',
	'version' => Testimonials_VERSION,
	'author' => array(
		'[http://www.mediawiki.org/wiki/User:Nischayn22 Nischay Nahata]',
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Testimonials',
	'descriptionmsg' => 'testimonials-desc'
);

// Autoloading classes
$wgAutoloadClasses['TestimonialsHooks'] = __DIR__ . '/Testimonials.hooks.php';

// Hooks
$wgHooks['ParserFirstCallInit'][] = 'addTestimonialResources';

// Hook our callback function into the parser
function addTestimonialResources( Parser $parser ) {
	$parser->setHook( 'addTestimonialResources', 'TestimonialsHooks::addTestimonialResourcesRender' );
	return true;
}


// i18n messages
$wgExtensionMessagesFiles['Testimonials'] = __DIR__ . '/Testimonials.i18n.php';


$wgResourceModules['ext.Testimonials.pageview'] = array(
	'styles' => array( 'css/reset.css', 'css/style.css' ),
	'scripts' => array( 'js/modernizr.js', 'js/jquery.flexslider-min.js', 'js/main.js'),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Testimonials',
	'position' => 'bottom', // available since r85616
);
