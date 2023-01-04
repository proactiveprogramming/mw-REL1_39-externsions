# Mediawiki-FlowdockNotify
A MediaWiki Extension for notifying your [Flowdock](https://www.flowdock.com) [team inbox](https://www.flowdock.com/help/team_inbox) on article creation, revision and deletion.

# Author
Tim Noise <tim@drkns.net>
https://github.com/dnoiz1/Mediawiki-FlowdockNotify

# Installation
- ```cd /path/to/mediawiki```
- ```git clone https://github.com/dnoiz1/Mediawiki-FlowdockNotify.git extensions/FlowdockNotify```
- Create a new source for your flow:
  1. Create a Flowdock [Developer application](https://www.flowdock.com/oauth/applications) with the following settings:
    - Name: `MediaWiki`
    - OAuth Redirect URI: `urn:ietf:wg:oauth:2.0:oob`
    - Setup URI: *blank*
    - Configuration URI: *blank*
    - Small icon: The [MediaWiki logo icon](/mediawiki-icon.png?raw=true) or any other 128x128 sized transparent PNG that'll be displayed in the team inbox for each activity message.
    - Large icon: *blank*
  2. Once the application is created, create a new source from the developer application's page with the following settings:
    - Source name: *your wiki's name*
    - Flow: The desired destination flow.
  3. Click on Generate Source. **Copy the source token that's displayed once the source is created.** Use this token as the `$flowdock_token` in the following step.
- Add to LocalSettings.php
```php
$flowdock_token = "YourFlowdockSourceToken";
require_once("$IP/extensions/FlowdockNotify/FlowdockNotify.php");
```

# Uninstalling
Remove or comment out the following from LocalSettings.php:

```php
$flowdock_token = "YourFlowdockSourceToken";
require_once("$IP/extensions/FlowdockNotify/FlowdockNotify.php");
```
