# UserHistory MediaWiki Extension
UserHistory is an extension to the MediaWiki software that keeps track of all articles accessed by a logged in user.
It creates an additional Special page in which you can find all your previously visited articles.

## Purpose
You know that time when you read a fascinating fact but completely forgot the article name and where you read it? No more! Add a simple UserHistory extension to your wiki portal and your history will be accessible in a single place.

For more information, please see https://www.mediawiki.org/wiki/Extension:UserHistory

## Download
Using git directly:
```
cd extensions/
git clone https://github.com/rexem/UserHistory.git
```

Alternatively download the source code directly from GitHub:
https://github.com/rexem/UserHistory/archive/master.zip

Extract the contents, rename the resulting directory to UserHistory, and add it to your MediaWiki extensions folder.

## Installation
Once downloaded and located in you 'extensions' directory, add the following code to your [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php) file:
```
wfLoadExtension( 'UserHistory' );
```

## Issues and Feedback
Any issues could be reported on the GitHub issue tracking system: https://github.com/rexem/UserHistory/issues
Alternatively on the https://www.mediawiki.org/wiki/Extension:UserHistory talk page

## Contributing
Please create a pull request against UserHistory [GitHub](https://github.com/rexem/UserHistory) repository using the latest available code.
Please make sure your code complies with MediaWiki PHPSniffer definition and other [coding conventions](https://www.mediawiki.org/wiki/Manual:Coding_conventions).