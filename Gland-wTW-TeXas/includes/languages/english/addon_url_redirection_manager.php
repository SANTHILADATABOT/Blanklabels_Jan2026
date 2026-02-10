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
	define('HEADING_TITLE', 'URL Redirection Manager');
	
	define('TBL_HEAD_ID', 'ID');
	define('TBL_HEAD_ORIGINAL_URL', 'Original URL');
	define('TBL_HEAD_REDIRECT_URL', 'Redirects To');
	define('TBL_HEAD_REDIRECT_TYPE', 'Type');
	define('TBL_HEAD_EXACT_MATCH', 'Exact');
	define('TBL_HEAD_REDIRECT_STATUS', 'Status');
	define('TBL_HEAD_ACTION', 'Action');
	
	define('PANE_HEAD_TITLE_SHOW_SETTINGS', 'Redirect Settings');
	define('PANE_HEAD_TITLE_EDIT_SETTINGS', 'Change Redirect');
	define('PANE_HEAD_TITLE_ADD_SETTINGS', 'Add Redirection');
	define('PANE_HEAD_TITLE_CONFIRM_DELETE', 'Delete Confirmation');
	
	define('LBL_REDIRECT_ID', 'Record ID:');
	define('LBL_ORIGINAL_URL', 'Original URL:');
	define('LBL_REDIRECT_URL', 'Redirect To:');
	define('LBL_REDIRECT_TYPE', 'Redirect Type:');
	define('LBL_REDIRECT_EXACT', 'Exact Match?');
	define('LBL_REDIRECT_STATUS', 'Status:');
	define('LBL_SEARCH', 'Search: ');
	
	define('INFO_YES', 'Yes');
	define('INFO_NO', 'No');
	define('INFO_ACTIVE', 'Active');
	define('INFO_DISABLED', 'Disabled');
	
	define('BUTTON_ADD', 'Add URL Redirect');
	define('BUTTON_EDIT', 'Edit Redirect');
	define('BUTTON_DELETE', 'Delete Redirect');
	define('BUTTON_SUBMIT', 'Submit');
	define('BUTTON_CANCEL', 'Cancel');
	
	define('IMAGE_ICON_EXACT_ON', 'Exact Match - ON');
	define('IMAGE_ICON_EXACT_OFF', 'Exact Match - OFF');
	
	/* FEEDBACK */
	define('FEEDBACK_NO_REDIRECTS', 'The database is currently empty. Click the &quot;' . BUTTON_ADD . '&quot; button to add an entry.');
	define('FEEDBACK_NO_SEARCH_RESULTS', 'There is no record matching your keyword search.');
	define('FEEDBACK_ORIGINAL_URL_MISSING', 'The original URL is required! Please, specify the original URL and try again.');
	define('FEEDBACK_REDIRECT_URL_MISSING', 'The redirect URL is required! Please, specify the new URL and try again.');
	define('FEEDBACK_INVALID_REDIRECT_TYPE', 'The type of redirection is not valid. Please, try again.');
	define('FEEDBACK_ORIGINAL_URL_INVALID', 'The original URL provided does not appear to be a valid URL. Please, try again.');
	define('FEEDBACK_REDIRECT_URL_INVALID', 'The redirect URL provided does not appear to be a valid URL. Please, try again.');
	define('FEEDBACK_SAME_ORIGINAL_REDIRECT_URLS', 'You cannot create a redirect to the original URL. Please, make sure the original and the redirect URLs are different.');
	define('FEEDBACK_ORIGINAL_IS_ROOT', 'The original URL cannot be root. Please, try again.');
	define('FEEDBACK_TITLE_DB_OPERATION_FAILED', 'Database Request Failed!');
	define('FEEDBACK_REDIRECT_ID_MISSING', 'The redirect your refered to does not exist.');
	define('FEEDBACK_MULTIPLE_REDIRECTS', 'Multiple redirects detected. You must redirect to the final destination. Please, try again.');
	define('FEEDBACK_DUPLICATE_ORIGINAL_URL', 'Duplicate original URL detected. Please, try again.');
	
	define('CONFIRM_DELETE_REDIRECT', 'Are you sure you want to delete this redirect?');
	
	/* SETTINGS */
	define('LIMIT_MAX_ROWS', 25);
	define('DEFAULT_STATUS', true); //ACTIVE = true, INNACTIVE = false
	define('PAGINATION_LABEL', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> redirections)');
?>
