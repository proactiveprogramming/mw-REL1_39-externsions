<?php
/**
 * Nagios - this extension places Nagios tables on mediawiki pages
 *
 * original by Edward Quick 24/03/2015
 *
 * Installation:
 *
 * To activate this extension, add the following into your LocalSettings.php file:
 * require_once '$IP/extensions/Nagios/Nagios.php';
 *
 * @ingroup Extensions
 * @author Edward Quick <edwardquick@hotmail.com>
 * @version 1.0b
 * @link http://www.mediawiki.org/wiki/Extension:Nagios Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 * Usage:
 *
 *  <Nagios --arguments-- >Header Text</Nagios>
 *
 *  --arguments--:
 *
 *     nagiosurl		: This is mandatory. Specify the url of the nagios backend. Example, http://linuxproblems.org/nagios/
 *     nagioscgi		: Optional. Default is nagiosurl + cgi-bin. Example, http://linuxproblems.org/nagios/cgi-bin/
 *     pnp4url			: Optional. Default is nagiosurl domain + /pnp4nagios. Example, http://linuxproblems.org/pnp4nagios/
 *
 *  The following parameters are taken from the Nagios CGIs status.cgi and extinfo.cgi.
 *
 *     extended			: display extended information (true or false)
 *     host			: display all hosts or a specific host whose services should be displayed
 *     hostgroup		: display all hostgroups or one specific hostgroup whose hosts and services should be displayed
 *     hoststatustypes		: display hosts in a given state 1=Pending; 2=Up; 4=Down; 8=Unreachable
 *     service			: display all services or a specific service
 *     servicegroup		: display hosts and services for all servicegroups or one specific servicegroup
 *     servicefilter		: display services matching a given pattern
 *     servicestatustypes	: display state the services should be in 1=Pending; 2=OK, 4=Warning; 8=Unknown; 16=Critical
 *     style			: display overview; detail; summary; grid; hostdetail
 *     type			: hosts; hostgroups; services; servicegroups; contacts; contactgroups; timeperiods; 
 *				  commands; hostescalations; serviceescalations; hostdependencies; servicedependencies
 *
 *  See http://docs.icinga.org/latest/en/cgiparams.html for more details on Nagios CGI parameters
 *
*/

 
class Nagios { 

	public static $status="";

	public static function init( &$parser ) {
                $parser->setHook( 'nagios', array( 'Nagios', 'renderNagios' ) );
                return true;
        }

	public static function renderNagios ( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgScriptPath, $wgOut, $nagiosStatusCounter, $nagiosExtinfoCounter, $wgNagiosLiveStatus, $wgNagiosPnp4Nagios;

		wfDebugLog( 'Nagios', "wfNagiosRender" );
		wfDebugLog( 'Nagios', "input=$input" );

		$output="";

		//table header
		$caption="<CAPTION>$input</CAPTION>";

		//invalidate cache
		$parser->disableCache();
	 
		//defaults
		$extended=false;
		$style="summary";

		$domain="";
		$host="";
		$hoststatustypes="";
		$nagioscgi="";
		$nagiosurl="";
		$path="";
		$pnp4url="";
		$service="";
		$servicegroup="";
		$type="";

		//parse xx values in <Nagios xx=yy> block and build the nagios url path
		foreach( $args as $name => $value ){
			switch(strtolower(htmlspecialchars($name))){ 
			case 'extended':
				$extended=htmlspecialchars($value);
				break;
			case 'host':
				$host=htmlspecialchars($value);
				$path.="&host=$host";
				break;
			case 'hostgroup':
                                $hostgroup=htmlspecialchars($value);
                                $path.="&hostgroup=$hostgroup";
                                break;
			case 'hoststatustypes':
				$hoststatustypes=htmlspecialchars($value);
				$path.="&hoststatustypes=$hoststatustypes";
				break;
			case 'nagiosurl':
				$nagiosurl=htmlspecialchars($value);
				$nagiosurl=self::sanitize($nagiosurl);
				break;
			case 'nagioscgi':
				$nagioscgi=htmlspecialchars($value);
				$nagioscgi=self::sanitize($nagioscgi);
				break;
			case 'pnp4url':
				$pnp4url=htmlspecialchars($value);
				$pnp4urli=self::sanitize($pnp4url);
				break;
			case 'service':
				$service=htmlspecialchars($value);
				$path.="&service=$service";
				break;
			case 'servicefilter':
                                $servicefilter=htmlspecialchars($value);
                                $path.="&servicefilter=$servicefilter";
                                break;
			case 'servicegroup':
				$servicegroup=htmlspecialchars($value);
				$path.="&servicegroup=$servicegroup";
				break;
			case 'servicestatustypes':
				$servicestatustypes=htmlspecialchars($value);
				$path.="&servicestatustypes=$servicestatustypes";
				break;
			case 'style':
				$style=htmlspecialchars($value);
				$path.="&style=$style";
				break;
			case 'type':
				$type=htmlspecialchars($value);
				$path.="&type=$type";
				break;
			}
		}

		// -------------------------------------------


		// check the nagios url can be accessed
		if(! self::checkUrl($nagiosurl)){
			wfDebugLog( 'Nagios', "nagiosurl=$nagiosurl could not connect" );
			$status=self::$status;
			$output= <<< EOT
<br><font color=red>
Failed to connect to nagios URL: $nagiosurl <br>
Status returned: $status
</font><br>
EOT;
			if (strpos($status,'401 Authorization Required')){
				$output.= <<< EOT
<br><font color=red>
See <a href="http://www.mediawiki.org/wiki/Extension:NagVis#Notes_about_authentication">NagVis Extension Notes about authentication</a>
</font><br>
EOT;
			}
			return $output;
		}
		$output="";

		wfDebugLog( 'Nagios', "nagiosurl=$nagiosurl, path=$path, extended=$extended, style=$style" );

		// set up default cgi-bin url if not specified
		if ( $nagioscgi=="" ){	
			$nagioscgi=$nagiosurl . "cgi-bin/";
			wfDebugLog( 'Nagios', "nagioscgi=$nagioscgi" );
		}

		// set up default pnp4nagios url if not specified
		if ( $wgNagiosPnp4Nagios==true && $pnp4url=="" ){
			$parse=parse_url($nagiosurl);
			$domain=$parse['host'];  
			$pnp4url='http://' . $domain . "/pnp4nagios/";
			wfDebugLog( 'Nagios', "pnp4url=$pnp4url" );
		}


		// -------------------------------------------

		// fetch the nagios page
		if($extended){
			$url=$nagioscgi . "extinfo.cgi?$path";
		}else{
			$url=$nagioscgi . "status.cgi?$path";
		}

		wfDebugLog( 'Nagios', "url=$url" );	

		// set up the requests headers
		$wgNagiosUserAgent="Mediawiki NagiosExtension";
		$nagiosUserAgentHeader = "User-Agent: $wgNagiosUserAgent";
		$headers= array ( "User-Agent: $wgNagiosUserAgent" );

		$opts = array('http' =>
                    array(
                        'method'  => 'GET',
                        'timeout' => 20,
                        'header'  => $headers
                        )
                );

		$context = stream_context_create($opts);
		require_once('includes/simple_html_dom.php');

		wfDebugLog( 'Nagios', 'Fetching the url');
		$html=file_get_html( $url, 0, $context );

		if($html==false){
			wfDebugLog( 'Nagios', "Failed to retrieve url." );
                        $output= <<< EOT
<br><font color=red>
ERROR: (nagiosurl)<br>
Failed to retrieve URL: $nagiosurl <br>
</font><br>
EOT;
                        return $output;

		}

		// -------------------------------------------

		// Add the css and js files to the output
		$wgOut->addModules( 'ext.nagios.common' );
		if($extended){
			$wgOut->addModules( 'ext.nagios.extinfo' );
		}else{
			$wgOut->addModules( 'ext.nagios.status' );
		}

		if($wgNagiosPnp4Nagios==true){
			$wgOut->addModules( 'ext.nagios.pnp4nagios' );
		}

		if(!$extended){
			// get the nagios status table and replace local links with remote nagios url
			wfDebugLog( 'Nagios', "Writing nagiosstatus div" );

			$output='<div class="status" id="nagiosstatus' . $nagiosStatusCounter++ . '">';
			$statustable=$html->find('table.status');
			foreach ($statustable as $s){
				$line="";

				$line=str_replace("/nagios",$nagiosurl ,$s);

				// add the table header
				$line=preg_replace('/(<table [^(class)]*class=\'status\'[^>]*>)/', "$1" . "$caption", $line);

				$line=str_replace("status.cgi",$nagioscgi . "status.cgi",$line);
				$line=str_replace("statusmap.cgi",$nagioscgi . "statusmap.cgi",$line);

				// removes sort columns header for tidiness
				$line=preg_replace('/<th class=\'status\'>(\w+)((?!<\/th>).)*<\/th>/',"<th class='status'>$1&nbsp;</th>",$line);

				$line=str_replace("extinfo.cgi",$nagioscgi . "extinfo.cgi",$line);

                                // add extinfo popup for host link
                                $line=preg_replace('/(extinfo\.cgi\?type=\d+&host=([^>]+)\')>(?!<img.*passive)/', "$1" . ' class="tips" >', $line);

				// add service details popup
                                if($wgNagiosLiveStatus==true){
					//service details
                                        $line=preg_replace('/(status\.cgi\?host=(\w+)\')\>/', "$1" . ' class=\'tips\' \' >', $line);
                                        $line=preg_replace('/title=\'View Service Details For This Host\'/', '', $line);
                                        $line=preg_replace('/(servicestatustypes=\d+&hoststatustypes=\d+&serviceprops=\d+&hostprops=\d+)/', "$1' class='tips'", $line);
					
					//extended information
                                	$line=preg_replace('/(extinfo\.cgi\?type=\d+&host=([^>]+)\')>(?!<img.*passive)/', "$1" . ' class="tips" >', $line);

					// get rid of title popups
					$line=preg_replace('/title=\'View Extended Information For This Host\'/', '', $line);
					$line=preg_replace('/title=\'Perform Extra (Host|Service) Actions\'/', '', $line);
				}

				if($wgNagiosPnp4Nagios==true){
					$line=preg_replace('/\/pnp4nagios\/(index\.php\/)?graph/',$pnp4url . "graph",$line);
					$line=preg_replace('/\/pnp4nagios\/(index\.php\/)?popup\?/', $wgScriptPath . '/extensions/Nagios/includes/popup.php?pnp4url=' . urlencode($pnp4url) . "&wgNagiosUserAgent=$wgNagiosUserAgent&",$line);
				}

				$output.=$line;
			}
		}else{
			// get the extended information table
			wfDebugLog( 'Nagios', "Writing nagiosextinfo div" );

			$output='<div class="stateInfoPanel" id="nagiosextinfo' . $nagiosExtinfoCounter++ . '">';
			$output.= "<TABLE BORDER=1 CELLSPACING=0 CELLPADDING=0>$caption";
		
			$stateInfoTable1=$html->find('td.stateInfoTable1');	
			foreach ($stateInfoTable1 as $line){
				$output.=$line;
			}

			$output.= " </TD></TR></TABLE>";
			
		}

		// close the div containuer
		$output.='</div>';

		$html->clear();
		unset($html);

		return $output;

	}

	// Check we can reach the url provided
	protected static function checkUrl( $url ){
		$headers = @get_headers($url);		
		$status=$headers[0];
		self::$status=$status;
		wfDebugLog( 'Nagios', "checkUrl: " . $headers[0] );
		if (is_array($headers)){
        		if(strpos($status, '200 OK')){
            			return true;
        		}else{
            			return false;    
			}
		}
		return false;
    	}         


	// Add http prefix if that gets missed off
	protected static function sanitize($url){
		if ((strpos($url, "http")) === false) {
			$url = "http://" . $url;
		}
		return $url;
	}
}

?>
