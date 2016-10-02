<?php
/**
 * Author: jimlink
 * Date: 6/5/2016
 * Time: 7:59 PM
 * Copyright (c) JJDEV
 * @version 1.0 (excellence)
 */
class Hms
{
	public function saveHostelConfig($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aDbTblColumns = $db->describeTable(HMS_SETTINGS);
		$aData = array();
		foreach ($aDbTblColumns as $sKey => $aSchema)
		{
			if ($sKey == 'id') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData))
		{
			//check for an existing record
			$sql = $db->select()
				->from(HMS_SETTINGS, array('id'))
				->where('id > ?', 0)
				->order('id ASC');
			$aRow = $db->fetchRow($sql);
			$iExistingID = (isset($aRow['id']) && $aRow['id'] > 0) ? $aRow['id'] : 0;
			if ($iExistingID == 0 || $iExistingID == '')
				$db->insert(HMS_SETTINGS, $aData);
			else
				$db->update(HMS_SETTINGS, $aData, 'id=' . $iExistingID);
		}
	}

	public function getHmsConfig()
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()->from(HMS_SETTINGS)->where('id > ?', 0)->order('id ASC');
		return $db->fetchRow($sql);
	}

	public function saveHostel($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aDbTblColumns = array_keys($db->describeTable(HOSTELS));
		$aData = array();
		$iHostelID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		foreach ($aDbTblColumns as $sKey )
		{
			if ($sKey == 'id' || $sKey == 'is_active') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData))
		{
			if ($iHostelID > 0)
			{
				$db->update(HOSTELS, $aData, 'id=' . $iHostelID);
			}
			else
			{
				//check for an existing record
				$sql = $db->select()
						->from(HOSTELS, array('id'))
						->where('lower(hostel_name) LIKE ?', $aPost['hostel_name'])
						->order('id ASC');
				$aRow = $db->fetchRow($sql);
				$iExistingID = (isset($aRow['id']) && $aRow['id'] > 0) ? $aRow['id'] : 0;
				if ($iExistingID == 0 || $iExistingID == '')
				{
					$db->insert(HOSTELS, $aData);
					$iHostelID = $db->lastInsertId();
				}
				else
					$iHostelID = 'This hostel Name already exist';
			}
		}
		return $iHostelID;
	}

	public function  listHostels($aPost=array(), $bAdmin=0)
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
					->from(array('a' => HOSTELS))
					->joinLeft(array('b' => USERS), 'a.warden_id=b.id', array('warden_firstname' => 'b.firstname', 'warden_surname' => 'b.surname'));
		if ($bAdmin == 0)
			$sql->where('a.is_active=?', 1);
		$sql->order('a.hostel_name');
		return $db->fetchAll($sql);
	}

	public function  getHostel($iID=0)
	{
		$aReturn= array();
		if ($iID > 0)
		{
			$db = Zend_Registry::get('db');
			$sql = $db->select()->from(array('a' => HOSTELS))
				->joinLeft(array('b' => USERS), 'a.warden_id=b.id', array('warden_firstname' => 'b.firstname', 'warden_surname' => 'b.surname'))
				->where('a.id=?', $iID);
			$aReturn =  $db->fetchRow($sql);
		}
		return $aReturn;
	}

	public function  deleteHostel($iID=0)
	{
		if ($iID > 0)
		{
			$db = Zend_Registry::get('db');
			$db->delete(HOSTELS, 'id='.$iID);
		}
	}

	public function activateHostel($iID=0, $sCommand)
	{
		$db = Zend_Registry::get('db');
		$iActive = (strtolower($sCommand) == 'activate') ? 1 : 0;
		$db->update(HOSTELS, array('is_active' => $iActive), "id=$iID");
	}

	public function listHostelRooms($iHostelID = 0, $bAll=FALSE)
	{
		$db = Zend_Registry::get('db');
		if (($iHostelID == 0 || !is_numeric($iHostelID)) && $bAll==FALSE) return false;

		$sql = $db->select()->from(array('a'=>ROOMS));
		if ($bAll == TRUE)
			$sql->where('1=?', 1);
		else
			$sql->where('a.hostel_id=?',$iHostelID);
		return  $db->fetchAll($sql);
	}

	public function getHostelRoom($iID = 0)
	{
		$db = Zend_Registry::get('db');
		if ($iID == 0 || !is_numeric($iID)) return false;

		$sql = $db->select()->from(array('a'=>ROOMS))->where('a.id=?',$iID);
		return  $db->fetchRow($sql);
	}

	public function createHostelRooms($aPost = array())
	{
		$db = Zend_Registry::get('db');
		if (!isset($aPost['hostel_id']) || !is_numeric($aPost['hostel_id']) || $aPost['hostel_id'] ==0) return false;
		$aHostel = $this->getHostel($aPost['hostel_id']);
		$iHostelCapacity = (isset($aPost['capacity']) && $aPost['capacity'] > 0) ? $aPost['capacity'] : 1;
		$sHostelCode = (isset($aHostel['short_name']) && $aHostel['short_name']!= '') ? $aHostel['short_name'] : '';
		$this->saveHostelFloor($aPost);
		for($i=1; $i <= $iHostelCapacity; $i++)
		{
			$sCode = $sHostelCode.'-'.$aPost['hostel_floor'].'-'.$i;
			$sql = $db->select()->from(ROOMS, array('id'))
					->where('lower(room_code) LIKE ?', strtolower($sCode))
					->where('hostel_id=?', $aPost['hostel_id'])
					->where('hostel_floor=?', $aPost['hostel_floor'])
					->order('id DESC');
			$iID = $db->fetchOne($sql);
			if ($iID == 0 || $iID == '')
			{
				$aData = array('room_code'=>$sCode, 'hostel_id'=>$aPost['hostel_id'], 'hostel_floor'=>$aPost['hostel_floor'] );
				$db->insert(ROOMS, $aData);
			}
		}
	}

	public function saveHostelFloor($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aDbTblColumns = array_keys($db->describeTable(FLOORS));
		$aData = array();
		foreach ($aDbTblColumns as $sKey )
		{
			if ($sKey == 'id' || $sKey == 'is_active') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData) && $aData['hostel_floor'] != '')
		{
			//check for an existing record
			$sql = $db->select()
				->from(FLOORS, array('id'))
				->where('lower(hostel_floor) LIKE ?', $aData['hostel_floor'])
				->where('hostel_id =?', $aPost['hostel_id'])
				->order('id ASC');
			$iExistingID = $db->fetchOne($sql);
			if ($iExistingID == 0 || $iExistingID == '')
				$db->insert(FLOORS, $aData);
			else
				$db->update(FLOORS, $aData, 'id='.$iExistingID);
		}
	}

	public static function getFloorRoom($aPost=array())
	{
		if (!isset($aPost['hostel_id']) || !is_numeric($aPost['hostel_id']) || $aPost['hostel_id'] ==0) return false;
		$floor = (isset($aPost['hostel_floor']) && $aPost['hostel_floor'] != '') ? $aPost['hostel_floor'] : 0;
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(FLOORS)
			->where('lower(hostel_floor) LIKE ?', $floor)
			->where('hostel_id =?', $aPost['hostel_id'])
			->order('id ASC');
		return $db->fetchRow($sql);
	}
}