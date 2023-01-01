<?php
/**
 * NAME
 *
 *      <include/>
 *
 * SYNOPSIS
 *
 *      <include src="[URL]" [noesc] [nopre] [svncat] [iframe]
 *                    [wikitext] [linestart="N"]
 *                    [lines="N-M"] [lang="[LANG]"] />
 *
 * INSTALL
 *    1.
 *      Place the folder of the downloaded zip in a directory called 
 *      'SecureInclude' in your 'extensions/' folder.
 *    2.
 *      Add the following code at the bottom of your LocalSettings.php:
 *      
 *      wfLoadExtension( 'SecureInclude' );
 *    3.
 *      Navigate to Special:Version on your wiki to verify that the 
 *      extension is successfully installed.
 *
 * CONFIGURATION
 *     You can add the following configuration settings in LocalSettings.php:
 *
 *     Most features are disabled by default for maximum security. You
 *     have to enable them one by one by setting
 *
 *       $wg_include_allowed_features['...'] = true

 *     Read carefully the security warnings below before doing so.
 *
 *     Available features are
 *       local - inclusion of local files via <include>
 *       remote - inclusion of remote files via <include>
 *       highlight - allow syntax highlighting (needs Extension.SyntaxHighlight_GeSHi)
 * 
 *       shell - allow embedded shell script execution via <shell>
 *       php - allow embedded php script execution via <php>
 *       
 *     TODO: Update & complete following documentation of configuration settings!
 *
 *     Note that these settings allow any document under your DOCUMENT_ROOT to be shared
 *     except LocalSettings.php or any file ending in .conf. You can add other regex patterns
 *     for files that you want to disallow. You can also set $wg_include_allowed_parent_paths
 *     as an array of allowed paths:
 *
 *         $wg_include_allowed_parent_paths = array($_SERVER['DOCUMENT_ROOT'], '/home');
 *
 *     Similarly, you can restrict URL using
 *     $wg_include_allowed_url_regexp and
 *     $wg_include_disallowed_url_regexp. A URL can be included if it
 *     matches one of the regexps $wg_include_allowed_url_regexp and
 *     none of the regexps in $wg_include_disallowed_url_regexp. To
 *     allow including pages from any source, you can set
 *     $wg_include_allowed_url_regexp to '//'
 *
 *     These settings affect local and remote URLs. These do not
 *     affect SVN URLs, and do not affect inclusion using the iframe
 *     attribute.
 *
 *
 * DESCRIPTION
 *
 *     This extension allows you to include the contents of remote and local
 *     files in a wiki article. It can optionally include content in an iframe.
 *
 *     This extension should almost certainly make you concerned about security!
 *     See the INSTALL section. The $wg_include_allowed_parent_paths and
 *     $wg_include_disallowed_regex configuration settings in LocalSettings.php
 *     can help limit access.
 *
 *     Note that external content is only refreshed
 *     when you save the wiki page that contains the <include/>. Changing the
 *     external file WILL NOT update the wiki page until the wiki page is
 *     edited and saved (not merely refreshed in the browser).
 *     You can also instruct the server to refresh the page by adding the
 *     refresh action. See
 *     http://en.wikipedia.org/wiki/Wikipedia:Bypass_your_cache#Server_cache
 *     You can add the following to a wiki page to make it easier to
 *     clear the cache:
 *     <code>{{fullurl:{{NAMESPACE}}:{{PAGENAME}}|action=purge}}</code>
 *
 *     For the latest version go here:
 *         http://gitorious.org/include/include/trees/master
 *
 * ATTRIBUTES
 *
 *      The <include/> tag must always include at least have a 'src' attribute.
 *
 *      src="[URL]"
 *          You must include 'src' to specify the URL of the file to import.
 *          This may be the URL to a remote file or it may be a
 *          local file system path.
 *
 *          Including local paths requires
 *          $wg_include_allowed_features['local'] = true;
 *          Including remote URLs requires
 *          $wg_include_allowed_features['remote'] = true;
 *
 *          WARNING: Chose carefully which one you want to activate.
 *                   Allowing users to include local files may give
 *                   them access to files you should have kept secret
 *                   (like .htpasswd files).
 *
 *                   If you allow remote inclusion, the remote page
 *                   will be fetched by the web server hosting the
 *                   wiki, which may be allowed to access private
 *                   pages (like intranet).
 *
 *      iframe   (needs $wg_include_allowed_features['iframe'] = true;)
 *          This sets tells the extension to render the included file
 *          as an iframe.  If the iframe attribute is included then the
 *          following attributes may also be included to determine how
 *          the iframe is rendered:
 *
 *              width
 *              height
 *              scrolling
 *              frameborder 
 *
 *          Example:
 *
 *              <include iframe src="http://www.noah.org/cgi-bin/pr0n" width="" height="1000px" />
 *
 *      noesc   (needs $wg_include_allowed_features['noesc'] = true;)
 *
 *              WARNING: activating this feature exposes you to
 *                       cross-site scripting attacks from anyone
 *                       having write access to your wiki. Do not
 *                       activate this unless you fully understand the
 *                       consequences and trust all your contributors.
 *
 *          By default <include> will escape all HTML entities in
 *          the included text. You may turn this off by adding
 *          the 'noesc' attribute. It does not take any value.
 *
 *      nopre
 *          By default <include> will add <pre></pre> tags around
 *          the included text. You may turn this off by adding
 *          the 'nopre' attribute. It does not take any value.
 *
 *      nocache
 *          Disable MediaWiki's (relatively agressive) cache. Using
 *          this option, the inclusion will be done each time the page
 *          is loaded. Useful when including a dynamic page from a
 *          remote URL.
 *
 *      wikitext   (needs $wg_include_allowed_features['wikitext'] = true;)
 *          This treats the included text as Wikitext. The text is
 *          passed to the Mediawiki parser to be turned into HTML.
 *
 *      svncat   (needs $wg_include_allowed_features['svncat'] = true;)
 *          This is used for including files from SVN repositories.
 *          This will tell include to use "svn cat" to read the file.
 *          The src URL is passed directly to svn, so it can be any
 *          URL that SVN understands.
 *
 *      lines="range"
 *          Select a line range from the file to include. The range
 *          can be of the form:
 *          - an integer ("42") : select this line
 *          - a comma-separated list of integers ("1, 3, 5") : select
 *            these lines.
 *          - a (comma-separated list of) ranges separated by a hyphen
 *            like "X-Y" : select lines between X and Y (included). If
 *            X and/or Y is omitted, consider the beginning/end of the
 *            file.
 *
 *      from="[STRING]", to="[STRING]", before="[STRING]", after="[STRING]"
 *          Select a range of lines to include according to the
 *          content of the file. For example, to include the file
 *          starting from the line whose content is FOO and stopping
 *          at the line whose content is BAR, one can say
 *
 *              from="FOO" to="BAR"
 *
 *          When using from= and to=, the matched lines are included
 *          in the output. before= and after= are similar except that
 *          the matched lines are excluded from the output.
 *          All of these attribute can take either a string, in which
 *          case the value is the complete content of the line, or a
 *          regexp (including delimiters, like /foo.*bar/), in which
 *          case the regexp is matched against the line content.
 *
 *      lang="[SYNTAX]"
 *          (needs
 *             wfLoadExtension( 'SyntaxHighlight_GeSHi' );
 *             $wg_include_allowed_features['highlight'] = true;
 *          )
 *          You may colorize the text of any file that you import.
 *          The value of SYNTAX must be any one managed by GeSHI. When
 *          highlight is activated, the following attributes are
 *          available :
 *
 *          linenums
 *              This will add line numbers to the beginning of each line
 *              of the inluded text file.
 *
 *          linestart="N"
 *              In conjunction with linenums, start numbering lines from
 *              line M instead of counting from 1.
 *
 *          select="range"
 *              Highlight lines selected by range. Range take the same
 *              syntax as the lines attribute above. Requires "highlight" to be
 *              selected. Corresponds to GeSHI's
 *              highlight_lines_extra().
 *
 *          style="css style"
 *              Style of the container (<div> or <pre>) for the code.
 *              For example, use style="border: 0px none white;" to
 *              disable the frame around the code. Corresponds to
 *              GeSHI's set_overall_style().
 *
 * EXAMPLES
 *
 *      Include a file from the local file system:
 *          <include src="/var/www/htdocs/README" />
 *      Include a remote file:
 *          <include src="http://www.google.com/search?q=noah.org" nopre noesc />
 *      Include a local fragment of HTML:
 *          <include src="/var/www/htdocs/header.html" nopre noesc />
 *      Include a local file with syntax highlighting:
 *          <include src="/home/svn/checkout/trunk/include.php" highlight="php" />
 *
 * DEPENDENCIES
 *
 *      For highlight support you will need to enable SyntaxHighlight_GeSHi
 *      which is included in MW since v1.21. see
 *      https://www.mediawiki.org/wiki/Extension:SyntaxHighlight#Installation
 *
 * AUTHOR
 *
 *      Noah Spurrier <noah@noah.org>
 *      http://www.noah.org/wiki/MediaWiki_Include
 *      Matthieu Moy <Matthieu.Moy@imag.fr>
 *      https://gitlab.com/MediawikiInclude/include/tree/master
 *      Edgar Soldin
 *      https://github.com/edeso/SecureInclude/
 *
 * @package extensions
 * @version 8
 * @copyright Copyright 2008 @author Noah Spurrier
 * @copyright Copyright 2013 @author Matthieu Moy 
 * @copyright Copyright 2019 @author Edgar Soldin
 * @license GPLv3 or later
 *
 */
if (! defined('MEDIAWIKI')) {
  die('This file is a MediaWiki extension, it is not a valid entry point');
}

/* Prevent register_global attacks */
$wg_include_allowed_features = Null;
$wg_include_allowed_parent_paths = Null;
$wg_include_allowed_url_regexp = Null;
$wg_include_disallowed_regex = Null;
$wg_include_disallowed_url_regexp = Null;

function ef_include_onParserFirstCallInit(Parser $parser){
  // Create a function hook associating the magic word with the function
  $parser->setHook('include', "ef_include_render");
  $parser->setHook('shell', "ef_include_shell");
  $parser->setHook('php', "ef_include_php");
  global $wgGroupPermissions;
  $wgGroupPermissions['secureinclude']['secureinclude-scripting'] = true;
}

/**
 * ef_include_path_in_regex_list
 *
 * This returns true if the needle_path matches any regular expression in haystack_list.
 * This returns false if the needle_path does not match any regular expression in haystack_list.
 * This returns false if the haystack_list is not set or contains no elements.
 *
 * @param mixed $haystack_list
 * @param mixed $needle_path
 *
 * @access public
 * @return boolean
 */
function ef_include_path_in_regex_list($haystack_list, $needle_path)
{
  // polymorphism. Allow either a string or an Array of strings to be passed.
  if (is_string($haystack_list)) {
    $haystack_list = Array(
      $haystack_list
    );
  }
  // no list, nothing allowed
  if ( !is_array($haystack_list) || count($haystack_list) < 1) {
    return false;
  }

  foreach ($haystack_list as $p) {
    if (preg_match($p, $needle_path)) {
      return true;
    }
  }
  return false;
}

/**
 * ef_include_path_in_allowed_list
 *
 * This returns true if the given needle_path is a subdirectory of any
 * directory listed in haystack_list. Similar to
 * ef_include_path_in_regex_list, but does not not allow regular
 * expression, in $haystack_list.
 *
 * @param mixed $haystack_list
 * @param mixed $needle_path
 * @access public
 * @return boolean
 */
function ef_include_path_in_allowed_list($haystack_list, $needle_path)
{
  // polymorphism. Allow either a string or an Array of strings to be passed.
  if (is_string($haystack_list)) {
    $haystack_list = Array(
      $haystack_list
    );
  }
  // no list, nothing allowed
  if ( !is_array($haystack_list) || count($haystack_list) < 1) {
    return false;
  }

  foreach ($haystack_list as $path) {
    $path = realpath($path);
    // path does not exist
    if ( !$path ) continue;
    // succeed only if requested path starts with an allowed absolute path 
    if ( strpos( $needle_path, $path ) === 0 ) return true;
  }
  return false;
}

/* helper function for ef_include_is_regexp */
function ef_include_trap_error()
{
  global $wg_include_error_trapped;
  $wg_include_error_trapped = true;
}

/**
 * ef_include_is_regexp
 *
 * Check whether $reg_exp is a valid regular expression (including
 * delimiters, like /foo/).
 *
 * @param string $reg_exp
 *          The expression to check
 *          
 * @access public
 * @return boolean
 */
function ef_include_is_regexp($reg_exp)
{
  global $wg_include_error_trapped;
  $wg_include_error_trapped = false;
  $sPREVIOUSHANDLER = set_error_handler('ef_include_trap_error');
  preg_match($reg_exp, '');
  restore_error_handler($sPREVIOUSHANDLER);
  return ! $wg_include_error_trapped;
}

/**
 * ef_include_match
 *
 * Check whether a string matches with a regular expression (either a
 * string or a /regexp/)
 *
 * @param string $regexp_or_string
 *          The expression to match with
 * @param string $to_match
 *          String to match
 *          
 * @access public
 * @return boolean
 */
function ef_include_match($regexp_or_string, $to_match)
{
  if (ef_include_is_regexp($regexp_or_string))
    return preg_match($regexp_or_string, $to_match);
  else
    return $to_match === $regexp_or_string;
}

/**
 * ef_include_extract_line_range_maybe
 *
 * Extract a line range from a multi-line string.
 *
 * @param string $output
 *          Multi-line string from which to do the extraction
 * @param string $lines
 *          Line range to extract
 * @param integer $startline
 *          If not set before calling the function,
 *          this variable is set to the first line extracted.
 *          
 * @access public
 * @return boolean
 */
function ef_include_extract_line_range_maybe($output, $argv, &$startline)
{
  if ((! isset($argv['lines'])) && (! isset($argv['after'])) && (! isset($argv['before'])) && (! isset($argv['from'])) && (! isset($argv['to'])))
    return $output;

  $output_a = explode("\n", $output);
  if (isset($argv['lines'])) {
    $array = ef_include_parse_range($argv['lines'], count($output_a));
  } else {
    $array = range(1, count($output_a));
  }

  $computed_startline = - 1;
  $i = 0;
  $in_regexp = ! isset($argv['after']) && ! isset($argv['from']);

  foreach ($array as $line) {
    // $array is indexed from 1, but $output_X are indexed
    // from 0, hence the -1.
    $index = $line - 1;

    if (isset($argv['from']) && ef_include_match($argv['from'], $output_a[$index]))
      $in_regexp = true;

    if (isset($argv['before']) && ef_include_match($argv['before'], $output_a[$index]))
      break;

    if ($in_regexp) {
      $output_b[$i] = $output_a[$index];
      $i ++;
      if ($computed_startline == - 1)
        $computed_startline = $line;
    }

    if (isset($argv['after']) && ef_include_match($argv['after'], $output_a[$index]))
      $in_regexp = true;

    if (isset($argv['to']) && ef_include_match($argv['to'], $output_a[$index]))
      break;
  }
  if ($i == 0)
    return "";
  // When extracting lines X-Y, start counting at X unless asked
  // otherwise.
  if (! isset($startline)) {
    $startline = $computed_startline;
  }

  $output = join("\n", $output_b);
  return $output;
}

/**
 * ef_include_parse_range
 *
 * Parse a line-range string, and return a list of line numbers. For
 * example:
 *
 * "42" => (42)
 * "1,4,12" => (1 4 12)
 * "1,4-12" => (1 4 5 6 7 8 9 10 11 12)
 * "-3" => (1 2 3)
 * "3-" => (3 4 5 ... untill end of file)
 *
 * @param string $range
 *          The range string to parse.
 * @param integer $last_lineno
 *          Number of the last line in file.
 *          
 * @access public
 * @return boolean
 */
function ef_include_parse_range($range, $last_lineno)
{
  $res = array();
  $array = explode(",", $range);
  foreach ($array as $elem) {
    if (preg_match('/^ *([0-9]+) *$/', $elem, $matches)) {
      $res[] = intval($matches[1]);
    } else if (preg_match('/^ *([0-9]*) *- *([0-9]*) *$/', $elem, $matches)) {
      if ($matches[1] == "") {
        // lines="-12" mean start from first line.
        $start = 1;
      } else {
        $start = intval($matches[1]);
      }

      if ($matches[2] == "") {
        // lines="42-" mean finish at last line.
        $end = $last_lineno;
      } else {
        $end = intval($matches[2]);
      }
      if ($start < 1)
        $start = 1;
      if ($end > $last_lineno)
        $end = $last_lineno;
      for ($i = $start; $i <= $end; $i ++) {
        $res[] = $i;
      }
    }
  }
  return $res;
}

/**
 * ef_include_geshi_syntax_highlight
 *
 * Apply syntax-highlighting using GeSHI.
 *
 * @param string $output
 *          Text to syntaxe-highlight.
 * @param array $argv
 *          Parameters given to the <include /> tag.
 *          
 * @access public
 * @return boolean
 */
// function ef_include_geshi_syntax_highlight($output, $argv)
// {
//   if (preg_match('/([a-zA-Z0-9+]+)/', $argv['highlight'], $matches)) {
//     // If the language string contains garbage but still matches a
//     // language name somewhere, take just the language name.
//     $lang = $matches[1];
//   } else {
//     $lang = "c";
//   }
//   $geshi = new GeSHi($output, $lang);
//   if (isset($argv['nopre'])) {
//     $geshi->set_header_type(GESHI_HEADER_NONE);
//   } else {
//     $geshi->set_header_type(GESHI_HEADER_PRE);
//   }
//   if (isset($argv['style'])) {
//     $geshi->set_overall_style(htmlspecialchars($argv['style']));
//   }
//   if (isset($argv['select'])) {
//     $array = ef_include_parse_range($argv['select'], substr_count($output, "\n") + 1);
//     $geshi->highlight_lines_extra($array);
//   }

//   if (isset($argv['linenums'])) {
//     $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
//     if (isset($argv['linestart'])) {
//       // intval to make sure we don't pass arbitrary
//       // string to geshi for security reasons.
//       $geshi->start_line_numbers_at(intval($argv['linestart']));
//     }
//   }
//   $output = $geshi->parse_code();
//   return $output;
// }

/**
 * ef_include_render_iframe
 *
 * Generate an iframe including the remote code.
 *
 * @param array $argv
 *          Parameters given to the <include /> tag.
 *          
 * @access public
 * @return boolean
 */
function ef_include_render_iframe($argv)
{
  if (isset($argv['frameborder']))
    $frameborder = htmlspecialchars($argv['frameborder']);
  else
    $frameborder = '1';
  if (isset($argv['scrolling']))
    $scrolling = htmlspecialchars($argv['scrolling']);
  else
    $scrolling = 'yes';
  if (isset($argv['width']))
    $width = htmlspecialchars($argv['width']);
  else
    $width = '100%';
  if (isset($argv['height']))
    $height = htmlspecialchars($argv['height']);
  else
    $height = '100%';

  return '<iframe src="' . htmlspecialchars($argv['src']) . '" frameborder="' . $frameborder . '" scrolling="' . $scrolling . '" width="' . $width . '" height="' . $height . '">iframe</iframe>';
}

/**
 * ef_include_check_remote_url
 *
 * Checks whether a remote URL is allowed.
 *
 * @param string $src_path
 *          URL to check.
 *          
 * @access public
 * @return mixed (True if the URL is allowed, string error message
 *         otherwise)
 */
function ef_include_check_remote_url($src_path)
{
  global $wg_include_allowed_features;
  global $wg_include_disallowed_url_regexp;
  global $wg_include_allowed_url_regexp;

  if ( @$wg_include_allowed_features['remote'] !== true )
    return "Not allowed to include remote URLs!";

  // Errors in parse_url generating a warning also return
  // false. Since we check for false right after, we don't
  // need/want to see the warning.
  $old_report_level = error_reporting(E_ERROR);
  $parsed = parse_url($src_path);
  error_reporting($old_report_level);

  if ($parsed === false or ! isset($parsed['scheme']) or $parsed['scheme'] == "")
    return htmlspecialchars($src_path) . " does not look like a URL, and doesn't exist as a file.";

  // file:// URLs would be _dangerous_, since they bypass
  // the $wg_include_allowed_parent_paths test, and
  // therefore allow things like file:///etc/passwd.
  // Be safe: fuzzy match for anything containing 'file'.
  if (preg_match('/file/', $parsed['scheme']))
    return "file:// URLs not allowed.";

  if (ef_include_path_in_regex_list($wg_include_disallowed_url_regexp, $src_path))
    return "URL " . htmlspecialchars($src_path) . " in disallowed list.";
  if (! ef_include_path_in_regex_list($wg_include_allowed_url_regexp, $src_path))
    return "URL " . htmlspecialchars($src_path) . " not in allowed list.";
  // URL is allowed.
  return True;
}

/**
 * ef_include_check_local_file
 *
 * Checks whether a local file can be included.
 *
 * @param string $src_path
 *          path name to check.
 *          
 * @access public
 * @return mixed (True if the path is allowed, string error message
 *         otherwise)
 */
function ef_include_check_local_file($src_path)
{
  global $wg_include_allowed_features;
  global $wg_include_allowed_parent_paths;
  global $wg_include_disallowed_regex;

  // general permission
  if (! $wg_include_allowed_features['local'])
    return "Not allowed to include local files.";
  // in path list?
  if (! ef_include_path_in_allowed_list($wg_include_allowed_parent_paths, $src_path)) {
    return "'" . htmlspecialchars($src_path) . "' is not a child of any path in \$wg_include_allowed_parent_paths. '" . htmlspecialchars(implode('; ', array_map(function ($val) {
      $path = realpath($val);
      return $path ? $path : $val . ' [not found]';
    }, $wg_include_allowed_parent_paths))) . "'";
  }
  // in regex list?
  if (ef_include_path_in_regex_list($wg_include_disallowed_regex, $src_path)) {
    return htmlspecialchars($src_path) . " matches a pattern in \$wg_include_disallowed_regex.";
  }
  // openable?
  if ((! is_readable($src_path)) || is_dir($src_path)) {
    // purposely the same message for unreadable files and
    // directories, to avoid leaking information.
    return "Cannot open file " . htmlspecialchars($src_path) . ".";
  }
  // Local file is allowed.
  return True;
}

/**
 * ef_include_render
 *
 * This is called automatically by the MediaWiki parser extension system.
 * This does the work of loading a file and returning the text content.
 * $argv is an associative array of arguments passed in the <include> tag as
 * attributes.
 *
 * @param mixed $input
 *          string
 * @param mixed $argv
 *          associative array
 * @param mixed $parser
 *          Parser
 * @param mixed $parser
 *          PPFrame
 *          
 * @access public
 * @return string
 */
function ef_include_render($input, $argv, $parser, $frame)
{
  global $wg_include_highlighter_package;
  global $wg_include_allowed_features;
  global $wg_include_allowed_parent_paths;
  global $wg_include_disallowed_regex;
  global $wg_include_allowed_url_regexp;
  global $wg_include_disallowed_url_regexp;

  // $argv['nocache'] = true;
  // http://www.mediawiki.org/wiki/Extensions_FAQ#How_do_I_disable_caching_for_pages_using_my_extension.3F
  if (array_key_exists('nocache', $argv)) {
    $parser->getOutput()->updateCacheExpiry(0);
  }

  $error_msg_prefix = "<b>ERROR</b> in " . htmlspecialchars(basename(__FILE__)) . ": ";

  foreach ($argv as &$a) {
    if (isset($a)) {
      $a = $parser->recursivePreprocess($a, $frame);
    }
  }

  if (! isset($argv['src'])) {
    return ef_include_get_errors("<include> tag is missing 'src' attribute.");
  }

  // iframe option...
  // Note that this does not check that the iframe src actually exists.
  // I also don't need to check against $wg_include_allowed_parent_paths or $wg_include_disallowed_regex
  // because the iframe content is loaded by the web browser and so security
  // is handled by whatever server is hosting the src file.
  if (isset($argv['iframe'])) {
    if (! $wg_include_allowed_features['iframe'])
      return ef_include_get_errors("'iframe' feature not activated for include.");
    return ef_include_render_iframe($argv);
  }

//   if (isset($argv['shell'])) {
//     if (! $wg_include_allowed_features['shell'])
//       return ef_include_get_errors("'shell' feature not activated for include.");

//     // $result = Shell::command( $argv['src'] )
//     // // ->environment( [ 'MW_CPU_LIMIT' => '0' ] )
//     // // ->limits( [ 'time' => 300 ] )
//     // ->execute();

//     // $exitCode = $result->getExitCode();
//     // $output = $result->getStdout();
//     // $error = $result->getStderr();

//     $cmd = "sh -c " . escapeshellarg($argv['src']) . "";
//     exec($cmd, $output, $return_var);

//     return $output;
//   }

  // cat file from SVN repository...
  if (isset($argv['svncat'])) {
    if (! $wg_include_allowed_features['svncat'])
      return ef_include_get_errors("'svncat' feature not activated for include.");

    $cmd = "svn cat " . escapeshellarg($argv['src']);
    exec($cmd, $output, $return_var);
    // If plain 'svn cat' fails then try again using 'svn cat
    // --config-dir=/tmp'. Plain 'svn cat' worked fine for months
    // then just stopped.
    // Adding --config-dir=/tmp is a hack that fixed it, but
    // I only want to use it if necessary. I wish I knew what
    // the root cause was.
    if ($return_var != 0) {
      $cmd = "svn cat --config-dir=/tmp " . escapeshellarg($argv['src']);
      exec($cmd, $output, $return_var);
    }
    if ($return_var != 0)
      return ef_include_get_errors("could not read the given src URL using 'svn cat'.\ncmd: $cmd\nreturn code: $return_var\noutput: " . join("\n", $output));
    $output = join("\n", $output);
  } else // load file from URL (may be a local or remote URL)...
  {
    $src_path = realpath($argv['src']);
    if (! $src_path) {
      $msg = ef_include_check_remote_url($argv['src']);
      if (! ($msg === True))
        return ef_include_get_errors($msg);
    } else {
      $msg = ef_include_check_local_file($src_path);
      if (! ($msg === True))
        return ef_include_get_errors($msg);
    }

    // We will generate a clean error message in case fetching a
    // remote URL fails. Don't generate extra warnings.
    $old_report_level = error_reporting(E_ERROR);
    $output = file_get_contents($argv['src']);
    error_reporting($old_report_level);

    if ($output === False)
      return ef_include_get_errors("could not read the given src URL " . htmlspecialchars($argv['src']));
  }

  $output = ef_include_extract_line_range_maybe($output, $argv, $argv['linestart']);

  if (isset($argv['lang'])) {
    if (! $wg_include_allowed_features['highlight'])
      return ef_include_get_errors("'highlight' feature not activated for include.");

    $error = '';
    if (! class_exists('SyntaxHighlight')) {
      $error = ef_include_add_error('Missing SyntaxHighlight_GeSHi extension.');
    } else {
      $status = SyntaxHighlight::highlight($output, $argv['lang'], $argv);
      if ($status->isOK()) {
        $output = $status->getValue();
        //enqueue css so styles are rendered
        $parser->getOutput()->addModuleStyles( 'ext.pygments' );
      } else {
        ef_include_add_error( var_export($status, true) );
        $output = htmlspecialchars($output);
      }
    }
  } elseif (isset($argv['wikitext'])) {
    if (! $wg_include_allowed_features['wikitext'])
      return ef_include_get_errors("'wikitext' feature not activated for include.");

    $parsedText = $parser->parse($output, $parser->mTitle, $parser->mOptions, false, false);
    $output = $parsedText->getText();
  } else if (isset($argv['noesc'])) {
    if (! $wg_include_allowed_features['noesc'])
      return ef_include_get_errors("'noesc' feature not activated for include.");
    // nothing
  } else {
    $output = htmlspecialchars($output);
  }

  if ( ! ef_include_argv_value_is($argv, 'nopre', true) ) {
    $output = "<pre>" . $output . "</pre>";
  }

  // prepend formatted errors, if any
  $output = [
    ef_include_get_errors() . $output
  ];

  // dont touch output further, if nowiki is set
  if (! ef_include_argv_value_is($argv, 'nowiki', false))
    $output['markerType'] = 'nowiki';

    return $output;
}

function ef_include_shell($input, $argv, $parser, $frame)
{
  $checksum = sha1($input);
  $res = ef_include_isEvalAllowed('shell', $checksum);
  if (! $res[0])
    return ef_include_get_errors($res[1]);

  // $output = var_export($input, true);
  $cmd = "sh -c " . escapeshellarg($input) . "2>&1";
  exec($cmd, $output, $return_var);

  $error = '';
  if (isset($res[1])) {
    $error = ef_include_get_errors($res[1]);
    $parsedText = $parser->parse($error, $parser->mTitle, $parser->mOptions, false, false);
    $error = $parsedText->getText();
  }
  return $error . implode("\n", $output);
}

function ef_include_php($input, $argv, $parser, $frame)
{
  $input = trim($input);
  $checksum = sha1($input);
  $res = ef_include_isEvalAllowed('php', $checksum);
  if (! $res[0])
    return ef_include_get_errors($res[1]);

  if (isset($res[1]))
    ef_include_add_error($res[1]);

  $output = '';
  ob_start();
  try {
    eval( $input );
  }catch (Error $e) {
     ef_include_add_error("'{$e->getMessage()}' in line {$e->getLine()}");
  }
  $output = ob_get_clean();

  $output = [
    ef_include_get_errors() . $output
  ];
  return $output;
}

function ef_include_isEvalAllowed( $mode, $checksum = null ) {
  // are enabled globally?
  global $wg_include_allowed_features;
  if ( !$wg_include_allowed_features[$mode] )
    return [ false, "'${mode}' feature not activated for include." ];

  // enabled via checksum?
  global $wg_include_allowed_checksums;
  $checksum_ok = $checksum && isset($wg_include_allowed_checksums[$mode]) && is_array($wg_include_allowed_checksums[$mode]) && in_array($checksum, $wg_include_allowed_checksums[$mode]);

  $group = 'secureinclude';
  $right = 'secureinclude-scripting';
  // enabled via user?
  global $wgUser, $wg_include_allowed_users, $wgOut;
  $logged_in = $wgUser && $wgUser->isLoggedIn();
  $edit_ok = $logged_in && $wgUser->isAllowed( 'edit' );
  $script_ok = $edit_ok && in_array($right,$wgUser->getRights());
  // test if latest revision is from same user
  $revUserId='';
  if ( $wgOut && $wgOut->getContext() && $wgOut->getContext()->canUseWikiPage() &&
    $wgOut->getContext()->getWikiPage() && 
    $wgOut->getContext()->getWikiPage()->getRevisionRecord() &&
    $wgOut->getContext()->getWikiPage()->getRevisionRecord()->getUser() )
    $revUserId = $wgOut->getContext()->getWikiPage()->getRevisionRecord()->getUser()->getId();
  $lastEdit_ok = $wgUser->getId() === $revUserId;

  if (! $checksum_ok) {
    $prohibited = "Executing this '{$mode}' code is currently prohibited";
    $user = "the currently logged in user '{$wgUser->mName}'";
    if ( !$logged_in )
      return [
        false,
        "$prohibited because \$wg_include_allowed_checksums[$mode] does not contain a matching checksum!"
        ];
    elseif (! $script_ok)
      return [
        false,
        "$prohibited because $user is no member of group '$group' and \$wg_include_allowed_checksums[$mode] does not contain a matching checksum!"
      ];
    elseif (! $lastEdit_ok)
      return [
        false,
        "$prohibited because someone else than $user has edited the page inbetween and \$wg_include_allowed_checksums[$mode] does not contain a matching checksum! <br>
Doublecheck the changes and make sure they don't pose a security risk. Afterwards do some minor edit and save the page so you are the latest editor!"
      ];
    else
      return [
        true,
        "Executing this '{$mode}' code with checksum '{$checksum}' is '''only temporarily allowed''' during editing. To make it permanent add the checksum to '''\$wg_include_allowed_checksums['$mode']''' !"
        ];
  } else {
     return [ true ];
  }

  // should never reach here
  return [ false, "Executing this '{$mode}' code is currently prohibited. Dunno why." ];
}

function ef_include_add_error(string $message)
{
  global $ef_include_errors;
  $fileinfo = 'no_file_info';
  $backtrace = debug_backtrace();
  if (! empty($backtrace[0]) && is_array($backtrace[0]) && is_array($backtrace[1])) {
    $fileinfo = basename($backtrace[0]['file']) . "::" . $backtrace[1]['function'] . ' (line ' . $backtrace[0]['line'] . ')';
  }
  $error_msg_prefix = "<b>ERROR</b> in " . htmlspecialchars($fileinfo);
  $error = '<p class="errorbox">' . $error_msg_prefix . ' - ' . htmlspecialchars($message) . '</p>';
  $ef_include_errors[] = $error;
}

function ef_include_get_errors(string $message = null)
{
  global $ef_include_errors;
  if ($message)
    ef_include_add_error($message);
  $error = ($ef_include_errors ? join($ef_include_errors) : '');
  $ef_include_errors = [];
  return $error;
}

/**
 * compare a (list of) value(s) against an argv entry.
 * comparison is as follows
 * .'true' and 'false' keep their meaning (case is ignored)
 * .defined key but empty value equals true
 * .undefined key equals null (to allow a default setting)
 *
 * eg. ef_include_argv_value_is( $argv, 'nowiki', [ true, null, 'Bernd' ] );
 *
 * @param
 *          array of args $argv
 * @param string $key
 * @param mixed $value
 *          (array of values or plain boolean or string value)
 * @return boolean
 */
function ef_include_argv_value_is(array $argv, string $key, $value)
{
  if (is_array($value)) {
    $in_array = false;
    foreach ($value as $value_entry) {
      if (ef_include_argv_value_is($argv, $key, $value_entry))
        $in_array = true;
    }
    return $in_array;
  }

  if (! array_key_exists($key, $argv))
    if ($value === null)
      return true;
    else
      return false;

  if (is_bool($value))
    $value = ($value) ? 'true' : 'false';
  $argv_value = empty($argv[$key]) ? 'true' : $argv[$key] . "";
  // false == 'false', true == 'true' or ''
  return (strtolower($value) === strtolower($argv_value));
}
?>
