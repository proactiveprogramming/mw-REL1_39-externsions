var Vue = require( 'vue' ),
    Tiles = require( './Tiles.vue' ),
	taskSearch = OO.ui.infuse( '#taskSearch' ),
	itemData = [],
	dataSearch = [],
	searchInput = [],
	$allRLModules = [];

// eslint-disable-next-line no-new
var vm = new Vue( {
    el: '#unifiedTaskOverview-tiles',
    render: function ( h ) {
        return h( Tiles );
    }

} );

taskSearch.on( 'change', function ( value ) {
	getSearchResults( value.toLowerCase() );
} );

var dfd = $.Deferred();
$.ajax( {
	url: mw.util.wikiScript( 'rest' ) + '/unifiedtaskoverview/list',
	contentType: "application/json",
	dataType: 'json'
} ).done( function( response ) {
	if ( response.length > 0 ) {
		loadData( response );
	}
	else {
		vm.$children[ 0 ].noTaskDesc = true;
	}
} ).fail ( function ( jgXHR, type, status ) {
	if ( type === 'error' ) {
		return dfd.reject( {
			error: jgXHR.responseJSON || jgXHR.responseText
		} );
	}
	return dfd.reject( { type: type, status: status } );
} );

function loadData( response) {
	loadRLModules( response );
	itemData = sortItems( response );
	setDataForSearch( itemData );
	setVisibility( itemData );
	setItems( itemData );
}

function sortItems( data ) {
	data = data.sort( ( a, b ) => a.sortkey - b.sortkey );
	return data;
}

// get RLModules from data
function loadRLModules ( data ) {
	for ( var x = 0; x < data.length; x++ ) {
		data[x][ 'RLmodules' ].forEach( function ( item ) {
			if ($allRLModules.length === 0 ) {
				$allRLModules.push( item );
			} else {
				$allRLModules.forEach( function ( module ) {
					if ( module !== item ) {
						$allRLModules.push( item );
					}
				});
			}
		});
	}
	mw.loader.using( $allRLModules );
}

function setItems ( items ) {
	vm.$children[ 0 ].items = items;
}

function setDataForSearch ( items ){
	items.forEach( function ( item )  {
		dataSearch.push( item.type.toLowerCase() + " "
			+ item.header.toLowerCase() + " " + item.subheader.toLowerCase() + " "
			+ item.body.toLowerCase() );
	});
}

function setVisibility ( items ) {
	items.forEach( function ( item )  {
		item.isVisible = true;
	});
}

function getSearchResults ( searchStr ) {
	found = 1;
	if ( searchStr !== '' && searchStr !== false ) {
		if ( searchStr.search( ' ' ) !== -1 ) {
			searchInput = searchStr.split( " " );
		} else  {
			searchInput[ 0 ] = searchStr;
		}

		for ( let x = 0; x < dataSearch.length; x++ ) {
			for (let y = 0; y < searchInput.length; y++ ) {
				if ( dataSearch[x].search( searchInput[y] )  === -1 ) {
					found = 0;
				}
			}
			if ( found === 0 ) {
				itemData[x].isVisible = false;
			} else {
				itemData[x].isVisible = true;
			}
			found = 1;
		}
	}
	else {
		itemData.forEach( function ( item )  {
			item.isVisible = true;
			searchInput = [];
		});
	}
	setItems( itemData );
}
