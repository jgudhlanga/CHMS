<?php

class Admin
{
	public function adminImports($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aUpload = (isset($aPost['upload']) && !empty($aPost['upload'])) ? $aPost['upload'] : array();
		$sql = $db->select()
			->from(array('i' => GENERAL_PUBLIC, array('name')))
			->where('id=?', $aPost['import_id']);
		$sImportType = $db->fetchOne($sql);
		/*
		  * 1-> get the file,
		  * 2-> validate its type,
		  * 3-> open and read it,
		  * 4->loop through the data rows and remember to skip row one a it is the header
		  * 5 ->get the import name i.e using the
		 */
		$sFileName = $aUpload['name'];
		$aCSV = explode('.', $sFileName);
		$sCSV = array_pop($aCSV);
		if ($sCSV == 'csv')
		{
			$sTempFile = $aUpload['tmp_name'];
			$sFileOpen = fopen($sTempFile, 'r');
			$iCounter = 0;
			while ($aRow = fgetcsv($sFileOpen, '3000', ','))
			{
				/*
				 [0] => Username  [1] => Password  [2] => Firstname  [3] => Surname  [4] => Initials  [5] => Email Address  [6] => ID  / Passport Number
				 [7] => Birth Date  [8] => Title  [9] => Gender  [10] => Cell Number [11] => Alt Number [12] => Work Number [13] => Web Address [14] => Fax Number
				 [15] => Street Address [16] => City  [17] => Province [18] => Country [19] => Job Title  [20] => Department
				 */
				$iCounter++;
				if ($iCounter == 1) continue;
				$aData = array();
				if (strtolower($sImportType) == USERS)
				{
					$aData['username'] = (isset($aRow[0]) && $aRow[0] != '') ? $aRow[0] : '';
					$aData['password'] = (isset($aRow[1]) && $aRow[1] != '') ? secure("sha256", $aRow[0], PASSWORD_HASH_KEY) : NULL;
					$aData['firstname'] = (isset($aRow[2]) && $aRow[2] != '') ? $aRow[2] : NULL;
					$aData['surname'] = (isset($aRow[3]) && $aRow[3] != '') ? $aRow[3] : NULL;
					$aData['initials'] = (isset($aRow[4]) && $aRow[4] != '') ? $aRow[4] : NULL;
					$aData['email_address'] = (isset($aRow[5]) && $aRow[5] != '') ? $aRow[5] : NULL;
					$aData['id_number'] = (isset($aRow[6]) && $aRow[6] != '') ? $aRow[6] : NULL;
					$aData['birth_date'] = (isset($aRow[7]) && $aRow[7] != '') ? strtotime($aRow[7]) : NULL;
					$aData['cell_number'] = (isset($aRow[10]) && $aRow[10] != '') ? $aRow[10] : NULL;
					$aData['alt_number'] = (isset($aRow[11]) && $aRow[11] != '') ? $aRow[11] : NULL;
					$aData['work_number'] = (isset($aRow[12]) && $aRow[12] != '') ? $aRow[12] : NULL;
					$aData['web_address'] = (isset($aRow[13]) && $aRow[13] != '') ? $aRow[13] : NULL;
					$aData['fax_number'] = (isset($aRow[14]) && $aRow[14] != '') ? $aRow[14] : NULL;
					$aData['street_address'] = (isset($aRow[15]) && $aRow[15] != '') ? $aRow[15] : NULL;
					$aData['is_active'] = 1;
					//check from general_public for the title with the passed name, if its there return the id else insert and return its id
					if ($aRow[8] != '')
					{
						$iTitleID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[8]') and is_title=1");
						if ($iTitleID == 0 || $iTitleID == NULL)
						{
							$db->insert(GENERAL_PUBLIC, array('name' => $aRow[8], 'is_title' => 1));
							$iTitleID = $db->lastInsertId();
						}
						$aData['title'] = $iTitleID;
					}
					if ($aRow[9] != '')
					{
						$iGenderID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[9]') and is_gender=1");
						if ($iGenderID == 0 || $iGenderID == NULL)
						{
							$db->insert(GENERAL_PUBLIC, array('name' => $aRow[9], 'is_gender' => 1));
							$iGenderID = $db->lastInsertId();
						}
						$aData['gender'] = $iGenderID;
					}
					if ($aRow[16] != '')
					{
						$iCityID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[16]') and is_city=1");
						if ($iCityID == 0 || $iCityID == NULL)
						{
							$db->insert(GENERAL_PUBLIC, array('name' => $aRow[16], 'is_city' => 1));
							$iCityID = $db->lastInsertId();
						}
						$aData['city_id'] = $iCityID;
					}
					if ($aRow[17] != '')
					{
						$iProvinceID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[17]') and is_province=1");
						if ($iProvinceID == 0 || $iProvinceID == NULL)
						{
							$db->insert(GENERAL_PUBLIC, array('name' => $aRow[17], 'is_province' => 1));
							$iProvinceID = $db->lastInsertId();
						}
						$aData['province_id'] = $iProvinceID;
					}
					if ($aRow[18] != '')
					{
						$iCountryID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[18]') and is_country=1");
						if ($iCountryID == 0 || $iCountryID == NULL)
						{
							$db->insert(GENERAL_PUBLIC, array('name' => $aRow[18], 'is_country' => 1));
							$iCountryID = $db->lastInsertId();
						}
						$aData['country_id'] = $iCountryID;
					}
					if ($aRow[19] != '')
					{
						$iDesignationID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[19]') and is_designation=1");
						if ($iDesignationID == 0 || $iDesignationID == NULL)
						{
							$aInsert = array('name' => $aRow[19], 'is_designation' => 1);
							$db->insert(GENERAL_PUBLIC, $aInsert);
							$iDesignationID = $db->lastInsertId();
						}
						$aData['designation_id'] = $iDesignationID;
					}
					if ($aRow[20] != '')
					{
						$iDepartmentID = $db->fetchone("select id from ".GENERAL_PUBLIC." where lower(name) like lower('$aRow[20]') and is_department=1");
						if ($iDepartmentID == 0 || $iDepartmentID == NULL)
						{
							$db->insert(GENERAL_PUBLIC, array('name' => $aRow[20], 'is_department' => 1));
							$iDepartmentID = $db->lastInsertId();
						}
						$aData['department_id'] = $iDepartmentID;
					}
					//check if the user exists and if true then update else insert
					$db->insert('user', $aData);
				}
			}
		}

	}

	public function saveAdminPublic($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aTableFields = $db->describeTable(GENERAL_PUBLIC);
		$aData = array();
		$iID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		foreach ($aTableFields as $sKey => $aColumns)
		{
			if (trim($sKey) != '' && array_key_exists(trim($sKey), $aPost))
			{
				$aData[trim($sKey)] = (isset($aPost[trim($sKey)]) && $aPost[trim($sKey)] != '') ? $aPost[trim($sKey)] : NULL;
			}
		}
		if ($iID > 0 && $aPost['command'] == 'Delete')
		{
			$db->delete(GENERAL_PUBLIC, "id=$iID");
		}
		else
		{
			if ($iID > 0)
			{
				$db->update(GENERAL_PUBLIC, $aData, "id=$aPost[id]");
			}
			else
			{
				$db->insert(GENERAL_PUBLIC, $aData);
			}
		}
	}

	public function getAdminPublic($aPost = array(), $iID = 0)
	{
		$db = Zend_Registry::get('db');
		$aReturn = array();
		if ($iID > 0)
		{
			$sql = $db->select()
				->from(array('g' => GENERAL_PUBLIC))
				->where('id=?', $iID);
			$aReturn = $db->fetchRow($sql);
		}
		else
		{
			$aTableFields = $db->describeTable(GENERAL_PUBLIC);
			$sql = $db->select()
				->from(array('g' => GENERAL_PUBLIC))
				->where('1=?', 1);
			foreach ($aTableFields as $sKey => $aColumns)
			{
				if (isset($aPost[trim($sKey)]) && $aPost[trim($sKey)] != '' && $sKey != 'id')
					$sql->where("$sKey=?", $aPost[$sKey]);
			}
			$sql->order('name ASC');
			$aReturn = $db->fetchAll($sql);
		}

		return $aReturn;
	}

	function saveDbTableUpdateSettings($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$aTableFields = $db->describeTable(TABLE_SETTINGS);
		$iID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$aData = array();
		foreach ($aTableFields as $sKey => $aValues)
		{
			if ($sKey == 'id' || $sKey == 'is_active') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData))
		{
			if ($iID > 0)
				$db->update(TABLE_SETTINGS, $aData, "id=$iID");
			else
			{
				//check if the setting already exist
				$sql = $db->select()
					->from(TABLE_SETTINGS, array('id'))
					->where('setting_name=?', $aPost['setting_name']);
				$iExistID = $db->fetchone($sql);
				if ($iExistID == 0 || $iExistID == NULL)
				{
					$db->insert(TABLE_SETTINGS, $aData);
					$iID = $db->lastInsertId();
				}
			}
		}
		return $iID;
	}

	public function deleteDbTableUpdateSettings($iID = 0)
	{
		$db = Zend_Registry::get('db');
		$db->delete(TABLE_SETTINGS, "id=$iID");
	}

	public function activateDbTableUpdateSettings($iID = 0, $sCommand = '')
	{
		$db = Zend_Registry::get('db');
		$iActive = ($sCommand == 'Activate') ? 1 : 0;
		$db->update(TABLE_SETTINGS, array('is_active' => $iActive), "id=$iID");
	}

	function getDbTableUpdateSettings($id = 0)
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(TABLE_SETTINGS)
			->where('id=?', $id);
		return $db->fetchrow($sql);
	}

	function listDbTableUpdateSettings($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$sql = $db->select()
			->from(TABLE_SETTINGS)
			->order(array('setting_name ASC', 'id DESC'));
		return $db->fetchAll($sql);
	}

	/*
	 * This function will generate an HTML output that shows the contents of db table where u can do the CRUD
	 * @param int   $iID => the setting id i.e
	 * @param array $aPost data to be saved when saving a new entry to the db table specified
	 */
	public function generateEditTable($iID = 0, $aPost = array())
	{
		$db = Zend_Registry::get('db');
		$config = Zend_Registry::get('config');
		$sBaseUrl = $config->baseUrl;
		$iItemID = (isset($aPost['item_id']) && $aPost['item_id'] > 0) ? $aPost['item_id'] : 0;
		$aEdit = $this->getDbTableUpdateSettings($iID);
		$iWidth = (isset($aEdit['width'])) ? rtrim($aEdit['width'], '%') . '%' : '100%';
		$sReturn = "<form method='post' ><table class='table' style='width: $iWidth'>";

		#0 Get the user theme
		$aUserSettings = Bootstrap::getSystemOptions();
		$theme = (isset($aUserSettings['system_theme']) && $aUserSettings['system_theme'] != '') ? $aUserSettings['system_theme'] : 'classic';
		//1 get the settings from admin_general_table_settings using the id;
		$sEditName = isset($aEdit['setting_name']) ? $aEdit['setting_name'] : '';
		$sTable = (isset($aEdit['table_name']) && $aEdit['table_name'] != '') ? $aEdit['table_name'] : ''; # The db table name that saves the data
		$sOrder = (isset($aEdit['order_by']) && $aEdit['order_by'] != '') ? $aEdit['order_by'] : '';
		$aColumnsWhere = (isset($aEdit['table_columns']) && $aEdit['table_columns'] != '') ? explode('?', $aEdit['table_columns']) : array();
		$aTableColumns = (isset($aColumnsWhere[0])) ? explode('.', $aColumnsWhere[0]) : array(); # db table columns to work with
		$aWhereClause = (isset($aColumnsWhere[1])) ? explode('.', $aColumnsWhere[1]) : array(); # Filtering Clause

		//country_id.general_country.id.name
		$aForeignKey = ($aEdit['foreign_keys'] != '') ? explode('.', $aEdit['foreign_keys']) : ''; # Foreign Table params
		$sForeignKey = (is_array($aForeignKey)) ? array_shift($aForeignKey) : $aForeignKey;
		$sForeignKeyTable = is_array($aForeignKey) ? array_shift($aForeignKey) : '';
		$sForeignKeyField = $aForeignKey;
		$sTable = ($sTable != '') ?  constant($sTable) : '';
		$sForeignKeyTable = ($sForeignKeyTable != '') ?  constant($sForeignKeyTable) :'';
		if ($iID > 0)
		{
			#TODO if  the table columns are not specified describe the table
			if (empty($aTableColumns))
			{
				$aColumns = array_keys($db->describeTable($sTable));
				foreach ($aColumns as $sKey)
				{
					if ($sKey == 'id' || $sKey == 'is_active') continue;
					$aTableColumns[] = $sKey;
				}
			}
			$iTableSpan = (!empty($aTableColumns)) ? count($aTableColumns) + 3 : 1; #

			//delete button
			$sDelete = loadImage(Null, Null, 'delete.png', 16, 16);
			$sDelete = "<span class='glyphicon glyphicon-trash'></span>";
			$sReturn .= "<tr><th colspan=\"$iTableSpan\"><span class='jjdev-toggle-title'>$sEditName</span>
						<span class='jjdev-toggle-icon'><img rel='1_exp' title='Collapse' src='$sBaseUrl/img/$theme/col.png' onclick='return toggleContent(1)'></span>
            	</th>
            </tr>";//table header
			//generate header row
			$sReturn .= "<tr class='caption'><td></td>";
			foreach ($aTableColumns as $sHeader) $sReturn .= "<td style='text-align: left;'>" . strtoupper($sHeader) . "</td>";
			$sReturn .= "<td></td><td></td></tr>";
			//get the data from the table

			$sql = $db->select()
				->from($sTable)
				->where('1=?', 1);
			if (!empty($aWhereClause))
			{
				foreach ($aWhereClause as $sWhere)
				{
					$sql->where("$sWhere");
				}
			}
			if ($sOrder != '')
				$sql->order($sOrder);
			$aResult = $db->fetchAll($sql);
			if (!empty($aResult))
			{
				$iBatch = $iBatchCount = 1;
				$iPaginationMax = $iPaginationMin = 15;
				foreach ($aResult as $aRow)
				{
					$iBatch++;
					$sButtonActivate = ($aRow['is_active'] == 1) ? 'Deactivate' : 'Activate';
					$sIconActivate = ($aRow['is_active'] == 1) ? 'ban-circle' : 'check';
					if ($iBatch % 15 == 0)
					{
						$iBatchCount++;
						$iPaginationMax += 15;
						$iPaginationMin = ($iPaginationMax - 14);
						$sRel = $iBatchCount . '_exp';
						$sReturn .= "<tr>
                                    <th colspan='$iTableSpan'><span class='jjdev-toggle-title'>$iPaginationMin &rarr; $iPaginationMax</span>
            	                      <span class='jjdev-toggle-icon'><img title='Collapse' rel='$sRel' src='$sBaseUrl/img/$theme/col.png'  onclick='return toggleContent($iBatchCount)'></span>
                                    </th>
                                </tr>";
					}
					$sReturn .= "<tr class='content_$iBatchCount' style='display:;'>"; #Start a new row
					$sReturn .= "<td style='text-align: center' width='80'>
                    <button class='btn btn-default btn-xs' onclick=\"url('$sBaseUrl/admin/edit-tables/id/$iID/item_id/$aRow[id]')\">
                        <span class='glyphicon glyphicon-edit'></span>Edit
                    </button>
                    </td>";#Edit button
					foreach ($aTableColumns as $column)
					{
						if ($column == $sForeignKey && $sForeignKey != '')
						{
							$aItems = itemsToArray($sForeignKeyTable, $sForeignKeyField, NULL);
							$value = isset($aItems[$aRow[$column]]) ? $aItems[$aRow[$column]] : '';
						}
						else
						{
							$value = isset($aRow[$column]) ? $aRow[$column] : '';
						}
						$sReturn .= "<td style='text-align: left;'>$value</td>";
					}
					$sReturn .= "<td style='text-align: center' width='80'>
										<button class=\"btn btn-default btn-xs\" style='width: 100px'
										  onclick=\"url('$sBaseUrl/admin/activate-db-table-data/id/$iID/item_id/$aRow[id]/command/$sButtonActivate')\">
											<span class=\"glyphicon glyphicon-{$sIconActivate}\"></span>
											$sButtonActivate</button></td>";#active deactivate button
					$sReturn .= "<td style='text-align: center; width:10px;' >
                                    <a alt='Delete' title='Delete' onclick=\"confirmDelete('$sBaseUrl/admin/delete-db-table-data/item_id/$aRow[id]/', $iID)\">
                                        $sDelete
                                    </a>
                            </td>";#Delete button
					$sReturn .= "</tr>";
				}
			}
			else
			{
				$sReturn .= "<tr><td  colspan='$iTableSpan'>Nothing found</td></tr>";

			}
			#Add new / Edit
			$sql = $db->select()
				->from($sTable)
				->where('id=?', $iItemID);
			$aEdit = $db->fetchRow($sql);
			$sReturn .= "<tr><td colspan='$iTableSpan'>&nbsp;</td></tr>";
			$sReturn .= "<tr><th  colspan='$iTableSpan'>Add/Edit $sEditName Below</th></tr>";
			$sReturn .= "<form method='post'><tr><td></td>";
			foreach ($aTableColumns as $sColumn)
			{
				$value = (isset($aEdit[$sColumn]) && $aEdit[$sColumn] != '') ? $aEdit[$sColumn] : '';
				if ($sColumn == $sForeignKey && $sForeignKey != '')
				{
					$sReturn .= "<td><select name='$sColumn' class='form-control input-sm'>
                        <option value=''>-- select option --</option>";
					$sReturn .= createSelectOptions($sForeignKeyTable, $value, $sForeignKeyField, '');
					$sReturn .= "</select></td>";
				}
				else
				{
					$sReturn .= "<td><input type='text' name='$sColumn' value='$value' class='form-control input-sm'></td>";
				}
			}
			$sReturn .= "<td colspan='2'>
                    <button type=\"submit\" name=\"command\" value=\"Save\" class=\"btn btn-default btn-sm\">
                        <span class=\"glyphicon glyphicon-floppy-save\"></span>Save
                  </button>
                    </td></tr>";
		}
		else
			$sReturn .= "<tr><td colspan='1'><div class='alert alert-warining' role='alert'>Nothing found</div></td></tr>";
		$sReturn .= "</table></form>";

		return $sReturn;
	}

	public function saveOrganizationSettings($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$sCommand = isset($aPost['command']) ? $aPost['command'] : '';
		$config = Zend_Registry::get('config');
		if ($sCommand != '')
		{
			$aTableDescription = $db->describeTable(COMPANY_SETUP);
			$aData = array();
			foreach ($aTableDescription as $sKey => $aSchema)
			{
				if ($sKey == 'id') continue;
				$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
			}
			$aUpload = (isset($_FILES['logo']) && !empty($_FILES['logo'])) ? $_FILES['logo'] : array();
			$sImage = (isset($aUpload['tmp_name']) && $aUpload['tmp_name'] != '') ? $aUpload['name'] : 'default_logo.png';
			$sImageSave = '';
			$aImage = explode('.', $sImage);
			$sExt = (!empty($aImage[1])) ? $aImage[1] : '';
			if (isset($aUpload['name']) && $aUpload['name'] != '')
			{
				$sImagePath = $config->imgPath;
				if (!file_exists("$sImagePath") && !is_dir("$sImagePath"))
				{
					mkdir("$sImagePath", 0775, TRUE);
				}
				$sImageSave = "logo_" . time() . '.' . $sExt;
				move_uploaded_file($aUpload['tmp_name'], "$sImagePath/" . $sImageSave);
			}
			if (!empty($aData))
			{
				//check for an existing record

				$sql = $db->select()
					->from(COMPANY_SETUP, array('id', 'logo'))
					->where('id > ?', 0)
					->order('id ASC');
				$aRow = $db->fetchRow($sql);
				$iExistingID = (isset($aRow['id']) && $aRow['id'] > 0) ? $aRow['id'] : 0;
				$sLogo = (isset($aRow['logo']) && $aRow['logo'] != '') ? $aRow['logo'] : new Zend_Db_Expr('NULL');
				$aData['logo'] = ($sImageSave == '') ? $sLogo : $sImageSave;
				if ($iExistingID == 0 || $iExistingID == '')
					$db->insert(COMPANY_SETUP, $aData);
				else
					$db->update(COMPANY_SETUP, $aData, 'id=' . $iExistingID);
			}
		}
	}


	public function saveDbTableData($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$iID = (isset($aPost['id']) && $aPost['id'] > 0) ? $aPost['id'] : 0;
		$iItemID = (isset($aPost['item_id']) && $aPost['item_id'] > 0) ? $aPost['item_id'] : 0;
		if ($iID > 0)
		{
			$aEdit = $this->getDbTableUpdateSettings($iID);
			$sTable = (isset($aEdit['table_name']) && $aEdit['table_name'] != '') ? $aEdit['table_name'] : ''; # The db table name that saves the data
			$aColumnsWhere = (isset($aEdit['table_columns']) && $aEdit['table_columns'] != '') ? explode('?', $aEdit['table_columns']) : array();
			$aWhereClause = (isset($aColumnsWhere[1])) ? explode('.', $aColumnsWhere[1]) : array(); # Filtering Clause
			if ($sTable != '')
			{
				$sTable = constant($sTable);
				$aTableColumns = $db->describeTable($sTable);
				$aInsert = array();
				foreach ($aTableColumns as $sKey => $aSchema)
				{
					if ($sKey == 'id' || $sKey == 'is_active') continue;
					$aInsert[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
				}
				if (!empty($aInsert))
				{
					if (array_key_exists('is_active', $aTableColumns))
						$aInsert['is_active'] = 1;

					if (!empty($aWhereClause))
					{
						foreach ($aWhereClause as $sWhereClause)
						{
							$aWhere = explode('=', $sWhereClause);
							$aInsert[$aWhere[0]] = $aWhere[1];
						}
					}
					if ($iItemID > 0)
					{
						$db->update($sTable, $aInsert, "id=$iItemID");
					}
					else
					{
						$db->insert($sTable, $aInsert);
					}
				}
			}
		}
	}

	public function deleteDbTableData($iID = 0, $iItemID = 0)
	{
		$aEdit = $this->getDbTableUpdateSettings($iID);
		$sTable = (isset($aEdit['table_name']) && $aEdit['table_name'] != '') ? $aEdit['table_name'] : '';
		# The db table name that saves the data
		$sTable = constant($sTable);
		$db = Zend_Registry::get('db');
		$db->delete($sTable, "id=$iItemID");
	}

	public function activateDbTableData($iID = 0, $iItemID = 0, $sCommand = '')
	{
		$aEdit = $this->getDbTableUpdateSettings($iID);
		$sTable = (isset($aEdit['table_name']) && $aEdit['table_name'] != '') ? $aEdit['table_name'] : ''; # The db table name that saves the data
		$db = Zend_Registry::get('db');
		$iActive = ($sCommand == 'Activate') ? 1 : 0;
		$db->update($sTable, array('is_active' => $iActive), "id=$iItemID");
	}

	public function saveSystemOptions($aPost = array())
	{
		$db = Zend_Registry::get('db');
		$nameSpace = new Zend_Session_Namespace('user');
		$user = (isset($nameSpace->loggedUser) && !empty($nameSpace->loggedUser)) ? $nameSpace->loggedUser : array();
		$iUserID = (isset($user['id']) && $user['id'] > 0) ? $user['id'] : 0;
		$aTableDescription = $db->describeTable(USER_SETTINGS);
		$aData = array();
		foreach ($aTableDescription as $sKey => $aSchema)
		{
			if ($sKey == 'id') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if (!empty($aData))
		{
			$aData['user_id'] = $iUserID;
			//check for an existing record
			$sql = $db->select()
				->from(USER_SETTINGS, array('id'))
				->where('user_id = ?', $iUserID)
				->order('id ASC');
			$aRow = $db->fetchRow($sql);
			$iExistingID = (isset($aRow['id']) && $aRow['id'] > 0) ? $aRow['id'] : 0;
			if ($iExistingID == 0 || $iExistingID == '')
				$db->insert(USER_SETTINGS, $aData);
			else
				$db->update(USER_SETTINGS, $aData, 'id=' . $iExistingID);
		}
	}

	public function saveQuickLinkSettings($aPost=array())
	{
		$db = Zend_Registry::get('db');
		$aTblCols = array_keys($db->describeTable(QUICK_LINK_SETTINGS));
		$iID = (isset($aPost['id']) && $aPost['id'] > 0)? $aPost['id'] : 0;
		$aData = array();
		foreach ($aTblCols as $sKey)
		{
			if ($sKey == 'id' || $sKey == 'is_active') continue;
			$aData[$sKey] = (isset($aPost[$sKey]) && $aPost[$sKey] != '') ? $aPost[$sKey] : new Zend_Db_Expr('NULL');
		}
		if(!empty($aData))
		{
			if(isset($aPost['resource']) && $aPost['resource'] != '')
			{
				if($iID > 0)
				$db->update(QUICK_LINK_SETTINGS, $aData, 'id='.$iID);
				else
				{
					$aData['link'] = $aPost['resource'];
					/* check existence */
					$sql = $db->select()->from(QUICK_LINK_SETTINGS, array('id'))
						->where('lower(resource) LIKE ?', strtolower($aPost['resource']))
						->where('module_id=?', $aPost['module_id']);
					$iExistID = $db->fetchOne($sql);
					if($iExistID == 0 || $iExistID == '')
						$db->insert(QUICK_LINK_SETTINGS, $aData);
				}
			}
		}
	}

	public function getAdminRow($sDbTbl='', $sWhere='')
	{
		if($sDbTbl == '' || $sWhere == '') return NULL;
		$db = Zend_Registry::get('db');
		$sql = $db->select()->from($sDbTbl)->where($sWhere);
		return $db->fetchRow($sql);
	}
	public function listAdminRows($sDbTbl='', $sWhere='')
	{
		if($sDbTbl == '') return NULL;
		$db = Zend_Registry::get('db');
		$sql = $db->select()->from($sDbTbl);
		if($sWhere != '')
			$sql->where($sWhere);
		return $db->fetchAll($sql);
	}

	public function activateAdminRow($sDbTbl='', $sCommand='', $iID=0)
	{
		if($sDbTbl =='' || $iID == 0) return FALSE;
		$db = Zend_Registry::get('db');
		$iActive = (strtolower($sCommand) == 'activate') ? 1: 0;
		$db->update($sDbTbl, array('is_active'=>$iActive), 'id='.$iID);
	}

	public function deleteAdminRow($sDbTbl='', $iID=0)
	{
		if($sDbTbl =='' || $iID == 0) return FALSE;
		$db = Zend_Registry::get('db');
		$db->delete($sDbTbl, 'id='.$iID);
	}

	public function updateQuickLinks($aPost=array())
	{
		$db = Zend_Registry::get('db');
		if(isset($aPost['link']) && !empty($aPost['link']))
		{
			foreach($aPost['link'] as $iID => $sLink)
			{
				if($sLink != '')
					$db->update(QUICK_LINK_SETTINGS, array('link'=>$sLink), 'id='.$iID);
			}
		}
	}
}
