<section class="CommunityCornerModule module">
	<h2 class="dark_text_2"><?=wfMsg('myhome-community-corner-header')?></h2>
	<div id="myhome-community-corner-content"><?=wfMsgExt('community-corner', array('parse','content'))?></div>
	
<?php if($isAdmin) { ?>
	<div id="myhome-community-corner-edit"><a class="more" href="<?=Title::newFromText('community-corner', NS_MEDIAWIKI)->getLocalURL('action=edit')?>" rel="nofollow"><?=wfMsg('oasis-myhome-community-corner-edit')?></a></div>
<?php } ?>
</section>