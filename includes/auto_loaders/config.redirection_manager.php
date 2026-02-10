<?php
/**
 * @package addon_url_redirection_manager
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version: 1.4
 * @Date: 2019-03-14 10:15:00Z UTC -8
 * @Author: Will Vasconcelos <willvasconcelos@outlook.com>
 */
	if (!defined('IS_ADMIN_FLAG')) {
		die('Illegal Access');
	}
	$autoLoadConfig[100][] = array(
							'autoType' => 'class', 
							'loadFile' => 'observers/class.redirection_manager.php'
							);
	$autoLoadConfig[101][] = array(
							'autoType'  =>'classInstantiate', 
							'className' => 'redirection_manager',
							'objectName' => 'redirect'
							);