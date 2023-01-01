<?php
class TransM {
  	static function TM($phi, $l) {
  		// transverse Mercator projection
  		// Based on http://www.igorexchange.com/node/927 and http://home.hiwaay.net/~taylorc/toolbox/geography/geoutm.html
  		$sm_a = 6378137.0;
  		$sm_b = 6356752.314;
  		//      $ep2 = ($sm_a * $sm_a - $sm_b * $sm_b) / ($sm_b * $sm_b);
  		$ep2 = 0.00673949681993606;
  		$nu2 = $ep2 * pow(cos($phi), 2.0);
  		$N = pow ($sm_a,             2.0) / ($sm_b * sqrt(1 + $nu2));
  		$t = tan ($phi);
  		$t2= $t * $t;
  		$l3coef = 1.0 - $t2 + $nu2;
  		$l4coef = 5.0 - $t2 + 9 * $nu2 + 4.0 * ($nu2 * $nu2);
  		$l5coef = 5.0 - 18.0 * $t2 + ($t2 * $t2) + 14.0 * $nu2 - 58.0 * $t2 * $nu2;
  		$l6coef = 61.0 - 58.0 * $t2 + ($t2 * $t2) + 270.0 * $nu2 - 330.0 * $t2 * $nu2;
  		$l7coef = 61.0 - 479.0 * $t2 + 179.0 * ($t2 * $t2) - ($t2 * $t2 * $t2);
  		$l8coef = 1385.0 - 3111.0 * $t2 + 543.0 * ($t2 * $t2) - ($t2 * $t2 * $t2);
  		$x      = $N * cos($phi) * $l
  		+ ($N / 6.0    * pow(cos($phi), 3.0) * $l3coef * pow($l, 3.0))
  		+ ($N / 120.0  * pow(cos($phi), 5.0) * $l5coef * pow($l, 5.0))
  		+ ($N / 5040.0 * pow(cos($phi), 7.0) * $l7coef * pow($l, 7.0));

  		/*      $nn = ($sm_a - $sm_b) / ($sm_a + $sm_b);
  		$alpha = (($sm_a + $sm_b) / 2.0) * (1.0 + (pow($nn, 2.0) / 4.0) + (pow($nn, 4.0) / 64.0));
  		$beta = (-3.0 * $nn / 2.0) + (9.0 * pow($nn, 3.0) / 16.0) + (-3.0 * pow($nn, 5.0) / 32.0);
  		$gamma = (15.0 * pow($nn, 2.0) / 16.0) + (-15.0 * pow($nn, 4.0) / 32.0);
  		$delta = (-35.0 * pow($nn, 3.0) / 48.0) + (105.0 * pow($nn, 5.0) / 256.0);
  		$epsilon = (315.0 * pow($nn, 4.0) / 512.0);

  		$length = $alpha * ($phi        + ($beta    * sin(2.0 * $phi))
  		+ ($gamma   * sin(4.0 * $phi))
  		+ ($delta   * sin(6.0 * $phi))
  		+ ($epsilon * sin(8.0 * $phi)));
  		*/
  		$length = 6367449.14570093 * ($phi      - 2.51882794504748e-3  * sin(2.0 * $phi)
  		+ 2.64354112052895e-6  * sin(4.0 * $phi)
  		- 3.45262354148954e-9  * sin(6.0 * $phi)
  		+ 4.89183055303118E-12 * sin(8.0 * $phi));
  		$y      = $length
  		+ ($t / 2.0     * $N * pow(cos($phi),  2.0) * pow($l, 2.0))
  		+ ($t / 24.0    * $N * pow(cos($phi),  4.0) * $l4coef * pow($l, 4.0))
  		+ ($t / 720.0   * $N * pow(cos($phi),  6.0) * $l6coef * pow($l, 6.0))
  		+ ($t / 40320.0 * $N * pow(cos($phi),  8.0) * $l8coef * pow($l, 8.0));
  		$ret = array($x, $y);
  		return($ret);
  	}

	static function WGS84ToUTM( &$parser, $phi_d, $lambda_d, $zone='') {
		// WGS84 Latitude/Longitude to UTM
		// Based on http://www.igorexchange.com/node/927 and http://home.hiwaay.net/~taylorc/toolbox/geography/geoutm.html
		$bandletter = array ("C", "D","E","F","G","H","J","K","L","N","P","Q","R","S","T","U","V","W","X","X");
		$TMScaleFactor = 0.9996;
		if ( $phi_d >= 84 || $phi_d <= -80) return "Polar area, use Universal Polar Stereographic (UPS)";
		// Special zone for Norway
		if(!$zone && $phi_d >= 56.0 && $phi_d < 64.0 && $lambda_d >= 3.0 && $lambda_d < 12.0 )
		$zone = 32;
		// Special zones for Svalbard
		if(!$zone && $phi_d >= 72.0 && $phi_d < 84.0)
		if  ( $lambda_d >= 0.0  && $lambda_d <  9.0 )
		$zone = 31;
		elseif( $lambda_d >= 9.0  && $lambda_d < 21.0 )
		$zone = 33;
		elseif($lambda_d >= 21.0 && $lambda_d < 33.0 )
		$zone = 35;
		elseif($lambda_d >= 33.0 && $lambda_d < 42.0 )
		$zone = 37;
		if (!$zone)
		$zone = floor(floatval ($lambda_d)/6) + 31;
		$band = $bandletter[floor((floatval ($phi_d)+72)/8)];
		$lambda0 = deg2rad(-183.0 + ($zone * 6.0));
		$lambda=deg2rad(floatval ($lambda_d));
		$l = floatval ($lambda) - floatval ($lambda0);
		$phi=deg2rad(floatval ($phi_d));
		$xy     = self::TM($phi, $l);
		$x_tm   = $xy[0]*$TMScaleFactor + 500000;
		$y_tm   = $xy[1]*$TMScaleFactor;
		if($phi_d < 0)
		$y_tm += 10000000; //10000000 meter offset for southern hemisphere
		return sprintf("%d%s %dm E %dm N", $zone,$band, $x_tm, $y_tm);
	}

	static function WGS84ToTM35FIN( &$parser, $phi_d, $lambda_d) {
		// WGS84 Latitude/Longitude to TM35FIN = extended UTM zone 35N - Finland
		// Based on http://www.samenland.nl/pdf/the_change_of_coordinate_system_in_finland.pdf
		$TMScaleFactor = 0.9996;
		$lambda0 = deg2rad(27); //UTM 35N
		$lambda=deg2rad(floatval ($lambda_d));
		$l = floatval ($lambda) - floatval ($lambda0);
		$phi=deg2rad(floatval ($phi_d));
		$xy     = self::TM($phi, $l);
		$x_tm   = $xy[0]*$TMScaleFactor + 500000;
		$y_tm   = $xy[1]*$TMScaleFactor;
		if ($x_tm < 0)
		return sprintf("&nbsp;&nbsp;%dE, %dN", $x_tm + 8000000, $y_tm);
		else
		return sprintf("(8)%dm E %dm N", $x_tm, $y_tm);
	}

	static function WGS84ToMTM( &$parser, $phi_d, $lambda_d, $zone='') {
		// WGS84 Latitude/Longitude to MTM Canada
		// Based on https://leware.net/geo/utmgoogle.htm
		// MTM zone to reference meridian
		$mtmSmers = array (0, -53, -56, -58.5, -61.5, -64.5, -67.5, -70.5, -73.5, -76.5, -79.5, -82.5, -81, -84, -87, -90, -93, -96, -99, -102, -105, -108, -111, -114, -117, -120, -123, -126, -129, -132, -135, -138, -141);  // last was 142 ?!! I think it should be 141.

		// ? matches http://www.posc.org/Epicentre.2_2/DataModel/LogicalDictionary/StandardValues/coordinate_transformation.html

		if (!$zone)  // determine zone from lat/lon
		{
			if ($lambda_d < -51.5 && $lambda_d >= -54.5)                                    $zone=1;
			if ($lambda_d < -54.5 && $lambda_d >= -57.5)                                    $zone=2;
			if ($lambda_d < -57.5 && $lambda_d >= -59.5  && $phi_d <= 46.5
			||  $lambda_d < -57.5 && $lambda_d >= -60    && $phi_d >  46.5 )                $zone=3;
			if ($lambda_d < -59.5 && $lambda_d >= -63.   && $phi_d <  46.5
			||  $lambda_d < -60   && $lambda_d >= -63    && $phi_d >= 46.5 )                $zone=4;
			if ($lambda_d < -63   && $lambda_d >= -66.5  && $phi_d <= 44.75
			||  $lambda_d < -63   && $lambda_d >= -66    && $phi_d >  44.75 )               $zone=5;
			if ($lambda_d < -66   && $lambda_d >= -69    && $phi_d >  44.75
			||  $lambda_d < -66.5 && $lambda_d >= -69    && $phi_d <= 44.75 )               $zone=6;
			if ($lambda_d < -69   && $lambda_d >= -72)                                      $zone=7;
			if ($lambda_d < -72   && $lambda_d >= -75)                                      $zone=8;
			if ($lambda_d < -75   && $lambda_d >= -78)                                      $zone=9;
			if ($lambda_d < -78   && $lambda_d >= -79.5  && $phi_d >  47
			||  $lambda_d < -78.  && $lambda_d >= -80.25 && $phi_d <= 47 && $phi_d > 46
			||  $lambda_d < -78   && $lambda_d >= -81    && $phi_d <= 46)                   $zone=10;
			if ($lambda_d < -81   && $lambda_d >= -84    && $phi_d <= 46)                   $zone=11;
			if ($lambda_d < -79.5 && $lambda_d >= -82.5  && $phi_d >  47
			||  $lambda_d < -80.25&& $lambda_d >= -82.5  && $phi_d <= 47 && $phi_d > 46)    $zone=12;
			if ($lambda_d < -82.5 && $lambda_d >= -85.5  && $phi_d >  46)                   $zone=13;
			// still not found, try regular Western Canada
			if (!$zone)
			$zone = floor(($lambda_d + 85.5)/-3) + 14;
			}
			if ($zone < 1 || $zone > 32)
			return "Outside Canada";
			else
			$lambda0 = $mtmSmers[$zone];
			$l = deg2rad($lambda_d - $lambda0);
			$phi=deg2rad($phi_d);
			$xy     = self::TM($phi, $l);
			$TMScaleFactor = 0.9999;
			$x_tm   = $xy[0]*$TMScaleFactor + 304800;
			$y_tm   = $xy[1]*$TMScaleFactor;
			return sprintf("%d %dm E %dm N", $zone, $x_tm, $y_tm);
	}
}
