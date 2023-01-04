local spy = require 'spy'
local carp = spy.newCarp()
local cluck = spy.newCluck()
local croak = spy.newCroak()
local confess = spy.newConfess()

local h = {}

function h.carp()
	carp 'carp carp carp'
end

function h.cluck()
	cluck 'cluck cluck cluck'
end

function h.croak()
	croak 'croak croak croak'
end

function h.confess()
	confess "confess confess confess"
end

return h
