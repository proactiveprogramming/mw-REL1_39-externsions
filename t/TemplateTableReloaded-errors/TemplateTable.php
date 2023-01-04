<?php
/**
 * TemplateTableReloaded Extension
 *
 * Copyright 2006 CS "Kainaw" Wagner
 * Copyright 2015 Rusty Burchfield
 *
 * Licensed under GPLv2 or later (see COPYING)
 */

$wgExtensionCredits['specialpage'][] = $wgExtensionCredits['parserhook'][] = array(
  'path' => __FILE__,
  'name' => 'TemplateTableReloaded',
  'version' => '2.2',
  'url' => 'https://www.mediawiki.org/wiki/Extension:TemplateTableReloaded',
  'author' => array('Rusty Burchfield'),
  'description' => 'Render a table of parameters passed to a template.',
);

$wgAutoloadClasses['TemplateTableRenderer'] = __DIR__ . '/TemplateTableRenderer.php';
$wgAutoloadClasses['TemplateTableParser'] = __DIR__ . '/TemplateTableParser.php';
$wgAutoloadClasses['SpecialTemplateTable'] = __DIR__ . '/SpecialTemplateTable.php';

$wgMessagesDirs['TemplateTable'] = __DIR__ . "/i18n";
$wgSpecialPages['TemplateTable'] = 'SpecialTemplateTable';
$wgSpecialPageGroups['TemplateTable'] = 'pages';

$wgHooks['ParserFirstCallInit'][] = 'wfTemplateTableInit';
function wfTemplateTableInit($parser) {
  global $wgTemplateTableTagName;
  $parser->setHook($wgTemplateTableTagName, 'wfTemplateTableParserHook');

  return true;
}

function wfTemplateTableParserHook($input, $args, $parser, $frame) {
  $parserOptions = $parser->getOptions();
  $wikiText = TemplateTableRenderer::execute($input, $args, $parserOptions);

  return $parser->recursiveTagParse($wikiText, $frame);
}

$wgHooks['BeforePageDisplay'][] = 'wfTemplateTableLoadAssets';
function wfTemplateTableLoadAssets($out, $skin) {
  $out->addModules('ext.TemplateTableReloaded');

  return true;
}

$wgResourceModules['ext.TemplateTableReloaded'] = array(
  'scripts' => 'assets/script.js',
  'styles' => 'assets/styles.css',
  'localBasePath' => __DIR__,
  'remoteExtPath' => basename(__DIR__)
);

$wgTemplateTableParseDepth = 0;

/* Options */
$wgTemplateTableDefaultRowLimit = 500;
$wgTemplateTableMaxRowLimit = 1000;
$wgTemplateTableDefaultClasses = 'wikitable';
$wgTemplateTableTagName = 'ttable';
