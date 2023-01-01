# Mediawiki 'slideshow' extension

[![Build Status Image](https://github.com/PascalNoisette/mediawiki-extensions-Slides/workflows/Automated%20code%20checks/badge.svg)](https://github.com/PascalNoisette/mediawiki-extensions-Slides/actions?query=workflow%3A%22Automated+code+checks%22)

Adds a "Slideshow" action to turn any wikipage into a slideshow. Each level 1 sections are turned into a slide.

![New action in tab area](doc/tab_area.png?raw=true "New action in tab area")

Formating and other Mediawiki extensions should work and they will be embeded in slides (tested on tables, SyntaxHighlight_GeSHi, SimpleMathJax, DrawioEditor, or Score).

## Installation ##

* Clone project or download latest release 
* Copy it into your extension folder as ./extensions/Slides
* Enable the extension by adding the folowing lines to your LocalSettings.php configuration file :

```
// Enable Slides for MediaWiki 1.34 and later
wfLoadExtension( 'Slides' );
```

## Dependencies

This project is using the library from https://revealjs.com/, the html presentation framework created by Hakim El Hattab and contributors released under MIT License at version 4.1.0.

```
	"dependencies": {
		"reveal.js": "^4.1.0"
	}
```

## Developpment ##

This project was initialized from the mediawiki extensions BoilerPlate https://phabricator.wikimedia.org/diffusion/EBOP/ 

1. install nodejs, npm, and PHP composer
2. change to the extension's directory
3. `npm install`
4. `composer install`

Once set up, running `npm test` and `composer test` will run automated code checks.

If the extension is loaded by LocalSettings.php with the php instruction `wfLoadExtension( 'Slides' )`, the mediawiki software will parse the extension.json file. The node "Hooks.SkinTemplateNavigation" trigger a side effect described in includes/SlidesTab.php : it adds a new "Slideshow" button in the tab area linking to the current page, but with the additional query parameter `?action=` set to "slide".

The query parameter triggers the controller that is registered for this action. That is why in extension.json file, the node "Actions.slide" point to the file includes/SlidesAction.php. There I render the template includes/SlideShow.phtml.

Css and javascript are managed by the mediawiki software for performance purpuses, they are not included manually. They are declared in the extension.json file in "ResourceModules.ext.slides" and the files referenced in this node are included to the merged css and js by the hook declared in the extension.json file in "Hooks.BeforePageDisplay".


## Related Works ##

I made this extension because I came accross the project https://github.com/WolfgangFahl/S5SlideShow.git which is unfortunatly not compatible with my mediwiki version. It was a much more flexible extension and allow custom keywords and metadata to generate a slideshow.
