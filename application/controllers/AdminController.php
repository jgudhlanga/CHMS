<?php
require_once APPLICATION_PATH . '/models/Admin.php';
require_once APPLICATION_PATH . '/models/Developer.php';

class AdminController extends Zend_Controller_Action
{
	public function init()
	{
		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('save-system-options', 'json')
						->initContext();
	}

	public function indexAction()
	{
	}

	public function importsAction()
	{
		$oAdmin = new Admin();
		$aPost = $this->getRequest()->getParams();
		if (isset($aPost['command']) && $aPost['command'] != '')
		{
			$oAdmin->adminImports($aPost);
		}
		$oForm = new Forms();
		$aFormParams = array('width' => '60%');
		$this->view->sForm = $oForm->generateForm('Imports', 'Setup', null, $aFormParams);
	}

	public function exportsAction()
	{
		$oAdmin = new Admin();
		$aPost = $this->getRequest()->getParams();
	}

	public function dbTableUpdatesAction()
	{
		$oAdmin = new Admin;
		$id = $this->getRequest()->getParam('id', 0);
		$this->view->aGeneralTables = $oAdmin->listDbTableUpdateSettings();
		$this->view->aEdit = $oAdmin->getDbTableUpdateSettings($id);
	}

	public function saveDbTableUpdateSettingsAction()
	{
		$oAdmin = new Admin;
		$oAdmin->saveDbTableUpdateSettings($this->getRequest()->getParams());
		$this->_redirect('/admin/db-table-updates');
	}

	public function activateDbTableUpdateSettingsAction()
	{
		$oAdmin = new Admin;
		$iID = $this->getRequest()->getParam('id', 0);
		$sCommand = $this->getRequest()->getParam('command', '');
		$oAdmin->activateDbTableUpdateSettings($iID, $sCommand);
		$this->_redirect('/admin/db-table-updates');
	}

	public function deleteDbTableUpdateSettingsAction()
	{
		$oAdmin = new Admin;
		$iID = $this->getRequest()->getParam('id', 0);
		$oAdmin->deleteDbTableUpdateSettings($iID);
		$this->_redirect('/admin/db-table-updates');
	}

	public function editTablesAction()
	{
		$oAdmin = new Admin;
		$iEditID = $this->getRequest()->getParam('id', 0);
		$sCommand = $this->getRequest()->getParam('command', '');
		if ($sCommand != '')
		{
			$oAdmin->saveDbTableData($this->getRequest()->getParams());
			$this->_redirect('/admin/edit-tables/id/'.$iEditID);
		}
		$this->view->iWidth = $this->getRequest()->getParam('width', 0);
		$this->view->sEditTable = $oAdmin->generateEditTable($iEditID, $this->getRequest()->getParams());
	}

	public function deleteDbTableDataAction()
	{
		$oAdmin = new Admin;
		$iEditID = $this->getRequest()->getParam('id', 0);
		$iItemID = $this->getRequest()->getParam('item_id', 0);
		$oAdmin->deleteDbTableData($iEditID, $iItemID);
		$this->_redirect("/admin/edit-tables/id/$iEditID");
	}

	public function activateDbTableDataAction()
	{
		$oAdmin = new Admin;
		$iEditID = $this->getRequest()->getParam('id', 0);
		$iItemID = $this->getRequest()->getParam('item_id', 0);
		$sCommand = $this->getRequest()->getParam('command', '');
		$oAdmin->activateDbTableData($iEditID, $iItemID, $sCommand);
		$this->_redirect("/admin/edit-tables/id/$iEditID");
	}

	public function systemResourcesAction()
	{
		$oDeveloper = new Developer();
		$oControl = new Control();
		$aPost = $this->getRequest()->getParams();
		$this->view->iModuleID = $iModuleID = (isset($aPost['module_id']) && $aPost['module_id'] > 0) ? $aPost['module_id'] : 0;
		$this->view->aModules = $aModules = $oDeveloper->listModules();
		if ($iModuleID > 0)
		{
			$this->view->aModule = $oDeveloper->getModule($iModuleID);
			$this->view->aResources = $oControl->listResources($iModuleID);
		}
	}

	public function updateSystemResourcesAction()
	{
		$oControl = new Control();
		$oControl->updateSystemResources($this->getRequest()->getParams());
		$this->_redirect('admin/system-resources');
	}

	public function deleteResourceAction()
	{
		$oControl = new Control();
		$iModuleID = $this->getRequest()->getParam('module_id', 0);
		$iResourceID = $this->getRequest()->getParam('id', 0);
		$oControl->deleteResource($iResourceID);
		$this->_redirect('admin/system-resources/module_id/' . $iModuleID);
	}

	public function organisationConfigAction()
	{
		$oForm = new Forms();
		$this->aOrganizationSettings = $aOrganizationSettings = Bootstrap::getOrganizationSettings();
		$aFormParams = array('width' => '100%');
		$this->view->sForm = $oForm->generateForm('Organization Details', 'admin', $aOrganizationSettings, $aFormParams);
	}

	public function saveOrganizationSettingsAction()
	{
		$oAdmin = new Admin();
		$oAdmin->saveOrganizationSettings($this->getRequest()->getParams());
		$this->_redirect('/admin/organisation-config');
	}

	public function mySettingsAction()
	{
		$oForm = new Forms();
		$this->view->aSettings = $aSettings = Bootstrap::getSystemOptions();
		$aFormParams = array('width' => '100%');
		$this->view->sForm = $oForm->generateForm('System Options', 'admin', $aSettings, $aFormParams);
	}

	public function saveSystemOptionsAction()
	{
		$oAdmin = new Admin();
		$oAdmin->saveSystemOptions($this->getRequest()->getParams());
		$aSettings = Bootstrap::getSystemOptions();
		if($aSettings['system_theme'] == 'integrity' || $aSettings['system_theme'] == 'j-flex')
			$this->_redirect('/index/index');
		else
			die;
	}
	public function userQuickLinksAction()
	{
		$oDeveloper = new Developer();
		$oControl = new Control();
		$aPost = $this->getRequest()->getParams();
		$oAdmin = new Admin();
		if(isset($aPost['command']) && $aPost['command'] != '')
		{
			$oAdmin->saveQuickLinkSettings($aPost);
		}
		$this->view->iModuleID = $iModuleID = (isset($aPost['module_id']) && $aPost['module_id'] > 0) ? $aPost['module_id'] : 0;
		$this->view->iID = $iID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$this->view->aModules = $aModules = $oDeveloper->listModules();
		if ($iModuleID > 0)
		{
			$this->view->aResources = $oControl->listResources($iModuleID);
		}

		$this->view->aQuickLinks = $oAdmin->listAdminRows(QUICK_LINK_SETTINGS);
		$this->view->aEdit = $oAdmin->getAdminRow(QUICK_LINK_SETTINGS, "id=$iID");
	}
	public function deleteQuickLinkAction()
	{
		$oAdmin = new Admin();
		$oAdmin->deleteAdminRow(QUICK_LINK_SETTINGS, $this->getRequest()->getParam('id', 0));
		$this->_redirect('/admin/user-quick-links');
	}
	public function activateQuickLinkAction()
	{
		$oAdmin = new Admin();
		$oAdmin->activateAdminRow(QUICK_LINK_SETTINGS, $this->getRequest()->getParam('command'),$this->getRequest()->getParam('id', 0));
		$this->_redirect('/admin/user-quick-links');
	}

	public function updateQuickLinksAction()
	{
		$oAdmin = new Admin();
		$oAdmin->updateQuickLinks($this->getRequest()->getParams());
		$this->_redirect('/admin/user-quick-links');
	}
}