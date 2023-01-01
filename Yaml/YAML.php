<?php
# Software License Agreement (BSD License)
#
# Copyright (c) 2012, I Heart Engineering
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
#
# * Redistributions of source code must retain the above copyright
# notice, this list of conditions and the following disclaimer.
# * Redistributions in binary form must reproduce the above
# copyright notice, this list of conditions and the following
# disclaimer in the documentation and/or other materials provided
# with the distribution.
# * Neither the name of I Heart Engineering nor the names of its
# contributors may be used to endorse or promote products derived
# from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
# COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
#

# Example usage
# ----------------
# {{#yaml:db|0000}}
# {{#yaml:db|0000|Unknown}}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point'
 );
}

$wgExtensionCredits['parserhook'][] = array(
   'path' => __FILE__,
   'name' => 'YAML Database',
   'description' => 'YAML database extension allows for key/value storage and retrieval',
   'descriptionmsg' => 'yaml-desc',
   'version' => 0.1, 
   'author' => 'I Heart Engineering <code@iheartengineering.com>',
   'url' => 'http://www.iheartengineering.com',
);
 
$wgHooks['ParserFirstCallInit'][] = 'YAMLInitFunction';
$wgExtensionMessagesFiles['YAMLMagic'] = dirname( __FILE__ ) . '/YAML.i18n.magic.php';

function YAMLCheckInput($input){
  return preg_match('/^[\<\>\/\#\(\)\.\ \"\”\\\'\-0-9a-zA-Z]*$/iU', $input) ? TRUE : FALSE;
}

function YAMLCheckDatabaseName ($input){
  return preg_match('/^[\_\-0-9a-zA-Z]*$/iU', $input) ? TRUE : FALSE;
}

function YAMLInitFunction( &$parser ) {
   $parser->setFunctionHook( 'yaml', 'YAMLFunction' );
   return true;
}
 
function YAMLFunction( $parser, $data = '', $k = '', $v = '' ) {
  if (!YAMLCheckInput($k)) {
    $output = " YAML: Invalid Key";
    return $output;
  }
  if (!YAMLCheckInput($v)) {
    $output = " YAML: Invalid value ($v)";
    return $output;
  }
  if (!YAMLCheckDatabaseName($data)) {
    $output = " YAML: Invalid database name";
    return $output;
  }
  $database = dirname( __FILE__ ) . "/data/" . $data . ".yaml";
  if (!file_exists($database)) {
    $output = " YAML: Database does not exist ($database)";
    return $output;
  }

  # --------------------------------------------------------------------
  #$output = " Database=$data.yaml\n Key = $k\n Value = $v";
  if (isset($v) and $v != '') {
    # WRITE

    $yaml = file_get_contents($database);
    $db = yaml_parse($yaml);
    $db[$k] = $v;
    $yaml = yaml_emit($db);

    $fp = fopen($database, "r+");

    if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock
      ftruncate($fp, 0);      // truncate file
      fwrite($fp, $yaml);
      fflush($fp);            // flush output before releasing the lock
      flock($fp, LOCK_UN);    // release the lock
      $output = $v;
    } else {
      $output = " YAML: Can not lock database";
    }

    fclose($fp);
    return $output;

  } else {
    # READ
    $yaml = file_get_contents($database);
    $db = yaml_parse($yaml);
    if (isset($db[$k])) {
      $output = $db[$k];
    } else {
      $output = "";
    }
  }
  return $output;
}

?>
