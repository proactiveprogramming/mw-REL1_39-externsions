<?php
/**
 * 
 * @author Lain
 *
 */
include_once('DocxConvertFile.php');
include_once('DocxForm.php');

class DocxConverter extends SpecialPage {
	
	var $id;
	var $path; 
	
	function __construct() {
		parent::__construct('DocxConverter');
		wfLoadExtensionMessages('DocxConverter');
	}
	
    function execute() {
		global $wgRequest, $wgOut, $wgTitle;
		
		$this->setHeaders();
		$wgOut->enableClientCache(false);
		
		$action = $wgRequest->getVal('action');

		switch($action)
		{
			case 'add':
					$wgOut->addWikiText('=== ' . wfMsg('addDocumentLabel') . ' ===');
					$this->showUploadForm();
					
					 if($wgRequest->wasPosted()){
					 	$wgOut->addHTML($this->addFile($_FILES['file']));
					 }
					$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       				$action = $titleObj->escapeLocalURL();
       			
       				$wgOut->addHTML('<a href ="' . $action . '" style="margin-top:15px;color:#002BB8;font-size:14px;">' . wfMsg( 'backButton') . '</a>');
				break;
			case 'edit':
					$this->id = $wgRequest->getVal('id');
					$document = $this->getDocument();
					
					if(!$document)
						return $wgOut->showErrorPage('error','pageNotFound');
						
					$this->createForm($document);
					$wgOut->addHTML( $form );
				break;
				
			case 'multi':
				
					if($wgRequest->wasPosted()){
						if($wgRequest->getVal('step') == 1)
						{
							$wgOut->addHTML($this->multiSave($wgRequest->getValues()));
						}
						else{
							$this->addZipFile($_FILES['file']);
							$this->multiUploadForm(1);
						}
					}
					else{
						$wgOut->addWikiText('=== ' . wfMsg( 'addDocumentZipLabel') . ' ===');
						$this->multiUploadForm();
					}
				break;
			default:
				$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       			$action = $titleObj->escapeLocalURL();
       			
       			$wgOut->addHTML('<a href ="' . $action . '?action=add" style="color:#002BB8;font-size:14px;font-weight:bold;">' . wfMsg( 'addFileLink') . '</a>');
       			$wgOut->addHTML('<a href ="' . $action . '?action=multi" style="margin-left:30px;color:#002BB8;font-size:14px;font-weight:bold;">' . wfMsg( 'addFilesLink') . '</a>');
				$wgOut->addHtml($this->getList());
		}
}
		/**
		 * create and populate form
		 * @param $text
		 */
		function createForm($data)
		{
			global $wgTitle, $wgOut;
			$wiki = new MediaWiki();
			
			$tempTitle = Title::newFromText($data[0]);
			
			$art = $wiki->articleFromTitle($tempTitle);
			
			$edit = new DocxForm($art, $this->id);
						
			$edit->setPreloadedText($data[1]);
			
			$edit->initialiseForm();
			$edit->edit();
		}
		
		/**
		 * show upload form
		 */
		function showUploadForm()
		{
			global $wgOut;
			$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       		$action = $titleObj->escapeLocalURL();
        
        	$html = '<form method="post"
          				enctype="multipart/form-data" action="'.$action.'?action=add">
        				<label for="file">' . wfMsg( 'fileNameLabel') . '</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" id="form" name="submit" value="' . wfMsg( 'sendButton') . '" />
					</form>';
			$wgOut->addHTML($html);
		}
		
		
		/**
		 * Upload file and add row to db
		 * @param $file
		 */
		function addFile($file)
		{
			global $wgOut;
			$preIP = dirname( __FILE__ );
			
			$archives = "$preIP/archives/";

			if(substr($file['name'],-4) != 'docx')
			 	return $wgOut->showErrorPage('error','extensionNotSupported');
			 
			if($file["error"] > 0)
				return $wgOut->showErrorPage('error','uploadErrors');
			else
			{
				$name = substr($file["name"], 0,-5);
				$file['name'] = $name . '_' .date("dmY_His") . '.docx'; 
				
				move_uploaded_file($file['tmp_name'],$archives . '/' . $file['name']);
				$message = 'File uploaded succesfully';						 
				
				$dbw = wfGetDB( DB_MASTER );
				
				$converter = new DocxConvertFile();
				$text = $converter->convertFile($archives . '/' . $file['name']);
				
				$dbw->insert('converter',
						array(	'title' 	=> $file["name"],
							  	'text'		=> $text,
								'date_add' 	=> date('Y-m-d H:i:s'),
								'filename'	=> $file["name"],
								'status'	=> 1								
						),
						'DatabaseBase::insert',
						array()
				);
			}
			
		}
		/**
		 * Get list of uploaded files
		 */
		function getList()
		{
			//preparing edit link
			$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       		$edit = $titleObj->escapeLocalURL();
       		
			/// geting data from db
			$db = wfGetDB( DB_SLAVE );
			$res = $db->select( array('converter'),
					array('id','title','date_add','date_edit','status', 'url'),
					array(), /// where
					__METHOD__, 
					array(),
					array()
				);
				
			$rows = '';
		
			if ( $db->numRows( $res ) ) {
				$i = 1;
				foreach( $res as $row ) {

					if ($row->url)
						$r ='<a href="' . $row->url . '">' . $row->title . '</a>';
					else
						$r = $row->title;
					if ($row->date_edit == NULL)
						$row->date_edit = '----';
					$rows .= '<tr>
								<td>' . $i++ . '.</td>
								<td>' . $r . '</td>
								<td style="text-align:center">' . $row->date_add . '</td>
								<td style="text-align:center">' . $row->date_edit . '</td>
								<td style="text-align:center">' . $this->checkStatus($row->status) . '</td>
								<td style="text-align:center"><a href="' . $edit . '?action=edit&id=' . $row->id .'">' . wfMsg( 'editButton') . '</a></td>
							</tr>';
				}
			}
			
			$db->freeResult( $res );
			
			$table = '<table style="margin-top:15px">
						<tr>
							<th style=\"width:75px\">' . wfMsg('lpHeader') . '</th>
							<th style="width:300px;text-align:left">' . wfMsg('titleHeader') . '</th>
							<th style="width:150px">' . wfMsg('addDateHeader') . '</th>
							<th style="width:150px">' . wfMsg('lastEditHeader') . '</th>
							<th style="width:200px">' . wfMsg('statusHeader') . '</th>
							<th style="width:100px">' . wfMsg('actionHeader') . '</th>
						</tr>
						' . $rows .'
					 </table>';
			
			return $table;
		}
		
		/*
		 * Get document from id
		 */
		public function getDocument()
		{
			$db = wfGetDB( DB_SLAVE );
			$res = $db->select( array('converter'),
					array('title', 'text'),
					array('id' => $this->id), /// where
					__METHOD__, 
					array(),
					array()
				);
			if ( $db->numRows( $res ) ) {	
				foreach( $res as $row ) {
					$data = array($row->title, $row->text);
				}
			}
			else
							
			$db->freeResult( $res );

			return $data;
		}

		/**
		 * Create forms
		 * default upload form
		 * multi title form
		 * @param $type
		 */
		public function multiUploadForm($type = 0)
		{
			global $wgOut;
			
			$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
	       	$action = $titleObj->escapeLocalURL();
	       		
			if ($type == 1)
			{
				$dir = opendir($this->path);
				$dr = readdir($dir);
				$i = 0;
				$wgOut->addWikiText(wfMsg( 'multiTitleFormText'));
				$wgOut->addHTML('<form method="post" enctype="multipart/form-data" action="'.$action.'?action=multi&step=1">');
				
				while (($file = readdir($dir)) !== false) {
            		if (filetype($this->path .'/' . $file))
            		{
            			if(substr($file,-4) == 'docx')
            			{
            				$wgOut->addHTML( Xml::inputLabel(wfMsg( 'siteNameLabel') . $file .':','title_' . $i++,'mw-input', 150, $file, array('style' => 'margin:20px;display:block')) );
            				
            			}
            		}					
				}
				$wgOut->addHTML(Xml::hidden('path',$this->path));
				$wgOut->addHtml(Xml::checkLabel(wfMsg( 'overwriteCheckbox'),'wpOver', 'wpOver', false));
				$wgOut->addHtml('<br/>');
				$wgOut->addHtml(Xml::checkLabel(wfMsg( 'notPublishCheckbox'),'wpNotPublish', 'wpNotPublish',false));
				$wgOut->addHtml('<br/>');
				$wgOut->addHTML('<input type="submit" id="form" name="submit" value="' . wfMsg( 'sendButton') . '" />
									</form>');
				
			}
			else
			{
	        	$html = '<form method="post"
          				enctype="multipart/form-data" action="'.$action.'?action=multi">
        				<label for="file">' . wfMsg( 'zipNameLabel') . '</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" id="form" name="submit" value="' . wfMsg( 'sendButton') . '" />
					</form>';
	        	$wgOut->addHTML($html);
	        	
	        	$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       			$action = $titleObj->escapeLocalURL();
       			
       			$wgOut->addHTML('<a href ="' . $action . '" style="margin-top:15px;color:#002BB8;font-size:14px;">' . wfMsg( 'backButton') . '</a>');
			}
			
			
		}
		
		/**
		 * Save and extrac zip file
		 * @param unknown_type $file
		 */
		public function addZipFile($file)
		{
			global $wgOut;
			/// check file errors
			if($file["error"] > 0)
				return $wgOut->showErrorPage('error','uploadErrors');
			else
			{
				$preIP = dirname( __FILE__ );
			
				/// generating random temp directory
				$temp_hash =  'temp_' . substr(sha1(md5($type . md5(microtime()) . microtime())),1,10);
				$this->path = "$preIP/archives/$temp_hash" ;
					
				if (!mkdir($this->path)) 
    				return $wgOut->showErrorPage('error','directory_error');
    				
    			move_uploaded_file($file['tmp_name'],$this->path . '/' . $file['name']);
    			
    			/// extgracting zip archive
				$zip = new ZipArchive();
				if ($zip->open($this->path . '/' . $file['name']) === TRUE) {
				    $zip->extractTo($this->path);
				    $zip->close();
				    return true;
				} 
				else {
				    return $wgOut->showErrorPage('error','directory_error');
				}

			}
		}
		/**
		 * Save multiple recordes
		 * @param array $data
		 */
		public function multiSave($data)
		{
			global $wgOut;
			
			$preIP = dirname( __FILE__ );
			$path = "$preIP/archives/" ;
			$dir = opendir($data['path']);
			$i = 0;
			$wiki = new MediaWiki();
			
			while (($file = readdir($dir)) !== false) {
            	if(substr($file,-4) == 'docx')
            	{
            		///change filename to name_date_time.docx
					$name = substr($file, 0,-5);
					$name .= '_' .date("dmY_His") . '.docx'; 
					
					//save file
					rename($data['path'] . '/' . $file ,$path . $name);
					
					$title = 'title_' . $i++;
					$Title = Title::newFromText($data[$title]);
					
					/// convert text
					$converter = new DocxConvertFile();
					$text = $converter->convertFile($path . $name);
					
					if($data['wpNotPublish'] == 0)
					{
						//creating article object
						$art = $wiki->articleFromTitle($Title);
						
						$edit = new DocxForm($art, $this->id);
						$edit->textbox1 = $text;
						$edit->allowBlankSummary = true;
						$resultDetails = false;
						
						// if this is set script will overwrite
						if($data['wpOver'] == 1)
							$edit->edittime = $edit->mArticle->getTimestamp();
						
						// getting result code (error, success, confilct etc)
						$result = $edit->internalAttemptSave($resultDetails);	
						
						// make url
						$titleObj = Title::makeTitle( NS_MAIN, $data[$title] );
	       				$action = $titleObj->escapeLocalURL();
					}
					$status = (isset($result))? $result : 101;

					$dbw = wfGetDB( DB_MASTER );
					$dbw->insert('converter',
							array(	'title' 	=> $data[$title],
								  	'text'		=> $text,
									'date_add' 	=> date('Y-m-d H:i:s'),
									'filename'	=> $name,
									'status'	=> $status,		
									'url'		=> $action						
							),
							'DatabaseBase::insert',
							array()
					);
            	}
			}
			
			$this->recursiveDelete($data['path']);
			
			
			$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       		$action = $titleObj->escapeLocalURL();
			return $wgOut->redirect($action);
			
		}
	/**
	 * Getting error text form error number
	 * @param int $error
	 */
	public function checkStatus($error)
	{
		switch($error)
		{
			case 101: return wfMsg('NOT_PUBLISHED') ; break;
			case 200: return wfMsg('AS_SUCCESS_UPDATE') ; break;
			case 201: return wfMsg('AS_SUCCESS_NEW_ARTICLE') ; break;
			case 210: return wfMsg('AS_HOOK_ERROR') ; break;
			case 211: return wfMsg('AS_FILTERING') ; break;
			case 212: return wfMsg('AS_HOOK_ERROR_EXPECTED') ; break;
			case 215: return wfMsg('AS_BLOCKED_PAGE_FOR_USER') ; break;
			case 216: return wfMsg('AS_CONTENT_TOO_BIG') ; break;
			case 217: return wfMsg('AS_USER_CANNOT_EDIT') ; break;
			case 218: return wfMsg('AS_READ_ONLY_PAGE_ANON') ; break;
			case 219: return wfMsg('AS_READ_ONLY_PAGE_LOGGED') ; break;
			case 220: return wfMsg('AS_READ_ONLY_PAGE') ; break;
			case 221: return wfMsg('AS_RATE_LIMITED ') ; break;
			case 222: return wfMsg('AS_ARTICLE_WAS_DELETED') ; break;
			case 223: return wfMsg('AS_NO_CREATE_PERMISSION') ; break;
			case 224: return wfMsg('AS_BLANK_ARTICLE') ; break;
			case 225: return wfMsg('AS_CONFLICT_DETECTED') ; break;
			case 226: return wfMsg('AS_SUMMARY_NEEDED') ; break;
			case 228: return wfMsg('AS_TEXTBOX_EMPTY ') ; break;
			case 229: return wfMsg('AS_MAX_ARTICLE_SIZE_EXCEEDED') ; break;
			case 230: return wfMsg('AS_OK') ; break;
			case 231: return wfMsg('AS_END') ; break;
			case 232: return wfMsg('AS_SPAM_ERROR') ; break;
			case 233: return wfMsg('AS_IMAGE_REDIRECT_ANON') ; break;
			case 234: return wfMsg('AS_IMAGE_REDIRECT_LOGGED') ; break;
		}
	}
	/**
     * Delete a file or recursively delete a directory
     * @author Eddy Vlad
     * @param string $str Path to file or directory
	**/	
	function recursiveDelete($str){
        if(is_file($str)){
            return @unlink($str);
        }
        elseif(is_dir($str)){
            $scan = glob(rtrim($str,'/').'/*');
            foreach($scan as $index=>$path){
                $this->recursiveDelete($path);
            }
            return @rmdir($str);
        }
    }
}
?>