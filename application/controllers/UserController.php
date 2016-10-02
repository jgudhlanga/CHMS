<?php
require_once APPLICATION_PATH . "/models/User.php";
require_once APPLICATION_PATH . "/models/Admin.php";
require_once APPLICATION_PATH . "/models/Developer.php";

class UserController extends Zend_Controller_Action
{
	public function indexAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oUser = new User();
		$oForm = new Forms();
		$this->view->aUsers = $oUser->getUsers($aPost);
		$aFormParams = array('width' => '80%', 'button' => 'Search', 'button-icon' => 'search');
		$this->view->sSearchForm = $oForm->generateForm('User Search', 'user', $aPost, $aFormParams);
	}

	public function addUserAction()
	{
		$aPost = $this->getRequest()->getParams();
		$sCommand = (isset($aPost['command']) && $aPost['command'] != '') ? $aPost['command'] : '';
		$iUserID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$oUser = new User();
		if ($sCommand != '')
		{
			if ($sCommand == 'Delete')
			{
				$oUser->deleteUserProfile($iUserID);
				$this->_redirect('/user/index');
			}
			else
			{
				$iUserID = $oUser->saveUser($aPost);
				$this->_redirect('/user/my-profile/id/' . $iUserID);
			}
		}
		$aUser = ($iUserID > 0) ? $oUser->getUsers($aPost) : array();

		$oForm = new Forms();
		$aFormParams = array('width' => '80%');
		$this->view->sForm = $oForm->generateForm('Add User', 'user', $aUser, $aFormParams);
	}

	public function myProfileAction()
	{
		$aPost = $this->getRequest()->getParams();
		$userNameSpace = new Zend_Session_Namespace('user');
		$oAdmin = new Admin();
		$oControl = new Control();
		$oUser = new User();
		$user = isset($userNameSpace->loggedUser) ? $userNameSpace->loggedUser : array();
		$aPost['id'] = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : $user['id'];
		if (isset($aPost['command']) && $aPost['command'] != '')
		{
			if ($aPost['command'] == 'Save About Me')
				$oUser->saveUser($aPost);
			else if ($aPost['command'] == 'Update User Role')
				$oControl->saveUserRole($aPost);
		}
		$this->view->aUser = $oUser->getUsers($aPost);
		$aParams = array('is_role' => 1);
		$this->view->iUserID = $aPost['id'];
		$this->view->aRoles = $oAdmin->getAdminPublic($aParams);
		$aUserRoles = $oControl->getUserRoles();
		$this->view->iUserRoleId = isset($aUserRoles[$aPost['id']]) ? $aUserRoles[$aPost['id']] : 0;
	}

	public function userRolesAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oAdmin = new Admin();
		$iID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aPost['is_role'] = 1;
		$this->view->aRoles = $oAdmin->getAdminPublic($aPost);
		$this->view->aRole = $oAdmin->getAdminPublic($aPost, $iID);
	}

	public function moduleResourcesAction()
	{
		$oAdmin = new Admin();
		$oControl = new Control();
		$oDeveloper = new Developer();
		$this->view->iRoleID = $iRoleID = $this->getRequest()->getParam('role_id', 0);
		$this->view->iControllerID = $iControllerID = $this->getRequest()->getParam('ctrl_id', 0);
		$this->view->aModule = $oDeveloper->getModule($iControllerID);
		$this->view->aModules = $oDeveloper->listModules();
		$this->view->aResources = $oControl->listResources($iControllerID);
		$this->view->aModuleRights = $oControl->getUserRoleRights($this->getRequest()->getParams(), 1);
		$this->view->aResourceRights = $oControl->getUserRoleRights($this->getRequest()->getParams(), 0);
		$this->view->aRole = $oAdmin->getAdminPublic(array(), $iRoleID);
		$this->getRequest()->setParam('is_right', 1);
		$aRights = $oAdmin->getAdminPublic($this->getRequest()->getParams());
		$aArray = array();
		foreach ($aRights as $aRow)
		{
			$aArray[$aRow['level']] = $aRow['name'];
		}
		ksort($aArray, 0);
		$this->view->aRights = $aArray;
	}

	public function updateRoleModuleRightsAction()
	{
		$oControl = new Control();
		$iRoleID = $this->getRequest()->getParam('role_id', 0);
		$oControl->updateRoleModuleRights($this->getRequest()->getParams());
		$this->_redirect('/user/module-resources/role_id/' . $iRoleID);
	}

	public function updateRolePageRightsAction()
	{
		$oControl = new Control();
		$iControllerID = $this->getRequest()->getParam('ctrl_id', 0);
		$iRoleID = $this->getRequest()->getParam('role_id', 0);
		$oControl->updateRoleActionRights($this->getRequest()->getParams());
		$this->_redirect('/user/module-resources/ctrl_id/' . $iControllerID . '/role_id/' . $iRoleID);
	}

	public function adminAction()
	{

	}

	public function reportsAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oUser = new User();
	}
}