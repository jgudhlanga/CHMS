<?php
/**
 * Created by JJ-DEVELOPERS
 * User: James Gudhlanga
 * Date: 29/11/2015 06:20:37
 */
class Chms extends DbHook
{
	public function saveMembersConfig($aPost = array())
	{
		$db = $this->db();
		$aDbTblColumns = array_keys($db->describeTable(CHMS_SETTINGS));
		$aData = array();
		foreach ($aDbTblColumns as $sKey)
		{
			if ($sKey == 'id') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData))
		{
			//check for an existing record
			$sql = $db->select()
				->from(CHMS_SETTINGS, array('id'))
				->where('id > ?', 0)
				->order('id ASC');
			$aRow = $db->fetchRow($sql);
			$iExistingID = (isset($aRow['id']) && $aRow['id'] > 0) ? $aRow['id'] : 0;
			if ($iExistingID == 0 || $iExistingID == '')
				$db->insert(CHMS_SETTINGS, $aData);
			else
				$db->update(CHMS_SETTINGS, $aData, 'id=' . $iExistingID);
		}
	}

	/**
	 * @param $sql
	 * @param $iGroupID
	 */
	private function groupMembers($sql, $iGroupID=0)
	{
		if(!is_numeric($iGroupID) || $iGroupID == 0) return FALSE;
		$sql->join(array('b' => CHMS_GROUP_MEMBERS), "a.id=b.member_id and b.group_id=$iGroupID",
				   array('group_date_joined' => 'b.date_joined', 'member_group_link_id' => 'b.id'));
	}

	/**
	 * @param $sql
	 */
	private function notGroupMembers($sql)
	{
		$db = $this->db();
		$sSubSelect = $db->select()->from(CHMS_GROUP_MEMBERS, array('member_id'));
		$sql->where("a.id NOT IN ($sSubSelect)");
	}

	/**
	 * @param $sql
	 */
	private function notZoneMembers($sql)
	{
		$db = $this->db();
		$sSubSelect = $db->select()->from(CHMS_ZONE_MEMBERS, array('member_id'));
		$sql->where("a.id NOT IN ($sSubSelect)");
	}
	/**
	 * @param $sql
	 */
	private function notMinistryMembers($sql)
	{
		$db = $this->db();
		$sSubSelect = $db->select()->from(CHMS_MINISTRY_MEMBERS, array('member_id'));
		$sql->where("a.id NOT IN ($sSubSelect)");
	}

	/**
	 * @param $sql
	 * @param $iMinistryID
	 */
	private function ministryMembers($sql, $iMinistryID=0)
	{
		if(!is_numeric($iMinistryID) || $iMinistryID ==0) return FALSE;
		$sql->join(array('c' => CHMS_MINISTRY_MEMBERS), "a.id=c.member_id and c.ministry_id=$iMinistryID",
				   array('ministry_date_joined' => 'c.date_joined', 'member_ministry_link_id' => 'c.id'));
	}

	/**
	 * @param $sql
	 * @param $iZoneID
	 */
	private function zoneMembers($sql, $iZoneID=0)
	{
		if(!is_numeric($iZoneID) || $iZoneID ==0) return FALSE;
		$sql->join(array('d' => CHMS_ZONE_MEMBERS), "a.id=d.member_id and d.zone_id=$iZoneID",
				   array('zone_date_joined' => 'd.date_joined', 'member_zone_link_id' => 'd.id'));
	}

	public function  listMembers($aPost=array())
	{
		$db = $this->db();
		$sql = $db->select()
			->from(array('a' => USERS));
		$aDbColumns = array_keys($db->describeTable(USERS));
		if(!empty($aPost) && is_array($aPost))
		{
			foreach ($aPost as $sKey => $sVal)
			{
				if($sKey == 'id' && ($sVal == '' || $sVal == 0)) continue;
				if($sKey == 'group_id')
					$this->groupMembers($sql, $sVal); // Get only members with that group id
				else if($sKey == 'ministry_id')
					$this->ministryMembers($sql, $sVal); // Get only members with that ministry id
				else if($sKey == 'zone_id')
					$this->zoneMembers($sql, $sVal); // // Get only members with that zone id
				else if($sKey == 'search_text')
				{
					$sVal = strtolower($sVal);
					$sql->where("lower(a.firstname) LIKE ? OR lower(a.surname) LIKE ?
					OR lower(a.email_address) LIKE ? OR lower(a.street_address) LIKE ? ", "%$sVal%");
				}
				else if($sKey == 'not-in' && $sVal != '')
				{
					if($sVal == 'group_id')
						$this->notGroupMembers($sql); // Get members not in that group
					if($sVal == 'zone_id')
						$this->notZoneMembers($sql); // Get members not in that zone
					if($sVal == 'ministry_id')
						$this->notMinistryMembers($sql); // Get members not in that ministry
				}
				else if (in_array($sKey, $aDbColumns) && $sVal != '')
					$sql->where("a.$sKey=?", "$sVal");
			}
		}
		$sql->where('a.is_member=?', 1);
		$sql->order(array('a.date_created DESC'));
		return $db->fetchAll($sql);
	}

	public function  getMember($iID=0)
	{
		$db = $this->db();
		$sql = $db->select()->from(array('a' => USERS))->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}

	public function saveCellGroup($aPost=array())
	{
		$db = $this->db();
		$aDbCols = array_keys($db->describeTable(CHMS_GROUPS));
		$iGroupID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aDataInsert =array();
		foreach($aDbCols as $sCol)
		{
			if($sCol == 'id' || $sCol == 'is_active' || $sCol == 'created_by' || $sCol == 'date_created') continue;
			if($sCol == 'date_started')
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? strtotime($aPost[$sCol]) : new Zend_Db_Expr('NULL');
			else
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? $aPost[$sCol] : new Zend_Db_Expr('NULL');
		}
		if($iGroupID > 0)
		{
			$aDataInsert['last_updated_by'] = Session::loggedUserId();
			$aDataInsert['last_updated'] = time();
			$db->update(CHMS_GROUPS, $aDataInsert, "id=$iGroupID");
		}
		else
		{
			$aDataInsert['created_by'] = Session::loggedUserId();
			$aDataInsert['date_created'] = time();
			$db->insert(CHMS_GROUPS, $aDataInsert);
			$iGroupID = $db->lastInsertId();
		}
		return $iGroupID;
	}
	public function getGroup($iID = 0)
	{
		if(!is_numeric($iID) || $iID == 0) return NULL;
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_GROUPS))
			->joinLeft(array('b'=>USERS), 'a.group_leader_id=b.id', array('b.firstname', 'b.surname'))
			->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}

	public function listGroups($aPost=array(), $bAdmin=0)
	{
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_GROUPS))
			->joinLeft(array('b'=>USERS), 'a.group_leader_id=b.id', array('b.firstname', 'b.surname'))
			->joinLeft(array('c'=>CHMS_GROUP_MEMBERS), 'a.id=c.group_id',array('member_count'=> new Zend_Db_Expr('COUNT(c.id)')));
		if($bAdmin == 0)
			$sql->where('a.is_active=?',1);
		$sql->group('a.id');
		return $db->fetchAll($sql);
	}
	public function saveGroupMembers($aPost = array())
	{
		$iGroupID = isset($aPost['group_id']) ? $aPost['group_id'] : 0;
		if(!is_numeric($iGroupID) || $iGroupID == 0)
		{
			//set message here and return false;
			Session::setAlertMessage('danger', 'There is no group selected for this operation');
			return false;
		}
		$iNewMembers = 0;
		$db = $this->db();
		if(isset($aPost['members']))
		{
			foreach($aPost['members'] as $iMemberID => $aDetails)
			{
				if($aDetails['selected'] != 'on') continue;
				$aDataInsert = array(
					'date_joined' => (isset($aDetails['date_joined']) && $aDetails['date_joined'] != '') ? strtotime($aDetails['date_joined']) : time(),
					'member_id'=>$iMemberID, 'group_id'=>$aPost['group_id'], 'date_created'=>time(), 'created_by'=>Session::loggedUserId()
				);
				$db->insert(CHMS_GROUP_MEMBERS, $aDataInsert);
				$iID = $db->lastInsertId();
				if($iID > 0)
					$iNewMembers++;
			}
		}
		if($iNewMembers > 0)
			Session::setAlertMessage('success', $iNewMembers.' new members where added to this group');
		else
			Session::setAlertMessage('warning', $iNewMembers.' members where added to this group');
	}

	public function removeGroupMembers($aPost = array())
	{
		$iRemovedMembers = 0;
		$db = $this->db();
		if(isset($aPost['members']))
		{
			foreach($aPost['members'] as $iMemberID => $sSelected )
			{
				$db->delete(CHMS_GROUP_MEMBERS, "id=".$iMemberID);
				$iRemovedMembers++;
			}
		}
		if($iRemovedMembers > 0)
			Session::setAlertMessage('info', $iRemovedMembers.'  members were removed from this group');
	}
	public function getSettings()
	{
		$db = $this->db();
		$sql = $db->select()->from(CHMS_SETTINGS)->where('id>?', 0)->order(array('id ASC'));
		return $db->fetchRow($sql);
	}

	public function getZone($iID = 0)
	{
		if(!is_numeric($iID) || $iID == 0) return NULL;
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_ZONES))
			->joinLeft(array('b'=>USERS), 'a.zone_leader_id=b.id', array('b.firstname', 'b.surname'))
			->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}
	public function listZones($aPost=array(), $bAdmin=0)
	{
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_ZONES))
			->joinLeft(array('b'=>USERS), 'a.zone_leader_id=b.id', array('b.firstname', 'b.surname'))
			->joinLeft(array('c'=>CHMS_ZONE_MEMBERS), 'a.id=c.zone_id',array('member_count'=> new Zend_Db_Expr('COUNT(c.id)')));
		if($bAdmin == 0)
			$sql->where('a.is_active=?',1);
		$sql->group(array('a.id'));
		return $db->fetchAll($sql);
	}

	public function saveZoneMembers($aPost = array())
	{
		$iZoneID = isset($aPost['zone_id']) ? $aPost['zone_id'] : 0;
		if(!is_numeric($iZoneID) || $iZoneID == 0)
		{
			//set message here and return false;
			Session::setAlertMessage('danger', 'There is no zone selected for this operation');
			return false;
		}
		$iNewMembers = 0;
		$db = $this->db();
		if(isset($aPost['members']))
		{
			foreach($aPost['members'] as $iMemberID => $aDetails)
			{
				if($aDetails['selected'] != 'on') continue;
				$aDataInsert = array(
					'date_joined' => (isset($aDetails['date_joined']) && $aDetails['date_joined'] != '') ? strtotime($aDetails['date_joined']) : time(),
					'member_id'=>$iMemberID, 'zone_id'=>$aPost['zone_id'], 'date_created'=>time(), 'created_by'=>Session::loggedUserId()
				);
				$db->insert(CHMS_ZONE_MEMBERS, $aDataInsert);
				$iID = $db->lastInsertId();
				if($iID > 0)
					$iNewMembers++;
			}
		}
		if($iNewMembers > 0)
			Session::setAlertMessage('success', $iNewMembers.' new members were added to this zone');
		else
			Session::setAlertMessage('warning', $iNewMembers.' members were added to this zone');
	}
	public function removeZoneMembers($aPost = array())
	{
		$iRemovedMembers = 0;
		$db = $this->db();
		if(isset($aPost['members']))
		{
			foreach($aPost['members'] as $iMemberID => $sSelected )
			{
				$db->delete(CHMS_ZONE_MEMBERS, "id=".$iMemberID);
				$iRemovedMembers++;
			}
		}
		if($iRemovedMembers > 0)
			Session::setAlertMessage('info', $iRemovedMembers.'  members were removed from this zone');
	}
	public function getMinistry($iID = 0)
	{
		if(!is_numeric($iID) || $iID == 0) return NULL;
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_MINISTRIES))
			->joinLeft(array('b'=>USERS), 'a.ministry_leader_id=b.id', array('b.firstname', 'b.surname'))
			->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}

	public function listMinistries($aPost=array(), $bAdmin=0)
	{
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_MINISTRIES))
			->joinLeft(array('b'=>USERS), 'a.ministry_leader_id=b.id', array('b.firstname', 'b.surname'))
			->joinLeft(array('c'=>CHMS_MINISTRY_MEMBERS), 'a.id=c.ministry_id',array('member_count'=> new Zend_Db_Expr('COUNT(c.id)')));
		if($bAdmin == 0)
			$sql->where('a.is_active=?',1);
		$sql->group(array('a.id'));
		return $db->fetchAll($sql);
	}

	public function saveMinistryMembers($aPost = array())
	{
		$iMinistryID = isset($aPost['ministry_id']) ? $aPost['ministry_id'] : 0;
		if(!is_numeric($iMinistryID) || $iMinistryID == 0)
		{
			//set message here and return false;
			Session::setAlertMessage('danger', 'There is no ministry selected for this operation');
			return false;
		}
		$iNewMembers = 0;
		$db = $this->db();
		if(isset($aPost['members']))
		{
			foreach($aPost['members'] as $iMemberID => $aDetails)
			{
				if($aDetails['selected'] != 'on') continue;
				$aDataInsert = array(
					'date_joined' => (isset($aDetails['date_joined']) && $aDetails['date_joined'] != '') ? strtotime($aDetails['date_joined']) : time(),
					'member_id'=>$iMemberID, 'ministry_id'=>$aPost['ministry_id'], 'date_created'=>time(), 'created_by'=>Session::loggedUserId()
				);
				$db->insert(CHMS_MINISTRY_MEMBERS, $aDataInsert);
				$iID = $db->lastInsertId();
				if($iID > 0)
					$iNewMembers++;
			}
		}
		if($iNewMembers > 0)
			Session::setAlertMessage('success', $iNewMembers.' new members where added to this ministry');
		else
			Session::setAlertMessage('warning', $iNewMembers.' members where added to this ministry');
	}

	public function removeMinistryMembers($aPost = array())
	{
		$iRemovedMembers = 0;
		$db = $this->db();
		if(isset($aPost['members']))
		{
			foreach($aPost['members'] as $iMemberID => $sSelected )
			{
				$db->delete(CHMS_MINISTRY_MEMBERS, "id=".$iMemberID);
				$iRemovedMembers++;
			}
		}
		if($iRemovedMembers > 0)
			Session::setAlertMessage('info', $iRemovedMembers.'  members were removed from this ministry');
	}

	public function getVisitor($iID = 0)
	{
		if(!is_numeric($iID) || $iID == 0) return NULL;
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_VISITORS))->where('a.id=?', $iID);
		return $db->fetchRow($sql);
	}
	public function listVisitors($aPost=array(), $bAdmin=0)
	{
		$db = $this->db();
		$sql = $db->select()->from(array('a'=>CHMS_VISITORS));
		if($bAdmin == 0)
			$sql->where('a.is_active=?',1);
		return $db->fetchAll($sql);
	}

	public function activateChmsRow($sDbTbl='', $sCommand='', $iID=0)
	{
		if($sDbTbl =='' || $iID == 0) return FALSE;
		$db = $this->db();
		$iActive = (strtolower($sCommand) == 'activate') ? 1: 0;
		$db->update($sDbTbl, array('is_active'=>$iActive), 'id='.$iID);
	}
	public function deleteChmsRow($sDbTbl='', $iID=0)
	{
		if($sDbTbl =='' || $iID == 0) return FALSE;
		$db = $this->db();
		$db->delete($sDbTbl, 'id='.$iID);
	}

	public function saveZone($aPost=array())
	{
		$db = $this->db();
		$aDbCols = array_keys($db->describeTable(CHMS_ZONES));
		$iZoneID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aDataInsert =array();
		foreach($aDbCols as $sCol)
		{
			if ($sCol == 'id' || $sCol == 'is_active' || $sCol == 'created_by' || $sCol == 'date_created') continue;
			if($sCol == 'date_started')
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? strtotime($aPost[$sCol]) : new Zend_Db_Expr('NULL');
			else
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? $aPost[$sCol] : new Zend_Db_Expr('NULL');
		}
		if($iZoneID > 0)
		{
			$aDataInsert['last_updated_by'] = Session::loggedUserId();
			$aDataInsert['last_updated'] = time();
			$db->update(CHMS_ZONES, $aDataInsert, "id=$iZoneID");
		}
		else
		{
			$aDataInsert['created_by'] = Session::loggedUserId();
			$aDataInsert['date_created'] = time();
			$db->insert(CHMS_ZONES, $aDataInsert);
			$iZoneID = $db->lastInsertId();
		}
		return $iZoneID;
	}

	public function saveMinistry($aPost=array())
	{
		$db = $this->db();
		$aDbCols = array_keys($db->describeTable(CHMS_MINISTRIES));
		$iMinistryID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aDataInsert =array();
		foreach($aDbCols as $sCol)
		{
			if ($sCol == 'id' || $sCol == 'is_active' || $sCol == 'created_by' || $sCol == 'date_created') continue;
			if($sCol == 'date_started')
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? strtotime($aPost[$sCol]) : new Zend_Db_Expr('NULL');
			else
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? $aPost[$sCol] : new Zend_Db_Expr('NULL');
		}
		if($iMinistryID > 0)
		{
			$aDataInsert['last_updated_by'] = Session::loggedUserId();
			$aDataInsert['last_updated'] = time();
			$db->update(CHMS_MINISTRIES, $aDataInsert, "id=$iMinistryID");
		}
		else
		{
			$aDataInsert['created_by'] = Session::loggedUserId();
			$aDataInsert['date_created'] = time();
			$db->insert(CHMS_MINISTRIES, $aDataInsert);
			$iMinistryID = $db->lastInsertId();
		}
		return $iMinistryID;
	}

	public function saveModuleSettings($aPost=array())
	{
		$db = $this->db();
		$aDbCols = array_keys($db->describeTable(CHMS_SETTINGS));
		$aDataInsert =array();
		foreach($aDbCols as $sCol)
		{
			if ($sCol == 'id') continue;
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? $aPost[$sCol] : new Zend_Db_Expr('NULL');
		}

		$aSettings = $this->getChmsRow(CHMS_SETTINGS, "id>0", "id ASC");
		if(isset($aSettings['id']) && $aSettings['id'] > 0)
			$db->update(CHMS_SETTINGS, $aDataInsert, "id=".$aSettings['id']);
		else
			$db->insert(CHMS_SETTINGS, $aDataInsert);
	}

	public function saveVisitor($aPost=array())
	{
		$db = $this->db();
		$aDbCols = array_keys($db->describeTable(CHMS_VISITORS));
		$iVisitorID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aDataInsert =array();
		foreach($aDbCols as $sCol)
		{
			if($sCol == 'id' || $sCol == 'is_active' || $sCol == 'created_by' || $sCol == 'date_created') continue;
			if($sCol == 'birth_date' || $sCol == 'date_of_visit')
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? strtotime($aPost[$sCol]) : new Zend_Db_Expr('NULL');
			else
				$aDataInsert[$sCol] = (isset($aPost[$sCol]) && $aPost[$sCol]) ? $aPost[$sCol] : new Zend_Db_Expr('NULL');
		}
		if($iVisitorID > 0)
		{
			$aDataInsert['last_updated_by'] = Session::loggedUserId();
			$aDataInsert['last_updated'] = time();
			$db->update(CHMS_VISITORS, $aDataInsert, "id=$iVisitorID");
		}
		else
		{
			$aDataInsert['created_by'] = Session::loggedUserId();
			$aDataInsert['date_created'] = time();
			$db->insert(CHMS_VISITORS, $aDataInsert);
			$iVisitorID = $db->lastInsertId();
		}
		return $iVisitorID;
	}
}
