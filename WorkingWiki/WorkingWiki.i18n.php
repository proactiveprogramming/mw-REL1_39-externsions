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

$messages = array();

global $wgVersion;

/* *** English *** */
$messages['en'] = array(
  # Main WW code messages
  'ww' => 'WorkingWiki',
  'go-to-project' => 'Go:',                       // search field label
  'import-project-link' => 'Import project',      // link text
  'import-project-files-link' => 'Import project files', // link text
  'ww-messages-legend' =>                         // fieldset legend
    'WorkingWiki messages',
  'ww-insert-too-long' =>                         // error message
    'Content of file ‘$1’ exceeds the maximum allowed length of $2 '
    . 'for inserting into the text of a wiki page.',
  'ww-illegal-filename' =>                        // error message
    'Filename ‘$1’ not permitted for project files.',
  'ww-illegal-link' =>
    'File ‘$1’ not shown.  For security reasons, WorkingWiki does not follow '
    . 'links outside its project directories when displaying files.',
  'ww-no-project-files-during-history' =>
    'File ‘$1’ not displayed, because display of project files on historical '
    . 'pages is not currently supported.',
  'ww-make-failed' => 
    'Make ‘$1’ failed.  Consult the <a href="$2">log file</a> '
    . 'for more information.',
  'ww-make-failed-x' => 
    'Make ‘$1’ failed.  Consult the <a href="$2">log file</a> '
    . 'for more information.',
  # Dynamic display of project files
  'ww-dynamic-project-file-placeholder' =>
    'Loading WorkingWiki file "$1" dynamically.  If it doesn\'t load, '
    . '<a href="$3">click to view the page statically</a>.',
  'ww-dynamic-display-preference' => 'Dynamic updating of WorkingWiki files',
  'ww-dynamic-display-preferences-default' => 'Default: update dynamically whenever browser is equipped',
  'ww-dynamic-display-preferences-never' => 'Disable dynamic updating',
  'ww-dynamic-display-preferences-help' =>
    'This experimental feature updates WorkingWiki files on the fly,'
    . ' after the wiki page is loaded in the browser.  It can help avoid'
    . ' slow loading times for wiki pages, and soon may allow more'
    . ' options, such as remaking files without leaving the page.',
  'ww-dynamic-project-file-failed' =>
    'File  ‘<strong>$1</strong>’, in project ‘<strong>$2</strong>’,'
    . ' failed to load.',
  'ww-dynamic-altlinks-reload' => 'reload',
  'ww-dynamic-altlinks-remake' => 'remake',
  'ww-dynamic-altlinks-download' => 'download',
  # Comet operations
  'ww-comet-dialog-title' => 'WorkingWiki status',
  'ww-comet-dialog-action-title' => 'WorkingWiki: $1',
  'ww-comet-opening' => 'Opening connection for $1 operation... ',
  'ww-comet-lost-connection' => 'Lost connection... ',
  'ww-comet-timed-out-reconnecting' => 'Timed out - reconnecting... ',
  'ww-comet-subscribing' => 'Opening connection for updates... ',
  'ww-comet-connected' => 'connected',
  'ww-comet-lost-data' => 'Lost data: retrying...',
  'ww-comet-done-loading-files-in-page' => 'Done loading files in page.',
  'ww-comet-done' => 'Done.',
  'ww-comet-could-not-connect' => 'Could not connect to server.',
  'ww-comet-error' => 'Error: $1',
  'ww-comet-connection-failed' => 'Too many failed connection attempts.  Please try again later.',
  'ww-comet-syncing-source-files' => 'Syncing source files for {{PLURAL:$2|project $1|projects $1}}',
  'ww-comet-merging-from-preview' => 'Merging preview directories into persistent storage',
  # Special:GetProjectFile
  'viewprojectfile' => 'Get Project File',         // page title ?
  'getprojectfile' => 'Get Project File',         // page title
  'ww-errorpage' => 'Error',                      // page title
  'ww-need-filename' =>                           // error message
    'Special:GetProjectFile requires a filename or project argument, or both.',
  'ww-resources-dir' => 'Resources',              // part of title
  'directorycontents' => 'Working directory contents: $1',
  'ww-getprojectfile-error-retrieving' => "Error retrieving $1. $2",
  #'directorycontents-2' => 'Working directory contents: $1 › $2',
  'ww-permissions' => 'Permissions',              // table header
  'ww-filesize' => 'Size',                        // table header
  'ww-filesize-KB' => 'K',                        // # of bytes
  'ww-filesize-MB' => 'M',
  'ww-filesize-GB' => 'G',
  'ww-filesize-TB' => 'T',
  'ww-mod-time' => 'Modification time',           // table header
  'ww-filename' => 'Filename',                    // table header
  # Special:ManageProject
  'manageproject'  => 'Manage Project: $1',       // page title
  'ww-delete-project' => 'Delete',                // action tab
  'ww-projectdescription' => 'Project description', // link text
  'ww-projectpage' => 'Wiki page ‘$1’',           // link text
  'ww-projectpage-comment' => '(project does not have a saved description)',
                                                  // message
  'ww-listwd' => 'Browse working directory',        // link text
  'ww-source-files' => 'Source file definitions', // heading
  'ww-sf-filename' => 'Filename',                    // table header
  'ww-sf-page' => 'Page',                            // table header
  'ww-page-by-default' => '(by&nbsp;default)',         // message
  'ww-sf-missing' => '(missing)',                 // message
  'ww-sf-automatically-generated' => '(automatically generated)', // message
  'ww-add-sf-filename' => 'Source filename',      // textfield label
  'ww-add-sf-page' => 'Page',                     // textfield label
  'ww-project-options' => 'Project options',      // heading
  'ww-project-uri-section' => 'Project URI',      // heading
  'ww-project-uri-label' => 'Project URI: ',      // label
  'ww-use-default-makefiles' =>                   // checkbox
    'Use WorkingWiki\'s supplemental make rules',
  'ww-project-file-appearances' => 'Project file locations',  // heading
  'ww-project-files-archived' => 'Archived project file locations', //heading
  'ww-add-apf-filename' => 'Archived project filename', // textfield label
  'ww-add-apf-page' => 'Page',                    // textfield label
  'ww-apf-filename' => 'Filename',                // table header
  'ww-apf-page' => 'Page',                        // table header
  'ww-pf-filename' => 'Filename',                 // table header
  'ww-pf-page' => 'Page',                         // table header
  'ww-prerequisite-projects' => 'Prerequisite projects', // heading
  'ww-prerequisite-name' => 'Prerequisite project name',       // table header
  'ww-prerequisite-project-dir-var' => 
    'Variable name for make operations',          // table header
  'ww-prerequisite-readonly' => 'Copy for previews and background jobs', //table header
  'ww-prerequisite-is-readonly' => 'Do not copy',   // checkbox label
  'ww-add-prereq-name' => 'Prerequisite project name', // textfield label
  'ww-pages' => 'Pages',                          // table header
  'ww-make-target' => 'Target',                   // textfield label
  'ww-cleardirectory' => 'Clear working directory', // link text
  'ww-sync-all' => 'Sync all source files',       // link text
  'ww-export-sf' => 'Export source files',        // link text
  'ww-export-wd' => 'Export working directory',   // link text
  'ww-export-wp' => 'Export including pages',     // link text
  'ww-importfiles' => 'Import project files',     // link text
  'ww-clear-project-directory-button' =>          // button
    'Clear Working Directory',
  # API actions
  'ww-sync-file-success' => 'Synced file ‘$1’ into working directory',
  'ww-sync-all-success' => 'Synced source files into working directory',
  'ww-clear-directory-success' => 'Cleared working directory',
  'ww-clear-directory-confirm-message' =>
    'Are you sure you want to clear away all the working files in project $1?',
  'ww-clear-directory-confirm-button' => 'Clear',
  'ww-import-project-files-status' => 'Import',
  'ww-get-project-file-success' => 
	( version_compare( $wgVersion, '1.23', '<' ) ?
	'Retrieved file $1' :
	'{{PLURAL:$2|0=Retrieved|1=Made}} {{PLURAL:$3|0=project|1=source}} file $1' ),
  'ww-get-project-file-status' => 
	( version_compare( $wgVersion, '1.23', '<' ) ?
	'Get $1' :
	'{{PLURAL:$2|0=Get|1=Make}} $1' ),
  'ww-list-directory-status' => 'List project directory',
  'ww-list-resources-directory-status' => 'List resources directory',
  'ww-sync-file-status' => 'Sync $1',
  'ww-sync-all-status' => 'Sync source files',
  'ww-clear-directory-status' => 'Clear directory',
  # WWActions
  'ww-action-succeeded' => '‘$1’ action succeeded.', // success message
  'ww-unknown-action' => '‘$1’ action is not supported.', // error message
  'ww-cancel' => 'Cancel',                        // button
  'source-file' => 'source file',                 // noun
  'project-file' => 'project file',               // noun
  'archived-project-file' => 'archived project file', // noun
  'ww-confirm-remove-projwd' =>                   // question
    'Do you want to remove $1 ‘$2’ from the project, '
    . 'as well as from the working directory?',
  'ww-confirm-remove-projwdpage-sf' =>               // question
    'Do you want to remove $1 ‘$2’ from the project and the '
    . 'wiki, as well as from the working directory?',
  'ww-confirm-remove-projwdpage-archived' =>      //question
    'Do you want to remove $1 ‘$2’ from the project and '
    . 'page ‘$3’, as well as from the working directory?',
  'ww-wdonly' => 'Working directory only',        // submit button label
  'ww-projwd' => 'Project and working directory', // submit button label
  'ww-projwdpage' => 'Project, working directory and wiki page',
                                                  // submit button label
  'ww-removed' =>                                 // message
    'Removed $1 ‘$2’ from working directory.',
  'ww-removed-projwd' =>                          // message
    'Removed $1 ‘$2’ from project.',
  'ww-removed-projwdpage' =>                      // message
    'Removed $1 ‘$2’ from project and wiki.',
  'ww-removed-projwd-appears' =>                  // message
    'Removed project-file ‘$1’ appearance on page ‘$2’.',
  'ww-removed-projwd-archived' =>                 // message
    'Project-file ‘$1’ will no longer be archived on page ‘$2’.',
  'ww-removed-projwdpage-archived' =>             // message
    'Removed archived project-file ‘$1’ from page ‘$2’.',
  'ww-confirm-image' =>                           // message
    'Page ‘$2’ is a text page.  Are you sure you want to set '
    . 'image file ‘$1’ to be archived there?  This may cause problems.',
  'ww-confirm-delete-project' =>
    'Are you sure you want to delete project $1?',// message
  'ww-confirm-delete-project-and-files' =>        // message
    'Do you want to delete project $1 and all its source and project files?',
  'ww-delete-project-only' =>
    'Delete project',                             // submit button label
  'ww-delete-project-and-files' =>
    'Delete project and files',                   // submit button label
  'ww-delete-project-success' =>
    'Deleted project $1.',                        // message
  'ww-delete-project-and-files-success' =>
    'Deleted project $1 and its files.',          // message
  'ww-made-target' => 'Made ‘$1’.',               // message
  'ww-added-prerequisite-project' =>              // message
    'Added dependency on project ‘$1’.',
  'ww-updated-prerequisite-project' =>            // message
    'Updated prerequisite project ‘$1’.',
  'ww-removed-prerequisite-project' =>            // message
    'Removed dependency on project ‘$1’.',
  'ww-synced-all' =>                              // message
    'Synced all source files into the working directory.',
  # Special:CustomUpload
  'duplicate-destination' =>                      // warning message
    'The destination filename is the same as ‘Upload file $1’, above.',
  'duplicate-upload' =>                           // warning message
    'This file is a duplicate of the file being uploaded by ‘Upload file $1’, above.',
  # Upload messages that have changed since I took a copy of Special:Upload,
  # so that I need to have them here.  Complete as of MW 1.17. 
  # Very much need a better implementation of the upload interface.
  'customupload' => 'Upload',                     // page title
  'custom-uploadbtn' => 'Upload',                 // button
  'ww-fileexists'                  => "A file with this name exists already, please check '''<tt>$1</tt>''' if you are not sure if you want to change it.",
  'fileexists-thumb' => 'Existing file',
  'emptydest' =>                                  // warning message
    'The destination filename is missing.  Please provide a filename.',
  'uploaderror' => 'Upload error',                // error message header
  'uploadcorrupt' => 'The file is corrupt or has an incorrect extension.
Please check the file and upload again.',
  # Special:MultiUpload
  'multiupload' => 'MultiUpload',              // name of page
  'multiupload-row' => 'File $1',              // fieldset legend for row
  'multiupload-submit' => 'Upload files',           // submit button
  'multiupload-uploadedto' => 'Uploaded file $1.',  // success message
  # Special:ImportProject
  'importproject' => 'Import Project',            // page title
  'import-project-top' =>                          // instructions
    "Use this form to import a directory of files into a project.\n" .
    "<ul><li>You will be allowed to assign all the files within " .
    "the package to source and project files in the project.</li>\n".
    "<li>You can create a new project, or add " .
    "or replace files within an existing project.</li>\n" .
    "<li>It's not possible to upload a directory directly, " .
    "so it needs to be packaged into a tar or zip file.</li></ul>", 
  'import-project-legend' =>                      // fieldset legend
    "Upload package",
  'import-project-source' =>                      // upload field label
    "File to upload:",
  'bad-project-name' =>                           // error message
    'Bad project name ‘$1’.',
  # Special:ImportProjectFiles
  # (the old version)
  'importprojectfiles' => 'Import Project Files', // page title
  'uploadprojectfiles-top' =>                     // instructions
    "Use this form to upload files to project $1.\n\n" .
    "You can upload text files to any wiki page.  If it's to a regular " .
    "wiki page, it'll be inserted between \"source-file\" or " .
    "\"project-file\" tags.  If it's to a $2: page, it'll be uploaded " .
    "like an image file.\n\n" .
    "For binary files it's recommended to use $2: pages.",
  'ww-upload-permitted' =>                        // message
    'Permitted file types for $1 pages: $2.',
  'ww-text-upload-permitted' =>                   // message
    'Permitted file types for text pages: $1.',
  //'ww-text-upload-permitted-prefixes' =>          // message
  //  '; and files beginning with: $1.',
  'ww-upload-preferred' =>                        // message
    'Preferred file types for $1 pages: $2.',
  'ww-text-upload-preferred' =>                   // message
    'Preferred file types for text pages: $1.',
  'ww-upload-prohibited' =>                       // message
    'Prohibited file types for $1 pages: $2.',
  'ww-text-upload-prohibited' =>                  // message
    'Prohibited file types for text pages: $1.',
  'ww-filetype-unwanted-type' =>                  // message
    "'''\".\$1\"''' is an unwanted file type.  " .
    "Preferred {{PLURAL:\$3|file type|file types}} for " .
    "uploading to text pages {{PLURAL:\$3|is|are}} \$2.",
  'upload-projectname' => 'Project name:',        // textfield label
  'upload-project-file' => 'Import project file $1', // fieldset legend
  'upload-project-page' => 'Import page $1',      // fieldset legend
  'import-project-files-source-1' => 'Source file', // radio button label
  'import-project-files-source-0' =>              // radio button label
    'Archived project file',
  # Special:ImportProjectFiles
  # (the new version)
  'ww-importprojectfiles-top' =>                  // instructions
    "Use the form below to import files into WorkingWiki projects.\n\nYou can "
    ."import any kind of files to File: locations (for example '''File:XYZ.jpg'''), "
    ."and you can also import text files into text pages (for example '''Main_page''').\n\n"
    ."This form can import package files (<code>.zip</code>, <code>.tar</code>, <code>.tgz</code>, or <code>.tar.gz</code>) as is, or unpack them and import the files they contain.\n\n"
    ."The files you import will be added to your project(s), and you will be able "
    ."to use them to make and display output files.",
  'ww-importprojectfiles-multiupload-missing' =>
    'Special:ImportProjectFiles is not available, because this wiki does not have the '
    . '[http://www.mediawiki.org/wiki/Extension:MultiUpload MultiUpload extension] installed, '
    . 'which is necessary to make it work.',
  'ww-multiupload-row-name-base' => 'File $1', // fieldset legend
  'ww-importprojectfiles-maxfilesize' =>          // upload help message
    'Maximum size for insertion into a text page: $1',
  'ww-importprojectfiles-project-filename' => 'Destination&nbsp;filename:', // textfield label
  'ww-importprojectfiles-project-filename-help' => // help message
    'The name this file will have in the project\'s working directory',
  'ww-importprojectfiles-desttype' => 'Import as', // select label
  'ww-importprojectfiles-desttype-help' =>      // help message
    "* Source file: Store the file contents in a wiki page, register it as one of the project's source files, and sync it into the working directory before making any targets\n" .
    "* Project file only: Save the file contents directly into the project's working directory without saving it on a wiki page\n" .
    "* Archived project file: Register the file to be saved to a wiki page any time its contents change in the working directory, and save this file's contents as the latest version\n",
  'ww-importprojectfiles-desttype-source' => 'Source file', // select option
  'ww-importprojectfiles-desttype-project' => 'Project file only', // select option
  'ww-importprojectfiles-desttype-archived' => 'Archived project file', // select option
  'ww-importprojectfiles-project-name' => 'Project:', // textfield label
  'ww-importprojectfiles-project-name-help' =>     // help message
    'The name of the project to house this file',
  'ww-importprojectfiles-destpage' => 'Destination page:', // textfield label
  'ww-importprojectfiles-destpage-help' =>         // help message
    'The name of the wiki page where the file\'s contents will be stored',
  'ww-importprojectfiles-file-upload-permitted' => // help message
    'Permitted types for uploads to File: locations: $1.',
  'ww-importprojectfiles-file-upload-preferred' => // help message
    'Preferred types for uploads to File: locations: $1.',
  'ww-importprojectfiles-file-upload-prohibited' => // help message
    'Prohibited types for uploads to File: locations: $1.',
  'ww-importprojectfiles-succeeded-project' =>    // output message
    'Importing project file $2 into project $1 working directory',
  'ww-importprojectfiles-succeeded-archived' =>   // output message
    'Importing archived project file $1 to page $2',
  'ww-importprojectfiles-succeeded-source' =>     // output message
    'Importing source file $1 into page $2',
  'ww-importprojectfiles-in-project' =>           // suffix
    '(in project $1)',
  'ww-importprojectfiles-in-no-project' =>        // suffix
    '(with no project name)',
  'ww-importprojectfiles-bad-projectname' =>      // error message
    'Bad project name \'$1\'',
  'ww-importprojectfiles-bad-dest-filename' =>    // warning message
    'Can\'t import to destination filename "$1"',
  'ww-importprojectfiles-bad-dest-page' =>        // error message
    'Can\'t import to page "$1"',
  'ww-importprojectfiles-image-to-text-page' =>   // warning message
    'This seems to be a binary or image file.  Inserting its contents'
    . ' into the text of page $1 could produce unpleasantly cryptic'
    . ' page text.  Consider using a destination page in the File: namespace.',
  'ww-importprojectfiles-bad-filesize' =>         // error message
    'Could not get size of file',
  'ww-importprojectfiles-missing-file' =>         // error message
    'Could not access uploaded file',
  'ww-importprojectfiles-empty-file' =>           // error message
    'Uploaded file is empty',
  'ww-importprojectfiles-file-too-large' =>       // error message
    'This file is bigger than the wiki is configured to accept',

  'import-project-files-skip' => 'Skip this file', // checkbox label
  'upload-filename' => 'File to upload:',         // textfield label
  'project-filename' =>                           // textfield label
    'Destination project filename:',
  'upload-source-page-name' => 'Import wiki page:', // textfield label
  'destpage' => 'Destination page:',              // textfield label
  'watchthispage' => 'Watch this page',           // checkbox label
  'ww-import-maxfilesize'          
     => 'Maximum file size: $1 in general, $2 on text pages', // message
  'upload-nodestpage' =>                          // warning message 
    'A destination page wasn\'t specified',
  'upload-noproject-title' =>                     // error page title 
    'Missing Project Name',
  'upload-noproject' =>                           // error page text
    'This page can\'t be used in isolation, because it needs to know ' .
    'what project it\'s operating on.  It should be called by clicking a ' .
    'link on a project management page.',
  'image-to-text-page' =>                         // warning message
    'Uploading a .$1 file to a text page (‘$2’) is likely to produce an ' .
    'unpleasantly formatted page.  Consider using an Image: page.',
  'import-project-files-none' =>                  // error page text
    'No project files found!',
  'ww-appended-imported-page' =>                  // 'recent changes' comment
    'WorkingWiki appended imported page to existing page ‘$1’.',
  'ww-created-imported-page' =>                   // 'recent changes' comment
    'WorkingWiki imported page ‘$1’.',
  'import-project-files-will-append' =>           // warning text
    'Page [[$1]] already exists.  The imported ' .
    'page content will be appended, which may cause duplicate source ' .
    'files or other problems.',
  'ww-multiupload-unpack-button' =>        // button text
    'Unpack',
  'ww-importprojectfiles-file-unpacked-from' =>   // informational message
    'File <b>$1</b> from package <b>$2</b>',
  # WWPreview class
  'ww-in-preview-session' => '(in preview session ‘$1’)', // for JS notices
    // not currently in use - LW
  'preview-h2' => 'Preview session ‘$1’',         // special page header
  'ww-dynamic-project-file-placeholder-preview' =>
    'Loading WorkingWiki file "$1".  If it doesn\'t load, '
    . 'try previewing again.',
  # WWBackground
  'ww-background' => 'WW-Background',
  'wwb-job-state-r' => 'Running',
    # see https://confluence.rcs.griffith.edu.au/display/v20zCluster/Sun+Grid+Engine+SGE+state+letter+symbol+codes+meanings
  'wwb-job-state-dr' => 'Scheduled for termination',
  'wwb-job-state-qw' => 'Pending',
  'wwb-job-state-hqw' => 'Pending, system hold',
  'wwb-job-state-t' => 'Transferring',
  'wwb-job-is-running' => '$5: Background job <code>$1</code> (user $2, $3project(s) $4), running since $6.',
  'wwb-job-succeeded' => 'Succeeded: Background job <code>$1</code> (user $2, $3project(s) $4).',
  'wwb-job-succeeded-time' => 'Succeeded: Background job <code>$1</code> (user $2, $3project(s) $4), at $5.',
  'wwb-job-failed' => 'Failed: Background job <code>$1</code> (user $2, $3project(s) $4).',
  'wwb-job-failed-time' => 'Failed: Background job <code>$1</code> (user $2, $3project(s) $4), at $5.',
  'wwb-job-status-unknown' => 'Job status unknown: Background job <code>$1</code> ($2project(s) $3).',
  'wwb-target' => 'target $1, ',
  'wwb-header' => 'Displaying files within background job <code>$1</code>.',
  'wwb-actions-line-start' => '',
  'wwb-browse-link' => 'Browse',
  'wwb-destroy-link' => 'Destroy',
  'wwb-merge-link' => 'Merge',
  'wwb-kill-link' => 'Kill',
  'wwb-retry-link' => 'Retry',
  'wwb-actions-line-middle' => ' · ',
  'wwb-actions-line-end' => '',
  #'ww-create-background-job-confirm-message' => 'Are you sure you want to create a background job to make ‘$1’ in project ‘$2’?',
  #'ww-create-background-job-confirm-button' => 'Create',
  'ww-create-background-job-success' => 'Created background job for target ‘$1’',
  'ww-create-background-job-status' => 'Create background job for ‘$1’',
  'ww-kill-background-job-confirm-message' => 'Are you sure you want to kill background job $1?',
  'ww-kill-background-job-confirm-button' => 'Kill',
  'ww-kill-background-job-success' => 'Killed background job $1.',
  'ww-kill-background-job-status' => 'Kill background job.',
  'ww-merge-background-job-success' => 'Merged background job $1 into persistent working directory.',
  'ww-merge-background-job-status' => 'Merge background job',
  'ww-destroy-background-job-confirm-message' => 'Are you sure you want to permanently destroy background job $1?',
  'ww-destroy-background-job-confirm-button' => 'Destroy',
  'ww-destroy-background-job-success' => 'Destroyed background job $1.',
  'ww-destroy-background-job-status' => 'Destroy background job',
  'ww-no-make-in-background' => 'Make operations are not allowed in background job directories',
  # Messages for use in JavaScript
  'ww-default-confirm-message' => 'Are you sure you want to do the ‘$1’ action?', // JS question
  'ww-default-confirm-button' => 'Do it',
  'ww-default-success' => '‘$1’ action succeeded.', // JS success message
  'ww-default-failure' => '‘$1’ action failed.', // JS failure message
  'ww-default-status' => '$1',
  'ww-notify-ok' => 'OK',  // button on general notification
  'ww-api-no-action' =>    // error message
    'Internal error: API call requested with no action specified.',
  # Preferences
  'prefs-workingwiki' => 'WorkingWiki',
  'ww-mathml-preference' => "Use browser's MathML rendering when available",
  'ww-mathml-preference-help' => "Without this option, WorkingWiki uses MathJax to display math in all browsers.  When this option is enabled, it leaves MathJax out when you are using a browser (Firefox) that can display MathML on its own.",
  'tog-ww-background-jobs-emails' => 
    "Email me when my WorkingWiki background jobs finish",
  'ww-leekspin-preference' => 'Display http://leekspin.com when page is loading slowly',
  # misc.php
  'multiupload-backto' => 'Go back to',           // link message
);

?>
