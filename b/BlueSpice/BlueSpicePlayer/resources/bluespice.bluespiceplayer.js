(function( mw, $, d, undefined ) {
    $( function() {
      $( '.bs-video' ).each( function() {
          new Plyr( this );
      } );
    } );
})( mediaWiki, jQuery, document );