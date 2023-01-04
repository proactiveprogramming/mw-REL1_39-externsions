<?php

if(!defined('SIMPLE_TEST')) define('SIMPLE_TEST', '../../../../simpletest/');
require_once(SIMPLE_TEST . 'reporter.php');
require_once(SIMPLE_TEST . 'unit_tester.php');

class HeaderTreeReporter extends HtmlReporter {
    function _getCss() {
        return
            parent::_getCss() .
            '.headerTree-example {font-family: Arial, sans-serif; font-size: 8pt; width: 750px}
             .headerTree-example h1,
             .headerTree-example h2,
             .headerTree-example h3,
             .headerTree-example h4,
             .headerTree-example h5,
             .headerTree-example h6 {padding: 0; margin: 0}
             .headerTree-example .given       { width: 250px; float: left; border: 1px solid #aaa}
             .headerTree-example .expected    { width: 250px; float: left; border: 1px solid #aaa}
             .headerTree-example .received    { margin-left: 502px;  border: 1px solid #aaa}
             .fail .headerTree-example .received    { margin-left: 502px;  border: 1px solid #aaa; background: red; color: black}
             .headerTree-example .given div.headerTree,
             .headerTree-example div.headerTree {padding: 0 0.8em; margin: 0 0.8em; border-left: 3px solid #dfffdf}
             .headerTree-example div.headerTree-h1 {background-color: #1aff1a}
             .headerTree-example div.headerTree-h2 {background-color: #64ff64}
             .headerTree-example div.headerTree-h3 {background-color: #87ff87}
             .headerTree-example div.headerTree-h4 {background-color: #a8ffa8}
             .headerTree-example div.headerTree-h5 {background-color: #dfffdf}
             .headerTree-example div.headerTree-h6 {background-color: #ffffff}
             .headerTree-example div.special {background: yellow}
             .headerTree-example div.question {background: orange}
             .headerTree-example div.code-cyan {background: cyan}
             .headerTree-example div.code-blue {background: blue}';
    }

    function paintPass($message) {
        if(0 === strpos($message, '<!-- DISPLAY PASS -->')) {
            print $message;
        }
    }

    function paintFail($message) {
        print "<div class='fail'>$message</div>";
    }

}

$suite = new TestSuite('HeaderTree');
$suite->addTestFile('make_tree.php');
$suite->addTestFile('apply_rules.php');
$suite->addTestFile('tolerance.php');
$suite->run(new HeaderTreeReporter());
?>