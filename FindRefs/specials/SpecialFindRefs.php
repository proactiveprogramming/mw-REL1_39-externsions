<?php
/**
 * FindRefs extension page
 *
 * @file
 * @ingroup Extensions
 */

class SpecialFindRefs extends SpecialPage {
	public function __construct() {
		parent::__construct( 'FindRefs' );
	}

	public function execute( $sub ) {
		$out = $this->getOutput();

		$out->setPageTitle( 'FindRefs' );

		$out->addHelpLink( 'How to become a MediaWiki hacker' );

		$out->addWikiMsg( 'findrefs-intro' );

		$form = "Enter the search string: <form><input type=text name='ques' placeholder='Enter search string'></form><br>";

		$out->addHTML($form);

		$sstring = (string)urlencode($_GET["ques"]);

		if($sstring){
			$style = "<style>table { border:1px solid black;}</style>";
			$tablebegin = "<table style='border:1px solid black'>";
			$gsearch = "<tr><td><a href=https://www.google.com/search?q=$sstring>Google Search</a></td></tr>";
			$gbooks = "<tr><td><a href=https://www.google.com/search?tbm=bks&q=$sstring>Google Books</a></td></tr> ";
			$gscholar = "<tr><td><a href=https://scholar.google.co.in/scholar?&q=$sstring>Google Scholar</a></td></tr>";
			$youtube = "<tr><td><a href=https://www.youtube.com/results?search_query=$sstring>YouTube</a></td></tr>";
			$highbeam = "<tr><td><a href=https://www.highbeam.com/Search?searchTerm=$sstring>HighBeam Research</a></td></tr>";
			$wolfram = "<tr><td><a href=https://www.wolframalpha.com/input/?i=$sstring>Wolfram Alpha</a></td></tr>";
			$opendata = "<tr><td><a href=https://data.gov.in/search/site/$sstring>Open Data (Govt of India)</a></td></tr>";
			$sciencemag = "<tr><td><a href=http://search.sciencemag.org/?q=$sstring>Science Mag (General)</a></td></tr>";
			$sciencemagadv = "<tr><td><a href=http://search.sciencemag.org/?q=$sstring>Science Mag (Advanced)</a></td></tr>";
			$livescience = "<tr><td><a href=http://www.livescience.com/search?q=$sstring></a></td></tr>";
			$smithsonian = "<tr><td><a href=http://www.smithsonianmag.com/search/?q=$sstring>Smithsonian Magazine</a></td></tr>";
			$popsci = "<tr><td><a href=http://www.popsci.com/find/$sstring>Popular Science</a></td></tr>";
			$nature = "<tr><td><a href=http://www.nature.com/search?q=$sstring>Nature Mag</a></td></tr>";
			$space = "<tr><td><a href=http://www.space.com/search?q=$sstring>Space Mag</a></td></tr>";
			$scieneddaily = "<tr><td><a href=https://www.sciencedaily.com/search/?keyword=$sstring>Science Daily</a></td></tr>";
			$nasa = "<tr><td><a href=https://nasasearch.nasa.gov/search?query=$sstring&affiliate=nasa&utf8=%E2%9C%93>NASA</a></td></tr>";
			$cleanedstring = str_replace("+", " ", $sstring);
			$out->addHTML($style);
			$out->addHTML($tablebegin);
			$out->addHTML("<tr><th>Search String: $cleanedstring</th></tr>");
			$out->addHTML("<tr><th>General</th></tr>");
			$out->addHTML($gsearch);	
			$out->addHTML($gbooks);
			$out->addHTML($gscholar);
			$out->addHTML($youtube);
			$out->addHTML("<tr><th>Research and Data</th></tr>");
			$out->addHTML($highbeam);
			$out->addHTML($wolfram);
			$out->addHTML($opendata);
			$out->addHTML("<tr><th>Science Mags and Journals</th></tr>");
			$out->addHTML($sciencemag);
			$out->addHTML($sciencemagadv);
			$out->addHTML($livescience);
			$out->addHTML($smithsonian);
			$out->addHTML($popsci);
			$out->addHTML($nature);
			$out->addHTML($space);
			$out->addHTML($nasa);
			$out->addHTML("</table>");
		}
	
	}


}
