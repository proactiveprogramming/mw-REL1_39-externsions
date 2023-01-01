# Semantic Tasks
[![Build Status](https://travis-ci.org/SemanticMediaWiki/SemanticTasks.svg?branch=master)](https://travis-ci.org/SemanticMediaWiki/SemanticTasks)
[![Code Coverage](https://scrutinizer-ci.com/g/SemanticMediaWiki/SemanticTasks/badges/coverage.png?s=c5563fd91abeb49b37a6ef999198530b6796dd3c)](https://scrutinizer-ci.com/g/SemanticMediaWiki/SemanticTasks/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/SemanticMediaWiki/SemanticTasks/badges/quality-score.png?s=9cc8ce493f63f5c2c22db71b2061b4b8c21f43ba)](https://scrutinizer-ci.com/g/SemanticMediaWiki/SemanticTasks/)
[![Latest Stable Version](https://poser.pugx.org/mediawiki/semantic-tasks/version.png)](https://packagist.org/packages/mediawiki/semantic-tasks)
[![Packagist download count](https://poser.pugx.org/mediawiki/semantic-tasks/d/total.png)](https://packagist.org/packages/mediawiki/semantic-tasks)

Semantic Tasks (a.k.a. ST) is a [Semantic Mediawiki][smw] extension that provides task notification
and reminder emails with the help of semantic annotations.

In contrast to the built in notification systems in MediaWiki (watching pages), this extension can be used to trigger notifications without user interaction with the MediaWiki system, e. g. by filling in a form (using the PageForms extension) where a user name is entered in a form and in the resulting template the respective properties are set to trigger a email notification (see below). 

## Requirements

 - PHP 7.3 or later
 - MediaWiki 1.35 or later
 - Semantic MediaWiki 3.2 or later

## Installation

The recommended way to install Semantic Tasks is using [Composer](http://getcomposer.org) with
[MediaWiki's built-in support for Composer](https://www.mediawiki.org/wiki/Composer).

Note that the required extension Semantic MediaWiki must be installed first according to the installation
instructions provided.

### Step 1

Change to the base directory of your MediaWiki installation. If you do not have a "composer.local.json" file yet,
create one and add the following content to it:

```
{
	"require": {
		"mediawiki/semantic-tasks": "~2.0"
	}
}
```

If you already have a "composer.local.json" file add the following line to the end of the "require"
section in your file:

    "mediawiki/semantic-tasks": "~2.0"

Remember to add a comma to the end of the preceding line in this section.

### Step 2

Run the following command in your shell:

    php composer.phar update --no-dev

Note if you have Git installed on your system add the `--prefer-source` flag to the above command.

### Step 3

Add the following line to the end of your "LocalSettings.php" file:

    wfLoadExtension( 'SemanticTasks' );

### Step 4
You must run a cron job e.g. once a day to trigger the reminders to be sent by e-mail. To do so add the
following line to your crontab to execute the respective script every day at 12:

```
0 12 * * * php /path/to/SemanticTasks/maintenance/checkForReminders.php
```

### Step 5

It is possible to adapt the names of the properties for your wiki via configuration in
the "LocalSettings.php" file. This is an optional step if you would like to use different
property names. This is the list of default settings:

* `$stgPropertyAssignedTo = 'Assigned to';`
* `$stgPropertyCarbonCopy = 'Carbon copy';`
* `$stgPropertyTargetDate = 'Target date';`
* `$stgPropertyReminderAt = 'Reminder at';`
* `$stgPropertyStatus = 'Status';`
* `$stgPropertyAssignedToGroup = 'Assigned to group';`
* `$stgPropertyHasAssignee = 'Has assignee';`

## Configuration

* `SemanticTasksNotifyIfUnassigned` - (default: `false`) If set to `true` will notify
  users being unassigned from tasks
* `$stgNotificationFromSystemAddress` - (default: `false`) If set to `true` will use
  `$wgSiteName` and `$wgPasswordSender` for notification emails From field

## Usage

### Notification emails
They are sent as soon a page is saved. The system looks for the `[[Assigned to::*]]`, `[[Carbon copy::*]]` and/or `[[Assigned to group::*]]` properties.

### Groups
Create a group page with properties `[[Has assignee::*]]` and then assign the group to the task with `[[Assigned to group::*]]` property.

### Status
Set Status to `[[Status::Closed]]` to inform assignees that the task has been closed and to disable notifications and reminders.

### Reminder emails
Once the script execution is triggered via cron the software looks for the `[[Reminder at::*]]` and the `[[Target date::*]]` property. It then sends reminders to all the assignees.


## Contribution and support

If you want to contribute work to the project please subscribe to the developers mailing list and
have a look at the contribution guideline.

* [File an issue](https://github.com/SemanticMediaWiki/SemanticCite/issues)
* [Submit a pull request](https://github.com/SemanticMediaWiki/SemanticCite/pulls)
* Ask a question on [the mailing list](https://www.semantic-mediawiki.org/wiki/Mailing_list)

## Version history
See [release notes](docs/RELEASE-NOTES.md).

## Credits

Semantic Tasks was initially created by Steren Giannini for Creative Commons. Later it was sponsored by KDZ - Centre for Public Administration Research, with most upgrade work done by [Peter Grassberger](https://github.com/PeterTheOne).
Currently it is sponsored by [KM-A Knowledge Management Associates](https://km-a.net/knowledge-wiki/), with most upgrade work done by [thomas-topway-it](https://github.com/thomas-topway-it).


## License

[GNU General Public License, version 2 or later][gpl-licence], see [COPYING](COPYING) file.

[smw]: https://github.com/SemanticMediaWiki/SemanticMediaWiki
[gpl-licence]: https://www.gnu.org/copyleft/gpl.html
