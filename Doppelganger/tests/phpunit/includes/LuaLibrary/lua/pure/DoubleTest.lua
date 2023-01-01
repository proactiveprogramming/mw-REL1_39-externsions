--- Tests for the double module.

local testframework = require 'Module:TestFramework'

local Double = require 'double'
assert( Double )

local function testExists()
	return type( Double )
end

local function testCondition( name, ... )
	local results = {}
	for i,v in ipairs( { ... } ) do
		local obj = Double.new( unpack( v[1] ) )
		--results[i] = obj[name]( obj, unpack( v[2] ) )
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
		name = 'Is empty',
		func = testCondition,
		args = { 'isEmpty',
			{ {}, {} },
			{ { nil }, {} },
			{ { true }, {} },
			{ { false }, {} },
			{ { { 1 } }, {} },
			{ { { 1 }, { 2 }, { 3 } }, {} },
		},
		expect = {
			{ true },
			{ true },
			{ false },
			{ false },
			{ false },
			{ false },
		}
	},
}

return testframework.getTestProvider( tests )
