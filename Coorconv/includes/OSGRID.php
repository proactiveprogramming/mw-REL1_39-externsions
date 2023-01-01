<?php
class OSGRID {
	static function WGS84ToOSGB36( &$parser,  $phi_d, $lambda_d, $height = 0) {
		// WGS84 Latitude/Longitude to OSGB36 - Great Britain
		// Based on http://www.movable-type.co.uk/scripts/latlong-convert-coords.html
		$WGS84['a']     = 6378137;
		$WGS84['b']     = 6356752.3142;
		//      $WGS84['f']     = 1/298.257223563;
		$Airy1830['a']  = 6377563.396;
		$Airy1830['b']  = 6356256.910;
		//      $Airy1830['f']  = 1/299.3249646;
		$WGS84toOSGB36['tx']= -446.448;
		$WGS84toOSGB36['ty']=  125.157;
		$WGS84toOSGB36['tz']= -542.060;
		$WGS84toOSGB36['rx']=   -0.1502;
		$WGS84toOSGB36['ry']=   -0.2470;
		$WGS84toOSGB36['rz']=   -0.8421;
		$WGS84toOSGB36['s']=    20.4894;
		$originOSGB36['F0'] = 0.9996012717;             // NatGrid scale factor on central meridian
		$originOSGB36['phi0'] = deg2rad(49);
		$originOSGB36['lambda0'] = deg2rad(-2);         // NatGrid true origin
		$originOSGB36['N0'] = -100000;
		$originOSGB36['E0'] = 400000;                   // northing & easting of true origin, metres
		$point['phi'] = deg2rad(floatval ($phi_d));
		$point['lambda'] = deg2rad(floatval ($lambda_d));
		$point['height'] = $height;
		$point = Common::datumtransformation($point, $WGS84, $WGS84toOSGB36, $Airy1830);
		$grid = self::LatLongToOSGrid($point, $Airy1830,$originOSGB36);
		//      level 1 transformation
		//      $grid = self::LatLongToOSGrid($point,$WGS84,$originOSGB36);
		//      $grid['E'] += 49;
		//      $grid['N'] -= 23.4;
		return self::gridrefNumToLetGB($grid, 10);
		}

		static function WGS84ToIG( &$parser, $phi_d, $lambda_d, $height = 0) {
			// WGS84 Latitude/Longitude to Irish Grid - Republic Ireland and North Ireland
			// Based on http://www.movable-type.co.uk/scripts/latlong-convert-coords.html
			// Based on http://www.osni.gov.uk/2.1_the_irish_grid.pdf
			$WGS84['a']     = 6378137;
			$WGS84['b']     = 6356752.3142;
			//      $WGS84['f']     = 1/298.257223563;
			$Airy1830_m['a']= 6377340.189;
			$Airy1830_m['b']= 6356034.447;
			//      $Airy1830_m['f']= ;
			$WGS84toOSI['tx']= -482.53;
			$WGS84toOSI['ty']=  130.596;
			$WGS84toOSI['tz']= -564.557;
			$WGS84toOSI['rx']=   -1.042;
			$WGS84toOSI['ry']=   -0.214;
			$WGS84toOSI['rz']=   -0.631;
			$WGS84toOSI['s']=    -8.15;
			$originOSI['F0'] = 1.000035;            // NatGrid scale factor on central meridian
			$originOSI['phi0'] = deg2rad(53.5);
			$originOSI['lambda0'] = deg2rad(-8);            // NatGrid true origin
			$originOSI['N0'] = 250000;
			$originOSI['E0'] = 200000;                      // northing & easting of true origin, metres

			$point['phi'] = deg2rad(floatval ($phi_d));
			$point['lambda'] = deg2rad(floatval ($lambda_d));
			$point['height'] = $height;
			//      $point = self::datumtransformation($point, $WGS84, $WGS84toOSI, $Airy1830_m);
			//      $grid = self::LatLongToOSGrid($point,$Airy1830_m,$originOSI);
			//      level 1 transformation
			$grid = self::LatLongToOSGrid($point, $WGS84,$originOSI);
			$grid['E'] += 49;
			$grid['N'] -= 23.4;
//			$grid['height'] = $height;
			return self::gridrefNumToLetIG($grid, 8);
		}

		static function WGS84ToITM( &$parser, $phi_d, $lambda_d, $height = 0) {
			// WGS84 Latitude/Longitude to ITM Ireland (doesn't work yet !!!!!!!!!!!!!!)
			$WGS84['a']     = 6378137;
			$WGS84['b']     = 6356752.3142;
			$originITM['F0'] = 0.999820;            // NatGrid scale factor on central meridian
			$originITM['phi0'] = deg2rad(53.5);
			$originITM['lambda0'] = deg2rad(-8);            // NatGrid true origin
			$originITM['N0'] = 750000;
			$originITM['E0'] = 600000;                      // northing & easting of true origin, metres
			$point['phi'] = deg2rad($phi_d);
			$point['lambda'] = deg2rad($lambda_d);
			$point['height'] = $height;
			$grid = self::LatLongToOSGrid($point, $WGS84,$originITM);
			return sprintf("%dm E %dm N", $grid['E'], $grid['N']);
			}

		/*
		* convert geodesic co-ordinates to OS grid reference (transverse Mercator projection)
		*/
		static function LatLongToOSGrid($point, $ellipse,$origin) {
			// Based on http://www.movable-type.co.uk/scripts/latlong-gridref.html
			extract($point);
			extract($ellipse);
			extract($origin);
			$e2 = 1 - ($b*$b)/($a*$a);      // eccentricity squared
			$n = ($a-$b)/($a+$b);
			$n2 = $n*$n;
			$n3 = $n*$n*$n;
			$cosLat = cos($phi);
			$sinLat = sin($phi);
			$nu = $a*$F0/sqrt(1-$e2*$sinLat*$sinLat);              // transverse radius of curvature
			$rho = $a*$F0*(1-$e2)/pow(1-$e2*$sinLat*$sinLat, 1.5);  // meridional radius of curvature
			$eta2 = $nu/$rho-1;

			$Ma = (1 + $n + (5/4)*$n2 + (5/4)*$n3) * ($phi-$phi0);
			$Mb = (3*$n + 3*$n*$n + (21/8)*$n3) * sin($phi-$phi0) * cos($phi+$phi0);
			$Mc = ((15/8)*$n2 + (15/8)*$n3) * sin(2*($phi-$phi0)) * cos(2*($phi+$phi0));
			$Md = (35/24)*$n3 * sin(3*($phi-$phi0)) * cos(3*($phi+$phi0));
			$M = $b * $F0 * ($Ma - $Mb + $Mc - $Md);              // meridional arc

			$cos3lat = $cosLat*$cosLat*$cosLat;
			$cos5lat = $cos3lat*$cosLat*$cosLat;
			$tan2lat = tan($phi)*tan($phi);
			$tan4lat = $tan2lat*$tan2lat;

			$I = $M + $N0;
			$II = ($nu/2)*$sinLat*$cosLat;
			$III = ($nu/24)*$sinLat*$cos3lat*(5-$tan2lat+9*$eta2);
			$IIIA = ($nu/720)*$sinLat*$cos5lat*(61-58*$tan2lat+$tan4lat);
			$IV = $nu*$cosLat;
			$V = ($nu/6)*$cos3lat*($nu/$rho-$tan2lat);
			$VI = ($nu/120) * $cos5lat * (5 - 18*$tan2lat + $tan4lat + 14*$eta2 - 58*$tan2lat*$eta2);

			$dLon = $lambda-$lambda0;
			$dLon2 = $dLon*$dLon;
			$dLon3 = $dLon2*$dLon;
			$dLon4 = $dLon3*$dLon;
			$dLon5 = $dLon4*$dLon;
			$dLon6 = $dLon5*$dLon;

			$Grid['N'] = $I + $II*$dLon2 + $III*$dLon4 + $IIIA*$dLon6;
			$Grid['E'] = $E0 + $IV*$dLon + $V*$dLon3 + $VI*$dLon5;
			$Grid['height'] = (empty($height)) ? null : $height;
			return $Grid;
		}

		/*
		* convert numeric grid reference (in metres) to standard-form grid ref
		*/
		static function gridrefNumToLetGB($Grid, $digits) {
			// Based on http://www.movable-type.co.uk/scripts/latlong-gridref.html
			extract($Grid);
			// get the 100km-grid indices
			$e100k = floor($E/100000);
			$n100k = floor($N/100000);
			if ($e100k<0 || $e100k>6 || $n100k<0 || $n100k>12) return '';

			// translate those into numeric equivalents of the grid letters
			$l1 = (19-$n100k) - (19-$n100k)%5 + floor(($e100k+10)/5);
			$l2 = (19-$n100k)*5%25 + $e100k%5;

			// compensate for skipped 'I' and build grid letter-pairs
			if ($l1 > 7) $l1++;
			if ($l2 > 7) $l2++;
			$letPair = chr($l1+ord('A')).chr($l2+ord('A'));

			// strip 100km-grid indices from easting & northing, and reduce precision
			$e = floor(($E%100000)/pow(10, 5-$digits/2));
			$n = floor(($N%100000)/pow(10, 5-$digits/2));
			switch ($digits)
			{
				case 2:
				return sprintf("%s %'01d %'01d %.1f", $letPair, $e, $n, $height);
				break;
				case 4:
				return sprintf("%s %'02d %'02d %.1f", $letPair, $e, $n, $height);
				break;
				case 6:
				return sprintf("%s %'03d %'03d %.1f", $letPair, $e, $n, $height);
				break;
				case 10:
				return sprintf("%s %'05d %'05d %.1f", $letPair, $e, $n, $height);
				break;
				default:
				return sprintf("%s %'04d %'04d %.1f", $letPair, $e, $n, $height);
				}
			}

			static function gridrefNumToLetIG($Grid, $digits) {
				// Based on http://www.movable-type.co.uk/scripts/latlong-gridref.html
				extract($Grid);
				// get the 100km-grid indices
				$e100k = floor($E/100000);
				$n100k = floor($N/100000);
				if ($e100k<0 || $e100k>5 || $n100k<0 || $n100k>5) return '';

				// translate those into numeric equivalents of the grid letter
				$l = (4-$n100k)*5 + $e100k;

				// compensate for skipped 'I'
				if ($l > 7) $l++;
				$let = chr($l+ord('A'));

				// strip 100km-grid indices from easting & northing, and reduce precision
				$e = floor(($E%100000)/pow(10, 5-$digits/2));
				$n = floor(($N%100000)/pow(10, 5-$digits/2));
				switch ($digits)
				{
					case 2:
					return sprintf("%s %'01d %'01d", $let, $e, $n);
					break;
					case 4:
					return sprintf("%s %'02d %'02d", $let, $e, $n);
					break;
					case 6:
					return sprintf("%s %'03d %'03d", $let, $e, $n);
					break;
					case 10:
					return sprintf("%s %'05d %'05d", $let, $e, $n);
					break;
					default:
					return sprintf("%s %'04d %'04d", $let, $e, $n);
				}
			}
}
