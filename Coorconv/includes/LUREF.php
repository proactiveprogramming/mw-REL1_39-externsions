<?php
class LUREF {
			static function WGS84ToLUREF( &$parser,  $phi_d, $lambda_d) {
				// WGS84 Latitude/Longitude to LUREF - Luxembourg
				// Based on http://www.act.etat.lu/datum.html
				$HAYFORD24['a'] = 6378388;
				$HAYFORD24['b'] = 6356911.946;
				$originLUREF['F0'] = 1;         // scale factor on central meridian
				$originLUREF['phi0'] = deg2rad(49 + 50/60);
				$originLUREF['lambda0'] = deg2rad(6 + 10/60);           // NatGrid true origin
				$originLUREF['N0'] = 100000;
				$originLUREF['E0'] = 80000;                     // northing & easting of true origin, metres
				//      no datumtransformation needed, Luxembourg is small enough
				$point['phi'] = deg2rad(floatval ($phi_d));
				$point['lambda'] = deg2rad(floatval ($lambda_d));
				$grid = OSGRID::LatLongToOSGrid($point, $HAYFORD24,$originLUREF);
				extract($grid);
				return sprintf("%dm E %dm N",           $E, $N);
		}
}
