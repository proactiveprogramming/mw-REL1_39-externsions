Well, this archive contains the current version of xGlossary extension (v. 0.1.3), released under the GNU GPL 3 license. It is now completed and fully featured (from my point of view, of course ;) ) but it yet needs to be tested by others!

It is mainly a PHP extension, with a few wiki templates and JS to help a bit things.

It has been tested with MediaWiki 1.15 and PHP 5.2.

The “xglossary” directory is the PHP extension – install it as any other mediawiki extension. It is fully documented in the “glossary.help.en.php” file, preferably accessed through the {{#glossary_help:}} wiki func (just put it in an empty page…).

The other parts are sample content, designed for Blender wiki – but they should be easily usable with other wiki, as long as you append the jQuery inclusion in your wiki skin. Note the wiki-xml-export version of templates and sample content should be the easiest way to quickly setup a functional sample!

The “TEMPLATES” directory contains the templates used in conjunction with the xGlossary extension:
*template_glossary_link.txt                    → Template:Glossary/Link
*template_glossary_link_documentation.txt      → Template:Glossary/Link/Documentation
*template_glossary_link_path.txt               → Template:Glossary/Link/Path
*template_glossary_link_path_documentation.txt → Template:Glossary/Link/Path/Documentation
IMPORTANT: These templates also use some of the “Languages” ones already defined on Blender wiki. However, I have included them here:
*template_languages_section.txt                → Template:Languages/Section
*template_languages_language.txt               → Template:Languages/Language
*template_languages_slash.txt                  → Template:Languages/Slash
*template_documentation.txt                    → Template:Documentation
*template_literal.txt                          → Template:Literal

The “SAMPLE_CONTENT” directory contains text files of all “glossary content” example pages. WARNING: All these use the “Doc” namespace – they won’t work if you have not such defined namespace!
*EN_glossary.txt                               → Doc:Glossary
*EN_glossary_A.txt                             → Doc:Glossary/A
*etc.
*FR_glossary.txt                               → Doc:FR/Glossary
*FR_glossary_A.txt                             → Doc:FR/Glossary/A
*etc.
*EN_text.txt                                   → Doc:Test
*FR_text.txt                                   → Doc:FR/Test
*EN_glossary_help.txt                          → Doc:Glossary_Help (the complete doc about Glossary extension).
*EN_glossary_tests.txt                         → Doc:Glossary_Tests (a few Glossary auto-tests).

The CSS and JS files…
For Blender skin:
*Blender.css                                   → MediaWiki:Blender.css (should extend it, not replace it, of course)
*xglossary.js                                  → MediaWiki:Blender.js (should extend it, not replace it, of course)
For Monobook skin:
*Monobook.css                                  → MediaWiki:Monobook.css (should extend it, not replace it, of course)
*xglossary.js                                  → MediaWiki:Monobook.js (should extend it, not replace it, of course)

I also put a wiki-xml-export version of these templates and sample content, “wiki_export.xml”.

The “BlenderWiki_skin” is a copy of the official skin (as of 2009/12/15), with just jQuery path made local (to be able to use it offline!) –&nbsp;so you shouldn’t need it in most cases…

Note: to use the example data of the archive, you should add these lines in your “LocalSettings.php” file, after including glossary extension:

## Glossary extension…
require_once "$IP/extensions/glossary/glossary.setup.php";
$wgGlossarySettings->mEnsynRedirMsg = array("en" => "See the “{{Glossary/Link|ref=$1|txt=$2}}” entry.",
                                            "fr" => "Voyez l’entrée “{{Glossary/Link|ref=$1|txt=$2}}”.");

With this glossary-template redefined this way, the auto-generated “synonyms” entries will use the {{Glossary/Link}} template for back link, instead of a standard wiki-link – not a crucial point, but for consistency…

