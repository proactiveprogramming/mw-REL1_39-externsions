<?php
/**
	Simple JS loader
*/
class ecSimpleJSLoader
{
	// true => always generate scripts from scratch
	var $noCache = false;
	// base path for modules and generated scripts
	var $strBaseScriptDir;
	// prefix for modules loaded in createMiniModules
	var $strBaseModulesName = 'edit_calend_';
	// file name for a script generated in createMiniModules
	var $strMiniModulesName = 'edit_calend.modules.mini.js';
	// minification options
	var $isRemoveInlineComments = true;
	var $isRemoveMultiComments = true;

	function __construct($strBaseScriptDir)
	{
		$this->strBaseScriptDir = $strBaseScriptDir;
	}

	/*
		Create minified file from an array of JS modules.
		
		$arrModules should contain names of files without the prefix and an extension
	*/
	function createMiniModules($arrModules)
	{
		$strOutputPath = "{$this->strBaseScriptDir}/{$this->strMiniModulesName}";
		
		// check if we need to change anything
		if (!$this->noCache)
		{
			$isChanged = $this->isChanged($arrModules, $strOutputPath);
		}
		else
		{
			$isChanged = true;
		}
		
		// generate & create file
		if ($isChanged)
		{
			$hFile = fopen ($strOutputPath, 'w');
			foreach ($arrModules as $m)
			{
				$strFileName = $m;
				fwrite ($hFile, "\n// $strFileName, line#0\n");	// file start marker
				fwrite ($hFile, $this->getMiniContents($this->getModulePath($m)));
				fwrite ($hFile, "\n// $strFileName, EOF");		// EOF marker
			}
			fclose ($hFile);
		}
		
		return $this->strMiniModulesName;
	}
	
	/*
		Checks if any of module files were changed after changing the output file
	*/
	function isChanged($arrModules, $strOutputPath)
	{
		if (!file_exists($strOutputPath))
		{
			return true;
		}
		
		$intMaxTime = 0;
		foreach ($arrModules as $m)
		{
			$intTmpTime = filemtime($this->getModulePath($m));
			if ($intTmpTime>$intMaxTime)
			{
				$intMaxTime = $intTmpTime;
			}
		}
		$intFileTime = filemtime($strOutputPath);
		
		return ($intFileTime < $intMaxTime);
	}

	/*
		Get module path
	*/
	function getModulePath($strModuleName)
	{
		return "{$this->strBaseScriptDir}/{$this->strBaseModulesName}$strModuleName.js";
	}

	/*
		Gets minified contents of the given file
	*/
	function getMiniContents($strFilePath)
	{
		// contents
		$strCode = file_get_contents($strFilePath);
		
		// BOM del
		$strCode = preg_replace('#^\xEF\xBB\xBF#', '', $strCode);
		
		// lines (simpli/uni)fication
		$strCode = preg_replace(array("#\r\n#", "#\r#"), "\n", $strCode);
		
		// remove in-line comments without removing any vertical whitespace
		// TODO: A different aproach? Preserve strings and then match comments...
		if ($this->isRemoveInlineComments)
		{
			$strCode = preg_replace("#[ \t]*//[^\"\'\n]*[^\\\\\"\'\n](?=\n)#", '', $strCode);

			// not working: $strCode = preg_replace("#\n//[^'\n]*(['\"])[^'\n]*\1(?=\n)#", "\n", $strCode);
			$strCode = preg_replace("#\n//[^\"\n]*\"[^\"\n]*\"[^\"\n]*(?=\n)#", "\n", $strCode);
			$strCode = preg_replace("#\n//[^'\n]*'[^'\n]*'[^'\n]*(?=\n)#", "\n", $strCode);

			$strCode = preg_replace("#\n//(?=\n)#", "\n", $strCode);
		}
		
		// remove horizontal whitespace from EOL
		$strCode = preg_replace("#[ \t]+\n#", "\n", $strCode);
		
		// remove multi-line comments, add in-line comment in format: "// EOC@line#X".
		if ($this->isRemoveMultiComments)
		{
			$strCode = $this->parseMultiCom($strCode);
		}
		
		return $strCode;
	}

	/*
		Parse multiline comments
	*/
	function parseMultiCom($strCode)
	{
		// prepare for simplified search
		//$strCode = "\n".$strCode."\n";
		
		//
		// find comments
		$arrComments = array();
		$reMulti = "#(?:^|\n)\s*/\*[\s\S]+?\*/\s*(?=\n|$)#";
		if (preg_match_all($reMulti, $strCode, $arrMatches, PREG_OFFSET_CAPTURE))
		{
			foreach ($arrMatches[0] as $m)
			{
				$intInd = count($arrComments);
				$arrComments[$intInd] = array('start'=>$m[1], 'len'=>strlen($m[0]));
				if ($intInd>0)
				{
					$arrComments[$intInd]['previous'] = $arrComments[$intInd-1]['start']+$arrComments[$intInd-1]['len'];
				}
				else
				{
					$arrComments[$intInd]['previous'] = 0;
				}
			}
		}
		
		//
		// replace comments
		$intCorrection = 0;
		$intLines = 0;
		foreach ($arrComments as $c)
		{
			$intLines += 1+preg_match_all("#\n#"
				, substr($strCode
					, $c['previous']-$intCorrection
					, ($c['start']-$c['previous'])+$c['len'])
				, $m);
			$intLenPre = strlen($strCode);
			$strCode = substr_replace($strCode, "\n// EOC@line#{$intLines}", $c['start']-$intCorrection, $c['len']);
			$intLines--;	// correction as line at the end is not matched
			$intCorrection += $intLenPre-strlen($strCode);
		}
		
		return $strCode;
	}
}

?>