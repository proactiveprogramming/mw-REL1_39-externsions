# SitckyTOC

Makes the TOC float to a page side and act as a sticky panel

# Requirements:

* MediaWiki 1.35+
* [Chameleon](https://github.com/ProfessionalWiki/chameleon) skin 2+

# Setup

* Clone the extension into the `extensions` folder
* Add `wfLoadExtension('StickyTOC');` to the bottom of `LocalSettings.php`

# Configuration

* `$wgStickyTOCFloat` - allows controlling to which side the TOC will float. By
default it's set to `left`, but it can be changed to `right` or `aside`:
	* `left` - TOC floats to the left
	* `right` - TOC floats to the right
	* `aside` - TOC jumps to the left and acts as a column, shifting the page contents
	to the right
* `$wgStickyTOCNavbar` - If set to `true`m will force the TOC to treat the current layout
navbar as being sticky. By default this is set to `false`. There is no need to change
  this for the default Chameleon `stickyhead.xml` layout, only for custom ones.

Created for the National Gallery of Art HEALD wiki.

