<?php

	#########################################################################
	#
	#	CONFIGURATION VARIABLES
	#
	#	CHANGE THE FOLLOWING VARIABLES TO YOUR REQUIREMENTS
	#
	#########################################################################
	
	#URL For your wiki
	#Enter the url for your wiki.
	#
	#If you do not use short-urls, you must enter the url like this:
	#"http://www.yourdomain.com/wiki/index.php?title="
	#
	#If short-urls are used, you must append a trailing slash to the end of the url, like such:
	#"http://www.yourdomain.com/wiki/" 
	$WIKI_URL = "http://www.yourdomain.com/wiki/index.php?title=";
	
	#SMTP settings
	$SMTP_HOST = 'smtp.yourdomain.com';
	$SMTP_PORT = '25';	#standard smtp port, change if different
	$SMTP_AUTHORIZATION = false; #set to true if required then fill in username, password
	$SMTP_USERNAME = '';
	$SMTP_PASSWORD = '';

	#By default, email is sent from the emergency contact email stored in localsettings.php
	#Leave blank, '', if you want the from field to equal the to field
	$EMAIL_FROM = '';
	
	#change subject if desired
	$EMAIL_SUBJECT = "Your category subscriptions";

	#By defaul, this script is designed to run everyday and gather updates that
	#occured yesterday.  This time frame can be changed if you wish.
	$YESTERDAY = date("Ymd",mktime(0,0,0,date("m"),date("d")-1,date("Y")));

	#########################################################################
	#
	#	End configuration variables
	#
	#########################################################################



	#
	#FILE INCLUDES
	#
	
	require_once("Mail.php");	#Mail package in PEAR
	require_once( 'commandLine.inc' );
	
	#
	#GLOBAL VARIABLES
	#
	global $wgDBprefix;
	global $wgEmergencyContact;
	
	
	#
	#BEGIN script stuff
	#
	
	
	#EMAIL CONNECTION CONFIGURATION
	$smtp = Mail::factory('smtp',
  		array ('host' => $SMTP_HOST,
    		'port' => $SMTP_PORT,
    		'auth' => $SMTP_AUTHORIZATION,
    		'username' => $SMTP_USERNAME,
    		'password' => $SMTP_PASSWORD
    		
    	)
    );
	
	
	#slave database handle
	$dbr =& wfGetDB( DB_SLAVE );	
	
	#collect distinct user ids
	$user_ids = $dbr->select('category_subscriptions', array('user_id'), NULL, NULL, array('DISTINCT'));
	
	while($user = $user_ids->fetchRow() )
	{
		#collect user's subscribed categories
		$categories = $dbr->select('category_subscriptions', array('category'), array("user_id = '".$user["user_id"]."'") );
		
		
		#build the email
		$email_body =	"<style type=\"text/css\">table {  width: 80%; border-width: 0px; " . 
						"border-style: none;  border-color: gray; border-collapse: collapse; " . 
						"background-color: white; } table th { border-width: 1px; padding: 2px; " . 
						"border-style: solid; border-color: gray; background-color: white; " . 
						"-moz-border-radius: ;} table td { border-width: 1px; padding: 2px; border-style: solid; " . 
						"border-color: gray; background-color: white; -moz-border-radius: ; }</style> \n" . 
						"Your category subscriptions: \n" . 
						"<p><table><th>Category</th><th>New Pages</th><th>Updated Pages</th> \n";

		while($category = $categories->fetchRow() )
		{
			$new_pages = array();
			$updated_pages = array();
			
			#get updated pages
			$result = $dbr->select(array('categorylinks', 'page'), array('page_title'), array("cl_from = page_id", "LEFT(page_touched, 8) = '".$YESTERDAY."'", "cl_to = '".$category["category"]."'", "page_is_new = '0'"));
			while($row = $result->fetchRow() )
			{
				$updated_pages[] = $row["page_title"];
			}
			
			#get new pages
			$result = $dbr->select(array('categorylinks', 'page'), array('page_title'), array("cl_from = page_id", "LEFT(page_touched, 8) = '".$YESTERDAY."'", "cl_to = '".$category["category"]."'", "page_is_new = '1'"));
			while($row = $result->fetchRow() )
			{
				$new_pages[] = $row["page_title"];
			}
			
			
			$email_body .= '<tr><td>'.$category["category"].'</td><td align="center">';
			if (count($new_pages) > 0 )
			{
				foreach($new_pages as $new){
					$email_body .= '<a href="'.$WIKI_URL.'">'.$new.'</a><br />';
				}
			}
			else
			{
				$email_body .= " - ";
			}
			
			$email_body .= '</td><td align="center">';
			if (count($updated_pages) > 0 )
			{
				foreach($updated_pages as $update){
					$email_body .= '<a href="'.$WIKI_URL.$update.'">'.$update.'</a><br />';
				}
			}
			else
			{
				$email_body .= " - ";
			}
			
			$email_body .= "</td></tr> \n";
		}
		
		$email_body .= "\n </table>";
		
		
		#put email together
		
		#first get the user's email out of the database
		$result = $dbr->select('user', array('user_name', 'user_email'), array("user_id = '".$user["user_id"]."'") );
		$usr = $result->fetchRow();
		$EMAIL_TO = $usr["user_email"];
		
		if ($EMAIL_FROM == '')
			$EMAIL_FROM = $EMAIL_TO;
		
		
		#build headers
		$headers = array ('From' => $EMAIL_FROM, 'To' => $EMAIL_TO, 'Subject' => $EMAIL_SUBJECT, 'Content-type' => 'text/html');
		
		#Send the email
		$mail = $smtp->send($EMAIL_TO, $headers, $email_body);
	
	
		#Debugging output
		#Uncomment if you want confirmation of email sent printed to console, piped to a log file, etc
		#
		#Use the $usr object above if you wish to customize the output of the error message
		#to include the user's username.
		/**
		if (PEAR::isError($mail)) {
			echo("<p>" . $mail->getMessage() . "</p>");
		} else {
			echo("<p>Message successfully sent!</p>");
		}
		*/
	}
?>