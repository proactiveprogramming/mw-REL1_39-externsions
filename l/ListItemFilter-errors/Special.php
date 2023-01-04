<?php // Redistributable under GNU GPLv2 or later: http://www.gnu.org/licenses/gpl.html
class SpecialListItemFilter extends IncludableSpecialPage {
  function __construct() {
    parent::__construct ('ListItemFilter');
  }

  function parse_options ($parameters) {
    $opts = array();
    // fetch query string options
    if ($this->including()) {
      preg_match ('%[?](?:.*&)*k=([^&]*)%', $parameters, $matches);
      $key_string = $matches !== false ? $matches[1] : '';
    } else
      $key_string = $this->getRequest()->getVal ('k');
    // split key list
    $opts['keys'] = preg_split ('%[,;]%', $key_string, -1, PREG_SPLIT_NO_EMPTY);
    // extract page name
    preg_match ('%^([^?]+)%', $parameters, $matches);
    $opts['pagename'] = sizeof ($matches) ? $matches[1] : '';
    return $opts;
  }

  function html_from_page ($pagename) {
    global $wgTitle, $wgUser;
    if ($pagename)
      $title_obj = Title::newFromText ($pagename);
    if ($title_obj)
      $article_obj = new Article ($title_obj);
    $parser = new Parser(); // isolate parsing from parser of the current page
    $parser_options = ParserOptions::newFromUser ($wgUser);
    $parser_options->setEditSection (false);
    if ($article_obj)
      $html_pout = $parser->parse ($article_obj->getContent(), $title_obj, $parser_options);
    if ($pagename and $title_obj and $article_obj and $html_pout) {
      $html_pout->setTOCHTML ('');
      return $html_pout->getText();
    } else
      return '';
  }

  function execute ($parameters) {
    global $wg_SpecialListItemFilter_recursion;
    if ($wg_SpecialListItemFilter_recursion > 0)
      return;

    // initialize and fetch options
    $opts = $this->parse_options ($parameters);
    $output = $this->getOutput();
    $this->setHeaders();

    // parse target page into HTML and extract list items, prevent recursion when including
    $wg_SpecialListItemFilter_recursion++;
    $list_html = $this->html_from_page ($opts['pagename']);
    $wg_SpecialListItemFilter_recursion--;
    preg_match_all ('%<(?:li|h[1-6])>(.*?)</?(?:li|h[1-6]|ol|ul|table)%si', $list_html, $matches);
    // $matches: [0] => array of full patterns, [1] => array of first sub pattern

    // filter list items according to keys
    $secs = array();
    for ($i = 0; $i < sizeof ($matches[0]); $i++)
      if (strpos ($matches[0][$i], '<li') === 0) {
	$line = $matches[1][$i];
	foreach ($opts['keys'] as $k) {
	  if (stripos ($line, $k) !== false || $k == '*') {
	    if (!isset ($secs[$k]))
	      $secs[$k] = array();
	    $secs[$k][] = $line;
	  }
	}
      }

    // ouput of list items by section key
    $tidyconf = array ('output-xhtml' => 1, 'show-body-only' => 1);
    foreach ($opts['keys'] as $k)
      if (isset ($secs[$k]))
	{
	  $output->addHtml ('<h2>' . htmlspecialchars ($k) . "</h2>\n");
	  foreach ($secs[$k] as $li)
	    {
	      if (function_exists ("tidy_repair_string"))
		$li = tidy_repair_string ($li, $tidyconf, 'utf8');
	      else
		$li = htmlspecialchars ($li);
	      $output->addHtml ('<li>' . $li . "</li>\n");
	    }
	}
  }
}
