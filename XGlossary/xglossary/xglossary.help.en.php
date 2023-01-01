<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing the english doc of the xGlossary extension.
 * File released under the terms of the GNU GPL v3.
 *
 * @file
 */

// Do not access this file directly…
if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

// Redefine/extend the GLOSSARY_HELP static var of xGlossaryI18n class.
// Note: Do not use any template here, you don’t know which wiki will have set
//       them! Only use standard base wiki syntax.
// Don’t forget the version number in the main title!
xGlossaryI18n::$GLOSSARY_HELP["en"] =
'=xGlossary Extension Help (v0.1.3)=
This page documents the whole xGlossary extension (GPL 3 license).
:\'\'\'Note\'\'\': This page only documents the \'\'extension functions\'\' part, not the additional templates that you might set up (as we might do with [http://wiki.blender.org blenderwiki]), to ease/enhance these base functionalities. However, you will find at the end of this page some suggestions…

This extension defines following functions:
*[[#The Glossary Pages|<code>#glossary:…</code>]] – The main glossary function.
*[[#The Glossary Entries|<code>#glossary_entry:…</code>]] – The entry-definition function.
*[[#The Glossary Links|<code>#glossary_link:…</code>]] – The link glossary function.
*[[#The Glossary Help|<code>#glossary_help:…</code>]] – The function displaying this help page!
*[[#The Glossary Tests|<code>#glossary_test:…</code>]] – A function performing some simple tests (only useful for development/debugging purposes).
*It also has some [[#Global Settings|global settings]] that control some of its general behavior…

About wiki-functions parameters:
*Some are <code>(mandatory)</code>, others are <code>(optional)</code>; In general, parameters are not wiki-parsed by the xGlossary extension, but there are some important exceptions, noted as <code>(mandatory[wiki])</code> or <code>(optional[wiki])</code>. Lacking or void mandatory parameters will produce error messages in the result xhtml.
*Some parameters are in fact a collection of values. In this case, they use the following common syntax: one or more sets of options wrapped inside parenthesis, each set made of one or more “key=value” pair separated by semi-colons “;”. This implies that if you want to use one of the “(”, “)” or “;” chars inside a value, \'\'you’ll have to escape it with a backslash “\”\'\'.
*All non-wiki-parsed parameters are of course escaped – no xhtml nor wiki is allowed in them, they are raw text!

==General Introduction==
As you might have guessed, this extension has been designed and coded to implement a simple “glossary” for sites using MediaWiki as a project documentation engine. Here are the general concepts:
*You create one or more pages as glossaries, by putting in them the <code>#glossary:</code> func call. Each glossary contains “entries”, sorted and regrouped inside “groups”.
*Then, you create several sub-pages (i.e. pages which names beginning exactly like their glossary page one), which contain the entries (as <code>#glossary_entry:…</code> calls). By default, each sub-page defines a group. However, the entries contained in a given sub-page won’t be necessarily put in the group defined by this page (see below for details).
*Each entry is made of various parameters. Again, see below for precisions.
*Then, everywhere in the wiki, you can use <code>#glossary_link:…</code> to create links to a given entry (it is up to you to give a valid wiki-path to it – one point where wrapping inside a custom template is most handy!). These links might contain the shordesc of the entry, to be shown e.g. in a hover-popup infobox…

\'\'\'Important:\'\'\' Currently, the cached pages are not invalidated, when you modify e.g. a sub-page entry, the main glossary one won’t be immediately updated accordingly, unless you purge it (<code>?&action=purge</code>) explicitly (this is also valid for “shortdesc” text of glossary links).

==The Glossary Pages==
You create a glossary page by inserting in it a call to <code>#glossary:</code> function. This “tag” will be replaced by the content (entries) of all its sub-pages, regrouped and sorted.

By default, each sub-page automatically defines a group, but this can be overridden by the optional “groups” parameter, see below. \'\'\'It is important to understand that the sub-page in which is defined an entry has no importance in the <code>#glossary:</code> output!\'\'\' So you can put your entries where you want – however, you should of course keep them well arranged, for the sake of future editors!

Apart from global settings, that are described [[#Global Settings|below]], this function takes three optional parameters:
;<code>groups</code> (optional)
:If not void, this will override the groups found as sub-pages, see [[#The Glossary Groups|below]] for details.
;<code>disp_lang</code> (optional)
:The ISO code of the language you want to use (might affect the messages, or even the whole output, see [[#Global Settings|Global Settings]]). If void, the current user value is used.
;<code>keep_emptgrp</code> (optional)
:Whether to display empty groups (“yes”) or not (“no”). Note that this will also override a global parameter.

There is one important thing to know about this function: currently, it does not bother whether it regroups/orders actual entries, or other elements! It just parses the xhtml-rendered sub-pages, to extract and order all xhtml elements containing an attribute named “<code>xg_sortkey</code>”. So everything containing such attribute might (will) be displayed and sorted by this function – and nothing else (i.e. everything outside such elements is striped of).

===The Glossary Groups===
As said above, groups in a glossary regroup entries. They can be auto-detected from sub-pages names, of explicitly specified as a parameter in the <code>#glossary:</code> call, using following syntax:

Each group is wrapped inside parenthesis (like “<code>(name=A;ref=a)(name=Meshes;ref=meshes;sort=mesh)</code>”), containing following parameters:
;<code>name</code> (mandatory)
:The name of the group (what will be display).
;<code>ref</code> (mandatory)
:The reference key of the group (what will be used in links).
;<code>sort</code> (optional)
:The sort key of the group (what will be used when ordering groups). If void, <code>ref</code> value is used instead.

Groups have a \'\'name\'\' (their printed form), a \'\'reference\'\' (their link id), and a \'\'sort key\'\' (used when ordering them in the glossary). When auto-generated, name, reference and sort key are all created from the sub-page name (minus the “root” part, the glossary page name).

There is one subtlety with groups: you can have sort of “sub-groups” – not in a “tree form”, but “refined” sub-groups. E.g., you can have a general “m” group, and a more specific “mesh” group, which will be placed after the “m” one, and will regroup all entries which sort keys begin with “mesh”.

As for entries, reference and sort keys should only contain simple lowercase alpha-numeric chars (this ease things when handling urls).

There are two special predefined sort keys: “__first” and “__last” – quite self-explanatory, I think…

As a final word, you may ask yourself “what do you do with entries not fitting in any group?” Well, there is a special group, called by default “Misc” (with “__misc” id and “__last” sort key), that will handle all those poor orphan entries!

==The Glossary Entries==
<code>#glossary_entry:</code> is the function “rendering” in xhtml an entry. Should be just a wiki template, if these templates could produce raw xhtml! However, it also does some more non-template advanced things, like with the [[#Synonyms|synonyms]] option…

It is designed to be used in sub-glossary pages. It expects a “template” with following parameters:
;<code>disp_lang</code> (optional)
:The iso code of the language to use for templates/messages/… (defaults to current user language).
;<code>langs</code> (mandatory)
:A comma-separated list of language codes, e.g. “EN, FR”. Might of course be only one!
;<code>title</code> (mandatory)
:The title of the entry.
;<code>ref</code> (mandatory)
:The reference used in links (should be simple alpha-numeric values, without fancy things like accents…).
;<code>sort</code> (optional)
:The sort key (used by [[#The Glossary Pages|<code>#glossary:</code>]] to sort the entries!), if void defaults to <code>ref</code>.
;<code>dict</code> (optional)
:Some “dict” (sub-)entries, mainly useful for authors/translators, see [[#Dict|below]].
;<code>syns</code> (optional)
:Defines some “synonyms” entries, that will just contain a link to the “real” entry. Especially useful for translated glossaries, see [[#Synonyms|below]].
;<code>shortdesc</code> (mandatory[wiki])
:A short description of this entry, parsed as wiki-text. It will be included in glossary link info box.
;<code>longdesc</code> (optional[wiki])
:A longer description of this entry, if needed (will also be wiki-parsed!).

===Dict===
“Dict” is a small piece of information about a specific term, similar to a simplified dictionary entry. It is designed to contain some data mainly useful for authors/translators of the documentation (it should not be a definition, but rather point a synonym of the current entry, or a translation in another language, …).

Each “dict” element is wrapped inside parenthesis, following the same syntax as [[#The Glossary Groups|groups definitions]], and expects following parameters:
;<code>langs</code> (mandatory)
:The language(s) of the term, as a comma-separated list.
;<code>term</code> (mandatory)
:The term defined here…
;<code>approx</code> (optional)
:Is it approximative (will produce a “~” if set to “yes”, nothing otherwise)?
;<code>uncertain</code> (optional)
:Is it uncertain (will produce a “(?)” if set to “yes”, nothing otherwise)?
;<code>usage</code> (optional)
:“Usage” value, between 1 (strongly discouraged) and 5 (strongly encouraged). Will produce as much stars as the value.
;<code>note</code> (optional[wiki])
:Some short comments, that will be wiki-parsed (be careful here about the “(”, “)” and “;”, that \'\'\'must\'\'\' be escaped…).

===Synonyms===
“Synonyms” are auto-generated entries, that just cross-link back to their “creator” entry – so as its name suggests, it is a shortcut to create synonyms to an entry! The redirect message might be customize and translated, see the [[#Global Settings|global <code>mEnsynRedirMsg</code> parameter description]].

As with “dict” elements, each synonym is wrapped inside parenthesis, following the same syntax as [[#The Glossary Groups|groups definitions]], and expects following parameters:
;<code>langs</code> (mandatory)
:The languages of the synonym, as above.
;<code>title</code> (mandatory)
:The title of the synonym…
;<code>ref</code> (mandatory)
:The link-reference of the synonym.
;<code>sort</code> (optional)
:The sort key of the synonym, if void defaults to <code>ref</code>.

Note that in sub-pages, the synonym entries appear just below their creators. However, in the main glossary page, they will be correctly regrouped and sorted as expected!

==The Glossary Links==
xGlossary links are a “specialized” type of links pointing to glossary entries in main glossary pages.

One of there key feature is that they can retrieve the content of the “shortdesc” of the entry they link to, which, with a bit of JavaScript, can be turned in an infobox poping up when the mouse hover the link, for example.

Note that when you use the reference of a synonym auto-generated entry, you will get \'\'the shortdesc of the “real” entry\'\', not the “redirect text” of the synonym entry!

Here are the parameters of this function:
;<code>disp_lang</code> (optional)
:The iso code of the language to use for templates/messages/… (defaults to current user language).
;<code>ref</code> (mandatory)
:The full wiki path to the linked entry (i.e. page/name#entry_“ref”). See [[#Templates Suggestions|below]] for hints about how to automate this path creation based on only the reference of the entry…
;<code>text</code> (mandatory[wiki])
:The text of the link.
;<code>show_sdesc</code> (optional)
:If set to “yes” or “no”, it will overrides the value of the [[#Global Settings|global mLinkShowShortDesc setting]].

Note that you can prevent the “shortdesc” auto-fetching by setting the [[#Global Settings|global mLinkShowShortDesc]] to “false”…

==The Glossary Help==
Well, it displays this help page! Just create an empty page with the <code>#glossary_help:</code> call (and optionally a disp_lang parameter) to see it.

==The Glossary Tests==
<code>#glossary_test:</code> will run some simple tests, and display their results. It can’t test all functionalities, but it is a good first point to check if you experiment troubles…

==Global Settings==
These are the settings you can define inside your <code>LocalSettings.php</code> file, \'\'after having imported <code>glossary.setup.php</code>\'\'. Note that we use only one global var, <code>wgxGlossarySettings</code>, which stores all settings!

;$wgxGlossarySettings->mKeepEmptyGroups
:(default: <code>false</code>)
:Whether to keep empty groups in final glossary page. This might be overridden by each <code>#glossary:</code> call, see [[#The Glossary Pages|above]].

;$wgxGlossarySettings->mMiscGroupName
:(default: <code>array("en" => "Misc", "fr" => "Divers")</code>)
:The name of the “__misc” group, in all languages needed (default/fall-back one is english; only english and french values currently defined).

;$wgxGlossarySettings->mMiscGroupSortKey
:(default: <code>"__last"</code>)
:The key that will be used to sort the “__misc” group (use “__first” to place it first, “__last” to place it last… Be careful not to use a same sort key as one of your “normal” groups!).

;$wgxGlossarySettings->mEnsynRedirMsg
:(default: <code>array("en" => "See the “&#91;&#91;$1|$2&#93;&#93;” entry.", "fr" => "Voyez l’entrée “&#91;&#91;$1|$2&#93;&#93;”.")</code>)
:The messages for the “redirection” in [[#Synonyms|synonyms auto-generated entries]].

;$wgxGlossarySettings->mLinkShowShortDesc
:(default: <code>true</code>)
:Whether to show the “shortdesc” part of the entry in glossary links or not. This might be overridden by each <code>#glossary_link:</code> call, see [[#The Glossary Links|above]].

;$wgxGlossarySettings->mLinkShowShortDescInGlossary
:(default: <code>true</code>)
:Whether to show the “shortdesc” part of the entry in glossary_links or not, in the Glossary pages. Showing them implies \'\'\'two\'\'\' rendering of these Glossary pages, so you might want to disable that for perfomance reasons…

;$wgxGlossarySettings->mShowPerfs
:(default: <code>false</code>)
:Whether to show performance infos (i.e. process time of few key functions)…

===Templates===
All elements of the glossary system are rendered through a simple “template” system, which mean you can customize in a quite advanced fashion the xhtml code produced by xGlossary functions. These templates are also i18n-able.

\'\'\'Warning :\'\'\'Here we are talking about xGlossary templates, not MediaWiki templates! There are two options in $wgxGlossarySettings that control the templates:

;$wgxGlossarySettings->mTemplateVarsNames
:The template variables names definition. Here is its current content:
<pre>array(
	// The place holder for syntax-errors messages.
	"xg_err"         => "{{{xg_err}}}",
	// General glossary index and content.
	"xg_idx"         => "{{{xg_idx}}}",
	"xg_content"     => "{{{xg_content}}}",
	// The glossary group reference, name and content.
	"xg_grname"      => "{{{xg_grname}}}",
	"xg_grref"       => "{{{xg_grref}}}",
	"xg_grcontent"   => "{{{xg_grcontent}}}",
	// The glossary entry elements.
	"xg_enref"       => "{{{xg_enref}}}",        // The reference of the entry.
	"xg_enediturl"   => "{{{xg_enediturl}}}",    // The “edit” url of owner sub-page.
	"xg_enlangs"     => "{{{xg_enlangs}}}",      // The languages of the entry.
	"xg_entitle"     => "{{{xg_entitle}}}",      // The title of the entry.
	"xg_ensort"      => "{{{xg_ensort}}}",       // The sort key of the entry.
	"xg_enshortdesc" => "{{{xg_enshortdesc}}}",  // The short description.
	"xg_endict"      => "{{{xg_endict}}}",       // The dict content.
	"xg_enlongdesc"  => "{{{xg_enlongdesc}}}",   // The long description.
	// The synonym entry specificities.
	"xg_ensynorgref" => "{{{xg_ensynorgref}}}",  // The reference to the org entry.
	"xg_ensynmsg"    => "{{{xg_ensynmsg}}}",     // The message of the “redirection”.
	// The dict elements.
	"xg_dctlangs"    => "{{{xg_dctlangs}}}",     // The languages of this def.
	"xg_dctterm"     => "{{{xg_dctterm}}}",      // The term of this def.
	"xg_dctapprox"   => "{{{xg_dctapprox}}}",    // The approx state ("" or "?").
	"xg_dctuncrt"    => "{{{xg_dctuncrt}}}",     // The uncertain state ("" or "~").
	"xg_dctusage"    => "{{{xg_dctusage}}}",     // The usage value ("*" to "*****").
	"xg_dctnote"     => "{{{xg_dctnote}}}",      // The notes about this def.
	// The link elements.
	"xg_lnkpath"     => "{{{xg_lnkpath}}}",      // The wiki-path to the entry.
	"xg_lnktext"     => "{{{xg_lnktext}}}",      // The text used by the link.
	"xg_lnkshortdesc"=> "{{{xg_lnkshortdesc}}}", // The shortdesc of the linked entry.
);</pre>
:The keys (left-part strings) should \'\'\'never\'\'\' be modified.
:The right-part strings are the strings that will serve as placeholders for the templates variables. You should nearly never have to modify them, anyway.

;$wgxGlossarySettings->mTemplates
:The templates themselves, which are pieces of xhtml code, with placeholders to be replaced by computed variables.
:It is a 2D array, with at first level, the ISO codes of each languages (“en”, “fr”, etc.), and then the i18n templates.
:Here is a simplified example:
<pre>array(
	"en" =&gt; array(
		/*
		 * A “template” for warning the user about errors (lacking parameters, etc.).
		 * {{{xg_err}}} will be replaced by the error message.
		 */
		xGlossaryI18n::TMPL_ERROR =&gt;
\'&lt;span class="xg_error"&gt;{{{xg_err}}}&lt;/span&gt;\',
		/*
		 * A “template” for the glossary page.
		 * {{{xg_idx}}} will be replaced by an unordered list of links to all known groups.
		 * {{{xg_content}}} will be replaced by all generated groups.
		 */
		xGlossaryI18n::TMPL_GLOSSARY =&gt; 
\'&lt;div class="glss"&gt;&lt;!--
	--&gt;&lt;div class="xg_index"&gt;&lt;!--
		--&gt;{{{xg_idx}}}&lt;!--
	--&gt;&lt;/div&gt;&lt;!--
	--&gt;&lt;div class="xg_content"&gt;&lt;!--
		--&gt;{{{xg_content}}}&lt;!--
	--&gt;&lt;/div&gt;&lt;!--
--&gt;&lt;/div&gt;,\'
	),
);</pre>
:You will note in the code above that placeholders use the keys of <code>$wgxGlossarySettings->mTemplateVarsNames</code>, not its values. These will be replaced at init time by the right values, to allow you to define other placeholders name, without having to touch to the templates themselves…
:Note also that all newlines are xhtml-commented out, to prevent MediaWiki adding &lt;p&gt;&lt;/p&gt; everywhere (Grrr!).
:<code>xGlossaryI18n::TMPL_XXX</code> are constants for each template:
<pre>// Templates “id”.
const TMPL_ERROR          = "xg_tmpl_error";
const TMPL_GLOSSARY       = "xg_tmpl_glossary";
const TMPL_GROUP          = "xg_tmpl_group";
const TMPL_ENTRY          = "xg_tmpl_entry";
const TMPL_ENTRYSYN       = "xg_tmpl_entrysyn";
const TMPL_DICT           = "xg_tmpl_dict";
const TMPL_LINK           = "xg_tmpl_link";</pre>

For more info and examples on the xGlossary templates, read the code in <code>glossary.i18n.php</code> file – anyway, if you plan to modify these settings, you are definitively a site admin ;) .

Even if it is obvious, let’s mention also the CSS style sheet as a way to customize the graphical part of this extension!

==About JS==
xGlossary uses Java Script in two places (the foldable groups of entries in glossary pages, and the pop-up info-boxes of the glossary links) – but JS is \'\'absolutely not needed for xGlossary to work\'\'!

The Java Script code shipped with this extension uses jQuery, so you’ll have to include this library in your pages if you want to use it “as is”. However, I tried to nicely prepare the xGlossary output to be used by any JS code, using “xg_js_xxx” classes to mark elements for JS, so it should be quite easy to adapt all this to another JS toolbox…

==Templates Suggestions==
Here are a few suggestions for templates (MediaWiki, this time!) wrapping xGlossary functions, and automating some actions:

;Display language selection
:If you have a multi-language site with a standard way of naming pages in different languages, you might want to create a glossary for each one. E.g. with Blender wiki, we use {namespace}:{lang/}{pagename}, so you could create a template that detects the language of current glossary page, and automatically set accordingly the “disp_lang” parameter of the <code>#glossary:</code> call it wraps. The same goes for entries and glossary links, of course!

;Set the right path for glossary links
:Assuming you have a well defined glossary page, you can create a Glossary/Link template that, given just an entry reference, will create the right complete path to the glossary page, with the reference as url “fragment”.
';








