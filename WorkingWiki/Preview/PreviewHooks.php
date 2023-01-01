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

# code for fancy WW preview operations, with a preview working directory
# separate from the regular working directory.

$wgHooks['WW-MakeManageProjectQuery'][]
  = 'WWPreview::MakeManageProjectQuery_hook';
$wgHooks['WW-GetProjectFileQuery'][]
  = 'WWPreview::GetProjectFileQuery_hook';
$wgHooks['WW-GetProjectFile-Headers'][]
  = 'WWPreview::GetProjectFile_Headers_hook';
$wgHooks['WW-ManageProject-Headers'][]
  = 'WWPreview::GetProjectFile_Headers_hook';
$wgHooks['WW-HiddenActionInputs'][]
  = 'WWPreview::HiddenActionInputs_hook';
$wgHooks['WW-UploadMissingFilesButton'][]
  = 'WWPreview::UploadMissingFilesButton_hook';
$wgHooks['WW-PERequest'][]
  = 'WWPreview::PERequest_hook';
$wgHooks['WW-RenderProjectFile'][] 
  = 'WWPreview::RenderProjectFile_hook';
$wgHooks['WW-ProactivelySyncIfNeeded'][]
  = 'WWPreview::ProactivelySyncIfNeeded_hook';
$wgHooks['WW-MakeTarget'][] 
  = 'WWPreview::MakeTarget_hook';
$wgHooks['WW-OKToSyncSourceFiles'][]
  = 'WWPreview::OKToSyncSourceFiles_hook';
$wgHooks['WW-OKToSyncFromExternalRepos'][]
  = 'WWPreview::OKToSyncFromExternalRepos_hook';
$wgHooks['WW-OKToArchiveFiles'][]
  = 'WWPreview::OKToArchiveFiles_hook';
$wgHooks['WW-BackgroundMakeOK'][]
  = 'WWPreview::BackgroundMakeOK_hook';
$wgHooks['WW-OKToInsertBackgroundJobsList'][]
  = 'WWPreview::OKToInsertBackgroundJobsList_hook';
$wgHooks['WW-Api-Call-Arguments'][]
  = 'WWPreview::ApiCallArguments_hook';
$wgHooks['WW-DynamicProjectFilesPlaceholderMessage'][]
  = 'WWPreview::DynamicProjectFilesPlaceholderMessage_hook';

$wgHooks['OutputPageBeforeHTML'][]
  = 'WWPreview::OutputPageBeforeHTML_hook';
$wgHooks['EditPage::showEditForm:fields'][] 
  = 'WWPreview::showEditForm_fields_hook';
$wgHooks['EditPage::attemptSave'][]
  = 'WWPreview::attemptSave_hook';
$wgHooks['ArticleSaveComplete'][]
  = 'WWPreview::ArticleSaveComplete_hook';
$wgHooks['EditPage::showEditForm:initial'][]
  = 'WWPreview::EditPage__showEditForm_initial_hook';

$wgAutoloadClasses['WWPreview']
  = "$wwExtensionDirectory/Preview/WWPreview.php";

?>
