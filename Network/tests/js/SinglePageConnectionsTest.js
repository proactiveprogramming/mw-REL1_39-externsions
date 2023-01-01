( function (ApiConnectionsBuilder, stub) {
	QUnit.module( 'ext.network.SinglePageConnectionsTest' );


	QUnit.test( 'Pages/nodes integration test', function ( assert ) {
		let builder = new ApiConnectionsBuilder();
		let connections = builder.connectionsFromApiResponses(stub.Cats);

		// console.log(JSON.stringify(connections.pages, null, 4));

		assert.deepEqual(
			connections.pages,
			[
				{
					"title": "404",
				},
				{
					"title": "Cats",
				},
				{
					"title": "Kittens",
				},
				{
					"title": "Main Page",
				},
				{
					"title": "Nyan Cat",
				},
				{
					"title": "Talk:Main Page",
				}
			]
		);
	} );

	QUnit.test( 'Links/edges integration test', function ( assert ) {
		let builder = new ApiConnectionsBuilder();
		let connections = builder.connectionsFromApiResponses(stub.Cats);

		// console.log(JSON.stringify(connections.links, null, 4));

		assert.deepEqual(
			connections.links,
			[
				{
					"from": "Cats",
					"to": "404"
				},
				{
					"from": "Cats",
					"to": "Kittens"
				},
				{
					"from": "Cats",
					"to": "Main Page"
				},
				{
					"from": "Cats",
					"to": "Nyan Cat"
				},
				{
					"from": "Main Page",
					"to": "Cats"
				},
				{
					"from": "Talk:Main Page",
					"to": "Cats"
				}
			]
		);
	} );

}(window.NetworkExtension.ApiConnectionsBuilder, module.stub) );
