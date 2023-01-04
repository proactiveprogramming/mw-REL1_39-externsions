<?php
/* WorkingWiki extension for MediaWiki 1.23 and later
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/*
 * implementation for Special:PE
 *
 * This Special page is the entry point for passing requests
 * unmediated into the ProjectEngine component of the WW system.
 *
 * usage: Special:PE/<url components>
 */

class SpecialPE extends SpecialPage {

	function __construct() {
		parent::__construct('PE');
		$this->mIncludable = true;
		$this->mListed = false;
	}

	function execute( $par ) { 
		# some url-encoded stuff can't be put into $par, so we put
		# it into 'par' instead
		if ( ! $par ) {
			$par = $this->getRequest()->getVal( 'par' );
		}
		global $wwUseHTTPForPE;
		#wwLog( 'REQUEST_URI : ' . $_SERVER['REQUEST_URI'] );
		wwLog( 'SpecialPE request: _REQUEST = ' . json_encode( $_REQUEST ) . ', par = ', $par );
		if ( $wwUseHTTPForPE ) {
			$headers = array();
			if ( ( $lastId = $this->getRequest()->getHeader( 'Last-Event-ID' ) ) ) {
				$headers[] = "Last-Event-ID: $lastid";
			}
			# TODO: what abour $par??
			$result = ProjectEngineConnection::call_project_engine_http_internal( 
				( count( $_POST ) > 0 ),
				$_REQUEST,
				$headers
			);
		} else {
			$result = array();
			$pe = new PEAPI();
			$pe->process_request_raw(
				( count( $_POST ) > 0 ),
				$par,
				$_REQUEST,
				$result
			);
		}
		# either of those cases may produce output directly and die
		# without returning.
		if ( isset( $result['messages'] ) ) {
			foreach ( $result['messages'] as $m ) {
				echo $m[0] . ': ' . htmlspecialchars( $m[1] ) . "<br/>\n";
			}
		}
		echo PEMessage::report_messages();
		exit();
	}
}
