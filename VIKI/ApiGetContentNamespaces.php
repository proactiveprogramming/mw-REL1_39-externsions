<?php
/*
 * Copyright (c) 2014 The MITRE Corporation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

use MediaWiki\MediaWikiServices;

class ApiGetContentNamespaces extends ApiBase {
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}
	public function execute() {
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
		$this->getResult()->addValue( null, $this->getModuleName(), $namespaceInfo->getContentNamespaces() );

		return true;
	}
	public function getDescription() {
		return 'Get the list of content namespaces for this wiki.

Note that because the returned value is a JSON object, you must specify ' .
'format=json in this query; the default xml format will return only an error.';
	}
	public function getExamples() {
		return array(
			'api.php?action=getContentNamespaces'
		);
	}
	public function getHelpUrls() {
		return '';
	}
}
