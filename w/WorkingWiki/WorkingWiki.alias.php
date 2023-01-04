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

$aliases = array();
 
/** English */
$aliases['en'] = array(
      'GetProjectFile' => array( 'GetProjectFile' ),
      'ManageProject' => array( 'ManageProject' ),
      'ImportProject' => array( 'ImportProject' ),
      'ImportProjectFiles' => array( 'ImportProjectFiles' ),
      'MultiUpload' => array( 'MultiUpload' ),
    );

/** Chinese **/
$aliases['zh'] = array(
      'GetProjectFile' => array( 'GetProjectFile' ),
      'ManageProject' => array( 'ManageProject' ),
      'ImportProject' => array( 'ImportProject' ),
      'ImportProjectFiles' => array( 'ImportProjectFiles' ),
      'MultiUpload' => array( 'MultiUpload' ),
    );
 
# mw 1.16+
# this isn't working, but I don't know why
$specialPageAliases = array();

$specialPageAliases['en'] = array(
      'GetProjectFile' => array( 'GetProjectFile', 'Get Project File'),
      'ManageProject' => array( 'ManageProject', 'Manage Project' ),
      'ImportProject' => array( 'ImportProject', 'Import Project' ),
      'ImportProjectFiles' => array( 'ImportProjectFiles', 'Import Project Files' ),
      'MultiUpload' => array( 'MultiUpload' ),
    );

?>
