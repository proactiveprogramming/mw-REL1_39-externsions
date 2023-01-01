<?php
/**
 * @file POCCodeExtractor.php
 *
 * Subversion
 *	- ID:  $Id$
 *	- URL: $URL$
 *
 * @copyright 2010 Alejandro Darío Simi
 * @license GPL
 * @author Alejandro Darío Simi
 * @date 2010-08-29
 */

class POCCodeExtractor {
	/**
	 * @var PieceOfCode
	 */
	protected	$_pocInstance;

	/**
	 * @var string
	 */
	protected	$_connection;
	/**
	 * @var POCErrorsHolder
	 */
	protected	$_errors;
	/**
	 * @var array
	 */
	protected	$_fileInfo;
	/**
	 * @var string
	 */
	protected	$_filename;
	/**
	 * @var string
	 */
	protected	$_highlightLines;
	/**
	 * @var array
	 */
	protected	$_lines;
	/**
	 * @var bool
	 */
	protected	$_parseInput;
	/**
	 * @var int
	 */
	protected	$_revision;
	/**
	 * @var bool
	 */
	protected	$_showTitle;
	/**
	 * @var string
	 */
	protected	$_separator;
	/**
	 * @var POCStoredCodes
	 */
	protected	$_storeCodes;

	public  function __construct() {
		$this->_errors = POCErrorsHolder::Instance();

		$this->_pocInstance = PieceOfCode::Instance();
		$this->_storeCodes  = POCStoredCodes::Instance();

		$this->clear();
	}

	/*
	 * Public methods.
	 */
	/**
	 * @todo doc
	 * @param $input @todo doc
	 * @param $params @todo doc
	 * @param $parser @todo doc
	 * @return @todo doc
	 */
	public function load($input, &$params, $parser) {
		$out = "";
		/*
		 * Clearing status.
		 */
		$this->clear();

		/*
		 * Loading configuration from tags.
		 */
		$out.= $this->loadParams($params);
		if($this->_errors->ok()) {
			if($this->_parseInput) {
				$input = $parser->recursiveTagParse($input);
			}
			$out.= $this->loadVariables($input);
		}

		/*
		 * Loading file information
		 */
		if($this->_errors->ok()) {
			$this->_fileInfo = $this->_storeCodes->getFile($this->_connection, $this->_filename, $this->_revision);
			if(!$this->_fileInfo) {
				$out.=$this->_errors->setLastError(wfMsg('poc-errmsg-no-fileinfo', $this->_connection, $this->_filename, $this->_revision));
			}
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @return @todo doc
	 */
	public function show() {
		$out = "";

		if($this->_fileInfo) {
			global	$wgPieceOfCodeConfig;
			global	$wgParser;

			$tag = '';
			if(!PieceOfCode::CheckSyntaxHighlightExtension($tag)) {
				$out.= $this->_errors->getLastError();
			}

			$upload_path = $wgPieceOfCodeConfig['uploaddirectory'].DIRECTORY_SEPARATOR.$this->_fileInfo['upload_path'];

			$out.= "<div class=\"PieceOfCode_code\">\n";
			if($this->_showTitle) {
				$auxUrl = Title::makeTitle(NS_SPECIAL,'PieceOfCode')->escapeFullURL("action=show&connection={$this->_connection}&path={$this->_filename}&revision={$this->_revision}");
				$out.="<span class=\"PieceOfCode_title\"><a href=\"{$auxUrl}\"><strong>{$this->_connection}></strong>{$this->_filename}:{$this->_revision}</a></span>";
			}

			$auxCount = count($this->_lines);
			if($auxCount) {
				foreach($this->_lines as $l) {
					$auxCount--;
					$auxOut = "<{$tag} lang=\"{$this->_fileInfo['lang']}\" line=\"GESHI_NORMAL_LINE_NUMBERS\" start=\"{$l[0]}\"".($this->_highlightLines?" highlight=\"{$this->_highlightLines}\"":"").">";
					$file = file($upload_path);
					for($i=$l[0]-1; $i<$l[1]; $i++) {
						if(isset($file[$i])) {
							$auxOut.=$file[$i];
						}
					}
					$auxOut.= "</{$tag}>";
					$out.= $wgParser->recursiveTagParse($auxOut);
					if($this->_separator && $auxCount > 0) {
						$out.=html_entity_decode($this->_separator);
					}
				}
			} else {
				$st = stat($upload_path);
					
				if($st['size'] > $wgPieceOfCodeConfig['maxsize']['showing']) {
					$out.= $this->_errors->setLastError(wfMsg('poc-errmsg-large-showall', $wgPieceOfCodeConfig['maxsize']['showing']));
					$out.= "<pre>";
					$out.= htmlentities(file_get_contents($upload_path));
					$out.= "</pre>";
				} else {
					$lang = $this->_fileInfo['lang'];

					if($st['size'] > $wgPieceOfCodeConfig['maxsize']['highlighting']) {
						$out .= $this->_errors->setLastError(wfMsg('poc-errmsg-large-highlight', $wgPieceOfCodeConfig['maxsize']['highlighting']));
						$lang = "text";
					}
					$auxOut = "<{$tag} lang=\"{$lang}\" line=\"GESHI_NORMAL_LINE_NUMBERS\" start=\"1\">";
					$auxOut.= file_get_contents($upload_path);
					$auxOut.= "</{$tag}>";
					$out.= $wgParser->recursiveTagParse($auxOut);
				}
			}
			$out.= "</div>\n";
		}

		return $out;
	}

	/*
	 * Protected Methods
	 */
	/**
	 * Clears all data concerning the file to be shown.
	 */
	protected function clear() {
		$this->_showTitle = false;

		$this->_filename       = '';
		$this->_revision       = '';
		$this->_connection     = '';
		$this->_lines          = array();
		$this->_highlightLines = '';
		$this->_parseInput     = false;

		$this->_fileInfo = null;
	}
	/**
	 * Return parameters from mediaWiki;
	 *	use Default if parameter not provided;
	 *	use '' or 0 if Default not provided
	 * @param $input @todo doc
	 * @param $name @todo doc
	 * @param $isNumber @todo doc
	 * @return @todo doc
	 */
	protected function getVariable($input, $name, $isNumber=false) {
		if($this->_pocInstance->varDefault($name)) {
			$out = $this->_pocInstance->varDefault($name);
		} else {
			$out = ($isNumber) ? 0 : '';
		}

		if(preg_match("/^\s*$name\s*=\s*(.*)/mi", $input, $matches)) {
			if($isNumber) {
				$out = intval($matches[1]);
			} elseif($matches[1] != null) {
				$out = htmlspecialchars($matches[1]);
			}
		}

		return $out;
	}
	/**
	 * @todo doc
	 * @param $params @todo doc
	 * @return @todo doc
	 */
	protected function loadParams(array &$params) {
		$out = "";

		foreach($params as $k => $v) {
			switch($k) {
				case 'title':
					$this->_showTitle = in_array(strtolower($v), array('true', 'title'));
					break;
				case 'highlight':
					$this->_highlightLines = $v;
					break;
				case 'parseinput':
					$this->_parseInput = in_array(strtolower($v), array('true', 'parseinput'));
					break;
			}
		}

		return $out;
	}
	/**
	 * This method tries to load all the useful information set between tags
	 * &lt;pieceofcode&gt; and &lt;/pieceofcode&gt;.
	 * @param $input Configuration text to be analyzed.
	 * @return @todo doc
	 */
	protected function loadVariables($input) {
		$out = "";

		$this->_filename   = $this->getVariable($input, 'file');
		$this->_revision   = $this->getVariable($input, 'revision', true);
		$this->_connection = $this->getVariable($input, 'connection');
		$this->_separator  = $this->getVariable($input, 'separator');
		$this->_lines      = explode(',', $this->getVariable($input, 'lines'));

		$auxLen = count($this->_lines);
		for($i=0; $i<$auxLen; $i++) {
			$this->_lines[$i] = explode('-', $this->_lines[$i]);
			if(isset($this->_lines[$i][0]) && isset($this->_lines[$i][1])) {
				$this->_lines[$i][0] = trim($this->_lines[$i][0]);
				$this->_lines[$i][1] = trim($this->_lines[$i][1]);

				if($this->_lines[$i][0] > $this->_lines[$i][1]) {
					unset($this->_lines[$i]);
				}
			} else {
				unset($this->_lines[$i]);
			}
		}

		/*
		 * Last lines values check.
		 */
		if(count($this->_lines) < 1) {
			unset($this->_lines);
			$this->_lines = array();
		}

		return $out;
	}

	/*
	 * Public class methods
	 */
}

?>
