--- Class for doubles.
-- @module Double

-- pure libs
local libUtil = require 'libraryUtil'

-- lookup
local checkType = libUtil.checkType
local makeCheckSelfFunction = libUtil.makeCheckSelfFunction

-- @var lib var
local double = {}

-- @var structure used as metatable for spy
local meta = {}

--- Get arguments for a functionlike call.
-- @tparam vararg ... pass on to dispatch
-- @treturn self
function meta:__call( ... ) -- luacheck: no self
	return self:dispatch( ... )
end

--- Internal creator function.
-- @local
-- @tparam varargs ... functions kept as callbacks, everything else as data for callback
-- @treturn self
local function makeDouble( ... )

	local obj = setmetatable( {}, meta )

	--- Check whether method is part of self.
	-- @local
	-- @function checkSelf
	-- @raise if called from a method not part of self
	local checkSelf = makeCheckSelfFunction( 'double', 'obj', obj, 'double object' )

	-- keep in closure
	local _list = {}
	local _name = nil
	local _level = nil
	local _onEmpty = nil
	local _stub = nil

	for i,v in ipairs( { ... } ) do
		libUtil.checkTypeMulti( 'double:dispatch', i, v, { 'table', 'boolean', 'number', 'string', 'function' } )
		local tpe = type( v )
		if tpe == 'table' then
			obj:insert( v )
		elseif tpe == 'boolean' then
			obj:insert( v )
		elseif tpe == 'number' then
			obj:setLevel( v )
		elseif tpe == 'string' then
			obj:setName( v )
		elseif tpe == 'function' then
			obj:setOnEmpty( v )
		end
	end

	--- Set identifying name
	-- @raise on wrong arguments
	-- @tparam nil|string name used for error reports
	-- @treturn self
	function obj:setName( name )
		checkSelf( self, 'setName' )
		libUtil.checkType( 'double:setLevel', 1, name, 'string', true )
		_name = name
		return self
	end

	--- Has set name.
	-- @treturn boolean
	function obj:hasName()
		checkSelf( self, 'hasName' )
		return not not _name
	end

	--- Set stack level.
	-- @raise on wrong arguments
	-- @tparam nil|number level where to start reporting
	-- @treturn self
	function obj:setLevel( level )
		checkSelf( self, 'setLevel' )
		libUtil.checkType( 'double:setLevel', 1, level, 'number', true )
		_level = level
		return self
	end

	--- Has set level.
	-- @treturn boolean
	function obj:hasLevel()
		checkSelf( self, 'hasLevel' )
		return not not _level
	end

	--- Set on empty fallback.
	-- @raise on wrong arguments
	-- @tparam nil|function func fallback to be used in place of precomputed values
	-- @treturn self
	function obj:setOnEmpty( func )
		checkSelf( self, 'setOnEmpty' )
		libUtil.checkType( 'double:setOnEmpty', 1, func, 'function', true )
		_onEmpty = func
		return self
	end

	--- Has set empty fallback.
	-- @treturn boolean
	function obj:hasOnEmpty()
		checkSelf( self, 'hasOnEmpty' )
		return not not _onEmpty
	end

	--- Is the list of values empty.
	-- Note that the internal structure is non-empty even if a nil
	-- is shifted into the values list.
	-- @treturn boolean whether the internal values list has length zero
	function obj:isEmpty()
		checkSelf( self, 'isEmpty' )
		return #_list == 0
	end

	--- What is the depth of the internal values list.
	-- Note that the internal structure has a depth even if a nil
	-- is shifted into the values list.
	-- @treturn number how deep is the internal structure
	function obj:depth()
		checkSelf( self, 'depth' )
		return _list:depth()
	end

	--- Get the layout of the values list.
	-- This method is used for testing to inspect which types of objects exists in the values list.
	-- @treturn table description of the internal structure
	function obj:layout()
		checkSelf( self, 'layout' )
		return _list:layout()
	end

	--- Insert value(s) to the list of values.
	-- @treturn self facilitate chaining
	function obj:insert( ... )
		checkSelf( self, 'insert' )
		_list:unshift( ... )
		return self
	end

	--- Remove value from the list of values.
	-- @treturn any item that can be put on the internal structure
	function obj:remove( num )
		checkSelf( self, 'remove' )
		libUtil.checkType( 'double:remove', 1, num, 'number', true )
		num = num or 1
		return _list:shift( num )
	end

	--- Return a stub function for the object.
	-- Each call to the returned closure will remove one case of values from the internal structure.
	-- @treturn closure
	function obj:stub()
		checkSelf( self, 'stub' )
		if not _stub then
			_stub = function( ... )
				local item = self:remove()
				local itemType = type( item )
				-- precomputed values
				if itemType == 'table' then
					return unpack( item )
				end
				-- conditional redirect
				if itemType == 'boolean' then
					if item == true then
						if not _onEmpty then
							error( mw.message.new( 'pickle-stub-no-fallback', _name or 'double' ):plain(), 0 )
						end
						return _onEmpty( ... )
					end
					error( mw.message.new( 'pickle-stub-no-more-frames', _name or 'double' ):plain(), 0 )
				end
				-- unconditional redirect
				if self._onEmpty then
					return _onEmpty( ... )
				end
				-- failed
				error( mw.message.new( 'pickle-stub-no-more-frames', _name or 'double' ):plain(), 0 )

			end
		end
		return _stub
	end

	--- Has set stub function.
	-- treturn boolean
	function obj:hasStub()
		checkSelf( self, 'hasStub' )
		return not not self._stub
	end

	--- Export a list of all the contents.
	-- @treturn table list of values
	function obj:export()
		checkSelf( self, 'export' )
		local t = {}
		for i,v in ipairs( self._bag ) do
			t[i] = v
		end
		return unpack( t )
	end

	--- Flush all the contents.
	-- Note that this clears the internal storage.
	-- @treturn table list of values
	function obj:flush()
		checkSelf( self, 'flush' )
		local t = { self:export() }
		self._bag = {}
		return unpack( t )
	end

	return obj
end

--- Create a new instance.
-- @function double.new
-- @tparam vararg ... arguments to be passed on
-- @treturn self
function double.new( ... )
	local obj = makeDouble( ... )
	--obj:dispatch()
	return obj
end

return double