<?php
class Common {
	static function datumtransformation($point, $e1, $t, $e2) {
		// Based on http://www.movable-type.co.uk/scripts/latlong-convert-coords.html
		// -- convert polar to cartesian coordinates (using ellipse 1)
		extract($point);
		$sinPhi = sin($phi);
		$cosPhi = cos($phi);
		$sinLambda = sin($lambda);
		$cosLambda = cos($lambda);
		extract($e1);
		$eSq = ($a*$a - $b*$b) / ($a*$a);
		$nu = $a / sqrt(1 - $eSq*$sinPhi*$sinPhi);
		$x1 = ($nu+$height) * $cosPhi * $cosLambda;
		$y1 = ($nu+$height) * $cosPhi * $sinLambda;
		$z1 = ((1-$eSq)*$nu + $height) * $sinPhi;

		// -- apply helmert transform using appropriate params
		extract($t);
		// normalise seconds to radians
		$rx = deg2rad($rx/3600);
		$ry = deg2rad($ry/3600);
		$rz = deg2rad($rz/3600);
		$s1 = $s/1e6 + 1;              // normalise ppm to (s+1)

		// apply transform
		$x2 = $tx + $x1*$s1 - $y1*$rz + $z1*$ry;
		$y2 = $ty + $x1*$rz + $y1*$s1 - $z1*$rx;
		$z2 = $tz - $x1*$ry + $y1*$rx + $z1*$s1;


		// -- convert cartesian to polar coordinates (using ellipse 2)
		extract($e2);
		$precision = 4 / $a;  // results accurate to around 4 metres
		$eSq = ($a*$a - $b*$b) / ($a*$a);
		$p = sqrt($x2*$x2 + $y2*$y2);
		$phi = atan2($z2,                          $p*(1-$eSq));
		$phiP = 2*M_PI;
		while (abs($phi-$phiP) > $precision) {
			$nu = $a / sqrt(1 - $eSq*sin($phi)*sin($phi));
			$phiP = $phi;
			$phi = atan2($z2 + $eSq*$nu*sin($phi),    $p);
		}
		$point['phi'] = $phi;
		$point['lambda'] = atan2($y2, $x2);
		$point['height'] = $p/cos($phi) - $nu;
		return $point;
	}
}
