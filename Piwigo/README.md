
# mw-Piwigo
This is a mediawiki extension that displays a gallery of images extracted from a Piwigo setup

## What this does

This extension adds a ```<piwigo />``` keyword and a ```{{#piwigo}}``` parser function that show a gallery in a page. The keyword can contain the same kind of parameters as Piwigo's URL (category, tags, ...):

### Search parameter ###
You can search for photos, including with a complex search, using the search parameter. See [here](https://fr.piwigo.org/doc/doku.php?id=utiliser:utilisation:fonctionnalites:recherche_rapide) for the syntax.

That would give for example: ```{{#piwigo:search = tag:1-flowers red}}``` 

### Tags parameter ###
You can select all photos for a given tag by using: ```{{#piwigo:tags=1-tagname}}``` or ```<piwigo tags="1-tagname"/>```  or ```<piwigo tags="1"/>``` (only the tag id is relevant).

It is also possible to target more than one tag with the parser function: ```{{#piwigo: tags=3 | tags=4 | count=5 }}``` (not that for that you'll need to use the parser function and not the keyword - ie. ```<piwigo  tags=3 | tags=4 | count=5>``` will only show images from tag 4)

If the ```tags``` parameter is set, the ```category``` is ignored.

### Category parameter ###
The category parameter is used to select photos from an album. You cannot select both an album and a tag (both are mutually exclusive): ```{{#piwigo: category = 5}}```

### Count parameter ###
You can use the ```count``` parameter to limit the number of results: ```{{#piwigo: category = 5 | count = 10}}```  or ```<piwigo tags="1" count = 4/>```

### Site parameter ###
You may want to look up photos from another setup of Piwigo than the one defined by default in LocalSettings.php. In such case, simply add the site parameter:
```{{#piwigo: category = 5 | count = 10 | site = https://yourpiwigosetup.com }}```

## Performance ##
The images are loaded in JS which means that the page is effectively cached as any wiki page, and checks for new images only at display time.

The images are shown using this JS gallery: https://tutorialzine.com/2017/02/freebie-4-bootstrap-galleries (the four layouts are available)

## Configuration

You will need to store the extension in ```extensions/Piwigo```, then add the following to your LocalSettings.php:

```
wfLoadExtension( 'Piwigo' );
$wgPiwigoURL = 'https://somegallery.piwigo.fr';
$wgPiwigoGalleryLayout = 'fluid'; // one of the four: fluid (default), grid, thumbnails, clean
```

