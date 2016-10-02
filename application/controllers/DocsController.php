<?php
/**
 * Created by JJ-DEVELOPERS
 * User: James Gudhlanga
 * Date: 05/11/2015 08:23:21
 */
require_once APPLICATION_PATH.'/models/Docs.php';

class DocsController extends Zend_Controller_Action
{
   public function indexAction()
   {
	   $this->view->sSearchText = $this->getRequest()->getParam('search_text');
   }

   public function recycleBinAction()
   {

   }

   public function libraryAction()
   {
	   $oDocs = new Docs();
	   $this->view->config = Zend_Registry::get('config');
	   $this->view->aCoreFolders = $oDocs->listPrimaryFolders();
   }

   public function setupAction()
   {
      $oDocs = new Docs();
      $aPost = $this->getRequest()->getParams();
	   if (isset($aPost['command']) && $aPost['command'] != '')
	   {
		   $oDocs->saveConfig($aPost);
	   }
       $this->view->config = Zend_Registry::get('config');
	   $this->view->aSettings = $oDocs->getSettings();
   }

	public function requestsAction()
	{

	}
	public function coreFoldersAction()
	{
		$oDocs = new Docs();
		$oForms = new Forms();
		$aPost = $this->getRequest()->getParams();
		/*Save the new folder or delete */
		if (isset($aPost['command']) && $aPost['command'] != '')
		{
			if ($aPost['command'] == 'Save')
				$oDocs->saveFolder($aPost);
		}
		$this->view->config = Zend_Registry::get('config');
		$this->view->aCoreFolders = $oDocs->listPrimaryFolders();
		$aEdit = array();
		$this->view->sForm = $oForms->generateForm('Add/Edit Core Folder', 'developer', $aEdit, '70%');

	}

	public function viewAction()
	{
		$oDocs = new Docs();
		$aPost = $this->getRequest()->getParams();
		$this->view->iFolderID = $iFolderIID = $this->getRequest()->getParam('folder_id', 0);
		$this->view->aFolder = $oDocs->getDocument($iFolderIID);
		$this->view->aDocuments = $oDocs->listDocuments($aPost);
	}
	public function newFileAction()
	{

	}
	public function newFolderAction()
	{

	}
	public function bookmarksAction()
	{

	}
	public function notificationsAction()
	{

	}
}
