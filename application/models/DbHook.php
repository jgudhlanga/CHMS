<?php
/**
 * Author: jimlink
 * Date: 9/18/2016
 * Time: 2:23 PM
 * Copyright (c) Wemasoft
 * @version 3.0 (excellence)
 */

class DbHook {
	public function db()
	{
		return Zend_Registry::get('db');
	}
} 