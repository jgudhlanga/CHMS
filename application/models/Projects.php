<?php
/**
 * Author: jimlink
 * Date: 7/24/2016
 * Time: 12:08 AM
 * Copyright (c) JJDEV
 * @version 1.0 (excellence)
 */
class Projects
{
	public function saveProject($aPost=array())
	{
		$db = Zend_Registry::get('db');
		$aDbCols = array_keys($db->describeTable(PROJECTS));
		$iProjectID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aDataInsert =array();
		foreach($aDbCols as $sCol)
		{
			if ($sCol == 'id' || $sCol == 'is_active' || $sCol == 'created_by' || $sCol == 'date_created') continue;
			if($sCol == 'date_started' || $sCol == 'end_date')
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? strtotime($aPost[$sCol]) : new Zend_Db_Expr('NULL');
			else
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? $aPost[$sCol] : new Zend_Db_Expr('NULL');
		}
		$nameSpaceUser = new Zend_Session_Namespace('user');
		$user = $nameSpaceUser->loggedUser;
		$iUserID = (isset($user['id']) && $user['id'] > 0) ? $user['id'] : 0;
		if($iProjectID > 0)
		{
			$db->update(PROJECTS, $aDataInsert, "id=$iProjectID");
		}
		else
		{
			$aDataInsert['created_by'] = $iUserID;
			$aDataInsert['date_created'] = time();
			$db->insert(PROJECTS, $aDataInsert);
			$iProjectID = $db->lastInsertId();
		}
		return $iProjectID;
	}

	public function getProject($iID=0)
	{
		if(!is_numeric($iID) || $iID == 0) return NULL;
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(array('a'=>PROJECTS))
			->joinLeft(array('b'=>USERS), 'a.pm_id=b.id', array('b.firstname', 'b.surname'))
			->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}
	public function listProjects($aPost=array(), $bAdmin=0)
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(array('a'=>PROJECTS))
			->joinLeft(array('b'=>USERS), 'a.pm_id=b.id', array('b.firstname', 'b.surname'));
		if($bAdmin ==0)
			$sql->where('a.is_active=?',1);
		return $db->fetchAll($sql);
	}
	public function activateProject($iID=0, $sCommand)
	{
		if(!is_numeric($iID) || $iID == 0) return FALSE;
		$db = Zend_Registry::get('db');
		$iActive = (strtolower($sCommand) == 'activate') ? 1: 0;
		$db->update(PROJECTS, array('is_active'=>$iActive), 'id='.$iID);
	}

	public function deleteProject($iID=0)
	{
		if(!is_numeric($iID) || $iID == 0) return FALSE;
		$db = Zend_Registry::get('db');
		$db->delete(PROJECTS, 'id='.$iID);
	}
}