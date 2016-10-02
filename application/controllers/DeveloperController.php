<?php
require_once APPLICATION_PATH . '/models/Developer.php';
require_once APPLICATION_PATH . '/models/Admin.php';

/**
 * Created by PhpStorm.
 * User: jimlink
 * Date: 7/31/2015
 * Time: 10:16 PM
 */
class DeveloperController extends Zend_Controller_Action
{
	public function _init()
	{
		
	}
	public function indexAction()
	{
		$oDev = new Developer();
		$this->view->aModules = $aModules = $oDev->listModules(0, 0, 1, 0);
	}

	public function activateModuleAction()
	{
		$oDeveloper = new Developer();
		$oDeveloper->activateModule($this->getRequest()->getParam('id', 0), $this->getRequest()->getParam('command', ''));
		$this->redirect('developer/index');
	}

	public function deleteModuleAction()
	{
		$oDeveloper = new Developer();
		$sFrom = $this->getRequest()->getParam('from', '');
		$iParentModuleID = $this->getRequest()->getParam('module_id', 0);
		$oDeveloper->deleteModule($this->getRequest()->getParam('id', 0));
		$sAppendToUrl = '';
		if ($iParentModuleID > 0)
			$sAppendToUrl = '/id/' . $iParentModuleID;
		$this->redirect('developer/' . $sFrom . $sAppendToUrl);
	}

	public function updateInstallationsAction()
	{
		$oDeveloper = new Developer();
		$oDeveloper->updateInstallations($this->getRequest()->getParams());
		$this->redirect('developer/index');
	}

	public function updateModuleVersionAction()
	{
		$oDeveloper = new Developer();
		$oDeveloper->updateModuleVersion($this->getRequest()->getParams());
		$this->redirect('developer/index');
	}

	public function orderModulePositionsAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oDev = new Developer();
		$oDev->orderPositions($aPost);
		$config = Zend_Registry::get('config');
		$sBaseUrl = $config->baseUrl . '/' . $aPost['controller'];
		if (isset($aPost['from']) && $aPost['from'] == 'view')
		{
			$sBaseUrl = $config->baseUrl . '/' . $aPost['controller'] . '/view/id/' . $aPost['module_id'];
		}
		$this->redirect("$sBaseUrl");
	}

	public function addNewAction()
	{
		$oDev = new Developer();
		$oForm = new Forms();
		$aPost = $this->getRequest()->getParams();
		$iID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$iModuleID = (isset($aPost['module_id']) && $aPost['module_id'] > 0) ? $aPost['module_id'] : 0;
		$sError = '';
		$aFields = array();
		if (isset($aPost['command']) && $aPost['command'] != '')
		{
			$iID = $oDev->saveModuleItems($aPost);
			$aFields = array();
			if (!is_numeric($iID))
			{
				$sError = $iID;
				$aFields = $aPost;
			}
			else if($iModuleID > 0)
				$this->redirect("/developer/view/id/".$iModuleID);
			else
				$this->redirect("/developer/index");
		}
		$this->view->sMessage = $sError;
		$aModule = $oDev->getModule($iID);
		$aData = (!empty($aFields)) ? $aFields : $aModule;
		$aFormParams = array('width' => '90%');
		$this->view->sForm = $oForm->generateForm('Add Module', 'Developer', $aData, $aFormParams);
	}

	public function viewAction()
	{
		$aPost = $this->getRequest()->getParams();
		$iParentID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$oDev = new Developer();
		$this->view->aModules = $oDev->listModules($iParentID, 0, 0, 0);
		$this->view->aModule = $oDev->getModule($iParentID);
	}

	public function formsAction()
	{
		$oDev = new Developer();
		$oForm = new Forms();
		$oAdmin = new Admin();
		$sCommand = $this->getRequest()->getParam('command', '');
		$iFormId = $this->getRequest()->getParam('id', 0);
		$iModuleId = $this->getRequest()->getParam('module_id', 0);
		$sError = '';
		if ($sCommand != '')
		{
			$iFormId = $oForm->saveForm($this->getRequest()->getParams());
			if (!is_numeric($iFormId))
			{
				$sError = $iFormId;
				$this->view->sError = $sError;
				$this->view->aFields = $this->getRequest()->getParams();
			}
			if ($sError == '')
				$this->redirect("/developer/forms/module_id/$iModuleId");
		}
		$this->view->aForms = $oForm->listForms($iModuleId);
		$this->view->aForm = $oForm->getForm($iFormId);
		$this->view->aModule = $oDev->getModule($iModuleId);
		$this->view->iModuleId = $iModuleId;
		$aParams = array('is_active' => 1, 'is_form_option' => 1);
		$this->view->aFormSplitOptions = $oAdmin->getAdminPublic($aParams);
	}

	public function deleteFormAction()
	{
		$oForm = new Forms();
		$iFormId = $this->getRequest()->getParam('id', 0);
		$iModuleId = $this->getRequest()->getParam('module_id', 0);
		$oForm->deleteForm($iFormId);
		$this->redirect("/developer/forms/module_id/$iModuleId");
	}

	public function fieldsAction()
	{
		$oForm = new Forms();
		$aPost = $this->getRequest()->getParams();
		$sCommand = (isset($aPost['command']) && $aPost['command'] != '') ? $aPost['command'] : '';
		$this->view->iFormId = $iFormId = (isset($aPost['form_id']) && $aPost['form_id'] > 0) ? $aPost['form_id'] : 0;
		$iId = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$this->view->iModuleId = $iModuleId = (isset($aPost['module_id']) && $aPost['module_id'] > 0) ? $aPost['module_id'] : 0;
		$sError = '';
		if ($sCommand != '')
		{
			$iId = $oForm->saveFields($aPost);
			if (!is_numeric($iId))
			{
				$sError = $iId;
				$this->view->aPreFields = $aPost;
				$this->view->sError = $sError;
			}
			if ($sError == '')
				$this->redirect("/developer/fields/form_id/$iFormId/module_id/$iModuleId");
		}
		$this->view->aFields = $oForm->listFormFields($iFormId, NULL, 1);
		$this->view->aField = $aField = $oForm->getFormField($iId);
		$this->view->aForm = $oForm->getForm($iFormId);
		$aFormParams = array('width' => '80%');
		$this->view->sForm = $oForm->generateForm('Add/Edit Fields', 'Developer', $aField, $aFormParams);
	}

	public function orderFieldsAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oForms = new Forms();
		$oForms->orderFields($aPost);
		$sBaseUrl = '/' . $aPost['controller'] . '/' . 'fields/form_id/' . $aPost['form_id'] . '/module_id/' . $aPost['module_id'];
		$this->redirect("$sBaseUrl");
	}

	public function deleteFieldAction()
	{
		$oForm = new Forms();
		$iFormId = $this->getRequest()->getParam('form_id', 0);
		$iModuleId = $this->getRequest()->getParam('module_id', 0);
		$oForm->deleteField($this->getRequest()->getParam('id', 0));
		$this->redirect("/developer/fields/form_id/$iFormId/module_id/$iModuleId");
	}

	public function activateFieldAction()
	{
		$oForm = new Forms();
		$iFormId = $this->getRequest()->getParam('form_id', 0);
		$iModuleId = $this->getRequest()->getParam('module_id', 0);
		$oForm->activateField($this->getRequest()->getParam('id', 0), $this->getRequest()->getParam('command', ''));
		$this->redirect("/developer/fields/form_id/$iFormId/module_id/$iModuleId");
	}

	public function fieldOptionsAction()
	{
		$oForms = new Forms();
		$this->view->iFieldId = $iFieldId = $this->getRequest()->getParam('field_id', 0);
		$this->view->iFormId = $iFormId = $this->getRequest()->getParam('form_id', 0);
		$this->view->iModuleId = $iModuleId = $this->getRequest()->getParam('module_id', 0);
		$this->view->iId = $iId = $this->getRequest()->getParam('id', 0);
		$sCommand = $this->getRequest()->getParam('command');
		if ($sCommand != '')
		{
			$iId = $oForms->saveFieldOptions($this->getRequest()->getParams());
			$this->redirect("/developer/field-options/field_id/$iFieldId/form_id/$iFormId/module_id/$iModuleId");
		}
		$this->view->aField = $oForms->getFormField($iFieldId);
		$this->view->aFieldOptions = $oForms->listFormFieldOptions($iFieldId, 1);
		$this->view->aEdit = $aEdit = $oForms->getFormFieldOption($iId);
		$aFormParams = array('width' => '70%');
		$this->view->sForm = $oForms->generateForm('New Option', 'developer', $aEdit, $aFormParams);
	}

	public function deleteFieldOptionAction()
	{
		$oForms = new Forms();
		$iFieldId = $this->getRequest()->getParam('field_id', 0);
		$iFormId = $this->getRequest()->getParam('form_id', 0);
		$iModuleId = $this->getRequest()->getParam('module_id', 0);
		$iId = $this->getRequest()->getParam('id', 0);
		$oForms->deleteFieldOption($iId);
		$this->redirect("/developer/field-options/field_id/$iFieldId/form_id/$iFormId/module_id/$iModuleId");
	}

	public function activateFieldOptionAction()
	{
		$oForms = new Forms();
		$iFieldId = $this->getRequest()->getParam('field_id', 0);
		$iFormId = $this->getRequest()->getParam('form_id', 0);
		$iModuleId = $this->getRequest()->getParam('module_id', 0);
		$iId = $this->getRequest()->getParam('id', 0);
		$sCommand = $this->getRequest()->getParam('command');
		$oForms->activateFieldOption($iId, $sCommand);
		$this->redirect("/developer/field-options/field_id/$iFieldId/form_id/$iFormId/module_id/$iModuleId");
	}

	public function orderFieldOptionsAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oForms = new Forms();
		$oForms->orderFieldOptions($aPost);
		$sBaseUrl = '/' . $aPost['controller'] . '/' . 'field-options/field_id/' . $aPost['field_id'] . '/form_id/' . $aPost['form_id'] . '/module_id/' . $aPost['module_id'];
		$this->redirect("$sBaseUrl");
	}

	public function workflowAction()
	{
		$oForm = new Forms();
		$oDeveloper = new Developer();
		$iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$sCommand = $this->getRequest()->getParam('command', '');
		if ($sCommand != '')
		{
			//save the workflow
			$iWorkflowID = $oDeveloper->saveSystemWorkflow($this->getRequest()->getParams());
			$this->redirect("/developer/workflow");
		}
		$aData = array();
		if ($iWorkflowID > 0)
		{
			$aData = $oDeveloper->getSystemWorkflow($iWorkflowID);
		}
		$this->view->aWorkflow = $oDeveloper->listSystemWorkflow();
		$aFormParams = array('width' => '98%');
		$this->view->sForm = $oForm->generateForm('Add / Edit Workflow', 'Developer', $aData, $aFormParams);
	}

	public function activateWorkflowAction()
	{
		$oDeveloper = new Developer();
		$iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$oDeveloper->activateWorkflow($iWorkflowID, $this->getRequest()->getParam('command'));
		$this->redirect("/developer/workflow");
	}

	public function deleteWorkflowAction()
	{
		$oDeveloper = new Developer();
		$iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$oDeveloper->deleteWorkflow($iWorkflowID);
		$this->redirect("/developer/workflow");
	}

	public function workflowStepAction()
	{
		$oDeveloper = new Developer();
		$oForm = new Forms();
		$this->view->iWorkFlowID = $iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$this->view->iStepID = $iStepID = $this->getRequest()->getParam('step_id', 0);
		$sCommand = $this->getRequest()->getParam('command', '');
		$sError = '';
		if ($sCommand != '')
		{
			$iStepID = $oDeveloper->saveSystemWorkflowSteps($this->getRequest()->getParams());
			if (!is_numeric($iStepID))
				$sError = $iStepID;
			$this->view->sError = $sError;
			if ($sError == '')
				$this->redirect('/developer/workflow-step/workflow_id/' . $iWorkflowID);
		}
		$this->view->aWorkflow = $oDeveloper->getSystemWorkflow($iWorkflowID);
		$this->view->aWorkflowSteps = $oDeveloper->getSystemWorkflowSteps($this->getRequest()->getParams());
		$aEditData = $oDeveloper->getWorkflowStep($iStepID);
		$aFormParams = array('width' => '100%');
		$this->view->sForm = $oForm->generateForm('Add / Edit Step', 'Developer', $aEditData, $aFormParams);
	}

	public function deleteWorkflowStepAction()
	{
		$iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$iStepID = $this->getRequest()->getParam('step_id', 0);
		$oDeveloper = new Developer();
		$oDeveloper->deleteWorkflowStep($iStepID);
		$this->redirect('/developer/workflow-step/workflow_id/' . $iWorkflowID);
	}

	public function activateWorkflowStepAction()
	{
		$iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$iStepID = $this->getRequest()->getParam('step_id', 0);
		$sCommand = $this->getRequest()->getParam('command', '');
		$oDeveloper = new Developer();
		$oDeveloper->activateWorkflowStep($iStepID, $sCommand);
		$this->redirect('/developer/workflow-step/workflow_id/' . $iWorkflowID);
	}

	public function orderStepsAction()
	{
		$oDeveloper = new Developer();
		$sBaseUrl = Zend_Registry::get('config')->baseUrl;
		$iWorkflowID = $this->getRequest()->getParam('workflow_id');
		$oDeveloper->orderSteps($this->getRequest()->getParams());
		$this->redirect("$sBaseUrl/developer/workflow-step/workflow_id/$iWorkflowID");
	}

	public function stepActivitiesAction()
	{
		$this->view->iStepID = $iStepID = $this->getRequest()->getParam('step_id', 0);
		$this->view->iWorkflowID = $iWorkflowID = $this->getRequest()->getParam('workflow_id', 0);
		$oForm = new  Forms();
		$aEditData = array();
		$aFormParams = array('width' => '100%');
		$this->view->sForm = $oForm->generateForm('Step Activities', 'Developer', $aEditData, $aFormParams);
	}

	public function importsAction()
	{

	}

	public function workflowOperatorsAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oDeveloper = new Developer();
	}
	public function formExportAction()
	{
		$oForm = new Forms();
		$oForm->formExport($this->getrequest()->getParam('id', 0));
	}
	public function formImportAction()
	{
		$oForm = new Forms();
		$oForm->importForm($this->getRequest()->getParams());
		$this->redirect("/developer/forms/module_id/".$this->getRequest()->getParam('module_id', 0));
	}
	
	public function installAsStandAloneAction()
	{
		$oDeveloper = new Developer();
		$oDeveloper->installAsStandAlone($this->getRequest()->getParam('module_id', 0));
		$this->redirect('/developer/index');
	}
}