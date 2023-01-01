<?php
/* ProjectEngine compute server
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/ProjectEngine
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
 * PEGitInterface
 *
 * Subclass of PERepositoryInterface, for interfacing with
 * projects stored in MediaWiki with the WorkingWiki extension.
 *
 * At some point I wrote:
 * [This class deals with WorkingWiki projects accessed through WW's http api
 * Another class will work as a subprocess of WW that communicates with it]
 * but it's not true currently - this class leaves it to WW to push updates
 * to the source files, and doesn't make calls to WW to get them.
*/

class PEWorkingWikiInterface extends PERepositoryInterface
{ var $base, $project;

  public function __construct($loc, $session)
  { if (!preg_match('{^https?://(.*):(.*?)$}i', $loc, $matches))
      PEMessage::throw_error('Bad location ' . htmlspecialchars($loc) . '.');
    $this->base = $matches[1];
    $this->project = $matches[2];
    parent::__construct($loc,$session);
  }

  public function uri_scheme()
  { return 'pe-ww';
  }

  public function sync_from_repo_internal( $request )
  { $cache_dir = $this->wd->directory_name();
    if (file_exists($cache_dir))
    {# PEMessage::record_message("$cache_dir exists");
    }
    else if (0) #if ($useAPI) # forget this until some other time
    { // construct an API call to get the source files
      $crl = curl_init();
      curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($crl, CURLOPT_USERAGENT, 'ProjectEngine');

      // first log in
      curl_setopt($crl, CURLOPT_POST, true);
      curl_setopt($crl, CURLOPT_HEADER, true);
      curl_setopt($crl, CURLOPT_URL,"http://$this->base/api.php");
      curl_setopt($crl, CURLOPT_POSTFIELDS,
        array('action'     => 'login',
              'lguser'     => 'wonder',
              'lgpassword' => '749-1471'));
      $curl_output = curl_exec($crl);
      $cookie = '';
      if (preg_match('/^Set-Cookie: (.*?)$/m', $curl_output, $m))
        $cookie = $m[1];
      PEMessage::record_message("Cookie: "
        ."<pre>". htmlspecialchars($cookie) . "</pre>");

      // now use the cookie
      // to get a placeholder page.
      // to come: actual API call that returns WW data.
      curl_setopt($crl, CURLOPT_COOKIE, $cookie);
      curl_setopt($crl, CURLOPT_HTTPGET, true);
      curl_setopt($crl, CURLOPT_HEADER, false);
      $url = "http://$this->base/api.php?action=query&prop=revisions&"
        ."titles=Main%20Page&rvprop=content&format=xml";
      curl_setopt($crl, CURLOPT_URL,$url);
      $curl_output = curl_exec($crl);
      PEMessage::record_message("Output of ".htmlspecialchars($url)
        .": <pre>". htmlspecialchars($curl_output) . "</pre>");
    }
    else
      if (mkdir($cache_dir,0700,true) === false)
        PEMessage::throw_error("Could not create working directory "
          . "for project " . htmlspecialchars($this->project));
        #PEMessage::record_message("Could not mkdir "
        #  . htmlspecialchars($cache_dir) . ".");
    return true;
  }
}
