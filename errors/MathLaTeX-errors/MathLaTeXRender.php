<?php

/**
 * MathLaTeXRender
 *
 * @brief MathLaTeXRender implements the MathLaTexRender class.
 *
 * @file
 * @name MathLaTeX.body
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
class MathLaTeXRender {
/**
 * render
 * 
 * @brief Render the latex statement as an image
 *
 * @function
 * @name render
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @param string equation temp path
 * @param string equation filename
 * @param string latex statement
 * @return string error message upon fafulre
 * @return true on success
 */
	public function render( $equation_temp_path, $equation_filename, $equation ) {
		global $MathTempPath;
		// test for '%' as the first character in $equation
		// If true pass the equation, with no wrapper, to LaTeX.
		// If fail, format the equation and pass it to LaTeX.
		if( ($equation[0] == '%' ) == false ) {
			$equation = self::wrapper( $equation );
		}

		// call LaTeXrender
		// returns true on success or error string on failure
		$render_return = self::LaTeXrender( $equation_temp_path, $equation_filename, $equation );

		// test for error string
		if( is_string( $render_return ) == true ){
			$msg = "<span tyle=\"color:red\">Render::render</span> failed<br />\n" .
			$render_return . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// call DviPNGrender
		// returns true on success or error string on failure
		$render_return = self::DviPNGrender( $equation_temp_path );

		// test for error string
		if( is_string( $render_return ) == true ){
			$msg = "<span style=\"color:red\">Render:DviPNGrender</span> failed<br />\n" .
			$render_return . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// Get here and everything worked.
		return true;
	} // render

/**
 * private functions
 */

/**
 * LaTeXrender
 *
 * @brief Convert the latex statement to a dvi
 *
 * @function
 * @name LaTeXrender
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @param string equation temp path
 * @param string equation filename
 * @param string latex statement
 * @return string error message upon fafulre
 * @return true on success
 */
	 private function LaTeXrender( $equation_temp_path, $equation_filename, $equation ) {
		global $MathTempPath;

		wfDebugLog( 'MathLaTeX', $MathTempPath );
		wfDebugLog( 'MathLaTeX', $equation_temp_path );
		
		// change current dir to $MathTempPath
		if( chdir ( $MathTempPath . '/'. $equation_temp_path ) == false ) {
			$msg = "<span style=\"color:red\">Render:LaTeXrender chdir</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// write out the tex file in $MathTempPath
		$file_handle = fopen( $equation_temp_path . ".tex", "w");

		// test for a valid filehandle
		if( $file_handle == false ) {
			$msg = "<span style=\"color:red\">Render:LaTeXrender fopen</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// write out the equation file
		if( fwrite($file_handle, $equation ) == false) {
			$msg = "<span style=\"color:red\">Render:LaTeXrender fwrite</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// fwrite succeeded, close
		if( fclose( $file_handle ) == false ) {
			$msg = "<span style=\"color:red\">Render:LaTeXrender fclose</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// have the input file.
		// assemble the latex call
		$cmd = 'pdftex.exe ' .
			   '--fmt=latex ' .               // use latex format
			   '--interaction=nonstopmode ' . // don't stop, no point in it
			   $equation_temp_path . '.tex';        // source file

		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// verify if tex was produced.
		if ( file_exists( $equation_temp_path . '.tex' ) == false ) {
			$msg = "<span style=\"color:red\">Render:LaTeXrender tex creation</span> failed<br />\n" .
			"cmd " . $cmd . "<br />\n".
			"retval " . $retval . "<br />\n" .
			"result " . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		} else {
			return true;
		}
	} // LaTeXrender

/**
 * DviPNGrender
 *
 * @brief Convert a dvi file to the $MathImageExt image
 *
 * @function
 * @name DviPNGrender
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @global string $MathImageExt
 * @global string $MathDotsPerInch
 * @param string equation temp path
 * @return string error message upon fafulre
 * @return true on success
 */
	 private function DviPNGrender( $equation_temp_path ) {
		global $MathTempPath;
		global $MathImageExt;
		global $MathDotsPerInch;

		// change current dir to $MathTempPath
		if( chdir ( $MathTempPath . '/'. $equation_temp_path ) == false ) {
			$msg = "<span style=\"color:red\">Render:DviPNGrender chdir</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// assemble the dvipng call
		$cmd = 'dvipng.exe ' .          // dvipng.exe command
			   '-bg Transparent ' .     // set background to transparent
			   '--gamma 1.5 ' .         // color interpolation
			   '-D ' .$MathDotsPerInch . ' ' . // output resolution
			   '-T tight ' .            // reduce image size to just the equation
			   '--strict ' .            //  don't stop, no point in it
			   $equation_temp_path . '.dvi ' . // input file
			   '-o ' .
			   $equation_temp_path . '.' . $MathImageExt;  // output file

		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// verify if png was produced.
		if( file_exists( $equation_temp_path . '.' . $MathImageExt ) == false ) {
			$msg = "<span style=\"color:red\">Render::DviPNGrender png creation</span> failed<br />\n" .
			"cmd " . $cmd . "<br />\n" .
			"retval " . $retval . "<br />\n" .
			"dvipng result " . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		} else {
			return true;
		}
	} // DviPNGrender


/**
 * DviPSrender
 *
 * @brief Converts a dvi image to postscript
 *
 * @function
 * @name DviPSrender
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @global string $MathImageExt
 * @param string equation temp path
 * @return string error message upon fafulre
 * @return true on success
 */
	 private function DviPSrender( $equation_temp_path ) {
		global $MathTempPath;
		global $MathImageExt;

		// change current dir to $MathTempPath
		if( chdir ( $MathTempPath . '/'. $equation_temp_path ) == false ) {
			$msg = "<span style=\"color:red\">Render:DviPSrender chdir</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// assemble the dvips call
		$cmd = 'dvips.exe ' .            // dvips.exe command
			   '-q ' .                   // Run quietly.
			   '-R ' .                   // Run securely
			   '-E ' .                   // Try to create EPSF, crop canvas to equation
			   $equation_temp_path . '.dvi ' . // input file
			   '-f >' .                  // Run as filter
			   $equation_temp_path . '.ps';  // output file

		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// verify if ps was produced.
		if( file_exists( $equation_temp_path . '.ps' ) == false ) {
			$msg = "<span style=\"color:red\">Render:DviPSrender ps creation</span> failed<br />\n" .
			"cmd " . $cmd . "<br />\n" .
			"retval " . $retval . "<br />\n" .
			"dvips result \n" . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		} else {
			return true;
		}
	} // DviPSrender

/**
 * PsPngconvert
 *
 * @brief Convert a postscript file to a png image
 *
 * @function
 * @name PsPngConvert
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @global string $MathImageExt
 * @param string equation temp path
 * @return string error message upon fafulre
 * @return true on success
 */
	 private function PsPngconvert( $equation_temp_path ) {
		global $MathTempPath;
		global $MathImageExt;

		// change current dir to $MathTempPath
		if( chdir ( $MathTempPath . '/'. $equation_temp_path ) == false ) {
			$msg = "<span style=\"color:red\">Render:PsPngconvert chdir</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// assemble the dvips call
		$cmd = 'convert ' .             // dvips.exe command
		       '-background transparent ' .
			   '-quality 100 ' .        // Run quietly.
			   '-density 120 ' .        // Run securely
			   $equation_temp_path . '.ps ' . // input file
			   $equation_temp_path . '.png';  // output file

		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// verify if png was produced.
		if( file_exists( $equation_temp_path . '.' . $MathImageExt ) == false ) {
			$msg = "<span style=\"color:red\">Render:PsPngconvert png creation</span> failed<br />\n" .
			"cmd " . $cmd . "<br />\n" .
			"retval " . $retval . "<br />\n" .
			"convert result " . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		} else {
			return true;
		}
	} // DviPSrender

/**
 * wrapper
 *
 * @brief Wrap the latex statement in commands for texlive
 *
 * @function
 * @name onParserFirstCallInit
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string latex statement
 * @return string latex statement wrapped
 */
	 public function wrapper( $plain_text ) {
		return  "\\nonstopmode" .
				"\n" .
			    "\\documentclass[12pt]{article}" .
			    "\n" .
			    "\\usepackage{mathtools}" . // texlive-collection-latexrecommended
			    "\n" .
			    "\\usepackage{lmodern}" .
			    "\n" .
			    "\usepackage{amsmath}" .  // texlive-collection-latex
			    "\n" .
			    "\\usepackage{amsfonts}" .
			    "\n" .
			    "\\usepackage{amssymb}" .
			    "\n" .
			    "\\usepackage{pst-plot}" . // texlive-collection-pstricks
			    "\n" .
			    "\\usepackage{color}" .
			    "\n" .
				"\\pagestyle{empty}" .
				"\n".
				"\\begin{document}" .
				"\n" .
				"$$" .
				"\n" .
				$plain_text .
				"\n" .
				"$$" .
				"\n" .
				"\\end{document}";
	} // wrapper
} // MathLaTeXRender
?>