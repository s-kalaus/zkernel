<?php
/**
 * Zkernel
 *
 * Copyright (c) 2010 Magwai Ltd. <info@magwai.ru>, http://magwai.ru
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

class Zkernel_Db_Model_Menu extends Zkernel_Db_Table {
	protected $_name = 'menu';
	protected $_multilang_field = array(
		'title'
	);
}
