(function( $ ) {
	$(document).ready(function () { //jquery

		if ( $(window).width() < 981 ) {
			var splash_data = $('script[type="text/json"]#ba_splash_ad').text();
			if ( splash_data == "" ) {
				return;
			}
			splash_data = $.parseJSON(splash_data);
			$( 'body' ).append( '<a id="splashscreen" target="_blank" href="'+ splash_data.url +'"><img style="width:100%;height:100%;" src="'+ splash_data.img +'"></img></a>' );
			setTimeout( function () {
				$( '#splashscreen' ).remove();
			}, 3000 );
		}

	});
} )( jQuery );
