<?php
/**
 * @file config.php
 *
 * Subversion
 *	- ID:  $Id$
 *	- URL: $URL$
 *
 * @copyright 2010 Alejandro Darío Simi
 * @license GPL
 * @author Alejandro Darío Simi
 * @date 2010-08-28
 */

/**
 * Arrays.
 */
if(!isset($wgPieceOfCodeSVNConnections)) {
	$wgPieceOfCodeSVNConnections = array();
}

/**
 * Configuration.
 * @{
 */
if(!isset($wgPieceOfCodeConfig) || !is_array($wgPieceOfCodeConfig)) {
	$wgPieceOfCodeConfig = array();
}
/**
 * Allows to enable/disable internal CSS-file inclution.
 * @var boolean
 */
$wgPieceOfCodeConfig['autocss']	= true;
/**
 * Enables statistics survey.
 *	@{
 */
$wgPieceOfCodeConfig['stats']			= true;
$wgPieceOfCodeConfig['db-stats-limited']	= false;	//! active stats limitaions.
$wgPieceOfCodeConfig['db-stats-per-try']	= 2;		//! 2 updates for each try.
$wgPieceOfCodeConfig['db-stats-timelimit']	= 2592000;	//! 30 days.
/** 	@} */
/**
 * Enables history.
 *	@{
 */
$wgPieceOfCodeConfig['history']			= true;
/** 	@} */
/**
 * Subversion.
 * 	@{
 */
/**
 * Specifies SVN command full-path.
 * @var string
 */
$wgPieceOfCodeConfig['svn-binary']	= '/usr/bin/svn';
/**	@} */
/**
 * Uploading codes.
 * 	@{
 */
$wgPieceOfCodeConfig['uploaddirectory']	= $wgUploadDirectory.DIRECTORY_SEPARATOR.'PieceOfCode';
$wgPieceOfCodeConfig['enableuploads']	= $wgEnableUploads;
/** 	@} */
/**
 * Database.
 * 	@{
 */
$wgPieceOfCodeConfig['db-tablename']		= 'poc_codes';
$wgPieceOfCodeConfig['db-tablename-texts']	= 'poc_codes_pages';
$wgPieceOfCodeConfig['db-tablename-ccounts']	= 'poc_codes_counts';
$wgPieceOfCodeConfig['db-tablename-flags']	= 'poc_flags';
$wgPieceOfCodeConfig['db-tablename-history']	= 'poc_codes_history';
/** 	@} */
/**
 * Font-code types.
 * 	@{
 */
$wgPieceOfCodeConfig['fontcodes']		= array();
$wgPieceOfCodeConfig['fontcodes']['bash']	= array('sh');
$wgPieceOfCodeConfig['fontcodes']['make']	= array('mk', 'mak');
$wgPieceOfCodeConfig['fontcodes']['cpp']	= array('cpp', 'h', 'hpp', 'c');
$wgPieceOfCodeConfig['fontcodes']['php']	= array('php', 'inc', 'php3');
$wgPieceOfCodeConfig['fontcodes']['text']	= array('txt', 'log');
$wgPieceOfCodeConfig['fontcodes']['sql']	= array('sql');
$wgPieceOfCodeConfig['fontcodes']['xml']	= array('xml', 'xsl', 'xslt', 'mxml');
$wgPieceOfCodeConfig['fontcodes']['java']	= array('java');
$wgPieceOfCodeConfig['fontcodes']['dos']	= array('bat');
$wgPieceOfCodeConfig['fontcodes']['asm']	= array('asm');
$wgPieceOfCodeConfig['fontcodes']['html4strict']= array('html', 'htm');
$wgPieceOfCodeConfig['fontcodes']['python']	= array('py');
$wgPieceOfCodeConfig['fontcodes']['css']	= array('css');
$wgPieceOfCodeConfig['fontcodes']['javascript']	= array('js');
$wgPieceOfCodeConfig['fontcodes-forbidden']	= array('exe', 'so', 'zip', 'rar', '7z',
							'gz', 'jar', 'class', 'a', 'o',
							'tar', 'bz2', 'jpg', 'jpeg', 'png',
							'gif', 'xcf', 'svg');
$wgPieceOfCodeConfig['fontcodes-allowempty']	= false;
/** 	@} */
/**
 * These variables allows to enable/disable information on
 * [[Special:PieceOfCode]].
 * 	@{
 */
$wgPieceOfCodeConfig['show']			= array();
$wgPieceOfCodeConfig['show']['installdir']	= true;
$wgPieceOfCodeConfig['show']['tablenames']	= true;
$wgPieceOfCodeConfig['show']['binarypaths']	= true;
$wgPieceOfCodeConfig['show']['updaloaddirs']	= true;
$wgPieceOfCodeConfig['show']['svnusernames']	= true;
$wgPieceOfCodeConfig['show']['svnpasswords']	= false;
$wgPieceOfCodeConfig['show']['stored-limit']	= 20;
$wgPieceOfCodeConfig['show']['history-limit']	= 50;
$wgPieceOfCodeConfig['show']['authorslogo']	= true;
/** 	@} */
/**
 * Others.
 * 	@{
 */
$wgPieceOfCodeConfig['maxsize']			= array();
$wgPieceOfCodeConfig['maxsize']['highlighting']	= 30720;	//! 30KB
$wgPieceOfCodeConfig['maxsize']['showing']	= 51200;	//! 50KB
/** 	@} */
/** @} */
?>
