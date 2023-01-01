<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing the main “real” actions of this extension, all embedded in a class.
 * File released under the terms of the GNU GPL v3.
 *
 * @file
 */

// Do not access this file directly…
if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

/*
 * The class embedding all “real work” functions.
 */
class xGlossaryMain {
	/**************************************************************************
	 * Main functions.                                                        *
	 **************************************************************************/
	
	/*
	 * The function building a glossary page from the content of the children ones.
	 * Parameters:
	 * * groups= : The groups to create in glossary. If null, create a group for each child page.
	 *             Format: “(name=name;ref=ref;sort=sort_key)”.
	 *             If you want to use a “;”, a “(” or a “)” in name/ref/sortk, escape it with “\”!
	 * * disp_lang= : The display language to use (if void, use default user one).
	 * * keep_emtpgrp= : Whether to keep empty groups or not (overrides settings value).
	 *
	 * @param parser &$parser The reference to a parser object.
	 * @param string One or more of this function parameters, see above.
	 * @return string The built glossary code, AS RAW HTML!
	 */
	public static function renderGlossary(&$parser) {
		global $wgxGlossarySettings;
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) $mtime = microtime(true);
		
		// Misc init.
		$output = "";
		// The regex for replacing template placeholders with real values.
		$vnames = $wgxGlossarySettings->vnames;
		$thisTitle = $parser->mTitle;
		$glossary = array($vnames["xg_idx"]     => "",
		                  $vnames["xg_content"] => "");
		$args = array_slice(func_get_args(), 1);
		$params = self::getParameters($args);
		$lng = array("language" => self::getDispLang($params));
		$keepEmptGrp = $wgxGlossarySettings->mKeepEmptyGroups;
		// Overrides default settings with given param, if valid…
		if ($params["keep_emptgrp"]) {
			if (strtolower($params["keep_emptgrp"]) === "yes") $keepEmptGrp = true;
			else if (strtolower($params["keep_emptgrp"]) === "no") $keepEmptGrp = false;
		}
		if ($params["groups"]) {
			// This helper will parse our groups in an array of arrays (key => value).
			$tgr = self::getSubParameters($params["groups"]);
			$groups = array();
			foreach ($tgr as $gr) {
				// If invalid group definition, silently ignore!
				if (!($gr["name"] and $gr["ref"])) continue;
				if (!$gr["sort"]) $gr["sort"] = $gr["ref"];
				$groups[wfEscapeWikiText($gr["sort"])] =
					array("gr_name" => wfEscapeWikiText($gr["name"]),
					      "gr_ref" => wfEscapeWikiText($gr["ref"]));
			}
		}
		
		// Retrieve all children pages.
		$thisTitle = $parser->mTitle;
		$dbconn = wfGetDB(DB_SLAVE);
		$conds['page_namespace'] = $thisTitle->getNamespace();
		$conds[] = 'page_title LIKE '
		         . $dbconn->addQuotes($dbconn->escapeLike($thisTitle->getDBkey() . '/') . '%');
		$children = TitleArray::newFromResult(
			$dbconn->select('page',
			                array('page_id', 'page_namespace', 'page_title', 'page_is_redirect'),
			                $conds, __METHOD__, array())
		);
		
		// Get: * The html code of ALL entries, to be ordered and regrouped later;
		//      * The groups, if none were specified in parameter.
		$html = "";
		if (!$groups) $tgroups = array();
		foreach ($children as $title) {
			// If no groups were given as parameter, use child page names.
			// Note that we use the original title, not the potentially redirected
			// one, as group name.
			if (isset($tgroups)) {
				// Remove “root” part.
				$tgr = substr($title->mTextform, strlen($thisTitle->mTextform)+1);
				$tgroups[strtolower($tgr)] = array("gr_name" => $tgr, "gr_ref" => strtolower($tgr));
			}
			$article = new Article($title);
			$txt     = $article->getContent();
			$rtitle  = $article->followRedirectText($text);
			if ($rtitle !== false) {
				$title   = $rtitle;
				$article = new Article($title);
				$txt    = $article->getContent();
			}
			// Note: prefer parsing sub-page in their “own” context
			// (for the “edit” link, for example…).
			$parser->xgRealPageTitle = $title;
			$html .= self::parseText($parser, $txt, $parser->mTitle);
//			$html .= self::parsePage($parser, $title);
		}
		// Do not parse in “Glossary page” context…
//		$html = self::parseText($parser, $ttxt, $parser->mTitle);
		if (!$groups) $groups = $tgroups;
		
		// This helper function will sort and regroup all entries.
		$groups = self::glossarySortEntries($groups, $html, $keepEmptGrp, $lng);
		
		// Now we have all groups, just have to set them in glossary, and update index!
		$glossary[$vnames["xg_idx"]] = "<ul>";
		foreach ($groups as $gr) {
			$glossary[$vnames["xg_idx"]] .= "<li><a href=\"#" . $gr["gr_ref"]
			                                . "\">" . $gr["gr_name"] . "</a></li>";
			$glossary[$vnames["xg_content"]] .= $gr["html"];
		}
		$glossary[$vnames["xg_idx"]] .= "</ul>";
		
		// Put all this in glossary template!
		if ($output === "") // Means no errors…
			$output = preg_replace(array_keys($glossary), array_values($glossary),
			                       wfMsgExt(xGlossaryI18n::TMPL_GLOSSARY, $lng));
		// Remove all comments around new lines (and consequently all new lines!)
		// from the result, to lighten it a bit! Note that it will create a very
		// long line!
		// Try to avoid removing “real” comments, they might be of use!
		$output = preg_replace("/<!--(\n|\r|\s)*-->/", "", $output);
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) {
			$ms = sprintf("%05d", (int)((microtime(true) - $mtime)*1000));
			print('<span style="color: red;">' . __METHOD__ . " (" . $ms ." ms)</span><br/>");
		}
		
		// FIXME: Both solutions seem to add <p></p> to all new lines! Grrrrrr…
		return $parser->insertStripItem($output);
//		return array($output, 'noparse' => true, 'isHTML' => true);
	}
	
	/*
	 * The function sorting all entries in given html code, and regrouping them.
	 *
	 * @param array $groups The groups to create, in form
	 *                      (<group sort> => (<group name>, <group ref>)).
	 * @param string $html The html code containing all entries.
	 * @return array The created groups, as arrays
	 *               (<group sort> => (<group name>, <group ref>, <html code>))!
	 */
	public static function glossarySortEntries($groups, $html, $keepEmptGrp, $lng) {
		global $wgxGlossarySettings;
		
		// The misc group contains all entries that do not fit in others.
		$miscgr = array("gr_name" => wfMsgExt(xGlossaryI18n::TMPL_MISC_GRP_NAME, $lng),
		                "gr_ref" => "__misc", "html" => array());
		$ret = $groups;
		$sortkeys = array_keys($groups);
		$vnames = $wgxGlossarySettings->vnames;
		// Find any kind of tag containing both a “id” and a “xg_sortkey” attribute,
		// and return them with “xg_sortkey” content as key.
		$filt = array(array("tag" => null,
		                    "attrs" => array("id", "xg_sortkey"),
		                    "key" => "xg_sortkey"));
		$entries = self::extractXMLElements ($html, $filt);
		$entries = $entries[0];
		foreach ($entries as $sk => $content) {
			$content = $content["content"];
			// The sorting is a bit tricky. All this allows us to handle situations
			// like having as groups (“m”, “mesh”), putting everything that start
			// with “mesh” in “mesh” group, and everything else starting with
			// “m” in “m” group… Entries not fitting in any groups will be put in
			// “__misg” group!
			if (in_array($sk, $sortkeys)) {
				$ret[$sk]["html"][$sk] = $content;
				continue;
			}
			$mink = $sk{0};
			$tsk = $sortkeys;
			$tsk[] = $sk;
			sort($tsk, SORT_STRING);
			// $psk: position of our sort key.
			// $pmink: position of the “most general” group in which $sk will fit.
			$psk = array_search($sk, $tsk)-1;
			// If $mink not in groups, find “most general” group…
			if (!in_array($mink, $tsk)) {
				$tt = $tsk;
				$tt[] = $mink;
				sort($tt);
				$pmink = array_search($mink, $tt) + 1;
			}
			else $pmink = array_search($mink, $tsk);
			// Must go backward to fit “finest” group possible (i.e. “mesh” rather than “m”).
			for ($i = $psk; $i >= $pmink; $i--) {
				if (strpos($sk, $tsk[$i]) === 0) {// Found a matching group!
					$ret[$tsk[$i]]["html"][$sk] = $content;
					break;
				}
			}
			// No groups fitting, put it in __misc one.
			if ($i < $pmink) $miscgr["html"][$sk] = $content;
		}
		// Append __misc group (if necessary).
		$miscGrpSKey = $wgxGlossarySettings->mMiscGroupSortKey;
		if ($keepEmptGrp or count($miscgr["html"]))
			$ret[$miscGrpSKey] = $miscgr;
		// Now all our entries are into the good groups.
		// We need to sort the entries inside each group, and then flatten html code.
		foreach ($ret as $k => $gr) {
			if (!count($gr["html"])) {
				if (!$keepEmptGrp) {
					unset($ret[$k]);
					continue;
				}
				else $gr["html"] = array();
			}
			ksort($gr["html"], SORT_STRING);
			$thtml = "";
			foreach ($gr["html"] as $en) $thtml .= $en;
			// Put everything in group “template”.
			$patterns = array($vnames["xg_grname"],
			                  $vnames["xg_grref"],
			                  $vnames["xg_grcontent"]);
			$values   = array($gr["gr_name"], $gr["gr_ref"], $thtml);
			$ret[$k]["html"] = preg_replace($patterns, $values,
			                                wfMsgExt(xGlossaryI18n::TMPL_GROUP, $lng));
		}
		// And finally sort the groups themselves!
		// Note that because of the possible “special” sort key values (“__first”
		// and “__last”), we have to use a custom compare function.
		uksort($ret, "xGlossaryMain::ccmp");
		return $ret;
	}
	
	static function ccmp($a, $b) {
		if ($a === $b) return 0;
		if ($a === "__first" or $b === "__last") return -1;
		if ($a === "__last" or $b === "__first") return 1;
		return strcmp($a, $b);
	}
	
	/*
	 * The function “rendering” in xhtml an entry. Should be just a wiki template,
	 * if these templates could produce raw xhtml!
	 * To be used in sub-glossary pages…
	 *
	 * Expects a “template” with following parameters (wiki mean that it can
	 * contain wiki text, that will be parsed…):
	 *
	 * {{#glossary_entry:
	 *  |disp_lang= (optional)        The iso code of the language to use for
	 *                                templates/messages/…
	 *  |langs=     (mandatory)       A comma-separated list of language codes,
	 *                                e.g. “EN, FR”. Might of course be only one!
	 *  |title=     (mandatory)       The title of the entry.
	 *  |ref=       (mandatory)       The reference used in links (should be
	 *                                simple alpha-numeric values, without fancy
	 *                                things like accents…
	 *  |sort=      (optional)        The sort key (used by #glossary: to sort
	 *                                the entries!), if void uses ref.
	 *  |dict=      (optional)        Some “dict” entries, mainly useful for
	 *                                authors/translators. Syntax: see below.
	 *  |syns=      (optional)        Some “synonyms” entries. Will just contain
	 *                                a link to the “real” entry. Especially useful
	 *                                for non-english glossaries! Syntax: see below.
	 *  |shortdesc= (mandatory, wiki) A short description of this entry. It will
	 *                                be included in glossary link info box.
	 *  |longdesc=  (optional, wiki)  A longer description of this entry, if needed.
	 *  }}
	 *
	 * The dict syntax: a comma-separated list of:
	 * (langs=""    (mandatory)      The language(s) of the term, as above.
	 *  term=""     (mandatory)      The term defined here…
	 *  approx=""   (optional)       Is it approximative?
	 *  uncertain=""(optional)       Is it uncertain?
	 *  usage=""    (optional)       “Usage” value, between 1 and 4.
	 *  note="")    (optional, wiki) Some short comments…
	 *
	 * The syns syntax: a comma-separated list of:
	 * (langs=""    (mandatory)      The languages of the synonym, as above.
	 *  title=""    (mandatory)      The title of the synonym…
	 *  ref=""      (mandatory)      The link-reference of the synonym.
	 *  sort="")    (optional)       The sort key of the synonym, if void uses ref.
	 *
	 * @param parser &$parser The reference to a parser object.
	 * @param string All the parameters of this “template”, see above.
	 * @return string The entry (and maybe its synonyms), AS RAW HTML!
	 */
	public static function renderGlossaryEntry (&$parser) {
		global $wgxGlossarySettings;
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) $mtime = microtime(true);
		
		// The regex for replacing template placeholders with real values.
		$vnames = $wgxGlossarySettings->vnames;
		$thisTitle = $parser->mTitle;
		$thisPageName = $thisTitle->getPrefixedText();
		// When called (parsed) from the “glossary” function (i.e. main glossary
		// page), the “page title” is glossary one, and xgRealPageTitle is the
		// real page title of the entry (needed for editing)!
//		print(pp($parser->xgRealPageTitle));
		if ($parser->xgRealPageTitle)
			$thisEditUrl = $parser->xgRealPageTitle->getEditURL();
		else
			$thisEditUrl = $thisTitle->getEditURL();
		$vars = array($vnames["xg_enlangs"]     => "",
		              $vnames["xg_entitle"]     => "",
		              $vnames["xg_enediturl"]   => $thisEditUrl,
		              $vnames["xg_enref"]       => "",
		              $vnames["xg_ensort"]      => "",
		              $vnames["xg_endict"]      => "",
		              $vnames["xg_enshortdesc"] => "",
		              $vnames["xg_enlongdesc"]  => "");
		$syns = array();
		$args = array_slice(func_get_args(), 1);
		$params = self::getParameters($args);
		$lng = array("language" => self::getDispLang($params));
		foreach ($params as $name => $value) {
			if (!$value) continue;
			if ($name === "langs") {
				$vars[$vnames["xg_enlangs"]] = "<ul>";
				foreach (explode(",", $value) as $lang)
					$vars[$vnames["xg_enlangs"]] .=
						"<li>" . trim(wfEscapeWikiText($lang)) . "</li>";
				$vars[$vnames["xg_enlangs"]] .= "</ul>";
			}
			else if ($name === "title")
				$vars[$vnames["xg_entitle"]] = wfEscapeWikiText($value);
			else if ($name === "ref")
				$vars[$vnames["xg_enref"]] =
					self::replaceSpaces(strtolower(wfEscapeWikiText($value)));
			else if ($name === "sort")
				$vars[$vnames["xg_ensort"]]  = strtolower(wfEscapeWikiText($value));
			else if ($name === "dict")
				$vars[$vnames["xg_endict"]] .=
					self::renderGlossaryDict($parser, self::getSubParameters($value), $lng);
			else if ($name === "syns")
				$syns = array_merge($syns, self::getSubParameters($value));
			else if ($name === "shortdesc")
				// DO NOT USE parse() here!!!
				// Can’t use the API parse neither!!!
				// Wrap this piece of html inside a tag, to find it later with glossary_link!
				$vars[$vnames["xg_enshortdesc"]] =
					'<span class="xg_shortdesc_el">'
					. $parser->recursiveTagParse($value)
					//. self::parseText($value, $thisPageName)
					. '</span>';
			else if ($name === "longdesc")
				// DO NOT USE parse() here!!!
				// Can’t use the API parse neither!!!
				$vars[$vnames["xg_enlongdesc"]] = $parser->recursiveTagParse($value);
				//$vars[$vnames["xg_enlongdesc"]] = &self::parseText($value, $thisPageName);
		}
		// Use ref if no sort key was given.
		if (!$vars[$vnames["xg_ensort"]])
			$vars[$vnames["xg_ensort"]] = $vars[$vnames["xg_enref"]];
		
		// Test for lacking mandatory parameters…
		$err_template = wfMsgExt(xGlossaryI18n::TMPL_ERROR, $lng);
		if (!$vars[$vnames["xg_enlangs"]])
			$vars[$vnames["xg_enlangs"]] =
				preg_replace($vnames["xg_err"],
				             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "langs"),
			                 $err_template);
		if (!$vars[$vnames["xg_entitle"]])
			$vars[$vnames["xg_entitle"]] =
				preg_replace($vnames["xg_err"],
				             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "title"),
				             $err_template);
		if (!$vars[$vnames["xg_enref"]]) {
			$vars[$vnames["xg_enref"]] = "__first";
			$vars[$vnames["xg_ensort"]] = "__first";
			$vars[$vnames["xg_enshortdesc"]] .=
				preg_replace($vnames["xg_err"],
				             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "ref"),
				             $err_template);
		}
		if (!$vars[$vnames["xg_enshortdesc"]])
			$vars[$vnames["xg_enshortdesc"]] =
				preg_replace($vnames["xg_err"],
				             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "shortdesc"),
				             $err_template);
		
		// Render this entry…
		$output = preg_replace(array_keys($vars), array_values($vars),
		                       wfMsgExt(xGlossaryI18n::TMPL_ENTRY, $lng));
		// Now, create the synonyms…
		$org_title = $vars[$vnames["xg_entitle"]];
		$org_ref = $vars[$vnames["xg_enref"]];
		$output .= self::createGlossarySynonyms($parser, $syns, $thisEditUrl,
		                                        $org_title, $org_ref, $lng);
		// Remove all comments around new lines (and consequently all new lines!)
		// from the result, to lighten it a bit! Note that it will create a very
		// long line!
		// Try to avoid removing “real” comments, they might be of use!
		$output = preg_replace("/<!--(\n|\r|\s)*-->/", "", $output);
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) {
			$ms = sprintf("%05d", (int)((microtime(true) - $mtime)*1000));
			print('<span style="color: green;">' . __METHOD__ . " (" . $ms ." ms)</span><br/>");
		}
		
		// FIXME: Both solutions seem to add <p></p> to all new lines! Grrrrrr…
		return $parser->insertStripItem($output);
//		return array($output, 'noparse' => true, 'isHTML' => true);
	}
	
	/*
	 * Parse the dict parameters into dict xhtml code.
	 *
	 * The dict syntax: a comma-separated list of:
	 * (langs=""    (mandatory)      The language(s) of the term, as above.
	 *  term=""     (mandatory)      The term defined here…
	 *  approx=""   (optional)       Is it approximative?
	 *  uncertain=""(optional)       Is it uncertain?
	 *  usage=""    (optional)       “Usage” value, between 1 and 4.
	 *  note="")    (optional, wiki) Some short comments…
	 *
	 * @param Parser &$parser The parser object (used for “note” part).
	 * @param array $values The values (2D array, first whole dict, then dict params).
	 * @param Language $lng The current language object.
	 * @return string The xhtml rendered dicts…
	 */
	public static function renderGlossaryDict(&$parser, $values, $lng) {
		global $wgxGlossarySettings;
		
		$vnames = $wgxGlossarySettings->vnames;
		$ret = "";
		foreach ($values as $dict) {
			$vars = array();
			$vars[$vnames["xg_dctlangs"]]  = wfEscapeWikiText($dict["langs"]);
			$vars[$vnames["xg_dctterm"]]   = wfEscapeWikiText($dict["term"]);
			$vars[$vnames["xg_dctapprox"]] =
				strtolower($dict["approx"]) === "yes" ? "~" : "";
			$vars[$vnames["xg_dctuncrt"]]  =
				strtolower($dict["uncertain"]) === "yes" ? " (?)" : "";
			$vars[$vnames["xg_dctnote"]]   = $parser->recursiveTagParse($dict["note"]);
			$usg = min(5, max(0, intval($dict["usage"])));
			$vars[$vnames["xg_dctusage"]]  = str_pad("", $usg, "*");
			
			// Testing for lacking mandatory parameters.
			$err_template = wfMsgExt(xGlossaryI18n::TMPL_ERROR, $lng);
			if (!$vars[$vnames["xg_dctlangs"]])
				$vars[$vnames["xg_dctlangs"]] =
					preg_replace($vnames["xg_err"],
					             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "langs"),
					             $err_template);
			if (!$vars[$vnames["xg_dctterm"]])
				$vars[$vnames["xg_dctterm"]] =
					preg_replace($vnames["xg_err"],
					             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "term"),
					             $err_template);
			$ret .= preg_replace(array_keys($vars), array_values($vars),
			                     wfMsgExt(xGlossaryI18n::TMPL_DICT, $lng));
		}
		return $ret;
	}
	
	/*
	 * Parse the syns data into a valid entry.
	 *
	 * The syns syntax: a comma-separated list of:
	 * (langs=""    (mandatory)      The languages of the synonym, as above.
	 *  title=""    (mandatory)      The title of the synonym…
	 *  ref=""      (mandatory)      The link-reference of the synonym.
	 *  sort="")    (optional)       The sort key of the synonym, if void uses ref.
	 *
	 * @param Parser &$parser The parser object (used for “note” part).
	 * @param array $syns The synonyms (2D array, first whole synonym, then syn params).
	 * @param string $editUrl The edit-url of the “generator” entry.
	 * @param string $org_title The title of the “generator” entry (for redirect-link).
	 * @param string $org_ref The reference of the “generator” entry (for redirect-link).
	 * @param Language $lng The current language object.
	 * @return string The xhtml rendered dicts…
	 */
	public static function createGlossarySynonyms(&$parser, $syns, $editUrl,
	                                              $org_title, $org_ref, $lng) {
		global $wgxGlossarySettings;
		
		$vnames = $wgxGlossarySettings->vnames;
		$ret = "";
		foreach ($syns as $syn) {
			$vars = array();
			$vars[$vnames["xg_enlangs"]]     = strtoupper(wfEscapeWikiText($syn["langs"]));
			$vars[$vnames["xg_entitle"]]     = wfEscapeWikiText($syn["title"]);
			$vars[$vnames["xg_enediturl"]]   = $editUrl;
			$vars[$vnames["xg_enref"]]       =
				self::replaceSpaces(strtolower(wfEscapeWikiText($syn["ref"])));
			$vars[$vnames["xg_ensort"]]      =
				strtolower($syn["sort"] ? wfEscapeWikiText($syn["sort"])
				                        : wfEscapeWikiText($syn["ref"]));
			$vars[$vnames["xg_endict"]]      = "";
			$vars[$vnames["xg_enlongdesc"]]  = "";
			$sdesc = wfMsgExt(xGlossaryI18n::TMPL_ENTRYSYN_SDESC, $lng, $org_ref, $org_title);
			$sdesc = $parser->recursiveTagParse($sdesc);
			$sdesc = preg_replace(array($vnames["xg_ensynorgref"], $vnames["xg_ensynmsg"]),
			                      array($org_ref, $sdesc),
			                      wfMsgExt(xGlossaryI18n::TMPL_ENTRYSYN, $lng));
			$vars[$vnames["xg_enshortdesc"]] = '<span class="xg_shortdesc_el">'
			                                   . $sdesc . '</span>';
			
			// Testing for lacking mandatory parameters.
			$err_template = wfMsgExt(xGlossaryI18n::TMPL_ERROR, $lng);
			if (!$vars[$vnames["xg_enlangs"]])
				$vars[$vnames["xg_enlangs"]] =
					preg_replace($vnames["xg_err"],
					             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "langs"),
					             $err_template);
			if (!$vars[$vnames["xg_entitle"]])
				$vars[$vnames["xg_entitle"]] =
					preg_replace($vnames["xg_err"],
					             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "title"),
					             $err_template);
			if (!$vars[$vnames["xg_enref"]]) {
				$vars[$vnames["xg_enref"]] = "__first";
				$vars[$vnames["xg_ensort"]] = "__first";
				$vars[$vnames["xg_enshortdesc"]] .=
					preg_replace($vnames["xg_err"],
					             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "ref"),
					             $err_template);
			}
			$ret .= preg_replace(array_keys($vars), array_values($vars),
			                     wfMsgExt(xGlossaryI18n::TMPL_ENTRY, $lng));
		}
		return $ret;
	}
	
	/*
	 * The function rendering a glossary link, especially adding a floating box
	 * (with some JS) containing the shortdesc of the corresponding entry.
	 *
	 * Expects a “template” with following parameters (wiki mean that it can
	 * contain wiki text, that will be parsed…):
	 *
	 * {{#glossary_link:
	 *  |ref=        (mandatory)       The reference of the linked entry.
	 *  |text=       (mandatory, wiki) The text of the link.
	 *  |show_sdesc= (optional)        “yes” or “no”, overrides the value of
	 *                                 mLinkShowShortDesc setting!
	 *  }}
	 *
	 * @param parser &$parser The reference to a parser object.
	 * @param string All the parameters of this “template”.
	 * @return string The link code, AS RAW HTML!
	 */
	public static function renderGlossaryLink (&$parser) {
		global $wgxGlossarySettings;
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) $mtime = microtime(true);
		
		// The regex for replacing template placeholders with real values.
		$vnames = $wgxGlossarySettings->vnames;
		$vars = array($vnames["xg_lnkpath"]     => "",
		              $vnames["xg_lnktext"]     => "",
		              $vnames["xg_lnkshortdesc"]=> "");
		$args = array_slice(func_get_args(), 1);
		$params = self::getParameters($args);
		$lng = array("language" => self::getDispLang($params));
		$thisPageName = $parser->mTitle->getPrefixedText();
		foreach ($params as $name => $value) {
			if (!$value) continue;
			if ($name === "path") {
				$ttitle = Title::newFromText($value);
				if (!$ttitle) continue;
				$vars[$vnames["xg_lnkpath"]] = $ttitle->getLinkUrl();
			}
			else if ($name === "text") {
				$vars[$vnames["xg_lnktext"]] = $parser->recursiveTagParse($value);
				//$vars[$vnames["xg_lnktext"]] = self::parseText($value, $thisPageName);
			}
			else if ($name === "show_sdesc") {
				// If value is not valid, let $showSDesc as set by global settings.
				if (strtolower($value) === "yes") $showSDesc = true;
				else if (strtolower($value) === "no") $showSDesc = false;
			}
		}
		$vars[$vnames["xg_lnkshortdesc"]] = "";
		// rec is a marker, avoiding us infinite recursion when rendering
		// glossary links from inside glossary itself!
		static $rec = 0;
		// cache stores the rendered glossary pages (usually only one…) for
		// future glossary links (in same “render session” only!).
		static $cache = array();
		$showSDesc = ($wgxGlossarySettings->mLinkShowShortDesc and
		              ($wgxGlossarySettings->mLinkShowShortDescInGlossary or
		               $ttitle->getPrefixedText()));
		if ($ttitle and $showSDesc and $rec === 0) {
			$rec++;
			$ref  = self::replaceSpaces($ttitle->mFragment);
			$page = $ttitle->getPrefixedText();
			if (!$page) {
				$ttitle = clone $parser->mTitle;
				$ttitle->setFragment($ref);
				$page = $ttitle->getPrefixedText();
			}
			// The html-rendered-filtered glossary page is cached in a static
			// variable, to speed-up things!
			$entries = $cache[$page];
			// If not in cache…
			if (!$entries) {
				$html = &self::parsePage($parser, $ttitle);
				// Find any kind of tag containing both an “id” and a “xg_sortkey”,
				// attribute and return them with “xg_sortkey” content as key.
				$filt = array(array("tag" => null,
				                    "attrs" => array("id", "xg_sortkey"),
				                    "key" => "id"));
				$entries = self::extractXMLElements ($html, $filt);
				// Store the entries in static cache, for the other
				// glossary_link in this page.
				$cache[$page] = $entries[0];
				$entries = $entries[0];
			}
			// Get the entry xhtml code that interests us.
			$entry = $entries[$ref]["int_content"];
			// Find all span tags containing a “class” attribute in this entry,
			// and select the one with “xg_shordesc_el” class.
			$filt = array(array("tag" => "span",
			                    "attrs" => array("class"),
			                    "key" => "class"));
			$spans = self::extractXMLElements ($entry, $filt);
			$ret = $spans[0]["xg_shortdesc_el"]["int_content"];
			
			// Now, we have the “shortdesc” of relevant entry. Must check whether
			// it is a “synonym” one, to try to get “real” shortdesc!
			$filt = array(array("tag" => "span",
			                    "attrs" => array("class", "org_ref"),
			                    "key" => null));
			$spans = self::extractXMLElements ($entry, $filt);
			// If this is a synonym entry…
			if ($spans[0][0]["attrs"]["org_ref"]) {
				// Get the entry xhtml code that interests us.
				$entry = $entries[$spans[0][0]["attrs"]["org_ref"]]["int_content"];
				// Find all span tags containing a “class” attribute in this entry,
				// and select the one with “xg_shordesc_el” class.
				$filt = array(array("tag" => "span",
				                    "attrs" => array("class"),
				                    "key" => "class"));
				$spans = self::extractXMLElements ($entry, $filt);
				$ret = $spans[0]["xg_shortdesc_el"]["int_content"];
			}
			
			$vars[$vnames["xg_lnkshortdesc"]] = $ret;
			$rec--;
		}
		
		// Test for lacking mandatory parameters…
		$err_template = wfMsgExt(xGlossaryI18n::TMPL_ERROR, $lng);
		if (!$vars[$vnames["xg_lnktext"]])
			$vars[$vnames["xg_lnktext"]] =
				preg_replace($vnames["xg_err"],
				             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "text"),
				             $err_template);
		if (!$vars[$vnames["xg_lnkpath"]] === "") {
			$vars[$vnames["xg_lnkpath"]] = "#";
			$vars[$vnames["xg_lnktext"]] .=
				preg_replace($vnames["xg_err"],
				             wfMsgExt(xGlossaryI18n::MSG_ERR_TMPL_PARAM, $lng, "path"),
				             $err_template);
		}
		
		$output = preg_replace(array_keys($vars), array_values($vars),
		                       wfMsgExt(xGlossaryI18n::TMPL_LINK, $lng));
		// Remove all comments around new lines (and consequently all new lines!)
		// from the result, to lighten it a bit! Note that it will create a very
		// long line!
		// Try to avoid removing “real” comments, they might be of use!*/
		$output = preg_replace("/<!--(\n|\r|\s)*-->/", "", $output);
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) {
			$ms = sprintf("%05d", (int)((microtime(true) - $mtime)*1000));
			print('<span style="color: blue;">' . __METHOD__ . " (" . $ms ." ms)</span><br/>");
		}
		
		// FIXME: Both solutions seem to add <p></p> to all new lines!
		//        Even if marked as raw html!!! Grrrrrr…
		return $parser->insertStripItem($output/*"<i>test_glink</i>"*/);
//		return array($output, 'noparse' => true, 'isHTML' => true);
	}
	
	/*
	 * The function printing a nice help page (as wiki text – non need to parse here!).
	 * @param parser &$parser The reference to a parser object.
	 * @return string The built-in help, AS WIKI CODE!
	 */
	public static function renderGlossaryHelp(&$parser) {
		$args = array_slice(func_get_args(), 1);
		$params = self::getParameters($args);
		$lng = self::getDispLang($params);
		$lng = $lng->getCode();
		$output = xGlossaryI18n::$GLOSSARY_HELP[$lng] ? xGlossaryI18n::$GLOSSARY_HELP[$lng]
		                                              : xGlossaryI18n::$GLOSSARY_HELP["en"];
		return array($output, 'isHTML' => false);
	}
	
	/**************************************************************************
	 * Util/misc functions.                                                   *
	 **************************************************************************/
	
	/*
	 * The function used to extract all nodes in some xml(html) code, that fit
	 * the given conditions (either tag name, or existing attribute(s), or both).
	 * Note that only “root” elements are returned (i.e. if nested elements might
	 * match the filter conditions, they wont be detected).
	 * FIXME: It should of course use the DOM features, but I have problems with
	 * encoding, it seems… So, using a complex regex for now (not as easy/versatile/
	 * flexible/robust/… as the other solution, but this one works!).
	 *
	 * @param string $html The html code to “parse”.
	 * @param array $filters An array of arrays like
	 *                       (tag => <tag>, attrs => <array off attribute names>,
	 *                        key => <nothing for numeric, or name of one of the attributes>)
	 * @return array An array of arrays of the detected entries,
	 *               as an array of (<key> => (content => <html code>,
	 *                                         int_content => <html code>,
	 *                                         attrs => (<attrs_names> => <attr_vals>))).
	 */
	public static function extractXMLElements ($html, $filters) {
		global $wgxGlossarySettings;
		
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) $mtime = microtime(true);
		
		$ret = array();
		foreach ($filters as $kf => $filt) {
			$ret[$kf] = null;
			// We need at least one of (tag name, one-or-more attribute name).
			if (!($filt["tag"] or count($filt["attrs"]))) continue;
			$filt["tag"] ? $tagname = preg_quote($filt["tag"]) : $tagname = "[^ >]+";
			// $attributes will match all parameters in “root” element.
			// Will give something like (remember “\” is escape char!):
			// '(?=[^>]* attr_name=["\'][^>])'
			// '(?:\s+|attr_name=(?P<attr_name>"(?:\\"|[^"])*"|'(?:\\\'|[^\'])*\')|…'
			// '|[^=]*=(?:"(?:\\"|[^"])*"|\'(?:\\\'|[^\'])*\')*',
			// which match all attributes in an element (the look-ahead assertions
			// are here to assure requested attributes are really presents!).
			// Note that “value delimiters” might be either “"” or “'”, and might
			// be escaped inside values…
			$attributes = '(?:\s*|';
			foreach ($filt["attrs"] as $attr) {
				$attr = preg_quote($attr);
				$attributes = '(?=[^>]* ' . $attr . '=["\'][^>])' . $attributes
				            . $attr . '=(?P<' . $attr . '>"(?:[^"]|\\\\")+"'
				            . '|\'(?:[^\']|\\\\\')+\')|';
			}
			// This must be the last case, as it is the most general.
			$attributes .= '[^=]+=(?:"(?:[^"]|\\\\")+"|\'(?:[^\']|\\\\\')+\'))*';
//			$attributes = '(?: |id=(?P<id>"(?:[^"]|\\\\")+"|\'[^\']+\')|[^=]+=(?:"[^"]+"|\'[^\']+\'))*';
//			print ($attributes . "<br/>");
			// This regex will “parse” the piece of html code to extract the elements
			// we need. Note that it is not “fully armored” against strange or invalid html!
			// This a VERY complex recursive regex (took me hours to make it work!).
			// So lets detail it!
			// Note: About opening elements: when no tag name is given, all opening
			//       elements are tested. There seems to be a bug with elements
			//       like “<hr />” (returns a white page, without any exception…).
			//       So I added a negative look-ahead test to exclude these things
			//       explicitly (even though their matching test should fail nicely!).
			// WARNING: This seems to be a VERY sensible regex, prone to strange errors
			//          very quickly…
			$regex = '/'
				  // The initial open element.
				. '<(?P<tagname>'.$tagname.')(?! *\/>)'.$attributes.'\s*>'
					  // Condition: If it is not a closing tag…
					. '(?P<content>(?(?!<\/(?P=tagname)>)'
						  // Condition: If it is an opening tag, try the (recursive) sub-pattern matching…
						. '(?(?=<(?P=tagname)(?: [^>]*|)>)'
							  // The start of the recursive sub-pattern.
							. '(?P<subpattern>'
								  // A nested element of same kind as first opening one,
								. '<(?P=tagname)(?: [^>]*|)>'
									  // Condition: If it is not a closing tag…
									. '(?(?!<\/(?P=tagname)>)'
										  // Condition: If it is an opening one, try recursive pattern…
										. '(?(?=<(?P=tagname)(?: [^>]*|)>)(?P>subpattern)'
										  // Else, consume as much chars as possible, until the next
										  // opening or closing tag…
										. '|((?:.(?!<(?P=tagname)(?: [^>]*|)>)(?!<\/(?P=tagname)>))*.))'
									  // And retest the “extern” condition.
									. ')*'
								  // The matching closing tag!
								. '<\/(?P=tagname)>'//.*?'
							  // End of subpattern.
							. ')'
						  // Else, consume as much chars as possible, until the next
						  // opening or closing tag.
						. '|((?:.(?!<(?P=tagname)(?: [^>]*|)>)(?!<\/(?P=tagname)>))*.))'
					  // And retest the whole “extern” condition.
					. ')*)'
				  // Out-most closing tag.
				. '<\/(?P=tagname)>'
				. '/s';
			$tret = $tels = array();
//			$html = '<div id="b" xg_sortkey="dd"> <div/><div>test<div class="tt">ttt</div></div><div></div></div>';
			preg_match_all($regex, $html, $tels, PREG_SET_ORDER);
//			print($html . "<br/>");
//			print_r($tels);
			foreach ($tels as $el) {
				$attrs = array();
				foreach ($filt["attrs"] as $attrname)
					// Remove “"” or “'” at beginning and end of value…
					$attrs[$attrname] = substr($el[$attrname], 1, -1);
				$ttret = array("content" => $el[0],
				               "int_content" => $el["content"],
				               "attrs" => $attrs);
				if ($filt["key"] and $el[$filt["key"]])
					$tret[substr($el[$filt["key"]], 1, -1)] = $ttret;
				else $tret[] = $ttret;
			}
			$ret[$kf] = $tret;
		}
		// For performances printing…
		if ($wgxGlossarySettings->mShowPerfs) {
			$ms = sprintf("%05d", (int)((microtime(true) - $mtime)*1000));
			print('<span style="color: yellow;">' . __METHOD__ . " (" . $ms ." ms)</span><br/>");
		}
		
		return $ret;
	}
	
	/*
	 * Parse all given parameters, which are supposed in the form <name>=<value>.
	 * @param array $params An array of all parameters strings.
	 * @return array A mapping (<param_name> => <param_value>). Note that both
	 *               names and values are trimed, and that names are also “lowered”.
	 */
	public static function getParameters($params = array()) {
		$ret = array();
		if (!$params) return $ret;
		foreach ($params as $p) {
			list($name, $value) = explode("=", $p, 2);
			if ($name) $ret[strtolower(trim($name))] = trim($value);
		}
		return $ret;
	}
	
	/* 
	 * Given a string like “(id=the id;class=a "dummy" class\; ignore it!;)(test=another value…)”,
	 * it returns an array with one element for each “()” group, containing unescaped key/values.
	 * @param string $param The string to parse…
	 * @return array All parameters, as an array of mappings (<key> => <value>).
	 */
	public static function getSubParameters($param) {
		// First, explode the () groups.
		$ret = array();
		preg_match_all("/\(((?:[^\(\)]|(?<=\\\\)[\(\)])*)\)/s", $param, $ret, PREG_SET_ORDER);
		// Capture the key and the value.
		$regex = "/\s*(?P<key>\w*)=(?P<value>(?:[^;]|(?<=\\\\);)*)(;|$)/s";
		foreach ($ret as $k => $v) {
			// Unescape parenthesis…
			$v = preg_replace("/\\\\([\(\)])/", "$1", $v[1]);
			$ret[$k] = $tmatch = array();
			preg_match_all($regex, $v, $tmatch, PREG_SET_ORDER);
			foreach ($tmatch as $tm)
				// Unescape semi-colons…
				$ret[$k][$tm["key"]] = preg_replace("/\\\\;/", ";", $tm["value"]);
		}
		return $ret;
	}
	
	/*
	 * Return the “display language” to use, either extracting it from parameters,
	 * or using wiki user language.
	 * @param array $params An mapping of all parameters (<name> => <value>).
	 * @return Language The corresponding language object.
	 */
	public static function getDispLang($params) {
		$lng = $params["disp_lang"];
		// If no valid lang passed, get USER one (not site one!).
		return wfGetLangObj($lng ? $lng : false);
	}
	
	/*
	 * Replaces Spaces by underscore…
	 * @param string $str The string to replace spaces.
	 * @return string The string with spaces replaced by underscores…
	 */
	public static function replaceSpaces($str) {
		return preg_replace("/ /", "_", $str);
	}
	
	/*
	 * Parse a piece of wiki text, with given title.
	 *
	 * @param Parser &$parser The current parser object (currently cloned…).
	 * @param string $text The wiki text to parse.
	 * @param string $title The title of the page in which the text is parsed.
	 * @return string The xhtml parsed result.
	 */
	private static function parseText(&$parser, $text, $title) {
		// TODO: Cloning might not be the best solution – but it works!!!!
		//       Else we get some UNIQ-XXX-QINU in result (recursion problems, I think).
		//       Parsing is a real headache in MediaWiki!!!!
		$lparser = clone $parser;
//		$lparser->startExternalParse($title, new ParserOptions(), OT_HTML);
		$ret = $lparser->parse($text, $title, new ParserOptions());
		$ret = $ret->getText();
		return $ret;
	}
/* Note: The “API” solution do not work (remaining UNIQ-XXX-QINU in result!).
	private static function parseText($text, $title) {
		$req = new FauxRequest(array("action" => "parse",
		                             "text"   => $text,
		                             "title"  => $title,
		                             "prop"   => "text",
		                             "format" => "php"));
		$api = new ApiMain($req);
		$api->execute();
		$ret = &$api->getResultData();
		return $ret["parse"]["text"]["*"];
	}
*/	
	/*
	 * Parse the content of given page.
	 * @param Parser &$parser The current parser object (currently cloned…).
	 * @param Title $title The title object of the page to parse.
	 * @param bool $redir Whether to auto follow redirects or not.
	 * @return string The xhtml parsed result.
	 */
	private static function parsePage(&$parser, $title, $redir=true) {
		// TODO: Cloning might not be the best solution – but it works!!!!
		//       Else we get some UNIQ-XXX-QINU in result (recursion problems, I think).
		//       Parsing is a real headache in MediaWiki!!!!
		$lparser = clone $parser;
//		$lparser->startExternalParse($title, new ParserOptions(), OT_HTML);
		$article = new Article($title);
		$rtitle  = $article->followRedirectText($text);
		if ($rtitle !== false and $redir) {
			$title   = $rtitle;
			$article = new Article($title);
		}
		$txt  = $article->getContent();
		$text = $lparser->parse($txt, $title, new ParserOptions());
		$text = $text->getText();
		return $text;
	}
/* Note: The “API” solution do not work (remaining UNIQ-XXX-QINU in result!).
	private static function parsePage(&$parser, $title, $redir=true) {
		$req = array("action" => "parse",
		             "page"   => $title->getPrefixedText(),
		             "prop"   => "text",
		             "format" => "php");
		if ($redir) $req["redirects"] = "yes";
		$req = new FauxRequest($req);
		$api = new ApiMain($req);
		try {
			$api->execute();
		} catch (Exception $e) {
			print("Error parsing a page: " . $e->getMessage() . "(" . $title . ")<br/>");
			return "";
		}
		$ret = &$api->getResultData();
		return $ret["parse"]["text"]["*"];
	}*/
}

function pp($obj) {
	$t = htmlspecialchars(print_r($obj, true));
	return preg_replace("/\n/", "<br />", $t);
}











