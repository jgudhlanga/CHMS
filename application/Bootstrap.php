<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initSession()
	{
		$root = BASE_PATH;
		Zend_Session::start(array('save_path' => $root . '/application/tmp/',
								  'name' => 'JJdevSESSID',
								  'remember_me_seconds' => 7200
							));
	}

	public function _initErrorReporting()
	{
		if (APPLICATION_ENV == 'development')
		{
			error_reporting(E_ALL);
			ini_set('display_startup_errors', 1);
			ini_set('display_errors', 1);
		}
	}

	public function _initGlobalPlugin()
	{

		$this->bootstrap('frontController');

		require_once BASE_PATH . '/application/controllers/GlobalControllerPlugin.php';
		$plugin = new GlobalControllerPlugin();
		$front = Zend_Controller_Front::getInstance();
		$front->registerPlugin($plugin);

		return $plugin;
	}

	public function _initAclPlugin()
	{
		$this->bootstrap('frontController');
		require_once BASE_PATH . '/application/controllers/AclPlugin.php';
		$plugin = new ControlAcl();
		$front = Zend_Controller_Front::getInstance();
		$front->registerPlugin($plugin);
		return $plugin;
	}

	protected function _initView()
	{
		// Initialize view
		$view = new Zend_View();
		//$view->env = APPLICATION_ENV;
		// Add it to the ViewRenderer
		//add JQUERY
		ZendX_JQuery::enableView($view);
		$view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$viewRenderer->setView($view);
		$this->bootstrap('layout');
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		// Return it, so that it can be stored by the bootstrap
		//return $view;
	}

	protected function _initConfig()
	{
		$root = BASE_PATH;
		try
		{
			$frontendOptions = array('master_file' => $root . '/application/configs/application.ini', 'automatic_serialization' => true, 'lifetime' => 7200);//cache is valid for 2 hrs
			$backendOptions = array('cache_dir' => $root . '/application/tmp/');

			$cache = Zend_Cache::factory('File',
										 'File',
										 $frontendOptions,
										 $backendOptions);
			if (!$config = $cache->load('config'))
			{
				$config = new Zend_Config_Ini($root . '/application/configs/application.ini', null, array('allowModifications' => true));
				$cache->save($config, 'config');
			}
		}
		catch (Exception $e)
		{
			if (file_exists($root . '/application/configs/application_tmp.ini'))
			{
				copy($root . '/application/configs/application_tmp.ini', $root . '/application/configs/application.ini');
			}
			$config = new Zend_Config_Ini($root . '/application/configs/application.ini', null, array('allowModifications' => true));
		}
		Zend_Registry::set('config', $config);
		return $config;
	}

	protected function _initRequest()
	{
		$front = Zend_Controller_Front::getInstance();
		$request = $front->getRequest();
		if (null === $request)
		{
			$request = new Zend_Controller_Request_Http();
			$front->setRequest($request);
		}
		return $request;
	}

	//do a function to perform Access Control

	protected function _initDb()
	{
		$root = BASE_PATH;
		$config = Zend_Registry::get('config');

		$active_db = $config->active_database;
		$db_config = new Zend_Config_Ini($root . '/application/configs/application.ini', $active_db);

		$db_config = $db_config->toArray();
		$db_config = $db_config['database'];

		$adapter = $db_config['adapter'];
		$params = array();
		foreach ($db_config as $key => $value)
		{
			if ($key == 'adapter') continue;
			$params[$key] = $value;
		}

		$db = Zend_Db::factory($adapter, $params);
		if ($config->database_auto_connect)
		{
			try
			{
				$db->getConnection();
			}
			catch (Zend_Db_Exception $e)
			{
				throw new Intranet_Exception($e->getMessage(), 'Message');
			}
			catch (Zend_Exception $e)
			{
				throw new Intranet_Exception($e->getMessage(), 'Message');
			}
			$db->closeConnection();
		}

		Zend_Registry::set('db', $db);
		Zend_Db_Table::setDefaultAdapter($db);
		$frontendOptions = array('automatic_serialization' => true);//cache is valid for ever
		$backendOptions = array('cache_dir' => $root . '/application/tmp/');

		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

		Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
		return $db;
	}

	protected function _initStandAloneModule()
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()->from(SYSTEM_MODULES)->where('install_as_stand_alone=? ', 1);
		$aStandAlone = $db->fetchRow($sql);
		if(isset($aStandAlone['id']))
			Session::set('stand_alone_module_id', $aStandAlone['id']);
	}
	public static function getModuleID($module = '')
	{
		$db = Zend_Registry::get('db');
		$id = 0;
		if ($module != '')
		{
			$sql = $db->select()->from(SYSTEM_MODULES, array('id'))->where('path =? ', "$module");
			$id = $db->fetchone($sql);
		}
		return $id;
	}

	public static function getModule($iModuleID=0)
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()->from(SYSTEM_MODULES)->where('id =? ', $iModuleID);
		return $db->fetchRow($sql);
	}

	public static function getModules($module_id = 0, $bAdmin = 0)
	{
		$db = Zend_Registry::get('db');
		if ($module_id > 0)
		{
			$sql = $db->select()
				->from(SYSTEM_MODULES)->where('is_active=?', 1)
				->where('module_id=?', $module_id)
				->order(array('position ASC'));
		}
		else
		{
			$sql = $db->select()
				->from(SYSTEM_MODULES)
				->where('is_active=?', 1)->where('installed=?', 1)
				->where('module_id=0')
				->order(array('position ASC'));
		}
		return $db->fetchAll($sql);
	}

	public static function  getUserModules()
	{
		$aAllModules = Bootstrap::getModules();//get the parent modules which the current user has access to
		$aReturn = array();
		foreach ($aAllModules as $aModule)
		{
			if (Control::hasAccess($aModule['name']) && $aModule['name'] != 'user' && $aModule['name'] != 'example')
			{
				$aReturn[] = $aModule;
			}
		}
		return $aReturn;
	}

	public static function saveEnviroment($aPost = array())
	{
		$sString = '';
		foreach ($aPost as $sKey => $sEnv) $sString = "$sKey = $sEnv ";
		if ($sString != '' && file_exists(APPLICATION_PATH . '/configs/env.ini'))
		{
			$fOpen = fopen(APPLICATION_PATH . '/configs/env.ini', 'w');
			fwrite($fOpen, $sString);
			fclose($fOpen);
		}
	}

	public static function  getOrganizationSettings()
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(COMPANY_SETUP)
			->where('id > ?', 0)
			->order('id ASC');
		$aReturn = $db->fetchrow($sql);
		return $aReturn;
	}

	public static function  getSystemOptions()
	{
		$db = Zend_Registry::get('db');
		$nameSpace = new Zend_Session_Namespace('user');
		$user = (isset($nameSpace->loggedUser) && !empty($nameSpace->loggedUser)) ? $nameSpace->loggedUser : array();
		$iUserID = (isset($user['id']) && $user['id'] > 0) ? $user['id'] : 0;
		$sql = $db->select()
			->from(USER_SETTINGS)
			->where('user_id =?', $iUserID)
			->order('id ASC');
		$aReturn = $db->fetchrow($sql);
		return $aReturn;
	}

	public static function createModuleQuickLinks()
	{
		$frontController = Zend_Controller_Front::getInstance();
		$controllerName = $frontController->getRequest()->getControllerName();
		$actionName = $frontController->getRequest()->getActionName();
		$iModuleID = Bootstrap::getModuleID($controllerName);
		/* get the module quick link settings and check whether they are allowed in that module */

		$db = Zend_Registry::get('db');
		$sql = $db->select()->from(QUICK_LINK_SETTINGS)
			->where('module_id=?', $iModuleID)
			->where('link NOT LIKE ?',"$actionName")
			->where('is_active=?',1)
			->order(array('resource'));
		$aLinks =  $db->fetchAll($sql);
		$config = Zend_Registry::get('config');
		$sBaseUrl = $config->baseUrl;
		$aModule = Bootstrap::getModule($iModuleID);
		$sLinks = '';
		if(!empty($aLinks))
		{
			$sLinks .= '<div class="row">';
			$sLinks .= '<ul class="col-md-6 jjdev-quick-links">';
			foreach($aLinks as $aLink)
			{
				$sLinks .= '<li>';
				$sLinks .= "<a href=\"$sBaseUrl/$aModule[path]/$aLink[link]\">".ucwords($aLink['resource'])." "."&raquo;</a>";
				$sLinks .= '</li>';
			}
			$sLinks .= '</ul>';
			$sLinks .= '</div>';
		}
		return $sLinks;
	}
}