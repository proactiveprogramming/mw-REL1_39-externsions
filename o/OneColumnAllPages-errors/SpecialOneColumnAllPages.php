
<?php
if ( !defined( 'MEDIAWIKI' ) ) {
   die( 'This file is a MediaWiki extension. It is not a valid entry point' );
}

class SpecialOneColumnAllPages extends SpecialPage {
   function __construct( ) {
      parent::__construct( 'OneColumnAllPages' );
   }

   function execute( $par ) {
      global $wgSpecialPages;
      $this->setHeaders();
      $viewOutput = $this->getOutput();
      $dbr = wfGetDB( DB_REPLICA );
      global $wgSitename;
      $output = "<big>'''" . wfMessage( 'onecolumnallpages-intro', $wgSitename )->plain()
         . "'''</big><br/><br/>";
      $namespaces = MWNamespace::getCanonicalNamespaces();
	// Support for, e.g., page_id-0 as parameter, with 0 being the namespace
	$where = array( '1=1' );
	$pars = explode ( '-', $par );
	if ( isset( $pars[1] ) ) {
		echo $pars[1];
		$where = array( 'page_namespace' => $pars[1] );
		$par = $pars[0];
	}
      if ( $par != 'page_id' ) {
         $res = $dbr->select( 'page', array ( 'page_title', 'page_namespace' ), $where );
      } else {
         $res = $dbr->select( 'page', array ( 'page_title', 'page_namespace' ),
            $where, __METHOD__, array( 'ORDER BY' => 'page_id ASC' ) );
      }
      foreach ( $res as $row ) {
         if ( $par == 'raw' ) {
            $output .= '[{{fullurl:';
         } else {
            $output .= "[[:";
         }
         if ( $par == 'viewwikitext' ) {
            $output .= 'Special:ViewWikitext/';
         }
         $pageTitle = '';
         if ( $row->page_namespace == 828 ) {
            $pageTitle .= 'Module:';
         } else {
            $pageTitle .= $namespaces[$row->page_namespace];
            if ( $namespaces[$row->page_namespace] ) {
               $pageTitle .= ':';
            }
         }
         $pageTitle .= $row->page_title;
         $output .= $pageTitle;
         if ( $par == 'viewwikitext' && isset( $wgSpecialPages['ViewWikitext'] ) ) {
            $output .= "|$pageTitle]]<br/>";
         }
         elseif ( $par == 'raw' ) {
            $output .= "|action=raw}} $pageTitle]<br/>";
         } else {
            $output .= "]]<br/>";
         }
      }
      $viewOutput->addWikiTextAsContent( $output );
      return $output;
   }

   function getRobotPolicy() {
      global $wgOneColumnAllPagesRobotPolicy;
      return $wgOneColumnAllPagesRobotPolicy;
   }
}
