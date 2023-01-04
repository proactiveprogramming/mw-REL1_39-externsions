local timeconvert = {}
local php

function timeconvert.setupInterface(options)
   -- Remove setup function.
   timeconvert.setupInterface = nil

   -- Copy the PHP callbacks to a local variable, and remove the global one.
   php = mw_interface
   mw_interface = nil

   -- Do any other setup here.
   timeconvert.timeconvert = php.timeconvert

   -- Install into the mw global.
   mw = mw or {}
   mw.ext = mw.ext or {}
   mw.ext.timeconvert = timeconvert

   -- Indicate that we're loaded.
   package.loaded["mw.ext.timeconvert"] = timeconvert
end

return timeconvert
