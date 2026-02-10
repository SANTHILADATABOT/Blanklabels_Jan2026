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
class redirection_manager extends base{
	
	#INSTANCE VARIABLE
	public $current_url = '';
	public $main_page = '';
	public $cPath = '';
	public $id = 0;
	
	function __construct() {
		global $zco_notifier;
		$this->attach($this, array('NOTIFY_HTML_HEAD_START'));
	}
	
	function update(&$callingClass, $notifier, $paramsArray) {
		global $db, $sniffer;
		
		if ( !$sniffer->table_exists( TABLE_ADDON_URL_REDIRECTION_MANAGER ) ) {
			return;
		}
		
		#CLEAN UP INPUT
		$this->current_url = $this->cleanup_url( $_SERVER['REQUEST_URI'] );
		
		if( zen_not_null($_GET['main_page']) ){
			$this->main_page = zen_db_prepare_input( $this->cleanup_url($_GET['main_page']) );
		}
		if( zen_not_null($_GET['cPath']) ){
			$this->cPath = zen_db_prepare_input( $this->cleanup_url($_GET['cPath']) );
		}
		if( zen_not_null($_GET['id']) ){
			$this->id = zen_db_prepare_input( $this->cleanup_url((int)$_GET['id']) );
		}else if( zen_not_null($_GET['products_id']) ){
			$this->id = zen_db_prepare_input( $this->cleanup_url((int)$_GET['products_id']) );
		}
		
		if( zen_not_null( $this->current_url ) ){
			if( $this->current_url != DIR_WS_CATALOG and $this->current_url != DIR_WS_HTTPS_CATALOG ){
				$sql = "SELECT `original_url`, `redirect_url`, `redirect_type`, `exact_match`
					FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
					WHERE `original_url` LIKE '" . $this->current_url . "'
						AND `status` = '1'";
				$rec = $db->Execute( $sql );
				if( !$rec->EOF ){
					$redirect_url = $this->url_builder( $rec->fields['redirect_url'] );
					if( $rec->fields['exact_match'] == '1' ){ #EXACT MATCH
						zen_redirect( $redirect_url, $rec->fields['redirect_type'] );
					}
				}
				#PROCESS NON-EXACT MATCH
				#CREATE FILTER FOR NON-EXACT MATCH
				$where = '';
				#IDENTIFY CURRENT PAGE
				$redirect_type = '301'; #DEFAULT
				
				/* START: DEALING WITH VANISHING GET PARAMETERS */
				$url_parts = parse_url( $this->current_url );
				if( isset( $url_parts['query'] ) ){
					parse_str($url_parts['query'], $parsed_url );
				}
				if( $this->main_page == 'page_not_found' or $this->main_page == '' ){ #NO MAIN PAGE
					if( isset($parsed_url['main_page']) ){
						$this->main_page = zen_db_prepare_input( $this->cleanup_url($parsed_url['main_page']) );
					}
				}
				if( $this->id <= 0 ){ #NO PRODUCT OR EZ PAGE ID
					if( isset($parsed_url['products_id']) ){
						$this->id = zen_db_prepare_input( $this->cleanup_url($parsed_url['products_id']) );
					}else if( isset($parsed_url['id']) ){
						$this->id = zen_db_prepare_input( $this->cleanup_url($parsed_url['id']) );
					}
				}
				if( $this->cPath == '' ){ #NO CATEGORY ID
					if( isset($parsed_url['cPath']) ){
						$this->cPath = zen_db_prepare_input( $this->cleanup_url($parsed_url['cPath']) );
					}
				}
				/* START: DEALING WITH VANISHING GET PARAMETERS */
				
				#VALIDATE REDIRECT REQUEST
				$valid = false;
				if( $this->main_page == 'product_info' ){ #PRODUCT DESCRIPTION PAGE
					if( $this->id > 0 ){
						$valid = true;
					}
				}else if( $this->main_page == 'page' ){ #EZ PAGES
					if( $this->id > 0 ){
						$valid = true;
					}
				}else if( $this->main_page == 'index' ){ #CATEGORY / PRODUCT LISTING
					if( $this->cPath != '' ){
						$valid = true;
					}
				}else if( $this->main_page != '' ){ #DEFINES PAGES
					$valid = true;
				}
				
				if( $valid ){
					$this->process_redirect( $this->main_page, $this->id, $this->cPath );
				}else #NOT A VALID ZEN CART PAGE REDIRECT
				if( $this->main_page == 'product_info' or $this->main_page == 'index' ){
					#CHECK IF REDIRECT AVAILABLE IN CEON URI REDIRECT TABLE
					if ( $sniffer->table_exists( TABLE_CEON_URI_MAPPINGS ) ) {
						$sql = "SELECT `main_page`, `associated_db_id`
								FROM `" . TABLE_CEON_URI_MAPPINGS . "`
								WHERE (`uri` LIKE '" . DIR_WS_CATALOG . $this->current_url . "'
									OR `uri` LIKE '" . DIR_WS_HTTPS_CATALOG . $this->current_url . "')
									AND `current_uri` = '1'";
						$rec = $db->Execute( $sql );
						if( !$rec->EOF ){ #POTENTIAL REDIRECT FOUND
							$this->cPath = '';
							$this->id = 0;
							if( $rec->fields['main_page'] == 'index' ){
								$this->cPath = zen_get_generated_category_path_rev( $rec->fields['associated_db_id'] );
							}else{
								$this->id = $rec->fields['associated_db_id'];
							}
							$this->process_redirect( $rec->fields['main_page'], $this->id, $this->cPath );
						} #END: IF REDIRECTS FOUND
					} #END: IF CEON INSTALLED
				} #END: IF WHERE NOT EMPTY
			} #END: IF CURRENT URL NOT CATALOG ROOT
		} #END: IF SERVER REQUEST_URI
	} #END UPDATE FUNCTION

	function cleanup_url( $url ){
		$url = iconv( "UTF-8", "ASCII//IGNORE", $url );
		#REMOVE ALL QUOTES
		$url = str_replace( array("'", "%27", '"', "%22","`"), "", $url);
		
		#REMOVE UNSAFE CHARACTERS
		$url = preg_replace( '/[^A-Za-z0-9\%\&\=\-\;\+\.\?\_\/]/', '', $url );
		
		#REMOVE UNECESSARY CHARACTERS AT THE END
		$url = trim( $url );
		$url = trim( $url, '&' );
		$url = trim( $url, '_' );
		$url = trim( $url, '/' );
		$url = trim( $url, '\\' );
		#REPLACE ENCODED AMP WITH LITERAL
		$url = str_replace("&amp;", "&", $url);
		$url = filter_var( $url, FILTER_SANITIZE_URL );
		#ZEN CART INPUT SANITIZE
		$url = zen_db_prepare_input( $url );
		
		return $url;
	}
	
	function url_builder( $url ){
		$url = $this->cleanup_url( $url );
		if( $url == '' ) return $url; #RETURN EMPTY
		if( ENABLE_SSL ){
			$url = HTTPS_SERVER . DIR_WS_CATALOG . $url;
		}else{
			$url = HTTP_SERVER . DIR_WS_CATALOG . $url;
		}
		return $url;
	}
	
	function process_redirect( $main_page, $id = 0, $cPath = '' ){
		global $db;
		
		$where = '';
		if( $main_page == 'product_info' ){
			if( $id > 0 ){
				$where = " WHERE `original_url` LIKE '%main_page=product_info%'
							AND (`original_url` LIKE '%products_id=" . $id . "'
								OR `original_url` LIKE '%products_id=" . $id . "&%')";
			}
		}else if( $main_page == 'page' ){
			if( $id > 0 ){
				$where = " WHERE `original_url` LIKE '%main_page=page%'
							AND (`original_url` LIKE '%id=" . $id . "'
								OR `original_url` LIKE '%id=" . $id . "&%')";
			}
		}else if( $main_page == 'index' ){
			if( $cPath != '' ){
				$where = " WHERE `original_url` LIKE '%main_page=index%'
							AND (`original_url` LIKE '%cPath=" . $cPath . "'
								OR `original_url` LIKE '%cPath=" . $cPath . "&%')";
			}
		}else if( $main_page != '' ){
			$where = " WHERE `original_url` LIKE '%main_page=" . $main_page . "'
						OR `original_url` LIKE '%main_page=" . $main_page . "&%'";
		}
		
		if( $where != '' ){
			$sql = "SELECT `original_url`, `redirect_url`, `redirect_type`, `exact_match`
					FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
					" . $where . "
						AND `status` = '1'";
			$rec = $db->Execute( $sql );
			if( !$rec->EOF ){ #REDIRECT AVAILABLE
				$redirect_type = (int)$rec->fields['redirect_type'];
				$new_url = $this->url_builder( $rec->fields['redirect_url'] );
				if( $new_url != '' ){
					zen_redirect($new_url, $redirect_type );
				} #END: IF NEW URL NOT EMPTY
			} #END: IF REDIRECT AVAILABLE
		} #END: IF WHERE NOT EMPTY
	}
} #END: CLASS
?>