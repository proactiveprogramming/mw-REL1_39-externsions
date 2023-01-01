<?php
/******************
 *  variable Extension - this extension is an QBox Special:SpecialPages extention
 *  Copyright (C) 2016 qbox4u.com <qbox4u@gmail.com>
 *
 *  Implements Special:FlexImage
 *
 *  This program is not free software therefore you can-not redistribute 
 *  it and/or modify it under the terms of the GNU General Public License 
 *  as published by the Free Software Foundation; either version 2 of the 
 *  License, or (at your option) any later version.
 *
 *  Please consult and/or request the administrator of QBox4u 
 *  to use the information and samples
 *  To copy the data an written autorisation of the developer as stated in 
 *  the $wgExtensionCredits is required 
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
class SpecialFleximage extends SpecialPage {
	function __construct() {
		parent::__construct( 'FleximageAdmin' );
	}

	function execute( $par ) {
		global $wgTitle, $wgUser,$wf_QBox_FlexImage_SpecialPage,$wf_QBox_PHP_Bin,$IP;
		
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
 
		# Start of area that is reserved for your Special Page application
		# Do your Special Page stuff 
		# Do php stuff .. some examples
		
		# ... Default Admin page
		$output->addWikiText($wf_QBox_FlexImage_SpecialPage);

		# ...Import default FlexImage images eg Active_No.png Active_Yes.png
		$ImportImagesScript = $IP.'/maintenance/importImages.php';
		$ImportImagesFrom 	= $IP.'/extensions/FlexImage/resources/';
		$ImageUploadCommand	= '--comment="default imported" --user="Admin" --search-recursively png jpg gif bmp PNG JPG GIF BMP';
		$cmd = $wf_QBox_PHP_Bin.' '.$ImportImagesScript.' '.$ImportImagesFrom.' '.$ImageUploadCommand;
		$Shell_Result = shell_exec($cmd);
		
		$Script_output  = "\n";
		$Script_output .= '{|class="wikitable mw-collapsible mw-collapsed"'."\n";
		$Script_output .= '!Importing images script logging'."\n";
		$Script_output .= '|-'."\n";
		$Script_output .= "| $Shell_Result "."\n";
		$Script_output .= '|}'."\n";
		
		// Check for an error during upload. The ^$ is checing an empty returned string when php is not operable
		if (preg_match('/Warning|Failed|Error|suitable|Could not open|^$/i', $Shell_Result)) {
				if ( preg_match('/^$/i', $Shell_Result)) {
					$Result_img = 'Active_No.png'; 
					$output->addWikiText(" [[File:$Result_img]] " . $this->msg( "fleximage_upldefimg_nok" )->parse() ."<br><br>&#09;<code>$ImportImagesFrom</code>" );
					$output->addWikiText( $this->msg( "fleximage_upldefimg_noresponce" )->parse() );
					$output->addWikiText($Script_output);}
				else{
					$Result_img = 'Active_No.png'; 
					$output->addWikiText(" [[File:$Result_img]] " . $this->msg( "fleximage_upldefimg_nok" )->parse() ."<br><br>&#09;<code>$ImportImagesFrom</code>" );
					$output->addWikiText($Script_output);} 
			}
			else { 
				$Result_img = 'Active_Yes.png'; 
				$output->addWikiText(" [[File:$Result_img]] " . $this->msg( "fleximage_upldefimg_ok" )->parse() ."<br><br>&#09;<code>$ImportImagesFrom</code>" );
				$output->addWikiText($Script_output);
			}
		
		# ...User name
		$a = $wgUser->getName();
		
		# ...messages		
		$SpecialPageinfo = "Hello user:$a !<br>";
		$SpecialPageinfo .= $this->msg( 'fleximage_welcome' )->parse().'<br>';		
		$output->addWikiText( $SpecialPageinfo );
		
		// Create an help page to mediawiki
		//$this->addHelpLink( 'Help:Extension:MyExtension' );
		//$this->addHelpLink( 'FlexImage_extension_demo_page' ); 
		
		// Whenever you want to stop, you can use this as an error screen
		//$output->showErrorPage( 'error', 'fleximage_welcome' ,  array( 'param1', 'param2' ) );
		
		
		# ...images		
		//$SpecialPageinfo = "[[File:Active_No.png]][[File:Active_Yes.png]]";	
		//$output->addWikiText( $SpecialPageinfo );
		
		# ...HTML text
		//$output->addHTML('<b>This is not a pipe...</b>');
		
		# WebRequest->getVal($key)
		# wfGetDB()
		# User->isAllowed($right)
		# ...Parced text	
		//$output->addWikiText("This is some ''lovely'' [[Main Page]] that will '''get''' parsed nicely.");
		
		
		

// Testing ************************************	


		// Finding php in /mnt/ext/opt/apache/bin/php (/opt/bin/php is old one) is more difficut
		// $cmd = "which php"; # functioning only as admin and not as http-user
		// $cmd = 'php -i';    # functioning only as admin and not as http-user

		//$cmd = 'whoami';
		//$cmd = 'ls';
		//$cmd = 'su - admin  2>&1 1> /dev/null; echo $?';
		//$cmd = $wf_QBox_PHP_Bin.' '.'/share/Web/VHost_qbox4u/conf/abc/mwk/maintenance/deleteOldRevisions.php  --delete';
		//$cmd = 'which php  2>&1 1> /dev/null; echo $?';
		//$cmd = 'type -a php 2>&1 1> /dev/null; echo $?';
		//$a = '$cmd:'.$cmd."<br>";
		//$aa = shell_exec($cmd);
		//$output->addWikiText($a.$aa);

		

		//$cmd = MW_INSTALL_PATH;
		//$cmd = $IP;
		//$cmd = PHP_BINDIR;
		//$cmd = $_SERVER["PATH"];
		//$output->addWikiText($cmd);
		
		//http://www.tecmint.com/execute-php-codes-functions-in-linux-commandline/
		
		# WebRequest->getVal($key)
		# wfGetDB()
		# User->isAllowed($right)
		# ...
		
		
// Testing ************************************

	}
}
 

?>