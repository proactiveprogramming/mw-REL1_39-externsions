# nodos-extension
A MediaWiki extension that creates the semantic properties, forms and templates needed to represent and use the ontology of the Performing Arts in any wiki.

## Installation

* Download the sources and save it in the folder named **extensions/nodos** in your mediawiki project.

* Add the following line at the bottom of your LocalSettings.php file:
    `wfLoadExtension( 'Nodos' );`

* Go to the page **Special:Nodos**. There you can click the button to start the ontology replication in your own wiki.

*Note:* You must have Semantic MediaWiki, Semantic Internal Objects and PageForms extensions previously installed for this extension to work.

## Updating the ontology

To update the semanting data that the plugin loads to a newer version, you can follow these steps:

* Obtain the names of all the semantic pages that you want to import. If you want to keep the old pages and add new ones, you can run the script `scripts/getPageTitles.js` which will list all the semantic page titles existing in the old file. You can copy that and add the new pages at the bottom. Each page must take a new line.

* Paste the list of the pages in the export section of the wiki: *Special:Export*, click "Save as new file" and click export.

* Replace the file `data/NodosOntology.xml` with the new downloaded data. NOTE: The name `NodosOntology.xml` must not be changed.

