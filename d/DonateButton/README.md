# MediaWiki DonateButton

Die Pflege der MediaWiki-Erweiterung [DonateButton](https://www.mediawiki.org/wiki/Extension:DonateButton) wird von WikiMANNia verwaltet.

The maintenance of the MediaWiki extension [DonateButton](https://www.mediawiki.org/wiki/Extension:DonateButton) is managed by WikiMANNia.

El mantenimiento de la extensión de MediaWiki [DonateButton](https://www.mediawiki.org/wiki/Extension:DonateButton) está gestionado por WikiMANNia.

## Description

Fügt einen Spenden-Knopf der Navigationsleiste hinzu.

Adds a Donation Button into the [sidebar](https://www.mediawiki.org/wiki/MediaWiki:Sidebar).

## Configuration options

Enable the DonateButton. Default is `false`.

* `$wgDonateButton = true;`

Specify the link to a donation page.

* `$wgDonateButtonURL = "https://yourdomain.org/yourdonationpage.php?lang=";`

The link is automatically completed by the code of the language selected by the user or alternatively by the `$wgLanguageCode` variable.

## Localization

The extension is localized for the languages "de", "en", "es", "fr", "it", "nl", "pt", and "ru".

## Support

Currently, this extension supports the skins Cologne Blue, Modern, MonoBook, Timeless, and Vector.
Further skins may require additional adjustments, which would have to be made in `resources/css/myskin.css`.

For support of skin [Minerva](https://www.mediawiki.org/wiki/Skin:Minerva_Neue) the link to the donation page has to be placed direct into the wiki page `MediaWiki:sitesupport-url`.
Alternatively you may modify this extension and expand the `i18n/en.json` file with an entry like
* `"sitesupport-url": "https://yourdomain.org/donationpage.php?lang=en"`
or
* `"sitesupport-url": "https://yourdomain.org/en/donationpage.php"`

## Compatibility
This extension works from REL1_25 and has been tested up to MediaWiki version `1.39.0-rc.1`.

The [SkinBuildSidebar](https://www.mediawiki.org/wiki/Manual:Hooks/SkinBuildSidebar) hook of several skins no longer allows images and HTML code to be placed in the sidebar.

A solution for this circumstance is not yet known.
As a minimal solution, a simple text link to the donation page is now given.
This occurs in Skins Cologne Blue, Modern, MonoBook and Vector since REL1_37. Skin Timeless still works as usual.

For Skins MonoBook and Vector in REL1_35 and REL1_36 see these [Hacks](https://www.mediawiki.org/wiki/Extension:WimaAdvertising#Hacks).

## Version history

1.0.0

* First public release

1.1.1
* Support added for language "pt".
* Global variable `wgDonateButtonFilename` removed, now the images in the folder `resources/images` will be accessed.
* Global variable `wgDonateButtonLangArray` added. The array contains supported languages, konkret sind damit die verfügbaren Bilder für die Buttons gemeint.
* If no image is available for the Button, the english image will be used instead.
* Support added for MediaWiki REL1_37.
* Support added for Skin "minerva"
* Renamed "CologneBlue.css" into "Cologneblue.css" and "MonoBook.css" into "Monobook.css"

1.2.0

* Global variable `DonateButtonURL` added. Set the link to a custom donation page.
* Global variable `DonateButtonEnabledPaypal` added. If this variable is set to "true", then it will be linked to the Paypal page and the variable `DonateButtonURL` ignored.

1.3.0

* Support for MediaWiki REL1_37+ improved.
* Support added for Skin "timeless", "fallback", and "vector-2022".
* Tested with MediaWiki version `1.39.0-rc.1`.
