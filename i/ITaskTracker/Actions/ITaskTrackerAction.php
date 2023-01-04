<?php
/**
 * ITaskTrackerAction abstract class. 
 */
abstract class ITaskTrackerAction extends ArrayObject
{
	/**
	 * Action name.
	 * @var string
	 */
	protected $_action = null;	
	protected $_checker = array( "Elly","Claire", "Ls", "Manas", "Xavier", "Rams", "Bcadminwiki","Alit","Support","Lionel");	
	protected $_numMaxfileupload=5;
	protected $_filelocation;
	protected $_downloadfileurl;
	protected $_salt = "IbugMisKronos201";
	/**
	 * Instance of IssueTrackerConfig.
	 * @var IssueTrackerConfig
	 */
	protected $_config = null;
	
	/**
	 * Access control list
	 * @var array
	 */
	protected $_acl = array();
	
	/**
	 * Page output.
	 * @var array
	 */
	protected $_pageOutput = null;
	
	/**
	 * Arguments.
	 * @var array
	 */
	protected $_args = array();
	
	/**
	 * Instances of IssueTrackerModel
	 * @var IssueTrackerModel
	 */
	protected $_model = array();
	
	/**
	 * Namespace: DBKey and Text.
	 * @var array
	 */
	protected $_namespace = array();
	
	/**
	 * Parser hook or special page.
	 * @var bool
	 */
	protected $_isParserHook = false;
	
	/**
	 * Class constructor.
	 *
	 * @return void
	 */
        
	public function __construct()
	{
                global $wgCurrentDir, $wgCurrentHome;
		parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
                
                $this->_filelocation = $wgCurrentDir."/ITaskTrackerUploads/";
                $this->_downloadfileurl = "/".$wgCurrentHome."/";
	}
	
	/**
	 * Returns true if the self::executeHook() method was called.
	 *
	 * @return bool
	 */
	public function isIbugParserHook()
	{
		return $this->_isParserHook;
	}
	
	/**
	 * Sets the action name.
	 * 
	 * @param string $action
	 * @return void
	 */
	public function setIbugAction($action)
	{
		$this->_action = strtolower($action);
	}
	
	/**
	 * Returns the action name.
	 * 
	 * @return string 
	 */
	public function getIbugAction()
	{
		return $this->_action;
	}
	
	/**
	 * Sets the self::_isParserHook variable.
	 * 
	 * @param bool $isParserHook
	 * @return void
	 */
	public function setIbugParserHook($isParserHook)
	{
		$this->_isParserHook = $isParserHook;
	}
	
	/**
	 * Sets the Config object.
	 * 
	 * @param IssueTrackerConfig
	 * @return void
	 */
	public function setIbugConfig(ITaskTrackerConfig $config)
	{		
		$this->_config = $config;
	}
	
	/**
	 * Check whether the user's group has permission to perform this action.
	 *
	 * @param string $action
	 */
	public function hasIbugPermission($action)
	{
		global $wgUser;
		
		if (isset($this->_acl[$action])) {
			return $this->_acl[$action];
		}
		
		$perms = $this->_config->getIbugPermissions();
		if ($perms[$action]['group'] == '*') {
			$this->_acl[$action] = true;
			return true;
		} else {
			$userGroups = $wgUser->getGroups();
			foreach ($userGroups as $group) {
				if ($group == strtolower($perms[$action]['group'])) {
					$this->_acl[$action] = true;
					return true;
				}
			}
		}
		
		$this->_acl[$action] = false;
		return false;
	}    
	
	public function isIbugApprover($strChecker){
                return (in_array($strChecker,$this->_checker));
        }
	 public function isIbugArchiver($strArchiver){
                $objModel = new ITaskTrackerModelDefault();              
                $arrUsers = $objModel->getDatabaseUsers("itaskarchiver");
                unset($objModel);                
                foreach ($arrUsers as $user):
                    $this->_archiver[]=$user['user_name'];
                endforeach;
                return (in_array($strArchiver,$this->_archiver));                
        }
	public function isIbugUrgent($strUrgent){
                $objModel = new ITaskTrackerModelDefault();              
                $arrUsers = $objModel->getDatabaseUsers("itaskurgent");
                unset($objModel);                
                foreach ($arrUsers as $user):
                    $this->_urgent[]=$user['user_name'];
                endforeach;               
                return (in_array($strUrgent,$this->_urgent));                   
        }
	public function getnumMaxfileupload(){
		return ($this->_numMaxfileupload);
	}
	public function getfilelocation(){
		return ($this->_filelocation);
	}
	public function getdownloadfileurl(){
                return ($this->_downloadfileurl);
        }
	/**
	 * Sets the arguments.
	 *
	 * @param array $args
	 * @return void
	 */
	public function setIbugArguments($args) 
	{
		$this->_args = $args;
	}

	/**
	 * Factory method responsible of injecting model objects.
	 *
	 * @param string $namespace
	 * @param IssueTrackerModel $obj
	 */
	public function setIbugModel(ITaskTrackerModel $obj, $key = 'default')
	{
		if (! array_key_exists($key, $this->_model)) {
			$this->_model[$key] = $obj;
		}
	}

	/**
	 * Returns a model based on a given key name.
	 *
	 * @return IssueTrackerModel
	 * @throws Exception
	 */
	public function getIbugModel($key)
	{
		if (! array_key_exists($key, $this->_model)) {
			throw new Exception('Model object undefined: ' . $key);
		}
		return $this->_model[$key];
	}
	
	/**
	 * Sets the page namespace.
	 *
	 * @param array $namespace
	 */
	public function setIbugNamespace($namespace) 
	{
		$this->_namespace = $namespace;
	}
	
	/**
	 * Returns the page namespace.
	 *
	 * @return array self::$_namespace
	 */
	public function getIbugNamespace($key = null) 
	{		
		if (null !== $key && array_key_exists($key, $this->_namespace)) {
			return $this->_namespace[$key];
		} else {
			return $this->_namespace;	
		}
	}
	
	/**
	 * Sets the special page response.
	 *
	 * @param string $output
	 * @return void
	 */
	public function setIbugOutput($output)
	{           
		$this->_pageOutput = $output;
	}
	
	/**
	 * Returns the special page response.
	 *
	 * @return void
	 */
	public function getIbugOutput()
	{             
		return $this->_pageOutput;
	}
	
	/**
	 * Returns true if the user is logged in.
	 *
	 * @return bool Returns true if the user is logged in, or false otherwise.
	 */
	public function isIbugLoggedIn()
	{
		global $wgUser;
		return $wgUser->isLoggedIn();
	}
	
	/**
	 * Renders the template.
	 *
	 * @return string 
	 */
	public function Ibugrender($filename = null) 
	{            
            
		if (! isset($this->action)) {
			throw new Exception('Action undefined: $this->action');
		}
		
		if ($filename === null) {
			$filename = $this->action;
		}
		
		ob_start();                               
		$fileAct = dirname(__FILE__) . '/../Views/' . $filename . 'ibug.html';
		
		
		if (file_exists($fileAct)) {
			include $fileAct;
		
		}
		$Ibugoutput = ob_get_clean();
		                                 
		return $this->_processIbugOutputEncoding($Ibugoutput);
	}
	
	/**
	 * Determines wether we need to hide content from the Parser or not.
	 *
	 * @return string
	 */
	protected function _processIbugOutputEncoding($output) 
	{	                  
		if ($this->_isParserHook === true) {
			/* Hide content from the Parser using base64 to avoid mangling.
			   Content will be decoded after Tidy has finished its processing of the page */
	    	return '@ENCODED@'.base64_encode($output).'@ENCODED@';
		}                
		return $output;
	}

	protected function simple_encrypt($plaintext)
	{				
		//return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->_salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
		$cipher = "aes-128-gcm";
		$ivlen = openssl_cipher_iv_length($cipher);
    	$iv = openssl_random_pseudo_bytes($ivlen);
    	$ciphertext = openssl_encrypt($plaintext, $cipher, $key, $options=0, $iv, $tag);	
		return $ciphertext;
	}

	protected function simple_decrypt($ciphertext)
	{
		//return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,  $this->_salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));		
		$cipher = "aes-128-gcm";
		$original_plaintext = openssl_decrypt($ciphertext, $cipher, $key, $options=0, $iv, $tag);		
		return $original_plaintext;
	}


	public function html_cleanup($text) 
	{ 
		//$string = htmlentities($text);
		//$out_ = str_replace(array("&lt;i&gt;", "&lt;b&gt;", "&lt;/i&gt;", "&lt;/b&gt;", "&lt;br /&gt;", "&lt;br/&gt;"), array("<i>", "<b>", "</i>", "</b>", "<br />", "<br/>"), $string);                                                    
		$out_ = str_replace("&not","&amp;not",$text);
		return $out_;		
	}	

}