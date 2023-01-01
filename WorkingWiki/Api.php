<?php
/* class for api.php interface to WW actions.  This will be useful in 
 * implementing Ajax interface features.
 *
 * Copyright 2012 by Lee Worden.
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

abstract class WWApiBase extends ApiBase {
	protected $ssePrinter;
	protected $sseKey;

	public function __construct( $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
		$this->ssePrinter = null;
	}

	// used by many classes here: get the ProjectDescription object
	// needed for the operation
	public function getProject( $projectname ) {
		global $wwContext;
	        try {
			$project = $wwContext->wwStorage->find_project_by_name($projectname);
			if ( ! $project ) {
				throw new WWException;
			}
	        } catch ( WWException $ex ) {
			$this->dieUsage(
				'Bad or missing project name for '
				. $this->getRequest()->getVal( 'action' )
				. ' action', 
			      'badprojectname' );
	        }
		return $project;
	}

	// adding convenience functions for use when emitting SSE updates on progress

	// derived classes should/can add these to their params, to enable SSE
	public function getAllowedParams() {
		return array(
			'logkey' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'sse' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
		);
	}

	// derived classes should/can add these to their params description, to enable SSE
	public function getParamDescription() {
		return array(
			'logkey' => '(internal use) key for access to updates from the operation as it happens',
			'sse' => 'flag to request sse output in wikis before MW 1.23',
		);
	}

	protected function outputIsSSE() {
		$this->setupSSE();
		return ( $this->ssePrinter !== null );
	}

	protected function setupSSE( $printer = null ) {
		if ( $printer ) {
			$this->ssePrinter = $printer;
		}
		if ( ! $this->ssePrinter && $this->ssePrinter !== false ) {
			if ( method_exists( $this->getMain()->getPrinter(), 'sendEvent' ) ) {
				$this->ssePrinter = $this->getMain()->getPrinter();
			} else if ( $this->getRequest()->getVal( 'sse' ) ) {
				$this->ssePrinter = new WWApiFormatSSE( $this->getMain(), 'sse' );
			} else {
				$this->ssePrinter = false;
				$params = $this->extractRequestParams();
				if ( $params and $params['logkey'] ) {
					// logkey should only be used with SSE output
					$this->dieUsage( 'Bad format for use with logkey', 'unknownerror' );
				}
			}
		}
	}

	protected function claimSSEkey( $key ) {
		$this->setupSSE();
		if ( $this->ssePrinter ) {
			$this->sendSSEUrl( $key );
			try {
				$result = ProjectEngineConnection::call_project_engine_lowlevel( array(
					'operation' => array(
						'name' => 'claim-log-key',
						'key' => $this->sseKey,
					),
				) );
				wwLog( 'claim-log-key result: ' . json_encode( $result ) );
				$success = $result['succeeded'];
				$claimed = $success && $result['claimed'];
			} catch ( WWException $ex ) {
				$success = false;
			}
			if (0) {
				if ( ! $success ) {
					$this->sseEvent( 'Internal error', 'error' );
				} else if ( ! $claimed ) {
					$this->sseEvent( 
						'This operation is already executing.',
						'error'
					);
				}
			}
		}
	}

	protected function sendSSEUrl( $key ) {
		$this->setupSSE();
		if ( ! $this->ssePrinter )
			return;
		$this->sseKey = $key;
		// this tells the client to disconnect and start listening for events
		// at the url provided instead.
		global $wgScript;
		$this->sseEvent( 
			"$wgScript/Special:PE?operation[name]=sse-retrieve-log&operation[key]=$key",
			'updates-url'
		);
	}

	// write an SSE event, if appropriate, do nothing if not
	public function sseEvent() {
		$this->setupSSE();
		if ( $this->ssePrinter ) {
			$args = func_get_args();
			call_user_func_array(
				array( $this->ssePrinter, 'sendEvent' ),
				$args
			);
		}
	}

	// record an update message in the streaming log, via PE
	public function logUpdate( $text, $event = null ) {
		$this->setupSSE();
		if ( ! $this->ssePrinter ) {
			return;
		}
		try {
			wwLog( "WW: log update: $event: $text" );
			$operation = array(
				'name' => 'append-to-log',
				'key' => $this->sseKey,
				'text' => $text,
			);
			if ( $event ) {
				$operation['event'] = $event;
			}
			$result = ProjectEngineConnection::call_project_engine_lowlevel( array(
				'operation' => $operation
			) );
			$success = isset( $result['succeeded'] ) and $result['succeeded'];
		} catch ( WWException $ex ) {
			$success = false;
		}
		if ( ! $success ) {
			wwLog( 'failed logging message to ProjectEngine' );
		}
	}

	public function sendResult() {
		$this->logUpdate( json_encode( $this->getResult()->getData() ), 'result' );
	}

	// close SSE stream after doing operation, to prevent extra output
	public function closeSSE( $sendResultFirst = true ) {
		$this->setupSSE();
		if ( $this->ssePrinter ) {
			if ( $sendResultFirst ) {
				$this->sendResult();
			}
			$this->ssePrinter->close();
		}
	}

	// handle error output differently when in comet situation
	public function dieUsage( $message, $code, $httpRespCode = 0, $extradata = NULL ) {
		$this->setupSSE();
		if ( $this->ssePrinter ) {
			$error = array(
				'code' => $code,
				'info' => $message
			);
			if ( is_array( $extradata ) ) {
				$error = array_merge( $error, $extradata );
			}
			$this->logUpdate( json_encode( array( 'error' => $error ) ), 'result' );
			$this->closeSSE( false );
		} else {
			parent::dieUsage( $message, $code, $httpRespCode, $extradata );
		}
	}
}

class WWApiImportProjectFiles extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		#wwLog("in WWApiImportProjectFiles");

		#wwLog("params is: " . serialize($params) );

		if (!isset($params['filename']) or !isset($params['project'])) {
			$this->dieUsage(
				'Parameters "filename" and "project" are required.', 
				'missingparam'
			);
		}
		$projectname = $params['project'];
		$project = $this->getProject($projectname);
		$filename = $params['filename'];

		$src = $params['as-source-file'];

		// what if it's already there?
		$creon = $params['create-only'];
		if ($creon and isset($project->project_files[$filename])
			and (!$src or isset($project->project_files[$filename]['source']))) {
			// need to be more sophisticated in the project-file case?
			// here create-only means fail if the project has a project-file
			// tag of that name on any page.
			$this->dieUsage( 'File is already present.', 'create-only' );
		}
		// do we need to know a page location?
		if (isset($params['page'])) {
			$page = $params['page'];
		}
		else if (isset($project->project_files[$filename]['page'])) {
			$page = $project->project_files[$filename]['page'];
		} else {
			$this->dieUsage(
				'Parameter "page" is required, because project "'
				. $project->project_name()
				. '" does not have a location recorded for '
				. ' file "'
				. $filename
				. '".',
				'page'
			);
		}

		if ( isset($params['file-contents'])
		     and $params['file-contents'] !== null ) {
			$file_contents = $params['file-contents'];
		} else if ( isset($params['file-contents-json-encoded']) 
		     and $params['file-contents-json-encoded'] !== null) {
				$file_contents = json_decode($params['file-contents-json-encoded']);
		} else if (isset($params['file-contents-base64-encoded'])
		     and $params['file-contents-base64-encoded'] !== null) {
				$file_contents = base64_decode($params['file-contents-base64-encoded']);
		} else if ($src) {
			$this->dieUsage(
				'One of "file-contents", "file-contents-json-encoded", or '
				. '"file-contents-base64-encoded" is required.',
				'missingparam'
			);
		} else {
			$file_contents = null;
		}

		try {
			// import the file text into the wiki
			$title = Title::newFromText($page);
			if (!is_object($title)) {
				$this->dieUsageMsg('invalidtitle');
			}
			if ($title->getNamespace() == NS_MEDIA) {
				$title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
			}
			if ( class_exists( 'ImportQueue' ) ) {
				$importer = new ImportQueue;
			} else {
				$importer = new ImportProjectFiles;
			}
			if ($title->getNamespace() == NS_IMAGE) {
				global $wgTmpDirectory;
				$tmpfname = tempnam($wgTmpDirectory, 'ww_upload_tmp_');
				$tmpfhandle = fopen($tmpfname, "w");
				if ($tmpfhandle === false) {
					$this->die_usage('Couldn\'t open temp file to write', 'unknownerror');
				}
				if (fwrite($tmpfhandle, $file_contents) === false) {
					$this->die_usage('Couldn\'t write to temp file', 'unknownerror');
				}
				fclose($tmpfhandle);
				$importer->upload_file( $page, $tmpfname );
			} else {
				$attrs = $params['tag-attributes'];
				$retval = $importer->insert_file_element(
					/*source*/$src,
					$filename,
					$projectname,
					$page,
					$file_contents,
					$attrs
				);
				if (! $retval) {
					$this->dieUsage(
						"Error inserting file '$filename' into page '$page':\n"
						. $wwContext->wwInterface->report_errors_as_text('file', $filename),
						'unknownerror'
					);
				}
			}
			if ( ! $importer->commit( /*overwrite*/true ) ) {
				$this->dieUsage( 
					$wwContext->wwInterface->report_errors_as_text('file', $filename),
					'unknownerror'
				);
			}
			// add the file to the project
			$project->add_source_file( array( 'filename' => $filename, 'page' => $page ) );
			$wwContext->wwStorage->save_project_description($project);
			$wwContext->wwInterface->invalidate_pages( $project );
			// should I sync the file into the directory?	No need, I think, as
			// the project will do it the next time it does a make.
		} catch (UsageException $ex) {
			throw $ex;
		} catch (Exception $ex) {
			$this->dieUsage(
				'Internal error: caught an exception while importing.',
				'unknownerror'
			);
		}
		# for backward compatibility, remove soon
		$this->getResult()->addValue( null, 'success', true );

		$this->closeSSE();
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'project' => array( 
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
			'filename' => array(
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
			'page' => array(
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
			'as-source-file' => array(
				# if I declare this as a boolean, I get true any time the
				# parameter is present.	I want to allow as-source-file=0,
				# so I call it an integer.
					ApiBase::PARAM_TYPE => 'integer',
					#ApiBase::PARAM_REQUIRED => false
			),
			'create-only' => array(
					ApiBase::PARAM_TYPE => 'integer',
					#ApiBase::PARAM_REQUIRED => false
			),
			'file-contents' => array(
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
			'file-contents-json-encoded' => array(
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
			'file-contents-base64-encoded' => array(
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
			'tag-attributes' => array(
					ApiBase::PARAM_TYPE => 'string',
					#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'filename' => 'Name of project file',
			'page' => 'Name of wiki page destination for file',
			'as-source-file' => 'Whether to upload the file as a source file',
			'create-only' => 'If true, refuse to upload a source file that already exists',
			'file-contents' => 'Contents of the file',
			'file-contents-json-encoded' => 'Contents of the file, '
				.'JSON encoded (alternative to file-contents parameter)',
			'file-contents-base64-encoded' => 'Contents of the file, '
				.'base-64 encoded (alternative to file-contents parameter)',
			'tag-attributes' => 'attributes to add to wikitext tag, for example "display=\"link\" linktext=\"click here\""',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Upload a file into a WorkingWiki project';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiGetProjectFile extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		wwLog( "in WWApiGetProjectFile" );

		wwLog( "params is: " . serialize($params) );

		$this->claimSSEkey( $params['logkey'] );

		// this has to be called before anything: establish exclusive ownership
		// of the key for logging events: if someone already has it, this is a
		// repeat call to the api and needs to return without doing anything.
		// EventSource objects in the browser make reconnect attempts
		// automatically, so we need to watch out for this.
		$this->claimSSEkey( $params['logkey'] );

		$projectname = $params['project'];
		$resources = $params['resources'];
		$filename = $params['filename'];

		$this->logUpdate( 'WorkingWiki fetching file ' . htmlspecialchars( $filename ) . ".\n" );

		# source files get retrieved from the wiki, rather than from working dir.
		# except when previewing - they're synced in the dir and not available anywhere else.
		$source = ($params['source-file'] and ! $params['preview-key']);
		if ($source && !$resources) {
			# if this is set, retrieve source file text from wiki storage.
			# if not, retrieve file content from working directory.
			# this looks like it's going to duplicate a lot of Special:GetProjectFile.
			# should fix that.
			$revid = $params['revision'];
			#wwLog(" get source file: $projectname, $filename, $revid");
			if ($revid != '') {
				$rev = Revision::newFromId($revid);
				$project = $wwContext->wwStorage->find_project_by_name($projectname, true, $revid);
			} else {
				$project = $this->getProject($projectname);
			}
			#wwLog("project's project_files is " . serialize($project->project_files) );
			$page = (isset($project->project_files[$filename]['page']) ?
				$project->project_files[$filename]['page'] : null);
			#wwLog("page is $page");

			$this->logUpdate( "Fetching source file content from wiki page.\n" );

			$sfc = $project->find_source_file_content($filename, $page);
			#wwLog("sfc is " . serialize($sfc) );
			if ( $params['html'] ) {
				if ( ! isset( $sfc['text']) ) {
					wwLog( "Source file not found in Api.php" );
					$this->dieUsage( 'filenotfound',
						'Could not retrieve source file '
						. htmlspecialchars( $filename ) );
				}
				$text = $sfc['text'];
			} else {
				if ($sfc['type'] == 'file') {
					$title = Title::newFromText($sfc['page']);
					$fileopts = array('private'=>true);
					if ($revid != '') {
						$fileopts['time'] = $rev->getTimestamp();
					}
					#$file = wfFindFile($title, $fileopts);
					$file = wfLocalFile($title);
					if ( ! $file or ! $file->exists() ) {
						$this->dieUsage( 'File not found', 'missingfile' );
					} else {
						$this->getResult()->addValue( null, 'url', $file->getFullUrl() );
						$this->getResult()->addValue( null, 'timestamp', 
							wfTimestamp( TS_ISO_8601, $file->getTimestamp() ) );
						if ($revid != '') {
							global $wgUser;
							$this->getResult()->addValue( null, 'user', 
									$rev->getUserText( ) ); # use args for more recent MW
									#$rev->getUserText( Revision::FOR_THIS_USER, $wgUser ) );
							$this->getResult()->addValue( null, 'comment', 
									$rev->getComment( ) );
									#$rev->getComment( Revision::FOR_THIS_USER, $wgUser ) );
						} else {
							$this->getResult()->addValue( null, 'user', 'WorkingWiki' );
							$this->getResult()->addValue( null, 'comment', 'Pull resources from wiki' );
						}
					}
				} else if (!isset($sfc['text'])) {
					$this->dieUsage( "No text found", 'missingfile' );
				} else if ($sfc['text'] === null) {
					$this->dieUsage( "Null text found", 'missingfile' );
				} else {
					$this->addTextValue( $sfc['text'] );
					if ($revid != '') {
						global $wgUser;
						$this->getResult()->addValue(
							null,
							'user', 
							$rev->getUserText()
						); # use args for more recent MW
						#$rev->getUserText( Revision::FOR_THIS_USER, $wgUser ) );
						$this->getResult()->addValue(
							null,
							'comment', 
							$rev->getComment()
						);
						#$rev->getComment( Revision::FOR_THIS_USER, $wgUser ) );
						$this->getResult()->addValue(
							null,
							'timestamp', 
							wfTimestamp( TS_ISO_8601, $rev->getTimestamp() )
						);
					} else {
						$this->getResult()->addValue( null, 'user', 'WorkingWiki' );
						$this->getResult()->addValue( null, 'comment', 'Pull resources from wiki' );
						$ts = $project->latest_revision()->getTimestamp();
						$this->getResult()->addValue(
							null,
							'timestamp', 
							wfTimestamp( TS_ISO_8601, $ts)
						);
					}
				}
			}
		} else {
			# without 'source-file', retrieve file content from working directory.
			if ( ! $resources ) {
				$project = $this->getProject($projectname);
			}
			# possibly make before retrieving.
			if ($params['make'] && !$resources) {
				if ( $params['background-job'] ) {
					$this->dieUsage(
						'nomakeinbackground',
						$wwContext->wwInterface->message( 'ww-no-make-in-background' )
					);
				}
				$this->logUpdate( "Running make...\n" );
				try {
					#wwLog( 'make_target: ' . $project->project_name() . ' > ' . $filename );
					if ( $params['containing-page'] ) {
						$wwContext->wwInterface->set_page_being_parsed( $params['containing-page'] );
					}
					$make_success = ProjectEngineConnection::make_target(
						$project,
						$filename,
						array(),
						array()
					);
				} catch ( WWException $ex ) {
					$make_success = false;
				}
				$wwContext->wwInterface->save_modified_projects();
				$wwContext->wwStorage->update_archived_project_files();
				if (!$make_success) {
					$logfilename = $filename.'.make.log';
		#wwLog( 'make did not succeed' );
					$wwContext->wwInterface->record_error( 
						$wwContext->wwInterface->altlinks_text($project,$filename,array(),true) .
						$wwContext->wwInterface->message(
							'ww-make-failed',
							htmlspecialchars($filename),
							$wwContext->wwInterface->make_get_project_file_url($project,$logfilename,false)
						)
					);
					$this->dieUsage(
						"Failed to make '$filename'.",
						'makefailed',
						0,
						array( 'messages' => $wwContext->wwInterface->report_errors() )
					);
				}
			}
			# now retrieve.
			if ( ! $params['html'] ) {
				$dereference_symlinks = $params['dereference-symlinks'];
				$this->logUpdate( "Retrieving file from working directory...\n" );
				$pe_result = ProjectEngineConnection::call_project_engine(
					'retrieve',
					($resources ? 'resources' : $project),
					array(
						'target'=>$filename, 
						'dereference-symlinks'=>$dereference_symlinks
					)
				);
				$file_contents = null;
				if ( is_array($pe_result) and
				    isset($pe_result['target-file-contents']) and
				    isset($pe_result['target-file-contents'][$filename]) ) {
					$file_contents = $pe_result['target-file-contents'][$filename];
				}
				if (is_null($file_contents)) {
					$this->dieUsage( 'File not found', 'missingfile' );
				} else {
					if ($file_contents[0] == 'c') {
						$this->addTextValue( $file_contents[1] );
						$this->getResult()->addValue(
							null,
							'timestamp', 
							wfTimestamp( TS_ISO_8601, $file_contents[2] )
						);
					} else if ($file_contents[0] == 'p') {
						$path = $file_contents[1];
						global $wwMaxLengthForSourceCodeDisplay;
						if (filesize($path) > $wwMaxLengthForSourceCodeDisplay) {
							$this->getResult()->addValue(
								null,
								'url', 
								$wwContext->wwInterface->make_get_project_file_url(
									($resources ? null : $projectname),
									$filename,
									/*make*/false,
/*display*/'raw'
								)
							);
						} else {
							$this->addTextValue( file_get_contents($path) );
						}
						$this->getResult()->addValue(
							null,
							'timestamp', 
							wfTimestamp( TS_ISO_8601, filemtime($path) )
						);
					} else {
						$this->dieUsage( 'Can not return directory contents', 'badcontent' );
					}
				}
			} else {
				$text = null;
			}
			$this->getResult()->addValue( null, 'user', 'WorkingWiki' );
			$this->getResult()->addValue( null, 'comment', 'Pull resources from wiki' );
		}
		if ( $params['html'] ) {
			if ( ! $source and ! $params['make'] ) {
				$this->logUpdate( "Retrieving and formatting file contents for display.\n" );
			} else {
				$this->logUpdate( "Formatting file contents for display.\n" );
			}
			$display = $params['display'];
			$parser = new Parser;
			$wwContext->wwInterface->set_page_being_parsed( $params['containing-page'] );
			#$title = $params['containing-page'];
			#wwLog( 'containing page is ' . $title );
			#if ( ! $title ) {
				$title = Title::newFromText( '[API]' );
			#} else {
			#	$title = Title::newFromText( $title );
			#}
			$altlinks = $params['altlinks'];
			if ( $altlinks ) {
				$altlinks = json_decode( $altlinks, true );
			} else {
				$altlinks = $wwContext->wwInterface->make_altlinks( array() );
			}
			$args = $params['tag-args'];
			if ( ! $args ) {
				$args = array();
			} else {
				$args = json_decode( $args, true );
			}
			$parser->startExternalParse( $title, new ParserOptions, OT_HTML, true );
			$tproj = ($resources ? ResourcesProjectDescription::factory() : $project); 
			$this->addTextValue( $wwContext->wwInterface->display_file_contents(
				$tproj,
				$filename,
				/*text*/$text,
				/*display*/$display ? $display : false,
				/*alts*/$altlinks,
				/*line*/false,
				/*args*/$args,
				$parser,
				/*getprojfile*/false
			) );
			$this->addIfPresent( 'modules', $parser->getOutput()->getModules() );
			$this->addIfPresent( 'styles', $parser->getOutput()->getModuleStyles() );
			$this->addIfPresent( 'scripts', $parser->getOutput()->getModuleScripts() );
			$this->addIfPresent( 'messages', $parser->getOutput()->getModuleMessages() );
			$this->addIfPresent( 'headItems', $parser->getOutput()->getHeadItems() );
			# to do: display_project_file records an error if file not found, but I'd like to do a
			# different message for "make failed but did not create the file".
			$messages = $wwContext->wwInterface->report_errors();
			if ( $messages ) {
				$this->getResult()->addValue( null, 'ww-get-project-file', array( 'messages' => $messages ) );
			}
		}
		$this->getResult()->addValue( null, 'success', true );
		$this->closeSSE();
	}

	public function addTextValue( $text ) {
		if ( mb_detect_encoding( $text, 'ASCII', true ) or
		    mb_detect_encoding( $text, 'UTF-8', true ) ) {
			$this->getResult()->addValue( null, 'text', $text );
		} else {
			$this->getResult()->addValue(
				null,
				'text-base64',
				base64_encode( $text )
			);
		}
	}

	public function addIfPresent( $key, $ar ) {
		if ( count( $ar ) > 0 ) {
			$this->getResult()->addValue( null, $key, $ar );
		}
	}

	public function getAllowedParams() {
		return array(
			'project' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'filename' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'revision' => array( # an integer, but let the default be ''
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'source-file' => array(
				# integer for boolean because boolean comes out true whenever it
				# is assigned any value
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'resources' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'make' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'dereference-symlinks' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'containing-page' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'preview-key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'background-job' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'html' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'display' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'altlinks' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'tag-args' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'filename' => 'Name of project file',
			'source-file' => 'Whether to look in the wiki for a source file, versus in the working directory for a project file',
			'revision' => 'A revision number of a wiki page, to retrieve a past version of a source file',
			'resources' => 'If true, retrieve from the resources directory, rather than from a project',
			'make' => 'If true, make the project file before retrieving',
			'dereference-symlinks' => 'If true and the project file is a symbolic link, return the content of the link rather than the file it references',
			'containing-page' => 'For an operation involving parsing wikitext, the title of the wiki page within which it should be presumed to be parsed',
			'preview-key' => 'If a nonempty string, key indicating a preview session',
			'background-job' => 'If a nonempty string, key indicating a background job',
			'html' => 'If true, return HTML code to display the file, rather than raw file contents',
			'display' => 'If "html" is true, in what format to display the file',
			'altlinks' => 'JSON structure representing action links to attach to HTML output',
			'tag-args' => 'JSON structure representing miscellaneous arguments from the source-file or project-file tag',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Retrieve a file from a WorkingWiki project';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiGetProjectData extends WWApiBase {
	public function execute() {
		$params = $this->extractRequestParams();

		#wwLog("in WWApiGetProjectData");
		#wwLog("params is: " . serialize($params) );

		$this->claimSSEkey( $params['logkey'] );

		$projectname = $params['project'];
		$project = $this->getProject($projectname);
		if ($project === null) {
			$this->dieUsage( "Project not found", 'projectnotfound' );
		}
		$this->getResult()->addValue( null, 'project-files', $project->project_files );
		$this->getResult()->addValue( null, 'options', $project->options );
		$lr = $project->latest_revision();
		if ($lr) {
			$this->getResult()->addValue( null, 'last-revision', $lr->getId() );
			$this->getResult()->addValue(
				null,
				'timestamp',
				wfTimestamp( TS_ISO_8601, $lr->getTimestamp() )
			);
		}
		$this->getResult()->addValue( null, 'success', true );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Retrieve data about a WorkingWiki project';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiGetProjectRevisions extends WWApiBase {
	public function execute() {
		$params = $this->extractRequestParams();
		#wwLog("in WWApiGetProjectRevisions");
		#wwLog("params is: " . serialize($params) );

		$this->claimSSEkey( $params['logkey'] );

		$projectname = $params['project'];
		$project = $this->getProject($projectname);
		$this->getResult()->addValue(
			null,
			'revisions',
			$project->project_revisions($params['fetch-from'])
		);
		$this->getResult()->addValue( null, 'success', true );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'fetch-from' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'fetch-from' => 'Last revision number retrieved',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Retrieve a list of changes to the structure of a WorkingWiki project';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiGetFileRevisionsInProject extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();
		#wwLog("in WWApiGetFileRevisionsInProject");
		#wwLog("params is: " . serialize($params) );

		$this->claimSSEkey( $params['logkey'] );

		$projectname = $params['project'];
		$fetch_from = $params['fetch-from'];
		$fetch_to = $params['fetch-to'];
		if ($fetch_to <= 0) {
			$fetch_to = null;
		}
		try {
			$tuples = $wwContext->wwStorage->file_revisions_in_project(
				$projectname,
				$fetch_from,
				$fetch_to
			);
		} catch (Exception $ex) {
			$this->dieUsage( 
				$wwContext->wwInterface->report_errors_as_text( 'revisions' ),
				'unknownerror'
			);
		}
		$this->getResult()->addValue( null, 'revisions', $tuples );
		$this->getResult()->addValue( null, 'success', true );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'fetch-from' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'fetch-to' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'fetch-from' => 'First revision number to consider',
			'fetch-to' => 'First revision number to omit',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Retrieve a list of changes to files in a WorkingWiki project';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiListDirectory extends WWApiBase {
	public function execute() {
		$params = $this->extractRequestParams();
		#wwLog("WWApiListDirectory");
		#wwLog("params is: " . serialize($params));

		$this->claimSSEkey( $params['logkey'] );

		$directory = $params['directory'];
		$fetch_from_timestamp = $params['fetch-from-timestamp'];
		$projectname = $params['project'];
		if ( $projectname === 'pe-resources:' ) {
			$project = null;
			$project_arg = 'resources';
			$allowActions = false;
		} else {
			$project = $this->getProject( $projectname );
			$project_arg = $project;
			$allowActions = ! wwfReadOnly();
		}
		$pe_result = ProjectEngineConnection::call_project_engine(
			'retrieve',
			$project_arg,
			array('target'=>$directory)
		);
		if ( ! is_array($pe_result) or !isset($pe_result['target-file-contents'])
		    or !isset($pe_result['target-file-contents'][$directory] )
		    or $pe_result['target-file-contents'][$directory][0] != 'd' ) {
			#wwLog( json_encode( $pe_result ) );
			$this->dieUsage(
				'Could not retrieve directory contents',
				'directorynotfound'
			);
		}
		if ( $params['html'] ) {
			$this->getResult()->addValue( null, 'html', wwfHtmlDirectoryListing(
				$pe_result['target-file-contents'][$directory][1],
				$directory,
				$project,
				$allowActions
			) );
		} else {
			$files = array();
			foreach ( $pe_result['target-file-contents'][$directory][1]
				as $filename=>$info ) {
				if ($info[2] > $fetch_from_timestamp or preg_match('/^d/', $info[0])) {
					$info[2] = wfTimestamp( TS_ISO_8601, $info[2] );
					$files[$filename] = $info;
				}
			}
			#$this->getResult()->addValue( null, 'timestamp', $fetch_from_timestamp );
			$this->getResult()->addValue( null, 'files', $files );
		}
		$this->getResult()->addValue( null, 'success', true );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'directory' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'fetch-from-timestamp' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'preview-key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'background-job' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'html' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Name of WW project',
			'directory' => 'Name of directory within project',
			'fetch-from-timestamp' => 'Omit files older than this time',
			'preview-key' => 'If nonempty, key indicating a preview session',
			'background-job' => 'If a nonempty string, key indicating a background job',
			'html' => 'Whether to provide an HTML-formatted listing',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'List the files in a WorkingWiki project\'s working directory';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiListResourcesDirectory extends WWApiBase {
	public function execute() {
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );
		$directory = $params['directory'];
		$dr = new DerivativeRequest(
			$this->getRequest(),
			array(
				'action' => 'ww-list-directory',
				'project' => 'pe-resources:',
	'directory' => $directory,
			)
		);
		$api = new ApiMain( $dr );
		$api->execute();
		foreach ( $api->getResultData() as $key => $value ) {
			$this->getResult()->addValue( null, $key, $value );
		}
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'directory' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'fetch-from-timestamp' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'directory' => 'Directory to list',
			'fetch-from-timestamp' => 'Omit files older than this time',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Retrieve a list of files in WorkingWiki\'s resources directory';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiSetSourceFileLocation extends WWApiBase {
	function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		$filename = $params['filename'];
		#wwLog("WWApiSetSourceFileLocation");
		#wwLog("params is: " . serialize($params));
		if ($filename == '') {
			$this->dieUsage(
				'Missing filename for ‘set sourcefile location’ action.',
				'missingparam'
			);
		}
		$page = $params['page'];
		$page = urldecode( $page );
		$project = $this->getProject( $params['project'] );
		try {
			if ( isset($project->project_files[$filename]) and
			     isset($project->project_files[$filename]['page'])) {
				$ititle = Title::newFromText($project->project_files[$filename]['page']);
				if (is_object($ititle)) {
					if ( $ititle->getNamespace() != NS_MEDIA ) {
						$ititle = Title::makeTitle( NS_IMAGE, $ititle->getDBkey() );
					}
					if ( $ititle->getNamespace() != NS_SPECIAL ) {
						$ititle->invalidateCache();
					}
				}
			}
			if ($page) {
				$project->add_source_file(
					array( 'filename' => $filename, 'page' => $page )
				);
			} else {
				$project->add_source_file( 
					array( 'filename' => $filename, 'page' => null )
				);
			}
			$wwContext->wwStorage->save_project_description($project);
			$wwContext->wwInterface->invalidate_pages( $project );
		} catch ( WWException $ex ) {
			$this->dieUsage(
				"Error setting location for source-file ‘" 
				. htmlspecialchars($filename)
				. "’.",
				'internalerror',
				0,
				array( 'messages' => $wwContext->wwInterface->report_errors() )
			);
		}
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'filename' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'page' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'filename' => 'Name of source file',
			'page' => 'Name of wiki page where source file is defined',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Establish the location where a source file is stored on the wiki.';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiSetProjectFileLocation extends WWApiBase {
	function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		$filename = $params['filename'];
		#wwLog("WWApiSetSourceFileLocation");
		#wwLog("params is: " . serialize($params));
		if ($filename == '') {
			$this->dieUsage(
				'Missing filename for ‘set project-file location’ action.',
				'missingparam'
			);
		}
		if ( ! ProjectDescription::is_allowable_filename($filename) ) {
			return array(
				'status' => WW_ERROR,
				'message' => "Prohibited filename ‘.".htmlspecialchars($filename)."’.",
			);
		}
		$page = $params['page'];
		$project = $this->getProject( $params['project'] );

		try {
			$project->add_file_element( array(
				'filename' => $filename,
				'appears' => array($page=>true)
			) );
			$wwContext->wwStorage->save_project_description($project);
			$wwContext->wwInterface->invalidate_pages( $project );
		} catch ( WWException $ex ) {
			$this->dieUsage(
				"Error setting location for project-file ‘" 
				. htmlspecialchars($filename)
				. "’.",
				'internalerror',
				0,
				array( 'messages' => $wwContext->wwInterface->report_errors() )
			);
		}
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'filename' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'page' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'filename' => 'Name of project file',
			'page' => 'Name of wiki page where project file appears',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Record that a project file appears on a particular page';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiRemoveFile extends WWApiBase {
	public function execute() {
		global $wwContext;
		// this can remove source files and project files;
		// from the working directory, from the project, and/or from their pages,
		// depending on the arguments.
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		$filename = $params['filename'];
		$projectname = $params['project'];
		$project = $this->getProject( $projectname );
		$remove_from_wiki = $params['projwdpage'];
		$remove_from_proj = $remove_from_wiki || $params['projwd'];
		$remove_from_wd = $remove_from_proj || $params['wdonly'];
		if ( ! $remove_from_wd ) {
			$this->dieUsage(
				"One of 'wdonly', 'projwd', 'projwdpage' arguments must be selected",
				'missingparam'
			);
		}
		$message = null;
		if ($remove_from_wiki) {
			try {
				#wwLog("remove from wiki.");
				$sfc = $project->find_source_file_content($filename,null);
				$wwContext->wwStorage->remove_element_from_wiki($project,$filename,/*src*/true,$sfc['page']);
			} catch ( WWException $ex ) {
			} # if that fails, do the remove from project anyway
		}
		#wwLog("remove from directory.");
		$op_result = ProjectEngineConnection::call_project_engine(
			'remove',
			$project,
			array('target'=>$filename)
		);
		#$success = $op_result['succeeded'];
		# note, invalidate pages first so as to include the one that's being
		# removed
		$wwContext->wwInterface->invalidate_pages( $project );
		if ($remove_from_proj) {
			#wwLog("remove from project.");
			unset($project->project_files[$filename]);
			$wwContext->wwStorage->save_project_description($project);
		}
		
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'filename' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'projwdpage' => array( 
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'projwd' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'wdonly' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
			'preview-key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'background-job' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'filename' => 'File name',
			'wdonly' => 'Only remove from the working directory',
			'projwd' => 'Remove from the project description and working directory',
			'projwdpage' => 'Remove from the project description, working directory, and the wiki page where it is stored',
			'preview-key' => 'If nonempty, key indicating a preview session',
			'background-job' => 'If a nonempty string, key indicating a background job',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Remove a file from a project\'s working directory, and optionally from the project and the wiki.';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiSyncFile extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		wwLog("WWApiSyncFile, params is " . json_encode($params) . "\n");

		$filename = $params['filename'];
		if ( $filename === '' ) {
			$this->dieUsage(
				'Missing filename for sync action', 
				'missingparam'
			);
		}
		if ( ! ProjectDescription::is_allowable_filename( $filename ) ) {
			$this->dieUsage(
				'Invalid filename provided',
				'badfilename'
			);
		}
		$projectname = $params['project'];
		$project = $this->getProject($projectname);
		if ( ! $project ) {
			$this->dieUsage(
				'Missing project name for sync action', 
				'missingparam'
			);
		}
		$request = array();
		$content = $params['content'];
		if ( $content ) {
			$request['projects'][ $project->project_uri() ]['source-file-contents'] = array(
				$filename => array(
					$filename,
					json_decode( $content, true ),
					wfTimestampNow() // todo: accept timestamp as arg
				)
			);
		}

		try {
			$op_result = ProjectEngineConnection::call_project_engine(
				'force-sync',
				$project,
				array( 'target' => $filename ),
				$request
			);
			$success = $op_result['succeeded'];
			$wwContext->wwInterface->invalidate_pages( $project );
		} catch ( WWException $ex ) {
			$success = false;
		}
		$messages = $wwContext->wwInterface->report_errors();
		if ( ! $success ) {
			$this->dieUsage(
				'Sync failed',
				'syncfailed',
				0,
				array( 'messages' => $messages )
			);
		}

		if ( $messages ) {
			$this->getResult()->addValue( null, 'ww-sync-file', array( 'messages' => $messages ) );
		}
		$this->closeSSE();
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'filename' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'content' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'preview-key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'background-job' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'project name',
			'filename' => 'name of file to sync',
			'content' => 'If not "", JSON-encoded content of file',
			'preview-key' => 'If nonempty, key indicating a preview session',
			'background-job' => 'If a nonempty string, key indicating a background job',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Write a source file\'s contents from wiki storage into its project\'s working directory.';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiSyncAll extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();
		#wwLog("WWApiSyncAll, params is " . json_encode($params) . "\n");

		$this->claimSSEkey( $params['logkey'] );

		$projectname = $params['project'];
		$project = $this->getProject($projectname);
		if ( ! $project ) {
			$this->dieUsage(
				'Missing project name for sync-all action', 
				'missingparam'
			);
		}

		try {
			$op_result = ProjectEngineConnection::call_project_engine(
				'force-sync',
				$project,
				null,
				array(),
				true
			);
			$success = $op_result['succeeded'];
			$wwContext->wwInterface->invalidate_pages( $project );
		} catch ( WWException $ex ) {
			$success = false;
		}
		$messages = $wwContext->wwInterface->report_errors();
		if ( ! $success ) {
			$this->dieUsage(
				'Sync failed',
				'syncfailed',
				0,
				array( 'messages' => $messages )
			);
		}

		if ( $messages ) {
			$this->getResult()->addValue( null, 'ww-sync-all', array( 'messages' => $messages ) );
		}
		$this->closeSSE();
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'preview-key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'background-job' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'project name',
			'preview-key' => 'If nonempty, key indicating a preview session',
			'background-job' => 'If a nonempty string, key indicating a background job',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Write all source files\' contents from wiki storage into the project\'s working directory.';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiClearDirectory extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();
		#wwLog("WWApiClearDirectory, params is " . json_encode($params) . "\n");

		$this->claimSSEkey( $params['logkey'] );

		$projectname = $params['project'];
		$project = $this->getProject($projectname);
		if ( ! $project ) {
			$this->dieUsage(
				'Missing project name for clear-directory action', 
				'missingparam'
			);
		}

		try {
			$op_result = ProjectEngineConnection::call_project_engine(
				'clear-directory',
				$project,
				array()
			);
			$success = $op_result['succeeded'];
		} catch ( WWException $ex ) {
			$success = false;
		}
		$messages = $wwContext->wwInterface->report_errors();
		if ( ! $success ) {
			$this->dieUsage(
				'Failed to clear directory',
				'cleardirectoryfailed',
				0,
				array( 'messages' => $messages )
			);
		}

		if ( $messages ) {
			$this->getResult()->addValue( null, 'ww-clear-directory', array( 'messages' => $messages ) );
		}
		$this->closeSSE();
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'preview-key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'background-job' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'project name',
			'preview-key' => 'If nonempty, key indicating a preview session',
			'background-job' => 'If a nonempty string, key indicating a background job',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Remove all files from the project\'s working directory';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiSetPrerequisite extends WWApiBase {
	function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		$prereq = $params['prerequisite'];
		wwLog("WWApiSetPrerequisite");
		wwLog("params: " . serialize($params));
		if ($prereq == '') {
			$this->dieUsage(
				'Missing prerequisite name for ‘set prerequisite project’ action.',
				'missingparam'
			);
		}
		$project = $this->getProject( $params['project'] );
		$res = array();
		try {
			$project->depends_on[$prereq] = array(
				'varname' => $params['varname'],
				'readonly' => ($params['readonly'] ? 1 : 0),
			);
			$wwContext->wwStorage->save_project_description($project);
			$wwContext->wwInterface->invalidate_pages( $project );
			$res['html'] = wwfHtmlPrerequisiteInfo( $project );
		} catch ( WWException $ex ) {
			$this->dieUsage(
				"Error updating prerequisite project info.",
				'internalerror',
				0,
				array( 'messages' => $wwContext->wwInterface->report_errors() )
			);
		}
		$this->getResult()->addValue( null, 'ww-set-prerequisite', $res );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'prerequisite' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'varname' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'readonly' => array( 
				ApiBase::PARAM_TYPE => 'boolean',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'prerequisite' => 'Name of prerequisite project',
			'varname' => 'Variable name to be used for prerequisite directory in make operations',
			'readonly' => 'True if prerequisite directory can be safely used without copying during preview and background operations',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Add or update prerequisite project information';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiRemovePrerequisite extends WWApiBase {
	function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		$prereq = $params['prerequisite'];
		if ($prereq == '') {
			$this->dieUsage(
				'Missing prerequisite for ‘remove prerequisite project’ action.',
				'missingparam'
			);
		}
		$project = $this->getProject( $params['project'] );
		$res = array();
		try {
			unset ( $project->depends_on[$prereq] );
			$wwContext->wwStorage->save_project_description($project);
			$wwContext->wwInterface->invalidate_pages( $project );
			$res['html'] = wwfHtmlPrerequisiteInfo( $project );
		} catch ( WWException $ex ) {
			$this->dieUsage(
				"Error removing prerequisite project.",
				'internalerror',
				0,
				array( 'messages' => $wwContext->wwInterface->report_errors() )
			);
		}
		$this->getResult()->addValue( null, 'ww-remove-prerequisite', $res );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'prerequisite' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'prerequisite' => 'Name of prerequisite project',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Remove a prerequisite from a project';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

abstract class WWCometBase extends WWApiBase {
	# call this at the beginning of execute()
	public function setupOutput() {
		# seize control of the HTTP response and go for it
		# this means no returning to the ApiBase machinery.
		wfResetOutputBuffers();
		@ob_end_clean(); // just to make sure.
		header( 'Content-type: text/event-stream' );
		header( 'Cache-Control: no-cache, no-store, max-age=0, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Connection: close' );
	}

	# call to send an error to client, if output hasn't started yet
	public function quitEarly( $text ) {
		header( 'HTTP/1.0 500 Internal Error: '. $text );
		$this->finishOutput();
	}

	# call this at the end of execute()
	public function finishOutput() {
		exit(0);
	}
	# used by subclass below
	var $position;
	public function update( $data ) {
		echo "event: update\n";
		if ( ! isset( $this->position ) ) {
			$this->position = 0;
		}
		$from = $this->position;
		$to = $this->position += strlen($data);
		echo "data: $from; $to; " . str_replace("\n", "\ndata: ", $data)."\n";
		echo "\n";
		@ob_flush();
		@ob_end_flush();
		flush();
	}
}

class WWApiLongJobTest extends WWCometBase {
	var $status_file_path;
	var $handle;
	var $counter;

	public function execute() {
		$params = $this->extractRequestParams();

		$this->setupOutput();
		global $wwStatusFileDirectory;
		$this->status_file_path = tempnam( $wwStatusFileDirectory, 'WW-statusfile-' );
		$this->handle = fopen( $this->status_file_path, 'w' );
		if ( ! $this->handle ) {
			$this->quitEarly( 'Could not open status file for writing' );
		}
		$key = preg_replace( "[.*/(WW-statusfile-)?]", '', $this->status_file_path );
		wwLog( "LongJob, {$this->status_file_path}" );

		# just send the key to the Comet client, it should be enough -
		# it'll close the connection and start following the status file using 
		# the key.
		echo "event: key\n";
		echo "data: $key\n";
		echo "\n";
		@ob_end_flush();
		@ob_flush();
		flush();

		for ( $i = 0; $i < 100; $i++ ) { 
			fwrite( $this->handle, "Y $i\n" );
			fflush( $this->handle );
			$dhandle = dio_open( $this->status_file_path, O_RDWR, 0600 );
			if (dio_fcntl($dhandle, F_SETLK, Array('type'=>F_WRLCK)) !== 0) {
				wwLog( "failed to lock" );
			}
			dio_close( $dhandle );
			wwLog( "$i" );
			usleep(500000);
			//sleep(1);
		}
		fclose($this->handle);
		# tempnam creates it with mode 0600, here we change to 0400 
		# to signal that we're done writing
		chmod( $this->status_file_path, 0400 );
		# race - once I chmod, the other process will unlink the file
		if ( $dhandle = dio_open( $this->status_file_path, O_RDONLY, 0600 ) ) {
			if (dio_fcntl($dhandle, F_SETLK, Array('type'=>F_RDLCK)) !== 0) {
				wwLog( "failed to lock" );
				dio_close( $dhandle );
			}
		}
		$this->finishOutput();
	}

	public function getAllowedParams() {
		return array(
		);
	}

	public function getParamDescription() {
		return array(
		);
	}

	public function getDescription() {
		return 'Fool around with status files, for testing purposes';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiCometTest extends WWCometBase {
	public function execute() {
		$params = $this->extractRequestParams();
		wwLog( "WWApiCometTest : " . serialize($params) );

		$key = $params['key'];
		$from = $params['from'];

		# and out goes the stream of events.
		#echo "retry: 5000\n";
		#echo "\n";
		global $wwStatusFileDirectory;
		$status_file_name = "$wwStatusFileDirectory/WW-statusfile-$key";
		$this->setupOutput();
		$done = false;
		if ( ! file_exists( $status_file_name ) ) {
			# patience at first - it might not appear yet
			sleep(1);
		}
		while( ! $done ) {
			if ( ! file_exists( $status_file_name ) ) {
				wwLog( "not found: $status_file_name" );
				# what will this do to a comet client?
				echo "event: done\n";
				echo "data: \n";
				echo "\n";
				break;
			}
			//wwLog( getmypid() . ' file_get_contents on ' . $status_file_name );
			$data = file_get_contents( $status_file_name, false, null, $from, 8192 );
			wwLog( strlen($data) . ' bytes' );
			if ( strlen( $data ) > 0 ) {
				$this->update( $data );
				wwLog( "$data" );
				$from += strlen( $data );
			}
			clearstatcache( $status_file_name );
			$stat = stat( $status_file_name );
			//wwLog( 'mode '. decoct($stat['mode'] & 0600) );
			if ( ( $stat['mode'] & 0600 ) != 0600 ) { 
				//wwLog( "$status_file_name is done: " . serialize( $stat ) );
				//wwLog( 'done' );
				echo "event: done\n"; 
				echo "data: \n";
				echo "\n";
				$done = true;
				unlink( $status_file_name );
			}
			# connection_aborted() will either return false or not return
			if ( ! connection_aborted() and ! $done and strlen($data) < 8192 ) {
				//sleep(1);
				usleep(300000);
			}
		}
		$this->finishOutput();
	}

	public function getAllowedParams() {
		return array(
			'key' => array( 
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
			'from' => array(
				ApiBase::PARAM_TYPE => 'integer',
				#ApiBase::PARAM_REQUIRED => false
			),
		);
	}

	public function getParamDescription() {
		return array(
			'key' => 'Key returned by the other test action',
			'from' => 'Starting byte index',
		);
	}

	public function getDescription() {
		return 'Send a bunch of bogus events, as a test of Comet updating.';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

class WWApiPassToPE extends WWApiBase {
	public function execute() {
		global $wwContext;
		$params = $this->extractRequestParams();
		wwLog("WWApiPassToPe, params is " . json_encode($params) . "\n");

		$this->claimSSEkey( $params['logkey'] );

		$request = json_decode( $params['request'], true );
		$result = null;
		try {
			$result = ProjectEngineConnection::call_project_engine_lowlevel(
				$request,
				false
			);
			$wwContext->wwStorage->update_archived_project_files();
			$success = $result['succeeded'];
		} catch ( WWException $ex ) {
			$success = false;
		}
		$messages = $wwContext->wwInterface->report_errors();
		if ( ! $success ) {
			$this->dieUsage(
				'ProjectEngine operation failed',
				'pefailed',
				0,
				array( 'messages' => $messages )
			);
		}

		$res = array(
			'result' => $result,
		);
		if ( $messages ) {
			$res['messages'] = $messages;
		}
		$this->getResult()->addValue( null, 'ww-pass-to-pe', $res );
		$this->closeSSE();
	}

	public function getAllowedParams() {
		return array(
			'request' => array(
				ApiBase::PARAM_TYPE => 'string',
				#ApiBase::PARAM_REQUIRED => false
			),
		) + parent::getAllowedParams();
	}

	public function getParamDescription() {
		return array(
			'request' => 'JSON-encoded request data structure for ProjectEngine',
		) + parent::getParamDescription();
	}

	public function getDescription() {
		return 'Pass a request verbatim to the wiki\'s ProjectEngine instance and return the result';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.	By Lee Worden.)';
	}
}

?>
