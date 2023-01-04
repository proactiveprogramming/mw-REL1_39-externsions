<?php
	$status['s_aba'] = 'Bug Assigned';
	$status['s_abb'] = 'Bug Working';
	$status['s_abca'] = 'Bug Feedback';
	$status['s_abcb'] = 'Bug Pending Approval';
	$status['s_abcc'] = 'Bug Pending Development';
	$status['s_abd'] = 'Bug Approved';
	$status['s_abe'] = 'Bug Cancelled';

	$status['s_ana'] = 'New Development Assigned';
	$status['s_anb'] = 'New Development Working';
	$status['s_anca'] = 'New Development Feedback';
	$status['s_ancb'] = 'New Development Pending Approval';
	$status['s_and'] = 'New Development Approved';
	$status['s_ane'] = 'New Development Cancelled';
	
	print json_encode($status);

?>
