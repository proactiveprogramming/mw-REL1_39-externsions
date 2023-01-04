# NaylorAMS
Authentication extension for Naylor AMS

## Installation
This extension requires PluggableAuth.

```
wfLoadExtension( 'NaylorAMS' );
$wgNaylorAMS_BaseUrl = 'https://secure.membershipsoftware.org/NAME_HERE'; // do not add trailing slash
$wgNaylorAMS_SecurityKey = '';
$wgNaylorAMS_UsernameDenyList = ['Bob']; // used to prevent login via SSO to local-only accounts. Case sensitive; needs to follow MediaWiki username conventions
```
