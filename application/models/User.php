<?php

/**
 * Created by PhpStorm.
 * User: jimlink
 * Date: 4/11/2015
 * Time: 3:21 PM
 */
class User
{
	public function userExport($sAction = '')
	{
		$sMessage = '';
		if ($sAction == 'Export Data')
		{
			$db = Zend_Registry::get('db');
			$aUsers = $this->getUsers();
			if (!empty($aUsers))
			{
				$aHeader = array();
				$sData = '';
				$sBasePath = BASE_PATH;
				$sUrl = BASE_URL;
				if (!is_dir("$sBasePath/public/uploads/users/") && !file_exists("$sBasePath/public/uploads/users/"))
				{
					mkdir("$sBasePath/public/uploads/users/", 0775, TRUE);
				}
				$sFile = "public/uploads/users/" . "jp" . strtotime('now') . ".csv";
				$sFileOpen = fopen($sFile, "w");
				foreach ($aUsers as $aUser)
				{
					foreach (array_keys($aUser) as $sKey)
					{
						if (!isset($aHeader[$sKey])) $aHeader[$sKey] = $sKey;
					}
					$sData .= $aUser['id'] . ',';
					$sData .= $aUser['firstname'] . ',';
					$sData .= $aUser['surname'] . ',';
					$sData .= $aUser['email'] . "\n";
				}
				$sHeader = $db->tableFields('userimports') . "\n";
				fputs($sFileOpen, $sHeader . $sData);
				$sFileLink = $sUrl . '/' . $sFile;
				$sMessage = "<tr><td colspan='2'><p class='success'>Your File has been successfully created. <br/><a href=\"$sFileLink\">
							Click to download your file</a></p></td></tr>";
			}
			else    $sMessage = "<tr><td colspan='2'><p class='error'>There is no data to export yet!</p></td></tr>";
		}
		return $sMessage;
	}

	public function saveUser($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aTableFields = array_keys($db->describeTable(USERS));
		$aData = array();
		$config = Zend_Registry::get('config');
		$passwordHashKey = $config->passwordSalt;
		$iId = 0;

		if (isset($aPost['id']) && $aPost['id'] > 0 && $aPost['command'] == 'Save About Me')
		{
			$aAboutMe = array(
				'about_me' => (isset($aPost['about_me']) && $aPost['about_me'] != '') ? $aPost['about_me'] : new Zend_Db_Expr('NULL'));
			$db->update(USERS, $aAboutMe, "id=$aPost[id]");
		}
		else
		{
			if (isset($aPost['password']) && trim($aPost['password']) != '' && trim($aPost['password']) == trim($aPost['cpassword']))
			{
				$sPassword = secure('sha256', $aPost['password'], $passwordHashKey);
				$aData['password'] = $sPassword;
			}
			foreach ($aTableFields as $sKey)
			{
				if ($sKey != '' && array_key_exists(trim($sKey), $aPost))
				{
					if ($sKey == 'id' || $sKey == 'profile_pic' || $sKey == 'date_created' || $sKey == 'password') continue;
					else
						$aData[trim($sKey)] = (isset($aPost[trim($sKey)]) && trim($aPost[trim($sKey)]) != '') ? $aPost[trim($sKey)] : NULL;
				}
			}

			$aUpload = (isset($aPost['profile_pic']) && $aPost['profile_pic'] != '') ? $aPost['profile_pic'] : array();

			if (isset($aUpload['tmp_name']) && $aUpload['tmp_name'] != '')
			{
				//do the upload of the profile picture
				$aData['profile_pic'] = (isset($aUpload['name']) && $aUpload['name'] != '') ? $aUpload['name'] : NULL;
				$sImagePath = $config->imgPath;
				if (!file_exists("$sImagePath/users/") && !is_dir("$sImagePath/users/"))
				{
					mkdir("$sImagePath/users/", 0775, TRUE);
				}
				move_uploaded_file($aUpload['tmp_name'], "$sImagePath/users/" . $aUpload['name']);
			}

			$aData['birth_date'] = (isset($aData['birth_date']) && $aData['birth_date'] != '') ? strtotime($aData['birth_date']) : 0;
			if (!empty($aData))
			{
				if (isset($aPost['id']) && $aPost['id'] > 0)
				{
					$aData['last_updated'] = time();
					$db->update(USERS, $aData, "id=$aPost[id]");
					$iId = $aPost['id'];
				}
				else
				{
					$aData['date_created'] = time();
					$db->insert(USERS, $aData);
					$iId = $db->lastInsertId();
				}
			}
		}
		return $iId;
	}

	function getUsers($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(array('u' => USERS))
			->joinLeft(array('g' => GENERAL_PUBLIC), 'g.id=u.gender', array('user_gender' => 'g.name'))
			->joinLeft(array('t' => GENERAL_PUBLIC), 't.id=u.title', array('user_title' => 't.name'))
			->joinLeft(array('d' => GENERAL_PUBLIC), 'd.id=u.designation_id', array('designation' => 'd.name'))
			->joinLeft(array('dd' => DEPARTMENTS), 'dd.id=u.department_id', array('dd.department'))
			->joinLeft(array('c' => CITIES), 'c.id=u.city_id', array('c.city'))
			->joinLeft(array('p' => PROVINCES), 'p.id=u.province_id', array('p.province'))
			->joinLeft(array('gc' => COUNTRIES), 'gc.id=u.country_id', array('country' => 'gc.name'));
		if (isset($aPost['is_active']) && $aPost['is_active'] > 0)
		{
			$sql->where('u.is_active=?', 1);
		}
		if (isset($aPost['id']) && $aPost['id'] > 0)
		{
			$sql->where('u.id=?', $aPost['id']);
			$aReturn = $db->fetchrow($sql);
		}
		else
			$aReturn = $db->fetchAll($sql);
		return $aReturn;
	}

	public function deleteUserProfile($iUserId = 0)
	{
		if ($iUserId > 0)
		{
			$db = Zend_Registry::get('db');
			$config = Zend_Registry::get('config');
			$aPost = array('id' => $iUserId);
			$aUser = $this->getUsers($aPost);
			//delete user rights,
			$db->delete(USER_ROLES, 'user_id=' . $iUserId);
			//delete user pictures
			unlink($config->imgPath . "/uploads/users/" . $aUser['profile_pic']);
			// delete from users
			$db->delete(USERS, 'id=' . $iUserId);
		}
	}
}