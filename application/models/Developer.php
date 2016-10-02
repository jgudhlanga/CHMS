<?php

/**
 * Created by PhpStorm.
 * User: jimlink
 * Date: 8/17/2015
 * Time: 8:23 PM
 */
class Developer extends DbHook
{
	/*
	 * This function will save modules and pages, if its a module it creates a physical Model file,
	 * Physical Controller File  with an index action and an physical view file
	 * if its a module  page it creates a page in the view and an action in the Controller Class
	 * @param array $aData
	 * @return numeric module id or an error string
	 */
	public function saveModuleItems($aData = array())
	{
		$db = $this->db();
		$config = Zend_Registry::get('config');
		/*Upload the module or page icon and return the upload name to save in the  database */
		$aUpload = (isset($_FILES['image']) && !empty($_FILES['image'])) ? $_FILES['image'] : array();
		$sImageSave = '';
		if (isset($aUpload['tmp_name']) && $aUpload['tmp_name'] != '')
		{
			$sImagePath = $config->imgPath;
			foreach (listThemes() as $sThemePath => $sDescription)
			{
				if (!file_exists("$sImagePath/$sThemePath/") && !is_dir("$sImagePath/$sThemePath/"))
				{
					mkdir("$sImagePath/$sThemePath/", 0775, TRUE);
				}
				$sImageSave = "$aData[path]_".$aUpload['name'];
				move_uploaded_file($aUpload['tmp_name'], "$sImagePath/$sThemePath/" . $sImageSave);
			}
		}
		/* Save or update the module or page in to the database */
		$aTableColumns = $db->describeTable(SYSTEM_MODULES);
		$aDataInsert = array();
		foreach ($aTableColumns as $sKey => $aSchema)
		{
			if ($sKey == 'id' || $sKey == 'is_active' || $sKey == 'version' || $sKey == 'installed') continue;
			if($sKey == 'module_id')
				$aDataInsert[$sKey] = (isset($aData[$sKey]) && $aData[$sKey] > 0)? $aData[$sKey] : 0;
			else
				$aDataInsert[$sKey] = (isset($aData[$sKey]) && $aData[$sKey] != '') ? $aData[$sKey] : new Zend_Db_Expr('NULL');
		}
		$aDataInsert['image'] = $sImageSave;
		if (isset($aData['id']) && $aData['id'] > 0)
		{
			/* do a module or page update */
			$sql = $db->select()
				->from(SYSTEM_MODULES, array('image'))
				->where('id = ?', $aData['id']);
			$sImage = $db->fetchOne($sql);
			$aDataInsert['image'] = ($sImageSave == '') ? $sImage : $sImageSave;
			$db->update(SYSTEM_MODULES, $aDataInsert, 'id=' . $aData['id']);
			$iId = $aData['id'];
			//delete the physical old icon
			if (file_exists("$sImagePath/menu_icons/classic/" . $sImage))
			{
				unlink("$sImagePath/menu_icons/classic/" . $sImage);
			}
		}
		else
		{
			/* save a new module or page */
			$sql = $db->select()
				->from(SYSTEM_MODULES, array('id'))
				->where('lower(name) LIKE ? ', strtolower($aData['name']));
			if ((isset($aData['module_id']) && $aData['module_id'] > 0))
				$sql->where('module_id=?', $aData['module_id']);
			else
				$sql->where('module_id=0 or module_id is NULL');
			$iExistID = $db->fetchOne($sql);
			if ($iExistID == 0)
			{
				if ($aDataInsert['module_id'] == 0)
				{
					/*create folder structure if not exists*/
					$this->createModuleStructure($aDataInsert['path']);
				}
				else
				{
					/*create view structure if not exists*/
					$this->createModuleStructure($aDataInsert['path'], $aDataInsert['link']);
				}
				$aDataInsert['is_active'] = 1;
				$db->insert(SYSTEM_MODULES, $aDataInsert);
				$iId = $db->lastInsertId();

				/*create a new row for the index Action in the Database*/
				if ($aDataInsert['module_id'] == 0 && $iId > 0)
				{
					$aIndex = array('module_id' => $iId, 'title' => 'Search', 'name' => 'index',
									'path' => $aDataInsert['path'], 'image' => 'search.png', 'link' => 'index', 'position' => 1,
					);
					$db->insert(SYSTEM_MODULES, $aIndex);
				}
			}
			else
				$iId = 'The item name already exist';
		}
		return $iId;
	}
	
	public function installAsStandAlone($iModuleID=0)
	{
		if(!is_numeric($iModuleID) || $iModuleID == 0)
			return FALSE;
		$db = $this->db();
		$aModule = $this->getModule($iModuleID);
		$iStandAlone = ($aModule['install_as_stand_alone'] == 1) ? new Zend_Db_Expr('NULL') :  1;
		$db->update(SYSTEM_MODULES, array('install_as_stand_alone'=> new Zend_Db_Expr('NULL')));
		$db->update(SYSTEM_MODULES, array('install_as_stand_alone'=> $iStandAlone), "id=$iModuleID");
		if($iStandAlone == NULL)
			$sAction = 'Installed';
		else
			$sAction = 'UnInstalled';
		Session::setAlertMessage('success', "The module ($aModule[title]) has been successfully $sAction as a stand alone");
	}

	/*
	 * save the module installations, i.e the user will only see installed modules on the interface
	 * @param array $aPost, this is an array of selected modules to be installed
	 */
	public function updateInstallations($aPost = array())
	{
		$db = $this->db();
		foreach ($aPost['mod_install'] as $iID => $iValue)
		{
			$db->update(SYSTEM_MODULES, array('installed' => $iValue), "id=$iID");
		}
	}

	/*
	 * This function will activate or deactivate modules or pages
	 * @param int $iID The id of the module to be activated or deactivated
	 * @param     $sCommand its either activate or deactivate
	 */
	public function activateModule($iID=0, $sCommand)
	{
		$db = $this->db();
		$iActive = (strtolower($sCommand) == 'activate') ? 1 : 0;
		$db->update(SYSTEM_MODULES, array('is_active' => $iActive), "id=$iID");
	}
	public function updateModuleVersion($aPost=array())
	{
		$db = $this->db();
		$iID = (isset($aPost['module_id']) && $aPost['module_id'] > 0) ? $aPost['module_id'] : 0;
		$iVersion = (isset($aPost['version']) && $aPost['version'] > 0) ? $aPost['version'] : new Zend_Db_Expr('NULL');
		$db->update(SYSTEM_MODULES, array('version' => $iVersion), "id=$iID");
	}
	public function deleteModule($iId = 0)
	{
		$db = $this->db();
		$aModule = $this->getModule($iId);
		#TODO don't delete developer parent module Developer
		$sError = '';
		if ($aModule['name'] == 'developer' && $aModule['module_id'] == 0)
			$sError = 'The is the pillar module of the system. It can not be deleted';
		if ($sError == '')
		{
			#TODO if the module is a parent delete all and if its not delete only the file
			$iModuleId = (isset($aModule['module_id']) && $aModule['module_id'] > 0) ? $aModule['module_id'] : 0;
			if ($iModuleId == 0)
			{
				//delete the physical controller,
				unlink(APPLICATION_PATH . "/controllers/" . ucfirst($aModule['path']) . "Controller.php");

				//delete the physical model
				unlink(APPLICATION_PATH . "/models/" . ucfirst($aModule['path']) . ".php");
				//delete all the physical views
				unlink(APPLICATION_PATH . "/views/scripts/" . $aModule['path']);

				$db->delete(SYSTEM_MODULES, 'module_id=' . $iId);
			}
			else
			{
				unlink(APPLICATION_PATH . "/views/" . "$aModule[path]/$aModule[link].phtml");
			}
			$db->delete(SYSTEM_MODULES, 'id=' . $iId);
		}
		return $sError;
	}
	public function getModule($iID = 0)
	{
		$db = $this->db();
		$sql = $db->select()
			->from(SYSTEM_MODULES)
			->where('id=?', $iID);
		return $db->fetchRow($sql);
	}
	/*
	 * @param null $iId if isset fetch the item direct
	 * @param null $iActive get the parent modules irrespective of state and if its not null fetch only active modules
	 * @param null $iParentID get the module children
	 *
	 * @return array|mixed
	 */
	public function listModules($iParentID = 0, $bActive = 0, $bAdmin = 0, $bInstalled = 0)
	{
		$db = $this->db();
		$aReturn=array();
		if ($iParentID == 0)
		{

			$sql = $db->select()
				->from(SYSTEM_MODULES)
				->where('module_id = ?', 0)
				->order(array('position ASC'));
			if ($bActive > 0)
				$sql->where('is_active=?', 1);
			if ($bInstalled > 0)
				$sql->where('installed=?', 1);
			//if ($bAdmin > 0)
				//$sql->where("id=$iParentID OR module_id=$iParentID");
			//else
				//$sql->where('module_id=?', $iParentID);
			$aParents = $db->fetchAll($sql);
			foreach ($aParents as $aRow)
			{
				//get the pages
				$sql = $db->select()
					->from(SYSTEM_MODULES)
					->where('module_id=?', $aRow['id']);
				if ($bActive > 0)
					$sql->where('is_active=?', 1);
				if ($bInstalled > 0)

					$sql->where('installed=?', 1);
				$sql->order(array('position ASC'));
				$pages = $db->fetchAll($sql);

				//get the forms
				$sql = $db->select()
					->from(DEV_FORMS)
					->where('module_id=?', $aRow['id'])
					->order(array('id DESC'));
				$forms = $db->fetchAll($sql);

				$aReturn[] = array('mod' => $aRow, 'pages' => $pages, 'forms' => $forms);
			}
		}
		else if ($iParentID > 0)
		{
			$sql = $db->select()
				->from(SYSTEM_MODULES);
			if ($bActive > 0)
				$sql->where('is_active=?', 1);
			if ($bInstalled > 0)
				$sql->where('installed=?', 1);
			if ($bAdmin > 0)
				$sql->where("id=$iParentID OR module_id=$iParentID");
			else
				$sql->where('module_id=?', $iParentID);
			$sql->order(array('position ASC'));
			$aReturn = $db->fetchAll($sql);
		}
		return $aReturn;
	}
	public function orderPositions($aPost = array())
	{
		$db = $this->db();

		$aRow = $this->getModule($aPost['id']);
		$iCurrentPosition = $aRow['position'];
		$sql = $db->select()
			->from(SYSTEM_MODULES);
		if ($aPost['direction'] == 'up')
			$sql->where('position < ?', $aRow['position']);
		else
			$sql->where('position > ?', $aRow['position']);
		if ($aRow['module_id'] == '' || $aRow['module_id'] == 0)
			$sql->where('module_id=0 OR module_id is null');
		else
			$sql->where('module_id=?', $aRow['module_id']);
		if ($aPost['direction'] == 'up')
			$sql->order('position DESC');
		else
			$sql->order('position ASC');
		$aImmediateRow = $db->fetchRow($sql);
		$iImmediatePosition = $aImmediateRow['position'];

		//swap positions;
		if ($iImmediatePosition != '')
		{
			$db->update(SYSTEM_MODULES, array('position' => $iImmediatePosition), 'id=' . $aPost['id']);
			$db->update(SYSTEM_MODULES, array('position' => $iCurrentPosition), 'id=' . $aImmediateRow['id']);
		}
		//order them with a perfect sequence
		$sql = $db->select()
			->from(SYSTEM_MODULES)
			->where('1=?', 1);
		if ($aRow['module_id'] == '' || $aRow['module_id'] == 0)
			$sql->where('module_id=0 OR module_id is null');
		else
			$sql->where('module_id=?', $aRow['module_id']);
		$sql->order('position ASC');
		$aModules = $db->fetchAll($sql);
		$i = 0;
		foreach ($aModules as $aRow)
		{
			$i++;
			$db->update(SYSTEM_MODULES, array('position' => $i), 'id=' . $aRow['id']);
		}

	}
	public function saveSystemWorkflow($aPost = array())
	{
		$db = $this->db();
		$aDbTableColumns = array_keys($db->describeTable(SYSTEM_WORKFLOW));
		$aData = array();
		$iWorkflowID = (isset($aPost['workflow_id']) && $aPost['workflow_id'] > 0) ? $aPost['workflow_id'] : 0;
		foreach ($aDbTableColumns as $sKey)
		{
			if ($sKey == 'id' || $sKey == 'is_active') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if ($iWorkflowID > 0)
		{
			//update
			$db->update(SYSTEM_WORKFLOW, $aData, "id=$iWorkflowID");
		}
		else
		{
			//check against duplicate entry of the workflow on the same module
			$sql = $db->select()
				->from(SYSTEM_WORKFLOW, array('id'))
				->where('lower(workflow_name) LIKE ?', strtolower($aPost['workflow_name']))
				->where('module_id = ?', $aPost['module_id']);
			$iExistingID = $db->fetchone($sql);
			//insert
			if ($iExistingID == 0)
			{
				$db->insert(SYSTEM_WORKFLOW, $aData);
				$iWorkflowID = $db->lastInsertId();
			}
		}
		return $iWorkflowID;
	}
	public function activateWorkflow($iWorkflowID=0, $sCommand)
	{
		$db = $this->db();
		$iIsActive = ($sCommand == 'Activate') ? 1 : 0;
		$db->update(SYSTEM_WORKFLOW, array('is_active' => $iIsActive), "id=$iWorkflowID");
	}
	public function deleteWorkflow($iWorkflowID=0)
	{
		$db = $this->db();
		$db->delete(SYSTEM_WORKFLOW, "id=$iWorkflowID");
	}
	public function listSystemWorkflow($aPost = array())
	{
		$db = $this->db();
		$sql = $db->select()
			->from(array('a' => SYSTEM_WORKFLOW))
			->joinLeft(array('b' => SYSTEM_MODULES), 'a.module_id=b.id', array('module_title' => 'b.title'));
		if (isset($aPost['module_id']) && $aPost['module_id'] > 0)
		{
			$sql->where('a.module_id=?', $aPost['module_id']);
		}
		return $db->fetchAll($sql);
	}
	public function getSystemWorkflow($iWorkflowID=0)
	{
		$db = $this->db();
		$sql = $db->select()
			->from(array('a' => SYSTEM_WORKFLOW))
			->joinLeft(array('b' => SYSTEM_MODULES), 'a.module_id=b.id', array('module_title' => 'b.title'))
			->where('a.id=?', $iWorkflowID);
		return $db->fetchrow($sql);
	}
	
	public function saveSystemWorkflowSteps($aPost = array())
	{
		$db = $this->db();

		$aDbTableColumns = array_keys($db->describeTable(WORKFLOW_STEPS));
		$aData = array();
		$sCommand = (isset($aPost['command']) && $aPost['command'] != '') ? $aPost['command'] : '';
		$iStepID = (isset($aPost['step_id']) && $aPost['step_id'] > 0) ? $aPost['step_id'] : 0;
		/* Only save a step for a supplied workflow_id */
		if (isset($aPost['workflow_id']) && $aPost['workflow_id'] > 0)
		{
			foreach ($aDbTableColumns as $sKey)
			{
				if ($sKey == 'id' || $sKey == 'is_active') continue;
				$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
			}
			if ($iStepID > 0)
			{
				//update
				$db->update(WORKFLOW_STEPS, $aData, "id=$iStepID");
			}
			else
			{
				//check against duplicate entry of the workflow on the same module i.e the status must be unique
				$sql = $db->select()
					->from(WORKFLOW_STEPS, array('id'))
					->where("(lower(step_name) LIKE '" . strtolower($aPost['step_name']) . "' OR step_status=" . $aPost['step_status'] . ")")
					->where('workflow_id = ?', $aPost['workflow_id']);
				//echo $sql; die;
				$iExistingID = $db->fetchone($sql);
				//insert
				if ($iExistingID == 0)
				{
					$db->insert(WORKFLOW_STEPS, $aData);
					$iStepID = $db->lastInsertId();
				}
				else
				{
					$iStepID = "The Step Name and Status must be unique";
				}
			}
		}
		else
		{
			$iStepID = "Specify a workflow for this step";
		}
		return $iStepID;
	}
	public function activateWorkflowStep($iStepID=0, $sCommand='')
	{
		$db = $this->db();
		$iIsActive = ($sCommand == 'Activate') ? 1 : 0;
		$db->update(WORKFLOW_STEPS, array('is_active' => $iIsActive), "id=$iStepID");
	}
	public function deleteWorkflowStep($iStepID=0)
	{
		$db = $this->db();
		$db->delete(WORKFLOW_STEPS, "id=$iStepID");
	}
	public function getSystemWorkflowSteps($aPost = array())
	{
		$db = $this->db();
		$aReturn = array();
		if (isset($aPost['workflow_id']) && $aPost['workflow_id'] > 0)
		{
			$sql = $db->select()
				->from(array('a' => WORKFLOW_STEPS))
				->where('a.workflow_id=?', $aPost['workflow_id'])
				->order('a.step_position');
			$aReturn = $db->fetchAll($sql);
		}
		return $aReturn;
	}
	public function getWorkflowStep($iStepID = 0)
	{
		$db = $this->db();
		$aReturn = array();
		if ($iStepID > 0)
		{
			$sql = $db->select()
				->from(array('a' => WORKFLOW_STEPS))
				->where('a.id=?', $iStepID);
			$aReturn = $db->fetchrow($sql);
		}
		return $aReturn;
	}
	public function orderSteps($aPost = array())
	{
		$db = $this->db();
		$iWorkflowID = (isset($aPost['workflow_id']) && $aPost['workflow_id'] > 0) ? $aPost['workflow_id'] : 0;
		$iStepID = (isset($aPost['step_id']) && $aPost['step_id'] > 0) ? $aPost['step_id'] : 0;
		if ($iWorkflowID > 0 && $iStepID > 0)
		{
			$aRow = $this->getWorkflowStep($iStepID);
			$iCurrentPosition = $aRow['step_position'];
			$sql = $db->select()
				->from(WORKFLOW_STEPS)
				->where('workflow_id=?', $iWorkflowID);
			if ($aPost['direction'] == 'up')
			{
				$sql->where('step_position < ?', $aRow['step_position']);
				$sql->order('step_position DESC');
			}
			else
			{
				$sql->where('step_position > ?', $aRow['step_position']);
				$sql->order('step_position DESC');

			}

			$aImmediateRow = $db->fetchRow($sql);
			$iImmediatePosition = $aImmediateRow['step_position'];
			//swap positions;
			if ($iImmediatePosition != '')
			{
				$db->update(WORKFLOW_STEPS, array('step_position' => $iImmediatePosition), 'id=' . $aPost['step_id']);
				$db->update(WORKFLOW_STEPS, array('step_position' => $iCurrentPosition), 'id=' . $aImmediateRow['id']);
			}
			//order them with a perfect sequence
			$sql = $db->select()
				->from(WORKFLOW_STEPS)
				->where('workflow_id=?', $iWorkflowID)
				->order('step_position ASC');
			$aSteps = $db->fetchAll($sql);
			$i = 0;
			foreach ($aSteps as $aRow)
			{
				$i++;
				$db->update(WORKFLOW_STEPS, array('step_position' => $i), 'id=' . $aRow['id']);
			}
		}
	}
	public function createModuleStructure($sPath = '', $sPage = '')
	{
		if ($sPath == '') return false;
		$config = Zend_Registry::get('config');
		$sViewScriptsPath = $config->viewPath;
		$sModelScriptsPath = $config->modelPath;
		$sControllerScriptsPath = $config->controllerPath;
		if ($sPage == '')
		{
			#CREATE MODEL HERE
			$sModelString = modelString($sPath);
			if (!is_file($sModelScriptsPath . '/' . ucfirst($sPath) . '.php'))
			{
				$sModel = fopen($sModelScriptsPath . '/' . ucfirst($sPath) . '.php', 'w');
				fwrite($sModel, $sModelString);
				fclose($sModel);
			}

			#CREATE CONTROLLER HERE
			$sControllerString = controllerString($sPath);
			if (!is_file($sControllerScriptsPath . '/' . ucfirst($sPath) . 'Controller.php'))
			{
				$sController = fopen($sControllerScriptsPath . '/' . ucfirst($sPath) . 'Controller.php', 'w');
				fwrite($sController, $sControllerString);
				fclose($sController);
			}
			if (!file_exists($sViewScriptsPath . '/' . $sPath . '/') && !is_dir($sViewScriptsPath . '/' . $sPath . '/'))
			{
				mkdir($sViewScriptsPath . '/' . $sPath . '/', 0775, TRUE);
				$sFile = fopen($sViewScriptsPath . '/' . $sPath . '/' . 'index.phtml', 'w');
			}
		}
		else
		{
			if (!is_file($sViewScriptsPath . '/' . $sPath . '/' . $sPage . '.phtml'))
				$sFile = fopen($sViewScriptsPath . '/' . $sPath . '/' . $sPage . '.phtml', 'w');
		}
	}
}