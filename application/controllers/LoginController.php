<?php

class LoginController extends Zend_Controller_Action
{
	public function indexAction()
	{
		$aPost = $this->getRequest()->getParams();
		$config = Zend_Registry::get('config');
		$passwordHashKey = $config->passwordSalt;

		$db = Zend_Registry::get('db');
		$sPassword = (isset($aPost['password']) && $aPost['password'] != '') ? secure('sha256', $aPost['password'], $passwordHashKey) : new Zend_Db_Expr('NULL');
		$sUsername = (isset($aPost['username']) && $aPost['username'] != '') ? $aPost['username'] : new Zend_Db_Expr('NULL');

		$nameSpaceUser = new Zend_Session_Namespace('user');

		if (isset($aPost['command']) && $aPost['command'] != '')
		{
			$sql = $db->select()
				->from(array('u' => USERS))
				->joinLeft(array('c' => COUNTRIES), 'c.id=u.country_id', array('country' => 'c.name'))
				->where('u.username =?', $sUsername)
				->where('u.password =?', $sPassword);
			$user = $db->fetchRow($sql);
			/*$table = new Zend_Db_Table(array('name' => 'users'));
			$sql = $table->select()->where('id=?', 1);
			$aUser = $table->fetchRow($sql);
			echo '<pre>'; print_r($aUser); die;*/
			if (isset($user['id']) && $user['id'] > 0)
			{
				if (!isset($nameSpaceUser->loggedUser))
				{
					$nameSpaceUser->loggedUser = $user;
					$aSetOptions = Bootstrap::getSystemOptions();
					$iSessionSpan = (isset($aSetOptions['system_session_span']) && $aSetOptions['system_session_span'] > 0) ? $aSetOptions['system_session_span'] : 6000;
					$nameSpaceUser->setExpirationSeconds($iSessionSpan);
				}

				if(Session::get('stand_alone_module_id') > 0)
				{
					//redirect to the standalone module index page
					$aModule = Bootstrap::getModule(Session::get('stand_alone_module_id'));
					$this->redirect($aModule['name']);
				}
				else
				{
					//redirect to the system index page
					$this->redirect('index');
				}
			}
			else
			{
				$this->view->sError = 'Invalid credentials. Try again';
			}
		}
	}

	public function logoutAction()
	{
		Zend_Session::expireSessionCookie();
		Zend_Session::destroy();
		$this->redirect('login');
	}
}