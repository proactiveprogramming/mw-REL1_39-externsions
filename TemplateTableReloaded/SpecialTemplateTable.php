<?php
/**
 * SpecialTemplateTable
 *
 * Copyright 2015 Rusty Burchfield
 *
 * Licensed under GPLv2 or later (see COPYING)
 */

class SpecialTemplateTable extends SpecialPage {
  function __construct() {
    parent::__construct('TemplateTable', 'read');
  }

  function execute($articleName) {
    $output = $this->getOutput();
    $request = $this->getRequest();

    $args = $request->getQueryValues();
    unset($args['title']);  // Is title of page, not a param for ttable

    $template = $request->getVal('template', $articleName);
    unset($args['template']);

    $pageTitle = 'Template Table';
    $pageTitle .= empty($template) ? '' : ' - ' . $template;
    $output->setPageTitle($pageTitle);

    // For the tag, empty values are meaninfully different from absent.  For
    // this page, treat empty the same as missing so the form works reasonably.
    foreach ($args as $key => $value) {
      if (empty($value)) {
        unset($args[$key]);
      }
    }

    $topMessage = <<<EOM
Enter a template name to see a table containing a list of where it is used
and what values it is supplied.  For additional details, visit
<a target="_blank" href="https://www.mediawiki.org/wiki/Extension:TemplateTableReloaded">TemplateTableReloaded on mediawiki.org</a>.
EOM;
    $output->addHTML($topMessage);
    $output->addHTML($this->generateForm($template, $request));

    if (!empty($template)) {
      $parserOpts = ParserOptions::newFromContext($output->getContext());
      $table = TemplateTableRenderer::execute($template, $args, $parserOpts);
      $output->addWikiText($table);
    }
  }

  private function generateForm($template, $request) {
    global $wgScript;
    $selfTitle = SpecialPage::getTitleFor($this->getName());

    $out = Xml::openElement(
      'form',
      array('method' => 'get', 'action' => $wgScript)
    );
    $out .= Html::hidden('title', $selfTitle->getPrefixedDbKey());

    if ($request->getVal('caption', '') != '') {
      $out .= Html::hidden('caption', $request->getVal('caption', ''));
    }
    if ($request->getVal('class', '') != '') {
      $out .= Html::hidden('class', $request->getVal('class', ''));
    }

    $out .= Xml::openElement('fieldset');
    $out .= Xml::element('legend', null, 'Template Table Options');
    $out .= Xml::openElement('table');

    $out .= Xml::openElement('tr');
    $out .= "<td class='mw-label'>" .
      Xml::label('Template', 'tt-template') .
      "</td><td class='mw-input'>" .
      Xml::input('template', 30, $template, array('id' => 'tt-template')) .
      "</td>";
    $out .= "<td class='mw-label'>" .
      Xml::label('Headers', 'tt-headers') .
      "</td><td class='mw-input'>" .
      Xml::input('headers', 30, $request->getVal('headers', ''), array('id' => 'tt-headers')) .
      "</td>";
    $out .= Xml::closeElement('tr');

    $out .= Xml::openElement('tr');
    $out .= "<td class='mw-label'>" .
      Xml::label('Categories', 'tt-categories') .
      "</td><td class='mw-input'>" .
      Xml::input(
        'categories',
        30,
        $request->getVal('categories', ''),
        array('id' => 'tt-categories')
      ) . "</td>";
    $out .= "<td class='mw-label'>" .
      Xml::label('Limit', 'tt-limit') .
      "</td><td class='mw-input'>" .
      Xml::input('limit', 5, $request->getVal('limit', ''), array('id' => 'tt-limit')) . ' ' .
      Xml::checkLabel(
        'Hide article',
        'hidearticle',
        'tt-hidearticle',
        ($request->getVal('hidearticle', '') != '')
      ) . "</td>";
    $out .= Xml::closeElement('tr');

    $out .= Xml::openElement('tr');
    $out .= "<td class='mw-label'>" .
      Xml::label('Header formatter', 'tt-headerformatter') .
      "</td><td class='mw-input'>" .
      Xml::input(
        'headerformatter',
        30,
        $request->getVal('headerformatter', ''),
        array('id' => 'tt-headerformatter')
      ) . "</td>";
    $out .= "<td class='mw-label'>" .
      Xml::label('Cell formatter', 'tt-cellformatter') .
      "</td><td class='mw-input'>" .
      Xml::input(
        'cellformatter',
        30,
        $request->getVal('cellformatter', ''),
        array('id' => 'tt-cellformatter')
      ) . "</td>";
    $out .= Xml::closeElement('tr');

    $out .= "<tr><td class='mw-label'></td><td class='mw-input'>" .
      Xml::submitButton('Submit') .
      "</td></tr>";

    $out .= Xml::closeElement('table');
    $out .= Xml::closeElement('fieldset');
    $out .= Xml::closeElement('form');
    return $out;
  }
}
