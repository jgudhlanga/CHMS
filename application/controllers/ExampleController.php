<?php

/**
 * Created by JJ-DEVELOPERS
 * User: James Gudhlanga
 * Date: 14/03/2016 07:36:55
 */
class ExampleController extends Zend_Controller_Action
{
	public function init()
	{
		//Zend_Dojo::enableView($this->view);
		$db = Zend_Registry::get('db');
		$this->view->url = Zend_Registry::get('config')->baseUrl;
		$this->view->action = $this->getRequest()->getActionName();
		header('Content-Type: text/html; charset=ISO-8859-1');
		Bvb_Grid_Deploy_Ofc::$url = Zend_Registry::get('config')->baseUrl;

		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('jquery', 'json')
			->initContext();
	}

	public function indexAction()
	{

	}

	public function jqueryAction()
	{
		$db = Zend_Registry::get('db');
		$oPaginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($db->select()->from(SYSTEM_MODULES)->where('module_id=?', 0)->where('id<?', 20)));
		$oPaginator->setItemCountPerPage(4)
			->setCurrentPageNumber(1)
			->setPageRange(4);
		$aModules = array();
		foreach ($oPaginator as $aModule)
		{
			$aModules[] = $aModule;
		}
		$this->view->aModules = $aModules;
		if (!$this->_request->isXmlHttpRequest())
		{
			$this->view->oPaginator = $oPaginator;
		}

		$oForm = new Forms();
		// $this->view->sForm = $oForm->generateForm('Examples Form Filters', 'example', array(), '98%', 2);
	}

	public function ajaxAction()
	{
		$aPost = $this->getRequest()->getParams();
		if(isset($aPost['command']) && $aPost['command'] != '')
		{
			$sReturn = '';
			foreach($aPost as $sKey => $sValue)
				$sReturn .= "$sKey => ".$sValue;
			echo $sReturn;
		}
	}

	public function bvbAction()
	{
		$db = Zend_Registry::get("db");
		$grid = $this->grid();

		$select = $db->select()->from(SYSTEM_MODULES, array('title', 'name', 'path', 'link', 'image', 'module_id', 'id'))->order('title ASC');
		$grid->query($select);

		$grid->setUseKeyEventsOnFilters(true);
		//$grid->listenEvent('grid.init_deploy', 'AddAutoCompleteToFields');

		$this->view->grid = $grid->deploy();

	}

	/**
	 * Simplify the datagrid creation process
	 * @return Bvb_Grid_Deploy_Table
	 */
	public function grid($id = '')
	{
		$view = new Zend_View();
		$view->setEncoding('ISO-8859-1');
		$config = new Zend_Config_Ini(BASE_PATH . '/application/configs/grid.ini', 'production');
		$grid = Bvb_Grid::factory('Table', $config, $id);
		$grid->setEscapeOutput(false);
		$grid->setExport(array('xml', 'json', 'csv', 'excel', 'word', 'pdf', 'print'));
		$grid->setView($view);
		return $grid;
	}

	public function sbAdminAction()
	{

	}

	public function myCalendarAction()
	{

	}

	public function bootstrapGridAction()
	{

	}
	public function tablesResponsiveAction()
	{

	}

	public function navBarAction()
	{

	}
	public function angularJsAction()
	{

	}
	public function javascriptAction()
	{

	}
	public function phpAction()
	{

	}
	public function html5Action()
	{

	}
}
