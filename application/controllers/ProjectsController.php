<?php
/**
 * Author: jimlink
 * Date: 7/24/2016
 * Time: 12:03 AM
 * Copyright (c) JJDEV
 * @version 1.0 (excellence)
 */
require_once APPLICATION_PATH . "/models/Projects.php";

class ProjectsController extends Zend_Controller_Action
{
	public function indexAction()
	{
	}

	public function saveProjectAction()
	{
		$oProject = new Projects();
		$oProject->saveProject($this->getRequest()->getParams());
		$this->_redirect('/projects/index');
	}

}
