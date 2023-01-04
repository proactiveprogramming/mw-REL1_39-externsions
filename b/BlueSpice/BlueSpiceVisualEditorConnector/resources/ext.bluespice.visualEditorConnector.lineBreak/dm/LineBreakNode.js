bs.util.registerNamespace( 'bs.vec.dm' );

bs.vec.dm.LineBreakNode = function () {
	// Parent constructor
	bs.vec.dm.LineBreakNode .super.apply( this, arguments );
};

/* Inheritance */
OO.inheritClass( bs.vec.dm.LineBreakNode, ve.dm.BreakNode );


bs.vec.dm.LineBreakNode.static.toDomElements = function ( data, doc ) {
	// Its impossible to create a <br> element in DOM directly,
	// it must be wrapped in another element
	var el = doc.createElement('span');
	el.innerHTML = '<br>';
	return [ el ];
};

/* Registration */
ve.dm.modelRegistry.register( bs.vec.dm.LineBreakNode  );
