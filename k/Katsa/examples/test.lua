--- This is a minimal example.
-- @module Test

local spy = require 'spy'
local carp = spy.newCarp()
local cluck = spy.newCluck()
local croak = spy.newCroak()
local confess = spy.newConfess()

local h = {}

function h.carp()
	carp 'this is "carp"'
end

function h.cluck()
	cluck 'this is "cluck"'
end

function h.croak()
	croak 'this is "croak"'
end

function h.confess()
	confess 'this is "confess"'
end

return h
