# Share
Add links/buttons to the sidebar to allow users to share your wiki's articles on Facebook, Twitter, LinkedIn, Tumblr, Reddit and via email.

Written by Agent Isai, mainly for Miraheze

Licensed under GPL 3.0 or later.

# Installation
As with most MediaWiki installations, just simply clone this repository into your `extensions` folder:

`git clone https://github.com/AgentIsai/Share`

# Configuration
Share adds various new configurations which allows you to select what platform sharing links you want in the sidebar and how you want the sidebar to look.

Currently, there are 3 'modes' you can configure to change the look of Share:
1. Full mode (Default) - This mode loads a share button directly from each platform's social plugin library. This might not be the most privacy friendly method to use as it loads scripts from all your enabled platforms. Platforms without a "share" button script (such as Reddit or email) are not usable in this mode. 
2. Basic buttons mode - This mode displays colorful "Share" buttons that users can click to share your articles on social media platforms. This does not load any scripts from social media platforms and is privacy-friendly.
3. Plain links mode - This mode displays all the share links as plain sidebar links, no different from sidebar links such as the ones going to the main page or to the recent changes page. This method is also privacy friendly.

**Note**: All of these options are disabled (false) by default and are all either true or false.

`$wgShareEmail` - Show "Share via email" option in sidebar?

`$wgShareLinkedIn` - Show "Share to LinkedIn" option in sidebar?

`$wgShareReddit` - Show "Share to Reddit" option in sidebar?

`$wgShareTumblr` - Show "Share to Tumblr" option in sidebar?

`$wgShareTwitter` - Show "Share to Twitter" option in sidebar?

`$wgShareFacebook` - Show "Share to Facebook" option in sidebar?

`$wgShareUseBasicButtons` - Enable 'Basic buttons' Mode? (See #2 in the "Configuration" section; conflicts with $wgShareUsePlainLinks)

`$wgShareUsePlainLinks` - Enable 'Plain links' Mode? (See #3 in the "Configuration" section; conflicts with $wgShareUseBasicButtons)

If the last two configuration variables are enabled, they will cancel each other out and cause the sidebar portlet to not appear.
