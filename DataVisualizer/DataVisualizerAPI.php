<?php

class DataVisualizerAPI {

	/*
	* Example of JSON data for this format in examples/d3_tree.json
	* However, this function accepts the same format of data in PHP array form and itself does the conversion to JSON
	*/
	static function getHTMLForTree( $data ){
		global $wgOut;
		$wgOut->addModules( 'ext.DataVisualizer' );

		return '<div class="dv_d3_tree" style="text-align:center;" dv_data=' . json_encode($data) .'></div>';
	}

}