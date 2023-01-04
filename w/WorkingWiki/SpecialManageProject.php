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

/*
 * implementation for Special:ManageProject
 *
 * This Special page is the interface for inspecting and modifying
 * a WorkingWiki project, which previously could only be done
 * by hand-editing a <project-description> XML element.
 *
 * usage: Special:ManageProject?project="projectname"
 */
//define( 'MW_NO_OUTPUT_COMPRESSION', 1 );

$this_dir = dirname(__FILE__);
require_once($this_dir."/WWInterface.php");

global $IP;
require_once( "$IP/includes/StreamFile.php" );

class SpecialManageProject extends SpecialPage
{
  function __construct()
  { parent::__construct('ManageProject');
    $this->mIncludable = false;
    $this->mListed = false;
  }

  function execute( $par )
  { global $wwContext;
    //$wwContext->wwInterface->include_css('manage-project',null);

    if ( method_exists($this, 'getOutput') )
    { $out = $this->getOutput();
      $request = $this->getRequest();
      $user = $this->getUser();
    }
    else
    { global $wgRequest, $wgOut, $wgUser;
      $out = $wgOut;
      $request = $wgRequest;
      $user = $wgUser;
    }
    $out->addModules( array(
	    'ext.workingwiki.manageproject',
	    'ext.workingwiki.manageproject.top',
    ) );

    $projectname = $request->getText('project', '');

    if ($projectname === '')
    { $out->showFatalError( 
        "Special:ManageProject requires a project name to be specified.");
      return false;
    }

    try
    { $projectname = ProjectDescription::normalized_project_name($projectname);
    } catch ( WWException $ex )
    { $out->showFatalError( "Error interpreting project name '" 
        . htmlentities($projectname) . "'." );
    }
    try
    { list($xml, $page) = $wwContext->wwStorage->find_project_description_by_name( $projectname );
      if ( ! $xml )
      { $wwContext->wwInterface->record_message( "Project \"" . htmlspecialchars($projectname)
          . "\" does not exist." );
      }
      $project = $wwContext->wwStorage->find_project_by_name( $projectname );
      $wwContext->wwInterface->default_project_name = $project->project_name();
      wwLog( "default_project_name: " . $wwContext->wwInterface->default_project_name );
    } catch ( WWException $ex )
    { $project = null; 
    }

    $page_label = $projectname;
    $out->setHTMLTitle($wwContext->wwInterface->message('manageproject',$page_label));
    $this->setHeaders();
    if (method_exists($this,'outputHeader'))
    { $this->outputHeader();
    }
    $out->setPageTitle($wwContext->wwInterface->message('manageproject',$page_label));
    wwRunHooks('WW-ManageProject-Headers', array());

    # This page might be invoked with a 'ww-action' parameter: process it
    # now.
    $action_result = WWAction::execute_action($request);
    if ( $action_result['status'] == WW_ABORTOUTPUT )
    { return;
    }
    if ( $action_result['status'] != WW_NOACTION )
    { # if any errors have been accumulated yet, report them.
      $out->addHTML( wwfSanitizeForSpecialPage(
        $wwContext->wwInterface->report_errors()
      ) );
      $out->addHTML( $action_result['html'] );
      /* else if ( !$wgUser->matchEditToken( $request->getVal('wpEditToken') ) )
          $out->addHTML( '<p class="ww-mf-error">'
          ."Error: edit token expired - please try again.</p>\n" ); */
      $out->addHTML( "<hr/>\n" );
      # Save the project description if anything has changed
      $wwContext->wwInterface->save_modified_projects();
      # the action might have changed the project description - reload
      unset(ProjectDescription::$project_cache[$project->project_name()]);
      $project = $wwContext->wwStorage->find_project_by_name($projectname);
    }
      # if no action requested, check for missing project files.
    else if ( ! wwfReadOnly() )
    { # this is expensive - when should we do it?
      try {
        $wwContext->wwStorage->check_all_project_files($project);
      } catch( WWException $ex ) {}
      $errhtml = $wwContext->wwInterface->report_errors();
      if ($errhtml != '')
        $out->addHTML( wwfSanitizeForSpecialPage($errhtml) . "\n<hr/>\n" );
    }

    global $wgHooks;
    # set hook function to make the 'special page' tab point where I want it to
    # on Special:ManageProject (for MW<1.18.0)
    $wgHooks['SkinTemplateBuildContentActionUrlsAfterSpecialPage'][]
      = array( $wwContext->wwInterface, 'fix_special_tab_old' );
    # hook for special page tabs in MW>=1.18.0
    $wgHooks['SkinTemplateNavigation::SpecialPage'][]
      = array( $wwContext->wwInterface, 'fix_special_tab' );
    # don't check whether it exists on-wiki, it might be useful to delete
    # the project directory even if it doesn't have a description.
    //$project_exists =
    //  ($project->project_description_page or $project->is_external());
    //if (!$project_exists)
    //{ $ppt = Title::newFromText($project->project_page());
    //  $project_exists = $ppt->exists();
    //}
    //if ($project_exists)
    { $wgHooks['SkinTemplateBuildContentActionUrlsAfterSpecialPage'][]
        = array( $wwContext->wwInterface, 'add_delete_tab_old' );
      $wgHooks['SkinTemplateNavigation::SpecialPage'][]
        = array( $wwContext->wwInterface, 'add_delete_tab' );
    }

    $wwContext->wwInterface->project_is_in_use( $project->project_name() );
    try {
      wwRunHooks('WW-BeforeManageProject', array(&$this,&$project,''));
    }
    catch( WWException $ex )
    { $errhtml = $wwContext->wwInterface->report_errors();
      if ($errhtml != '')
        $out->addHTML( wwfSanitizeForSpecialPage($errhtml) . "\n<hr/>\n" );
    }

    # display the forms
    $this->showForm( $project, $out, $user, $request );

    return true;
  }

  # create all the lists of files, action buttons, etc.
  # it's actually multiple forms.
  function showForm( &$project, &$out, &$user, &$request )
  { global $wwContext, $wwExtensionDirectory;

    $optionaljs = "wgDestPageLocations = "
      . wwfMakeJSONArray(wwfMakePageLocations($project)) . "\n";
    wwfIncludeSuggestJS($optionaljs);
    //$wwContext->wwInterface->include_js('manageproject', null);

    $readonly = wwfReadOnly();

    $source_files = $appearances = $archived = array();
    foreach ( $project->project_files as $pf ) {
      if ( isset($pf['source']) and $pf['source'] )
      { if ( isset ($pf['automatic']) )
          $autosf = $pf;
        else
          $source_files[] = $pf;
      }
      if ( array_key_exists('appears',$pf) and is_array($pf['appears'])
          and count($pf['appears']) > 0)
        $appearances[] = $pf;
      if ( array_key_exists('archived',$pf) and is_array($pf['archived'])
          and count($pf['archived']) > 0)
        $archived[] = $pf;
    }
    if (isset($autosf))
      $source_files[] = $autosf;

    try
    { $editToken = $user->editToken();

      $hiddeninputs = '';
      if ( ($titlearg = $request->getVal('title')) )
        $hiddeninputs .= wwfHidden( 'title', $titlearg );
      $hiddeninputs .= 
        wwfHidden( 'project', $project->project_name(), 
                     array( 'id'=>'ww-project' ) );
      wwRunHooks('WW-HiddenActionInputs', array(&$hiddeninputs));
    } catch ( WWException $ex )
    { }
    $out->addHTML( wwfSanitizeForSpecialPage(
      $wwContext->wwInterface->report_errors()
    ) );

    $out->addHTML( '<p class="ww-mp-action-links">' );
    $output_state = 'action-links';
    try
    { $out->addHTML(
        $user->getSkin()->makeLinkObj( 
          SpecialPage::getTitleFor( 'GetProjectFile' ),
          $wwContext->wwInterface->message( 'ww-listwd' ), 
          $wwContext->wwInterface->get_project_file_query($project,'.','',false,null,false),
          '') );
        //$user->getSkin()->link( 
        //  SpecialPage::getTitleFor( 'GetProjectFile' ),
        //  $wwContext->wwInterface->message( 'ww-listwd' ), array(),
        //  array( 'project' => $project->project_name(), 'filename' => '.' ),
        //  array('known') ) );
      
      $link_page = (!$project->project_description_page and 
        !$project->is_external());
      if ($link_page)
      { $pp = $project->project_page();
        if ( $pp ) {
          $ppt = Title::newFromText($pp);
          $link_page = $ppt->exists();
	} else {
	  $link_page = false;
	}
        //$wwContext->wwInterface->debug_message( "PD page does not exist and project page "
        //  . ($link_page ? "exists" : "does not exist") . ".");
      }
      $out->addHTML( ' · ' );
      if ($link_page)
      { $out->addHTML( 
          $user->getSkin()->makeLinkObj($ppt, $wwContext->wwInterface->message( 'ww-projectpage', 
            htmlspecialchars($ppt->getText()) ), '', '') 
          . ' ' . $wwContext->wwInterface->message( 'ww-projectpage-comment' ) );
          //$user->getSkin()->link( 
          //  Title::newFromText( $project->project_name() ),
          //  $wwContext->wwInterface->message( 'ww-projectpage' ), array(), array(), array('known') ) );
      }
      else
      { $pdp = $project->project_description_page;
        if (!$pdp)
          $pdp = 'ProjectDescription:'.$project->project_name();
        $out->addHTML( $user->getSkin()->makeLinkObj( Title::newFromText($pdp),
              $wwContext->wwInterface->message( 'ww-projectdescription' ), '','') );
          //$user->getSkin()->link( 
          //  Title::newFromText( $project->project_description_page ),
          //  $wwContext->wwInterface->message( 'ww-projectdescription' ), 
          //  array(), array(), array('known') ) );
      }

      # FIXME: export source files should be generalized to 
      # "export from repository" as opposed to "export including products"
      if ($project->has_source_files())
      { $out->addHTML( ' · ' );
        $out->addHTML( 
          $user->getSkin()->makeLinkObj( 
            SpecialPage::getTitleFor( 'ManageProject' ),
            $wwContext->wwInterface->message( 'ww-export-sf' ), 
            $wwContext->wwInterface->make_manage_project_query($project,'ww-action=export-sf',
              false) ) );
      }

      $out->addHTML( ' · ' );
      $out->addHTML( 
        $user->getSkin()->makeLinkObj( 
          SpecialPage::getTitleFor( 'ManageProject' ),
          $wwContext->wwInterface->message( 'ww-export-wd' ), 
          $wwContext->wwInterface->make_manage_project_query($project,'ww-action=export-wd',
            false) ) );

      $out->addHTML( "</p>\n" );

      if ( ! $readonly )
      { $out->addHTML( '<p class="ww-mp-action-links">' );
        $out->addHTML( 
	    $wwContext->wwInterface->make_manage_project_link(
		  $project,
		  $wwContext->wwInterface->message( 'ww-cleardirectory' ), 
                  'ww-action=clear-directory&ww-action-project=' . htmlspecialchars( $project->project_name() ),
		  false,
		  false,
		  null,
		  array( 'onClick' => 'wwlink(event)' )
	) );
        if ($wwContext->wwStorage->ok_to_sync_source_files())
        { $out->addHTML( ' · ' );
          $out->addHTML( 
              $wwContext->wwInterface->make_manage_project_link($project,
                  $wwContext->wwInterface->message( 'ww-sync-all' ), 
	          'ww-action=sync-all&ww-action-project=' . htmlspecialchars( $project->project_name() ),
		  false,
	  	  false,
		  null,
		  array( 'onClick' => 'wwlink(event)' )
	   ) );
        }
        # note import project files links ignores preview key, is this good?
        global $wgAutoloadClasses;
        if ( isset( $wgAutoloadClasses['SpecialMultiUpload'] ) ) {
		$out->addHTML( ' · ' );
		$out->addHTML( 
		  $user->getSkin()->makeLinkObj( 
		    SpecialPage::getTitleFor( 'ImportProjectFiles' ),
		    $wwContext->wwInterface->message( 'ww-importfiles' ),
		    'project='.urlencode($project->project_name()), '' ) );
	}

        $out->addHTML( "</p>\n" );
      }
    } catch ( WWException $ex )
    { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors() ) );
    }
    //$out->addHTML( implode(' ',$project->pages_involving_project_files()) );

    if ( count( $source_files ) ) {
      $output_state = 'source-files';
      # pirating style elements from Special:SpecialPages
      $out->wrapWikiMsg( "<h4 class='ww-mpf-h4'>$1</h4>\n",
        "ww-source-files" );

      $sync_ok = $wwContext->wwStorage->ok_to_sync_source_files();

      $out->addHTML( "<table class='ww-managesourcefiles'>\n" );
      $out->addHTML(
        "  <tr><th>" . $wwContext->wwInterface->message('ww-sf-filename') . "</th><th>"
        . $wwContext->wwInterface->message('ww-sf-page')."</th>"
        . ( $readonly ? '':'<th/>' ) . "</tr>\n" );
      $ssf = array();
      foreach ( $source_files as $pf )
      { $filename = $pf['filename'];
        $out->addHTML( "  <tr><td>" );
        try
        { $out->addHTML(
            ($filename == '' ? '(empty)' :
              $user->getSkin()->makeLinkObj( 
                SpecialPage::getTitleFor( 'GetProjectFile' ),
                htmlspecialchars($filename),
                $wwContext->wwInterface->get_project_file_query($project,$filename,'',
                  false,null,false), '' ) ) );
            //  $user->getSkin()->link(
            //    SpecialPage::getTitleFor( 'GetProjectFile' ),
            //    $filename, array(),
            //    array( 'project' => $project->project_name(), 
            //      'filename' => $filename, 'make' => 'false' ),
            //    array('known') ) );
        } catch ( WWException $ex )
        { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors()) );
        }
        $out->addHTML( "</td>\n" );
        $out->addHTML( "      <td class='stretch'>" );
        if (isset($pf['automatic']))
          $out->addHTML( $wwContext->wwInterface->message( 'ww-sf-automatically-generated' ) );
        else
        { try
          { $default_page = false;
            $page = $pf['page'];
            #list($fpage,$text,$modtime) = 
            $sfc = $project->find_source_file_content($filename,$page);
            if ( !$page )
            { $default_page = true;
              @$page = $sfc['page'];
            }
            if ( !$page )
            { $page_a = '';
            }
            else
            { $title = Title::newFromText($page);
              if( is_object($title) and NS_MEDIA == $title->getNamespace() )
                $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
              if (is_object($title) and $title->getNamespace() == NS_IMAGE)
              { $pagelink = $title->getPrefixedDBKey();
                $img = wfFindFile($title);
                if ( !$img or !$img->exists() )
                  $fpage = '';
              }
              else
                $pagelink = $page;
                #$pagelink = "$page#ww-sf-def-$filename";
              $page_a = 
                $wwContext->wwInterface->makeLink($page,$pagelink,$user->getSkin());
              #wwLog("straight from makeLink: $page_a\n");
            }
            if ($default_page and $page_a)
              $page_a .= ' ' . $wwContext->wwInterface->message( 'ww-page-by-default' );
            if ( !isset($sfc['page']) and $filename != '' )
            { $page_a .= ' ' . $wwContext->wwInterface->message( 'ww-sf-missing' );
            }
            if ( $page_a == '' )
              $page_a = '&nbsp;';
            $out->addHTML( $page_a );
          } catch ( WWException $ex )
          { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors() ) );
          }
        }
        $out->addHTML( "</td>\n" );
        if ( ! $readonly )
        { $out->addHTML( "      <td class='ww-actions'>" );
          $out->addHTML( "<form class='ww-inline-form'>"
            . $hiddeninputs
	    . wwfHidden( 'ww-action', 'sync-file' )
            . wwfHidden( 'ww-action-filename', $filename )
	    . wwfHidden( 'ww-action-content', false )
	    . wwfHidden( 'ww-action-project', $project->project_name() )
            . "<input type='submit' name='button' value='sync'"
            . ($sync_ok ? '':" disabled='disabled'")
	    . " title='Overwrite the file contents in the working directory'"
	    . " onClick='wwlink(event)'/>"
            . "</form>" );
          $out->addHTML( "<form class='ww-inline-form'>" 
            . $hiddeninputs
            . wwfHidden( 'ww-action', 'remove-source-file' )
            . wwfHidden( 'ww-action-filename', $filename )
            . wwfHidden( 'ww-action-project', $project->project_name() )
            . wwfHidden( 'ww-action-projwd', '1' )
            . "<input type='submit' name='submit' value='remove'"
            . " title='Remove this file from the project'"
            . (isset($pf['automatic']) ? " disabled='disabled'": '')
	    # don't ajaxify this - to remove the file and reload the listing
	    # works without ajax
	    #. " onClick=\"wwlink(event,{action:'ww-remove-file',project:'"
	    #  . htmlspecialchars($project->project_name())
	    #  . "',filename:'" . htmlspecialchars($filename)
	    #  . "',projwd:1})\""
            . "/></form>");
          global $wgAutoloadClasses;
          if ( isset( $wgAutoloadClasses['SpecialMultiUpload'] ) ) {
		  $out->addHTML("<form class='ww-inline-form' action='"
		    . SpecialPage::getTitleFor('ImportProjectFiles')->getLocalURL()
		    . "'>"
		    . wwfHidden( 'project', $project->project_name() )
		    . wwfHidden( 'wpProjFilename1', $filename )
		    . wwfHidden( 'wpProjFilenameTouched1', 1 )
		    . (isset($page) ? wwfHidden( 'wpDestPage1', $page ) : '')
		    . "<input type='submit' name='submit' value='upload'"
		    . " title='Upload a new version of this file'"
		    . (isset($pf['automatic']) ? " disabled='disabled'": '')
		    . "/></form>");
	  }
          $out->addHTML("</td>\n" );
        }
        $out->addHTML( "  </tr>\n" );
      }
      $out->addHTML( "</table>\n" );
    }

    $details = null;
    if ( ! $readonly and $project->has_source_files() and
	    $project->project_page() and
          wwfOKToEditPage( Title::newFromText($project->project_page()), $details ) )
    { $output_state = 'source-files';
      $out->addHTML( "<form id='add-sf-form'>\n" ); # onSubmit='event.preventDefault();$('#ww-add-sf-button').click();'>\n" );
      $out->addHTML( $hiddeninputs );
      $out->addHTML( wwfHidden( 'ww-action', 'set-source-file-location' ) );
      $out->addHTML( wwfHidden( 'ww-action-project', $project->project_name() ) );
      $out->addHTML( "\n<table class='ww-addsourcefile'><tr>"
        . "<th><label for='ww-add-sf-filename'"
        . "  title='Filename of source file'>".$wwContext->wwInterface->message('ww-add-sf-filename')
        . "</label></th>"
        . "<th><label for='ww-add-sf-page'"
        . " title='Page containing source file'>"
        . $wwContext->wwInterface->message('ww-add-sf-page') . "</label></th>"
        . "<th></th></tr>\n" );
      $out->addHTML( "  <tr><td><input type='text' name='ww-action-filename'"
        . " value='' id='ww-add-sf-filename'"
        . " onchange=\"mw.libs.ext.ww.fillAddPage();\"/></td>\n" );
      $out->addHTML( "      <td><input type='text' name='ww-action-page'"
        . " value='' id='ww-add-sf-page' onClick='wwlink(event)'/></td>\n" );
      $out->addHTML( "      <td><input type='submit' name='button'"
        ." value='set' id='ww-add-sf-button'" # onClick='wwlink(event)'"
        ." title=\"Set a source file's location\"/></td>\n" );
      $out->addHTML( "  </tr>\n</table>\n" );
      $out->addHTML( "</form>\n" );
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

    if ( ! $readonly )
    { global $wgScript;
      $make_hiddens = wwfHidden( 'title', 'Special:ManageProject' )
                  . wwfHidden( 'project', $project->project_name() )
                  . wwfHidden( 'ww-action-project', $project->project_name() );
                  #. wwfHidden( 'make', 'yes' );
      wwRunHooks('WW-HiddenActionInputs',array(&$make_hiddens));
      if ($output_state != 'action-links')
      { $out->addHTML( "<hr/>\n" );
      }
      $output_state = 'make-form';
      $make_form = "<form action='$wgScript' id='make-form'>"
        . $make_hiddens
        . "<table class='ww-make'><tr>"
        . "<th><label for='ww-make-target' title='Target'>"
        . $wwContext->wwInterface->message('ww-make-target')
        . "</label></th>"
        . "<th></th></tr>\n";
      $make_form .= "  <tr><td><input type='text' name='ww-action-filename'"
        . " value='' id='ww-make-target'/></td>\n";
      # There is a terrible hack going on here that shouldn't be tolerated,
      # and won't work once this make button is ajax-ified.  For the moment
      # we get the "background make" button to ajax-ify by appending a 
      # hidden input with 'ww-action=create-background-job'.
      $make_form .= "      <td><input type='submit' name='ww-action'"
        ." value='make' class='ww-make-button'"
        . (wwRunHooks('WW-AllowMakeInSession', array(&$request,&$bgm)) 
          ? '' : " disabled='disabled'")
        ." title='Make a user-specified target within the project'/></td>\n"
        ."  </tr></table></form>\n";
      wwRunHooks('WW-AddToMakeForm', array(&$make_form));
      $out->addHTML($make_form);
    }

    if ( count( $archived ) ) {
      $output_state = 'archived-project-files';
      $out->wrapWikiMsg( "<h4 class='ww-mpf-h4'>$1</h4>\n",
        "ww-project-files-archived" );
      $out->addHTML( "<table class='ww-managearchived'>\n" );
      $out->addHTML( "  <tr><th class='filename'>".$wwContext->wwInterface->message('ww-apf-filename')."</th><th>"
        .$wwContext->wwInterface->message('ww-apf-page')."</th></tr>\n" );
      foreach ( $archived as &$pf )
        if (is_array($pf['archived']))
        { $firstrow = true;
          foreach ($pf['archived'] as $pg=>$t)
          { $out->addHTML( "  <tr><td class='filename'>" );
            try
            { //$logfilename = "{$pf['filename']}.make.log";
              //if ( file_exists( 
              //  "{$project->project_directory()}/$logfilename" ) )
                //$out->addHTML( $wwContext->wwInterface->make_altlinks(
                //  array( 'log' => $project->get_project_file_link($logfilename) ) ) );
              $out->addHTML(
                ($pf['filename'] == '' ? '(empty)' : 
                  $user->getSkin()->makeLinkObj( 
                    SpecialPage::getTitleFor( 'GetProjectFile' ),
                    $pf['filename'], 
                    "project=" . urlencode($project->project_name())
                    . "&filename=" . urlencode($pf['filename'])
                    . "&make=false", '' ) ) );
              //  $user->getSkin()->link(
              //    SpecialPage::getTitleFor( 'GetProjectFile' ),
              //    $pf['filename'], array(),
              //    array( 'project' => $project->project_name(), 
              //      'filename' => $pf['filename'], 'make' => 'false' ),
              //    array('known') ) );
            } catch ( WWException $ex )
            { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors( ) ) );
            }
            $out->addHTML( "</td><td>" );
            try
            { $title = Title::newFromText($pg);
              #if ($title->getNamespace() != NS_IMAGE
              #    and $title->getNamespace() != NS_MEDIA)
              #  $link = "$pg#ww-pf-".$pf['filename'];
              #else
                $link = $pg;
              $a = $wwContext->wwInterface->makeLink($pg, $link, $user->getSkin());
              #wwLog("anchor for apf: $a\n");
              $out->addHTML( $a );
            } catch ( WWException $ex )
            { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors() ) );
            }
            $out->addHTML( "</td>\n" );
            if ( ! $readonly )
            { global $wgScript; 
              $out->addHTML("  <td class='ww-actions'>" );
              if ($firstrow)
                $out->addHTML(
                  "<form action='$wgScript' class='ww-inline-form'>"
                  . wwfHidden( 'title', 'Special:GetProjectFile' )
                  . wwfHidden( 'project', $project->project_name() )
                  . wwfHidden( 'filename', $pf['filename'] )
                  . wwfHidden( 'make', 'yes' )
                  . "<input type='submit' name='submit' value='make'"
                  . (wwRunHooks('WW-AllowMakeInSession', array(&$request,&$bgm)) 
                    ? '' : " disabled='disabled'")
                  . " title='Make this file'/>"
                  . "</form>" );
              $rmmakeattrs =  array('name'=>'submit',
                    'title'=>'Remove and remake the working file');
              if (!wwRunHooks('WW-AllowMakeInSession', array(&$request,&$bgm)))
                $rmmakeattrs['disabled'] = 'disabled';
              $out->addHTML( "<form class='ww-inline-form'>"
                . $hiddeninputs
                . wwfHidden( 'action-filename', $pf['filename'] )
                . wwfHidden( 'ww-action', 'remove-and-remake' )
                . Xml::submitButton( 'rm/make', $rmmakeattrs )
                . '</form>' );
              $out->addHTML(
                "<form class='ww-inline-form'>"
                . $hiddeninputs
                . wwfHidden( 'action-filename', $pf['filename'] )
                . wwfHidden( 'archived', $pg )
                . wwfHidden( 'ww-action', 'remove-archived' )
                . "<input type='submit' name='submit' value='remove'"
                . " title='Remove this file from the project'/>"
                . "</form>" );
              $out->addHTML( '</td>' );
            }
            $out->addHTML( "</tr>\n" );
            $firstrow = false;
          }
        }
      $out->addHTML( "</table>\n" );
    }

    $details = null;
    if ( ! $readonly and 
	    $project->project_page() and
          wwfOKToEditPage( Title::newFromText($project->project_page()), $details ) )
    { if ($output_state != 'archived-project-files' and
          $output_state != 'action-links')
      { $out->addHTML("<hr/>\n");
      }
      $output_state = 'archived-project-files';
      $out->addHTML( "<form id='add-apf-form'>\n" );
      $out->addHTML( $hiddeninputs );
      $out->addHTML( wwfHidden( 'ww-action', 'set-archived' ) );
      $out->addHTML( "<table class='ww-add-archived-project-file'><tr>"
        . "<th><label for='ww-add-apf-filename'"
        . "  title='Filename of archived project file'>"
        . $wwContext->wwInterface->message('ww-add-apf-filename')
        . "</label></th>"
        . "<th><label for='ww-add-apf-page'"
        . " title='Page containing archived project file'>"
        . $wwContext->wwInterface->message('ww-add-sf-page') . "</label></th>"
        . "<th></th></tr>\n" );
      $out->addHTML( "  <tr><td><input type='text' name='action-filename'"
        . " value='' id='ww-add-apf-filename'"
        . " onchange='mw.libs.ext.ww.fillAddApfPage()'/></td>\n" );
      $out->addHTML( "      <td><input type='text' name='archived'"
        . " value='' id='ww-add-apf-page'/></td>\n" );
      $out->addHTML( "      <td><input type='submit' name='button'"
        ." value='set' title=\"Set an archived project file's location\"/></td>\n" );
      $out->addHTML( "  </tr>\n</table>\n" );
      $out->addHTML( "</form>\n" );
    }

    $output_state = 'dependencies';
    $out->addHTML( wwfHtmlPrerequisiteInfo( $project ) );

    $output_state = 'project-options';
    $out->wrapWikiMsg( "<h4 class='ww-mpf-h4'>$1</h4>\n",
      "ww-project-options" );

    $out->addHTML( "<form id='manage-project-options-form'>"
      . $hiddeninputs . "\n" );
    $out->addHTML( "<table class='ww-manageprojectoptions'>\n" );
    $out->addHTML( "  <tr><td>" 
      . "<input type='checkbox' name='use-default-makefiles' id='wpDefMake'"
        . " title=\"Set whether the project uses WorkingWiki's default make "
        . "rules\""
        . ($project->options['use-default-makefiles'] ?
            " checked='checked'" : '')
        . ($readonly ? " disabled='disabled'":
            " onchange='mw.libs.ext.ww.enableProjectOptionsSubmit()'")
        . "/>"
      . "<label for='wpDefMake'>" . $wwContext->wwInterface->message('ww-use-default-makefiles')
      . "</label></td>" );
    if ( $readonly )
      $out->addHTML( '<td/>' );
    else
      $out->addHTML( "<td class='ww-actions'>" 
        . wwfHidden( 'ww-action', 'set-project-options' )
        . "<input type='submit' name='submit' value='submit'"
          . " id='ww-project-options-submit'"
          . " title='Set the project options now'/></td>" );
    $out->addHTML( "</tr></table></form>\n" );
    $out->addHTML( "<script type='text/javascript'>"
      . "document.getElementById('ww-project-options-submit').disabled=1;</script>\n" );

    if ( count( $appearances ) ) {
      $out->wrapWikiMsg( "<h4 class='ww-mpf-h4 debug-only'>$1</h4>\n",
        "ww-project-file-appearances" );
      $out->addHTML( "<table class='ww-manageappearances debug-only'>\n" );
      $out->addHTML( "  <tr><th class='filename'>"
	. $wwContext->wwInterface->message('ww-pf-filename')."</th><th>"
        . $wwContext->wwInterface->message('ww-pf-page')."</th></tr>\n" );
      foreach ( $appearances as &$pf )
        if (is_array($pf['appears']))
        { $firstrow = true;
          foreach ($pf['appears'] as $pg=>$t)
          { $out->addHTML( "  <tr><td class='filename'>" );
            try
            { #$logfilename = "{$pf['filename']}.make.log";
              #if ( file_exists( 
              #  "{$project->project_directory()}/$logfilename" ) )
              #  $out->addHTML( $wwContext->wwInterface->make_altlinks(
              #    array( 'log' => $project->get_project_file_link($logfilename) ) ) );
              $out->addHTML(
                ($pf['filename'] == '' ? '(empty)' : 
                  $user->getSkin()->makeLinkObj( 
                    SpecialPage::getTitleFor( 'GetProjectFile' ),
                    htmlspecialchars($pf['filename']),
                    "project=" . urlencode($project->project_name()) 
                    . "&filename=" . urlencode($pf['filename'])
                    . "&make=false", '' ) ) );
              //  $user->getSkin()->link(
              //    SpecialPage::getTitleFor( 'GetProjectFile' ),
              //    $pf['filename'], array(),
              //    array( 'project' => $project->project_name(), 
              //      'filename' => $pf['filename'], 'make' => 'false' ),
              //    array('known') ) );
            } catch ( WWException $ex )
            { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors() ) );
            }
            $out->addHTML( "</td><td>" );
            try
            { $sk = $user->getSkin();
              $out->addHTML( $wwContext->wwInterface->makeLink( 
                $pg, htmlspecialchars($pg), $sk) );
            } catch ( WWException $ex )
            { $out->addHTML( wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors( ) ) );
            }
            $out->addHTML( "</td>\n" );
            if ( ! $readonly )
            { global $wgScript; 
              $out->addHTML("  <td class='ww-actions'>" );
              if ($firstrow)
                $out->addHTML(
                  "<form action='$wgScript' class='ww-inline-form'>"
                  . wwfHidden( 'title', 'Special:GetProjectFile' )
                  . wwfHidden( 'project', $project->project_name() )
                  . wwfHidden( 'filename', $pf['filename'] )
                  . wwfHidden( 'make', 'yes' )
                  . "<input type='submit' name='submit' value='make'"
                  . (wwRunHooks('WW-AllowMakeInSession', array(&$request,&$bgm)) 
                    ? '' : " disabled='disabled'")
                  . " title='Make this file'/>"
                  . "</form>" );
              $rmmakeattrs =  array('name'=>'submit',
                    'title'=>'Remove and remake the working file');
              if (!wwRunHooks('WW-AllowMakeInSession', array(&$request,&$bgm)))
                $rmmakeattrs['disabled'] = 'disabled';
              $out->addHTML( "<form class='ww-inline-form'>"
                . $hiddeninputs
                . wwfHidden( 'action-filename', $pf['filename'] )
                . wwfHidden( 'ww-action', 'remove-and-remake' )
                . Xml::submitButton( 'rm/make', $rmmakeattrs )
                . '</form>' );
              $out->addHTML(
                "<form class='ww-inline-form'>"
                . $hiddeninputs
                . wwfHidden( 'action-filename', $pf['filename'] )
                . wwfHidden( 'appears', $pg )
                . wwfHidden( 'ww-action', 'remove-appears' )
                . "<input type='submit' name='submit' value='remove'"
                . " title='Remove this file from the project'/>"
                . "</form>" );
              $out->addHTML( '</td>' );
            }
            $out->addHTML( "</tr>\n" );
            $firstrow = false;
          }
        }
      $out->addHTML( "</table>\n" );
    }
    //else
    //  $out->addHTML( "<hr/>\n" );

    $output_state = 'project-uri';
    //$out->wrapWikiMsg( "<h4 class='ww-mpf-h4'>$1</h4>\n", 'ww-project-uri-section' );
    $out->addHTML( wfMessage( 'ww-project-uri-label' )->parse() );
    $out->addHTML( '<code>'
	    . htmlspecialchars( $project->project_uri() )
	    . "</code>\n"
    );

    return;
  }
}

?>
