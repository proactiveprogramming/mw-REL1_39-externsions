<?php
/**
 * MonoBook nouveau
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */

if( !defined( 'MEDIAWIKI' ) )
	die();

/** */
require_once('includes/SkinTemplate.php');

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class SkinBlender extends SkinTemplate {
	/** Using monobook. */
	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'blender';
		$this->stylename = 'blender';
		$this->template  = 'BlenderTemplate';
		$this->usercss = true;
	}

/** Make an HTML element for a stylesheet link */
        function makeStylesheetLink( $url ) {
                return '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars( $url ) . "\"/>";
        }

	function blendercss() {
		global $wgSquidMaxage;

		$query = "action=raw&ctype=text/css&smaxage=$wgSquidMaxage";

                $sitecss = '';

                $sitecss .= $this->makeStylesheetLink( self::makeNSUrl( 'Common.css', $query, NS_MEDIAWIKI ) ) . "\n";
                $sitecss .= $this->makeStylesheetLink( self::makeNSUrl( ucfirst( $this->stylename ) . '.css', $query, NS_MEDIAWIKI ) ) . "\n";

                // No deps
                return $sitecss;
	}
	/*function setupSkinUserCss( OutputPage $out ){
		$query = "action=raw&ctype=text/css&smaxage=$wgSquidMaxage";
                // Do not call parent::setupSkinUserCss(), we have our own print style
                $out->addStyle(self::makeNSUrl( 'Common.css', $query, NS_MEDIAWIKI ) );
                //$out->addStyle( self::makeNSUrl( 'Print.css', $query, NS_MEDIAWIKI ), 'print' );
		$out->addStyle( self::makeNSUrl( $this->getSkinName() . '.css', $query, NS_MEDIAWIKI ) );
	}*/

}

/**
 * @todo document
 * @addtogroup Skins
 */
class BlenderTemplate extends QuickTemplate {
	/**
	 * Template filter callback for MonoBook skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgAllowUserCss, $wgUser;
		$skin = $wgUser->getSkin();
		$body = $this->data['bodytext'];
		//$this->data['usercss'] = true;
		if($wgAllowUserCss && $wgUser->isLoggedIn()) {
			$this->data['usercss'] = $skin->makeUrl($wgUser->getUserPage()->getPrefixedText(). '/' . $skin->getSkinName() .'.css','action=raw&ctype=text/css' );
		}
		$skin_path = $this->data['stylepath'].'/'.$this->data['stylename'];
		$toc_pattern = '/<table id="toc".*?<\/table>/s';
		global $foo_toc;
		$foo_toc='';
		$body = preg_replace_callback(
					      $toc_pattern,
					      create_function('$match',
							      'global $foo_toc; $foo_toc=$match[0]; return "";'
							      ),
					      $body);

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="<?php $this->text('xhtmldefaultnamespace') ?>" <?php 
	foreach($this->data['xhtmlnamespaces'] as $tag => $ns) {
		?>xmlns:<?php echo "{$tag}=\"{$ns}\" ";
	} ?>xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">
	<head>
		<meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
		<?php $this->html('headlinks') ?>
		<title><?php $this->text('pagetitle') ?></title>
<script type="text/javascript" src="<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/nifty.js"></script>
<script type="text/javascript" src="<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/jquery.cookie.js"></script>
<script type="text/javascript" src="<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/slider.js"></script>

<script type="text/javascript">
/* 
 * many thanks to Alessandro Fulciniti and his most excellent 'nifty corners' script:
 * http://webdesign.html.it/articoli/leggi/528/more-nifty-corners/1/
 */

window.onload=function(){
 if(!NiftyCheck())
    return;
 Rounded("div.nifty","tl","#232323","#3e4d5e","smooth");
 Rounded("div#bottom","bl br","#000000","#292929","border #363636");
}


</script>
		<style type="text/css" media="screen,projection">/*<![CDATA[*/ @import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/main.css?<?php echo $GLOBALS['wgStyleVersion'] ?>"; /*]]>*/</style>
		<style type="text/css" media="screen,projection">/*<![CDATA[*/ @import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/niftyCorners.css?<?php echo $GLOBALS['wgStyleVersion'] ?>"; /*]]>*/</style>
		<link rel="stylesheet" type="text/css" <?php if(empty($this->data['printable']) ) { ?>media="print"<?php } ?> href="<?php $this->text('stylepath') ?>/common/commonPrint.css?<?php echo $GLOBALS['wgStyleVersion'] ?>" />

		<!--[if lt IE 5.5000]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE50Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<!--[if IE 5.5000]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE55Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<!--[if IE 6]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE60Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<!--[if IE 7]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE70Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<!--[if lt IE 7]><script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath') ?>/common/IEFixes.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>
		<meta http-equiv="imagetoolbar" content="no" /><![endif]-->
		
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/common/wikibits.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"><!-- wikibits js --></script>
<?php	if($this->data['jsvarurl'  ]) { ?>
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('jsvarurl'  ) ?>"><!-- site js --></script>
<?php	} 
	print $skin->blendercss();
?>
<?php	if( $this->data['pagecss']) { ?>
		<style type="text/css"><?php $this->html('pagecss') ?></style>
<?php	}
		if($this->data['usercss']) { ?>
		<link rel="stylesheet" type="text/css" href="><?php $this->html('usercss') ?>"></style>
<?php	}
		if($this->data['userjs']) { ?>
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('userjs' ) ?>"></script>
<?php	}
		if($this->data['userjsprev']) { ?>
		<script type="<?php $this->text('jsmimetype') ?>"><?php $this->html('userjsprev') ?></script>
<?php	}
		if($this->data['trackbackhtml']) print $this->data['trackbackhtml']; ?>
		<!-- Head Scripts -->
<?php $this->html('headscripts') ?>
	</head>
<body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload'    ]) { ?>onload="<?php     $this->text('body_onload')     ?>"<?php } ?>
 class="mediawiki <?php $this->text('nsclass') ?> <?php $this->text('dir') ?> <?php $this->text('pageclass') ?>">

<!-- top level nav -->
   <?php 
$menu= wfMsgGetKey('blenderskin-top',true); 
echo $menu ? $menu: '';
?>
<!-- END top level nav -->

<!-- main page container -->
<div id="pagecontainer">

	<!-- subsection header and subnav -->
	<div id="pageheader" style="background-image: url('/skins/blender/bg.png');"><h1 id="firstHeading" class="firstHeading"><?php $this->data['displaytitle']!=""?$this->html('title'):$this->text('title') ?></h1>
</div>
	<div class="subnav boxheader">

		<div class="search">
		<form method="get" action="http://www.blender.org/cgi-bin/search.cgi" style="display: inline;">
			<input type="hidden" name="ul" value="" />
			<input type="hidden" name="ps" value="10" />

			<input type="text" name="q" value="(search)" onfocus="this.value='';" class="search" id="search" size="8" />
		</form>
		</div>
		
   <?php 
$menu= wfMsgGetKey('blenderskin-menu',true); 
echo $menu ? $menu: '';
?>
	</div>
        <div id="p-cactions" class="subnav sublevel2">
			<ul>
<?php	$i=0;  foreach($this->data['content_actions'] as $key => $tab) { ?>
   <?php if($i>0) {?><li>&bull;</li><?php }?>
<li id="ca-<?php echo Sanitizer::escapeId($key) ?>"<?php
					 	if($tab['class']) { ?> class="<?php echo htmlspecialchars($tab['class']) ?>"<?php }
					 ?>><a href="<?php echo htmlspecialchars($tab['href']) ?>"><?php
					 echo htmlspecialchars($tab['text']) ?></a></li>
					       <?php	$i++;		 } ?>
			</ul>
	</div>

        <!-- END subsection header and subnav -->

	<div id="globalWrapper">
		<div id="col-content">
	<div id="content">
		<a name="top" id="top"></a>
		<?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
		<div id="bodyContent">
			<h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
			<div id="contentSub"><?php $this->html('subtitle') ?></div>
			<?php if($this->data['undelete']) { ?><div id="contentSub2"><?php     $this->html('undelete') ?></div><?php } ?>
			<?php if($this->data['newtalk'] ) { ?><div class="usermessage"><?php $this->html('newtalk')  ?></div><?php } ?>
			<?php if($this->data['showjumplinks']) { ?><div id="jump-to-nav"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div><?php } ?>
			<!-- start content -->
			<?php echo $body ?>
			<?php if($this->data['catlinks']) { ?><div id="catlinks"><?php       $this->html('catlinks') ?></div><?php } ?>
			<!-- end content -->
			<div class="visualClear"></div>
		</div>
	</div>
		</div>
		<div id="column-one">
        <div class="nifty col-right-header boxheader" id="col-one-header">
<?php
 $pitems=$this->data['personal_urls'];
 $us = array_shift($pitems); 
 $lo = array_pop($pitems);
 array_unshift($pitems,$us);
 if($lo) { array_push($pitems,$lo); }
 $lo['text']=preg_replace('/\s*\/.+$/','',$lo['text']);
?>

   <div class="username" id="p-idcard">

<a href="<?php echo htmlspecialchars($us['href']) ?>"><?php echo htmlspecialchars($us['text']) ?></a>
      </div>

<?php
      if(sizeof($pitems)>1) { # logged-in
?>
				<p class="logout">( <a href="<?php echo htmlspecialchars($lo['href']) ?>"><?php echo htmlspecialchars($lo['text']) ?></a> )</p><br />
<?php } ?>
      </div>
      <div id="col-one-body">
	<div class="port" id="p-navi">
	<div class="col-right-section bgcolor2">

	<?php $bar="navigation"; $cont=$this->data['sidebar'][$bar]; ?>
	<div class="c1" id='p-navigation'>
			<ul>
<?php 			foreach($cont as $key => $val) { ?>
				<li id="<?php echo Sanitizer::escapeId($val['id']) ?>"<?php
					if ( $val['active'] ) { ?> class="active" <?php }
				?>><a href="<?php echo htmlspecialchars($val['href']) ?>"><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php			} ?>
			</ul>
	</div>
<?php if(sizeof($pitems)>1) { 
   array_shift($pitems);
?>
	<div class="c2" id="p-personal">
			<ul>
<?php
			   foreach($pitems as $key => $item) { ?>
				<li id="pt-<?php echo Sanitizer::escapeId($key) ?>"<?php
					if ($item['active']) { ?> class="active"<?php } ?>><a href="<?php
				echo htmlspecialchars($item['href']) ?>"<?php
				if(!empty($item['class'])) { ?> class="<?php
				echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
				echo htmlspecialchars($item['text']) ?></a></li>
<?php			} ?>
			</ul>
        </div>

	<div class="visualClear"></div>

<?php } ?>
        </div>
         
        <div class="col-right-section" id="p-search">
			<form action="<?php $this->text('searchaction') ?>" id="searchform"><div id="p-search-div">
				<input id="searchInput" name="search" type="text"<?php 
					if( isset( $this->data['search'] ) ) {
						?> value="<?php $this->text('search') ?>"<?php } ?> />
<p>
				<input type='submit' name="go" class="searchButton" id="searchGoButton"	value="<?php $this->msg('go') ?>" />&nbsp;
				<input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg('search') ?>" />
			
</p>
</div></form>
	</div>
	<div class='col-right-section bgcolor3'>

        <div class="c1 bgcolor3" id="p-tb">
			<ul>
<?php
		if($this->data['notspecialpage']) { ?>
				<li id="t-whatlinkshere"><a href="<?php
				echo htmlspecialchars($this->data['nav_urls']['whatlinkshere']['href'])
				?>"><?php $this->msg('whatlinkshere') ?></a></li>
<?php
			if( $this->data['nav_urls']['recentchangeslinked'] ) { ?>
				<li id="t-recentchangeslinked"><a href="<?php
				echo htmlspecialchars($this->data['nav_urls']['recentchangeslinked']['href'])
				?>"><?php $this->msg('recentchangeslinked') ?></a></li>
<?php 		}
		}
		if(isset($this->data['nav_urls']['trackbacklink'])) { ?>
			<li id="t-trackbacklink"><a href="<?php
				echo htmlspecialchars($this->data['nav_urls']['trackbacklink']['href'])
				?>"><?php $this->msg('trackbacklink') ?></a></li>
<?php 	}
		if($this->data['feeds']) { ?>
			<li id="feedlinks"><?php foreach($this->data['feeds'] as $key => $feed) {
					?><span id="feed-<?php echo Sanitizer::escapeId($key) ?>"><a href="<?php
					echo htmlspecialchars($feed['href']) ?>"><?php echo htmlspecialchars($feed['text'])?></a>&nbsp;</span>
					<?php } ?></li><?php
		}

		foreach( array('contributions', 'blockip', 'emailuser', 'upload', 'specialpages') as $special ) {

			if($this->data['nav_urls'][$special]) {
				?><li id="t-<?php echo $special ?>"><a href="<?php echo htmlspecialchars($this->data['nav_urls'][$special]['href'])
				?>"><?php $this->msg($special) ?></a></li>
<?php		}
		}

		if(!empty($this->data['nav_urls']['print']['href']) && 0) { ?>
				<li id="t-print"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['print']['href'])
				?>"><?php $this->msg('printableversion') ?></a></li><?php
		}

		if(!empty($this->data['nav_urls']['permalink']['href'])) { ?>
				<li id="t-permalink"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['permalink']['href'])
				?>"><?php $this->msg('permalink') ?></a></li><?php
		} elseif ($this->data['nav_urls']['permalink']['href'] === '') { ?>
				<li id="t-ispermalink"><?php $this->msg('permalink') ?></li><?php
		}

		wfRunHooks( 'MonoBookTemplateToolboxEnd', array( &$this ) );
?>
			</ul>

	    </div>

	<?php $bar="extras"; $cont=$this->data['sidebar'][$bar]; ?>
	<div class="c2" id="p-extras">
			<ul>
<?php 			foreach($cont as $key => $val) { ?>
				<li id="<?php echo Sanitizer::escapeId($val['id']) ?>"<?php
					if ( $val['active'] ) { ?> class="active" <?php }
				?>><a href="<?php echo htmlspecialchars($val['href']) ?>"><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php			} ?>
			</ul>
	</div>
   	    <div class="visualClear"></div>

            </div>
   
        </div>
<?php 
				   $foo_toc=preg_replace('/^.+<td[^>]*>\s*/','',$foo_toc);
				   $foo_toc=preg_replace('!</td>.*$!','',$foo_toc);
				   $foo_toc=preg_replace('!<ul>!','<ul id="toc-ul">',$foo_toc,1);
if($foo_toc) { 
?>
        <p class="vspacer bgcolor0"></p>
  	<div class="port nifty bgcolor1" id="p-toc">
	<div class="col-right-section wikicontents bgcolor1" id="toc">
			    <?php echo $foo_toc; ?>
        <p></p>
   	    <div class="visualClear"></div>
	</div>

        </div>
<?php
}
?>							
</div>

</div>
</div>
<div class="visualClear"></div>
<div id="footer" class="boxbg">
<?php
		if($this->data['poweredbyico']) { ?>
				<div id="f-poweredbyico"><?php $this->html('poweredbyico') ?></div>
<?php 	}
		if($this->data['copyrightico']) { ?>
				<div id="f-copyrightico"><?php $this->html('copyrightico') ?></div>
<?php	}

		// Generate additional footer links
?>
			<ul id="f-list">
<?php
		$footerlinks = array(
			'lastmod', 'viewcount', 'numberofwatchingusers', 'credits', 'copyright',
			'privacy', 'about', 'disclaimer', 'tagline',
		);
		foreach( $footerlinks as $aLink ) {
			if( isset( $this->data[$aLink] ) && $this->data[$aLink] ) {
?>				<li id="<?php echo$aLink?>"><?php $this->html($aLink) ?></li>
<?php 		}
		}
?>
			</ul>
         			    
   	    <div class="visualClear"></div>
		</div>
	 <div id="bottom" class="boxbg"></div>
		
	<?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
</div>
<?php $this->html('reporttime') ?>
<?php if ( $this->data['debug'] ): ?>
<!-- Debug output:
<?php $this->text( 'debug' ); ?>

-->
<?php endif; ?>
</body></html>
<?php
	wfRestoreWarnings();
	} // end of execute() method
} // end of class
?>
