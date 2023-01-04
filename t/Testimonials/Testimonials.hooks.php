<?php

class TestimonialsHooks{

	public static function addTestimonialResourcesRender( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgOut;
		$wgOut->addModules( 'ext.Testimonials.pageview' );

		return '';
	}


}