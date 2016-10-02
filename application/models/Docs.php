<?php
/**
 * Created by JJ-DEVELOPERS
 * User: James Gudhlanga
 * Date: 05/11/2015 08:23:21
 */
class Docs
{
	public function saveConfig($aPost=array())
	{
		$db = Zend_Registry::get('db');
		$aTblColumns = array_keys($db->describeTable(DOC_SETTINGS));
		$aData = array();
		foreach ($aTblColumns as $sColumn)
		{
			if (strtolower($sColumn) == 'id') continue;
			$aData[$sColumn] = (isset($aPost[$sColumn]) && $aPost[$sColumn] != '') ? $aPost[$sColumn] : new Zend_Db_Expr('NULL');
		}
		$sql = $db->select()
				->from(DOC_SETTINGS, array('id', 'folder_icon'))
				->where('id>?', 0)
				->order('id');
		$aFound = $db->fetchRow($sql);
		$iID = isset($aFound['id']) ? $aFound['id'] : '';
		$sFolderIcon = isset($aFound['folder_icon']) ? $aFound['folder_icon'] : '';
		//upload the folder icon
		$aFolderIcon = $_FILES['folder_icon'];
		if (isset($aFolderIcon['tmp_name']) && $aFolderIcon['tmp_name'] != '')
		{
			$sFolderIcon = time().'_'.$aFolderIcon['name'];
			$config = Zend_Registry::get('config');
			$sUploadPath = $config->imgPath.'/menu_icons/classic/';
			if (!file_exists("$sUploadPath") && !is_dir("$sUploadPath"))
			{
				mkdir("$sUploadPath", 0775, TRUE);
			}
			//delete the physical old icon
			if (file_exists($sUploadPath.$sFolderIcon))
			{
				unlink($sUploadPath.$sFolderIcon);
			}
			move_uploaded_file($aFolderIcon['tmp_name'], "$sUploadPath" . $sFolderIcon);
		}
		$aData['folder_icon'] = $sFolderIcon;
		if ($iID == 0)
		{
			$db->insert(DOC_SETTINGS, $aData);
		}
		else
		{
			$db->update(DOC_SETTINGS, $aData, 'id='.$iID);
		}
	}

	public function getSettings()
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(DOC_SETTINGS)
			->where('id>?', 0)
			->order('id');
		$aSettings = $db->fetchRow($sql);
		return $aSettings;
	}

	public function getDocument($iID = 0)
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
				->from(array('a' => DOCUMENTS))
				->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}
	public function listDocuments($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
				->from(array('a' => DOCUMENTS))
				->where('is_deleted=0 or  is_deleted is NULL');
		if (isset($aPost['folder_id']) && $aPost['folder_id'] > 0)
		{
			$sql->where('a.folder_id=?', $aPost['folder_id']);
		}
		return $db->fetchAll($sql);
	}

	public function listPrimaryFolders()
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
				->from(array('a' => DOCUMENTS))
				->where('file_type=?', 0)
				->where('folder_id=0 or folder_id is NULL')
				->where('is_deleted=0 or is_deleted is NULL');
		return $db->fetchAll($sql);
	}

	public static function getIcon($sType = 'folder')
	{
		$sIcon = '';
		if ($sType == 'folder')
		{
			$db = Zend_Registry::get('db');
			$sql = $db->select()
				->from(DOC_SETTINGS)
				->where('id>?', 0)
				->order('id');
			$aConfig = $db->fetchRow($sql);
			if (isset($aConfig['folder_icon']) && $aConfig['folder_icon'] != '')
				$sIcon =  loadImage('menu_icons', 'classic', $aConfig['folder_icon'], $aConfig['icon_width'], $aConfig['icon_height']);
		}
		return $sIcon;
	}

	/*
	 * @param int $iID file id
	 * @param int $iType either 0 => folders, 1 => file
	 */
	public static function getSummary($iID = 0, $iType=0)
	{
		if ($iID == 0) return false;
		$oDocs = new Docs();
		$aSummary = array('folders'=> 0, 'files' => 0);
		if ($iType == 0)
		{
			$aPost['folder_id'] =$iID;
			$aDocuments = $oDocs->listDocuments($aPost);
			if (!empty($aDocuments))
			{
				foreach ($aDocuments as $aDoc)
				{
					if ($aDoc['file_type'] == 0)
						$aSummary['folders']++;
					if ($aDoc['file_type'] == 1)
						$aSummary['files']++;
				}
			}
		}

		return $aSummary;
	}

	public function saveFolder($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$iFolderID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aData = array();
		$nameSpace = new Zend_Session_Namespace('user');
		$user = (isset($nameSpace->loggedUser) && !empty($nameSpace->loggedUser)) ? $nameSpace->loggedUser : array();

		$aDbColumns = array_keys($db->describeTable(DOCUMENTS));
		foreach ($aDbColumns as $sColumn)
		{
			if ($sColumn == 'id' || $sColumn == 'is_active' || $sColumn == 'file_type') continue;
			$aData[$sColumn] = (isset($aPost[$sColumn]) && $aPost[$sColumn] != '') ? $aPost[$sColumn] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData))
		{
			if ($iFolderID > 0)
			{
				$aData['last_update'] = time();
				$aData['last_updated_by_user_id'] = $user['id'];
				$db->update(DOCUMENTS, $aData, "id=$iFolderID");
			}
			else
			{
				$aData['date_created'] = time();
				$aData['created_by_user_id'] = $user['id'];
				$aData['file_type'] = 0;
				$db->insert(DOCUMENTS, $aData);
			}
		}
	}
}
