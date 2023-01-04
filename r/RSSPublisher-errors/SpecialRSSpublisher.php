<?php
include('chooseimagefromarticle.func.php');
include('cleantext.func.php');
include('collectarticelchanges.func.php');
include('collectnewusers.func.php');

class SpecialRSSpublisher extends SpecialPage {
	function __construct() {
		parent::__construct( 'RSSpublisher' );
	}
 
	function execute( $par ) {
		global $wgEmergencyContact;
		global $wgFavicon;
		global $wgLanguageCode;
		global $wgOut;
		global $wgRSSpublisher;
		global $wgServer;
		global $wgSitename;

		$this->setHeaders();
		$wgOut->disable();

		//header
		header('Content-type: application/rss+xml');

		$output = NULL;
		

		//check if file is already in cache	and not to old
		if(isset($wgRSSpublisher['maxcacheage'])) $maxcacheage = $wgRSSpublisher['maxcacheage'];
		else $maxcacheage = 60*60;
		
		if(time() - filemtime('cache/rsspublisher.txt') <= $maxcacheage) {
			$output .= implode('',file('cache/rsspublisher.txt'));
		}
		//in case of not in cache
		else{
	
			$datetimeformat = 'D, d M Y H:i:s O';

			if (isset($wgRSSpublisher['limit'])){
				if ($wgRSSpublisher['limit'] == 'unlimited');
				else $itemlimit = $wgRSSpublisher['limit'];
			}
			else $itemlimit = 30;
			
			
			foreach(collectarticlechanges() as $additem) $items[] = $additem;
			foreach(collectnewusers() as $additem) $items[] = $additem;

			foreach($items as $item) $sortitems[$item['unixpubdate']] = $item;
			krsort($sortitems);

			$sortitems = array_slice($sortitems, 0, $itemlimit);
			
			$channel['pubdate'] = date($datetimeformat, max(array_keys($sortitems)));

			//OUTPUT
			$output .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			$output .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
			
			//Channel Information
			$output .= "<channel>\n";
			$output .= "\t<title>$wgSitename</title>\n";
			$output .= "\t<link>$wgServer</link>\n";
			if (isset($wgRSSpublisher['pagedescription'])) $output .= "\t<description>".$wgRSSpublisher['pagedescription']."</description>\n";
			$output .= "\t<language>$wgLanguageCode</language>\n";
			$output .= "\t<copyright>$wgSitename</copyright>\n";
			$output .= "\t<pubDate>".$channel['pubdate']."</pubDate>\n";
			$output .= "\t<lastBuildDate>".date($datetimeformat)."</lastBuildDate>\n";
			$output .= "\t<docs>http://thwiki.org/t=Special:RSSpublisher</docs>\n";
			$output .= "\t<generator>Mediawiki RSSpublicher Extrension ".$wgRSSpublisher['version']." from Thiemo Schuff</generator>\n";
			$output .= "\t<managingEditor>$wgEmergencyContact($wgSitename)</managingEditor>\n";
			$output .= "\t<webMaster>$wgEmergencyContact($wgSitename)</webMaster>\n";
			$output .= "\t<image>\n\t\t<url>$wgServer$wgFavicon</url>\n\t\t<title>$wgSitename</title>\n\t\t<link>$wgServer</link>\n\t</image>\n";
			$output .= "\t<atom:link href=\"http://thwiki.org/t=Spezial:RSSpublisher\" rel=\"self\" type=\"application/rss+xml\" />\n";

			//Items
			foreach	($sortitems as $item){
				$output .= "\t\t<item>\n";
				
				if ($item['type'] == 'collectarticelchanges') $output .= "\t\t\t<title>".trim(wfMsg('rsspublisher-articleedit',$item['title']))."</title>\n";
				elseif ($item['type'] == 'collectnewusers') $output .= "\t\t\t<title>".trim(wfMsg('rsspublisher-newusers',$item['title']))."</title>\n";
				else $output .= "\t\t\t<title>".$item['title']."</title>\n";
				
				$output .= "\t\t\t<link>".$item['link']."</link>\n";
				if (isset($item['imageurl'])) $output .= "\t\t\t<enclosure url=\"".$item['imageurl']."\" length=\"".$item['imagesize']."\" type=\"".$item['imagetype']."\" />\n";
				$output .= "\t\t\t<description><![CDATA[".trim($item['description'])."]]></description>\n";
				$output .= "\t\t\t<pubDate>".$item['pubdate']."</pubDate>\n";
				$output .= "\t\t\t<guid>".$item['guid']."</guid>\n";
				$output .= "\t\t</item>\n";
			}

			//footer
			$output .= "\t</channel>\n</rss>\n";
			
			$cachefile = fopen('cache/rsspublisher.txt','w');
/			fwrite($cachefile, $output);
			fclose($cachefile);

		}
		echo $output;
	}
}