<?php
/**
 * converting docx to wiki format with html tags
 * @param $file
 */
//TODO not standard styles eg headers according to language
//TODO get images
 class DocxConvertFile
 {
 	private $rows;
 	private $list = array();
 		
	public function convertFile($file)
	{
		$zip = new ZipArchive();
		$res = $zip->open($file);
		$document = $zip->getFromName('word/document.xml');
		$xml = new SimpleXMLElement($document);
		$namespaces = $xml->getNamespaces(true);
		$w = $xml->children($namespaces['w']);

		$out = array();
		
		$body = $w->body;
		
		for($i = 0; $i< count($body); $i++)
		{
			if ($body[$i]->p)
			{
				foreach($body[$i]->p as $p)
				{
					$Style = $p->pPr->pStyle;
		
					////headers goes here
					if (!$p->pPr->numPr && !empty($this->list)) /// adding lists to the text variable
					{
						echo '<pre>';
						print_r($this->list);die();
							$text .= $this->printList($this->list,$type);
							$list = '';
							$type='';
					}
					
					/// change Header[1-9] according to language for polish it is Nagwek[1-9]
					if(preg_match('/Header[1-9]/', $Style['val'], $matches)) 
					{
						$heading = substr($matches[0],-1);
						foreach($p->r as $r){
							foreach($r->t as $t){
							if(!empty($t))
								$header_text .= $t;
							}
						}
						$text .= '<h' . $heading[0] . '>' . $header_text . '</h' . $heading[0] . '>';
						$header_text = '';
					}
						/// lists goese here
					else if ($p->pPr->numPr) 
					{
						/// get list type
						$type = strval($p->pPr->numPr->numId['val']);

						foreach($p->r as $r){
							foreach($r->t as $t){
							if(!empty($t))
								$row .= strval($t);
							}
						}
						
//						//checking inserting row in correct depth (for now list can have 3 lvl of depths) i know it's silly but for now it works
						if($intensity == 0)
							$list[] = $row;
						else if ($intensity == 1)
							$list[][] = $row;	
						else if ($intensity == 2)
							$list[][][] = $row;	
							
						// clear variable
						$row ='';
					}
					else  /// text without special style goes here
					{
						foreach($p->r as $r) 
						{	
							foreach($r->t as $t) 
							{
								if($r->rPr->b)
								{
									$tag_open .= '<b>';
									$tag_close = '</b>' . $tag_close;
								}
								if($r->rPr->i)
								{
									$tag_open .= '<i>';
									$tag_close = '</i>' . $tag_close;
								}
								if($r->rPr->u)
								{
									$tag_open .= '<u>';
									$tag_close = '</u>' . $tag_close;
								}
									
								$text .= $tag_open . strval($t) . $tag_close;
								$tag_close = '';
								$tag_open = '';
								
							}
						}
					}
					
				if(!empty($text))
				{
					$out[] = array();
					$akapit = count($out) - 1;
					$out[$akapit] = $text;
				}
				unset($text);
				} 
			}
			if($body[$i]->tbl){
				foreach ($body[$i]->tbl as $bl)
				{
					$text = $this->printTable($bl);
						$out[] = array();
						$akapit = count($out) - 1;
						$out[$akapit] = $text;
					unset($text);
				}
			}
		}
		//// imploding table and puting there br
		$text_a = implode("<br/>", $out);
		
		// cleaning unnecessary html tags
		$patternArray = array('/<.ul><ul>/', '/<.h1><.br>/','/<.h2><.br>/','/<.h3><.br>/','/<.h4><.br>/', '/<.h5><.br>/','/<.br><.h1>/','/<.br><.h2>/','/<.br><.h3>/','/<.br><.h4>/', '/<.br><.h5>/', '/<h.><\/h.>/', '/<.br><.br>/','/<\/b><b>/','/<\/u><u>/','/<\/i><i>/','/<br.><br.>/');
		$replaceArray = array('','</h1>','</h2>','</h3>','</h4>','</h5>','<h1>','<h2>','<h3>','<h44>','<h5>', '','</br>');
		
		// cleaning twice should me enough
		$text_b = preg_replace($patternArray,$replaceArray, $text_a);
		$text_c = preg_replace($patternArray,$replaceArray, $text_b);
		
		//print_r($text_c);die();
		return $text_b;
		
	}
	
	private function printList($data,$type = '')
	{
		if ($type == 2)
			$tag = 'ol>';
		else 
			$tag = 'ul>';
		
		if( count($data) > 0)
		{	
			$this->rows .= '<' . $tag;
			
			foreach($data as $d1){
				if(is_array($d1))
					$this->printList($d1,$type);
				else 
					$this->rows .= '<li>' . $d1 . '</li>';
			}
			
			$this->rows .= '</' . $tag;
		}
		$list = $this->rows;
		$this->rows = '';

		return $list;
	}
	
	private function printTable($table)
	{
		$data = '<table>';
		foreach ($table->tr as $tr)
		{
			$data .='<tr>';
			foreach ($tr->tc as $td)
			{
				if ($td->tcPr->gridSpan)
					$data .='<td colspan="' . $td->tcPr->gridSpan['val'] . '">';
				else 
					$data .= '<td>';
				foreach($td->p->r as $r){
					foreach($r->t as $t){
					if(!empty($t))
						$data .= strval($t);
					}
				}
				$data .='</td>';
			}
			$data .='</tr>';
			
		}
		$data .= '</table>';
		$table = '';
		$table = $data;
		$data = '';
		
		return $table;
	}
	
 }
