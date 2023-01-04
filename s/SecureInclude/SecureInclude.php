<?php
/**
 * simple wrapper for the below file holding all the logic
 */
require_once ("secure-include.php");

class SecureInclude
{
  public static function onParserFirstCallInit(Parser $parser)
  {
    ef_include_onParserFirstCallInit( $parser );
  }
}