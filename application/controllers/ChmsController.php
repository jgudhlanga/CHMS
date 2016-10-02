<?php

/**
 * Created by JJ-DEVELOPERS
 * User: James Gudhlanga
 * Date: 29/11/2015 06:20:37
 */
require_once APPLICATION_PATH . '/models/User.php';
require_once APPLICATION_PATH . "/models/Admin.php";
require_once APPLICATION_PATH . "/models/Chms.php";
require_once APPLICATION_PATH . "/models/Projects.php";
require_once APPLICATION_PATH . "/models/Departments.php";


class ChmsController extends Zend_Controller_Action
{
	function init()
	{
		$oAdmin = new Admin();
		$this->view->aChmsAdminLinks = $this->chmsAdminLinks();
		$aGender = $oAdmin->getAdminPublic(array('is_gender'=>1, 'is_active'=>1));
		$this->view->aGender = (!empty($aGender)) ? array_column($aGender, 'name', 'id') : array();
		$aTitles = $oAdmin->getAdminPublic(array('is_title'=>1, 'is_active'=>1));
		$this->view->aTitles = (!empty($aTitles)) ? array_column($aTitles, 'name', 'id') : array();
	}
	public function chmsAdminLinks()
	{
		$aLinks = array(
			array('title'=>'Cell Groups', 'link'=>'/chms/groups', 'class'=>'success', 'status'=>1, 'icon'=>'list-alt'),
			array('title'=>'Data Imports', 'link'=>'/chms/data-imports', 'class'=>'success', 'status'=>1 , 'icon'=>'import'),
			array('title'=>'Departments', 'link'=>'/chms/departments', 'class'=>'success', 'status'=>1, 'icon'=>'list-alt'),
			array('title'=>'Events', 'link'=>'/chms/events', 'class'=>'success', 'status'=>1, 'icon'=>'calendar'),
			array('title'=>'Followups / Visitations', 'link'=>'/chms/followups', 'class'=>'success', 'status'=>1, 'icon'=>'bell'),
			array('title'=>'General Settings', 'link'=>'/chms/general-settings', 'class'=>'success', 'status'=>1, 'icon'=>'cog'),
			array('title'=>'Mailing', 'link'=>'/chms/mailing', 'class'=>'success', 'status'=>1, 'icon'=>'envelope'),
			array('title'=>'Member Profile Tabs', 'link'=>'/chms/profile-tabs', 'class'=>'success', 'status'=>1, 'icon'=>'cog'),
			array('title'=>'Ministries', 'link'=>'/chms/ministries', 'class'=>'success', 'status'=>1, 'icon'=>'tent'),
			array('title'=>'Projects', 'link'=>'/chms/projects', 'class'=>'success', 'status'=>1, 'icon'=>'tent'),
			array('title'=>'Users', 'link'=>'/user/index', 'class'=>'success', 'status'=>1, 'icon'=>'user'),
			array('title'=>'Visitors', 'link'=>'/chms/visitors', 'class'=>'success', 'status'=>1, 'icon'=>'user'),
			array('title'=>'Zones', 'link'=>'/chms/zones', 'class'=>'success', 'status'=>1, 'icon'=>'tent'),
		);
		return $aLinks;
	}
	public function indexAction()
	{
	}

	public function listMembersAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->aMembers= $aMembers =  $oChms->listMembers($aPost);
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 9;
		if(isset($aPost['command']) && $aPost['command'] != '')
		{
			Session::setAlertMessage('info', 'search complete and '.count($aMembers).' results were found');
		}
	}

	public function addNewAction()
	{
		$oForm = new Forms();
		$oUser = new User();
		$aFormParams = array('width' => '80%');
		$this->view->aMember = $aMember = $oUser->getUsers($this->getRequest()->getParams());
		$this->view->sNewMemberForm = $oForm->generateForm('Add/Edit Member', 'chms', $aMember, $aFormParams);
	}

	public function saveMemberAction()
	{
		$oUser = new User();
		$iID = $oUser->saveUser($this->getRequest()->getParams());
		$this->redirect('/chms/view-member/id/'.$iID);
	}

	public function viewMemberAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oAdmin = new Admin();
		$oControl = new Control();
		$oUser = new User();
		$aPost['id'] = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : Session::loggedUserId();
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

	public function saveMemberRoleAction()
	{
		$oControl = new Control();
		$oControl->saveUserRole($this->getRequest()->getParams());
		$this->redirect('/chms/view-member/id/'.$this->getRequest()->getParam('user_id', 0));
	}


	public function groupsAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 15;
		$this->view->aZones = $oChms->listZones();
		$this->view->aGroups = $oChms->listGroups($aPost, 1);
	}

	public function groupViewAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->iGroupID = $iGroupID = $this->getRequest()->getParam('group_id', 0);
		$this->view->aGroup = $aGroup = $oChms->getGroup($iGroupID);
		$this->view->aGroupMembers= $aGroupMembers =  $oChms->listMembers($aPost);
	}

	public function saveGroupAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$oChms->saveCellGroup($aPost);
		if(isset($aPost['id']) && $aPost['id'] > 0)
		{
			Session::setAlertMessage('info', 'The Group has been successfully Updated');
			$this->redirect('/chms/group-view/group_id/'.$aPost['id']);
		}
		else
		{
			Session::setAlertMessage('info', 'The Group has been successfully Added');
			$this->redirect('/chms/groups');
		}
	}

	public function activateGroupAction()
	{
		$oChms = new Chms();
		$sCommand = $this->getRequest()->getParam('command', '');
		$iGroupID = $this->getRequest()->getParam('group_id', 0);
		$oChms->activateChmsRow(CHMS_GROUPS, $sCommand, $iGroupID);
		$this->redirect('/chms/group-view/group_id/'.$iGroupID);
	}
	public function deleteGroupAction()
	{
		$oChms = new Chms();
		$iGroupID = $this->getRequest()->getParam('id', 0);
		$oChms->deleteChmsRow(CHMS_GROUPS, $iGroupID);
		$this->redirect('/chms/groups');
	}
	public function groupMembershipAction()
	{
		$oChms = new Chms();
		$this->view->iGroupID = $iGroupID = $this->getRequest()->getParam('group_id', 0);
		$this->view->aAllMembers= $aAllMembers =  $oChms->listMembers(array('not-in'=>'group_id'));
		$this->view->aGroup =$oChms->getGroup($iGroupID);
	}

	public function saveGroupMembershipAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
		$oChms->saveGroupMembers($aPost);
		$this->redirect('/chms/group-view/group_id/'.$aPost['group_id']);
	}
	public function removeGroupMembershipAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
		$oChms->removeGroupMembers($aPost);
		$this->redirect('/chms/group-view/group_id/'.$aPost['group_id']);
	}
	public function groupAttendanceAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
	}

	public function saveZoneAction()
	{
		$oChms = new Chms();
		$aPost=$this->getRequest()->getParams();
		$oChms->saveZone($aPost);
		if(isset($aPost['id']) && $aPost['id'] > 0)
		{
			Session::setAlertMessage('info', 'The Zone has been successfully Updated');
			$this->redirect('/chms/zone-view/zone_id/'.$aPost['id']);
		}
		else
		{
			Session::setAlertMessage('info', 'The Zone has been successfully Added');
			$this->redirect('/chms/zones');
		}

	}
	public function zonesAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 15;
		$this->view->aZones = $oChms->listZones($aPost, 1);
	}
	public function zoneViewAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->iZoneID = $iZoneID = $this->getRequest()->getParam('zone_id', 0);
		$this->view->aZone  = $aZone =  $oChms->getZone($iZoneID);
		$this->view->aZoneMembers= $aZoneMembers =  $oChms->listMembers($aPost);
	}

	public function activateZoneAction()
	{
		$oChms = new Chms();
		$sCommand = $this->getRequest()->getParam('command', '');
		$iZoneID = $this->getRequest()->getParam('zone_id', 0);
		$oChms->activateChmsRow(CHMS_ZONES, $sCommand, $iZoneID);
		$this->redirect('/chms/zone-view/zone_id/'.$iZoneID);
	}
	public function deleteZoneAction()
	{
		$oChms = new Chms();
		$iZoneID = $this->getRequest()->getParam('id', 0);
		$oChms->deleteChmsRow(CHMS_ZONES, $iZoneID);
		$this->redirect('/chms/zones');
	}
	public function zoneMembershipAction()
	{
		$oChms = new Chms();
		$this->view->iZoneID = $iZoneID = $this->getRequest()->getParam('zone_id', 0);
		$this->view->aAllMembers= $aAllMembers =  $oChms->listMembers(array('not-in'=>'zone_id'));
		$this->view->aZone =$oChms->getZone($iZoneID);
	}

	public function saveZoneMembershipAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
		$oChms->saveZoneMembers($aPost);
		$this->redirect('/chms/zone-view/zone_id/'.$aPost['zone_id']);
	}

	public function removeZoneMembershipAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
		$oChms->removeZoneMembers($aPost);
		$this->redirect('/chms/zone-view/zone_id/'.$aPost['zone_id']);
	}

	public function ministriesAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 15;
		$this->view->aMinistries = $oChms->listMinistries($aPost, 1);
	}

	public function saveMinistryAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$oChms->saveMinistry($aPost);
		if(isset($aPost['id']) && $aPost['id'] > 0)
		{
			Session::setAlertMessage('info', 'The Ministry has been successfully Updated');
			$this->redirect('/chms/ministry-view/ministry_id/'.$aPost['id']);
		}
		else
		{
			Session::setAlertMessage('info', 'The Ministry has been successfully Added');
			$this->redirect('/chms/ministries');
		}
	}

	public function ministryViewAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$this->view->iMinistryID = $iMinistryID = $this->getRequest()->getParam('ministry_id', 0);
		$this->view->aMinistry = $aMinistry = $oChms->getMinistry($iMinistryID);
		$this->view->aMinistryMembers =  $oChms->listMembers($aPost);
	}

	public function activateMinistryAction()
	{
		$oChms = new Chms();
		$sCommand = $this->getRequest()->getParam('command', '');
		$iMinistryID = $this->getRequest()->getParam('ministry_id', 0);
		$oChms->activateChmsRow(CHMS_MINISTRIES, $sCommand, $iMinistryID);
		$this->redirect('/chms/ministry-view/ministry_id/'.$iMinistryID);
	}
	public function deleteMinistryAction()
	{
		$oChms = new Chms();
		$iMinistryID = $this->getRequest()->getParam('id', 0);
		$oChms->deleteChmsRow(CHMS_MINISTRIES, $iMinistryID);
		$this->redirect('/chms/ministries');
	}

	public function ministryMembershipAction()
	{
		$oChms = new Chms();
		$this->view->iMinistryID = $iMinistryID = $this->getRequest()->getParam('ministry_id', 0);
		$this->view->aAllMembers= $aAllMembers =  $oChms->listMembers(array('not-in'=>'ministry_id'));
		$this->view->aMinistry =$oChms->getMinistry($iMinistryID);
	}

	public function saveMinistryMembershipAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
		$oChms->saveMinistryMembers($aPost);
		$this->redirect('/chms/ministry-view/ministry_id/'.$aPost['ministry_id']);
	}
	public function removeMinistryMembershipAction()
	{
		$aPost = $this->getRequest()->getParams();
		$oChms = new Chms();
		$oChms->removeMinistryMembers($aPost);
		$this->redirect('/chms/ministry-view/ministry_id/'.$aPost['ministry_id']);
	}


	public function departmentsAction()
	{
		$oDepartment = new Departments();
		$this->view->aDepartments = $oDepartment->listDepartments();
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 15;
		$this->renderScript('/departments/departments.phtml');
	}

	public function departmentViewAction()
	{
		$oDepartment = new Departments();
		$iDepartmentID = $this->getRequest()->getParam('department_id', 0);
		$this->view->aDepartment = $aDepartment = $oDepartment->getDepartment($iDepartmentID);
		$this->renderScript('/departments/department-view.phtml');
	}

	public function activateDepartmentAction()
	{
		$oDepartment = new Departments();
		$iDepartmentID = $this->getRequest()->getParam('department_id', 0);
		$oDepartment->activateDepartment($iDepartmentID, $this->getRequest()->getParam('command', ''));
		$this->redirect('/chms/department-view/department_id/'.$iDepartmentID);
	}
	public function deleteDepartmentAction()
	{
		$oDepartment = new Departments();
		$iDepartmentID = $this->getRequest()->getParam('id', 0);
		$oDepartment->deleteDepartment($iDepartmentID);
		$this->redirect('/chms/departments');
	}

	public function projectsAction()
	{
		$oProject = new Projects();
		$this->view->aProjects = $oProject->listProjects();
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 15;
		$this->renderScript('/projects/projects.phtml');
	}

	public function projectViewAction()
	{
		$oProject = new Projects();
		$iProjectID = $this->getRequest()->getParam('project_id', 0);
		$this->view->aProject = $aProject = $oProject->getProject($iProjectID);
		$this->renderScript('/projects/project-view.phtml');
	}

	public function saveProjectAction()
	{
		$oProject = new Projects();
		$aPost = $this->getRequest()->getParams();
		$oProject->saveProject($aPost);
		if(isset($aPost['id']) && $aPost['id'] > 0)
		{
			Session::setAlertMessage('info', 'The Project has been successfully Updated');
			$this->redirect('/chms/project-view/project_id/'.$aPost['id']);
		}
		else
		{
			Session::setAlertMessage('info', 'The Project has been successfully Added');
			$this->redirect('/chms/projects');
		}
	}
	public function saveDepartmentAction()
	{
		$oDepartment = new Departments();
		$aPost = $this->getRequest()->getParams();
		$oDepartment->saveDepartment($aPost);
		if(isset($aPost['id']) && $aPost['id'] > 0)
		{
			Session::setAlertMessage('info', 'The Department has been successfully Updated');
			$this->redirect('/chms/department-view/department_id/'.$aPost['id']);
		}
		else
		{
			Session::setAlertMessage('info', 'The Department has been successfully Added');
			$this->redirect('/chms/departments');
		}
	}

	public function activateProjectAction()
	{
		$oProject = new Projects();
		$iProjectID = $this->getRequest()->getParam('project_id', 0);
		$oProject->activateProject($iProjectID, $this->getRequest()->getParam('command', ''));
		$this->redirect('/chms/project-view/project_id/'.$iProjectID);
	}
	public function deleteProjectAction()
	{
		$oProject = new Projects();
		$iProjectID = $this->getRequest()->getParam('id', 0);
		$oProject->deleteProject($iProjectID);
		$this->redirect('/chms/projects');
	}


	public function libraryAction()
	{

	}
	public function reportsAction()
	{
		$oForm = new Forms();
		$aEdit = array();
		$aFormParams = array('width' => '80%', 'button'=>'Draw Report', 'button-icon'=>'search');
		$this->view->sForm = $oForm->generateForm('Chms Reports', 'chms', $aEdit, $aFormParams);
	}
	public function adminAction()
	{

	}
	public function generalSettingsAction()
	{
		$oChms = new Chms();
		$this->view->aModuleSettings = $aModuleSettings = $oChms->getSettings();
	}

	public function saveGeneralSettingsAction()
	{
		$oChms = new Chms();
		$oChms->saveModuleSettings($this->getRequest()->getParams());
		$this->redirect('/chms/general-settings');
	}

	public function visitorsAction()
	{
		$oChms = new Chms();
		$aPost= $this->getRequest()->getParams();
		$this->view->iCurrentPage = $this->getRequest()->getParam('current-page', 1);
		$this->view->iPerPage = 8;
		$this->view->aVisitors = $oChms->listVisitors($aPost, 1);
	}
	public function viewVisitorAction()
	{
		$oChms = new Chms();
		$iVisitorID = $this->getRequest()->getParam('id', 0);
		$this->view->aVisitor = $oChms->getVisitor($iVisitorID);
	}

	public function saveVisitorAction()
	{
		$oChms = new Chms();
		$aPost = $this->getRequest()->getParams();
		$oChms->saveVisitor($aPost);
		if(isset($aPost['id']) && $aPost['id'] > 0)
		{
			Session::setAlertMessage('info', 'The Visitor has been successfully Updated');
			$this->redirect('/chms/view-visitor/id/'.$aPost['id']);
		}
		else
		{
			Session::setAlertMessage('info', 'The Visitor has been successfully Added');
			$this->redirect('/chms/visitors');
		}
	}

	public function profileTabsAction()
	{
		$oForm = new Forms();
		$aEdit = array();
		$aFormParams = array('width' => '80%');
		$this->view->sForm = $oForm->generateForm('Chms Member Profile Tabs', 'chms', $aEdit, $aFormParams);
	}
}
