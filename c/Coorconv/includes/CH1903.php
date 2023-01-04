<?php
class CH1903 {
        static function CH03( $x, $y, $z = null ) {
            // WGS84 Latitude/Longitude to CH1903 - Suisse
            // based on https://www.swisstopo.admin.ch/content/swisstopo-internet/en/topics/survey/reference-systems/switzerland/_jcr_content/contentPar/tabs/$
    				// found on https://www.swisstopo.admin.ch/en/knowledge-facts/surveying-geodesy/reference-systems/switzerland.html#dokumente_publikatio
    				// https://www.swisstopo.admin.ch/de/karten-daten-online/calculation-services/navref.html

                $phi=((floatval ($x)*3600)-169028.66)/10000;
                $lambda=((floatval ($y)*3600)-26782.5)/10000;

                $y_ch03 = 2600072.37
                + 211455.93 * $lambda
                - 10938.51 * $lambda * $phi
                - 0.36 * $lambda * $phi * $phi
                - 44.54 * $lambda * $lambda * $lambda;
                $x_ch03 = 1200147.07
                + 308807.95 * $phi
                + 3745.25 * $lambda * $lambda
                + 76.63 * $phi * $phi
                - 194.56 * $phi * $lambda * $lambda
                + 119.79 * $phi * $phi * $phi;

          if( !empty($z) ) {
                  $z_ch03 = $z - 49.55
                  + 2.73 * $lambda
                  + 6.94 *  $phi;
          } else {
              $z_ch03 = null;
          }
                $ret = array($x_ch03, $y_ch03, $z_ch03);
                return($ret);
        }

        static function WGS84ToCH1903( &$parser, $x, $y, $z = null  ) {
                $xyz_ch03    = self::CH03($x, $y, $z);
								if( empty($xyz_ch03[2]) ) {
                  return number_format($xyz_ch03[1]-2000000, 0, '.', '&#39;') .", " .number_format($xyz_ch03[0]-1000000, 0, '.', '&#39;');
                } else {
                  return number_format($xyz_ch03[1]-2000000, 0, '.', '&#39;') .", " .number_format($xyz_ch03[0]-1000000, 0, '.', '&#39;').", ".number_format($xyz_ch03[2],1);;
								}
        }
        static function WGS84ToCH1903p( &$parser, $x, $y, $z = null  ) {
                $xyz_ch03    = self::CH03($x, $y, $z);
								if( empty($xyz_ch03[2]) ) {
                  return number_format($xyz_ch03[1], 0, '.', '&#39;') .", " .number_format($xyz_ch03[0], 0, '.', '&#39;');
                } else {
                  return number_format($xyz_ch03[1], 0, '.', '&#39;') .", " .number_format($xyz_ch03[0], 0, '.', '&#39;').", ".number_format($xyz_ch03[2],1);;
								}
        }
}
