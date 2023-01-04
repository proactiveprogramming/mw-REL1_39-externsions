# Extension:PlaceNewSection

This extension introduces two magic words, `__ADDNEWSECTIONBELOW__` and
`__ADDNEWSECTIONABOVE__`, which control placement of new Talk: page sections. 

This is an updated version of the single-file PHP script currently found
at [Extension:PlaceNewSection][1] to support MediaWiki 1.3x and above.

## Progress

So far:

* it's been updated to use an `extension.json` and `ExtensionMessageFiles`
  within that to register the extension hooks and magic words
* it's been reimplemented as an auto-loaded `MediaWiki\Extension\PlaceNewSection`
  class
* explict GPL v2 license headers have been added to the PHP source files
* it's undergone some manual testing to make sure it works with MediaWiki 1.31

## Authors

| Name         | Contact                  b| Why
| ----         | -------                  | ---
| Nx           | [User:Nx][2]             | Original author
| Kevin Ernst  | ernstki -at- mail.uc.edu | Updated to work with MW ≥ 1.33

[1]: https://www.mediawiki.org/wiki/Extension:PlaceNewSection
[2]: https://en.wikipedia.org/wiki/User:Nx
