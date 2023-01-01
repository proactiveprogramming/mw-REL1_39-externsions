<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing the config object of this extension.
 * File released under the terms of the GNU GPL v3.
 *
 * @file
 */

/*
 * The class embedding all config stuff.
 * Note that there should only exist one (global) instance of this class!
 */
class xGlossarySettings {
	// Do we want to keep empty groups in final glossary page?
	var $mKeepEmptyGroups = false;
	// The name of the “__misc” group, in all languages needed (default is english).
	var $mMiscGroupName = array("en" => "Misc", "fr" => "Divers");
	// The key that will be used to sort the “__misc” group (use “__first” to
	// place it first, “__last” to place it last… Be careful not to use a same sort
	// key as one of your “normal” groups!
	var $mMiscGroupSortKey = "__last";
	
	// The messages for the “redirection” in synonyms auto-generated entries.
	var $mEnsynRedirMsg = array("en" => "See the “[[$1|$2]]” entry.",
	                            "fr" => "Voyez l’entrée “[[$1|$2]]”.");
	
	// Whether to show the “shortdesc” part of the entry in glossary_links.
	var $mLinkShowShortDesc = true;
	// Whether to show the “shortdesc” part of the entry in glossary_links,
	// inside xGlossary pages (doubles render time of glossary pages…).
	var $mLinkShowShortDescInGlossary = true;
	
	// Whether to show performance infos…
	var $mShowPerfs = false;
	
	/*
	 * TEMPLATE STUFF
	 * All templates are translatable…
	 *
	 * FIXME: For now, you have to “escape” (with <!--\n-->) all your new-lines even
	 *        in these templates. Don’t know how mediawiki manage to put <p></p> on
	 *        every newline even here! Grrrrrrr…
	 * TODO: Is there any advantage in having this “two steps” placeholder system
	 *       in templates? Maybe…
	 */
	// The “template variables” names. You should not have to modify them, but…
	// DO NOT modify the keys!
	var $mTemplateVarsNames = array(
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
	);
	// Note: In all following templates, use THE KEY as placeholder, not the values!
	var $mTemplates = array(
		"en" => array(
			/*
			 * A “template” for warning the user about errors (lacking parameters, etc.).
			 * {{{xg_err}}} will be replaced by the error message.
			 */
			xGlossaryI18n::TMPL_ERROR =>
'<span class="xg_error">{{{xg_err}}}</span>',
			/*
			 * A “template” for the glossary page.
			 * {{{xg_idx}}} will be replaced by an unordered list of links to all known groups.
			 * {{{xg_content}}} will be replaced by all generated groups.
			 */
			xGlossaryI18n::TMPL_GLOSSARY => 
'<div class="xg_glossary"><!--
	--><div class="xg_index" id="xg_index"><!--
		-->{{{xg_idx}}}<!--
	--></div><!--
	--><div class="xg_content"><!--
		-->{{{xg_content}}}<!--
	--></div><!--
--></div>',
			/*
			 * A “template” for the groups of the glossary page.
			 * {{{xg_grref}}} will be replaced by the reference of the group.
			 * {{{xg_grname}}} will be replaced by the name of the group.
			 * {{{xg_grcontent}}} will be replaced by all generated entries.
			 */
			xGlossaryI18n::TMPL_GROUP =>
'<div class="xg_group xg_js_block" id="{{{xg_grref}}}"><!--
	--><div class="xg_grheader xg_js_block_toggle"><!--
		--><table style="float: right; font-size: 0.5em; background-color: inherit; line-height: 110%;"><!--
			--><tr><td><span class="xg_js_block_sall">Show All</span></td></tr><!--
			--><tr><td><span class="xg_js_block_hall">Hide All</span></td></tr><!--
		--></table><!--
		--><span style="float: right; font-size: 0.6em"><!--
			--><a class="xg_js_indexlnk" href="#xg_index">Index</a>&nbsp;<!--
		--></span><!--
		--><span>{{{xg_grname}}}</span><!--
	--></div><!--
	--><div class="xg_grcontent xg_js_block_content"><!--
		-->{{{xg_grcontent}}}<!--
	--></div><!--
--></div>',
			/*
			 * A “template” for the entries of the glossary.
			 * {{{xg_enref}}} will be replaced by the reference of the entry.
			 *                          You MUST place this ref as id in the entry wrapping element.
			 * {{{xg_enlangs}}} will be replaced by the languages of the entry (as <ul> list).
			 * {{{xg_entitle}}} will be replaced by the title of the entry.
			 * {{{xg_ensort}}} will be replaced by the sort key of the entry.
			 *                           You MUST include it in the “xg_sortkey” property
			 *                           of the element wrapping the whole entry, else entries
			 *                           will not be sorted by #glossary: func!
			 * {{{xg_enshortdesc}}} will be replaced by the xhtml-rendered short description.
			 * {{{xg_endict}}} will be replaced by the generated dict content.
			 * {{{xg_enlongdesc}}} will be replaced by the xhtml-rendered long description.
			 */
			xGlossaryI18n::TMPL_ENTRY =>
'<div class="xg_entry" id="{{{xg_enref}}}" xg_sortkey="{{{xg_ensort}}}"><!--
	--><div class="xg_enheader"><!--
		--><div class="xg_enheadermisc"><!--
			--><span class="xg_enediturl">[<a href="{{{xg_enediturl}}}">edit</a>]</span>&nbsp;–&nbsp;<!--
			--><span class="xg_disp_ref">{{{xg_enref}}}</span>&nbsp;–&nbsp;<!--
			--><span class="xg_disp_langs">{{{xg_enlangs}}}</span><!--
		--></div><!--
		--><span class="xg_entitle">{{{xg_entitle}}}</span><!--
	--></div><!--
	--><div class="xg_encontent"><!--
		--><div class="xg_enshortdesc"><!--
			-->{{{xg_enshortdesc}}}<!--
		--></div><!--
		--><div class="xg_endict"><!--
			-->{{{xg_endict}}}<!--
		--></div><!--
		--><div class="xg_enlongdesc"><!--
			-->{{{xg_enlongdesc}}}<!--
		--></div><!--
	--></div><!--
--></div>',
			/*
			 * A “template” for the “synonym” auto-generated text placed in these entries
			 * (it will be placed inside the “shortdesc” part of entry template).
			 * {{{xg_ensynorgref}}} will be replaced by the reference to the “original” entry.
			 *                        You MUST include it in the “glossary_orgref” property
			 *                        of the element wrapping the shortdesc, else info
			 *                        box of links to these “syn” entries wont show the
			 *                        good short desc!
			 * {{{xg_ensynmsg}}} will be replaced by the message pointing to the right entry.
			 */
			xGlossaryI18n::TMPL_ENTRYSYN =>
'<span class="xg_ensynonym" org_ref="{{{xg_ensynorgref}}}">{{{xg_ensynmsg}}}</span>',
			/*
			 * A “template” for the “dict” entries of a glossary entry.
			 * {{{xg_dctlangs}}} will be replaced by the languages of this definition (as a ulist).
			 * {{{xg_dctterm}}} will be replaced by the term of this definition.
			 * {{{xg_dctapprox}}} will be replaced by approx state ("" or "?").
			 * {{{xg_dctuncrt}}} will be replaced by uncertain state ("" or "~").
			 * {{{xg_dctusage}}} will be replaced by usage value ("*" to "*****").
			 * {{{xg_dctnote}}} will be replaced by the xhtml-rendred notes about this definition.
			 */
			xGlossaryI18n::TMPL_DICT =>
'<div class="xg_dict"><!--
	--><span class="xg_dctlangs">{{{xg_dctlangs}}}:</span><!--
	--><span class="xg_dctterm"> {{{xg_dctapprox}}}“{{{xg_dctterm}}}” </span><!--
	--><span class="xg_dctmisc">{{{xg_dctusage}}} {{{xg_dctuncrt}}}.</span><!--
	--><span class="xg_dctnote"> {{{xg_dctnote}}}</span><!--
--></div>',
			/*
			 * A “template” for glossary links to a glossary entry.
			 * {{{xg_lnkpath}}} will be replaced by the wiki-path to the entry.
			 * {{{xg_lnktext}}} will be replaced by the text to use as link.
			 * {{{xg_lnkshortdesc}}} will be replaced by the short desc of the linked entry.
			 */
			xGlossaryI18n::TMPL_LINK =>
'<span class="xg_link xg_js_tooltip_generator"><!--
	--><a href="{{{xg_lnkpath}}}">{{{xg_lnktext}}}</a><!--
	--><span class="xg_lnkshortdesc xg_js_tooltip">{{{xg_lnkshortdesc}}}</span><!--
--></span>',
		),
		"fr" => array(
			xGlossaryI18n::TMPL_GROUP =>
'<div class="xg_group xg_js_block" id="{{{xg_grref}}}"><!--
	--><div class="xg_grheader xg_js_block_toggle"><!--
		--><table style="display: block; float: right; font-size: 0.5em; background-color: inherit; line-height: 110%;"><!--
			--><tr><td><span class="xg_js_block_sall">Montrer tout</span></td></tr><!--
			--><tr><td><span class="xg_js_block_hall">Cacher tout</span></td></tr><!--
		--></table><!--
		--><span style="float: right; font-size: 0.6em"><!--
			--><a class="xg_js_indexlnk" href="#xg_index">Index</a>&nbsp;<!--
		--></span><!--
		--><span>{{{xg_grname}}}</span><!--
	--></div><!--
	--><div class="xg_grcontent xg_js_block_content"><!--
		-->{{{xg_grcontent}}}<!--
	--></div><!--
--></div>',
			xGlossaryI18n::TMPL_ENTRY =>
'<div class="xg_entry" id="{{{xg_enref}}}" xg_sortkey="{{{xg_ensort}}}"><!--
	--><div class="xg_enheader"><!--
		--><div class="xg_enheadermisc"><!--
			--><span class="xg_enediturl">[<a href="{{{xg_enediturl}}}">éditer</a>]</span>&nbsp;–&nbsp;<!--
			--><span class="xg_disp_ref">{{{xg_enref}}}</span>&nbsp;–&nbsp;<!--
			--><span class="xg_disp_langs">{{{xg_enlangs}}}</span><!--
		--></div><!--
		--><span class="xg_entitle">{{{xg_entitle}}}</span><!--
	--></div><!--
	--><div class="xg_encontent"><!--
		--><div class="xg_enshortdesc"><!--
			-->{{{xg_enshortdesc}}}<!--
		--></div><!--
		--><div class="xg_endict"><!--
			-->{{{xg_endict}}}<!--
		--></div><!--
		--><div class="xg_enlongdesc"><!--
			-->{{{xg_enlongdesc}}}<!--
		--></div><!--
	--></div><!--
--></div>',
			xGlossaryI18n::TMPL_DICT =>
'<div class="xg_dict"><!--
	--><span class="xg_dctlangs">{{{xg_dctlangs}}}&nbsp;:</span><!--
	--><span class="xg_dctterm"> {{{xg_dctapprox}}}“{{{xg_dctterm}}}” </span><!--
	--><span class="xg_dctmisc">{{{xg_dctusage}}} {{{xg_dctuncrt}}}.</span><!--
	--><span class="xg_dctnote"> {{{xg_dctnote}}}</span><!--
--></div>',
		),
	);
	
	/*
	 * As some config vars depend or are built from others, it MUST be called each
	 * time you modify a setting here (i.e. it will be called in the deffered setup
	 * function, after you (may) have modified some settings in your LocalSettings file).
	 */
	function update() {
		global $wgMessageCache;
		// Create a mapping (keys => matching regex values) of all placeholders.
		// And also a temp mapping(regexed keys => real placeholders).
		$this->vnames = array();
		$t = array();
		foreach ($this->mTemplateVarsNames as $k => $v) {
			$this->vnames[$k] = "/" . preg_quote($v) . "/";
			$t["/\{\{\{" . preg_quote($k) . "\}\}\}/"] = $v;
		}
		// Update all templates (i.e. replace placeholder keys by real ones).
		foreach ($this->mTemplates as $lng => $lng_tmpls) {
			foreach ($lng_tmpls as $k => $tmpl)
				$this->mTemplates[$lng][$k] = preg_replace(array_keys($t),
				                                           array_values($t), $tmpl);
		}
		// Now, update the i18n message cache.
		$msgs = $this->mTemplates;
		// As we use english as default, complete missing bits in other languages
		// by en versions… Only for templates (which have no default value in
		// xGlossaryI18n).
		foreach ($msgs as $lng => $val) {
			if ($lng === 'en') continue;
			$msgs[$lng] = array_merge($msgs['en'], $val);
		}
		foreach ($this->mMiscGroupName as $lng => $val)
			$msgs[$lng][xGlossaryI18n::TMPL_MISC_GRP_NAME] = $val;
		foreach ($this->mEnsynRedirMsg as $lng => $val)
			$msgs[$lng][xGlossaryI18n::TMPL_ENTRYSYN_SDESC] = $val;
		// TODO: Check this overides existing messages (else use addMessage)!
		$wgMessageCache->addMessagesByLang($msgs);
	}
}













