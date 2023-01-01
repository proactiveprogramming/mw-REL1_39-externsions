<?php
class RD {
	static function WGS84ToRD( &$parser, $x, $y = null  ) {
		if( $y == null) {
		                   $array = explode(',', $x);
		                   $x = $array[0];
		                   $y = $array[1];
		                  }
				// WGS84 Latitude/Longitude to RD - the Netherlands
				// based on http://www.dekoepel.nl/pdf/Transformatieformules.pdf
				$phi    = 0.36 * ($x - 52.15517440);
				$lambda = 0.36 * ($y -  5.38720621);

				$x_rd   = 155000
								+ 190094.945 * $lambda
								- 11832.228 * $lambda * $phi
								- 114.221 * $lambda * $phi * $phi
								- 32.391 * $lambda * $lambda * $lambda
								- 0.705 * $phi
								- 2.340 * $phi * $phi * $phi * $lambda
								- 0.608 * $phi * $lambda * $lambda * $lambda
								- 0.008 * $lambda * $lambda
								+ 0.148 * $phi * $phi * $lambda * $lambda * $lambda;

				$y_rd   = 463000
								+ 309056.544 * $phi
								+ 3638.893 * $lambda * $lambda
								+ 73.077 * $phi * $phi
								- 157.984 * $phi * $lambda * $lambda
								+ 59.788 * $phi *$phi * $phi
								+ 0.433 * $lambda
								- 6.439 * $phi * $phi * $lambda * $lambda
								- 0.032 * $phi * $lambda
								+ 0.092 * $lambda * $lambda * $lambda * $lambda
								- 0.054 * $phi * $lambda * $lambda * $lambda * $lambda;

				//      return sprintf("%06d%06d",$x_rd, $y_rd);
				return number_format($x_rd, 0, ',', ' ') ."-" .number_format($y_rd, 0, ',', ' ');
			}
}
