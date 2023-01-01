<?php
if(count($data) == 5) {
	echo wfMsg('myhome-hot-spots-newest')
?>
<ul class="clearfix" style="margin-top: 5px;">
<?php foreach($data as $item) { ?>
	<li>
		<span><a href="<?= htmlspecialchars($item['url']) ?>" class="title" rel="nofollow"><?= htmlspecialchars($item['title'])  ?></a></span>
	</li>
<?php } ?>
</ul>
<?php } else if(count($data) == 2) {
	echo '<p style="margin-bottom: 5px">'.wfMsgExt('myhome-hot-spots-definition','parsemag',$data['interval']).'</p>';
	$hotSpotSeverity = 1; //used to set background color heat level. 1 (hottest) - 5 (coolest).
	$hotSpotLast = Array(); //used to compare the last rendered item to current.
	$hotSpotFire = '';
	if($data['results'][0]['count'] - $data['results'][1]['count'] > 2) {
		$hotSpotFire = ' class="fire"';
	}
	echo '<ul id="myhome-hot-spots" class="reset">';
	foreach($data['results'] as $row) {
		if (isset($hotSpotLast['count']) && ($row['count'] == $hotSpotLast['count']) ) { //same count as before?
			$thisSeverity = $hotSpotLast['severity']; //use the last severity level
		} else {
			$thisSeverity = $hotSpotSeverity; //use the actual severity level for this row
		}
?>
		<li<?= $hotSpotFire ?>>
			<div class="myhome-hot-spots-fire">
				<div class="hot-spot-severity-<?=$thisSeverity?>">
					<big><?= $row['count'] ?></big>
					<small><?= wfMsg('myhome-hot-spots-number-of-editors') ?></small>
				</div>
			</div>

			<span><a href="<?= htmlspecialchars($row['url']) ?>" class="title" rel="nofollow"><?= htmlspecialchars($row['title'])  ?></a></span>
		</li>
<?php
		$hotSpotLast['count'] = $row['count'];
		$hotSpotLast['severity'] = $thisSeverity;

		$hotSpotFire = '';
		$hotSpotSeverity++;
	}
	echo '</ul>';
} ?>
