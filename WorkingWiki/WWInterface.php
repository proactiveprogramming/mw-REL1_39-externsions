<?php
/* WorkingWiki extension for MediaWiki 1.13 and later
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

#require_once($wwExtensionDirectory.'/misc.php');
#require_once($wwExtensionDirectory.'/SpecialCustomUpload.php');

# ===== This class provides WorkingWiki's interface to MediaWiki =====

class WWInterface {

	# we do a two-pass process for interpreting xml tags in wikitext -
	# when the parser gives them to us, we make a note of what projects are
	# in use, and store a token for later processing; then when the parser
	# is done, we lock all the projects, do the file operations, and 
	# replace the tokens by actual file contents.  Here we keep track of 
	# what the tokens stand for.
	public $uniq_tokens = array();

	# while parsing a page, there's a default project name to use with
	# tags that don't specify a project.  It's generally the same as the
	# name of the page, but not always.  We set this in 
	# catch_incoming_page(), and in one special case, in
	# display_file_contents().
	public $default_project_name = false;

	# $projects_to_list remembers (by name) projects to be
	# acknowledged in the 'Projects' box on the sidebar (or wherever
	# your skin puts it)
	# $projects_in_use is the same, except it isn't cleared when we
	# make the Projects box - we may need the info later.
	public $projects_to_list = false;
       	public $projects_in_use = array();

	# $cache_file_contents[$projname][$filename] records a
	# project file's contents, if we've come across it, in case it 
	# is needed again.
	# the content of the inner array is either
	#  array('p', path)
	# if the file is stored on a locally mounted filesystem, or
	#  array('c', content, modtime).
	public $cache_file_contents = array();

	# $modified_projects records (by name) projects to be saved
	# at the end of processing, because their structure has been changed
	# by adding project files or something
	protected $modified_projects = array();

	# $error_queue stores error messages during a single parsing
	protected $error_queue = array();

	# $dynamic_placeholder_counter is used in making unique ids
	protected $dynamic_placeholder_counter = 0;

	# cache the list of projects for a page
	protected $projects_list_from_db = null;

	# the key of the text that's being parsed is stored here.
	# Standalone projects use this to find their source files.
	# if it's null, it hasn't been set.  if it's '', we're parsing
	# something that doesn't have a page.
	public $currently_parsing_key = null;

	# name of the page being parsed, or '' if none.
	# this is similar to currently_parsing_key but not identical.
	# it may be set when we're not parsing a page (if we're parsing
	# a .wikitext file that belongs in a page, for example), and
	# it should contain spaces where the above key will contain 
	# underscores.  It's used to set the $(MW_PAGENAME) variable
	# exported to make jobs.
	public $_page_being_parsed = null;

	# ==== Parser hooks =====

	# The entry points - parser hooks and other public functions below -
	# need to catch WWExceptions any time they call private functions in
	# this class or anything in the ProjectDescription class.
	# Private functions in this class don't need to.

	# called to handle content of <source-file ...> tags during parsing.
	# when this happens, we replace the tag by a directive in a comment,
	# which will be dealt with by the WW machinery after the parsing
	# stage is done.
	# At this stage, we record which projects are going to be used, to
	# prepare for the directory locking at the beginning of the next stage.
	public function source_file_hook($text, $args, $parser) {
		global $wwSuppressWorkingDuringImport, $wwContext;
		wwProfileIn( __METHOD__ );
		#wwLog( "in source_file_hook.\n" );
		if ($wwSuppressWorkingDuringImport) {
			return '';
		}
		foreach ($args as $k=>$v) {
			if (!$this->validate_xml_arg($v,$k)) {
				return '';
			}
		}
		$standalone = ( isset($args['standalone']) ?
			preg_match('/^(y|yes|t|true|1)$/i',$args['standalone']) :
			false
		);
		try {
			if ($standalone) {
				$args['project'] = $wwContext->wwStorage->create_standalone_project_name(
					$args['filename']
				);
			} else if ( ! isset($args['project']) ) {
				#wwLog("source-file-hook: use default project " . $this->default_project_name . "\n");
				$args['project'] = $this->default_project_name;
			}
			if ( $args['project'] ) {
				$this->project_is_in_use($args['project']);
			}
		} catch ( WWException $ex ) {
			#wwLog( "exception caught in source_file_hook.\n" );
		}
		$token = "UNIQ-WW-".rand()."-QINU";
		$this->uniq_tokens[$token] = array(
			'tag' => 'source-file', 'args' => $args );
		if ($text === null) {
			$this->uniq_tokens[$token]['declaration-only'] = true;
		}
		wwProfileOut( __METHOD__ );
		return $token;
	}

	# called to handle content of <project-file ...> tags
	public function project_file_hook($text,$args,$parser) {
		global $wwSuppressWorkingDuringImport;
		wwProfileIn( __METHOD__ );
		if ($wwSuppressWorkingDuringImport) {
			return '';
		}
		foreach ($args as $k=>$v) {
			if (!$this->validate_xml_arg($v,$k)) {
				return '';
			}
		}
		try {
			if ( ! isset($args['project']) ) {
				#wwLog("project-file-hook: use default project\n");
				$args['project'] = $this->default_project_name;
			}
			if ( $args['project'] ) {
				$this->project_is_in_use($args['project']);
			}
		} catch ( WWException $ex ) {
			#wwLog( "exception caught in project_file_hook.\n" );
		}
		if ( ( !array_key_exists( 'make', $args ) or $args['make'] == '')
		     and array_key_exists( 'remake', $args ) ) {
			$args['make'] = wwfArgumentIsYes($args['remake']) ? 1 : 0;
			$this->record_warning(
				"The 'remake' attribute is deprecated. "
				. "Please change 'remake=\"" . htmlspecialchars($args['remake']) 
				. "\"' to 'make=\"" . htmlspecialchars($args['remake'])
				. "\"' for future compatibility.");
			unset($args['remake']);
		}
		$token = "UNIQ-WW-".rand()."-QINU";
		$this->uniq_tokens[$token] = array(
			'tag' => 'project-file',
			'args' => $args
		);
		wwProfileOut( __METHOD__ );
		return $token;
	}

	protected function validate_xml_arg($content,$name) {
		if ( #strpos($content,"\x7f") !== false or
		     strpos($content, "-->") !== false ) {
			$this->record_error("Erroneous '".htmlspecialchars($name)
				."' attribute encountered.");
			return false;
		}
		if ( $content == '' and $name != "make" ) {
			$this->record_warning("Empty value for '"
				.htmlspecialchars($name) ."' attribute.");
			return true;
		}
		return true;
	}

	# called to handle content of <toggle-make-enabled ...> tags
	public function toggle_make_enabled_hook($text,$args,$parser) {
		global $wwSuppressWorkingDuringImport;
		wwProfileIn( __METHOD__ );
		if ($wwSuppressWorkingDuringImport) {
			return '';
		}
		$token = "UNIQ-WW-".rand()."-QINU";
		$this->uniq_tokens[$token] = array(
			'tag' => 'toggle-make-enabled',
			'args' => $args
		);
		wwProfileOut( __METHOD__ );
		return $token;
	}

	# called to handle content of <project-description ...> tags
	# as found in the ProjectDescription: namespace.
	# This doesn't parse it, just renders the XML text for viewing.
	public function render_project_description($text, $args, $parser) {
		#wwLog("render project description\n");
		if (isset($args['display']) and $args['display'] == 'none') {
			return;
		}
		$attrs = '';
		foreach($args as $attr=>$val) {
			# SyntaxHighlighter will escape these strings for us
			$attrs .= ' '.$attr.'="'.$val.'"';
		}
		if (preg_match('/<project[^>]*\bname="(.*?)"/i', $text, $matches)) {
			$pname = $matches[1];
		} else {
			$tl = $parser->getTitle();
			if ( $tl->getNamespace() == NS_PROJECTDESCRIPTION ) {
				$pname = $tl->getDBKey();
			} else {
				$pname = $tl->getPrefixedDBKey();
			}
		}
		try {
			if (($pn = ProjectDescription::normalized_project_name($pname,false)) !== null)
				$pname = $pn;
			$this->project_is_in_use($pname);
		} catch (WWException $ex) {
		}
		$text = '<project-description'.$attrs.'>'.
			$text.'</project-description>';
		#wwLog("render project description $pname\n$text\n");
		global $wwMaxLengthForSyntaxHighlighting,
			$wwMaxLengthForSourceCodeDisplay;
		if ( $this->use_syntax_highlighting() and 
		     strlen($text) <= $wwMaxLengthForSyntaxHighlighting ) {
			$text = '<source lang="xml">'.$text.'</source>';
		} else if (strlen($text) <= $wwMaxLengthForSourceCodeDisplay) {
			$text = '<pre>'.htmlspecialchars($text).'</pre>';
		} else {
			$this->record_message(
				"Project description is too long to display."
			);
		}
		global $withinParser;
		++$withinParser;
		$text = $parser->recursiveTagParse( $text );
		--$withinParser;
		//$this->record_error("{$pname} » description");
		//$projectname='';
		return $this->prepend_errors($text);
	}

	# called before parsing.  we take the opportunity to make sure
	# we are drawing our source-files and project-descriptions from
	# the right version of the page, if it's being edited or previewed
	public function catch_incoming_page(&$parser, &$text, &$strip_state) {
		global $wwContext, $withinParser;
		if ($withinParser > 0) {
			return true;
		}
		wwProfileIn( __METHOD__ );
		++$withinParser;
		$title = $parser->getTitle();
		# This timestamp is correct when the page is being saved or
		# previewed, and when viewing old revisions.	The latter is
		# a problem that should be addressed, since when the page is
		# an old version none of the files get remade.
		$timestamp = $parser->getRevisionTimestamp();
		# there seems to be a problem with time zones here.
		# test for userAdjust bug
		global $wgContLang;
		$ts0 = wfTimestampNow();
		$ts1 = $wgContLang->userAdjust( $ts0, '' );
		//if ( $ts0 != $ts1 )
		$correction = (wfTimestamp(TS_UNIX, $ts1) - wfTimestamp(TS_UNIX, $ts0));
		
		//$this->debug_message( "userAdjust changes $ts0 to $ts1: "
		//	. $correction );
		$timestamp = wfTimestamp( TS_MW, 
			wfTimestamp(TS_UNIX, $parser->getRevisionTimestamp()) - $correction );
		#$this->debug_message( "corrected timestamp is "
		#	. wfTimestamp( TS_DB, $timestamp ) );
		try {
			# don't check for article existence, because it fails when
			# you're creating the page from a redlink
			if ( is_object($title) // and $title->getArticleID() != 0
				// caching on ProjectDescription page just causes trouble
			     and $title->getNamespace() != NS_PROJECTDESCRIPTION ) {
				$text = $this->replace_inlines($text);
				if ( $this->currently_parsing_key === null ) {
					$this->currently_parsing_key = $title->getPrefixedDBKey();
				}
				$wwContext->wwStorage->cache_text_directly(
					$text,
					$title->getPrefixedDBKey(),
					$timestamp
				);
			}
		} catch (WWException $ex) {
			# no action on catching an exception: an error's recorded
			# and it'll be reported later
		}
		# Figure out what the default project name for this page is.
		# Generally it's the name of the page.
		if ( $this->default_project_name === false ) {
			$pagename = $title->getPrefixedDBKey();
			$project = $wwContext->wwStorage->find_project_given_page($pagename);
			if (is_null($project)) {
				$this->default_project_name = null;
			} else {
				$this->default_project_name = $project->project_name();
			}
			#wwLog("catch_incoming_page: default project name is "
			#		. $this->default_project_name . "\n");
		}
		--$withinParser;
		wwProfileOut( __METHOD__ );
		return true;
	}

	public function which_project_message( $pagename, $tagtype, $filename ) {
		global $wwContext;
		$components = explode('/', $pagename);
		$message = "<div class='ww-clickto'>Cannot process $tagtype '"
			. htmlspecialchars($filename)
			. "' because it's not clear what project should be the default for this page.	Click below to:<ul>\n";
		#wwLog( "in 'not clear' case: pagename == $pagename\n");
		while (count($components) > 0) {
			$candidate_projname = implode('/', $components);
			$candidate_proj = 
				$wwContext->wwStorage->find_project_by_name($candidate_projname);
			if ($candidate_proj->project_description_page !== null) {
				$message .=
					'<li> '
					. $this->click_to_add_link(
						"Use project '"
						  . htmlspecialchars($candidate_projname)
						  . "' for WorkingWiki files on this page",
						$candidate_proj,
						$filename,
						$pagename,
						$tagtype
					)
					. "</li>\n";
				break;
			} else {
				$message .=
					'<li> '
					. $this->click_to_add_link(
						'Create project \''
						  . htmlspecialchars($candidate_projname) . "'",
						$candidate_proj,
						$filename,
						$pagename,
						$tagtype
					)
					. "</li>\n";
			}
			array_pop($components);
		}
		$message .= "</ul></div>\n";
		return $message;
	}

	# called after parsing, to replace the comments marking project files
	# by the actual file contents.  From here, the real work of the projects
	# is done.
	# There are some considerations about directory locking:
	#  the system can deadlock if one page seeks to lock project A and then
	#   project B, and another one does B and then A.
	#  to prevent this, we use Dijkstra's algorithm: figure out what will
	#   need to be locked, and lock them in alphabetical order.
	# [Unfortunately, that's not enough: if a project generates a .wikitext
	#  file, that will be parsed using a recursive call to the parser.
	#  That wikitext could contain $$ lines and/or project-file tags involving
	#  projects we haven't locked, and it isn't safe to lock them out of
	#  order while the original set of projects are still locked.
	# So we have the inner invocation(s) of render_after_parsing refuse to
	#  do the locking and marker replacement, and the outer one does it
	#  after its unlock.
	# Consequently project files nestled in machine-generated wikitext can
	#  be out of sync with project files referenced directly, because another
	#  process can capture the lock and change the working files in between
	#  these two steps.  We'll just have to live with that.]
	# [Actually this precaution is no longer necessary now that the locking
	#  is done on the ProjectEngine side, but getting rid of it makes the
	#  source files look strange with WW messages inside the dashed outline,
	#  so I'm leaving this how it is for now...]
	public function render_after_parsing( &$parser, &$text ) {
		if ( $parser ) {
			$title = $parser->getTitle();
			if ( $title->getNamespace() >= 0 ) {
				$pagename = $title->getPrefixedDBKey();
			} else {
				$pagename = null;
			}
		} else {
			$pagename = $this->page_being_parsed();
		}
		return $this->expand_tokens( $text, $parser, $pagename );
	}

	public function expand_tokens( &$text, $parser, $pagename ) {
		global $render_after_parsing_recursion_flag;
		if ($render_after_parsing_recursion_flag) {
			return true;
		}
		wwProfileIn( __METHOD__ );
		$render_after_parsing_recursion_flag = true;
		$token_start = -1;
		# control infinite recursion.  I was testing this with a .wikitext file
		# that includes itself, and more than about 90 levels in seems to
		# overwhelm Firefox's DOM as well.
		$times_around = 0;
		$max_times_around = 90;
		do {
			#  argh, this preg_replace seems to be causing core dumps 
			#$replaced = preg_replace(
			#  "/(UNIQ-WW-.*?-QINU)/e",
			#  #"/<!-- ((source|project)-file\x7f.*?) -->/e",
			#  'WWInterface::render_token("\\1",$parser)', $text, -1, $nrepl);
			# Do in stages -
			#  find all the tokens
			#  associate them with projects
			#  do the file processing and displaying
			# This way, when a new file is added, it gets added to its project
			# before any of the processing.
			$token_opener = 'UNIQ-WW-';
			$token_closer = '-QINU';
			$tokens = array();
			$token_start = -1;
			while ( ( $token_start = strpos($text,$token_opener,$token_start+1) )
				  !== false and
				( $token_close = strpos($text,$token_closer,$token_start) )
				  !== false) {
				$token_end = $token_close + strlen($token_closer);
				$token_length = $token_end - $token_start;
				$token = substr($text, $token_start, $token_length);
				# don't do it inside HTML tags, like for instance
				# <span id="...">, or we can get very bad title
				# attributes for something like 
				# ==Section $$2 + \epsilon$$==
				$lt_before = strrpos($text,'<',($token_start - strlen($text)));
				$gt_before = strrpos($text,'>',($token_start - strlen($text)));
				$lt_after = strpos($text,'<',$token_end);
				$gt_after = strpos($text,'>',$token_end);
				if ( $lt_before !== false and $gt_after !== false and
				    ( $lt_before > $gt_before or $gt_before === false ) and
				    ( $gt_after < $lt_after or $lt_after === false ) ) {
					continue;
				}
				# also, seems bad if we do it within a mw:editsection tag.
				$es_open_before = strrpos(
					$text,
					'<mw:editsection',
					($token_start - strlen($text))
				);
				$es_close_before = strrpos(
					$text,
					'</mw:editsection',
					($token_start - strlen($text))
				);
				$es_open_after = strpos($text,'<mw:editsection', $token_end);
				$es_close_after = strpos($text,'</mw:editsection', $token_end);
				if ( $es_open_before !== false and $es_close_after !== false and
				     ( $es_close_before === false or $es_open_before > $es_close_before ) and
				     ( $es_open_after === false or $es_close_after < $es_open_after ) ) {
					     continue;
				}
				if (array_key_exists($token, $this->uniq_tokens)) {
					#$this->uniq_tokens[$token]['start'] = $token_start;
					#$this->uniq_tokens[$token]['length'] = $token_length;
					$tokens[] = array( $token, $token_start, $token_length);
				}
			}
			#wwLog( "render_after_parsing pass 1" );
			foreach ($tokens as $token_arr) {
				list($token, $token_start, $token_length) = $token_arr;
				$this->reconcile_token_with_project( $token, $token_start, $token_length, $pagename, $parser );
				$this->uniq_tokens[$token]['errors'] = $this->report_errors();
			}
			#wwLog( "render_after_parsing pass 2" );
			$newtext = '';
			$text_unread_pos = 0;
			foreach ($tokens as $token_arr) {
				list($token, $token_start, $token_length) = $token_arr;
				$tag = $this->uniq_tokens[$token]['tag'];
				$args = $this->uniq_tokens[$token]['args'];
				#wwLog("pass 2: $token: " . serialize($this->uniq_tokens[$token]));
				if ( isset($this->uniq_tokens[$token]['omit']) ) {
					$file_html = '';
				} else if ($tag == 'toggle-make-enabled') {
					if (!isset($args['enabled'])) {
						$this->record_error(
							"'toggle-make-enabled' tag requires an "
							. "'enabled' attribute.	Ignoring."
						);
						continue;
					}
					global $wwMakeTemporarilyDisabled;
					if ( ! $args['enabled'] ) {
						$wwMakeTemporarilyDisabled = true;
					} else {
						#unset($wwMakeTemporarilyDisabled);
						unset($GLOBALS['wwMakeTemporarilyDisabled']);
					}
					$this->record_message(
						'Notice: making of project files is '
						. (isset($wwMakeTemporarilyDisabled) ? 'disabled' : 'enabled')
						. ' from here down.'
					);
					$file_html = '';
				} else { # $tag is either source-file or project-file.
					$source = ($tag == 'source-file');
					$project = $this->uniq_tokens[$token]['project'];
					try {
						if ( $project ) {
							$project->proactively_sync_if_needed();
						}
						if ( wwRunHooks(
							'WW-RenderProjectFile',
							array($project, $source, $args, &$file_html)
						) ) {
							if ( ! isset($this->uniq_tokens[$token]['failed']) ) {
								if (isset($args['make'])) {
									$args['make'] = wwfArgumentIsYes($args['make']) ? 1 : 0;
								}
								global $withinParser;
								++$withinParser;
								if ($times_around >= $max_times_around - 1) {
									$this->record_error( "Excess recursion in tags" );
									$file_html = '';
								} else {
									$file_html = $this->display_project_file(
										$project,
										$this->uniq_tokens[$token]['file_content'], 
										$source,
										$args,
										$parser
									);
								}
								--$withinParser;
							} else {
								$file_html = '';
							}
						}
					} catch (WWException $ex) {
						$file_html = '';
						if ( $parser ) {
							$parser->disableCache();
							$title = $parser->getTitle();
							if ( $title->getNamespace() != NS_SPECIAL ) {
								$title->invalidateCache();
							}
						}
					}
					#if ($source and $args['is_definition'])
					#	$anchor = 'ww-sf-def-'.htmlspecialchars($args['filename']);
					#else
					#	$anchor = 'ww-pf-'.htmlspecialchars($args['filename']);
					#if (isset($anchor))
					#	$file_html = "<a name=\"$anchor\" id=\"$anchor\"></a>" . $file_html;
					#wwLog("at end of render_token\n");
				}
				$file_html = $this->prepend_errors( $file_html);
				$newtext .=
					substr($text, $text_unread_pos, $token_start - $text_unread_pos)
					. (isset($this->uniq_tokens[$token]['errors']) ?
						$this->uniq_tokens[$token]['errors'] : '')
					. $file_html;
				$text_unread_pos = $token_start + $token_length;
			}
			$text = $newtext
				. $this->report_errors()
				. substr($text, $text_unread_pos);
			++$times_around;
		} while (count($tokens) > 0 and $times_around < $max_times_around);
		$render_after_parsing_recursion_flag = false;
		#wwLog( "render_after_parsing exit" );
		wwProfileOut( __METHOD__ );
		return true;
	}

	# this is pass 1 of render_after_parsing: check the tag data against what's recorded
	# in the project, and make corrections and adjustments to the project as necessary,
	# plus warnings if tag data is weird or wrong.
	# in WW the parser is guaranteed to be non-null, but in non-MW settings it will probably
	# be null, and this function should be overridden.
	public function reconcile_token_with_project( $token, $token_start, $token_length, $pagename, $parser ) {
		#wwLog("$token: " . json_encode($this->uniq_tokens[$token]) );
		global $wwContext;
		$tag = $this->uniq_tokens[$token]['tag'];
		$args = $this->uniq_tokens[$token]['args'];
		$declaration = isset($this->uniq_tokens[$token]['declaration-only']); 
		if ($tag == 'source-file') {
			$source = true;
		} else if ($tag == 'project-file') {
			$source = false;
		} else {
			return;
		}
		$sstr = ($source ? 'source' : 'project');
		try {
			if ( ! isset( $args['filename'] ) or $args['filename'] == '' ) {
				$this->uniq_tokens[$token]['failed'] = true;
				$this->throw_error("Can not find filename for "
				  . "$sstr-file tag.  Please check for typing errors." );
			}
			else if ( ! ProjectDescription::is_allowable_filename( $args['filename'] ) ) {
				$this->uniq_tokens[$token]['failed'] = true;
				$this->throw_error(
					'Prohibited filename \''
					. htmlspecialchars($args['filename'])
					. '\''
				);
			}
			if ( $args['project'] !== null ) {
				$n_projectname = ProjectDescription::normalized_project_name($args['project']);
				if (is_null($n_projectname)) {
					$this->uniq_tokens[$token]['failed'] = true;
					$this->throw_error(
						"Project name '"
						. htmlspecialchars($args['project'])
						. "' not allowed. Contact the WorkingWiki author(s) if this "
						. "is too restrictive."
					);
				}
				$project = $wwContext->wwStorage->find_project_by_name($n_projectname);
				if (is_null($project)) {
					$this->uniq_tokens[$token]['failed'] = true;
					$this->throw_error("Project "
						. htmlspecialchars($n_projectname) . " not found.");
				}
				$this->uniq_tokens[$token]['project'] = $project;
			}
			if ($source and !$declaration) {
				global $wwClickToAdd;
				if ( $project !== null and $pagename !== null ) {
					$sfc = $project->find_source_file_content($args['filename'],$pagename);
					$this->uniq_tokens[$token]['file_content'] =
						isset($sfc['text']) ? $sfc['text'] : null;
					$pfe =& $project->project_files[$args['filename']];
				} else {
					$pfe = null;
				}

				# cases:
				#   source-file tag that is not in project yet
				#   source-file tag that project thinks is somewhere else
				#   project-file tag that project doesn't know about
				#   archived project-file that project thinks is non-archived
				# and a special case if we don't actually know what project
				# the files should belong to (because someone is creating a
				# new subpage).
				if ( ( $pfe === null or !$pfe['source'] ) and !wwfReadOnly()
				     and $pagename !== null and !$this->page_is_history() ) {
					# the project doesn't list this source file
					if ( $args['project'] === null ) {
						$message = $this->which_project_message( $pagename, 'source-file', $args['filename'] );
						$this->record_message($message);
						if ( $parser ) {
							$parser->getOutput()->addModules( 'ext.workingwiki.clicktoadd' );
						}
						$this->uniq_tokens[$token]['omit'] = true;
					} else if ( $wwClickToAdd ) {
						$this->record_message(
							'<div class="ww-clickto">Notice: Project ' 
							. $this->make_manage_project_link(
								$project,
								htmlspecialchars( $project->project_name() )
							)
							. " does not include source-file '" 
							. htmlspecialchars( $args['filename'] )
							. "'. ("
							. $this->click_to_add_link(
								'Click here to add',
								$project,
								$args['filename'],
								$pagename
							)
							. ' it.)</div>'
						);
						if ( $parser ) {
							$parser->getOutput()->addModules( 'ext.workingwiki.clicktoadd' );
						}
						$this->uniq_tokens[$token]['omit'] = true;
					} else {
						$project->add_source_file( array(
							'filename' => $args['filename'],
							'page' => $pagename
						) );
						$this->project_is_modified($project->project_name());
						$this->record_message(
							"Added source file '"
							. htmlspecialchars($args['filename'])
							. "' to project "
							. $this->make_manage_project_link(
								$project,
								htmlspecialchars($project->project_name())
							)
							. '.'
						);
						#wwLog(
						#	count($project->project_files)
						#	. " project files in "
						#	. $project->project_name()
						#);
						if ( $parser ) {
							$parser->disableCache(); # message should go away on reload
						}
					}
					if (isset($sfc['page']) and isset($sfc['text']) and $sfc['text'] == '') {
						$this->record_message(
							'Notice: Source file \''
							. htmlspecialchars($args['filename'])
							. "' is empty.	Is that your intention?"
						);
					}
				} else if (!wwfReadOnly() and !$this->page_is_history()) {
					if (isset($pfe['page'])) {
						$official_page = $pfe['page'];
						$opt = Title::newFromText($official_page);
						if ( $pagename !== null and
						     $opt->getPrefixedDBKey() != $pagename and !wwfReadOnly()) {
							# the project thinks this file is on some other page
							if ( $args['project'] === null ) {
								# special case: we're on a subpage that isn't in use yet -
								# do we create a new project for it or not?
								$components = explode('/', $pagename);
								$message =
									"<div class='ww-clickto'>It's not clear which project source file '"
									. htmlspecialchars($args['filename'])
									. "' should belong to.	Click below to:<ul>\n";
								while (count($components) > 0) {
									$candidate_projname = implode('/', $components);
									$candidate_proj = 
										$wwContext->wwStorage->find_project_by_name($candidate_projname);
									if ($candidate_proj->project_description_page !== null) {
										$message .=
											'<li> ' 
											. $this->click_to_add_link( 
												"Change the location of file '" 
												. htmlspecialchars($args['filename'])
												. "' in project '" 
												. htmlspecialchars($candidate_projname)
												. "'",
												$candidate_proj,
												$args['filename'],
												$pagename
											)
											. " from " 
											. $parser->getUser()->getSkin()->makeLinkObj($opt,$official_page)
											. " to here</li>\n";
										break;
									} else {
										$message .=
											'<li> '
											.  $this->click_to_add_link(
												'Create project \''
												. htmlspecialchars($candidate_projname)
												. "'",
												$candidate_proj,
												$args['filename'],
												$pagename
											)
											. "</li>\n";
									}
									array_pop($components);
								}
								$message .= "</ul></div>\n";
								$parser->getOutput()->addModules( 'ext.workingwiki.clicktoadd' );
								$this->record_message($message);
								$this->uniq_tokens[$token]['omit'] = true;
							} else if (!$wwClickToAdd) {
								$osfc = $project->find_source_file_content(
									$args['filename'],
									$opt->getPrefixedDBKey()
								);
								if (isset($osfc['text'])) {
									# the file is actually at the other location
									$this->record_message(
										"<div class='ww-clickto'>Notice: source-file '" 
										. htmlspecialchars($args['filename'])
										. "' in project "
										. $this->make_manage_project_link(
											$project,
											htmlspecialchars($project->project_name())
										)
										. " is already on page "
										. $parser->getUser()->getSkin()->makeLinkObj($opt,$official_page)
										. ', rather than here on page '
										. htmlspecialchars($pagename) 
										. '. ('
										. $this->click_to_add_link(
											'Click here to replace',
											$project,
											$args['filename'],
											$pagename
										)
										. ' that location with this one.)</div>'
									);
									$parser->getOutput()->addModules( 'ext.workingwiki.clicktoadd' );
								} else {
									# it's not there
									$project->add_source_file( array(
										'filename' => $args['filename'],
										'page' => $pagename
									) );
									$this->project_is_modified($project->project_name());
									$this->record_message(
										"Relocated source file '"
										. htmlspecialchars($args['filename'])
										. "' in project "
										. $this->make_manage_project_link(
											$project,
											htmlspecialchars($project->project_name())
										)
										. " from page "
										. $parser->getUser()->getSkin()->makeLinkObj($opt,$official_page)
										. " to this page."
									);
									$parser->disableCache(); # message should go away on reload
									$this->debug_message( "args is ". htmlspecialchars(serialize($args)) );
								}
							} else {
								$this->record_message(
									'<div class="ww-clickto">Notice: Project '
									. $this->make_manage_project_link(
										$project,
										htmlspecialchars($project->project_name())
									)
									. " records source-file '"
									. htmlspecialchars($args['filename'])
									. "' at page "
									. $parser->getUser()->getSkin()->makeLinkObj($opt,$official_page)
									. ', rather than here on page ' . htmlspecialchars($pagename)
									. '. ('
									. $this->click_to_add_link(
										'Click here to replace',
										$project,
										$args['filename'],
										$pagename
									)
									. ' that location with this one.)</div>'
								);
							}
							if ( $parser ) {
								$parser->getOutput()->addModules( 'ext.workingwiki.clicktoadd' );
							}
						}
					}
				}
			} else { # not a source-file tag: a project-file tag
				if ( $args['project'] === null ) {
					$message = $this->which_project_message( $pagename, 'project-file', $args['filename'] );
					$this->record_message( $message );
					$this->uniq_tokens[$token]['omit'] = true;
					if ( $parser ) {
						$parser->getOutput()->addModules( 'ext.workingwiki.clicktoadd' );
					}
				} else if ( $pagename !== null ) {
					$pfc = $wwContext->wwStorage->find_file_content(
						$args['filename'],
						$project,
						$pagename,
						false
					);
					$this->uniq_tokens[$token]['file_content'] =
						isset($pfc['text']) ? $pfc['text'] : null;
					if ( ! ( $this->page_is_preview()
					     or $this->page_is_history() ) ) {
						if (array_key_exists($args['filename'],$project->project_files)) {
							$pfe =& $project->project_files[$args['filename']];
							$has_app = (isset($pfe['appears']) and $pfe['appears'][$pagename]);
							$has_arch = (isset($pfe['archived']) and $pfe['archived'][$pagename]);
						} else {
							$has_app = $has_arch = false;
						}
						if ( !isset($pfc['text']) and !$has_app and !$has_arch ) {
							$project->add_file_element( array(
								'filename' => $args['filename'],
								'appears' => array($pagename=>true)
							) );
							# can't save during parsing - have to mark it for later saving
							//$wwContext->wwStorage->save_project_description($project);
							$this->project_is_modified($project->project_name());
							//$this->record_warning( "Project file $filename not found "
							//	. "on page {$parser->getTitle()->getPrefixedDBKey()} in project "
							//	. $project->project_name() );
						} else if (isset($pfc['text']) and !$has_arch and !wwfReadOnly()) {
							$this->record_message(
								'Notice: Project file ‘'
								. htmlspecialchars($args['filename']) 
								. '’ looks like an archived '
								. 'project file here in the wiki page, but project \''
								. $this->make_manage_project_link(
									$project,
									htmlspecialchars($project->project_name())
								)
								. '\' does not record it as archived here ('
								. 'it might be a regular project file tag that\'s '
								. 'missing a closing slash).'
							);
						}
					}
				}
			}
		} catch (WWException $ex) {
			$output = '';
			if ( $parser ) {
				$parser->disableCache();
				$title = $parser->getTitle();
				if ( $title->getNamespace() != NS_SPECIAL ) {
					$title->invalidateCache();
				}
			}
		}
	}

	public function click_to_add_link( $text, $project, $filename, $page, $tagtype = 'source-file' ) {
		return $this->make_manage_project_link(
			$project,
			$text,
			"ww-action=set-$tagtype-location&ww-action-project="
			. urlencode( $project->project_name() )
			. '&ww-action-filename='
			. urlencode($filename)
			. '&ww-action-page='
			. urlencode($page),
			false,
			false,
			null,
			array( 'onClick' => 'clickTo(event)' )
		);
	}

	# called after parsing, to decode things that have been sequestered
	# from the wikitext processing by being hidden in a base64-encoded
	# comment
	public function disarm_html( &$parser, &$text ) {
		$len = strlen($text);
		$text = preg_replace(
			'/<!-- WORKINGWIKI_EXTENSION BASE64_ENCODED (.*?) END_ENCODED -->/esm',
			'base64_decode("$1")',
			$text
		);
		if (is_null($text)) {
			$text = "<pre>an error occurred "
				."in WWInterface::disarm_html ... length=$len ... ";
			switch (preg_last_error()) {
				case PREG_NO_ERROR:
					$text .= "<br/>pcre_error: PREG_NO_ERROR!\n";
					break;
				case PREG_INTERNAL_ERROR:
					$text .= "<br/>pcre_error: PREG_INTERNAL_ERROR!\n";
					break;
				case PREG_BACKTRACK_LIMIT_ERROR:
					$text .= "<br/>pcre_error: PREG_BACKTRACK_LIMIT_ERROR!\n";
					break;
				case PREG_RECURSION_LIMIT_ERROR:
					$text .= "<br/>pcre_error: PREG_RECURSION_LIMIT_ERROR!\n";
					break;
				case PREG_BAD_UTF8_ERROR:
					$text .= "<br/>pcre_error: PREG_BAD_UTF8_ERROR!\n";
					break;
				case PREG_BAD_UTF8_OFFSET_ERROR:
					$text .= "<br/>pcre_error: PREG_BAD_UTF8_OFFSET_ERROR!\n";
					break;
			}
			$text .= "</pre>\n";
		}
		//$text = "(disarm_html $len characters)";
		//$text = htmlspecialchars($text);
		return true;
		$text = preg_replace(
			'/<!-- WORKINGWIKI_EXTENSION BASE64_ENCODED (.*?) END_ENCODED -->/esm',
			'SPUD', $text, -1);
		$text = 'HTML TEXT HERE: ['.$text.']';
	}

	# After the parsing and all is done, we retrieve what projects
	# have been used, and save them into the database,
	# so that the 'projects' sidebar will be correct
	# when the page is retrieved from the parser cache.
	# Also, include the list in the page for use by JavaScript code.
	public function massage_page_after( &$parser, &$text ) {
		//$text = htmlspecialchars($text);
		wwProfileIn( __METHOD__ );
		global $wgTitle;
		if (!is_object($wgTitle) or $wgTitle->getArticleID() == 0) {
			return true;
		}
		if ($wgTitle->getNamespace() == NS_SPECIAL) {
			return true;
		}
		$projs = $this->get_projects_list_for_page();
		sort($projs);
		$proj_list = trim(implode(' ', $projs));
		$parser->mOutput->setProperty('ww_projects',$proj_list);
		#wwLog("set ww_projects: $proj_list\n");
		if ( $this->default_project_name !== false ) {
			$parser->mOutput->setProperty('ww_default_project',$this->default_project_name);
		}
		if ( ! $this->page_is_preview() ) {
			$old_projs = $this->get_projects_list_from_db();
			sort($old_projs);
			$old_proj_list = trim(implode(' ', $old_projs));
			if ($old_proj_list !== $proj_list) {
				$u = new LinksUpdate( $parser->mTitle, $parser->mOutput );
				$dbw = wfGetDb( DB_MASTER );
				$dbw->begin();
				$u->doUpdate(); 
				$dbw->commit(); 
				$this->projects_list_from_db = $projs;
			} 
		}
		wwProfileOut( __METHOD__ );
		return true;
	}
	
	public function get_projects_list_for_page() {
		global $wwContext, $wgTitle;
		if (is_array($this->projects_to_list)) {
			$projs = $this->projects_to_list;
			global $wgRequest;
			if ($wgRequest and ($qproj = $wgRequest->getText('project','')) != '') {
				$projs[$qproj] = true;
			}
			if ( count($projs) == 0 and $wgTitle->getNamespace() != NS_SPECIAL ) {
				# if there are no projects actually referenced on this page,
				# there may be a project or potential project to connect the page 
				# to anyway.
				try {
					$candidate = $wwContext->wwStorage->find_project_given_page( $wgTitle->getPrefixedDBKey());
				} catch (WWException $ex) {
					$candidate = null;
				}

				if ($candidate !== null) {
					$projs[$candidate->project_name()] = true;
				} else {
					$ns = $wgTitle->getNamespace();
					# put project link on talk page if it would go on the regular page
					if ($ns % 2)	$ns -= 1;
					switch ($ns) {
						#case NS_MAIN: # don't put the red project if there's actually nothing 
						case NS_PROJECTDESCRIPTION:
							$projs[$wgTitle->getDBKey()] = true; 
							break;
						default:
							break;
					}
				}
			}
		} else if (is_object($wgTitle) and $wgTitle->getArticleID() != 0) {
			$projs = array_flip($this->get_projects_list_from_db());
		} else {
			$projs = array();
		}
		if ( ! count( $projs ) and $this->default_project_name !== false
		     and $this->default_project_name !== null ) {
			$projs[ $this->default_project_name ] = true;
		}
		return array_keys($projs);
	}

	public function get_projects_list_from_db() {
		if ( ! is_array( $this->projects_list_from_db ) ) {
			$dbr = wfGetDB( DB_SLAVE );
			global $wgTitle;
			$res = $dbr->select(
				'page_props',
				array( 'pp_value' ),
				array(
					'pp_page' => $wgTitle->getArticleID(),
					'pp_propname' => 'ww_projects'
				),
				__METHOD__,
				array()
			);
			$this->projects_list_from_db = array();
			while ( $row = $dbr->fetchObject( $res ) ) {
				if ( $row->pp_value != '' ) {
					$this->projects_list_from_db = 
						$this->projects_list_from_db + explode(' ',trim($row->pp_value));
				}
			}
			$dbr->freeResult( $res );
		}
		return $this->projects_list_from_db;
	}

	public function get_projects_list_from( &$text ) {
		#if (preg_match('/<!-- projects: (.*?) -->/', $text, $matches))
		#	$projs = explode(' ',$matches[1]);
		$text = preg_replace_callback(
			'/WW-PROJECTS-(.*?)-SRCEJORP-WW/',
			create_function(
				'$matches',
				'global $wwProjectsReferenced; '
				.'if (!isset($wwProjectsReferenced)) $wwProjectsReferenced = array(); '
				.'$wwProjectsReferenced = $wwProjectsReferenced + '
				.' explode(" ",base64_decode($matches[1])); '
				.'return "";'
			),
			$text
		);
		global $wwProjectsReferenced;
		return $wwProjectsReferenced;
	}

	public function get_project_file_query($project, $filename='',
			$xtra='', $make=false, $display_mode=null, $htmlenc=true) {
		$gq = '';
		if ($project instanceOf ProjectDescription) {
			$gq .= $project->project_url_attr();
		} else if ($project != '') {
			$gq .= 'project='.urlencode($project);
		}
		if ($xtra != '' and $xtra[0] != '&') {
			$xtra = '&' . $xtra;
		}
		$gq .= $xtra;
		# need to make sure the filename comes last because of how it's used
		# in transforming relative urls
		$gq .= ($make?'':'&make=false')
			.(is_null($display_mode)? '':"&display=$display_mode")
			. '&filename='.urlencode($filename);
		if ($gq[0] == '&') {
			$gq = substr($gq,1);
		}
		wwRunHooks('WW-GetProjectFileQuery', array(&$gq));
		if ($htmlenc) {
			$gq = htmlspecialchars($gq);
		}
		return $gq;
	}

	# this is called by the below function, but also used directly for the
	# base that's used when transforming relative urls in HTML project files.
	public function get_project_file_base_url($project, $filename='',
			$make=false, $display=null, $query='') {
		global $wgScript;
		$gpfb = "$wgScript/Special:GetProjectFile?"
			. $this->get_project_file_query($project,$filename,$query,$make,$display,true);
		return $gpfb;
	}
	
	# URLs for links to retrieve project files, including .make.log files,
	# pdfs and images.  See also the URL passed to the make process in
	# make_product().
	public function make_get_project_file_url($project,$filename,$make=true,$display=null) {
		return $this->get_project_file_base_url($project,$filename,$make,$display);
	}

	# format a link to ManageProject page for a given project.
	# returns HTML code like <a href="link">text</a>.
	# $project may be a project name, which will be used directly,
	# or a project object, whose name will be used.
	# $linktext will be the project name if not supplied.
	# $query should not begin with '?' or '&'.
	public function make_manage_project_query(
			$project,$query='',$htmlenc=true,$readonly=false) {
		if ( $project instanceOf ProjectDescription ) {
			$project = $project->project_name();
		}
		if ( $project === null ) {
			$project = '';
		}
		if ( $query != '' ) {
			$query = '&'.$query;
		}
		$query = "project=".urlencode($project).$query;
		wwRunHooks('WW-MakeManageProjectQuery',array(&$query,$readonly));
		if ($htmlenc) {
			$query = htmlspecialchars($query);
		}
		return $query;
	}

	public function make_manage_project_link(
			$project, $linktext='', $query='', $readonly=false, $local=false, 
			$class=null, $xtra_attrs=null ) {
		global $wwContext;
		if ( $project instanceOf ProjectDescription ) {
			$project = $project->project_name();
			$red = false;
		} else {
			$red = true;
		}
		if ( $project === null )
			$project = '';
		if ($red) {
			$red = ! $wwContext->wwStorage->project_is_known($project);
		}
		#wwLog("make_manage_project_link($project, $linktext, $query, $readonly, $local): ");
		#$this->debug_message("Is $project local?");
		$mpurl = $project;
		if ( !$local and $wwContext->wwStorage->is_project_uri($project) ) {
			global $wwLinksForURIs;
			$mpurl = preg_replace( array_keys($wwLinksForURIs),
				array_values($wwLinksForURIs), $project );
			# this was commented out -- why??
			# probably because it doesn't work!
			#if ($url != $project)
			#{ #$this->debug_message("URL for $project is $url");
			#	$mpl = "<a href=\"$url\">" . htmlspecialchars($project) . '</a>';
			#	#wwLog("$mpl\n");
			#	return $mpl;
			#}
			# if there's no external link known for a project, fall back on 
			# making a local link.
		}
		if ($mpurl == $project) {
			global $wgScript;
			$mpurl = "$wgScript/Special:ManageProject";
			$query = $this->make_manage_project_query($project,$query,
				true, $readonly);
		}
		$url = $mpurl;
		if ($query != '') {
			$url .= (strpos($url,'?') === false? '?' : '&') . $query;
		}
		if ($class !== null and !is_array($class)) {
			$class = array($class);
		}
		if ($red) {
			if ( $class == null )
				$class = array();
			$class[] = 'new';
		}
		#wwLog("make_manage_project_link: $project - " . ($red?'not ':''). 
		#	" known.\n");
		if ( $linktext == '' ) {
			$linktext = htmlspecialchars($project);
		}
		$xattr_string = '';
		if ( $xtra_attrs !== null ) {
			foreach ($xtra_attrs as $name => $value) {
				if (is_array($value)) {
					$value = implode(' ', $value);
				}
				$xattr_string .= " $name=\"$value\"";
			}
		}
		$mpl = "<a href=\"$url\""
			. ($class === null ? '' : ' class="'.implode(' ',$class).'"')
			. "$xattr_string>$linktext</a>";
		#wwLog("$mpl\n");
		return $mpl;
	}

	# slighly different from the makeLink functions we're given, in the 
	# way it handles links to image pages.	Used for links on ManageProject
	# and what not.
	public function makeLink($text,$target,$skin) {
		$title = Title::newFromText($target);
		if( is_object($title) and NS_MEDIA == $title->getNamespace() ) {
			$title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
		}
		if (is_object($title) and ($title->getNamespace() == NS_IMAGE)) {
			$img = wfFindFile($title);
			#wwLog("make image link\n");
			if (!$img || !$img->exists()) {
				#wwLog("broken image link: $text\n");
				return $skin->makeBrokenImageLinkObj($title,htmlspecialchars($text));
			}
		}
		#wwLog("title: ".serialize($title)."\n");
		return $skin->makeLinkObj($title,$text);
	}

	public function save_modified_projects() {
		global $wwContext;
		if (is_array($this->modified_projects)) {
			foreach ($this->modified_projects as $projectname=>$v) {
				$project = $wwContext->wwStorage->find_project_by_name($projectname);
				if ($project != null) {
					$wwContext->wwStorage->save_project_description($project);
				}
			}
		}
		$this->modified_projects = array();
	}

	public function before_page_display(&$outputPage, &$skin) {
		wwProfileIn( __METHOD__ );

		global $wwContext;

		if ( method_exists( $outputPage, 'addJsConfigVars' ) ) {
			$outputPage->addJsConfigVars( array(
				'wwDefaultProjectName' => $this->default_project_name,
				'wwProjectNames' => $this->get_projects_list_for_page()
			) );
		}

		if ( wwfUseMathJax() ) {
			global $wgOut;
			$wgOut->addHeadItem(
				'mathjax-config', 
				'<script type="text/x-mathjax-config">'
				. 'MathJax.Hub.Config({ jax: ["input/MathML", "output/HTML-CSS"] });'
				. '</script>'
			);
			$wgOut->addHeadItem(
				'mathjax',
				'<script type="text/javascript" '
				 .'src="http://cdn.mathjax.org/mathjax/latest/MathJax.js'
				 #.'"></script>');
				 .'?config=MML_HTMLorMML"></script>'
			 );
		}

		# Cookie for auto-sensing ability to do dynamic display.  See
		# https://sourceforge.net/p/workingwiki/bugs/362
		# can't use $wgRequest->response()->setcookie() because it forces httponly
		global $wwEnableDynamicProjectFiles;
		if ( $wwEnableDynamicProjectFiles ) {
			setcookie( 'WorkingWiki.no.js', '1', time() + 30 * 24 * 60 * 60, '/' );
		}

		try {
			if (!wwfReadOnly()) {
				#wwLog("before_page_display\n");
				# this save was added when auto-converting from implicit to explicit
				# project descriptions, and probably isn't needed any more.	But might
				# be useful in the future, when "click to add" becomes more often
				# automatic.
				$this->save_modified_projects();
				$wwContext->wwStorage->update_archived_project_files();

				global $wwPruneDirectoriesAfterPageRequest;
				if ($wwPruneDirectoriesAfterPageRequest) {
					# Do prune-working-directories operation in case it's time.
					# Generally it returns immediately because it isn't time to 
					# redo the prune yet.
					# We could do this using $wgDeferredUpdates or the job queue
					# but it would get run while the client is waiting anyway.
					$u = new WWPruneDirectoryUpdate;
					#global $wgDeferredUpdateList;
					#array_push($wgDeferredUpdateList, $u);
					#wwLog("Scheduled a deferred prune\n");
					$u->doUpdate();
				}
			}
		} catch (WWException $ex) {
		}
		wwProfileOut( __METHOD__ );
		return true;
	}

	# this hook is called at the end of saving edits to an article.
	# apparently MW parses the page during the save (at which time it isn't
	# displayed), puts it in the parser cache, then redirects to the regular
	# url of the page, which displays it from the parser cache.  We need to
	# catch this hook after the parsing, but before the redirect, before
	# we lose the info we recorded about what archived project files to update.
	public function after_edit_updates( $article, &$editInfo, $changed ) {
		global $wwContext;
		$wwContext->wwStorage->update_archived_project_files();
		return true;
	}

	# create_empty_project: just like it sounds.
	public function create_empty_project($projectname) {
		try {
			$projectname = ProjectDescription::normalized_project_name($projectname);
		} catch (WWException $ex) {
			$this->throw_error(
				"Bad project name ‘"
				. htmlspecialchars($projectname)
				. "’ in create_empty_project()"
			);
		}
		return ProjectDescription::newFromXML(
			$this->create_empty_project_description($projectname),
			null
		);
	}

	public function create_empty_project_description($projectname) {
		return '<project-description><project name="'
			. htmlspecialchars($projectname, ENT_QUOTES)
			. '"></project></project-description>';
	}

	# Implements the 'projects' portlet on the left sidebar (or wherever
	# your skin puts it).	
	public function add_project_box(&$skin, &$template) {
		global $wwContext, $wgOut, $wgScript, $wgUser, $wgTitle;

		$readonly = wwfReadOnly();
		$projs = $this->get_projects_list_for_page();
		$pbox = '';
		if (is_array($projs)) {
			foreach ($projs as $pname) {
				# validate the input!
				try {
					$vpname = ProjectDescription::normalized_project_name($pname);
					#wwLog("project link: $pname ($vpname)\n");
					$red = ! $wwContext->wwStorage->project_is_known($vpname);
					if ( !$red or !$readonly ) {
						$a = $this->make_manage_project_link(
							$vpname, 
							htmlspecialchars(ProjectDescription::human_readable_project_name($vpname))
						);
						$pbox .= "<li>$a</li>\n";
					}
				} catch ( WWException $ex ) {
					$this->record_warning( "Exception caught in add_project_box()" );
					#wwLog("Exception caught in add_project_box()\n");
				}	
			}
		}
		global $wgAutoloadClasses;
		if ( ! $readonly and isset( $wgAutoloadClasses['SpecialMultiUpload'] ) ) {
			$def_proj = false;
			if ( is_array($projs) and count($projs) == 1 ) {
				$def_proj = $projs[0];
			} else if ( ! is_array($projs) or count($projs) == 0 ) {
				$dproject = $wwContext->wwStorage->find_project_given_page($wgTitle->getPrefixedDBKey());
				if ( is_object($dproject) ) {
					$def_proj = $dproject->project_name();
				} else {
					$ns = $wgTitle->getNamespace();
					if ( $ns % 1 ) {
						$ns = $ns - 1;
					}
					if ( $ns == NS_PROJECTDESCRIPTION ) {
						$ns = 0;
					}
					if ( $ns != NS_FILE && $ns != NS_SPECIAL ) {
						$def_proj = Title::newFromText( $wgTitle->getText(), $ns )->getPrefixedDBKey();
					}
				}
			}
			if ( $def_proj ) {
				$importlink = $wgUser->getSkin()->makeLinkObj(
					SpecialPage::getTitleFor( 'ImportProjectFiles' ),
					$wwContext->wwInterface->message( 'import-project-files-link' ),
					'project='.urlencode($def_proj),
					''
				);
			} else {
				$importlink = $wgUser->getSkin()->makeLinkObj(
					SpecialPage::getTitleFor( 'ImportProjectFiles' ),
					$wwContext->wwInterface->message( 'import-project-files-link' )
			       	);
			}
			$importlink = "<li>$importlink</li>\n";
		} else {
			$importlink = '';
		}
		# unfortunately we can still sometimes get errors or debug messages
		# in the box because render_after_parsing gets called.	Be nice to
		# fix that.
		$errs = ''; //$this->report_errors();
		if ($pbox != '') {
			$pbox = "<ul>\n"
				. $pbox
				. ($errs == '' ? '' : "<li>$errs</li>\n")
				#. "<li><form action=\"$wgScript/Special:ManageProject\" method=\"get\""
				#. " class=\"project-search-form\">"
				#. "<input type=\"text\" id=\"project-search\" name=\"project\" "
				#. "title=\"Open a project by name\" value=\"\"/></form></li>\n"
				. $importlink
				. "</ul>";
		} else {
			$pbox =
				($errs == '' ? '' : "$errs\n")
				#. "<form action=\"$wgScript/Special:ManageProject\" method=\"get\""
				#. " class=\"project-search-form\">"
				#. wfMsgExt( 'go-to-project', array( 'parseinline' ) )
				#. " <input type=\"text\" id=\"project-go\" name=\"project\" "
				#. "title=\"Open a project by name\" value=\"\"/></form>\n"
				. ($importlink ? "<ul>$importlink</ul>\n" : '');
		}
		# insert the projects box just before toolbox 
		$bar =& $template->data['sidebar'];
		$newbar = array();
		foreach ($bar as $heading => &$box) {
			if (strcasecmp($heading,'Toolbox') === 0) {
				#if (count($pbox) > 0)
				#	$newbar['projects'] = $pbox;
				$newbar['Projects'] = $pbox;
			}
			$newbar[$heading] = $box;
			$heads[] = $heading;
		}
		if (!array_key_exists('Projects',$newbar)) {
			$newbar['Projects'] = $pbox;
		}
		$template->set( 'sidebar', $newbar );

		return true;
	}

	public function page_being_parsed() {
		if ( $this->_page_being_parsed === null ) {
			$title = RequestContext::getMain()->getTitle();
			if ( is_object($title) ) {
				$this->_page_being_parsed = $title->getPrefixedText();
			} else {
				$this->_page_being_parsed = 'ww-error-unknown-page';
			}
		}
		return $this->_page_being_parsed;
	}

	public function set_page_being_parsed( $name ) {
		$this->_page_being_parsed = $name;
	}

	# Informational function - returns true if the page being served
	# is an old revision of a wiki page.
	# Some WW features work differently when viewing old revisions of pages.
	public function page_is_history() {
		global $wgRequest;
		$context = RequestContext::getMain();
		$article = Article::newFromTitle( $context->getTitle(), $context );
		$diffid = $wgRequest->getVal( 'diff' );
		if (!is_null($diffid)) {
			$id_displaying = $diffid;
		} else {
			$oldid = $article->getOldID();
	//$this->debug_message("oldid is $oldid, latest is ".$article->getPage()->getLatest() );
			$id_displaying = $oldid;
		}
		if ($id_displaying != 0 and $id_displaying != $article->getPage()->getLatest()) {
			return true;
		}
		return false;
	}

	# Informational function - returns true if the page currently being
	# displayed is a preview rather than the saved version of the page.
	# Some WW features behave differently during previews.
	public function page_is_preview() {
		global $wgRequest;
		global $wwCaughtEdits; 
		# This distinguishes the save-and-then-view situation from a preview.	
		# The variable is set by catch_edits().	Without it, I guess you find 
		# the 'submit' action and get a false positive for previewing.
		if ($wwCaughtEdits)
			return false;
		global $wgTitle;
		// Some special pages can have action=submit but aren't previewing
		if ( $wgTitle instanceOf Title and $wgTitle->getNamespace() == NS_SPECIAL )
			return false;
		if ( ! isset( $wgRequest ) ) { # in wmd.php, say
			return false;
		}
		if ( in_array( $wgRequest->getText('action'),
				array('edit','submit','editredlink') ) )
			return true;
		return false;
	}

	# Gets a look at every page edited before it gets saved, and checks for 
	# project files so as to clear all pages containing project files from 
	# the cache.
	# Gutted from what it used to do, with all that flagging files removed,
	# added, etc.
	public function catch_edits($editor, $text, &$error, $summary) {
		global $wwContext, $wgRequest;
		$pagename = $editor->mTitle->getPrefixedDBKey();
		# which project files were there before the edit
		$project_files_before = array();
		$before_rev = $editor->getBaseRevision();
		#wwLog("in catch_edits\n");
		if (!is_null($before_rev)) {
			try {
				$project_files_before = $wwContext->wwStorage->project_files_referenced( 
					$before_rev->getText(), $pagename );
			} catch ( WWException $ex ) {
				$this->record_warning(
					"Could not get project file "
					. "data from old text of page."
				);
			}
		}
		# which are there after
		#wwLog("get after\n");
		try {
			$project_files_after = $wwContext->wwStorage->project_files_referenced(
				$text,
				$pagename,
				/*new*/true
			);
		} catch ( WWException $ex ) {
			$project_files_after = array();
			$this->record_warning(
				'Could not get project file '
				. 'data from new text of page'
			);
		}
		#wwLog("got after\n");
		#wwLog("project_files_before: " . serialize($project_files_before) . "\n");
		#wwLog("project_files_after: " . serialize($project_files_after) . "\n");
		foreach ($project_files_before as $projectname=>&$files) {
			if (is_array($files)) {
				foreach ($files as $filename=>&$attrs) {
					if ($attrs['source']) {
						$projects_to_invalidate[$projectname] = true;
					}
				}
			}
		}
		foreach ($project_files_after as $projectname=>&$files) {
			if (is_array($files)) {
				foreach ($files as $filename=>&$attrs) {
					if ($attrs['source']) {
						//throw new MWException( 'Invalidate: ['.$projectname.']' );
						$projects_to_invalidate[$projectname] = true;
					}
				}
			}
		}
		# I wonder if it's worth worrying about multiple names => one project.
		# The only problem is redundant invalidate_pages, so seems unimportant.
		if (isset($projects_to_invalidate)) {
			foreach ($projects_to_invalidate as $projectname=>$t) {
				try {
					//throw new MWException( 'Invalidate: ['.$projectname.']' );
					//if ( !array_key_exists( $projectname, $projects ) ) {
					if ( $projectname == '' ) {
						$project = $wwContext->wwStorage->find_project_given_page($pagename);
					} else {
						$project =& $wwContext->wwStorage->find_project_by_name($projectname); 
					}
					//}
					if ( $project !== null ) {
						//throw new MWException( 
						#wwLog("Invalidate pages for project '" 
						#	. $project->project_name() . "'\n");
						$this->invalidate_pages( $project, $pagename );
					}
				} catch ( WWException $ex ) {
					$this->record_warning(
						"Could not do parser cache "
						. "invalidation for project '"
						. htmlspecialchars($projectname) 
						. "'."
					);
				}
			}
		}
		# set a flag to distinguish a save and view from a preview.
		global $wwCaughtEdits; 
		$wwCaughtEdits = true;
		# get the archived project files.	The beforePageDisplay is supposed to
		# do that, but it seems not to in the case that you preview the page,
		# then save without any further changes.
		#$this->update_all_archived_project_files();
		# clear old versions of the page from the cache
		$wwContext->wwStorage->clear_from_cache($pagename);
		return true;
	}

	# When Special:ManageProject has been invoked with an action like
	# 'make X' or 'delete Y', this makes the 'special page' tab link
	# to the plain ManageProject tab, not the whole URL of the current page,
	# which would make it do the action again.
	# Also adds 'delete' tab to MP page.  This is for MW < 1.18.0: see below
	# for the newer version.
	public function fix_special_tab_old(&$skin, &$content_actions)
	{ $nskey = $skin->mTitle->getNamespaceKey();
		# doctor the page's url so it doesn't have the action arguments,
		# just the project.
		$url = $skin->mTitle->getFullURL();
		global $wgRequest;
		$query = "project=".$wgRequest->getText('project');
		if ($wgRequest->getText('filename'))
			$query .= "&filename=".$wgRequest->getText('filename');
		$content_actions[$nskey]['href']
			= "$url?$query";
		return true;
	}

	public function add_delete_tab_old(&$skin, &$content_actions) {
		global $wwContext, $wgTitle, $wgUser, $wgRequest;
		if ($wgUser->isAllowed('delete'))
			$content_actions['ww-delete-project-tab'] = array(
				'class' => false,
				'text' => $wwContext->wwInterface->message( 'ww-delete-project' ),
				'href' => $wgTitle->getLocalUrl(
						 "project=".$wgRequest->getText('project')
							 ."&ww-action=delete-project") );
		return true;
	}

	# When Special:ManageProject has been invoked with an action like
	# 'make X' or 'delete Y', this makes the 'special page' tab link
	# to the plain ManageProject tab, not the whole URL of the current page,
	# which would make it do the action again.
	public function fix_special_tab(&$skin, &$content_navigation) {
		$nskey = $skin->getTitle()->getNamespaceKey( '' );
		# doctor the page's url so it doesn't have the action arguments,
		# just the project.
		$url = $skin->getTitle()->getFullURL();
		$fn = $skin->getRequest()->getText('filename');
		if ($fn) {
			$fn = '&filename='.$fn;
		}
		$content_navigation['namespaces'][$nskey]['href'] =
			"$url?project="
			. $skin->getRequest()->getText('project')
			. $fn;
		return true;
	}

	public function add_delete_tab( SkinTemplate &$skt, array &$content_navigation) {
		global $wwContext;
		if ($skt->getUser()->isAllowed('delete')) {
			$content_navigation['actions']['ww-delete-project-tab'] = array(
				'class' => false,
				'text' => $wwContext->wwInterface->message( 'ww-delete-project' ),
				'href' => $skt->getTitle()->getLocalUrl(
					"project="
					. htmlspecialchars( $skt->getRequest()->getText('project') )
					. "&ww-action=delete-project"
				)
			);
		}
		return true;
	}

	# When some users are viewing a page with MathJax, and others are 
	# seeing it without (depending whether they have 'MathML if possible'
	# in their preferences), this makes the two versions 
	# be indexed differently in the parser cache so they don't have to be 
	# reparsed on every page view, and yet they aren't mixed up with each other
	# and served to the wrong users.
	# It also distinguishes when we serve pages to some people with 
	# placeholders for dynamically-loaded project file content.
	public function fix_hash( &$confstr ) {
		if ( wwfDynamicDisplayInEffect() ) {
			$confstr .= '-dynamic';
		}
		if ( wwfUseMathJax() ) {
			$confstr .= '-mathjax';
		}
		return true;
	}

	# add WW's preferences to Special:Preferences
	public function get_preferences( $user, &$preferences ) {
		global $wwAllowBackgroundJobEmails;
		if ( $wwAllowBackgroundJobEmails ) {
			$preferences['ww-background-jobs-emails'] = array(
				'type' => 'toggle',
				'label-message' => 'tog-ww-background-jobs-emails',
				'section' => 'personal/email'
			);
		}
		# add the 'Use browser's MathML if possible' preference if the wiki isn't using
		# the one provided by the Math extension
		global $wwProvideMathmlPreference;
		if ( $wwProvideMathmlPreference ) {
			$preferences['mathml'] = array(
				'type' => 'toggle',
				'label-message' => 'ww-mathml-preference',
				'section' => 'rendering/workingwiki',
				'help-message' => 'ww-mathml-preference-help',
			);
		}
		global $wwEnableDynamicProjectFiles;
		if ( $wwEnableDynamicProjectFiles ) {
			#global $wgScriptPath; wwLog( "ww-dynamic-display ($wgScriptPath)" );
			$preferences['ww-dynamic-display'] = array(
				'type' => 'radio',
				'label-message' => 'ww-dynamic-display-preference',
				'section' => 'rendering/workingwiki',
				'options' => array(
					wfMessage( 'ww-dynamic-display-preferences-default' )->text() => 'ifpossible',
					wfMessage( 'ww-dynamic-display-preferences-never' )->text() => 'never',
				),
				//'default' => 'ifpossible',
				'help-message' => 'ww-dynamic-display-preferences-help',
			);
		}
		global $wwEnableLeekspin;
		if ( $wwEnableLeekspin ) {
			$preferences['ww-leekspin'] = array(
				'type' => 'toggle',
				'label-message' => 'ww-leekspin-preference',
				'section' => 'rendering/workingwiki',
			);
		}
		#wwLog( 'defaultPreferences: ' . json_encode( $preferences ) );
		return true;
	}

	# ===== other public functions =====

	// This is probably ill-advised, bypassing the mediawiki parser extension
	// features: we directly change $$...$$ (on one line)
	// and {$...$} (on multiple lines), and <latex>...</latex> into
	// latex source files on their way into the system.
	public function replace_inlines($text) {
		global $wwContext;

		#$ex = new Exception(); wwLog( 'in replace_inlines: ' . $ex->getTraceAsString() );
		#wwLog(" replace_inlines called: ". $text ."...");
		#if ( preg_match( '/\$.*\$/', $text ) ) {
		#	$ex = new Exception;
		#	wwLog($ex->getTraceAsString());
		#}
		# when we're parsing a <source> tag or something we can get in
		# a bunch of trouble if we don't leave the replace_inlines out.
		global $withinParser;
		if ($withinParser > 1) {
			return $text;
		}
		if ( ! $text ) {
			return $text;
		}
		wwProfileIn( __METHOD__ );
		# replace these things between source-file elements, not within them.
		# to this end, we have to locate these elements.  this means we parse
		# the page for elements twice, once before replacing $$ etc., and once
		# afterward, but this seems necessary.
		$elements = $wwContext->wwStorage->find_project_files_on_page(null,$text,true,false);

		# need to iterate through the elements in order on the page:
		# make a key-value array with each as start=>end, then sort by start
		# positions.  First the source-file and project-file tags.
		foreach ($elements as $projname=>$files) {
			if (is_array($files)) {
				foreach ($files as $element) {
					if (array_key_exists('position',$element)) {
						foreach ( $element['position'] as $pair ) {
							#wwLog("source-file at [" . $element['position'][0]
							#	. '..' . $element['position'][1] . "]\n");
							$sort_element_positions[$pair[0]] 
								= $pair[1];
						}
					}
				}
			}
		}
		# Now also the syntaxhighlight and what not.
		foreach ($elements['extras'] as $match) {
			#wwLog("other element at [" . $match[1] . '..'
			#	. ($match[1] + strlen($match[0])) . "]\n");
			$sort_element_positions[$match[1]] = $match[1] + strlen($match[0]);
		}
		# extra one to make sure we go all the way to the end
		#wwLog("marker at [" . strlen($text) . '..' . strlen(text) . "]\n");
		$sort_element_positions[strlen($text)] = strlen($text);
		ksort($sort_element_positions);
		# now do replacement on the outside part and pass through the inside part.
		$repl_start = 0;
		$reassemble = '';
		foreach ($sort_element_positions as $el_start => $el_end) {
			if ($el_start >= $repl_start) {
				$fragment = substr($text,$repl_start,$el_start - $repl_start);
				global $wwWikitextReplacements;
				if ( is_array( $wwWikitextReplacements ) ) {
					foreach ( $wwWikitextReplacements as $pattern => $replacement ) {
						#wwLog( 'wikitext replacement: ' . $pattern );
						$fragment = preg_replace( $pattern, $replacement, $fragment );
					}
				}
				#wwLog("replace_inlines: do [$repl_start..".($el_start-1)."]\n");
				if ( 0 ) { # now implemented in the array above
				# FIXME: CODE INJECTION at md5('$2')!	Use preg_replace_callback!
				# FIXME but I tried it and it doesn't seem to inject code, why not?
				$math_repl_str =
					"\"$1\".'<source-file filename=\"'.md5('$2')"
					. ".'.tex-math\" standalone=\"yes\">"
					. "$2</source-file>'";
				# FIXME these fail on double backslashes
				# FIXME these also fail if they're nested, e.g. 
				# <latex>a b c $$x y z$$ f g h</latex>
				# FIXME even worse, they fail if head-to-tail, like
				# $$a$$$$+ b$$.	They need a space between, which is not good.
				$fragment = preg_replace(
					'/([^\\\\]|^)\{\$(.*?[^\\\\]|)\$\}/es',
					$math_repl_str,
					$fragment
				);
				$fragment = preg_replace(
					'/([^\\\\]|^)\$\$(.*?[^\\\\]|)\$\$/e',
					$math_repl_str,
					$fragment
				);
				$fragment = preg_replace(
					'/([^\\\\]|^)<latex>(.*?[^\\\\]|)<\/latex>/esi',
					"'$1<source-file filename=\"'.md5('$2')"
					. ".'.tex-inline\" standalone=\"yes\">"
					. "\\documentclass{article}\n"
					. "\\begin{document}\n"
					. "$2\n\\end{document}\n</source-file>'",
					$fragment
				);
				$fragment = preg_replace(
					'/__DISABLE_MAKE__/',
					'<toggle-make-enabled enabled=0/>',
					$fragment
				);
				$fragment = preg_replace(
					'/__ENABLE_MAKE__/',
					'<toggle-make-enabled enabled=1/>',
					$fragment
				);
				}
				$reassemble = $reassemble . $fragment;
				#wwLog("replace_inlines: skip [$el_start..$el_end]\n");
				$reassemble = $reassemble . substr($text,$el_start,$el_end+1-$el_start);
				$repl_start = $el_end + 1;
			}
		}
		#wwLog("replace_inlines changed\n=====\n$text\n=====\nto\n=====\n$reassemble\n=====\n");
		wwProfileOut( __METHOD__ );
		return $reassemble;
	}

	# clear all pages from the cache that need to be reevaluated due
	# to changes in this project's files.
	public function invalidate_pages( &$project, $except_page=null, $upstream=false ) {
		wwProfileIn( __METHOD__ );
		global $wwContext;
		#wwLog( 'Invalidate pages' );
		if ( !is_null($except_page) and is_string($except_page)) {
			$except_page = Title::newFromText($except_page);
		}
		if ( $except_page instanceOf Title ) {
			$except_page = $except_page->getPrefixedDBKey();
		}
		global $recursive_invalidate;
		if ( is_array($recursive_invalidate) and 
		     isset($recursive_invalidate[$project->project_name()]) ) {
			wwProfileOut( __METHOD__ );
			return;
			#$this->throw_error("Project " 
			#	.htmlspecialchars($project->project_name()). " is part of a cycle of "
			#	. "project dependencies!");
		}
		$recursive_invalidate[$project->project_name()] = true;
		foreach ($project->pages_involving_project_files() as $ipage) {
			$ititle = Title::newFromText($ipage);
			if ( is_object($ititle) and 
			     ( is_null($except_page) or
			       $ititle->getPrefixedDBKey() != $except_page ) and
			     $ititle->getNamespace() != NS_SPECIAL ) {
				#wwLog("cache invalidate page " . $ipage );
				if ( NS_MEDIA == $ititle->getNamespace() ) {
					$ititle = Title::makeTitle( NS_IMAGE, $ititle->getDBkey() );
				}
				# man! this actually &$#*@ works!
				$ititle->invalidateCache();
			}
		}
		if ($upstream) {
			if (is_array($project->depends_on)) {
				foreach ($project->depends_on as $depname => $t) {
					$dep = $wwContext->wwStorage->find_project_by_name($depname);
					if ($dep != null) {
						self::invalidate_pages($dep,$except_page,$upstream);
					}
				}
			}
		} else {
			if (is_array($project->depended_on_by)) {
				foreach ($project->depended_on_by as $depname => $t) {
					$dep = $wwContext->wwStorage->find_project_by_name($depname);
					if ($dep != null) {
						self::invalidate_pages($dep,$except_page);
					}
				}
			}
		}
		unset($recursive_invalidate[$project->project_name()]);
		wwProfileOut( __METHOD__ );
	}

	# flag a project for inclusion in the projects sidebar.
	public function project_is_in_use($projectname) {
		global $wwContext;
		if (($pn = ProjectDescription::normalized_project_name($projectname,false)) !== null) {
			$projectname = $pn;
		}
		if ( ! array_key_exists($projectname, $this->projects_in_use ) ) {
			$this->projects_in_use[$projectname] = true;
			if ( ! $wwContext->wwStorage->is_standalone_name( $projectname ) ) {
				if ( !is_array( $this->projects_to_list ) ) {
					$this->projects_to_list = array();
				}
				$this->projects_to_list[$projectname] = true;
				try {
					# mark this page as if it linked to all the File: (Image:)
					# pages involved in the project, so that changing them will
					# cause this page to reparse.
					$project = $wwContext->wwStorage->find_project_by_name($projectname);
					global $wgParser;
					if ( $wgParser != null and $wgParser->getOutput() != null and
					     $project instanceOf ProjectDescription and
					     is_callable(array($project,'source_image_pages') ) ) { 
						foreach ($project->source_image_pages() as $fpage) { 
							$ftitle = Title::newFromText($fpage);
							$wgParser->getOutput()->addImage($ftitle->getDBKey());
						}
					}
				} catch (WWException $ex) {
				}
			}
		}
		return;
		# store it directly in the page_props data - will this get it saved?
		# no
		global $wgParser;
		$projs = $wgParser->mOutput->getProperty('ww_projects');
		if ($projs != '') $projs .= ' ';
		$projs .= $projectname;
		$wgParser->mOutput->setProperty('ww_projects',$projs);
		#wwLog("add to ww_projects: $projs\n");
	}

	# mark a project's description modified, so as to save it 
	# when the time is right.
	public function project_is_modified($projectname) {
		global $wwContext;
		# this should be done by the ProjectDescription class or something
		if ( ! $wwContext->wwStorage->is_standalone_name( $projectname ) ) {
			$this->modified_projects[$projectname] = true;
		}
	}

	# know whether the syntax highlighter is installed
	public function hasSyntaxHighlighter() {
		global $wwUseSyntaxHighlighter, $wgExtensionCredits;
		return $wwUseSyntaxHighlighter and
			array_key_exists(
				'SyntaxHighlight_GeSHi',
				$wgExtensionCredits['parserhook']
			);
	}

	# Make sure to catch WWExceptions in these functions any time you
	# call private functions that may raise them, or ProjectDescription
	# functions.

	# How to display a project file?	Depends on its file extension.
	# source : source listing
	# image	 : inline image url
	# html   : inline HTML
	# wikitext : parse and display wikitext
	# link   : a link to view the file
	# download : a link to download the file
	public function default_display_mode($extension) {
		global $wwLinkExtensions, $wwImageExtensions;
		global $wwInlineImageExtensions, $wwHtmlExtensions, $wwTextCompatibleExtensions;
		if (in_array($extension, $wwInlineImageExtensions)) {
			return 'image';
		}
		if ( in_array($extension,$wwLinkExtensions) or
		     in_array($extension,$wwImageExtensions) ) {
			return 'link';
		}
		if (in_array($extension, $wwHtmlExtensions)) {
			return 'html';
		}
		if ($extension == 'wikitext' or $extension == 'mw') {
			return 'wikitext';
		}
		return 'source';
	}

	public function add_altlinks_to_project_file_html( $html, $project, $filename, $display_mode, $args, $alts ) {

		if ( $display_mode == 'html' ) {
			# this special case bypasses the alt_text and makes the output
			# itself be a link to the log file.
			if ( $project->is_standalone() ) {
				$alts = $this->alternatives_for_file($project,$filename,$args,array('log'=>''));
				return "<a href=\""
					. $alts['log']['url']
					. "\" class=\"ww-tex-math-link\">"
					. $html
					. "</a>";
			} else {
				# fold the fake edit links into the HTML
				$alt_text = $this->altlinks_text($project,$filename,$args,$alts);
				if ($alt_text != '') {
					# there's some special processing here: if the latex paper
					# begins with a title, we want to put the altlinks inside the
					# title element, because there is css to make it float above
					# the text in that case, so it doesn't push the title off
					# center.  But when the first line isn't a title we don't want
					# to float above, we want to make the paragraph wrap around it.
					# I guess if there's a title but not at the beginning this
					# code will behave counter-intuitively.
					# This should be cleaned up, both for the tex->html cases, and
					# for the case of general, third-party-created html.

					# try putting the alt links at the title.
					$count = 0;
					# newer latexml
					if ($count == 0) {
						$html = preg_replace(
							'/(<h1[^>]*\bltx_title_document\b.*?'.'>)/i',
							"\\1 $alt_text",
							$html,
							1,
							$count
						);
					}
					# latexml html5 version
					if ($count == 0) {
						if ( ! ( preg_match(
								'/^(.*?)<h1[^>]*\btitle\b.*?'.'>/si',
								$html,
								$matches
							) and
							preg_match(
								'/(<p|<ul)\b/i',
								$matches[1]
							)
					       	) ) {	
							$html = preg_replace(
								'/(<hgroup>\s*<h1[^>]*\btitle\b.*?'.'>)/i',
								"$alt_text\\1",
								$html,
								1,
								$count
							);
						}
					}
					if ($count == 0) {
						$html = preg_replace(
							'/(<div\b[^>]*"document".*?'.'>)/i',
							"\\1\n$alt_text",
							$html,
							1,
							$count
						);
					}
					# if not, put them at the beginning.
					if ($count == 0) {
						$html = preg_replace('/^(<div.*?'.'>)/is',
							"\\1\n$alt_text",$html,1,$count);
					}
					if ($count == 0) {
						$html = "$alt_text$html";
					}
					return $html;
				}
			}
		}
		// default behavior
		$altlinks_text = $this->altlinks_text($project,$filename,$args,$alts);
		return $altlinks_text . $html;
	}

	# Given we know what file to display, render it appropriately
	# if $text argument is not null, use it rather than content of file
	#  (depending on file type)
	#
	# $alts controls what links such as [log] appear with the file contents.
	public function display_file_contents( &$project, $filename, $text, 
			$display_mode=false, $alts=null, $line=false, $args=array(), 
			$parser=null, $getprojfile=false ) {
		wwProfileIn( __METHOD__ );
		$pnm = ($project ? $project->project_name() : '');
		#wwLog( "display_file_contents: {$pnm} | $filename | " . json_encode($text) . " | $display_mode | " .json_encode( $alts ) . " | $line | " . json_encode( $args ) . " | " . (is_null($parser) ? 'NULL' : '(parser)') . " | $getprojfile " );
		try {
			$extension = ProjectDescription::type_of_file($filename);
			$dm_given = $display_mode;
			if (!$display_mode) {
				$display_mode = $this->default_display_mode($extension);
			}
			if ($display_mode == 'none') {
				return $this->add_altlinks_to_project_file_html( 
					'',
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);
			}
			# don't link to GetProjectFile if we're already there, link to the file
			if ($getprojfile and $display_mode == 'link') {
				$display_mode = 'download';
			}
			#wwLog("How to display '". ($filename)
			#	. "' given display mode '" . ($dm_given) 
			#	. "'? '" . ($display_mode) . "'.	Make = {$args['make']}.\n");
			global $wwValidateProjectFiles;
			# if we are just displaying a link to view the file, we don't
			# have to make the file, the GetProjectFile page will do it.
			if ($display_mode == 'link' or $display_mode == 'download') {
				$linktext = (isset($args['linktext']) ? $args['linktext'] : $filename);
				# if GPF will display it as download by default, put display=download.
				# this helps the javascript avoid an irritating warning in preview.
				$disp = $display_mode;
				if ( $disp == 'link' ) {
					$disp = $this->file_to_display(
						$project,
						false,
						array('filename'=>$filename)
					);
					$disp = ($disp == 'link' ? 'download' : null);
				}
				# if it has make="no", make a link with make="yes" and vv
				$text = '<a href="' 
					. $this->make_get_project_file_url(
						$project,
						$filename,
						/*make*/(!isset($args['make']) or !$args['make']) and !$getprojfile,
						/*display_mode*/$disp
					)
					.'">'
					. htmlspecialchars($linktext)
					. '</a>';
				$text = $this->add_altlinks_to_project_file_html(
					$text,
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);
				wwProfileOut( __METHOD__ );
				return $text;
			}

			# check extension for what to do

			# most image types are displayed using <img>
			# note we do this even if 'source' is requested if it's 
			# a binary file
			# note removing that check - is that okay?
			if ($display_mode == 'image' and $extension != 'svg') {
				# the verify function raises an exception if there's a problem
				if ($wwValidateProjectFiles) {
					self::verify_file_before_displaying($filename,$project,$text);
				}

				$img_url = $this->make_get_project_file_url(
					$project,
					$filename,
					/*make*/false,
					/*display*/'raw'
				); 
				wwProfileOut( __METHOD__ );
				return $this->add_altlinks_to_project_file_html(
					"<img src=\"$img_url\" alt=\"("
					. htmlspecialchars($filename)
					. ")\"/>",
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);
			}

			if ($text === null) {
				$text = $this->retrieve_file_contents($project,$filename); 
				if ($text === null) {
					$this->throw_error(
						"File ‘"
						. $filename
						. "’ not found in working directory."
					);
				}
			}
			if ($text == WW_FILETOOBIG) {
				$this->record_message(
					"File '"
					. htmlspecialchars($filename)
					. "' is too large for"
					. " display.	Displaying a link to the file instead."
				);
				wwProfileOut( __METHOD__ );
				return $this->display_file_contents(
					$project,
					$filename,
					'',
					'download',
					$alts,
					$line,
					$args,
					$parser,
					$getprojfile
				);
			}

			# svg image handled specially
			# http://labs.silverorange.com/archive/2006/january/howtoinclude
			if ($display_mode == 'image' and $extension == 'svg') {

				# if it's a complete SVG document, put it in an iframe
				# and give it the relative-URL trick.
				if (0 and preg_match('/^(<\?xml|<!doctype)/i', $text)) {
					//wwLog( 'SVG in iframe' );
					$url = preg_replace(
						'/\?title=|\?|&amp;|&/',
						'/',
						preg_replace(
							'/(\/|&|&amp;)filename=/i',
							'', 
							preg_replace(
								'/%2F/i',
								'%5C/', # internal '/' becomes '\/'
								$this->get_project_file_base_url(
									$project,
									'',
									/*make*/false,
									/*display*/'raw'
								)
							)
						)
					);
					return $this->add_altlinks_to_project_file_html(
						'<iframe class="ww-project-file-iframe" '
						. 'width="100%" height="1000px" '
						. 'border="0" frameborder="0" '
						. 'src="' 
						. $url
						. '/'
						. $filename
						. '"></iframe>',
						$project,
						$filename,
						$display_mode,
						$args,
						$alts
					);	
				}

				//wwLog( 'SVG in object' );
				$svg_img = $this->make_get_project_file_url(
					$project,
					$filename,
					/*make*//*true*/false,
					/*display*/'raw'
				);
				$pngfilename = preg_replace('/\.svg$/i','.png',$filename);
				# assume making a png file from svg, for browsers that don't do
				# svg, is up to us
				$png_img = $this->make_get_project_file_url(
					$project,
					$pngfilename,
					/*make*/true,
					/*display*/'raw'
				);
				wwProfileOut( __METHOD__ );
				return $this->add_altlinks_to_project_file_html(
					"<object data=\"$svg_img\" type=\"image/svg+xml\">"
					. "<img src=\"$png_img\" alt=\""
					. htmlspecialchars($filename) 
					. "\"/></object>",
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);
			}

			# html content is included as is
			if ($display_mode == 'html') {
				# the sync step is needed for the verify step
				# FIXME: sync? verify?
				#if ($text !== null and $project->is_file_source($filename))
				#	$project->sync_source_file($filename,/*force*/true);
				# the verify function raises an exception if there's a problem
				#if ($wwValidateProjectFiles)
				#	self::verify_file_before_displaying($filename,$project,$text);

				# if it's a complete page, put it in an iframe.
				if (preg_match('/^(<html|<!doctype)/i', $text)) {
					# stupid problem with encoded slashes in preview page.	fortunately,
					# don't need it for this URL.
					#$url = preg_replace('/\/wwPreviewPage=[^&]*/', '', $url);
					$url = preg_replace(
						'/\?title=|\?|&amp;|&/',
						'/',
						preg_replace(
							'/(\/|&|&amp;)filename=/i',
							'', 
							preg_replace(
								'/%2F/i',
								'%5C/', # internal '/' becomes '\/'
								$this->get_project_file_base_url(
									$project,
									'',
									/*make*/false,
									/*display*/'raw'
								)
							)
						)
					);
					return $this->add_altlinks_to_project_file_html(
						'<iframe class="ww-project-file-iframe" '
						. 'width="100%" height="1000px" '
						. 'border="0" frameborder="0" '
						. 'src="' 
						. $url
						. '/'
						. $filename
						. '"></iframe>',
						$project,
						$filename,
						$display_mode,
						$args,
						$alts
					);
				}

				# change relative links to GetProjectFile links, assuming they
				# refer to files in the project directory.
				$base = $this->get_project_file_base_url($project,'',false,'download');
				$base = str_replace(array('\&','\;'),array('&',';'),$base); # :(:(
				#wwLog("before the preg replace:\n=====\n$text\n=====\n");
				$text = preg_replace(
					'{src="([^/:"]+(/[^/"]+)*)"}i',
					"src=\"$base\$1\"",
					$text
				);
				#wwLog("after the preg replace:\n=====\n$text\n=====\n");
				$text = $this->add_altlinks_to_project_file_html(
					$text,
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);

				if (preg_match('/latexml/i',$filename)) {
					$this->include_latexml_resources( $parser );
				}

				wwProfileOut( __METHOD__ );
				return $text;
			}
			if ($display_mode == 'wikitext') {
				# let's say wikitext doesn't need to be validated 
				global $withinParser;
				$temp = $withinParser;
				$withinParser = 0;
				if (!is_null($parser)) {
					$lparser = clone $parser;
				} else {
					$lparser = new Parser;
					global $wgUser, $wgTitle;
					$lparser->setUser($wgUser);
					$lparser->setTitle($wgTitle);
				}
				if (method_exists('ParserOptions', 'newFromUser')) {
					$lparserOptions = new ParserOptions($lparser->getUser());
				} else {
					$lparserOptions = new ParserOptions;
					$lparserOptions->initialiseFromUser($lparser->getUser());
				}
				$lparserOptions->setEditSection(false); //no edit links on sections
				$prev_default_projname = $this->default_project_name;
				$this->default_project_name = $project->project_name(); 
				#wwLog( 'parsing wikitext with title=' . $lparser->getTitle()->getPrefixedText() );
				#$text = $this->replace_inlines( $text );
				$save_parsing_key = $this->currently_parsing_key;
			        $this->currently_parsing_key = $lparser->getTitle()->getPrefixedDBKey();
				#$wwContext->wwStorage->cache_text_directly( $text, '', null );
				$parserOutput = $lparser->parse( $text, $lparser->getTitle(), $lparserOptions);
				$this->currently_parsing_key = $save_parsing_key;
				$this->default_project_name = $prev_default_projname;
				$text = $parserOutput->getText();
				# can't use getHeadItems() in MW 13
				foreach ($parserOutput->mHeadItems as $key=>$value) {
					global $wgOut;
					if ($parser !== null) {
						$parser->mOutput->addHeadItem($value, $key);
					} else {
						$wgOut->addHeadItem($key,$value);
					}
				}
				global $wgParser;
				if ($parser !== null) {
					foreach ( $parserOutput->getCategories() as $k => $v ) {
						$parser->mOutput->addCategory( $k, $v );
					}
				} else if ( $wgParser !== null && $wgParser->mOutput !== null ) {
					foreach ( $parserOutput->getCategories() as $k => $v ) {
						$wgParser->mOutput->addCategory( $k, $v );
					}
				}
				$this->render_after_parsing($parser,$text);
				$withinParser = $temp;
				wwProfileOut( __METHOD__ );
				return $this->add_altlinks_to_project_file_html(
					$text,
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);
			}
			# other types are displayed with source highlighting
			if ($display_mode == 'source') {
				global $wwMaxLengthForSyntaxHighlighting,
					$wwMaxLengthForSourceCodeDisplay;
				$filesize = ($text !== null ? strlen($text) : false);
				#wwLog("filesize is ". print_r($filesize, true) 
				#	. " with text " . ($text === null ? '===':'!==') . " null\n");
				if ($filesize > $wwMaxLengthForSourceCodeDisplay) {
					#if ($special == 'source')
					#  $this->record_message("File	‘"
					#    . htmlspecialchars($filename) . "’ is too large for source"
					#    . " code display.	Displaying a link to the file instead.");
					wwProfileOut( __METHOD__ );
					return $this->display_file_contents(
						$project,
						$filename,
					       	'',
						'download',
						$alts,
						$line,
						$args,
						$parser,
						$getprojfile
					);
				}
				if ( ! ( mb_detect_encoding( $text, 'ASCII', true ) or
					mb_detect_encoding( $text, 'UTF-8', true ) ) ) {
					wwLog( 'refusing source display because of non-unicodicity' );
					wwProfileOut( __METHOD__ );
					return $this->display_file_contents(
						$project,
						$filename,
						'',
						'download',
						$alts,
						$line,
						$args,
						$parser,
						$getprojfile
					);
				}
				$useSH = $this->use_syntax_highlighting();
				if ( $useSH and $filesize != false and
				     $filesize > $wwMaxLengthForSyntaxHighlighting ) {
					$useSH = false;
					#wwLog("Suppressing syntax highlighting for over-long file '"
					#  . $filename . "'.\n");
				}
				# it might seem source code display doesn't need validation
				# but it is possible to put weird stuff into files with innocuous
				# extensions, so let's be cautious.
				# well, actually let's not.  the syntax highlighter ought to produce
				# clean output.
				if ($wwValidateProjectFiles and !$useSH) {
					self::verify_file_before_displaying($filename,$project,$text);
				}
				$parsed_text = null;
				if (isset($args['lang'])) {
					$lang = $this->get_language_name_from_extension($args['lang']);
					if ($lang == '') {
						$lang = $args['lang'];
					}
				} else {
					$lang = $this->get_language_name_from_extension($extension);
				}
				if ($lang == '') {
					$lang = 'text';
				}
				#wwLog( 'before: ' . $text );
				if ($extension == 'makefile' or $extension == 'mk' or $extension == 'd') {
					if ( $useSH ) {
						$sh_formatting = $line ? ' line="true"' : ' enclose="pre"';
						$pr = ( $project instanceOf ProjectDescription ?
					   		$project->project_name() : $project );
						$pr = ' project="' . htmlspecialchars($pr) . '"';
						wwLog( 'setting up syntaxhighlight_mk, pr = "' . $pr . '"' );
						$parsed_text = $this->parse_wikitext( 
							"<syntaxhighlight_mk lang=\"$lang\"$sh_formatting$pr>"
							. wwfSanitizeInput($text)
							. "</syntaxhighlight_mk>",
							$parser
						);
					} else {
						$parsed_text = $this->add_links_to_makefile(
							'<pre>' . $text . '</pre>', $project );
					}
				} else if ( $useSH ) {
					$sh_formatting = $line ? ' line="true"' : ' enclose="pre"';
					$parsed_text = $this->parse_wikitext( 
						"<source lang=\"$lang\"$sh_formatting>"
						. wwfSanitizeInput($text)
						. "</source>",
						$parser
					);
				}
				if ( ! $parsed_text or strlen($parsed_text) < strlen($text) ) {
					#wwLog( "not using syntaxhighlighter; text is " . $text );
					#wwLog( "sanitized is ". $text );
					$parsed_text = "<pre>" . htmlspecialchars(wwfSanitizeInput($text)) . "</pre>";
				}
				$text = $parsed_text;
				#wwLog( 'after: ' . $text );

				# TODO this is odd, doing the add_altlinks before
				# wrapping it in a fieldset
				$text = $this->add_altlinks_to_project_file_html(
					$text,
					$project,
					$filename,
					$display_mode,
					$args,
					$alts
				);
				$text = '<fieldset class="ww-project-file-source ww-collapsible">'
					. '<legend><span>'.htmlspecialchars($filename).'</span></legend>'
					. '<div class="ww-collapsible-content">'
					. $text . '</div></fieldset>';
				wwProfileOut( __METHOD__ );
				return $text;
			}
			$this->throw_error("Unknown display mode '"
				. htmlspecialchars($display_mode) . "'.");
		} catch (WWException $ex) {
		}
		wwProfileOut( __METHOD__ );
		return '';
	}

	public function include_latexml_resources( $parser ) {
		global $wwUsesOldLaTeXML;
		#wwLog( 'include_latexml_resources' );
		if ( $wwUsesOldLaTeXML ) {
			#wwLog( 'include_css' );
			$this->include_css('latexml', $parser);
			$this->include_css('latexml.compat', $parser);
		} else {
			if ( $parser ) {
				#wwLog( 'parser modules' );
				$parser->getOutput()->addModules( 'ext.workingwiki.latexml' );
			} else if ( class_exists( 'RequestContext' ) ) {
				#wwLog( 'wgOut modules' );
				RequestContext::getMain()->getOutput()->addModules( 'ext.workingwiki.latexml' );
			}
		}
	}

	public function link_to_project_file( $filename, $project, $linktext ) {
		if (is_object($project)) {
			$target = $this->make_get_project_file_url($project,$filename,false);
			return "<a href='$target'>$linktext</a>";
		} else {
			# look out for the no-project case! when called from GPF!
			global $wgUser;
			return $wgUser->getSkin()->makeLinkObj(
				SpecialPage::getTitleFor( 'GetProjectFile' ),
				$linktext,
			       	"filename=" . urlencode($filename) . "&make=false",
				''
			);
			# TODO - not compatible with Preview
		}
	}

	public function parse_wikitext( $text, $parser = null ) {
		#wwLog( 'in parse_wikitext()' );
		global $wwContext;
		if ( ! is_null( $parser ) ) {
			$lparser = clone $parser;
			$title = $parser->getTitle();
		} else {
			$lparser = new Parser;
			global $wgTitle;
			$title = $wgTitle;
		}
		if ( ! $title instanceOf Title ) {
			$title = Title::newFromText( '(?)' );
		}
		$save_parsing_key = $this->currently_parsing_key;
		$this->currently_parsing_key = '';
		$wwContext->wwStorage->cache_text_directly( $text, '', null );
		$this->currently_parsing_key = $save_parsing_key;
		# note: need to set WWInterface's $_page_being_parsed ?
		$output = $lparser->parse(
			$text,
			$title,
			new ParserOptions()
		);
		# what's the ParserOutput got?  I probably need to grab at least headItems and Modules
		# for the Ajax file rendering.
		#wwLog( "ParserOutput getCategoryLinks: " . json_encode( $output->getCategoryLinks() ) );
		#wwLog( "ParserOutput getCategories: " . json_encode( $output->getCategories() ) );
		#wwLog( "ParserOutput getHeadItems: " . json_encode( $output->getHeadItems() ) );
		#wwLog( "ParserOutput getModules: " . json_encode( $output->getModules() ) );
		#wwLog( "ParserOutput getModuleScripts: " . json_encode( $output->getModuleScripts() ) );
		#wwLog( "ParserOutput getModuleStyles: " . json_encode( $output->getModuleStyles() ) );

		$text = $output->getText();
		#wwLog( 'parsed text is ' . $text );
		# don't let it change the title - this messes with special pages
		$output->setDisplayTitle(false);
		if ($parser !== null and $parser->mOutput !== null) {
			$poutput = $parser->getOutput();
			foreach ($output->mHeadItems as $key=>$section) {
				$poutput->addHeadItem($section,$key);
			}
			$poutput->addModules( $output->getModules() );
			$poutput->addModuleScripts( $output->getModuleScripts() );
			$poutput->addModuleStyles( $output->getModuleStyles() );
			$poutput->addModuleMessages( $output->getModuleMessages() );
		} else {
			global $wgOut;
			$wgOut->addParserOutputNoText($output);
		}
		return $text;
	}

	# syntax highlight a makefile AND add links to it.
	public function syntaxhighlight_mk_hook($text, $args, $parser) {
		wwProfileIn( __METHOD__ );
		global $wwContext;
		try {
			$pr = ( $args['project'] != '' ? 
				$wwContext->wwStorage->find_project_by_name($args['project']) : null );
			unset($args['project']);
			$text = $this->parse_wikitext(
				'<source lang="make">' . $text . '</source>',
				$parser
			);
			#$text = SyntaxHighlight_GeSHi::parserHook($text,$args,$parser);
			$text = $this->add_links_to_makefile($text, $pr);
		}
		catch (WWException $ex) {}
		wwProfileOut( __METHOD__ );
		return $text;
	}

	# 2 callback functions used by add_links_to_makefile()
	public function _clean($excerpt) {
		return preg_replace('/<.*?'.'>/','',$excerpt);
	}

	public function _link_a_filename($excerpt,$project) {
		if ($excerpt == '') {
			return '';
		}
		#wwLog("trying link stuff on '$excerpt'\n");
		// if it's a self-contained html element we can make it a link.
		// but is it a fragment?
		$anchor = $this->link_to_project_file( $this->_clean($excerpt), $project, $excerpt );
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($anchor);
		if ($sxe) {
			#wwLog( "linked it: $anchor" );
			return $anchor;
		}
		// if that failed, try the word<span> case, because that's what we get
		// when the line starts with word:, and that's an important case.
		#wwLog("the main case failed on $anchor\n");
		if (preg_match('/^(.*)(<.*?'.'>)$/',$excerpt,$matches)) {
			return $this->_link_a_filename($matches[1],$project).$matches[2];
		}
		#wwLog("the special case failed too\n");
		return $excerpt;
	}

	# this should work for the output of GeSHi as well as for an 
	# unaltered makefile.
	public function add_links_to_makefile($text,$project) {
		#wwLog( 'add_links_to_makefile, project = ' . ( $project ? $project->project_name() : '' ) );
		# GeSHi leaves each makefile line on a separate line and adds
		# <span class="...">...</span> markup to various stretches of text.
		# spaces between things that aren't within a tag seem to be between
		# words of the makefile so...
		# This probably won't work for DIV style GeSHi output...
		$lines = explode("\n",$text);
		foreach ($lines as &$line) {
			#wwLog( "line: $line" );
			if ( preg_match('/^[^\s#][^#]*:/i',html_entity_decode(preg_replace('/<.*?'.'>/','',$line))) or
			     preg_match('/^([^\s#]*|<span[^>]*>)include/i',$line) ) {
				$tokens = preg_split(
					'/(<.*?' . '>|\s+|&[^;]{1,7};|:|#)/i',
					$line,
					null,
					PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
				);
				#wwLog( 'tokens: ' . json_encode($tokens) );
				$line = $word = '';
				$tokens[] = '';
				$comment = false;
				foreach ($tokens as $token) {
					#wwLog("token: [".$token."]");
					if (preg_match('/^(<span.*?'.'>|<\/span>|[\w\.\-+\/\$_%]+)$/i',$token)) {
						$word .= $token;
					} else { 
						if ( $word != '' ) {
							if ( !$comment and !preg_match('/(^|[^\\\\])(\$|%)/',$word) ) {
								$word = $this->_link_a_filename($word,$project);
							}
							$line .= $word;
							//wwLog("word: ".$word);
							$word = '';
						}
						if ($token == '#') {
							$comment = true;
						}
						$line .= $token;
					}
				}
			}
		}
		return implode("\n",$lines);
	}

	# we use the SpecialUpload class's verify() to check for exploitative
	# code in our files before we allow them to be displayed.
	# if there's an issue with a file, raise a WWException, which will cause
	# an error message to be displayed in place of the file contents.
	public function verify_file_before_displaying($filename,&$project,$text) {
		global $wgRequest;
		$uploadObject = new UploadFormRow($this,'',$wgRequest);
		list( $partname, $ext ) = $uploadObject->splitExtensions( $filename );
		#if( count( $ext ) ) {
		#	$finalExt = $ext[count( $ext ) - 1];
		#} else {
		#	$finalExt = '';
		#}
		$finalExt = ProjectDescription::type_of_file($filename); 
			/* Check the file extension */
		global $wwStrictFileExtensionsForDisplay;
		global $wwFileExtensionsForDisplay, $wwFileBlacklistForDisplay;
		# files without extension are permitted unless extension '' is forbidden
		//if ($finalExt == '') {
		//	$this->throw_error('Unpermitted file extension '.
		//		"(i.e. no extension) for file ‘{$filename}’.");
		//} else
		if ( $uploadObject->checkFileExtensionList( $ext, $wwFileBlacklistForDisplay ) ||
			($wwStrictFileExtensionsForDisplay &&
				!$uploadObject->checkFileExtension( $finalExt, $wwFileExtensionsForDisplay ) ) ) {
			//$this->throw_error(
			$this->record_warning(
				'Unpermitted file extension '
				. "for file ‘"
				. htmlspecialchars($filename)
				. "’.	(This"
				. " is a new security feature under development and may reject "
				. 'some valid file types.  In the future files that are rejected '
				. 'will not be displayed by the wiki software.  If this feature '
				. 'is flagging valid files, please tell '
				. 'Jonathan or Lee so we can correct it during this testing phase.)'
			);
		}

		if ($text !== null) {
			$tempbase = urlencode($project->project_name()).'_'.mt_rand(0, 9999999);
			global $wwTempDirectory;
			$tempdir = "$wwTempDirectory/$tempbase";
			if ( file_exists($tempdir) or is_link($tempdir) ) {
				$this->throw_error("Couldn't create temp directory "
					. htmlspecialchars("...$tempbase"));
			}
			if ( ! mkdir($tempdir) ) {
				$this->throw_error("Couldn't make temp directory "
					. htmlspecialchars("...$tempbase"));
			}
			$filepath = "$tempdir/$filename";
			if (strpos($filename,'/') !== false) {
				$filepath_inner = preg_replace('{/[^/]*$}','',$filepath);
				if ( ! mkdir($filepath_inner, 0777, true) ) {
					$this->throw_error("Couldn't create directory for "
						. ".../$tempbase/$filename."); 
				}
			}
			if (($write_file = fopen($filepath,"w")) === false) {
				$this->throw_error(
					"Couldn't open "
					. htmlspecialchars(".../$tempbase/$filename")
					. " for writing.");
			}
			#if (fwrite($write_file,$text."\n") == false)
			if (fwrite($write_file,$text) === false) {
				$this->throw_error(
					"Couldn't write to "
					. htmlspecialchars(".../$tempbase/$filename")
					. "."
				);
			}
			if (fclose($write_file) === false) {
				$this->throw_error(
					"Couldn't close " 
					. htmlspecialchars(".../$tempbase/$filename")
					. " after writing."
				);
			}
		} else {
			#$filepath = $project->project_directory().'/'.$filename;
			#if ( ! file_exists($filepath) )
			$this->throw_error(
				"File ‘"
				. htmlspecialchars($filename)
				. "’ not found."
			);
		}

		if ($finalExt == '') {
			# special case, it should be able to verify as a text file
			$finalExt = 'txt';
		}
		$veri = $uploadObject->verify($filepath, $finalExt);
		if (isset($tempdir)) {
			wwfRecursiveUnlink($tempdir,true);
		}
		if ($veri !== true) {
			//$this->throw_error(
			$this->record_warning(
				"Project file ‘" 
				. htmlspecialchars($filename)
				. "’ did not pass "
				. 'the content validation step: ' //. htmlspecialchars(print_r($veri,true)) );
				. htmlspecialchars($veri->toString())
				. '  (This is an experimental security feature and may flag '
				. 'some valid files.  In the future rejected files will not '
				. 'be displayed.  If it is complaining about files you believe are '
				. 'not dangerous, please '
				. 'alert Jonathan or Lee so we can correct it during this testing '
				. 'phase.)'
			);
		}
	}

	public function include_css($name,$parser) {
		wwProfileIn( __METHOD__ );
		try {
			$resproj = ResourcesProjectDescription::factory();
			if (1) {
				# clumsy version: include the file contents inline (but faster?)
				$css = $this->retrieve_file_contents($resproj,"css/$name.css");
				$headItem = '<style type="text/css">/*<![CDATA[*/'
					. "\n$css/*]]>*/\n</style>\n";
			} else {
				# fancy version: link to the css project file
				$headItem = '<link rel="stylesheet" type="text/css" href="'
					. $this->make_get_project_file_url($resproj,"css/$name.css",
							/*make*/false, /*display*/'raw')
					. "\" media=\"all\"/>\n";
			}
			# the parser is null if we're called from Special:GetProjectFile
			# BUT IT MIGHT NOT BE!	WHAT A MESS!
			global $wgOut;
			global $wgTitle;
			global $wgParser;
			#$prs = ($parser === null ? 'null' : 
			#	($parser === $wgParser ? 'wgParser' : '(other parser?!)'));
			#$tit = ($wgTitle instanceOf Title ? $wgTitle->getPrefixedText() : 
			#	serialize($wgTitle));
			#wwLog("include_css '$name' parser=$prs wgTitle=$tit\n");
			#if ($wgTitle instanceOf Title
			#			and $wgTitle->getNamespace() != NS_SPECIAL
			#			and !is_null($parser))
			#{ $parser->mOutput->addHeadItem($headItem,$name); }
			#else
			if (!is_null($parser)) {
				#wwLog("Give $name to parser\n");
				$parser->getOutput()->addHeadItem($headItem,$name); 
			}
			#else if (!is_null($wgParser) and !is_null($wgParser->mOutput))
			#{ #wwLog("Give $name to wgParser\n");
			#	$wgParser->mOutput->addHeadItem($headItem,$name); 
			#}
			else { #if ($wgOut instanceOf OutputPage)
				try {
					#wwLog("Give $name to wgOut\n");
					$wgOut->addHeadItem($name,$headItem);
				} catch (Exception $ex) {
					$this->throw_error("Internal error: Can't insert '$name' css");
				}
			}
		} catch (WWException $ex) {
		}
		wwProfileOut( __METHOD__ );
	}

	public function include_js($name,$parser) {
		try {
			$resproj = ResourcesProjectDescription::factory();
			$headItem =
			       	'<script type="text/javascript" src="'
				. $this->make_get_project_file_url(
					$resproj,
					"js/$name.js",
					/*make*/false,
					/*display*/'raw'
				)
				. "\"></script>\n";
			global $wgTitle;
			if ( $wgTitle->getNamespace() != NS_SPECIAL and
			     ! is_null($parser) ) {
				$parser->mOutput->addHeadItem($headItem,$name);
			} else {
				global $wgOut;
				$wgOut->addHeadItem($name,$headItem);
			}
		} catch (WWException $ex) {
		}
	}

	public function sse_key_if_any() {
		return RequestContext::getMain()->getRequest()->getVal( 'logkey', null );		
	}

	public function amend_PE_request( &$request ) {
		wwRunHooks('WW-PERequest', array(&$request));
		$logkey = $this->sse_key_if_any();
		if ( $logkey ) {
			$request['sse-log-key'] = $logkey;
		}
	}

	public function can_check_file_for_inclusions($filename) {
		return substr($filename,-4) == '.tex';
	}

	# load in the file contents.  Assume all making is already done if needed.
	# if it's a source-file it can be retrieved directly from the wiki, but
	# first check in the working directory (because what about makefiles that
	# modify the source files?  I don't recommend it, but we can at least not
	# mislead the user about what's in there.)
	public function retrieve_file_contents($project, $filename) {
		wwProfileIn( __METHOD__ );
		$pname = ($project === null ? '' : $project->project_name());
		if ( ! array_key_exists( $pname, $this->cache_file_contents ) or
		     ! array_key_exists( $filename, $this->cache_file_contents[$pname] ) ) {
			#$this->debug_message("retrieve_file_contents: $filename");
			$pe_result 
				= ProjectEngineConnection::call_project_engine(
					'retrieve',
				       	$project, 
					array('target'=>$filename)
				);
			if (!is_array($pe_result)) {
				wwProfileOut( __METHOD__ );
				$this->throw_error("Call to ProjectEngine failed.");
			}
			if (isset($pe_result['target-file-contents'])) {
				foreach ($pe_result['target-file-contents'] as $filename=>$content) {
					$this->cache_file_contents[$pname][$filename]
						= $content;
				}
			}
			if ( ! isset( $this->cache_file_contents[$pname] ) or
			     ! isset( $this->cache_file_contents[$pname][$filename] ) or
			     $this->cache_file_contents[$pname][$filename] === null ) {
				#if (is_array($pe_result['peDirectoryContents']) and
				#  array_key_exists($filename,$pe_result['peDirectoryContents']))
				#  return $pe_result['peDirectoryContents'][$filename];
				wwProfileOut( __METHOD__ );
				$this->throw_error( "Project file not found: " 
					. htmlspecialchars($pname) . " › "
					. htmlspecialchars($filename));
			}
		}
		wwProfileOut( __METHOD__ );
		switch ($this->cache_file_contents[$pname][$filename][0]) {
			case 'c':
				return $this->cache_file_contents[$pname][$filename][1];
			case 'p':
				$path = $this->cache_file_contents[$pname][$filename][1];
				global $wwMaxProjectFileSize;
				if (filesize($path) > $wwMaxProjectFileSize) {
					$this->record_error(
						"Project file "
						. htmlspecialchars($filename)
						. " exceeds the file size limit of "
						. htmlspecialchars($wwMaxProjectFileSize)
						. " bytes."
					);
					return WW_FILETOOBIG;
				}
				return file_get_contents($path);
			case 'd':
			default:
				return null;
		}
	}

	# just to find out if it exists.  this is equivalent to calling
	# retrieve_file_contents and seeing if the result is null, except
	# when the file's a directory.	We don't want to display directory
	# contents as source listings!	But when a make target is a directory, 
	# we don't want to erroneously say 'make succeeded but did not create
	# the file'.
	public function does_file_exist($project, $filename) {
		$tx = $this->retrieve_file_contents($project,$filename);
		$pname = ($project === null ? '' : $project->project_name());
		if ( array_key_exists($pname,$this->cache_file_contents) and
		     array_key_exists($filename,$this->cache_file_contents[$pname]) ) {
			return true;
		}
		return false;
	}

	public function make_dynamic_placeholder( &$project, $filename, $display_mode, $source, $make, $alts, $args, $placeholder, &$parser ) {
		$mods = array(
			'ext.workingwiki.dynamic-project-files',
			'ext.workingwiki.dynamic-project-files.top',
		);
		if ( $parser and $parser->getOutput() ) {
			$parser->getOutput()->addModules( $mods );
		} else {
			RequestContext::getMain()->getOutput()->addModules( $mods );
		}
		$dpfid = 'ww-dpf-' . $this->dynamic_placeholder_counter++;
		unset( $args['filename'], $args['project'], $args['make'] );
		// no whitespace outside the span, lest it break formatting
		// after the file's loaded
		$html = '<span class="ww-project-file-source ww-dynamic-project-file ww-dynamic-project-file-unprocessed"'
			. " id=\"$dpfid\""
			. ' data-filename="'
			. htmlspecialchars( $filename ) . '"';
		if ( $display_mode ) {
			$html .= ' data-display="'
				. htmlspecialchars( $display_mode ) . '"';
		}
		$html .= ' data-make="'
			. ( $make ? 1 : 0 ) . '"'
			. ' data-source="'
			. ( $source ? 1 : 0 ) . '"'
			. ' data-altlinks="'
			. htmlspecialchars( $this->make_altlinks( $alts ) ) . '"';
		if ( count( $args ) ) {
			$html .= ' data-tag-args="'
				. htmlspecialchars( json_encode( $args ) ) . '"';
		}
		$html .= ( $project ? ' ' . $project->data_for_dynamic_placeholder() : '' )
			. '>'
			#. '<legend><span>'
			#. htmlspecialchars( $filename )
			#. '</span></legend>'
			. '<span class="ww-dynamic-project-file-placeholder">'
			. $placeholder
			. "</span></span>";
		global $wgVersion;
		if ( version_compare( $wgVersion, '1.22', '>=' ) ) {
			$html .= "<script>mw.hook( 'ww-qpf' ).add( function () { qpf('#$dpfid') } )</script>";
		}
		return $html;
	}

	# To display a project file (including source-file elements):
	#	figure out what to display
	#	whatever file is to be displayed, update and retrieve it
	public function display_project_file(&$project,$text,$source,$args,&$parser) {
		wwProfileIn( __METHOD__ );
		global $wwContext;
		$target = $this->file_to_display($project,$source,$args);
		#wwLog("display_project_file {$args['filename']} . "
		#  . ($text === null?'(no text)':'(text given)')
		#  . " . project={$project->project_name()} . source=$source"
		#  . (isset($args['is_definition'])?" . is_definition={$args['is_definition']}":'')
		#  . (isset($args['make'])?" make={$args['make']}":'')
		#  . " . display={$args['display']}"
		#  . " : target=$target\n");
		$display_mode = false;
		if ( $target == 'source' or $target == 'link' or
		     $target == 'download' or $target == 'none' ) {
			$display_mode = $target;
			$target = $args['filename'];
		}
		if ( ! isset( $args['make'] ) ) {
			if ($source) {
				if ($target !== $args['filename']) {
					$args['make'] = true;
					$source = false;
				} else {
					$args['make'] = false;
				}
			# don't do this - better to be consistent regardless of display mode
			#} else if ( $display_mode == 'link' or $display_mode == 'none') {
			#	$args['make'] = false;
			} else {
				$args['make'] = true;
			}
		}
		// viewing old revision of archived project file: 
		// don't make stuff, it just causes confusion.
		if ( ( !$text or !$source or $args['make'] )
		     and $this->page_is_history() 
		     and (!$project or !$project->is_standalone()) ) {
			return '<fieldset class="ww-project-file-source">'
				. '<legend>'
				. htmlspecialchars($target)
				. '</legend>'
				. $wwContext->wwInterface->message( 'ww-no-project-files-during-history',
					htmlspecialchars($target)
				)
				. '</fieldset>';
		}

		// archiving_in_progress, similar idea but a long story.
		// see sequester_archived_project_files().
		if ( $text !== null and !$source and
		     $wwContext->wwStorage->archiving_in_progress ) {
			#wwLog('display_project_file skipping make in midst of '
			#	.($wwContext->wwStorage->archiving_in_progress ? 'archiving':'history') . "\n");
			$args['make'] = false;
		}
		#$loglink = false;
		$alts = array();
		if ( $args['make'] ) {
			$alts['log'] = '';
		}
		$alts = $this->alternatives_for_file( $project, $target, $args, $alts );

		global $wwMakeTemporarilyDisabled;
		$make = $wwMakeTemporarilyDisabled ? false : $args['make'];
		if ( wwfDynamicDisplayInEffect()
		     //and ! defined( 'MW_API' ) // TODO: render the file requested, but allow dynamic project files within, for example if it's a .wikitext
		     and ! isset( $args['standalone'] )
		     and ! ($display_mode === 'link' and ! $make)
	       	) {
			$slcp = wwfStaticLinkToCurrentPage( $parser );
			$placeholder = $wwContext->wwInterface->message(
				'ww-dynamic-project-file-placeholder',
				$target,
				$project->project_name(),
				$slcp
			);
			wwRunHooks( 
				'WW-DynamicProjectFilesPlaceholderMessage',
				array( &$placeholder, $target, $project->project_name(), $slcp )
			);
			return $this->make_dynamic_placeholder( $project, $target, $display_mode, $source, $make, $alts, $args, $placeholder, $parser );
		}

		$okay_to_display = true;
		if ($args['make']) {
			$make_succeeded = ProjectEngineConnection::make_target( $project, $target );
			if ( ! $make_succeeded ) {
				$logfilename = $target.'.make.log';
				$this->record_error( 
					$this->altlinks_text( $project, $target, $args, true )
					.  $wwContext->wwInterface->message(
						'ww-make-failed',
						htmlspecialchars($target),
						$this->make_get_project_file_url( $project, $logfilename, false )
					)
				);
				$okay_to_display = false;
			}
			$text = null;
		}
		wwProfileOut( __METHOD__ );
		if ($display_mode == 'none' or !$okay_to_display) {
			return '';
		} else {
			#wwLog( 'display_project_file calls display_file_contents for ' . $target );
			return $this->display_file_contents(
				$project,
				$target,
				$text,
				$display_mode,
				$alts,
				/*line*/false,
				$args,
				$parser,
				false
			);
		}
	}

	# ===== private functions =====

	# no need for these to catch WWExceptions, let the caller
	# do it, unless there's a reason to.

	# To figure out what to display in place of a given file:
	#  check source-file element for display attribute
	#  check project description for explicit display attribute
	#  check for default display rule
	#  else just display the file itself
	#
	#  $args is the xml attributes attached to the <source-file>
	#    or <project-file/> in the source page (not in the project
	#    description), with some possible additions.
	#  $args['filename'] is the source-file name or project-file name
	public function file_to_display(&$project,$source,$args) {
		$answer = null;
		# first: if there's an explicit "display=" attached to the 
		# source-file tag
		if (isset($args['display'])) {
			$answer = $args['display'];
		# if linktext is given and not display, display='link' is implicit
		} else if (isset($args['linktext'])) {
			$answer = 'link';
		}
		# second: if there's an explicit "display=" for it in the 
		# project description
		if ( is_null($answer) and
		     isset($project->project_files[$args['filename']]) and
		     isset($project->project_files[$args['filename']]['display']) ) {
			$answer = $project->project_files[$args['filename']]['display'];
			if ($answer === '') {
				$answer = null;
			}
		}
		# still nothing? try default rules
		global $wwImageExtensions, $wwLinkExtensions, $wwInlineImageExtensions;
		global $wwDisplayTransformations;
		if (array_key_exists('non_inline_images',$wwDisplayTransformations)) {
			$non_inline = array_diff($wwImageExtensions, $wwInlineImageExtensions);
			$non_inline_image_pattern = '/\.('.implode('|',$non_inline).')$/i';
			$wwDisplayTransformations[$non_inline_image_pattern]
				= $wwDisplayTransformations['non_inline_images'];
			unset( $wwDisplayTransformations['non_inline_images'] );
		}
		if (is_null($answer)) {
			$img_ext_pattern
				= '/\.('.implode('|',$wwImageExtensions).')$/i';
			$link_ext_pattern
				= '/\.('.implode('|',$wwLinkExtensions).')$/i';
			if (preg_match($link_ext_pattern,$args['filename'])) {
				return 'link';
			}
			if ( ( $repl = preg_replace(
				array_keys($wwDisplayTransformations),
				array_values($wwDisplayTransformations),
				$args['filename'],
				1
			) ) !== $args['filename'] ) {
				#wwLog("\$wwDisplayTransformations is "
				#	. serialize($wwDisplayTransformations) . "\n");
				#wwLog("\$wwDisplayTransformations says display {$args['filename']}"
				#	. " as $repl\n");
				return $repl;
			}
		}
		# the above might give us the keywords "none", "source", or "link"
		# we might replace those with something, but currently we don't
		if ($answer == 'source') {
			return 'source';
		}
		if ($answer == 'none') {
			return 'none';
		}
		if ($answer == 'download') {
			return 'download';
		}
		if ($answer == 'link') {
			return 'link';
		}
		# final option: leave the file as is
		if (is_null($answer)) {
			$answer = $args['filename'];
		}
		return $answer;
	}

	# alternative things to view when a file is displayed.
	# these will be placed in brackets on the right side of the page,
	# similar to the [edit] link on a wiki section heading.
	public function alternatives_for_file( &$project,$display_filename,$args,$alts) {
		global $wwAltlinksRules;
		$apply_rules = '';
		foreach ($wwAltlinksRules as $re => $addon) {
			$result = preg_replace($re,$addon,$display_filename,-1,$count);
			if ($count) {
				$apply_rules .= ' '.$result;
			}
		}
		if ($apply_rules !== '') {
			$rules_results = array();
			foreach (explode(' ',substr($apply_rules,1)) as $entry) {
				if (strpos($entry, '=') !== false) {
					list($k,$v) = explode('=',$entry,2);
					$rules_results[$k] = $v;
				} else {
					#wwLog("confusing apply_rules entry without equal sign: $entry\n");
				}
			}
			$args = array_merge($rules_results,$args);
		}
		if (isset($args['altlinks'])) {
			$keys = explode(',',$args['altlinks']);
		} else {
			$keys = array();
			if ( isset( $alts['log'] ) ) {
				$keys[] = 'log';
			}
			foreach ($args as $k=>$v) {
				if (preg_match('/^(.+)link$/',$k,$m)) {
					$keys[] = $m[1];
				}
			}
		}
		if ( ! is_array( $alts ) ) {
			$alts = array();
		}
		foreach($keys as $key) {
			$alt = (isset($args[$key.'link']) ? $args[$key.'link'] : '');
			if ($alt != '') {
				if (strstr($alt, '://')) {
					#$alt = '<a href="'.urlencode($alts[$key]).'">'.htmlspecialchars($alts[$key]).'</a>';
				} else {
					$disp = $this->file_to_display(
						$project, 
						false,
						array('filename'=>$alt)
				       	);
					$disp = ($disp == 'link' ? 'download' : null);
					$alt = $this->make_get_project_file_url($project,$alt,true,$disp);
				}
			} else {
				if ($key == 'log') {
					$logfilename = "$display_filename.make.log";
					$alt = $this->make_get_project_file_url($project,$logfilename,false);
				} else {
					$base = preg_replace('/\.[^\.]+$/','',$display_filename);
					$alt = $this->make_get_project_file_url($project,"$base.$key");
				}
			}
			$alts[$key] = array( 'url' => $alt );
			if ( strstr($alt, 'ww-action=') ) {
				$alts[$key]['write'] = true;
			}
		}
		return $alts;
	}

	# Produce the html for the [pdf,log] or whatever, for placing
	# alongside product file on the right margin.
	public function altlinks_text(&$project, $display_filename, $args, $alts) {
		if (is_string($alts)) {
			return $alts;
		}
		# alternatives_for_file returns a dictionary of link names and targets.
		return $this->make_altlinks(
			$this->alternatives_for_file($project,$display_filename,$args,$alts)
		);
	}

	# general: make an html string like [log] from a list of key/target pairs.
	public function make_altlinks( $alts ) {
		#if (count($alts) <= 0) {
		#	return '';
		#}
		$outerclass = 'ww-altlinks noprint';
		$writeonly = true;
		$first = $firstread = true;
		$html = '';
		foreach($alts as $key => $vals) {
			$link = '';
			if (!$first) {
				$link .= '<span class="ww-altlinks-comma">, </span>';
			}
			if ( ! isset( $vals['write'] ) ) {
				$vals['write'] = false;
			}
			if ( isset( $vals['url'] ) ) {
				$link .=
					'<a href="'
					. $vals['url']
					. '">'
					. htmlspecialchars($key)
					. '</a>';
			} else if ( isset( $vals['html'] ) ) {
				$link .= $vals['html'];
			} else { # sanity check
				$link .= $key;
			}
			$first = false;
			$class = 'ww-altlink';
			if ( isset( $vals['write'] ) ) {
				$class .= ' ww-write-action';
			} else {
				$firstread = false;
				$writeonly = false;
			}
			if ( isset( $vals['class'] ) ) {
				if ( ! is_array( $vals['class'] ) ) {
					$vals['class'] = array( $vals['class'] );
				}
				foreach ($vals['class'] as $c) {
					$class .= ' ' . $c;
				}
			}
			$html .= '<span class="' . $class . '">'
				. $link
				. '</span>';
		}
		if ( $writeonly ) {
			$outerclass .= " ww-write-only";
		}
		return '<span class="' . $outerclass . '">'
			//. '<span class="ww-altlinks-open-bracket">[</span>'
			. '<span class="ww-altlinks-pulldown-arrow"></span>'
			. '<span class="ww-altlinks-inner">'
			. $html
			. '</span>'
			//. '<span class="ww-altlinks-close-bracket">]</span>'
			. '</span>';
	}

	# armor html in base64 comment
	# this should be no longer in use
	protected function arm_html($text) {
		# the disarm will choke if it's too long so break it into
		# chunks as needed
		$big_enough = 50000;
		$chunks = array();
		while (strlen($text) > $big_enough) {
			$chunks[] = substr($text,0,$big_enough);
			$text = substr($text,$big_enough);
		}
		$chunks[] = $text;
		$text = '';
		foreach ($chunks as $chunk) {
			$text .= "<!-- WORKINGWIKI_EXTENSION BASE64_ENCODED "
				.base64_encode($chunk)." END_ENCODED -->";
		}
		return $text;
	}

	public function use_syntax_highlighting() {
		return $this->hasSyntaxHighlighter();
	}

	# record a message to be reported to the user.
	# three ways to call this function:
	#   $message is a string, $status is WW_SUCCESS by default, meaning
	#     report an informational message that isn't a problem
	#   $message is a string, $status is WW_ERROR or something like it
	#   $message is an array with 'status' and 'message' values, $status 
	#     is ignored
	# Note: don't use any HTML entities (starting with '&') in the
	# message, because it will appear wrong in Special:GetProjectFile.
	public function record_message($message, $status=WW_SUCCESS) {
		if (!is_array($message)) {
			$message = array('message'=>$message, 'status'=>$status);
		}
		$prefix = '';
		global $wwOutputDebugMessages;
		if ($message['status'] != WW_DEBUG or $wwOutputDebugMessages) {
			if ($message['status'] == WW_ERROR) {
				$prefix = 'Error: ';
			} else if ($message['status'] == WW_WARNING) {
				$prefix = 'Warning: ';
			} else if ($message['status'] == WW_DEBUG) {
				$prefix = '(debug message) ';
			}
			if ($prefix != '') {
				$message['message'] = $prefix.$message['message'];
			}
			$this->error_queue[] = $message;
		}
		#wwLog($message['message']."\n");
		#$e = new Exception();
		#wwLog($e->getTraceAsString());
	}

	# Record an error message.
	# If your function has trouble, tell it to this function.
	# All errors will be reported when the parsing finishes.
	public function record_error($error_text) {
		$this->record_message($error_text,WW_ERROR);
	}

	# record a warning
	public function record_warning($message) {
		$this->record_message($message,WW_WARNING);
	}

	# record a debug message
	public function debug_message($message) {
		$this->record_message($message,WW_DEBUG);
	}
	
	# record an error and throw a WWException.
	public function throw_error($error_text, $status=WW_ERROR) {
		$this->record_message($error_text,$status);
		throw new WWException();
	}

	# when it's time, report the errors and clear the queue.
	# $project may be a project object or its name
	public function 
		report_errors($insert_before='', $insert_after='') {
		global $wwContext;
		if ( count($this->error_queue) == 0 ) {
			return '';
		}
		wwProfileIn( __METHOD__ );
		$messages = '';
		static $classes = array(
			WW_SUCCESS => 'message',
			WW_ERROR => 'error',
			WW_WARNING => 'warning',
			WW_DEBUG => 'debug'
		);
		$dbgonly = true;
		foreach ( $this->error_queue as &$entry ) {
			if ($entry['status'] != WW_NOACTION) {
				$messages .=
					"<p class='{$classes[$entry['status']]}'>"
					#. str_replace('&amp;amp;','&amp;',str_replace('&','&amp;',$entry['message']))
					. wwfSanitizeForSpecialPage($entry['message'])
					. "</p>\n";
				if ($entry['status'] != WW_DEBUG) {
					$dbgonly = false;
				}
			}
		}
		$text =
			'<fieldset class="ww-messages'
			. ($dbgonly ? ' debug-only':'')
			. '"><legend>'
			. $wwContext->wwInterface->message('ww-messages-legend')
			. '</legend>';
		if (!$dbgonly) {
			$messages = $insert_before.$messages.$insert_after;
		}
		$text .= $messages."</fieldset>". ($dbgonly ? '':"\n");
		$this->error_queue = array();
		//throw new MWException('Who called me?');
		wwProfileOut( __METHOD__ );
		return $text;
	}

	# as above, but stick the errors in front of the text that's given.
	# for convenience.  makes sure everything is evaluated before we
	# read the error queue.
	public function prepend_errors($text) {
		$errs = $this->report_errors();
		return $errs.$text; 
	}

	public function report_errors_as_text($what, $filename='') {
		if (!count($this->error_queue)) {
			return '';
		} // else
		if ($filename != "") {
			$what .= ' \''.$filename.'\'';
		}
		if ( $what != '' ) {
			$what = " processing $what";
		}
		$text = "WorkingWiki encountered errors$what:\n";
		foreach($this->error_queue as &$entry) {
			if ($entry['status'] != WW_NOACTION) {
				$text .= $entry['message']."\n";
			}
		}
		$this->error_queue = array();
		return $text;
	}

	# completely unrelated to the above: create an internationalized message
	# using MW's Message class if available.
	public function message( $key /*...*/ ) {
		$params = func_get_args();
		array_shift( $params );
		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}
		if ( class_exists( 'Message' ) ) {
			$m = new Message( $key, $params );
			return $m->text();
		} else {
			return "&lt;$key: " . implode( ', ', $params ) . '&gt;';
		}
	}

	# work out best we can what language name to put in the 'source' tag
	# to get the right syntax highlighting for a given filename.
	public function get_language_name_from_extension( $extension, $lookup = array() ) {
		if ($this->use_syntax_highlighting()) {
			# this currently works to make it load the GeSHi class's code 
			# but it would be nice to be able to do it in a supported way
			# https://bugzilla.wikimedia.org/show_bug.cgi?id=20657
			if ( is_callable('SyntaxHighlight_GeSHi::hSpecialVersion_GeSHi') ) {
				$t = null;
				SyntaxHighlight_GeSHi::hSpecialVersion_GeSHi($t,$t);
			} else if ( is_callable('SyntaxHighlight_GeSHi::parserHook') ) {
				global $wgParser;
				SyntaxHighlight_GeSHi::parserHook( '', array(), $wgParser );
			} else {
				#wwLog("Where's GeSHi?\n");
			}
			$geshi = new GeSHI('','');
			$answer = $geshi->get_language_name_from_extension($extension, $lookup);
			# some types we are using that it doesn't know last I checked
			if ($answer == '') {
				$answer = $geshi->get_language_name_from_extension(
					$extension,
					array(
						'rsplus' => array('r','R'), #,'rout'
						'make' => array('makefile','Makefile','mk','make','d'),
						'python' => array('sage'),
						'latex' => array('tex','tex-math','tex-inline'),
						'xml' => array('xml', 'svg', 'xhtml', 'html5'),
						'gnuplot' => array('gp', 'gnuplot')
					)
				);
			}
			#wwLog("get_language_name_from_extension $extension => $answer\n");
			return $answer;
		}
		return '';
	}
}

?>
