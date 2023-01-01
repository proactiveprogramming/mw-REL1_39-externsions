<?php
require_once( 'commandLine.inc' );

global $wgParser;

#echo header
echo "\n";
echo "Category Subscriptions Installation \n \n";

$dbr =& wfGetDB( DB_MASTER );
if ($dbr->tableExists( "category_subscriptions" ))
{
	echo "Table already exists.  No action has been taken. \n";
}
else
{
	echo "Table does not exist. \n\n";
	echo "Enter Y to install the table:";
	$answer = fread(STDIN, 1);
	echo "\n\n";
	if ($answer == "Y" || $answer == "y")
	{
		$sql = "CREATE TABLE ".$wgDBprefix."category_subscriptions ( id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT(10) UNSIGNED NOT NULL DEFAULT '0', category VARCHAR(255) NOT NULL DEFAULT '', PRIMARY KEY (id));";
		if( $dbr->query($sql, __METHOD__) )
		{
			echo "Table created successfully! \n";
		}
		else
		{
			echo "An error occured while creating the table. \n";
			echo "Please try agian or check your database for errors.";
		}
	}
	else
	{
		echo "No action was performed. \n";	
	}
}
?>