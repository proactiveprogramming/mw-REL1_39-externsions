<?php

class ImageServingTest extends SpecialPage {


	function __construct() {
		parent::__construct( 'ImageServingTest', 'imageservingtest' );
	}

	function execute($article_id = null, $limit = "", $offset = "", $show = true) {
		global $wgRequest, $wgOut, $wgTitle, $wgUser;
		if( !$wgUser->isAllowed( 'imageservingtest' ) ) {
			throw new PermissionsError( 'imageservingtest' );
		}
		$this->size = 200;
		$this->prop = array("w" => 2, "h" => 1);
		switch($wgRequest->getVal("option", 1)) {
			case "2":
				$this->size = 270;
				$this->prop = array("w" => 3, "h" => 1);
			break;
			case "3":
				$this->size = 100;
				$this->prop = array("w" => 1, "h" => 1);
			break;
		}

		if( $wgRequest->getVal("article","") != "")  {
			$title = Title::newFromText($wgRequest->getVal("article"),NS_MAIN);
			$test = new ImageServing(array($title->getArticleId()), $this->size, $this->prop);

			foreach ($test->getImages(20) as $value){
				$wgOut->addHTML( "<b>".$title->getText()."</b><br><br>");
				foreach ($value as $value2) {
					$wgOut->addHTML("<img src='{$value2['url']}' /> <br>");
					$wgOut->addHTML($value2['name']."<br>");
				}
			};
			return ;
		}

		$wgOut->addHTML(Xml::element("a", array("href" => $wgTitle->getLocalURL("option=1")), wfMsg('imageserving-option1') )."<br>" );
		$wgOut->addHTML(Xml::element("a", array("href" => $wgTitle->getLocalURL("option=2")), wfMsg('imageserving-option2') )."<br>" );
		$wgOut->addHTML(Xml::element("a", array("href" => $wgTitle->getLocalURL("option=3")), wfMsg('imageserving-option3') )."<br>" );

		if (empty($limit) && empty($offset)) {
            list( $limit, $offset ) = wfCheckLimits();
        }

	/** removed reference to deprecated mechanism of Mostvisitedpages
	 *  right now operation of ImageServing Test is not very clear
	 *  TODO: provide clearer way of using ImageServingTest (BugId:97236)
	 */

    }


}
