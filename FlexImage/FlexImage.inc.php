<?php
/******************
 *  This php include file is part of an qbox4u.com extension
 *  Copyright (C) 2010 qbox4u.com <qbox4u@gmail.com>  
 *
 *  This program is not free software therefore you can not redistribute 
 *  it and/or modify it under the terms of the GNU General Public License 
 *  as published by the Free Software Foundation; either version 2 of the 
 *  License, or (at your option) any later version.
 *
 *  Please consult and/or request the administrator of qbox4u@gmail.com
 *  to use the information and samples 
 * 
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *  http://www.gnu.org/copyleft/gpl.html
 *
 *  @ingroup Extensions
 *  @author Jan boer <qbox4u@gmail.com>
 *  @LinkedIn https://www.linkedin.com/in/jan-boer-a24640113
 *  @version 1.0
 *  @link http://QBox4u.com
 *
 */ 

/******************
 *
 * Wiki and PHP variabeles and modes for developers.
 * These parameters can be adjusted and or modified for adjustments to your WIKI setup
 *
 * Default settings : 	
 */ 
# Enable debugmode for the FLEXIMAGE application
define("QBOX_FLEXIMAGE_DEBUG",TRUE);
# Disable debugmode for the FLEXIMAGE application
//define("QBOX_FLEXIMAGE_DEBUG",FALSE);
	
if (QBOX_FLEXIMAGE_DEBUG){
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	}
	
# The php BIN executable ( & path ) inside the QBox Server
# This path has to be checked on other servers as mutiple versions may reside in your own server !!!!!
# Perform for QNAP an [~] # ipkg list_installed and check if eg '''php - 5.2.17-2 - The php scripting language''' has been installed  
# An incorrect method for QNAP is ==>  [~] # which php    or <?php echo shell_exec('which php'); ? >
# An incorrect method for QNAP is ==>  [~] # whereis php  or <?php echo shell_exec('whereis php'); ? >
$wf_QBox_PHP_Bin 	= '/mnt/ext/opt/apache/bin/php';


$wf_QBox_FlexImage_SpecialPage = <<<QBoxFSP
{| width=100%
|-
|width=50%|
 [[Special:Contact|'''CONTACT''']] the designer of this extension regarding suggestions 
 press the envelope [[File:Mail-send.png|50px|link=Special:Contact]] or email directly to qbox4u@gmail.com
 [[File:Linkedinrebound.png|link=https://www.linkedin.com/in/jan-boer-a24640113]]
|width=50%|{{#Fimg:Teach 01.png|[[FlexImage_extension_demo_page]]}}
|}
 Check out the [[FlexImage_extension_demo_page]]

QBoxFSP;
 
$wf_QBox_FlexImage_Demo_Template = <<<QBoxFDT

{|style="font-size:100%"
| [[Image:MediaWiki-extensions-icon_02.png|90px|left]]
|QBox ABC Information template: 
'''This page contains QBox php Extension lessons'''<br>
<categorytree mode=pages depth=0>Category:QBox_Extension_lessons</categorytree>
|}<includeonly>
{{#ifeq:{{FULLPAGENAME}}|Template:QPE||{{#ifeq:{{FULLPAGENAME}}|Category:QBox template||[[Category:QBox_Extension_lessons]]}}}}</includeonly><noinclude>
== Use ==
*Add '''<nowiki>{{QPE}}</nowiki> ''' 
To any page that contains QBox php Extension lessons related data
 
[[Category:QBox_template]]

</noinclude>

QBoxFDT;

$wf_QBox_FlexImage_DemoPage = <<<QBoxFD

{{QPE}}
=Intro=
[http://QBox4u.com QBox4u] is an private charity project intended to provide [https://qbox4u.com:8081/conf/abc/mwk/index.php?title=Community_portal#Education Basic '''IT''' Beginner Lessons] and know-how in order to have an easy start with computers
  
This Demo Page is Initially Created By This Extension '''[[Special:Version|FlexImage]]''' during the first activation of the extension.

=Installation=

* Add at the end of [[MediaWiki:Common.css|MediaWiki:Common.css]] the following '''CSS class''' 
<source lang="html5">
/* Auto Resizable images by eg <div>[[Image:CusterTool_03.png]]</div>  */
/* Used by the Extension '''FlexImage''' TAG {{#Fimg:<image>|<link>}} */
.fleximg { 
     max-width: 100%; 
     height: auto; 
     width: auto\9; /* ie8 */ 
}
</source>
* Create in the <code>'''extensions'''</code> directory from Mediawiki an <code>new folder</code>
* Rename the <code>new folder</code> to <code>FlexImage</code>
* Copy the FlexImage extension source files into this folder
* Add at the end of <code>'''LocalSettings.php'''</code> the following <code>'''require_once'''</code> PHP line
<PRE>
//{{#Fimg:xxxxxx}}
 
require_once( "&#36;IP/extensions/FlexImage/FlexImage.php");
</PRE>
* Check in <code>'''[[Special:Version]] </code>under the head ''''''Other'''''' if the extension is activated
{| class="wikitable" style="white-space: nowrap;font-size:12px"
|-
!Extension!!Version!!License!!Description!!Authors
|-
|Flexible image||1.0||Private||Flexible image extension by the tag
Dynamically adapt the image size to the window

<nowiki>{{#Fimg:<Image File name>|<Page link>}}</nowiki> 
|https://www.linkedin.com/in/jan-boer-a24640113 and qbox4u@gmail.com
|}

= Adapt PHP Bin=
This demo extension is using additional internal scripts
 Check and assure that the right allocation of the PHP Bin Executable is used
 
 The definition of the default '''PHP Bin Executable location''' is set inside '''<code>FlexImage.inc.php</code>'''
 # The php BIN executable ( & path ) inside the QBox Server
 # This path has to be checked on other servers as mutiple versions may reside in your own server !!!!!
 # Perform for QBox4u QNAP an [~] # ipkg list_installed and check if eg '''php - 5.2.17-2 - The php scripting language''' has been installed  
 # An incorrect method for QBox4u QNAP is ==>  [~] # which php    or < ?php echo shell_exec('which php'); ? >
 # An incorrect method for QBox4u QNAP is ==>  [~] # whereis php  or < ?php echo shell_exec('whereis php'); ? >
 # The above commands will provide in this case only the default php version 
 # The default php BIN executable inside the QBox Server
 
 &#36;wf_QBox_PHP_Bin 	= '/mnt/ext/opt/apache/bin/php';

=Auto create pages=
 This extension creates automatically during setup the following pages

 * [[FlexImage_extension_demo_page]]
  
 * [[Special:FleximageAdmin]]
  
 * [[Template:QPE]]

 This extension will automatically upload during setup several default images 

=Usage=
The FlexImage extension is for QBox4u training purposes created, and has to be used as follow
 
Activating the FlexImage parser is done by 
 '''<nowiki>{{#Fimg:<image path & name>|<image Link>}}</nowiki>'''
===Image===
The image can be supplied from 2 sources
{| class="wikitable" style="white-space: nowrap;font-size:12px"
|* Internal QBox4u image ||'''<code>Raining-catsanddogs-01.gif</code>'''
|-
|* External image URL ||'''<code><nowiki>https://cdn.knmi.nl/knmi/map/current/weather/forecast/kaart_verwachtingen_Vandaag_dag.gif</nowiki></code>'''
|}

 When using <big>'''external images'''</big> The following should be available/added inside  <code>'''LocalSettings.php'''</code>
<PRE>
&#36;wgAllowExternalImages     = FALSE;
 
&#36;wgAllowExternalImagesFrom = array( 'http://www.cwb.gov.tw/','https://cdn.knmi.nl/knmi/map/current/weather/forecast/','qbox4u.com' );
</PRE>
The Extension <big>'''verify's'''</big> if an external image is available inside  '''<code><nowiki>&#36;wgAllowExternalImagesFrom</nowiki></code>'''

===Image Linking===
An Clickable image Link can be implemented from 2 sources
{| class="wikitable"
|* Internal QBox4u Page Link ||'''<code><nowiki>[[Sandbox]]</nowiki></code>'''
|-
|* External URL Link ||'''<code><nowiki>[http://knmi.nl/home]</nowiki></code>'''
|}
 An external Link <span style="color:red"><big>'''MUST'''</big></span> begin with '''<code>http://</code>'''
 
 Whenever an external Link is supplied without '''<code>http://</code>''', The FlexImage extension will automatically
 
 ADD in front of your input the code '''<code>http://</code>''' but no functionality assurance is provided  
 
 EG: '''<code><nowiki>[qbox4u.com]</nowiki></code>''' becomes '''<code><nowiki>[http://qbox4u.com]</nowiki></code>'''

=Demo=
 Assuming, we want to have 3 images covering the entire horizontal screen
 
 Independent of the browser width
 
 <big>'''Changing the width of your browser page will automatically adapt the image size'''</big>   
<source lang="php">
{|class="wikitable mw-collapsible" width=99%
! Holland!!Taiwan!!Observation
|-
|width=33%|{{#Fimg:https://cdn.knmi.nl/knmi/map/current/weather/forecast/kaart_verwachtingen_Vandaag_dag.gif|[http://knmi.nl/home]}}
|width=33%|{{#Fimg:http://www.cwb.gov.tw/V7/observe/real/Data/Real_Image.png|[[Main_Page#Practice_Foreign_Words]]}}
|width=33%|{{#Fimg:Raining-catsanddogs-01.gif|[qbox4u.com]}}
|}

* The image Holland is by mouse click linking to the external web page [http://knmi.nl/home]
* The image Taiwan is by mouse click linking to the internal web page [[Main_Page#Practice_Foreign_Words]]
* The image Observation is by mouse click linking to the external web page [qbox4u.com]
</source>
{|class="wikitable mw-collapsible" width=99%
! Holland!!Taiwan!!Observation
|-
|width=33%|{{#Fimg:https://cdn.knmi.nl/knmi/map/current/weather/forecast/kaart_verwachtingen_Vandaag_dag.gif|[http://knmi.nl/home]}}
|width=33%|{{#Fimg:http://www.cwb.gov.tw/V7/observe/real/Data/Real_Image.png|[[Main_Page#Practice_Foreign_Words]]}}
|width=33%|{{#Fimg:Raining-catsanddogs-01.gif|[qbox4u.com]}}
|}

QBoxFD;



?>