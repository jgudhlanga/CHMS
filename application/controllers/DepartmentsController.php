<?php
/**
 * Author: jimlink
 * Date: 7/24/2016
 * Time: 12:06 AM
 * Copyright (c) JJDEV
 * @version 1.0 (excellence)
 */
require_once APPLICATION_PATH . "/models/Departments.php";

class DepartmentsController extends Zend_Controller_Action
{
	public function indexAction()
	{
	}

	public function saveDepartmentAction()
	{
		$oDepartment = new Departments();
		$iDepartmentID = $oDepartment->saveDepartment($this->getRequest()->getParams());
		$this->_redirect('/departments/index');
	}
}
