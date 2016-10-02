<?php

class GlobalControllerPlugin extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$config = Zend_Registry::get('config');

		$layout = Zend_Layout::getMvcInstance();
		$view = $layout->getView();
		$frontController = Zend_Controller_Front::getInstance();
		$controllerName = $frontController->getRequest()->getControllerName();
		$actionName = $frontController->getRequest()->getActionName();
		$userNameSpace = new Zend_Session_Namespace('user');
		$user = isset($userNameSpace->loggedUser) ? $userNameSpace->loggedUser : array();

		/** If the controller is not the Login or Error controller, then check for
		 *  a valid logged in  user and redirect to the login page if none found */
		if (($controllerName !== 'login') && ($controllerName !== 'error'))
		{
			if (!isset($user['id']))
			{
				$this->_response->setRedirect('/login')->sendResponse();
				exit;
			}
		}
		/* this can be accessed in all view pages */
		$view->baseUrl = $config->baseUrl;
		//$view->sDeleteBtn = loadImage(Null, Null, 'delete.png', 16, 16, null, null, 'Delete');
		$view->sDeleteBtn = '<span class="glyphicon glyphicon-trash"   title="Delete Item"></span>';
		//$view->sUpBtn = loadImage(Null, Null, 'up.png', 16, 10, null, null, 'Move Up');
		$view->sUpBtn = '<span class="glyphicon glyphicon-circle-arrow-up" title="Move Item Up"></span>';
		//$view->sDownBtn = loadImage(Null, Null, 'down.png', 16, 10, null, null, 'Move Down');
		$view->sDownBtn = '<span class="glyphicon glyphicon-circle-arrow-down" style="color: #999;" title="Move Item Down"></span>';
		$view->sLeftBtn = '<span class="glyphicon glyphicon-circle-arrow-left" ></span>';
		$view->sRightBtn = '<span class="glyphicon glyphicon-circle-arrow-right"></span>';
		$view->sEditIBtn = '<span class="glyphicon glyphicon-edit"  title="Edit Item"></span>';
		$view->sOkBtn = '<span class="glyphicon glyphicon-check"  title="Unblock this Item"></span>';
		$view->sBanBtn = '<span class="glyphicon glyphicon-ban-circle"  title="Block this item"></span>';
		/*User Theme */
		$aSet = Bootstrap::getSystemOptions();
		$view->userTheme = (isset($aSet['system_theme']) && $aSet['system_theme'] != '') ? trim(strtolower($aSet['system_theme'])) : 'j-flex';
		$view->userMenuIcon = (isset($aSet['menu_icons']) && $aSet['menu_icons'] != '') ? trim(strtolower($aSet['menu_icons'])) : 'plain-text';
		$view->userColor = (isset($aSet['theme_color']) && $aSet['theme_color'] != '') ? trim(strtolower($aSet['theme_color'])) : '';

	}
}