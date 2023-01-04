<?php
/**
 * Hooks for MwSSE extension
 *
 * @file
 * @ingroup Extensions
 */

class MwSSEHooks {

    public static function onlogBadLogin( $ret, $user, $userName ){

		// Database credentials (LocalSettings.php)
		global $wgDBserver;
		global $wgDBuser;
		global $wgDBname;
		global $wgDBpassword;

		// Data to send email (LocalSettings.php)
		global $wgMetaNamespace;
		global $wgEmergencyContact;

        	if( $ret->status === "FAIL" ) {
	        	$wgEmailSubject = "$wgMetaNamespace login ERROR";
		} else {
			$wgEmailSubject = "$wgMetaNamespace login OK";
		}
	
	     	$conn = new mysqli($wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname);
	   
		$sql = "SELECT user_email FROM user WHERE lower(user_name)=lower('$userName')";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();

		$email = $row['user_email'];

		error_log("[MwSSE - Extension] $userName: $ret->status email FROM: $wgEmergencyContact TO: $email");

		if(filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR') == "") { // Direct connection
        		$body = "Login attempt! $userName LOGIN from IP ".filter_input(INPUT_SERVER, 'REMOTE_ADDR');
			
	        } else {    // Connection through Reverse-Proxy
            		$body = "Login attempt! $userName LOGIN from IP ".filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR');
        	}

		$headers = "From:".$wgEmergencyContact."\r\n";
		$mailstatus = mail($email, $wgEmailSubject, $body, $headers);
	    
		return false;
    }
}
