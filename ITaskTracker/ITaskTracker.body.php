<?php
/** @see ITaskTrackerModelDefault **/
require_once dirname(__FILE__) . '/Models/ITaskTrackerModelDefault.php';

/**
 * Issue Tracking System
 * 
 * This class will be loaded automatically when the special page or hook 
 * is requested.
 *
 */
class ITaskTracker extends SpecialPage
{
	/**
	 * Instance of the class.
	 * @var obj
	 */
	protected static $_instance = null;
	
	/**
	 * Instance of ITaskTrackerConfig
	 * @var ITaskTrackerConfig
	 */
	protected $_config = null;
	
	/**
	 * Class constructor
	 * 
	 * @return void
	 */
	public function __construct() 
	{
		
		parent::__construct('ITaskTracker');
		//wfLoadExtensionMessages('ITaskTracker');
		
		$this->_loadIbugConfigFile();
	}
	
	/**
	 * Special Page
	 * 
	 * This methods overrides SpecialPage::execute(), it passes a single 
	 * parameter, usually referred to cryptically as $par.
	 *
	 * @return void
	 */
	public function execute($par) 
	{
		global $wgOut;

		// Set the page namespace
		$titleIbug = Title::makeTitle(NS_SPECIAL, $this->getName());
		$namespaceIbug['dbKeyIbug'] = $titleIbug->getPrefixedDbKey();
		$namespaceIbug['textIbug'] = $titleIbug->getPrefixedDbKey();
		
		// Process request
		$output = $this->_processIbugActionRequest($namespaceIbug);
		
		// Output               
		$this->setHeaders();
		$wgOut->addHtml($output);
	}

	/**
	 * Parser Hook
	 * 
	 * The following method is assigned to a hook, which will be run whenever
	 * the user adds a <bugs /> tag in the main MediaWiki code.
	 *
	 * @param string $text
	 * @param array $args
	 * @param obj $parser
	 * @return str
	 */
	public static function executeIbugHook($text, $args = array(), $parser)
	{		
		$parser->disableCache();
		
		// Set the page namespace
		$namespaceIbug['dbKeyIbug'] = $parser->getTitle()->getPrefixedDBkey();
		$namespaceIbug['textIbug'] = $parser->getTitle()->getPrefixedText();
		
		$isParserHook = true;
		
		// Process request
		$instance = self::_getIbugInstance();
		$output = $instance->_processIbugActionRequest($namespaceIbug, $isParserHook, $args);
		
		return $output;
	}

	/**
	 * Returns a single instance of the class.
	 *
	 * @return obj
	 */
	protected static function _getIbugInstance() 
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Loads the config file.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function _loadIbugConfigFile() 
	{
		$fileIbug = dirname(__FILE__) . '/' . __CLASS__ . '.config.php';
		//echo $fileIbug;
		if (! file_exists($fileIbug)) {
			throw new Exception('Unable to load the configuration file: ' . $fileIbug);
		}
		
		require_once $fileIbug;
		$class = __CLASS__ . 'Config';
		
		$this->setIbugConfig(new $class());
	}
	
	/**
	 * Processes the action request.
	 * 
	 * @return string
	 * @throws Exception
	 */
	protected function _processIbugActionRequest(array $namespaceIbug, $isParserHook = false, $args = array())
	{
		global $wgRequest;
		
		$action = $wgRequest->getText('bt_action', 'list');
		$class = __CLASS__ . 'Action' . ucfirst(strtolower($action));
		$method = $action . 'IbugAction';

		$file = dirname(__FILE__) . '/Actions/' . $class . '.php';
		
		//echo "CLASS : | ".$class;                 		
               
		if (! file_exists($file)) {
                        //die();
			throw new Exception('Invalid file: ' . $file);
		}
		
		if (array_key_exists($action, $this->_config->getIbugPermissions())) {
				require_once dirname(__FILE__) . '/Actions/' . $class . '.php';
				//echo dirname(__FILE__) . '/Actions/' . $class . '.php';
				$controllerIbug = new $class();
				$controllerIbug->setIbugConfig($this->getIbugConfig());
				if ($controllerIbug->hasIbugPermission($action)) {
					$controllerIbug->setIbugAction($action);
					$controllerIbug->setIbugParserHook($isParserHook);
					$controllerIbug->setIbugNamespace($namespaceIbug);
					$controllerIbug->setIbugModel(new ITaskTrackerModelDefault());
					$controllerIbug->setIbugArguments($args);
					if (!method_exists($controllerIbug, 'init') || $controllerIbug->init() === true) {
						$controllerIbug->$method();
                                                    switch ( $class ) :
                                                    case 'ITaskTrackerActionAdd1':                    
                                                    //die();
                                                    //break;
                                                    default :
                                                        return $controllerIbug->getIbugOutput();
                                                    endswitch;						                                                
					}
				}
				return wfMessage('not_authorized');
		}
		return wfMessage('invalid_action') . ': ' . $action; 
	}
	
	/**
	 * Sets the Config object.
	 * 
	 * @param ITaskTrackerConfig
	 * @return void
	 */
	public function setIbugConfig(ITaskTrackerConfig $config)
	{		
		$this->_config = $config;
	}
	
	/**
	 * Returns the config object.
	 *
	 * @return array self::$_config
	 */
	public function getIbugConfig()
	{
		return $this->_config;
	}
}
