<?php

/**
 * gchart4mw.php
 * provide tags for drawing charts the easy way using google chart API.
 * written by Dirk Festerling
 * http://code.google.com/p/gchart4mw
 * To activate the functionality of this extension include the following in your
 * LocalSettings.php file:
 *
 * $gchartWikiDefaults = Array ( "size" => "200x120" );
 * $gchartLinesDefaults = Array ( "grid" => "xy", "ymin" => "0", "ylabel" => "4");
 * $gchartBarsDefaults = Array ( "grid" => "y", "ymin" => "0", "ylabel" => "4" );
 * $gchartPieDefaults = Array ( "3d" => "3d" );
 * require_once( "$IP/extensions/gchart4mw/gchart4mw.php" );
 */

error_reporting (E_ALL);

if(! defined( 'MEDIAWIKI' ) ) {
  echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
  die( -1 );
} else {
  $wgExtensionCredits['parserhook'][] = array(
    'name' => 'googlechart',
    'author' =>'Dirk Festerling', 
    'url' => 'http://code.google.com/p/gchart4mw',
    'description' => 'this is an extension to use google charts in your wiki easily.'
    );
}

$wgExtensionFunctions[] = 'gfChartSetup';

// -----------------------------------------------------------------------------
function gfChartSetup() {
  global $wgParser;
  $wgParser->setHook( 'lines', 'gfLinesRender' );
  $wgParser->setHook( 'bars', 'gfBarsRender' );
  $wgParser->setHook( 'pie', 'gfPieRender' );
}
          
// -----------------------------------------------------------------------------
function gfChartInit() {
  global $fieldsep;
  global $hasxlabel;
  global $hasylabel;
  global $haslegend;
  global $hasxgrid;
  global $hasygrid;
  global $ishorizontal;
  global $size;
  global $title;
  global $colors;
  global $fill;
  global $isstacked;
  global $is3d;
  global $min;
  global $max;
  global $ysteps;
  
  $fieldsep = ",";
  $hasxlabel = false;
  $hasylabel = false;
  $haslegend = false;
  $hasxgrid = false;
  $hasygrid = false;
  $ishorizontal = false;
  $size = "200x120";
  $title = "";
  $colors = "";
  $fill = "";
  $isstacked = false;
  $is3d = false;
  $min = 4294967296;
  $max = -1;
  $ysteps = 2;
}
  
// -----------------------------------------------------------------------------
function gfArgsDebug ( $args ) {
  $attr = array();    
  // make a list of attributes and their values and dump them, along with the user input
  foreach( $args as $name => $value )
    $attr[] = '<strong>' . htmlspecialchars( $name ) . '</strong> = ' . htmlspecialchars( $value );
  $rslt = implode( '<br />', $attr ) . "<br />";

  return $rslt;
}

// -----------------------------------------------------------------------------
function gfArgsParseCommon ( $args ) {

  global $fieldsep;
  global $hasxlabel;
  global $hasylabel;
  global $haslegend;
  global $hasxgrid;
  global $hasygrid;
  global $ishorizontal;
  global $size;
  global $title;
  global $colors;
  global $fill;
  global $isstacked;
  global $is3d;
  global $min;
  global $max;
  global $ysteps;
  
  if (is_null($args)) return;

  foreach( $args as $name => $value ) {
    switch ($name) {
      case "size":
        $size = $value;
        break;
      case "title":
        $title = "&chtt=" . implode("+",explode(" ",$value));
        break;
      case "colors":
        $colors = "&chco=" . $value;
        break;
      case "nocolors":
        $colors = "";
        break;
      case "fill":
        $fill = "&chf=" . $value;
        break;
      case "nofill":
        $fill = "";
        break;
      case "fieldsep":
        $fieldsep = $value;
        break;
      case "ymin":
        $min = $value;
        break;
      case "ymax":
        $max = $value;
        break;
      case "ylabel":
        $hasylabel=true;
        $ysteps = $value;
        if ($ysteps == "ylabel") $ysteps = 2;
        break;
      case "noylabel":
        $hasylabel=false;
        break;
      case "xlabel":
        $hasxlabel=true;
        break;
      case "noxlabel":
        $hasxlabel=false;
        break;
      case "legend":
        $haslegend=true;
        break;
      case "nolegend":
        $haslegend=false;
        break;
      case "horizontal":
        $ishorizontal=true;
        break;
      case "nohorizontal":
        $ishorizontal=false;
        break;
      case "stacked":
        $isstacked=true;
        break;
      case "nostacked":
        $isstacked=false;
      case "3d":
        $is3d=true;
        break;
      case "no3d":
        $is3d=false;
        break;
      case "grid":
        switch ($value) {
          case "xy":
            $hasxgrid=true;
            $hasygrid=true;
            break;
          case "yx":
            $hasxgrid=true;
            $hasygrid=true;
            break;
          case "x":
            $hasxgrid=true;
            $hasygrid=false;
            break;
          case "y":
            $hasxgrid=false;
            $hasygrid=true;
            break;
        }
        break;
      case "nogrid":
        $hasxgrid=false;
        $hasygrid=false;
        break;
    }
  }
}

// -----------------------------------------------------------------------------
function gfArgsRenderCommon () {
  // parses all the parameters common to all types of charts

  global $size;
  global $title;
  global $colors;
  global $fill;

  $rslt = '<img src="http://chart.apis.google.com/chart?chs=' . $size;
  if ($title <> "") {
    $rslt = $rslt . $title;
  }
  if ($colors <> "") {
  $rslt = $rslt . $colors;
  }
  if ($fill <> "") {
  $rslt = $rslt . $fill;
  }
  return $rslt;
}

// -----------------------------------------------------------------------------
function gfArgsRenderLine () {
  // parses all additional parameters for line charts 
  $rslt = "&cht=lc";  
  return $rslt;
}

// -----------------------------------------------------------------------------
function gfArgsRenderBars () {
  // parses all additional parameters for Bar charts

  global $ishorizontal;
  global $isstacked;

  if ($ishorizontal){
    $rslt = "&cht=bh";
  } else {
    $rslt = "&cht=bv";
  }
  
  if ($isstacked) {
    $rslt = $rslt . "s";
  } else {
    $rslt = $rslt . "g";
  }
  return $rslt;
}
 
// -----------------------------------------------------------------------------
function gfArgsRenderPie () {
  // parses all additional parameters for Pie charts

  global $is3d;

  $rslt = "&cht=p";

  if ($is3d) {
    $rslt = $rslt . "3";
  }
  
  return $rslt;
}

// -----------------------------------------------------------------------------
function gfInputParseCSV ( $input, $type ) {
  // parses the input-data

  global $fieldsep;
  global $hasxlabel;
  global $hasylabel;
  global $haslegend;
  global $hasxgrid;
  global $hasygrid;
  global $ishorizontal;
  global $size;
  global $title;
  global $colors;
  global $fill;
  global $isstacked;
  global $is3d;
  global $min;
  global $max;
  global $ysteps;
  
  $lines = explode ("\n",$input); 
  foreach($lines as $line) {
    if ($line != "") {
      $data[] = explode($fieldsep,$line);
    }
  }
  
  $xlabel = "";
  if ($hasxlabel) {
    if ($haslegend) {
      $startrow = 1;
    } else {
      $startrow = 0;
    }
    for ($i = $startrow; $i < count($data); $i++) {
      if ($xlabel != "") $xlabel = $xlabel . "|";
      $xlabel = $xlabel . $data[$i][0];
    }
    $startcol = 1;
  } else {
    $startcol = 0;
  }
  
  $legend = "";
  if ($haslegend) {
    for ($i = $startcol; $i < count($data[0]); $i++) {
      if ($i != $startcol) $legend = $legend . "|";
      $legend = $legend . $data[0][$i];
    }
    $startrow = 1;
  } else {
    $startrow = 0;
  }
  
  for ($i = $startcol; $i < count($data[0]); $i++) {
    for ($j = $startrow; $j < count($data); $j++) {
      if (($min >= $data[$j][$i]) || ($min == "")) $min = $data[$j][$i];
      if ($max <= $data[$j][$i]) $max = $data[$j][$i];
    }
  }
  if ($type == "pie") $min = 0;

  $ylabel = "";
  if ($hasylabel) {
    $step = ($max - $min) / $ysteps;
    for ($i = $min; $i <= $max; $i += $step) {
      if ($ylabel != "") $ylabel = $ylabel . "|";
      $ylabel = $ylabel . $i;
    }
  }
  
  $rslt = "";
  
  for ($i = $startcol; $i < count($data[0]); $i++) {
    if ($i != $startcol) $rslt = $rslt . "|";
    for ($j = $startrow; $j < count($data); $j++) {
      $value = round(($data[$j][$i]-$min) / ($max-$min) * 100,0);
      if ($value > 100) $value = -1;
      if ($j != $startrow) $rslt = $rslt . ",";
      $rslt = $rslt . $value; 
    } 
  }
  
  $rslt = "&chd=t:" . $rslt;
  
  if ($type == "pie") {
    if ($hasxlabel) {
      $rslt = $rslt . "&chl=" . $xlabel;
    }
  } else {    
    if (($hasxlabel) && ($hasylabel)) {
      if ($ishorizontal) 
        $rslt = $rslt . "&chxt=x,y";
      else
        $rslt = $rslt . "&chxt=y,x";
      $rslt = $rslt . "&chxl=0:|" . $ylabel . "|1:|" . $xlabel;
  }
  if ((!$hasxlabel) && ($hasylabel)) {
      if ($ishorizontal) 
        $rslt = $rslt . "&chxt=x";
      else
        $rslt = $rslt . "&chxt=y";
        $rslt = $rslt . "&chxl=0:|" . $ylabel;
  }
  if (($hasxlabel) && (!$hasylabel)) {
      if ($ishorizontal) 
        $rslt = $rslt . "&chxt=y";
      else
        $rslt = $rslt . "&chxt=x";
      $rslt = $rslt . "&chxl=0:|" . $xlabel;
  }
  
  if ($haslegend) {
      $rslt = $rslt . "&chdl=" . $legend;
  }
  
  if (($hasxgrid) && ($hasygrid)) {
      if ($ishorizontal) 
      $rslt = $rslt . "&chg=" . 100/$ysteps . ",". 100/(count($data)-1);
    else
      $rslt = $rslt . "&chg=" . 100/(count($data)-1) . "," . 100/$ysteps;
  }
  if (($hasxgrid) && (!$hasygrid)) {
      if ($ishorizontal) 
      $rslt = $rslt . "&chg=0,". 100/(count($data)-1);
    else
      $rslt = $rslt . "&chg=" . 100/(count($data)-1) . ",0";
  }
  if ((!$hasxgrid) && ($hasygrid)) {
      if ($ishorizontal) 
      $rslt = $rslt . "&chg=" . 100/$ysteps . ",0";   
    else
      $rslt = $rslt . "&chg=0," . 100/$ysteps;
  }
  }

  return $rslt;
}
 
// -----------------------------------------------------------------------------
function gfLinesRender( $input, $args, $parser ) {
  global $gchartWikiDefaults;
  global $gchartLinesDefaults;

  gfChartInit ();
  gfArgsParseCommon ($gchartWikiDefaults);
  gfArgsParseCommon ($gchartLinesDefaults);
  gfArgsParseCommon ($args);

  $retval = "";
  $retval = $retval . gfArgsRenderCommon();
  $retval = $retval . gfArgsRenderLine();
  $retval = $retval . gfInputParseCSV($input,"line");
  $retval = $retval . '">';
  return $retval;
}

function gfBarsRender( $input, $args, $parser ) {
  global $gchartWikiDefaults;
  global $gchartBarsDefaults;

  gfChartInit ();
  gfArgsParseCommon ($gchartWikiDefaults);
  gfArgsParseCommon ($gchartBarsDefaults);
  gfArgsParseCommon ($args);

  $retval = "";
  $retval = $retval . gfArgsRenderCommon();
  $retval = $retval . gfArgsRenderBars();
  $retval = $retval . gfInputParseCSV($input,"bars");
  $retval = $retval . '">';
  return $retval;
}

function gfPieRender( $input, $args, $parser ) {
  global $gchartWikiDefaults;
  global $gchartPieDefaults;

  gfChartInit ();
  gfArgsParseCommon ($gchartWikiDefaults);
  gfArgsParseCommon ($gchartPieDefaults);
  gfArgsParseCommon ($args);

  $retval = "";
  $retval = $retval . gfArgsRenderCommon();
  $retval = $retval . gfArgsRenderPie();
  $retval = $retval . gfInputParseCSV($input,"pie");
  $retval = $retval . '">';
  return $retval;
}
