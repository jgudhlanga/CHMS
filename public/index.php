<?php
/**
 * Created by PhpStorm.
 * User: jimlink
 * Date: 7/31/2015
 * Time: 10:01 PM
 */
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
define('APPLICATION_PATH', BASE_PATH . '/application');
// Include path
set_include_path(
	'W:/jjdev_library'.get_include_path()
);
$sEnvFileString = file_get_contents(APPLICATION_PATH.'/configs/env.ini');
$aData = explode('=', $sEnvFileString);
$sWorkingEnvironment = isset($aData[1]) ? trim(strtolower($aData[1])) : 'development';

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV',
	(getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : $sWorkingEnvironment));//development or production

// Zend_Application
require_once 'Zend/Application.php';
require_once "Zend/Loader/Autoloader.php";
require_once "JJDEV/DbConstants.php";
require_once "JJDEV/Session.php";
require_once "JJDEV/Tools.php";
require_once "JJDEV/Control.php";
require_once "JJDEV/Forms.php";
require_once 'JJDEV/PDF.php';
require_once 'Bvb/Grid.php';
require_once APPLICATION_PATH.'/models/DbHook.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('ZFDebug');
$autoloader->registerNamespace('Bvb');
$autoloader->registerNamespace('My');
$autoloader->registerNamespace('OFC');
$autoloader->registerNamespace('Zendx');

$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');

$application->bootstrap();
$application->run();