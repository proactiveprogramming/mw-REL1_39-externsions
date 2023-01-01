project = 'Doppelganger'
title = 'Doppelgänger documentation'
description = 'Assertion framework for Lua embedded within Mediawiki.'
use_markdown_titles = true
format = 'markdown'
dir = 'docs'
--output = 'mw.doppelganger'
-- all = true

readme = {
	-- 'topics/doppelganger.md',
	'topics/vagrant.md',
	-- 'topics/databases.md',
	-- 'topics/delayed.md',
	-- 'topics/hello-world.md',
	-- 'topics/translatewiki.md',
	'topics/vscode.md',
}

examples = {
	'examples'
}

-- new_type( 'macro', 'Macros' )
custom_tags = {
	{
		'nick',
		title = 'Nicknames',
		hidden = false,
		format = nil
	},
}

custom_display_name_handler = function(item, default_handler)
	-- show a label beside functions tagged with @delayed.
	if item.type == 'lfunction' or item.type == 'function' then
		return item.name .. ' ()'
	end
	return default_handler(item)
end
