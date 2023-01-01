bs.util.registerNamespace( 'bs.vec.tool' );

bs.vec.tool.InsertLineBreak = function ( toolGroup, config ) {
	bs.vec.tool.InsertLineBreak.super.call( this, toolGroup, config );
};
OO.inheritClass( bs.vec.tool.InsertLineBreak, ve.ui.FragmentWindowTool );

bs.vec.tool.InsertLineBreak.static.name = 'bsInsertLineBreakTool';
bs.vec.tool.InsertLineBreak.static.group = 'object';
bs.vec.tool.InsertLineBreak.static.icon = 'newline';
bs.vec.tool.InsertLineBreak.static.modelClasses = [ bs.vec.dm.LineBreakNode ];
bs.vec.tool.InsertLineBreak.static.title = OO.ui.deferMsg( 'bs-vec-insert-line-break-title' );

bs.vec.tool.InsertLineBreak.static.commandName = 'bsInsertLineBreak';
ve.ui.toolFactory.register( bs.vec.tool.InsertLineBreak );

ve.ui.commandRegistry.register(
	new ve.ui.Command( 'bsInsertLineBreak', 'content', 'insert', {
		args: [
			[
				{ type: 'break' },
				{ type: '/break' }
			],
			// annotate
			false,
			// collapseToEnd
			true
		],
		supportedSelections: [ 'linear' ]
	} )
);

ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command( 'bsInsertLineBreak', 'content', 'insert', {
		args: [ '<br>', false, true /* collaseToEnd */ ],
		supportedSelections: [ 'linear' ]
	} )
);
