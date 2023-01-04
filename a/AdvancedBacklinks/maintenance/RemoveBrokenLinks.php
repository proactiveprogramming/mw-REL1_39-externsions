<?php
/**
 * AdvancedBacklinks
 * Copyright (C) 2019  Ostrzyciel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

$IP = getenv('MW_INSTALL_PATH');
if ($IP === false) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class RemoveBrokenLinks extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Deletes links from nonexistent pages in ab_links and ab_images tables.' );
	}

	public function execute() {

		$dbw = $this->getDB( DB_PRIMARY );

		//ab_links
		$dbw->delete(
			'ab_links',
			"abl_from NOT IN ({$dbw->selectSQLText( 'page', 'page_id' )})",
			__METHOD__
		);
		$this->output( "Deleted {$dbw->affectedRows()} invalid links from ab_links\n" );

		//ab_images
		$dbw->delete(
			'ab_images',
			"abi_from NOT IN ({$dbw->selectSQLText( 'page', 'page_id' )})",
			__METHOD__
		);
		$this->output( "Deleted {$dbw->affectedRows()} invalid links from ab_images\n" );
	}
}

$maintClass = RemoveBrokenLinks::class;
require_once RUN_MAINTENANCE_IF_MAIN;