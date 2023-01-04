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

global $wwExtensionDirectory;
# Why is this needed here??
require_once("$wwExtensionDirectory/misc.php");

# If MultiUpload is not installed, use this shim class to deliver an orderly
# error message
if ( ! isset( $wgAutoloadClasses['SpecialMultiUpload'] ) ) {
	class SpecialMultiUpload extends SpecialPage {
		function __construct() {
			parent::__construct( 'ImportProjectFiles' );
		}

		function execute( $par ) {
			$this->setHeaders();
			$output = $this->getOutput();
			$output->addWikiText( wfMessage( 'ww-importprojectfiles-multiupload-missing' )->text() );
		}
	};
	class MultiUploadForm {};
	class UploadRow {};
	class UploadFormRow {};
}

/* 
 * There are four classes in this file (unless this comment is out of date):
 * 
 * SpecialImportProjectFiles - subclass of SpecialPage, which instantiates 
 *	 the Special:ImportProjectFiles page
 * ImportProjectFilesForm - subclass of MultiUploadForm, to create the
 *	 form that collects the submissions.
 * ImportProjectFilesRow - a subclass of UploadRow, which does the
 *	 processing for one project file.
 * ImportProjectFilesFormRow - a subclass of UploadFormRow, which does the
 *	 interface for importing one file.
 */

/**
 * Special page class
 */
class SpecialImportProjectFiles extends SpecialMultiUpload {
       	var $mForm;
	var $mFrom, $mTo;
	var $mDefaultProjectName;
	var $importer;

	function __construct() {
	       	SpecialPage::__construct( 'ImportProjectFiles', 'upload' );
		$this->importer = new ImportQueue;
		// TODO need to adjust the target of the 'Special page' tab?
	}

	protected function handleRequestData() {
		global $wwContext;
		$this->mDefaultProjectName = $this->getRequest()->getText( 'wpDefaultProject' );
		if ( ! $this->mDefaultProjectName ) {
			# "&project=" is expected URL convention
			$this->mDefaultProjectName = $this->getRequest()->getText( 'project' );
		}
		parent::handleRequestData();
		$this->importer->commit();
		$wwContext->wwInterface->save_modified_projects();
		$this->getOutput()->addHTML( wwfSanitizeForSpecialPage(
			$wwContext->wwInterface->report_errors()
		) );
	}

	protected function createRow ( $i ) {
		return new ImportProjectFilesRow( $this, $i );
	}

	protected function getUploadForm( $message = '', $sessionKey = '', $hideIgnoreWarning = false ) {
		try { 
			$form = new ImportProjectFilesForm( $this, $this->mRows, $this->mTo, $this->getContext() );
		} catch ( PEException $ex )
		{ } # let exceptions be, they'll be reported later.
		if ( method_exists( $this, 'getPageTitle' ) ) {
			$form->setTitle( $this->getPageTitle() );
		} else {
			$form->setTitle( $this->getTitle() );
		}

		if ( ! $this->mTokenOk && $this->getRequest()->wasPosted() ) {
			$form->addPreText( 
				$this->msg( 'session_fail_preview' )->parse() .
				'<hr/>' 
			);
		}
		$form->addPreText( $this->msg( 'ww-importprojectfiles-top' )->parse() );
		return $form;
	}

	public function getGlobalFormDescriptors() {
		return parent::getGlobalFormDescriptors()
			+ array( 'DefaultProject' => 
				array(
					'type' => 'hidden',
					'id' => 'wpDefaultProject',
					'default' => $this->mDefaultProjectName,
				),
			);
	}
};

class ImportProjectFilesForm extends MultiUploadForm { 
	var $mUnpackDir;

	protected function addJsConfigVars( $out ) {
		parent::addJsConfigVars( $out );
		global $wgLang, $wwImportAsImageByDefault,
			$wwImportImageExtensions, $wwImportTextExtensions;
		$jsvars = array(
			'wgImageNamespace' => $wgLang->getNsText( NS_FILE ),
		);
		# just provide one or the other, the JS will understand
		if ( $wwImportAsImageByDefault ) {
			$jsvars['wwImportTextExtensions'] = 
				array_values( $wwImportTextExtensions );
		} else {
			$jsvars['wwImportImageExtensions'] = 
				array_values( $wwImportImageExtensions );
		}
		$out->addJsConfigVars( $jsvars );
	}

	protected function addRLModules( $out ) {
		$out->addModules( array(
			'ext.workingwiki.importprojectfiles',
			'ext.multiupload.top', # needs to be invoked directly :(
		) );
	}

	# TODO customize the warnings in both JS and PHP about allowed
	# extensions
}

class ImportProjectFilesRow extends UploadRow { 
	# variables corresponding to form fields
	var $mProjFilename;
	var $mDestPage;
	var $mDestType;
	var $mProjectName;
	var $mOrigFilename;
	var $mProjFileTouched;
	var $mDestPageTouched;
	var $mDestTypeTouched;
	# TODO: custom tag attributes

	# variables for internal use
	var $mDestTitle;
	var $mProject;
	var $mTempPath;
	var $mFileText;

	public function __construct( $page, $i ) { 
		global $wwContext;
	  
		parent::__construct( $page, $i );
		$request = $this->getRequest();
		$this->mProjFilename 
			= $request->getText( "wpProjFilename" );
		$this->mProjFileTouched 
			= $request->getBool("wpProjFilenameTouched",  false );
		$this->mDestType = $request->getVal( "wpDestType" );
		$this->mDestTypeTouched
			= $request->getBool( 'wpDestTypeTouched', false );
		if ($this->mDestType == '') {
			$this->mDestType = 'source';
		}
		$this->mDestPage = $request->getText( "wpDestPage" );
		$this->mDestPageTouched 
			= $request->getBool( "wpDestPageTouched", false );
		$this->mOrigFilename 
			= $request->getText( "wpOrigFilename" );
		if ($this->mOrigFilename == '') {
			$this->mOrigFilename = $this->mProjFilename;
		}
		$this->mProjectName = $request->getText( 'wpProjectName',
		       $this->mPage->mDefaultProjectName );
		if ($this->mProjectName != '') {
		       	try { 
				$this->mProjectName 
				  = ProjectDescription::normalized_project_name($this->mProjectName);
			} catch (WWException $ex) {
			       	$this->mProjectName = '(invalid project name)';
			}
			$wwContext->wwInterface->project_is_in_use( $this->mProjectName );
		}
		$this->mProject = null;
	}

	function project() {
		global $wwContext;
	       	if ($this->mProjectName == '') {
			return null;
		}
		if (is_null($this->mProject)) {
			try { 
				$this->mProject = $wwContext->wwStorage->find_project_by_name($this->mProjectName);
				if (is_null($this->mProject)) {
					$this->mProject = $wwContext->wwInterface->create_empty_project($this->mProjectName);
				}
			}
			catch( PEException $ex)
			{}
		}
		return $this->mProject;
	}

	/**
	 * getRequest() uses MultiUpload's technique of recasting the row's
	 * fields as global fields (without an appended index).  In IPF we
	 * also have to provide a wpDestFile value, because UploadFromFile
	 * needs to believe it's going to upload to a valid File: page.
	 *
	 * TODO does this crash if wpDestPage is ''?
	 */
	public function getRequest() {
		$request = parent::getRequest();
		$fakeDestPage = $request->getVal( 'wpDestPage' );
		$request->setVal( 'wpDestFile', $fakeDestPage );
		return $request;
	}

	protected function handleRequestData() {
		global $wwContext;
		parent::handleRequestData();
		$this->mFormMessage .= wwfSanitizeForSpecialPage(
			$wwContext->wwInterface->report_errors()
		);
	}

	protected function createFormRow() {
		return new ImportProjectFilesFormRow( $this,
			$this->getFormOptions( $this->mSessionKey,
				$this->mHideIgnoreWarning ),
			$this->getContext() );
	}

	protected function getFormOptions( $sessionKey = '', $hideIgnoreWarning = false ) {
		return parent::getFormOptions( $sessionKey, $hideIgnoreWarning )
			+ array(
				'desttype' => $this->mDestType,
				'destfilename' => $this->mProjFilename,
				'destpage' => $this->mDestPage,
				'projectname' => $this->mProjectName,
				'destfilenametouched' => $this->mProjFileTouched,
				'destpagetouched' => $this->mDestPageTouched,
				'desttypetouched' => $this->mDestTypeTouched,
				# custom tag attributes
			);
	}

	protected function getUploadWarning( $warnings ) {
		if ( is_array( $warnings ) ) {
			# 'Filename has been changed' to a different File:
			# name is not warning-worthy in IPF.
			unset( $warnings['badfilename'] );
		}
		return parent::getUploadWarning( $warnings );
	}

	protected function getFileText() {
		if ( $this->mFileText === null and $this->mUpload ) {
			$this->mTempPath = $this->mUpload->getTempPath();
			clearstatcache(); # don't remove this, it seems necessary

			if ( ! file_exists( $this->mTempPath ) ) {
				$repo = RepoGroup::singleton()->getLocalRepo();
				$stash = new UploadStash( $repo );
				#wwLog( 'session key: ' . $this->getRequest()->getText( 'wpSessionKey' ) );
				$metadata = $stash->getMetadata( $this->getRequest()->getText( 'wpSessionKey' ) );
				$file = $repo->getLocalReference( $metadata['us_path'] );
				$this->mTempPath = $file->getPath();
				#wwLog( "new mTempPath: " . $this->mTempPath );
			}
			global $wwMaxInsertFileSize;
			# TODO make sure file size limits are correctly checked
			$filesize = filesize( $this->mTempPath );
			if ( $filesize === false ) {
				$this->showUploadError(
					$this->msg('ww-importprojectfiles-bad-filesize')->parse() );
				return null;
			}
			if ( $filesize === 0 ) {
				$this->showUploadError( 
					$this->msg( 'ww-importprojectfiles-empty-file' )->escaped() );
				return null;
				//$warnings['emptyfile'] = array();
			}
			if ( $filesize > $wwMaxInsertFileSize ) {
				$this->showUploadError(
					$this->msg('ww-importprojectfiles-file-too-large') );
				return null;
			}
			$this->mFileText = file_get_contents( $this->mTempPath );
			$this->mFileModTime = filemtime( $this->mTempPath );

			if ( $this->mFileText === false ) {
				$this->showUploadError( 
					$this->msg('ww-importprojectfiles-missing-file', $srcPath )->parse() );
				return null;
			}
		}
		return $this->mFileText;
	}

	function shouldProcessUpload() {
		$spu = ( !$this->mUploadSuccessful &&
		         $this->mPage->mTokenOk && !$this->mCancelUpload &&
		         $this->mUploadClicked &&
		         $this->mUpload->getTempPath() != '' );
		return $spu;
	}

	# now we do uploading.	If the destination is a File: page (aka
	# Image: or Media:), pass to the usual upload process.	If not,
	# we do our own version of 'uploading' file content into source-file
	# tags.
	function processUpload() {
		if ( !ProjectDescription::is_allowable_filename($this->mProjFilename) ) {
			$this->showRecoverableUploadError(
				$this->msg( 'ww-importprojectfiles-bad-dest-filename',
		       		$this->mProjFilename )->escaped() );
			return;
		}
		if ( $this->mProjectName !== '' and
		     ($this->mProject = $this->project()) == null ) {
			$this->showRecoverableUploadError(
				$this->msg( 'ww-importprojectfiles-bad-projectname',
		       		htmlspecialchars( $this->mProjectName ) )->text() );
			return;
		}

		$this->mDestTitle = Title::newFromText( $this->mDestPage );
		if ( ! $this->mDestTitle instanceOf Title ) {
			if ($this->mDestType == 'project') {
				$dpns = '';
			} else {
				$this->showUploadError(
					$this->msg( 'ww-importprojectfiles-bad-dest-page',
					$this->mDestPage )->parse() );
				return;
			}
		} else {
			$dpns = $this->mDestTitle->getNamespace();
		}
		# let UploadFormRow::internalProcessUpload go ahead if it's a File page.
		if ($dpns == NS_IMAGE or $dpns == NS_MEDIA) {
		       	$this->mDesiredDestName = $this->mDestTitle->getDBKey();
			#wwLog( "do standard upload of $this->mProjFilename to $this->mDestPage" );
			parent::processUpload();
		} else {
			$this->processUploadToPage();
		}
	}

	protected function processUploadToPage() {
                // Fetch the file if required
                $status = $this->mUpload->fetchFile();
                if ( !$status->isOK() ) {
                        $this->showUploadError( $this->getOutput()->parse( $status->getWikiText() ) );
                        return;
                }

		# TODO check for permission to do the deed
		# if needed beyond what ImportQueue does

		# otherwise! do the weird text page stuff!

		if ( $this->getFileText() === null ) {
			// it does its own error reporting
			//$this->showUploadError( $this->msg( 'ww-importprojectfiles-missing-file' )->escaped() );
			return;
		}
		#wwLog( "do WW import of {$this->mTempPath} to $this->mDestPage" );

		/**
		 * Check for non-fatal conditions
		 */
		if ( ! $this->mIgnoreWarning ) {
			$warnings = array();

			# warn if user appears to be uploading an image 
			# or binary file into a text page
			# we call it binary if it contains '\0' in the first
			# 1000 characters
			$filename_parts = explode( '.', $this->mProjFilename );
			$ext = end($filename_parts);
			global $wwImageExtensions, $wwImportImageExtensions;
			if ( $this->mDestType != 'project' and
			    ( in_array($ext, $wwImageExtensions)
			      or in_array($ext, $wwImportImageExtensions)
			      or strpos(substr($this->mFileText,0,1000), '\0') !== false ) ) {
				$warnings['ww-importprojectfiles-image-to-text-page'] = array( "[[{$this->mDestPage}]]" );
			}

			/* TODO warn if duplicating another row's file
			if ( !is_null($this->mSameDestAs)) {
				// TODO wfMsgExt?
			       	$warnings['duplicate-destination'] = array( $this->mSameDestAs );
			}
			if ( !is_null($this->mSameHashAs) ) {
			       	$warnings['duplicate-upload'] = array( $this->mSameHashAs );
			} */

			/* TODO warn if project has file stored in a different place */
			/* TODO warn if file is already in project by a different name */
			/* TODO and if we're changing a source file to a project file, etc. */
			/* TODO warn if file will be unchanged */

			if( count($warnings) > 0 ) {
				$this->showUploadWarning( $warnings );
				return;
			}
		}

		# tell importer to put the file in the wiki
		if ( $this->mDestType == 'source'
		    or $this->mDestType == 'archived' ) {
			if ( !$this->mPage->importer->insert_file_element(
					($this->mDestType == 'source'), 
					$this->mProjFilename, 
					$this->mProjectName, 
					$this->mDestTitle->getPrefixedDBKey(), 
					$this->mFileText ) ) {
				$this->showUploadError( 
				    $this->msg('ww-importprojectfiles-internal-error')->parse() );
				return;
			}
		}

		$this->mUploadSuccessful = true;
		$this->uploadSucceeded();
	}

	protected function uploadSucceeded() {
		global $wwContext;
		#wwLog("uploadSucceed row {$this->mRowNumber}\n");
		if ($this->mProject !== null ) {
			# add file to project description
			if ($this->mDestType == 'source') {
				$this->mProject->add_source_file( array(
					'filename' => $this->mProjFilename,
					'page' => $this->mDestPage,
				) );
				$wwContext->wwInterface->project_is_modified( $this->mProjectName );
			} else if ($this->mDestType == 'archived') {
				$this->mProject->add_project_file( array(
					'filename' => $this->mProjFilename,
					'archived' => array( $this->mDestPage => true ),
				) );
				$wwContext->wwInterface->project_is_modified( $this->mProjectName );
			}
			# sync the file into the working directory
			try {
				# by the time we get here, $this->mTempPath is
				# obsolete
				if ( $this->mFileText === null ) {
					$file = wfLocalFile( $this->mDestTitle );
					$sfe = ProjectEngineConnection::make_sync_file_entry(
						$file->getLocalRefPath(),
						null,
						null );
				} else {
					$sfe = ProjectEngineConnection::make_sync_file_entry(
						null,
						$this->mFileText,
						$this->mFileModTime );
				}
				$op_result = ProjectEngineConnection::call_project_engine(
					'force-sync', 
					$this->project(),
					array('target'=>''),
					array(
					    'projects' => array(
					        $this->mProject->project_uri() => array(
						    'source-file-contents' => array(
						        $this->mProjFilename => $sfe
						    )
					        )
					    )
					) 
				);
			} catch ( WWException $ex )
			{ }
		}
	}
};

class ImportProjectFilesFormRow extends UploadFormRow {
	var $mProjectName;
	var $mProjFilename;
	var $mDestPage;
	var $mDestType;
	var $mProjFileTouched;
	var $mDestPageTouched;
	var $mDestTypeTouched;

	protected function constructData(array $options = array(), IContextSource $context = null ) {
		parent::constructData( $options, $context );
		$this->mProjectName = $options['projectname'];
		$this->mDestType = $options['desttype']; 
		$this->mProjFilename = $options['destfilename']; 
		$this->mDestPage = $options['destpage']; 
		$this->mProjectName = $options['projectname']; 
		$this->mDestTypeTouched = $options['desttypetouched'];
		$this->mProjFileTouched = $options['destfilenametouched']; 
		$this->mDestPageTouched = $options['destpagetouched']; 
	}

	# different success message
	protected function uploadedMessage() {
		global $wwContext;
		$project = $this->mRow->project();
		if ( $project !== null ) {
			$projlink = $wwContext->wwInterface->make_manage_project_link( $project );
			$projmsg = $this->msg( 'ww-importprojectfiles-in-project', $projlink )->text();
			$pflink = '<a href="'
				 . $wwContext->wwInterface->make_get_project_file_url(
					 $project, $this->mProjFilename, /*make*/false )
				 . '">' 
				 . htmlspecialchars( $this->mProjFilename )
				 . '</a>';
		} else {
			$projmsg = $this->msg( 'ww-importprojectfiles-in-no-project' )->text();
			$pflink = htmlspecialchars( $this->mProjFilename );
		}
		if ( $this->mDestType == 'project' ) {
			return '<div class="multiupload-success-message">'
				. wfMessage( 'ww-importprojectfiles-succeeded-project',
					$projlink, $pflink )->text()
				. '</div>';
		} else if ( $this->mDestType == 'archived' ) {
			return '<div class="multiupload-success-message">'
				. wfMessage( 'ww-importprojectfiles-succeeded-archived',
					$pflink,
					Linker::linkKnown( Title::newFromText(
						  $this->mDestPage ),
						$this->mDestPage ) )->text()
				. $this->msg( 'word-separator' )->escaped()
				. $projmsg
				. '</div>';
		} else { // source
			return '<div class="multiupload-success-message">'
				. wfMessage( 'ww-importprojectfiles-succeeded-source',
					$pflink,
					Linker::linkKnown( Title::newFromText(
						  $this->mDestPage ),
						$this->mDestPage ) )->text()
				. $this->msg( 'word-separator' )->escaped()
				. $projmsg
				. '</div>';
		}
	}

	# extra hidden fields in case of success
	protected function uploadSucceededDescriptor( $i, $sectionlabel ) {
		$d = parent::uploadSucceededDescriptor( $i, $sectionlabel );
		unset( $d['DestFile'.$i] );
		return $d + array(
			'DestPage'.$i => array(
				'type' => 'hidden',
				'default' => $this->mDestPage,
				'section' => $sectionlabel ),
			'ProjFilename'.$i => array(
				'type' => 'hidden',
				'default' => $this->mProjFilename,
				'section' => $sectionlabel ),
			'ProjectName'.$i => array(
				'type' => 'hidden',
				'default' => $this->mProjectName,
				'section' => $sectionlabel ),
		);
	}

	# small adjustment to the source section
	protected function getSourceSection() {
		$ss = parent::getSourceSection();
		# customize the file-size messages
		global $wwMaxInsertFileSize;
		$this->mMaxUploadSize['tag'] = $wwMaxInsertFileSize;
		if ( ! ( ( function_exists('wfIsHipHop') and wfIsHipHop() )
			|| ( function_exists('wfIsHHVM()') and wfIsHHVM() ) )
		) {
			$this->mMaxUploadSize['tag'] = min( 
				$this->mMaxUploadSize['tag'],
				wfShorthandToInteger( ini_get( 'upload_max_filesize' ) ),
				wfShorthandToInteger( ini_get( 'post_max_size' ) )
			);
		}
		if ( isset($ss['UploadFile']) ) {
			$ss['UploadFile']['help'] .= '<br/>' .
				$this->msg( 'ww-importprojectfiles-maxfilesize',
					$this->getContext()->getLanguage()->formatSize( 
						min( $this->mMaxUploadSize['tag'],
						     $this->mMaxUploadSize['file'] ) )
				)->parse();
		}
		if ( isset($ss['UploadFileURL']) ) {
			$ss['UploadFileURL']['help'] .= '<br/>' .
				$this->msg( 'ww-importprojectfiles-maxfilesize',
					$this->getContext()->getLanguage()->formatSize( 
						min( $this->mMaxUploadSize['tag'],
						     $this->mMaxUploadSize['url'] ) )
				)->parse();
		}
    		# make the file-types messages disappear when row is collapsed
		if ( isset($ss['Extensions']) ) {
			$ss['Extensions']['cssclass'] = 'multiupload-first-to-collapse';
		}
		return $ss;
	}

	# completely replace the description section
	protected function getDescriptionSection() {
		return array(
			'ProjFilename' => array(
				'type' => 'text',
				'id' => 'wpProjFilename',
				'label-message' => 'ww-importprojectfiles-project-filename',
				'cssclass' => 'multiupload-first-to-collapse ww-width-exemplar',
				'size' => 60,
				'default' => $this->mProjFilename,
				# see SpecialUpload.php.  hack for empty value
				'nodata' => strval( $this->mProjFilename ) !== '',
				'help' => $this->msg( 'ww-importprojectfiles-project-filename-help' ),
			),
			'ProjectName' => array(
				'type' => 'text',
				'id' => 'wpProjectName',
				'label-message' => 'ww-importprojectfiles-project-name',
				'size' => 60,
				'default' => $this->mProjectName,
				'nodata' => strval( $this->mProjFilename ) !== '',
				'help' => $this->msg( 'ww-importprojectfiles-project-name-help' ),
			),
			'DestType' => array(
				'type' => 'select',
				'id' => 'wpDestType',
				'label-message' => 'ww-importprojectfiles-desttype',
				'options' => array(
					$this->msg( 'ww-importprojectfiles-desttype-source' )->parse() => 'source',
					$this->msg( 'ww-importprojectfiles-desttype-project' )->parse() => 'project',
					$this->msg( 'ww-importprojectfiles-desttype-archived' )->parse() => 'archived',
				),
				'default' => 'source',
				'help' => $this->msg( 'ww-importprojectfiles-desttype-help' ),
			),
			'DestPage' => array(
				'type' => 'text',
				'id' => 'wpDestPage',
				'label-message' => 'ww-importprojectfiles-destpage',
				'size' => 60,
				'default' => $this->mDestPage,
				'nodata' => strval( $this->mDestPage ) !== '',
				'help' => $this->msg( 'ww-importprojectfiles-destpage-help' ),
			),
			'DestTypeTouched' => array(
				'type' => 'hidden',
				'id' => 'wpDestTypeTouched',
				'default' => $this->mDestTypeTouched,
			),
			'DestPageTouched' => array(
				'type' => 'hidden',
				'id' => 'wpDestPageTouched',
				'default' => $this->mDestPageTouched,
			),
			'ProjFilenameTouched' => array(
				'type' => 'hidden',
				'id' => 'wpProjFilenameTouched',
				'default' => $this->mProjFileTouched,
			),
		);
	}


	# Options section is as is but minus "Watch this file"
	protected function getOptionsSection() {
		$opts = parent::getOptionsSection();
		unset( $opts['Watchthis'] );
		return $opts;
	}

	# Message is different from regular upload interface
	protected function getExtensionsMessage() {
                global $wgCheckFileExtensions, $wgStrictFileExtensions,
                $wgFileExtensions, $wgFileBlacklist;

                if ( $wgCheckFileExtensions ) {
                        if ( $wgStrictFileExtensions ) {
                                # Everything not permitted is banned
                                $extensionsList =
                                        '<div id="mw-upload-permitted">' .
                                        $this->msg( 'ww-importprojectfiles-file-upload-permitted', $this->getContext()->getLanguage()->commaList( $wgFileExtensions ) )->parseAsBlock() .
                                        "</div>\n";
                        } else {
				# Don't include the preferred, it's confusing because
				# users will think it's about how it chooses text vs. file destinations
                                $extensionsList =
                                        #'<div id="mw-upload-preferred">' .
                                        #        $this->msg( 'ww-importprojectfiles-file-upload-preferred', $this->getContext()->getLanguage()->commaList( $wgFileExtensions ) )->parseAsBlock() .
                                        #"</div>\n" .
                                        '<div id="mw-upload-prohibited">' .
                                                $this->msg( 'ww-importprojectfiles-file-upload-prohibited', $this->getContext()->getLanguage()->commaList( $wgFileBlacklist ) )->parseAsBlock() .
                                        "</div>\n";
                        }
                } else {
                        # Everything is permitted.
                        $extensionsList = '';
                }
                return $extensionsList;
        }

};

