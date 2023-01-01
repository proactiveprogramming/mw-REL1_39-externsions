# ITaskTracker
iTask list to manage daily activity and project list for each members of your Team.

Download and place the file(s) in a directory called ITaskTracker in your extensions/ folder.

Add the following code at the bottom of your LocalSettings.php:

require_once "$IP/extensions/ITaskTracker/ITaskTracker.php";

$wgCurrentDir    = dirname(__FILE__);

$wgGroupPermissions['itaskowner']['itaskowner'] = true;

$wgGroupPermissions['itaskapprover']['itaskapprover'] = true;

$wgGroupPermissions['itaskcoordinator']['itaskcoordinator'] = true;

$wgGroupPermissions['itaskarchiver']['itaskarchiver'] = true;

$wgGroupPermissions['itaskurgent']['itaskurgent'] = true;

$wgMaxUrgentTask = 5 ;

$wgMaxHighTask   = 15;

$wgMaxMediumTask = 50;

$wgMaxLowTask    = 0 ;

# create new file Local.php containing database config :

$wgDBtype = "mysql";

$wgDBserver = "1.1.1.1";

$wgDBname = "wikiDBdev";

$wgDBuser = "user_db";

$wgDBpassword = "password_db";

# define file above in LocalSettings.php as per below :

require_once 'Local.php';
