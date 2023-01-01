<?php
/**
 *
 * @author 		@See $wgExtensionCredits
 * @license		GPL
 * @package		VgiLink
 * @addtogroup  Extensions
 * @link		http://www.mediawiki.org/wiki/Extension:VgiLink
 *
 **/

class VigLinkHooks {
	/**
	 * Setup parser function
	 * @param parser from mediawiki
	 * @return must return true for next prasers
	 **/
	public static function setupVigLink( $skin, &$text = '' ) {
	       
       $text .= VigLinkHooks::addVigLink();
       return true;
	}
    public static function addVigLink () {
        global $wgVigLinkKey;
        $html = <<<VIGLINK
<script type="text/javascript">
  var vglnk = { api_url: '//api.viglink.com/api',
                key: '{$wgVigLinkKey}' };

  (function(d, t) {
    var s = d.createElement(t); s.type = 'text/javascript'; s.async = true;
    s.src = ('https:' == document.location.protocol ? vglnk.api_url :
             '//cdn.viglink.com/api') + '/vglnk.js';
    var r = d.getElementsByTagName(t)[0]; r.parentNode.insertBefore(s, r);
  }(document, 'script'));
</script>
VIGLINK;
        return $html;
    }
}
