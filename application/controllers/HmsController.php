<?php

/**
 * Created by JJ-DEVELOPERS
 * User: James Gudhlanga
 * Date: 12/03/2016 03:32:32
 */
require_once APPLICATION_PATH . '/models/Chms.php';

class HmsController extends Zend_Controller_Action
{
	public function preDispatch()
	{
		$aMaleFemale = array('males' => 'Males', 'females' => 'Females', 'mixed'=> 'Both');
		$this->view->aMaleFemale = $aMaleFemale;
	}

	public function indexAction()
	{
		$oHms = new Hms();
		$this->view->aHostels = $aHostels = $oHms->listHostels();
	}

	public function moduleSettingsAction()
	{

	}
	public function generalSettingsAction()
	{

	}

	public function hostelListAction()
	{
		$oHms =  new Hms();
		$oForm = new Forms();
		$this->view->aHostels = $aHostels = $oHms->listHostels($this->getRequest()->getParams(), 1);
		$this->view->iHostelID = $iHostelID = $this->getRequest()->getParam('hostel_id', 0);
		$aHostel = $oHms->getHostel($iHostelID);
		$aFormParams = array('width' => '80%', 'button' => 'Save');
		$this->view->sForm = $oForm->generateForm('Add/Edit Hostel', 'hms', $aHostel, $aFormParams);
	}

	public function saveHostelAction()
	{
		$oHms = new Hms();
		$result = $oHms->saveHostel($this->getRequest()->getParams());
		$sError = '';
		if(!is_numeric($result))
			$sError = '/error/'.$result;
		$this->_redirect('hms/hostel-list'.$sError);
	}

	public function activateHostelAction()
	{
		$oHms = new Hms();
		$oHms->activateHostel($this->getRequest()->getParam('hostel_id', 0), $this->getRequest()->getParam('command', ''));
		$this->_redirect('hms/hostel-list');
	}
	public function deleteHostelAction()
	{
		$oHms = new Hms();
		$oHms->deleteHostel($this->getRequest()->getParam('id', 0));
		$this->_redirect('hms/hostel-list');
	}

	public function hostelViewAction()
	{
		$oHms = new Hms();
	}
	public function hostelAdminAction()
	{
		$oHms = new Hms();
		$this->view->iHostelID = $iHostelID = $this->getRequest()->getParam('hostel_id', 0);
		$this->view->aHostelRooms = $oHms->listHostelRooms($iHostelID);
		$this->view->aHostel = $oHms->getHostel($iHostelID);
	}


	public function updateHostelRoomsAction()
	{
		$oHms = new Hms();
		$oHms->createHostelRooms($this->getRequest()->getParams());
		$this->_redirect('/hms/hostel-admin/hostel_id/'.$this->getRequest()->getParam('hostel_id', 0));
	}

	public function addEditGuestAction()
	{
		$oForm = new Forms();
		$aGuest = array();
		$aFormParams = array('width' => '70%');
		$this->view->sForm = $oForm->generateForm('Add/Edit Guest', 'hms', $aGuest, $aFormParams);
	}

	public function saveContactBookingAction()
	{
		$oContactManager = new  ContactManager();
		$iContactID = $oContactManager->saveContact($this->getRequest()->getParams());
		if ($iContactID > 0)
			$this->_redirect('/hms/contact-profile/contact_id/'.$iContactID);
		else
			$this->_redirect('/hms/add-edit-guest/error/1');
	}
	public function contactProfileAction()
	{
		$oContactManager = new ContactManager();
		$this->view->aContact = $oContactManager->getContact($this->getRequest()->getParam('contact_id', 0));
		$this->view->aContacts = $oContactManager->listContacts($this->getRequest()->getParams());
	}

	public function allocationsAction()
	{

	}

	public function hostelsViewAction()
	{
		$oHms = new Hms();
		$this->view->iHostelID = $iHostelID = $this->getRequest()->getParam('hostel_id', 1);
		$aHostelRooms = array();
		$aAllHostelRooms = $oHms->listHostelRooms(0, 1);
		foreach ($aAllHostelRooms as $aRoom)
		{
			$aHostelRooms[$aRoom['hostel_id']][] = $aRoom;
		}
		$this->view->aHostelRooms = $aHostelRooms;
		$this->view->aHostel = $oHms->getHostel($iHostelID);
		$this->view->aHostels = $aHostels = $oHms->listHostels($this->getRequest()->getParams());
	}

}
