<?php
class Lambert {
	static function WGS84ToLAM($LAM, $x, $y ) {
		// Lambert projection
		// based on http://www.ngi.be/Common/Lambert2008/Transformation_Geographic_Lambert_NL.pdf
		extract($LAM);
		$m_1            = cos($phi_1) / sqrt(1 - pow($e * sin($phi_1),                                      2));
		$m_2            = cos($phi_2) / sqrt(1 - pow($e * sin($phi_2),                                      2));
		$t_1            = tan(M_PI / 4 - $phi_1 / 2) / pow((1 - $e * sin($phi_1)) / (1 + $e * sin($phi_1)), $e / 2);
		$t_2            = tan(M_PI / 4 - $phi_2 / 2) / pow((1 - $e * sin($phi_2)) / (1 + $e * sin($phi_2)), $e / 2);
		$t_0            = tan(M_PI / 4 - $phi_0 / 2) / pow((1 - $e * sin($phi_0)) / (1 + $e * sin($phi_0)), $e / 2);
		$n              = (log($m_1) - log($m_2)) / (log($t_1) - log($t_2));
		$g              = $m_1 / ($n * pow($t_1,                                                            $n));
		$r_0            = $a * $g * pow($t_0,                                                               $n);

		$phi            = floatval ($x) / 180 * M_PI;
		$lambda         = floatval ($y) / 180 * M_PI;
		$t              = tan(M_PI / 4 - $phi / 2) / pow((1 - $e * sin($phi)) / (1 + $e * sin($phi)),       $e / 2);
		$r              = $a * $g * pow($t,                                                                 $n);
		$theta          = $n * ($lambda - $lambda_0);
		$x_LAM = $x_0 + $r * sin($theta);
		$y_LAM = $y_0 + $r_0 - $r * cos($theta);

		return sprintf("%d/%d", $x_LAM, $y_LAM);
	}


	static function WGS84ToLAM93( &$parser, $x, $y ) {
		// WGS84 Latitude/Longitude to Lambert93  - France
		// based on http://professionnels.ign.fr/DISPLAY/000/526/695/5266956/Geodesie__projections.pdf
		$LB93['a']      = 6378137;              //demi grand axe de l'ellipsoide (m)
		$LB93['e']      = 0.08181919106 ;       //premire excentricit de l'ellipsoide
		$LB93['phi_1']  = deg2rad(44);          //1er parallele automcoque
		$LB93['phi_2']  = deg2rad(49);          //2eme parallele automcoque
		$LB93['phi_0']  = deg2rad(46.5);        //latitude d'origine en radian
		$LB93['lambda_0']= deg2rad(3);          //longitude de rfrence
		$LB93['x_0']    = 700000;               //coordonnes  l'origine
		$LB93['y_0']    = 6600000;              //coordonnes  l'origine
		return self::WGS84ToLAM($LB93, $x, $y );
	}

	static function WGS84ToLAM08( &$parser, $x, $y ) {
		// WGS84 Latitude/Longitude to Lambert2008 - Belgium
		// based on http://www.ngi.be/Common/Lambert2008/Transformation_Geographic_Lambert_NL.pdf
		$LB08['a']      = 6378137;              //demi grand axe de l'ellipsoide (m)
		$LB08['e']      = 0.08181919106 ;       //premire excentricit de l'ellipsoide
		$LB08['phi_1']  = deg2rad(49.833333333);//1er parallele automcoque  (49� 50' N)
		$LB08['phi_2']  = deg2rad(51.166666667);//2eme parallele automcoque ( 51� 10' N)
		$LB08['phi_0']  = deg2rad(50.797815);   //latitude d'origine en radian (50�47�52�134 N)
		$LB08['lambda_0']= deg2rad(4.359215833);//longitude de rfrence (4�21�33�177 E)
		$LB08['x_0']    = 649328;               //coordonnes  l'origine
		$LB08['y_0']    = 665262;               //coordonnes  l'origine
		return self::WGS84ToLAM($LB08, $x, $y );
  }
}
