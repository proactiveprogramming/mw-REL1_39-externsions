<?php
/**
 * Internationalisation for EmailDeletedPages
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();

/** English
 * @author Leucosticte
 */
$messages['en'] = array(
	'emaildeletedpages-desc' => 'Gives users the option of receiving an emailed copy of deleted pages they created.',
        'emaildeletedpages-email-subject' => '$1 was deleted from $2',
        'emaildeletedpages-email-opening' => '$1<$2> deleted $3, a page you created. The deletion reason given was:

$4

',
        'emaildeletedpages-email-body-intro' => 'Here is the text of the most recent revision made to the page before it was deleted:

',
        'emaildeletedpages-email-bodyintro-fallback' => 'The most recent revision made to the page before it was deleted was revisiondeleted and therefore
could not be provided to you. Here is the text of the most recent revision that you made to the page:',
        'emaildeletedpages-text-samerev-unavailable' => 'The most recent revision made to the page, which was made by you, was revisiondeleted
and therefore could not be provided to you.',
        'emaildeletedpages-email-text-unavailable' => 'Both the most recent revision made to the page and the most recent revision made to the
page by you were revisiondeleted and therefore could not be provided to you.',
        'emaildeletedpages-email-closing' => '

You are receiving this email because you opted in to be emailed when a page you created
is deleted. To unsubscribe from these emails, visit the User profile tab of
Special:Preferences and change the setting.
<{{canonicalurl:{{#special:Preferences}}}}>',
        'tog-emaildeletedpages' => 'Email me when a page I created is deleted',
);

/** Message documentation
 * @author Leucosticte
 */
$messages['qqq'] = array(
	'emaildeletedpages-desc' => '{{desc}}',
        'emaildeletedpages-email-subject' => 'The subject line of the email that gets sent to the user informing him of what page was deleted from what wiki
Parameters:
* $1 is the title of the deleted page
* $2 is the wiki name',
        'emaildeletedpages-email-opening' => 'The beginning line of the email that gets sent to the user when a page he created is deleted
Parameters:
* $1 is the user name of the user who deleted the page
* $2 is the URL of the talk page of the deleting user
* $3 is the title of the deleted page
* $4 is the deletion reason',
        'emaildeletedpages-email-body-intro' => 'The message informing the user that what will follow is the text of the most recent revision that was made before the page was deleted.',
        'emaildeletedpages-email-bodyintro-fallback' => 'The message informing the user that the most recent revision made to the page before it was deleted was revisiondeleted and therefore
he will instead be given the text of the most recent revision that the user made to the page',
        'emaildeletedpages-text-samerev-unavailable' => 'The message informing the user that the most recent revision made to the page, which was made by him, was revisiondeleted and
therefore could not be provided to him',
        'emaildeletedpages-email-text-unavailable' => 'The message informing the user that both the most recent revision made to the page and the most recent revision made to the page by him
were revisiondeleted and therefore could not be provided to him',
        'emaildeletedpages-email-closing' => 'The message informing the user that he is receiving this email because he opted in to be emailed when a page he created is deleted; and that to
unsubscribe from these emails, he can visit the User profile tab of Special:Preferences and change the setting',
        'tog-emaildeletedpages' => 'The preferences toggle to have the user be emailed when when a page he created is deleted',
);
