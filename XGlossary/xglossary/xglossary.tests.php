<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing the main “real” actions of this extension, all embedded in a class.
 * File released under the terms of the GNU GPL v3.
 *
 * TODO: Don’t forget to escape all params that are not wiki-parsed (unless mediawiki does it?).
 * @file
 */

// Do not access this file directly…
if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

/*
 * The class embedding all tests.
 */
class xGlossaryTests {
	static public function renderGlossaryTest(&$parser) {
		$ret =
"<h1>xGlossary Tests (v 0.1.3)</h1>"
. "<p>This is the result page of some basic simple auto-tests done by the "
. "xGlossary extension on this wiki server. If some failed, there’s few chances "
. "that the extension works… please report (including this page!). If all "
. "succeeds, it does not guaranty that xGlossary will work –&nbsp;but the "
. "chances are quite good!</p>";
		
		self::testExtractXMLElements($ret);
		self::testGetParameters($ret);
		self::testGetSubParameters($ret);
		self::testGetDispLang($ret);
		
		return $parser->insertStripItem($ret);
	}
	
	// Xml-“parsing” test.
	static private function testExtractXMLElements(&$ret) {
		$ret .= "<h2><code>extractXMLElements</code> test</h2>";
		$failed = false;
		
		$tests = array();
		$tests[] = array("html" => "",
		                 "filt" => array(array("tag" => "div", "attrs" => array("class"), "key" => null)),
		                 "r" => array(array()));
		$tests[] = array("html" => "<div class=\"test\"> <h1>test</h1>  <div>dummy</div></div>",
		                 "filt" => array(array("tag" => "div", "attrs" => array("class"), "key" => null)),
		                 "r" => array(array(array("content" => "<div class=\"test\"> <h1>test</h1>  <div>dummy</div></div>",
		                                    "int_content" => " <h1>test</h1>  <div>dummy</div>",
		                                    "attrs" => array("class" => "test")))));
		$tests[] = array("html" => "<div class=\"test\"> <h1>test</h1>  <div>dummy</div></div>",
		                 "filt" => array(array("tag" => "div", "attrs" => array("class"), "key" => "class")),
		                 "r" => array(array("test" => array("content" => "<div class=\"test\"> <h1>test</h1>  <div>dummy</div></div>",
		                                              "int_content" => " <h1>test</h1>  <div>dummy</div>",
		                                              "attrs" => array("class" => "test")))));
		$tests[] = array("html" => "<div id=\"test\"> <h1>test</h1>  <div>dummy</div></div>"
		                         . "<div id=\"foo\"> <h1>test</h1>  <div/> <div><div id=\"dummy\">dummy</div></div></div>"
		                         . "<div> <h1>test</h1>  <div/> <div id=\"dummy\">dummy</div></div>",
		                 "filt" => array(array("tag" => "div", "attrs" => array("id"), "key" => "id"),
		                                 array("tag" => "div", "attrs" => array(), "key" => null)),
		                 "r" => array(array("test" => array("content" => "<div id=\"test\"> <h1>test</h1>  <div>dummy</div></div>",
		                                                    "int_content" => " <h1>test</h1>  <div>dummy</div>",
		                                                    "attrs" => array("id" => "test")),
		                                    "foo"  => array("content" => "<div id=\"foo\"> <h1>test</h1>  <div/> <div><div id=\"dummy\">dummy</div></div></div>",
		                                                    "int_content" => " <h1>test</h1>  <div/> <div><div id=\"dummy\">dummy</div></div>",
		                                                    "attrs" => array("id" => "foo")),
		                                    "dummy"=> array("content" => "<div id=\"dummy\">dummy</div>",
		                                                    "int_content" => "dummy",
		                                                    "attrs" => array("id" => "dummy"))),
		                              array(array("content" => "<div id=\"test\"> <h1>test</h1>  <div>dummy</div></div>",
		                                          "int_content" => " <h1>test</h1>  <div>dummy</div>",
		                                          "attrs" => array()),
		                                    array("content" => "<div id=\"foo\"> <h1>test</h1>  <div/> <div><div id=\"dummy\">dummy</div></div></div>",
		                                          "int_content" => " <h1>test</h1>  <div/> <div><div id=\"dummy\">dummy</div></div>",
		                                          "attrs" => array()),
		                                    array("content" => "<div> <h1>test</h1>  <div/> <div id=\"dummy\">dummy</div></div>",
		                                          "int_content" => " <h1>test</h1>  <div/> <div id=\"dummy\">dummy</div>",
		                                          "attrs" => array()))));
		foreach ($tests as $test) {
			$t = xGlossaryMain::extractXMLElements($test["html"], $test["filt"]);
			if ($t != $test["r"]) {
				$ret .= "<p>for html: “" . htmlspecialchars($test["html"]) . "” "
				      . "and filters: “" . self::p($test["filt"]) . "” – Failed."
				      . "<table><thead><tr><th>Expected</th><th>Returned</th></tr></thead>"
				      . "<tbody><tr><td>" . self::p($t) . "</td><td>"
				      . self::p($test["r"]) . "</td></tr></tbody></table></p>";
				$failed = true;
			}
		}
		
		if ($failed)
			$ret .= "<p><b style=\"color: red;\"><code>extractXMLElements</code> tests failded!</b></p>";
		else
			$ret .= "<p><b style=\"color: lightgreen;\"><code>extractXMLElements</code> tests suceeded!</b></p>";
	}
	
	// “parameters” test.
	static private function testGetParameters(&$ret) {
		$ret .= "<h2><code>getParameters</code> test</h2>";
		$failed = false;
		
		$tests = array();
		$tests[] = array("p" => array(""), "r" => array());
		$tests[] = array("p" => array("dummy=test", "foo=bar", "baz=45"),
		                 "r" => array("dummy" => "test", "foo" => "bar", "baz" => "45"));
		$tests[] = array("p" => array("test=", "dummy", "foo=bar", "=baz"),
		                 "r" => array("test" => "", "dummy" => "", "foo" => "bar"));
		foreach ($tests as $test) {
			$t = xGlossaryMain::getParameters($test["p"]);
			if ($t != $test["r"]) {
				$ret .= "<p>param: “" . $test["p"] . "” – Failed."
				      . "<table><thead><tr><th>Expected</th><th>Returned</th></tr></thead>"
				      . "<tbody><tr><td>" . self::p($t) . "</td><td>"
				      . self::p($test["r"]) . "</td></tr></tbody></table></p>";
				$failed = true;
			}
		}
		
		if ($failed)
			$ret .= "<p><b style=\"color: red;\"><code>getParameters</code> tests failded!</b></p>";
		else
			$ret .= "<p><b style=\"color: lightgreen;\"><code>getParameters</code> tests suceeded!</b></p>";
	}
	
	// “sub-parameters” test.
	static private function testGetSubParameters(&$ret) {
		$ret .= "<h2><code>getSubParameters</code> test</h2>";
		$failed = false;
		
		$tests = array();
		$tests[] = array("p" => "", "r" => array());
		$tests[] = array("p" => "()", "r" => array(array()));
		$tests[] = array("p" => "(param=p1;test=t4p2)(misc=value;foo=bar;bar=baz)",
		                 "r" => array(array("param" => "p1", "test" => "t4p2"),
		                              array("misc" => "value", "foo" => "bar", "bar" => "baz"))
		                );
		$tests[] = array("p" => "(param=; test=a\;d\(umm\)y test)…, (misc=value;)",
		                 "r" => array(array("param" => "", "test" => "a;d(umm)y test"),
		                              array("misc" => "value"))
		                );
		foreach ($tests as $test) {
			$t = xGlossaryMain::getSubParameters($test["p"]);
			if ($t != $test["r"]) {
				$ret .= "<p>param: “" . $test["p"] . "” – Failed."
				      . "<table><thead><tr><th>Expected</th><th>Returned</th></tr></thead>"
				      . "<tbody><tr><td>" . self::p($t) . "</td><td>"
				      . self::p($test["r"]) . "</td></tr></tbody></table></p>";
				$failed = true;
			}
		}
		
		if ($failed)
			$ret .= "<p><b style=\"color: red;\"><code>getSubParameters</code> tests failded!</b></p>";
		else
			$ret .= "<p><b style=\"color: lightgreen;\"><code>getSubParameters</code> tests suceeded!</b></p>";
	}
	
	// “disp_lang” test.
	static private function testGetDispLang(&$ret) {
		$ret .= "<h2><code>getDispLang</code> test</h2>";
		$failed = false;
		$def_lng = wfGetLangObj(false);
		$cont_lng = wfGetLangObj(true);
		$ret .= "<p><i>This test supposes you have at least 'en' and 'fr' languages available</i></p>";
		$ret .= "<p>current default user language: “" . $def_lng->getCode() . "”.</p>";
		$ret .= "<p>current wiki content language: “" . $cont_lng->getCode() . "”.</p>";
		
		$p = array();
		$t = xGlossaryMain::getDispLang($p);
		if ($t != $def_lng) {
			$ret .= "<p>param: “" . self::p($p) . "” – Failed (returned “"
			      . $t->getCode() . "” instead of “" . $def_lng->getCode() . "”).</p>";
			$failed = true;
		}
		
		$p = array("param" => 'fr', 'test' => "test");
		$t = xGlossaryMain::getDispLang($p);
		if ($t != $def_lng) {
			$ret .= "<p>param: “" . self::p($p) . "” – Failed (returned “"
			      . $t->getCode() . "” instead of “" . $def_lng->getCode() . "”).</p>";
			$failed = true;
		}
		
		$p = array("param" => "test", "disp_lang" => "fr");
		$t = xGlossaryMain::getDispLang($p);
		if ($t->getCode() != 'fr') {
			$ret .= "<p>param: “" . self::p($p) . "” – Failed (returned “"
			      . $t->getCode() . "” instead of an “fr” object).</p>";
			$failed = true;
		}
		
		$p = array("param" => "test", "disp_lang" => "foobar");
		$t = xGlossaryMain::getDispLang($p);
		if ($t != $cont_lng) {
			$ret .= "<p>param: “" . self::p($p) . "” – Failed (returned “"
			      . $t->getCode() . "” instead of “" . $cont_lng->getCode() . "”).</p>";
			$failed = true;
		}
		
		if ($failed)
			$ret .= "<p><b style=\"color: red;\"><code>getDispLang</code> tests failded!</b></p>";
		else
			$ret .= "<p><b style=\"color: lightgreen;\"><code>getDispLang</code> tests suceeded!</b></p>";
	}
	
	static private function p($obj) {
		$t = htmlspecialchars(print_r($obj, true));
		return preg_replace("/\n/", "<br />", $t);
	}
}











