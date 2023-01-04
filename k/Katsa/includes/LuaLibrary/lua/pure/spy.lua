--- Class for spies.
-- @module Spy

-- pure libs
local libUtil = require 'libraryUtil'

-- lookup
local checkType = libUtil.checkType
local makeCheckSelfFunction = libUtil.makeCheckSelfFunction

-- @var lib var
local spy = {}

-- @var structure used as metatable for spy
local meta = {}

--- Instance is callable.
-- This call may not return at all.
-- Redirects to objects eval method.
-- @function spy:__call
-- @tparam string text for a last minute message
-- @treturn self
function meta:__call( text )
	return self:eval( text )
end

--- Internal creator function.
-- @local
-- @tparam varargs ... functions kept as callbacks, everything else as data for callback
-- @treturn self
local function makeSpy( ... )

	local obj = setmetatable( {}, meta )

	--- Check whether method is part of self.
	-- @local
	-- @function checkSelf
	-- @raise if called from a method not part of self
	local checkSelf = makeCheckSelfFunction( 'spy', 'obj', obj, 'spy object' )

	-- keep in closure
	local _callbacks = {}
	local _data = {}

	-- dispatch on type
	for _,v in ipairs( { ... } ) do
		if type( v ) == 'function' then
			table.insert( _callbacks, v )
		else
			table.insert( _data, v )
		end
	end

	--- Evaluate graph.
	-- @function spy:eval
	-- @tparam nil|string text to be reported on evaluation of the compute grap
	-- @treturn nil|string zero or more strings
	function obj:eval( text )
		checkSelf( self, 'eval' )
		checkType( 'spy:eval', 1, text, 'string', true )
		local t = { text }
		for _,v in ipairs( _callbacks ) do
			v( t, unpack( _data ) )
		end
		return unpack( t )
	end

	--- Add a callback.
	-- The function is expected to have a signature on the form `function( table, ... )`
	-- where the table is a list of strings. It may change the content of the table.
	-- @function spy:addCallback
	-- @tparam function func to be registered on the compute graph.
	-- @tparam nil|number index to inject the callbak
	-- @treturn self
	function obj:addCallback( func, index )
		checkSelf( self, 'eval' )
		checkType( 'spy:addCallback', 1, func, 'function', false )
		checkType( 'spy:addCallback', 2, index, 'number', true )
		if index then
			table.insert( _callbacks, index, func )
		else
			table.insert( _callbacks, func )
		end
		return self
	end

	--- Log a traceback.
	-- @function spy:log
	-- @tparam nil|string text to be reported on evaluation of the compute grap
	-- @tparam nil|number level to start reporting
	-- @treturn self
	function obj:log( text, level )
		checkSelf( self, 'log' )
		checkType( 'spy:log', 1, text, 'string', true )
		checkType( 'spy:log', 2, level, 'number', true )
		level = level or 4
		local f = function( t )
			local tmp = { text }
			for _,v in ipairs( t ) do
				table.insert( tmp, v )
			end
			if level == 0 then
				mw.log( table.concat( tmp, ': ' ) )
			else
				mw.log( debug.traceback( table.concat( tmp, ': ' ), level ) )
			end
		end
		table.insert( _callbacks, f )
		return self
	end

	--- Rise an exception.
	-- The Scribunto implementation makes it difficult to do this correctly.
	-- @function spy:raise
	-- @tparam nil|string text to be reported on evaluation of the compute grap
	-- @tparam nil|number level to start reporting
	-- @treturn self
	function obj:raise( text, level )
		checkSelf( self, 'raise' )
		checkType( 'spy:raise', 1, text, 'string', true )
		checkType( 'spy:raise', 2, level, 'number', true )
		level = level or 4
		local f = function( t )
			local tmp = { text }
			for _,v in ipairs( t ) do
				table.insert( tmp, v )
			end
			error( table.concat( tmp, ': ' ), level )
		end
		table.insert( _callbacks, f )
		return self
	end

	return obj
end

--- Create a new instance.
-- @function spy.new
-- @tparam vararg ... arguments to be passed on
-- @treturn self
function spy.new( ... )
	return makeSpy( ... )
end

--- Create a new carp instance.
-- This convenience function register a log callback.
-- @function spy.newCarp
-- @tparam vararg ... arguments to be passed on
-- @treturn self
function spy.newCarp( ... )
	local obj = makeSpy( ... )
	obj:log( mw.message.new( 'katsa-carp-final' ):plain(), 0 )
	return obj
end

--- Create a new cluck instance.
-- This convenience function register a log callback.
-- @function spy.newCluck
-- @tparam vararg ... arguments to be passed on
-- @treturn self
function spy.newCluck( ... )
	local obj = makeSpy( ... )
	obj:log( mw.message.new( 'katsa-cluck-final' ):plain(), 4 )
	return obj
end

--- Create a new croak instance.
-- This convenience function register a raise callback.
-- The Scribunto implementation makes it difficult to do this correctly.
-- @function spy.newCroak
-- @raise unconditionally
-- @tparam vararg ... arguments to be passed on
-- @treturn self
function spy.newCroak( ... )
	local obj = makeSpy( ... )
	obj:raise( mw.message.new( 'katsa-croak-final' ):plain(), 0 )
	return obj
end

--- Create a new confess instance.
-- This convenience function register a raise callback.
-- The Scribunto implementation makes it difficult to do this correctly.
-- @function spy.newConfess
-- @raise unconditionally
-- @tparam vararg ... arguments to be passed on
-- @treturn self
function spy.newConfess( ... )
	local obj = makeSpy( ... )
	obj:raise( mw.message.new( 'katsa-confess-final' ):plain(), 4 )
	return obj
end

-- Return the final lib
return spy
