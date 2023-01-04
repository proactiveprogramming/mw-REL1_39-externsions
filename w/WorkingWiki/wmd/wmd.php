<?php

$WW_DIR = realpath( __DIR__ . '/..' );

$ww_sources = array(
	'WorkingWiki.defs.php',
	'misc.php',
	'WWInterface.php',
	'WWStorage.php',
	'ProjectDescription.php',
	'WorkingWikiProjectDescription.php',
	'ProjectEngineConnection.php',
	'ProjectEngine/ProjectEngine.php',
	// TODO fill out this list thoughtfully
);

foreach ( $ww_sources as $php ) {
	require_once "$WW_DIR/$php";
}

$wwClickToAdd = false;
$wwLogFunction = function ( $string ) { 
	file_put_contents( 'php://stderr', "$string\n" );
};
$wwUseHTTPForPE = false;
$wwPECanReadFilesFromWiki = $wwPEFilesAreAccessible = true;

$peAllowProcessingInPlace = true;
$peTimeLimitForMake = 0;

class WMDInterface extends WWInterface {
	public $head_insertions = '';

	public function reconcile_token_with_project( $token, $token_start, $token_length, $pagename, $parser ) {
		global $wwContext;
		#wwLog( 'token: ' . json_encode( $this->uniq_tokens[$token] ) );
		$project = $wwContext->wwStorage->find_project_by_name(
			$this->uniq_tokens[$token]['args']['project']
		);
		#wwLog( "token in project: " . $this->uniq_tokens[$token]['args']['project'] );
		$this->uniq_tokens[$token]['project'] = $project;
		#wwLog( 'for project: ' . $this->uniq_tokens[$token]['project']->project_name() );
		$this->uniq_tokens[$token]['file_content'] = null;
		$args = $this->uniq_tokens[$token]['args'];
		$source = ( $this->uniq_tokens[$token]['tag'] == 'source-file' );
		if ( ! isset( $args['filename'] ) or $args['filename'] == '' ) {
			$this->uniq_tokens[$token]['failed'] = true;
			$sstr = ( $source ? 'source' : 'project' );
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
		#if ( $source and ! isset( $project->project_files[ $args['filename'] ] ) ) {
		#	$project->add_source_file( array(
		#		'filename' => $args['filename'],
		#		'page' => $pagename,
		#	) );
		#}
		#wwLog( "added source file {$args['filename']} to project {$project->project_name()}.\nProject: " . serialize($project) . "\nproject_cache: " . serialize(ProjectDescription::$project_cache) );

		# do sync before anything gets retrieved, rather than before
		# something gets made.
		if ( ! $project->synced ) {
			$success = ProjectEngineConnection::call_project_engine(
				'sync',$project,null,null,true);
			if ($success) {
				$project->synced = true;
			}
		}
	}

	public function page_is_history() {
		return false;
	}

	public function sse_key_if_any() {
		return null;
	}

	public function amend_PE_request( &$request ) {
		parent::amend_PE_request( $request );
		$projs = array_keys( $request['projects'] );
		if ( count($projs) >= 1 and !preg_match( '/standalone/', $projs[0] ) ) {
			$request['summary-to-stderr'] = true;
			#$request['log-to-stderr'] = true;
		}
	}

	/* TODO: syntax highlighting */
	public function hasSyntaxHighlighter() {
		return false;
	}

	public function file_to_display( &$project, $source, $args ) {
		global $wwOutputFormat;
		#wwLog( "How to display ${args['filename']} in $wwOutputFormat" );
		if ( isset($args["{$wwOutputFormat}display"]) ) {
			#wwLog( "using {$wwOutputFormat}display = " . $args["{$wwOutputFormat}display"] );
			$args['display'] = $args["{$wwOutputFormat}display"];
		}
		$answer = parent::file_to_display( $project, $source, $args );
		#wwLog( 'parent says ' . $answer );
		if ( $wwOutputFormat == 'html' ) {
			return $answer;
		} else if ( $wwOutputFormat == 'tex' ) {
			global $wwDisplayTransformations;
			$answer = preg_replace( 
				array_keys( $wwDisplayTransformations ),
				array_values( $wwDisplayTransformations ),
				$answer,
				1
			);
			#$mode = $this->default_display_mode( $extension );
			#if ( $mode == 'source' ) {
				# TODO: seriously, though
			#	$answer = 'source';
			#}
			if ( preg_match( "/\.(tex|tex-inline)\$/i", $answer ) 
			    or $answer == 'source' or $answer == 'link'
			    or $answer == 'download' or $answer == 'none' ) {
				#wwLog( $answer );
				return $answer;
			}
			$extension = ProjectDescription::type_of_file($answer);
			global $wwImageExtensions;
			if ( in_array($extension, $wwImageExtensions) ) {
				#wwLog( $answer );
				return $answer;
			}
			#wwLog( $answer.".tex" );
			return "$answer.tex";
		} else {
			if ( preg_match( "/\.$wwOutputFormat\$/i", $answer ) ) {
				return $answer;
			} else {
				return "$answer.$wwOutputFormat";
			}
		}
	}

        public function display_project_file(&$project,$text,$source,$args,&$parser) {
                $target = $this->file_to_display($project,$source,$args);
		global $options;
		if (isset($options['make-single-file']) and
			$options['make-single-file'] != $target and
			$options['make-single-file'] != $args['filename']) {
			#wwLog( "make-single-file: ".$args['filename']." / $target vs ".$options['make-single-file']." - setting make=false" );
			#wwLog( "no ".$args['filename']." / $target" );
			$args['make'] = false;
		}
		return parent::display_project_file($project,$text,$source,$args,$parser);
	}

	public function display_file_contents( &$project, $filename, $text,
			$display_mode=false, $alts=null, $line=false, $args=array(),
			$parser=null, $getprojfile=false ) {
		global $wwOutputFormat;
		if ( $wwOutputFormat == 'html' ) {
			$text = parent::display_file_contents(
				$project,
				$filename,
				$text,
				$display_mode,
				$alts,
				$line,
				$args,
				$parser,
				$getprojfile
			);
			if ( ! $display_mode ) {
				$extension = ProjectDescription::type_of_file($filename);
				$display_mode = $this->default_display_mode($extension);
			}
			if ( $display_mode == 'html' or $display_mode == 'source' ) {
				// protect the HTML from erroneous processing by Jekyll's Liquid markup thing
				//$text = "{% raw %}\n<span>\n$text\n</span>\n{% endraw %}";
			}
			return "<!-- $filename -->" . $text;
		} else if ( $wwOutputFormat == 'tex' ) {
			#wwLog( "displaying $filename with display_mode of $display_mode" );
			if ( ! $display_mode ) {
				$extension = ProjectDescription::type_of_file($filename);
				$display_mode = $this->default_display_mode(
					$extension
				);
			}
			if ( $display_mode == 'source' ) {
				#wwLog( "display $filename as source" );
				global $peCacheDirectory;
				$filepath = "$peCacheDirectory/{$project->project_name()}/$filename";
				#$filepath = preg_replace( '/([_%\$])/', '\\\\$1', $filepath );
				# TODO: does this barf if make=no and file isn't there?
				return "% WMD file $filename\n" .
					'\lstinputlisting{' .
					$filepath .
					'}' . "\n";
			} else if ( $display_mode == 'link' or $display_mode == 'download' ) {
				#wwLog( "display $filename as link" );
				$url = $this->make_get_project_file_url(
					$project,
					$filename,
					/*make*/(!isset($args['make']) or !$args['make']) and !$getprojfile,
					/*disp_mode*/$display_mode
				);
				$linktext = (isset($args['linktext']) ?
					$args['linktext'] :
					$filename);
				return "% WMD file $filename\n" .
					'\href{' . $url . '}{' .
					$linktext . '}' . "\n";
			} else if ( $display_mode == 'none' ) {
				#wwLog( "display $filename as none" );
				return "% WMD file $filename\n";
			} else if ( $display_mode == 'image' ) {
				#wwLog( "display $filename as image" );
				if ($text === null) {
					$text = $this->retrieve_file_contents(
						$project, $filename
					);
				}
				if ($text === null) {
					$this->throw_error(
						"File ‘" . $filename
                                                . "’ not found in working directory."
                                        );
				}
				global $peCacheDirectory;
				return "% WMD file $filename\n" .
					'\includegraphics{' .
					$peCacheDirectory . '/' . 
					$project->project_name() . '/' .
					$filename . '}' . "\n";
			}
			#wwLog( "display $filename as tex" );
			if ( $text === null ) {
				$text = $this->retrieve_file_contents( $project, $filename );
				if ( $text === null ) {
					$this->throw_error(
						'File ‘' .
						$filename .
						'’ not found in working directory.'
					);
				}
			}
			return "% WMD file $filename\n$text";
		} else {
			wwRecordError( "Unknown value of \$wwOutputFormat, `$wwOutputFormat'" );
			return "???.$wwOutputFormat";
		}
	}

	public function default_display_mode( $extension ) {
		global $wwOutputFormat;
		if ( $wwOutputFormat == 'tex' and
			($extension == 'tex' or $extension == 'tex-math'
			or $extension == 'tex-inline') ) {
			# TODO: use 'raw' instead of 'html' since that's what it means now
			return 'html';
		}
		return parent::default_display_mode( $extension );
	}

	public function latex_escape( $text ) {
		# http://stackoverflow.com/questions/2541616/how-to-escape-strip-special-characters-in-the-latex-document
		$map = array( 
		    "#"=>"\\#",
		    "$"=>"\\$",
		    "%"=>"\\%",
		    "&"=>"\\&",
		    "~"=>"\\~{}",
		    "_"=>"\\_",
		    "^"=>"\\^{}",
		    "\\"=>"\\textbackslash{}",
		    "{"=>"\\{",
		    "}"=>"\\}",
		);
		return preg_replace(
			"/([\^\%~\\\\#\$%&_\{\}])/e",
			"\$map['$1']",
			$text
		);
	}

	public function report_errors($insert_before='', $insert_after='') {
		global $wwOutputFormat;
		if ( $wwOutputFormat == 'html' ) {
			return parent::report_errors($insert_before, $insert_after);
		} else if ( $wwOutputFormat == 'tex' ) {
			$err_text = self::report_errors_as_text( '', '' );
			if ( $err_text == '' ) {
				return $err_text;
			}
			$err_text = self::latex_escape( htmlspecialchars_decode( strip_tags( $err_text ) ) );
			return "\\texttt{[$err_text]}";
			return '\fbox{\parbox{\textwidth}{\raggedright ' .
				$err_text .
				"}}\n";
		} else {
			return self::report_errors_as_text( '', '' );
		}
	}

	/* TODO: pulldown links or something? */
	public function add_altlinks_to_project_file_html( $html, $project, $filename, $display_mode, $args, $alts ) {
		return $html;
	}

	/* TODO: conditional mathjax */

	public function get_project_file_base_url( $project, $filename, $make=true, $display=null ) {
		if ( is_object($project) ) {
			$project = $project->project_name();
		}
		global $project_file_base_url;
		return "$project_file_base_url/$project/$filename";
	}
};

class WMDStorage extends WWStorage {
	public function ok_to_archive_files( $request ) {
		return false;
	}

	public function find_project_by_name( $name, $create=true, $as_of=null ) {
		$name = ProjectDescription::normalized_project_name($name);
		$is_external = $this->is_project_uri($name);
		if ( ! $is_external and
			isset( ProjectDescription::$project_cache ) and
			isset( ProjectDescription::$project_cache[$name] ) ) {
			return ProjectDescription::$project_cache[$name];
		}
		if ( ! $create ) {
			return null;
		}
		if ( $this->is_standalone_name( $name ) ) {
			return new WMDStandaloneProjectDescription( $name );
			$parts = explode( '?', $name );
			return new WMDStandaloneProjectDescription( $parts[1] );
		}
		return new WMDProjectDescription( $name );
	}

	public function find_file_content( $filename, &$project, $pagename, $src, $as_of=null ) {
		global $wmd_data;
		if ( ! isset($wmd_data['pagetext_cache'][$pagename]) ) {
			wwLog( "WMD find_file_content: no $pagename in pagetext_cache" );
			return array( 'type' => 'not found' );
		}
		$sftt = $this->find_file_content_on_page( $project, $filename, $pagename, $src, $as_of );
		if ( ! isset( $sftt['text'] ) ) {
			return array( 'type' => 'not found' );
		} else {
			return array(
				'type' => 'tag',
				'page' => $pagename,
				'text' => $sftt['text'],
				'touched' => $sftt['touched'],
			);
		}
	}

	public function cache_page_from_db($pagename) {
		global $wwContext;
		$wwContext->wwInterface->throw_error( "Page not found: $pagename" );
	}
};

class WMDProjectDescription extends WorkingWikiProjectDescription {

	public function __construct( $projectname ) {
		#wwLog( "Construct project: $projectname" );
		$this->project_description_page = null;
		$this->project_files = array();
		$this->projectname = $projectname;
		$this->uri = 'pe-project:' . $projectname;
		$this->options['use-default-makefiles'] = true;
		$this->as_of_revision = null;
		$this->add_GNUmakefile();
		if ( ! is_array( ProjectDescription::$project_cache ) ) {
			ProjectDescription::$project_cache = array();
		}
		ProjectDescription::$project_cache[ $projectname ] = $this;
	}

	public function all_source_file_contents() {
		$asfc = parent::all_source_file_contents();
		#wwLog( "ASFC: " . json_encode( $asfc ) );
		#wwLog( 'project_files is: ' . json_encode( $this->project_files ) );
		return $asfc;
	}

	public function project_page() {
		global $wwContext;
		return $wwContext->wwInterface->currently_parsing_key;
	}

	/*
	public function default_locations_for_file( $filename ) {
		global $wmd_data;
		return $wmd_data['project_pages'][$this->projectname];
	}
	 */

	public function fill_pe_request( &$request, $focal, $sync_sf ) {
		parent::fill_pe_request( $request, $focal, $sync_sf );
		#$request['projects'][ $this->uri ]['process-in-place'] = true;
	#wwLog( "Project {$this->project_name()} fill_pe_request - prereqs are " . json_encode( $this->depends_on ) );
	}

	public function short_dir_for_uri( $uri, $varname ) {
		return preg_replace( '/pe-project:/', '', $uri );
	}

	public function env_for_make_jobs() {
		global $wwOutputFormat;
		return array(
			'WW_OUTPUTFORMAT' => $wwOutputFormat,
		);
	}

	public function offline_resources_path() {
		global $wwUseHTTPForPE;
		if (!$wwUseHTTPForPE) {
			global $peResourcesDirectory;
			return realpath( $peResourcesDirectory );
		}
		global $peCacheDirectory;
		return "$peCacheDirectory/.workingwiki/resources";
	}
};

class WMDStandaloneProjectDescription extends WMDProjectDescription {
	public function __construct( $name ) {
		parent::__construct( $name );
		$parts = explode( '?', $name );
		$this->uri = "pe-project:.workingwiki/standalone/{$parts[1]}";
		#wwLog( "create_standalone_project $name: {$this->project_name()}" );
	}

	public function is_standalone() {
		return true;
	}

	public function fill_pe_request( &$request, $focal, $sync_sf ) {
		parent::fill_pe_request( $request, $focal, $sync_sf );
	}

	public function default_locations_for_file( $filename ) {
		global $title;
		return $title;
	}

	public function short_dir_for_uri( $uri, $varname ) {
		return preg_replace( '{^.*standalone/}', '', $uri );
	}
};

if ( php_sapi_name() !== 'cli' ) {
	error_log( "wmd.php called from web server" );
	header( "HTTP/1.0 500 Script execution error" );
?><html><head><title>Working Markdown Execution Error</title></head>
<body><h1>Error: Command-line script called from web server</h1>
<p>The wmd.php script can only be called as a command-line utility.</p>
<p>Usage: <tt>wmd.php &lt;input filename&gt; &lt;output filename&gt;</tt></p>
</body></html>
<?php
	exit -1;
}

# process command line options.

# the options have to come before the input and output filenames
$choice_option_names = array( 'pre', 'post' );
$optional_option_names = array( 'title:', 'modification-time:', 'process-inline-math', 'default-project-name:', 'prerequisite-projects:', 'data-store:', 'project-file-base-url:', 'persistent-data-store', 'enable-make:', 'output-format:', 'make-single-file:' );
$mandatory_option_names = array( 'cache-dir:' );
$options = getopt( '', array_merge( $choice_option_names, $optional_option_names, $mandatory_option_names ) );

$script = $argv[0];

# default values

if ( isset( $options['output-format'] ) ) {
	global $wwOutputFormat;
	$wwOutputFormat = $options['output-format'];
} # else default is 'html

if ( $wwOutputFormat == 'tex' ) {
	global $wwDisplayTransformations;
	# don't do the default conversion of tex => html, just inline it
	$wwDisplayTransformations = array(
		'/\.tex$/i' => '.tex-inline',
		'/\.tex-math$/i' => '.tex-inline',
		'/\.eps$/i' => '.pdf',
		#'/\.crop\.svg$/i' => '.tex-inline',
		'/\.svg$/i' => '.pdf',
	);
	# .pdf is now an inline-able image, not a link
	global $wwInlineImageExtensions, $wwLinkExtensions;
	$wwLinkExtensions = array_diff( $wwLinkExtensions, array( 'pdf' ) );
	$wwInlineImageExtensions[] = 'pdf';
}
$math_repl = "'$1<source-file filename=\"'.md5('$2').'.tex-math\" standalone=\"yes\">$2</source-file>'";
function dmath($math) {
	return "\\iflatexml\n\\begin{align*}\n$math\\end{align*}\n\\else\n" .
		"\\begin{dgroup*}\\begin{dmath*}\n" .
		preg_replace( '/\\\\\\\\/', "\n\\end{dmath*}\\begin{dmath*}",
		    preg_replace( '/\&/', '', $math ) ) .
		"\\end{dmath*}\\end{dgroup*}\n\\fi\n";
}
if ( $wwOutputFormat == 'tex' ) {
  $inline_repl = "'$1<source-file filename=\"'.md5('$2')"
                . ".'.tex-inline\" standalone=\"yes\">"
		. "\n$2\n</source-file>'";
                #. "\\documentclass{article}\n"
                #. "\\begin{document}\n"
                #. "$2\n\\end{document}\n</source-file>'";
} else {
  $inline_repl = "'$1<source-file filename=\"'.md5('$2')"
                . ".'.tex-inline\" standalone=\"yes\">"
                . "\\documentclass{article}\n"
                . "\\begin{document}\n"
                . "$2\n\\end{document}\n</source-file>'";
}
$wwWikitextReplacements = array(
	'/([^\\\\]|^)\{\$(.*?[^\\\\]|)\$\}/es' => $math_repl, # {$math$}
        '/([^\\\\]|^)\$\$(.*?[^\\\\]|)\$\$/e' => $math_repl,  # $$math$$
  # extra inline constructs for tex authoring
  # note this $...$ one especially has to go before the <latex> one (?)
	#'/()\$(.*?[^\\\\]|)\$/e' = $math_repl; # $math$
	#'/\\\\\[(.*?[^\\\\]|)\\\\\]/es' =>     # \[ math \]
	#  "'<source-file filename=\"'.md5('$1').'.tex-inline\" standalone=\"yes\">\n\\begin{align}\n$1\n\\end{align}</source-file>'",
	'/\\\\\[\*[\S$]*(.*?[^\\\\]|)\\\\\]/es' =>    # \[* math \]
	  #"'<source-file filename=\"'.md5('$1').'.tex-inline\" standalone=\"yes\">\n\\begin{dgroup*}\\begin{dmath*}\n$1\\end{dmath*}\n\\end{dgroup*}</source-file>'",
	  "'<source-file filename=\"'.md5('$1').'.tex-inline\" standalone=\"yes\">\n'.dmath('$1').'</source-file>'",
	  # <latex> latex </latex>
        '/([^\\\\]|^)<latex[^>]*>(.*?[^\\\\]|)<\/latex>/esi' => $inline_repl,
        '/__DISABLE_MAKE__/' => '<toggle-make-enabled enabled=0/>',
        '/__ENABLE_MAKE__/' => '<toggle-make-enabled enabled=1/>',
);

if ( isset( $options['default-project-name'] ) ) {
	# realpath doesn't work on directories that haven't been created yet
	$default_project_name = $options['default-project-name'];
} else {
	$default_project_name = "default";
}

$title = (isset( $options['title'] ) ? $options['title'] : $default_project_name);

if ( ! isset( $options['modification-time'] ) ) {
	$now = new DateTime( 'now' );
	$options['modification-time'] = $now->format( 'YmdHis' );
}

if ( isset( $options['cache-dir'] ) ) {
	global $peCacheDirectory;
	$peCacheDirectory = $options['cache-dir'];
	if ( $peCacheDirectory[0] != '/' ) {
		$peCacheDirectory = realpath( '.' ) . "/$peCacheDirectory";
	}
} else {
	global $peCacheDirectory;
	$peCacheDirectory = null;
}

if ( ! isset( $options['data-store'] ) ) {
	$options['data-store'] = '.wmd.data';
}

if ( isset( $options['project-file-base-url'] ) ) {
	global $project_file_base_url;
	$project_file_base_url = $options['project-file-base-url'];
}

if ( ! $peCacheDirectory or
	( isset( $options['post'] ) and ! $default_project_name ) ) {
	file_put_contents('php://stderr', 
		"Usage: {$argv[0]} --[" . implode('|', $choice_option_names) . '] '
			. implode( ' ', preg_replace( '/^.*$/', '[--$0]', preg_replace( '/:$/', '=XXX', $optional_option_names ) ) ) . ' '
			. implode( ' ' , preg_replace( '/^.*$/', '--$0', preg_replace( '/:$/', '=XXX', $mandatory_option_names ) ) ) . "\n"
	);
	exit -1;
}

function uncaught_exception_handler( $ex ) {
	wwLog( 'Uncaught exception: ' . $ex->getMessage() );
	global $wwContext, $default_project_name;
	wwLog( $wwContext->wwInterface->report_errors_as_text( 'input file' ) );
}

set_exception_handler( 'uncaught_exception_handler' );

# set up global ww data

$wwContext = new stdClass();
$wwContext->wwInterface = new WMDInterface;
$wwContext->wwStorage = new WMDStorage;

$wwContext->wwInterface->default_project_name = $default_project_name;

$tmpfilename = "$peCacheDirectory/.workingwiki/{$options['data-store']}";

$wmd_data = array(
	'project_pages' => array()
);

if ( isset( $options['pre'] ) ) {
	# preprocessing:
	
	# create and lock the token-data file

	# get the input text

	$intext = file_get_contents( 'php://stdin' );

	if ( file_exists( $tmpfilename ) ) {
		$wmd_data = json_decode( file_get_contents( $tmpfilename ), true );
		#unset( $wmd_data['file_contents']['cache-filled'] );
		$wwContext->wwInterface->uniq_tokens = $wmd_data['uniq_tokens'];
		$wwContext->wwStorage->pagetext_cache = $wmd_data['pagetext_cache'];
		global $wwOutputFormat;
		if ( $wwOutputFormat != 'html' ) {
			unset($wwContext->wwStorage->pagetext_cache[$title]);
			#foreach ( $wwContext->wwStorage->pagetext_cache as $pname=>$pdata ) {
			#	$pdata['project-files'] = array();
			#}
		}
		ProjectDescription::$project_cache = unserialize($wmd_data['project_cache']);
	}
	$wwContext->wwInterface->currently_parsing_key = $title;

	if ( isset( $options['prerequisite-projects'] ) ) {
		$prereq = json_decode( $options['prerequisite-projects'], true );
		$default_project = $wwContext->wwStorage->find_project_by_name( $default_project_name );
		foreach ($prereq as $varname => $pname) {
			$default_project->depends_on[$pname] = array(
				'varname' => $varname,
				'readonly' => true,
			);
		}
	}

	# handle math between double-$ signs, if requested
	if ( isset( $options['process-inline-math'] ) ) {
		$intext = $wwContext->wwInterface->replace_inlines( $intext );
	}

	# find locations of all the tags in the text

	$tag_positions = $wwContext->wwStorage->find_project_files_on_page( $title, $intext ); 
	$wwContext->wwStorage->pagetext_cache[$title]['touched'] = $options['modification-time'];

	#wwLog( json_encode( $tag_positions ) );

	# those tags are sorted by project, need them sorted from top to bottom of page

	# flatten
	$positions = array();
	foreach ( $tag_positions as $pname => $proj_positions ) {
		if ( ! is_array( $proj_positions ) ) {
			# 'cache-filled' => true
			continue;
		}
		foreach ( $proj_positions as $fname => $fdata ) {
			if ( isset( $fdata['position'] ) ) {
				foreach ( $fdata['position'] as $pair ) {
					$positions[] = array( $pair[0], $pair[1], $pname, $fname );
				}
			}
		}
	}
	# sort by page order
	usort( $positions, function ( $a, $b ) {
		return $a[0] - $b[0];
	} );

	#wwLog( json_encode( $positions ) );

	# replace each tag by a token and assemble preprocessed text
	$pretext = '';
	$nextpos = 0;
	foreach ( $positions as $position ) {
		list( $tagstart, $tagend, $pname, $fname ) = $position;
		$tagdata = $tag_positions[$pname][$fname];
		#if ( isset( $tagdata['attributes']['project'] ) ) {
		#	$projectname = $tagdata['attributes']['project'];
		#} else {
		#	$projectname = $wwContext->wwInterface->default_project_name;
		#}
		#print json_encode( $tagdata ) . "\n";
		$keeptext = substr( $intext, $nextpos, $tagstart - $nextpos );
		#wwLog( "keep text: " . $keeptext );
		$pretext .= $keeptext;
		$tagargs = $tagdata['attributes'];
		#if ( ! isset( $tagargs['project'] ) ) {
		#	$tagargs['project'] = $projectname;
		#}
		if ( $tagdata['source'] ) {
			$token = $wwContext->wwInterface->source_file_hook(
				isset( $tagdata['content'] ) ? $tagdata['content'] : '',
				$tagargs,
				null
			);
			#wwLog( "token: $token" );
			$pretext .= $token;
			if ( ! isset( $wwContext->wwInterface->uniq_tokens[$token]['declaration-only'] ) ) {
				# register source file in project description - very
				# important, so it can be found while processing
				# other pages
				$args = $wwContext->wwInterface->uniq_tokens[$token]['args'];
				$project = $wwContext->wwStorage->find_project_by_name( $args['project'] );
				$project->add_source_file( array(
					'filename' => $args['filename'],
					'page' => $title
				) );
			}
		} else {
			$token = $wwContext->wwInterface->project_file_hook(
				'',
				$tagargs,
				null
			);
			#wwLog( "token: $token" );
			$pretext .= $token;
		}
		$nextpos = $tagend + 1;
	}
	$keeptext = substr( $intext, $nextpos );
	#wwLog( "keep text: " . $keeptext );
	$pretext .= $keeptext;

	$wmd_data = array(
		'uniq_tokens' => $wwContext->wwInterface->uniq_tokens,
		'pagetext_cache' => $wwContext->wwStorage->pagetext_cache,
		'project_cache' => serialize(ProjectDescription::$project_cache),
		//'expanded_text' => $intext,
	);
	#wwLog( 'project_cache: ' . $wmd_data['project_cache'] );

	if ( ! isset( $options['post'] ) ) {
		echo $pretext;
	}

	# record the location data
	if ( ! is_dir( dirname( $tmpfilename ) ) ) {
		mkdir( dirname( $tmpfilename ), 0777, true );
	}
	file_put_contents( $tmpfilename, json_encode( $wmd_data ) . "\n" );

}

if ( isset( $options['post'] ) ) {

	# postprocessing:

	if ( ! isset( $options['pre'] ) ) {
		if ( isset( $options['make-single-file'] ) ) {
			$pretext = '';
		} else {
			# read in text with tokens
			$pretext = file_get_contents( 'php://stdin' );
		}

		# read in token data
		$wmd_data = json_decode( file_get_contents( $tmpfilename ), true );
		$wwContext->wwInterface->uniq_tokens = $wmd_data['uniq_tokens'];
		$wwContext->wwStorage->pagetext_cache = $wmd_data['pagetext_cache'];
		ProjectDescription::$project_cache = unserialize($wmd_data['project_cache']);
		$wwContext->wwInterface->currently_parsing_key = $title;
	}

	$posttext = $pretext;
	$wwContext->wwInterface->set_page_being_parsed( $title ); // for MW_PAGENAME env var
	#wwLog( "About to start expand_tokens(), pagetext_cache is " . json_encode( $wwContext->wwStorage->pagetext_cache ) );

	if ( isset( $options['make-single-file'] ) ) {
		# in this case, forget text processing, just make and quit
		$default_project = $wwContext->wwStorage->find_project_by_name( $default_project_name );
		ProjectEngineConnection::call_project_engine(
			'make',
			$default_project,
			array( 'target' => $options['make-single-file'] ),
			array(),
			false
		);
		exit(0);
	}

	if ( isset( $options['enable-make'] ) and 
	     ( ! $options['enable-make'] or $options['enable-make'] == 'false' ) ) {
		global $wwMakeTemporarilyDisabled;
		$wwMakeTemporarilyDisabled = true;
	}

	$wwContext->wwInterface->expand_tokens( $posttext, null, $title );

	# write text to output file
	#file_put_contents( $outfilename, $posttext );
	echo $posttext;

	# expire the data store, unless it's needed in future --pre processing
	if ( ! isset( $options['persistent-data-store'] ) ) {
		unlink( $tmpfilename );
	}
}
