--- Tests for the spy module.

local testframework = require 'Module:TestFramework'
assert( testframework )

local Spy = require 'spy'
assert( Spy )

local function testExists()
	return type( Spy )
end

local function testInstance( obj, ... )
	local keep1 = nil
	local keep2 = nil
	local func = function( tbl, ... )
		keep1 = tbl
		keep2 = { ... }
	end
	obj:addCallback( func )
	local results = {}
	for i,v in ipairs( { ... } ) do
		results[i] = { pcall( function( ... ) return obj( ... ) end, unpack( v ) ), keep1, keep2 }
	end
	return unpack( results )
end

local function testConvenience( spy, ... )
	local results = {}
	for i,v in ipairs( { ... } ) do
		results[i] = { pcall( function( ... ) return { spy( ... ) } end, unpack( v ) ) }
	end
	return unpack( results )
end

local tests = {
	{ -- 1
		name = 'Verify the lib is loaded and exists',
		func = testExists,
		type = 'ToString',
		expect = { 'table' }
	},
	{ -- 2
		name = 'Create without args',
		func = testInstance,
		args = { Spy.new(),
			{},
			{ 'foo' },
		},
		expect = {
			{ true, {}, {} },
			{ true, { 'foo' }, {} },
		}
	},
	{ -- 4
		name = 'Create with "foo" as arg, and two callbacks adding "bar" and "baz"',
		func = testInstance,
		args = { Spy.new()
				:addCallback( function(t) table.insert( t, 'bar' ) end, 1 )
				:addCallback( function(t) table.insert( t, 'baz' ) end, 2 ),
			{},
			{ 'foo' },
		},
		expect = {
			{ true, { 'bar', 'baz' }, {} },
			{ true, { 'foo', 'bar', 'baz' }, {} },
		}
	},
	{ -- 5
		name = 'Create with "foo" as arg, and two callbacks adding "baz" and "bar"',
		func = testInstance,
		args = { Spy.new()
				:addCallback( function(t) table.insert( t, 'bar' ) end, 2 )
				:addCallback( function(t) table.insert( t, 'baz' ) end, 1 ),
			{},
			{ 'foo' },
		},
		expect = {
			{ true, { 'baz', 'bar' }, {} },
			{ true, { 'foo', 'baz', 'bar' }, {} },
		}
	},
	{ -- 6
		name = 'Create with "foo" as arg and "bar" as data',
		func = testInstance,
		args = { Spy.new( 'bar' ),
			{'foo' },
		},
		expect = {
			{ true, { 'foo' }, { 'bar' } },
		}
	},
	{ -- 7
		name = 'Convenience carp',
		func = testConvenience,
		args = { Spy.newCarp(),
			{},
			{ 'foo' },
		},
		expect = {
			{ true, {} },
			{ true, { 'foo' } },
		}
	},
	{ -- 8
		name = 'Convenience cluck',
		func = testConvenience,
		args = { Spy.newCluck(),
			{},
			{ 'foo' },
		},
		expect = {
			{ true, {} },
			{ true, { 'foo' } },
		}
	},
	{ -- 9
		name = 'Convenience croak',
		func = testConvenience,
		args = { Spy.newCroak(),
			{},
			{ 'foo' },
		},
		expect = {
			{ false, 'Croak called' },
			{ false, 'Croak called: foo' },
		}
	},
	{ -- 13
		name = 'Convenience confess',
		func = testConvenience,
		args = { Spy.newConfess(),
			{},
			{ 'foo' },
		},
		expect = {
			{ false, 'Module:SpyTest:30: Confess called' },
			{ false, 'Module:SpyTest:30: Confess called: foo' },
		}
	},
}

return testframework.getTestProvider( tests )
