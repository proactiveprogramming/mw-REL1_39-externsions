<?php
// Global profile namespace reference
define( 'NS_USER_PROFILE', 202 );
define( 'NS_USER_WIKI', 200 );

// Show user avatars in diffs?
$wgUserProfileAvatarsInDiffs = false;

/**
 * If you want to require users to have a certain number of certain things, like
 * five edits or three friends or two comments or whatever (is supported by
 * SocialProfile/the user_stats DB table) before they can use Special:UpdateProfile,
 * use this global.
 *
 * For example, to require a user to have five edits before they're allowed to access
 * Special:UpdateProfile, set:
 * @code
 * $wgUserProfileThresholds = [ 'edits' => 5 ];
 * @endcode
 *
 * To require both ten edits *and* three friends, set:
 * @code
 * $wgUserProfileThresholds = [ 'edits' => 10, 'friend-count' => 3 ];
 * @endcode
 */
$wgUserProfileThresholds = [
/**
 * All currently "supported" options (supported meaning that there is i18n support):
 * edits // normal edits in the namespaces that earn you points ($wgNamespacesForEditPoints)
 * votes // [[mw:Extension:VoteNY]] votes
 * comments // [[mw:Extension:Comments]] comments
 * comment-score-plus // [[mw:Extension:Comments]] upvoted comments
 * comment-score-minus // [[mw:Extension:Comments]] downvoted comments
 * recruits // recruits; see [[mw:Extension:NewSignupPage]]
 * friend-count // friends
 * foe-count // foes
 * weekly-wins // @see /UserStats/GenerateTopUsersReport.php
 * monthly-wins // @see /UserStats/GenerateTopUsersReport.php
 * poll-votes // [[mw:Extension:PollNY]] votes
 * picture-game-votes // [[mw:Extension:PictureGame]] votes
 * quiz-created // [[mw:Extension:QuizGame]] created quizzes
 * quiz-answered // [[mw:Extension:QuizGame]] answered quizzes in total
 * quiz-correct // [[mw:Extension:QuizGame]] correctly answered quizzes
 * quiz-points // [[mw:Extension:QuizGame]] points in total
 */
];

// Default setup for displaying sections
$wgUserPageChoice = true;

$wgUserProfileDisplay['friends'] = false;
$wgUserProfileDisplay['foes'] = false;
$wgUserProfileDisplay['gifts'] = true;
$wgUserProfileDisplay['awards'] = true;
$wgUserProfileDisplay['profile'] = true;
$wgUserProfileDisplay['board'] = false;
$wgUserProfileDisplay['stats'] = false; // Display statistics on user profile pages?
$wgUserProfileDisplay['interests'] = true;
$wgUserProfileDisplay['custom'] = true;
$wgUserProfileDisplay['personal'] = true;
$wgUserProfileDisplay['activity'] = false; // Display recent social activity?
$wgUserProfileDisplay['userboxes'] = false; // If FanBoxes extension is installed, setting this to true will display the user's fanboxes on their profile page
$wgUserProfileDisplay['games'] = false; // Display casual games created by the user on their profile? This requires three separate social extensions: PictureGame, PollNY and QuizGame

$wgUpdateProfileInRecentChanges = false; // Show a log entry in recent changes whenever a user updates their profile?
$wgUploadAvatarInRecentChanges = false; // Same as above, but for avatar uploading

$wgAvailableRights[] = 'avatarremove';
$wgAvailableRights[] = 'editothersprofiles';
$wgAvailableRights[] = 'editothersprofiles-private';
$wgAvailableRights[] = 'populate-user-profiles';
$wgGroupPermissions['sysop']['avatarremove'] = true;
$wgGroupPermissions['staff']['editothersprofiles'] = true;
$wgGroupPermissions['staff']['editothersprofiles-private'] = true;
$wgGroupPermissions['staff']['populate-user-profiles'] = true;

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.socialprofile.userprofile.css'] = [
	'styles' => 'resources/css/UserProfile.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

$wgResourceModules['ext.socialprofile.userprofile.js'] = [
	'scripts' => 'resources/js/UserProfilePage.js',
	'messages' => [ 'user-board-confirm-delete' ],
	'dependencies' => [ 'mediawiki.api', 'mediawiki.util' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile',
];

// Modules for Special:EditProfile/Special:UpdateProfile
$wgResourceModules['ext.userProfile.updateProfile'] = [
	'scripts' => 'resources/js/UpdateProfile.js',
	'dependencies' => [ 'mediawiki.api', 'mediawiki.util', 'jquery.ui' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

// CSS for user avatars in page diffs
$wgResourceModules['ext.socialprofile.userprofile.diff'] = [
	'styles' => 'resources/css/AvatarsInDiffs.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

$wgResourceModules['ext.socialprofile.userprofile.tabs.css'] = [
	'styles' => 'resources/css/ProfileTabs.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

$wgResourceModules['ext.socialprofile.special.uploadavatar.css'] = [
	'styles' => 'resources/css/SpecialUploadAvatar.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

$wgResourceModules['ext.socialprofile.special.uploadavatar.js'] = [
	'scripts' => 'resources/js/UploadAvatar.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

$wgResourceModules['ext.socialprofile.special.updateprofile.css'] = [
	'styles' => 'resources/css/SpecialUpdateProfile.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

// styles for <randomfeatureduser> tag
$wgResourceModules['ext.socialprofile.userprofile.randomfeatureduser.styles'] = [
	'styles' => 'resources/css/RandomUsersWithAvatars.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'SocialProfile/UserProfile'
];

# Add new log types for profile edits and avatar uploads
global $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActions;
$wgLogTypes[]                    = 'profile';
$wgLogNames['profile']           = 'profilelogpage';
$wgLogHeaders['profile']         = 'profilelogpagetext';
$wgLogActions['profile/profile'] = 'profilelogentry';

$wgLogTypes[]                    = 'avatar';
$wgLogNames['avatar']            = 'avatarlogpage';
$wgLogHeaders['avatar']          = 'avatarlogpagetext';
$wgLogActions['avatar/avatar'] = 'avatarlogentry';

$wgHooks['ArticleFromTitle'][] = 'UserProfileHooks::onArticleFromTitle';
$wgHooks['TitleIsAlwaysKnown'][] = 'UserProfileHooks::onTitleIsAlwaysKnown';
$wgHooks['OutputPageBodyAttributes'][] = 'UserProfileHooks::onOutputPageBodyAttributes';
$wgHooks['ParserFirstCallInit'][] = 'UserProfileHooks::onParserFirstCallInit';
$wgHooks['DifferenceEngineShowDiff'][] = 'UserProfileHooks::onDifferenceEngineShowDiff';
$wgHooks['DifferenceEngineOldHeader'][] = 'UserProfileHooks::onDifferenceEngineOldHeader';
$wgHooks['DifferenceEngineNewHeader'][] = 'UserProfileHooks::onDifferenceEngineNewHeader';
