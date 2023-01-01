<?php

/**
 * MathLaTeXRepository
 *
 * @brief Add or find an equation image in the wiki repository.
 *
 * @file
 * @name MathLaTeXRepository
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
class MathLaTeXRepository {
/**
 * add
 *
 * @brief Add an equation image to the repository
 *
 * @function
 * @name onParserFirstCallInit
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @global string $PHPpath
 * @global string $IP
 * @global bool $MathDebug
 * @param string equation temp path
 * @param string equation filename
 * @param string equation 
 * @return string error message upon fafulre
 * @return true on success
 */
	public function add( $equation_temp_path, $equation_filename, $equation ) {
		global $MathTempPath;
		global $PHPpath;
		global $IP;
		global $MathDebug;

		// change current dir to $MathTempPath
		if( chdir ( $IP ) == false ) {
			$msg = "<span tyle=\"color:red\">Repository:add chdir</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}
		// summary holds 200 characters and no \n
		$equation = preg_replace("/[\n]/", " ", $equation);

		// importImages.php will normalize the filename
		$cmd = $PHPpath .
	    ' ' .
		'maintenance/importImages.php ' .
		'--comment="' . $equation .
		'" --user="MW_MATH"' . ' ' . 
		$MathTempPath . '/' . $equation_temp_path;

		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// last line has the error message
		// trim whitespace and carriage returns
		$contents = trim ( $contents, WHITESPACE_REGEX );

		$decode_array = explode( "\n" , $contents );
		$last_line = end( $decode_array );

		// if $last_line does not contain 'Added 1' a failure happened.
		// return the last line importImages produced and do not check
		// for file existence with inRepository
		if ( ( $last_line == "Added: 1" ) == false) {
			$msg = "<span tyle=\"color:red\">Repository:add importImages</span> failed<br />\n" .
			" <nowiki>\nimportImages returned\n" .
			"cmd " . $cmd . "<br />\n" .
			"retval " . $retval . "<br />\n" .
			"result " . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
		    return $msg;
		}

		return true;
	} // add

/**
 * inRepository
 *
 * @brief Add an equation image to the repository
 *
 * @function
 * @name inRepository
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string equation_filename
 * @return on success true on failure false
 */
	public function inRepository( $equation_filename ) {
		// create a sanatized filename and check
		// file namespace for existance
		$ret = Title::newFromText( $equation_filename , NS_FILE );

		// check for file existance
		if( $ret && $ret->exists() ) {
			return true;
		}
		return false;
	} // inRepository

} // MathLaTeXRepository
?>