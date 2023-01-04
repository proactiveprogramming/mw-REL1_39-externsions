<?php
/* WorkingWiki extension for MediaWiki 1.13
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

$wgHooks['WW-AddToMakeForm'][]   
  = 'WWBackground::AddToMakeForm_hook';
$wgHooks['WW-RenderProjectFile'][]
  = 'WWBackground::RenderProjectFile_hook';

$wgHooks['WW-PERequest'][] 
  = 'WWBackground::PERequest_hook';
$wgHooks['WW-GetProjectFileQuery'][] 
  = 'WWBackground::GetProjectFileQuery_hook';
$wgHooks['WW-MakeManageProjectQuery'][]
  = 'WWBackground::MakeManageProjectQuery_hook';
$wgHooks['WW-GetProjectFile-Headers'][] 
  = 'WWBackground::GetProjectFile_Headers_hook';
$wgHooks['WW-GetProjectFile-altlinks'][] 
  = 'WWBackground::GetProjectFile_altlinks_hook';
$wgHooks['WW-GetProjectFile-AssumeResourcesDirectory'][] 
  = 'WWBackground::GetProjectFile_AssumeResourcesDirectory_hook';
$wgHooks['WW-ListDirectorySetup'][]
  = 'WWBackground::ListDirectorySetup_hook';

$wgHooks['WW-AllowMakeInSession'][]
  = 'WWBackground::AllowMakeInSession_hook';
$wgHooks['WW-BackgroundMakeOK'][]
  = 'WWBackground::BackgroundMakeOK_hook';
$wgHooks['WW-Api-Call-Arguments'][]
  = 'WWBackground::ApiCallArguments_hook';

#$wgHooks['WW-ReplaceMakeCommand'][]    
#  = 'WWBackground::ReplaceMakeCommand_hook';
#$wgHooks['WW-RunMakeCommand'][]    
#  = 'WWBackground::RunMakeCommand_hook';
#$wgHooks['WW-ProjectDirectory'][]
#  = 'WWBackground::ProjectDirectory_hook';

#$wgHooks['ParserBeforeTidy'][]     
#  = 'WWBackground::ParserBeforeTidy_hook';
$wgHooks['OutputPageBeforeHTML'][] 
  = 'WWBackground::OutputPageBeforeHTML_hook';
$wgHooks['WW-BeforeManageProject'][] 
  = 'WWBackground::TopOfSpecialPage_hook';
$wgHooks['WW-BeforeGetProjectFile'][] 
  = 'WWBackground::TopOfSpecialPage_hook';

$wgAutoloadClasses['WWBackground']
  = "$wwExtensionDirectory/Background/WWBackground.php";

# Background API actions

$wgAPIModules['ww-create-background-job']  = 'WWApiCreateBackgroundJob';
$wgAPIModules['ww-kill-background-job']    = 'WWApiKillBackgroundJob';
$wgAPIModules['ww-destroy-background-job'] = 'WWApiDestroyBackgroundJob';
$wgAPIModules['ww-merge-background-job']   = 'WWApiMergeBackgroundJob';
$wgAPIModules['ww-list-background-jobs']   = 'WWApiListBackgroundJobs';

$wgAutoloadClasses['WWApiCreateBackgroundJob']
  = "$wwExtensionDirectory/Background/BackgroundApi.php";
$wgAutoloadClasses['WWApiKillBackgroundJob']
  = "$wwExtensionDirectory/Background/BackgroundApi.php";
$wgAutoloadClasses['WWApiDestroyBackgroundJob']
  = "$wwExtensionDirectory/Background/BackgroundApi.php";
$wgAutoloadClasses['WWApiMergeBackgroundJob']
  = "$wwExtensionDirectory/Background/BackgroundApi.php";
$wgAutoloadClasses['WWApiListBackgroundJobs']
  = "$wwExtensionDirectory/Background/BackgroundApi.php";

# format for actions' messages

$wwApiMessages['ww-create-background-job'] = array(
	'args' => array( 'filename', 'project' ),
);
$wwApiMessages['ww-kill-background-job'] = array(
	'args' => array( 'jobid' ),
);
$wwApiMessages['ww-destroy-background-job'] = array(
	'args' => array( 'jobid' ),
);
$wwApiMessages['ww-merge-background-job'] = array(
	'args' => array( 'jobid' ),
);

# modules for loading into the browser

$wgResourceModules['ext.workingwiki.background.top'] = array(
  'localBasePath' => "$wwExtensionDirectory/Background/resources", 
  'scripts' => array( 'ext.workingwiki.background.top.js' ),
  'position' => 'top',
  'dependencies' => array(
    'ext.workingwiki.top',
  ),
  'messages' => array(
    #'ww-create-background-job-confirm-message',
    'ww-kill-background-job-confirm-message',
    'ww-destroy-background-job-confirm-message',
  ),
);
$wgResourceModules['ext.workingwiki.background.confirm'] = array(
  'messages' => array(
    #'ww-create-background-job-confirm-button',
    'ww-kill-background-job-confirm-button',
    'ww-destroy-background-job-confirm-button',
  ),
);
$wgResourceModules['ext.workingwiki.confirm']['dependencies'][] =
  'ext.workingwiki.background.confirm';

$wgResourceModules['ext.workingwiki.background'] = array(
  'localBasePath' => "$wwExtensionDirectory/Background/resources", 
  'scripts' => array( 'ext.workingwiki.background.js' ),
  'styles' => array( 'ext.workingwiki.background.css' ),
  'dependencies' => array(
	  'ext.workingwiki',
  ),
  'messages' => array(
    'ww-create-background-job-success',
    'ww-create-background-job-status',
    'ww-kill-background-job-success',
    'ww-kill-background-job-status',
    'ww-destroy-background-job-success',
    'ww-destroy-background-job-status',
    'ww-merge-background-job-success',
    'ww-merge-background-job-status',
  ),
);

?>
