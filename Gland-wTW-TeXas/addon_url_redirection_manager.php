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
	require('includes/application_top.php');
	
	#ACTION
	if( isset($_GET['action']) and $_GET['action'] != ''){
		$action = $_GET['action'];
	}else if( isset($_POST['action']) and $_POST['action'] != ''){
		$action = $_POST['action'];
	}
	$feedback = '';
	
	#ORIGINAL URL
	$original_url = '';
	if( isset($_POST['txtOriginalURL']) ){
		if( trim($_POST['txtOriginalURL']) != '' ){
			$valid_url = validate_url( $_POST['txtOriginalURL'] );
			if( $valid_url ){
				$original_url = zen_db_prepare_input( prepare_url( $_POST['txtOriginalURL'] ) );
			}else{
				$feedback = FEEDBACK_ORIGINAL_URL_INVALID;
			}
		}else{
			$feedback = FEEDBACK_ORIGINAL_URL_MISSING;
		}
	}
	
	#REDIRECT URL
	$redirect_url = '';
	if( isset($_POST['txtRedirectURL']) ){
		if( trim($_POST['txtRedirectURL']) != '' ){
			$valid_url = validate_url( $_POST['txtRedirectURL'] );
			if( $valid_url ){
				$redirect_url = zen_db_prepare_input( prepare_url( $_POST['txtRedirectURL'] ) );
			}else{
				$feedback = FEEDBACK_REDIRECT_URL_INVALID;
			}
		}else{
			$feedback = FEEDBACK_REDIRECT_URL_MISSING;
		}
	}
	
	#REDIRECT TYPE
	$redirect_type = '301';
	$types_array = get_enum_values( TABLE_ADDON_URL_REDIRECTION_MANAGER, 'redirect_type' );
	if( isset($_POST['selRedirectType']) ){
		if( in_array($_POST['selRedirectType'], $types_array) ){
			$redirect_type = zen_db_prepare_input( $_POST['selRedirectType'] );
		}else{
			$feedback = FEEDBACK_INVALID_REDIRECT_TYPE;
		}
	}
	
	#EXACT MATCH
	$exact_match = 0;
	if( isset($_POST['cbxExactMatch']) ){
		if( $_POST['cbxExactMatch'] == 1  ){
			$exact_match = 1;
		}
	}
	
	#REDIRECT STATUS
	$redirect_active = 0;
	if( isset($_POST['cbxActive']) ){
		if( $_POST['cbxActive'] == 1 )
			$redirect_active = 1;
	}
	
	#REDIRECTION ID
	$rID = 0;
	if( isset($_GET['rID']) and (int)$_GET['rID'] > 0 ){
		$rID = zen_db_prepare_input( (int)$_GET['rID'] );
	}else if( isset($_POST['rID']) and (int)$_POST['rID'] > 0 ){
		$rID = zen_db_prepare_input( (int)$_POST['rID'] );
	}
	
	#KEYWORD SEARCH
	$keyword = '';
	if( isset($_POST['txtSearch']) AND $_POST['txtSearch'] != '' ){
		$keyword = zen_db_prepare_input( $_POST['txtSearch'] );
	}else if( isset($_GET['txtSearch']) AND $_GET['txtSearch'] != '' ){
		$keyword = zen_db_prepare_input( $_GET['txtSearch'] );
	}
	
	/*** START: RULES ***/
	#ORIGINAL AND REDIRECTION URLS MUST BE DIFFERENT
	if( $original_url != '' and $original_url == $redirect_url ){
		$feedback = FEEDBACK_SAME_ORIGINAL_REDIRECT_URLS;
	}
	#ORIGINAL URL CANNOT BE ROOT
	if( $original_url == "/" ){
		$feedback = FEEDBACK_ORIGINAL_IS_ROOT;
	}
	
	#REDIRECT CHAIN DETECTION
	$sql = "SELECT `original_url`
			FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
			WHERE `original_url` LIKE '" . $redirect_url . "'
				or `redirect_url` LIKE '" . $original_url . "'";
	$rec = $db->Execute($sql);
	if( !$rec->EOF ){
		$feedback = FEEDBACK_MULTIPLE_REDIRECTS;
	}
	
	#ORIGINAL URL ALREADY EXISTS
	$sql = "SELECT `original_url`
			FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
			WHERE `original_url` LIKE '" . $original_url . "'";
	if( $rID > 0 ){ #IF UPDATE
		$sql .= " AND `redirection_id` != '" . $rID . "'";
	}
	$rec = $db->Execute($sql);
	if( !$rec->EOF ){
		$feedback = FEEDBACK_DUPLICATE_ORIGINAL_URL;
	}
	/*** END: RULES ***/
	
	#DEFINE DEFAULT RIGHT PANE HEAD TITLE
	$pane_head_title = PANE_HEAD_TITLE_SHOW_SETTINGS;
	
	#EXECUTE AN ACTION
	if (zen_not_null($action)) {
		switch ($action) {
			case 'insert':
				if( $feedback == '' ){
					$sql = "INSERT INTO `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
							(`original_url`, `redirect_url`, `redirect_type`, `exact_match`, `status`, `added_datetime`, `added_by`)
							VALUES
							('" . $original_url . "', '" . $redirect_url . "', '" . $redirect_type . "', '" . $exact_match . "', '" . $redirect_active . "', '" . date("Y-m-d H:i:s") . "', '" . (int)$_SESSION['admin_id'] . "')";
					$db->Execute($sql);
					$rID = $db->Insert_ID();
					zen_redirect( zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, 'rID=' . $rID) );
				}
				break;
			case 'update':
				if( $feedback == '' ){
					if( $rID > 0 ){
						$sql = "UPDATE `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
								SET 
									`original_url` = '" . $original_url . "',
									`redirect_url` = '" . $redirect_url . "',
									`redirect_type` = '" . $redirect_type . "',
									`exact_match` = '" . $exact_match . "',
									`status` = '" . $redirect_active . "',
									`added_datetime` = '" . date("Y-m-d H:i:s") . "',
									`added_by` = '" . (int)$_SESSION['admin_id'] . "'
								WHERE `redirection_id` = '" . $rID . "'";
						$db->Execute($sql);
						$parameters = 'rID=' . $rID;
						if( isset( $_POST['page'] ) and (int)$_POST['page'] > 0 ){
							$parameters = '&page=' . (int)$_POST['page'];
						}
						
						zen_redirect( zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, $parameters) );
					}
				}
				break;
			case 'delete':
				if( $feedback == '' and $rID > 0 ){
					if( $rID > 0 ){
						$sql = "DELETE FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
								WHERE `redirection_id` = '" . $rID . "'";
						$db->Execute($sql);
						$parameters = '';
						if( isset( $_POST['page'] ) and (int)$_POST['page'] > 0 ){
							$parameters = 'page=' . (int)$_POST['page'];
						}
						zen_redirect( zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, $parameters) );
					}
				}
				break;
			case 'status_on':
				if( $rID > 0 ){
					$sql = "UPDATE `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
								SET `status` = true
								WHERE `redirection_id` = '" . $rID . "'";
						$db->Execute($sql);
				}
				break;
			case 'status_off':
				if( $rID > 0 ){
					$sql = "UPDATE `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
								SET `status` = false
								WHERE `redirection_id` = '" . $rID . "'";
						$db->Execute($sql);
				}
				break;
			case 'exact_on':
				if( $rID > 0 ){
					$sql = "UPDATE `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
								SET `exact_match` = true
								WHERE `redirection_id` = '" . $rID . "'";
						$db->Execute($sql);
				}
				break;
			case 'exact_off':
				if( $rID > 0 ){
					$sql = "UPDATE `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
								SET `exact_match` = false
								WHERE `redirection_id` = '" . $rID . "'";
						$db->Execute($sql);
				}
				break;
			case 'delete_redirect': #CONFIRMATION PAGE
				$pane_head_title = PANE_HEAD_TITLE_CONFIRM_DELETE;
				break;
			case 'edit_redirect': #FORM PAGE
				$pane_head_title = PANE_HEAD_TITLE_EDIT_SETTINGS;
				break;
			case 'add_redirect': #FORM PAGE
				$pane_head_title = PANE_HEAD_TITLE_ADD_SETTINGS;
				break;
			default: #DEFAULT TITLE ALREADY SET
				break;
		}
	}else{ #NO ACTION
		$feedback = '';
	}
	
	#ADD BUTTON
	$add_button = tabs(3) . '<a href="' . zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, 'action=add_redirect') . '">
				<button type="button" class="btn btn-success btn-sm" aria-label="Left Align">
					<span class="glyphicon glyphicon glyphicon glyphicon-plus" aria-hidden="true"></span> ' . BUTTON_ADD . '
				</button>
			</a>';
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
<style type="text/css">
	.feedback{
		max-width:50%;
		margin:20px auto;
	}
	#keyword{
		margin:0;
		padding:2px;
		background-color:#F8F8F8;
		font-size:1.3em;
		font-weight:bold;
		color:gray;
	}
	.chop-text{
		max-width:280px;
		overflow:hidden;
		text-overflow:ellipsis;
		white-space:nowrap;
		display:table-cell;
	}
</style>
</head>
<body onLoad="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<div class="container-fluid">
	<div class="row">
		<?php echo zen_draw_form('frmSearch', FILENAME_ADDON_URL_REDIRECTION_MANAGER, '', 'post'); ?>
		<div class="col-sm-6 col-md-8">
			<h1><?php echo HEADING_TITLE; ?></h1>
		</div>
		<div class="col-sm-6 col-md-4">
			<div class="col-xs-12 col-md-2 control-label">
				<?php echo zen_draw_label(LBL_SEARCH, 'txtSearch', 'class="pull-right"'); ?>
			</div>
			<div class="col-xs-12 col-md-10">
<?php
	echo zen_draw_input_field('txtSearch', '', 'maxlength="128" class="form-control"') . "\n";
	echo zen_draw_hidden_field("action","search") . "\n";
?>
			</div>
<?php
	if( $keyword != '' ){
?>
			<div id="keyword"><?php echo $keyword; ?> [<a href="<?php echo zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, ''); ?>">X</a>]</div>
<?php
	}
?>
		</div>
		</form>
	</div>
	
	<div class="row">
        <!-- body_text //-->
        <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
<?php
	#CHECK IF THERE ARE ANY REDIRECTS AVAILABLE TO SHOW
	$sql = "SELECT `redirection_id`, `original_url`, `redirect_url`, `redirect_type`, `exact_match`, `status`
			 FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`";
	if( $keyword != '' ){
		$sql .= " WHERE `original_url` LIKE '%" . $keyword . "%'
					OR `redirect_url` LIKE '%" . $keyword . "%'";
	}
	$sql .= " ORDER BY `redirection_id` ASC";
	#LOAD INFO FOR PAGINATION
	$rec = $db->Execute($sql);
	if( !$rec->EOF and $rec->RecordCount() > 0 ){
		$RecCount = $rec->RecordCount();
		#ENSURE PAGE IS KNOWN
		if ( !isset( $_GET['page'] ) or (int)$_GET['page'] <= 0) {
			$check_count=1;
			if ($rec->RecordCount() > LIMIT_MAX_ROWS) {
				while (!$rec->EOF) {
					if ($rec->fields['orders_id'] == $_GET['oID']) {
						break;
					}
					$check_count++;
					$rec->MoveNext();
				}
				$_GET['page'] = round((($check_count/LIMIT_MAX_ROWS)+(fmod_round($check_count,LIMIT_MAX_ROWS) !=0 ? .5 : 0)),0);
			} else {
				$_GET['page'] = 1;
			}
		}
		
		$pages_split = new splitPageResults( $_GET['page'], LIMIT_MAX_ROWS, $sql, $recCount );
?>
			<table class="table table-hover" style="margin-bottom:0;">
				<thead>
				<tr class="dataTableHeadingRow">
					<td class="dataTableHeadingContent" style="width:40px;"><?php echo TBL_HEAD_ID; ?></td>
					<td class="dataTableHeadingContent" style="min-width:300px;"><?php echo TBL_HEAD_ORIGINAL_URL; ?></td>
					<td class="dataTableHeadingContent" style="min-width:300px;"><?php echo TBL_HEAD_REDIRECT_URL; ?></td>
					<td class="dataTableHeadingContent" style="width:50px; text-align:center;"><?php echo TBL_HEAD_REDIRECT_TYPE; ?></td>
					<td class="dataTableHeadingContent" style="width:80px; text-align:center;"><?php echo TBL_HEAD_EXACT_MATCH; ?></td>
					<td class="dataTableHeadingContent" style="width:50px; text-align:center;"><?php echo TBL_HEAD_REDIRECT_STATUS; ?></td>
					<td class="dataTableHeadingContent" style="width:50px; text-align:center;"><?php echo TBL_HEAD_ACTION; ?></td>
				</tr>
				</thead>
				<tbody>
<?php
		#LOAD RECORDS FROM MODIFIED QUERY WITH LIMIT SET
		$rec = $db->Execute($sql);
		while( !$rec->EOF ){
			if( $rID == 0 ){
				$rID = $rec->fields['redirection_id']; #DEFAULT
			}
			#LOAD PARAMETERS
			$parameters = '';
			#REDIRECT ID
			if( $rID > 0 ){
				$parameters = 'rID=' . $rec->fields['redirection_id'];
			}
			#PAGE NUMBER
			if( isset( $_GET['page'] ) and (int)$_GET['page'] > 0 ){
				if( $parameters == '' ){
					$parameters = 'page=' . (int)$_GET['page'];
				}else{
					$parameters .= '&page=' . (int)$_GET['page'];
				}
			}
			#KEYWORD
			if( $keyword != '' ){
				if( $parameters == '' ){
					$parameters = 'txtSearch=' . $keyword;
				}else{
					$parameters .= '&txtSearch=' . $keyword;
				} 
			}
			#ACTION AND STYLE
			$row_class = ' class="dataTableRow"';
			if( $rec->fields['redirection_id'] == $rID ){
				$row_class = ' id="defaultSelected" class="dataTableRowSelected"';
				$action_icon = zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ICON_EDIT);
			}else{
				$row_class = ' class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, "rID=" . $rec->fields['redirection_id']) . $parameters . '\'"';
				$action_icon = '<a href="' . zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, "rID=" . $rec->fields['redirection_id']) . $parameters . '">' . zen_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>';
			}
			
			#EXACT MATCH
			$exact_match = zen_draw_form('frmExactMatch', FILENAME_ADDON_URL_REDIRECTION_MANAGER, $parameters, 'post');
			if( $rec->fields['exact_match'] == 1 ){
				$exact_match .= '<input type="image" src="' . DIR_WS_IMAGES . 'icon_green_on.gif" title="' . IMAGE_ICON_EXACT_ON . '" />';
				$exact_match .= zen_draw_hidden_field('action', 'exact_off') . "\n";
			}else{
				$exact_match .= '<input type="image" src="' . DIR_WS_IMAGES . 'icon_red_on.gif" title="' . IMAGE_ICON_EXACT_OFF . '" />';
				$exact_match .= zen_draw_hidden_field('action', 'exact_on') . "\n";
			}
			$exact_match .= zen_draw_hidden_field('rID', $rec->fields['redirection_id']) . "\n";
			$exact_match .= '</form>' . "\n";
			
			#STATUS
			$status_icon = zen_draw_form('frmStatusChange', FILENAME_ADDON_URL_REDIRECTION_MANAGER, $parameters, 'post');
			if( $rec->fields['status'] == 1 ){
				$status_icon .= '<input type="image" src="' . DIR_WS_IMAGES . 'icon_green_on.gif" title="' . IMAGE_ICON_STATUS_ON . '" />';
				$status_icon .= zen_draw_hidden_field('action', 'status_off') . "\n";
			}else{
				$status_icon .= '<input type="image" src="' . DIR_WS_IMAGES . 'icon_red_on.gif" title="' . IMAGE_ICON_STATUS_OFF . '" />';
				$status_icon .= zen_draw_hidden_field('action', 'status_on') . "\n";
			}
			$status_icon .= zen_draw_hidden_field('rID', $rec->fields['redirection_id']) . "\n";
			$status_icon .= '</form>' . "\n";
?>
				<tr<?php echo $row_class;?>>
					<td class="dataTableContent" valign="top"><?php echo $rec->fields['redirection_id']; ?></td>
					<td class="dataTableContent chop-text" valign="top"><?php echo $rec->fields['original_url']; ?></td>
					<td class="dataTableContent chop-text" valign="top"><?php echo $rec->fields['redirect_url']; ?></td>
					<td class="dataTableContent" valign="top" align="center"><?php echo $rec->fields['redirect_type']; ?></td>
					<td class="dataTableContent" valign="top" align="center"><?php echo $exact_match; ?></td>
					<td class="dataTableContent" valign="top" align="center"><?php echo $status_icon; ?></td>
					<td class="dataTableContent" valign="top" align="center">
						<?php echo $action_icon; ?>
					</td>
				</tr>
<?php
			$rec->MoveNext();
		} #END WHILE
?>
				</tbody>
			</table>
			<div class="row">
				<div class="col-xs-6 smallText">
<?php
		echo $pages_split->display_count($RecCount, LIMIT_MAX_ROWS, (int)$_GET['page'], PAGINATION_LABEL);
		
		$pars = '';
		if( $keyword != '' ){
			if( $pars == '' ){
				$pars = 'txtSearch=' . urlencode( $keyword );
			}else{
				$pars .= '&txtSearch=' . urlencode( $keyword );
			}
		}
		if( $action != '' ){
			if( $pars == '' ){
				$pars = 'action=' . $action;
			}else{
				$pars .= '&action=' . $action;
			}
		}
?>
				</div>
				<div class="col-xs-6 smallText">
					<?php echo $pages_split->display_links($RecCount, LIMIT_MAX_ROWS, $highest_page_number, $_GET['page'], $pars); ?>
				</div>
			</div>
			<br />
<?php
		}else{ #NO REDIRECTS IN DATABASE
		if( $keyword != '' ){
			echo '<h4>' . FEEDBACK_NO_SEARCH_RESULTS . '</h4>' . "\n";
		}else{
			echo '<h4>' . FEEDBACK_NO_REDIRECTS . '</h4>' . "\n";
		}
	}
	echo $add_button;
	
	if( $feedback != '' ){
		echo '<div class="panel panel-warning feedback">
			<div class="panel-heading">
				<h3 class="panel-title"><span class="glyphicon glyphicon glyphicon-warning-sign" aria-hidden="true"></span> ' . FEEDBACK_TITLE_DB_OPERATION_FAILED . '</h3>
			</div>
			<div class="panel-body">' . $feedback . '</div>
		</div>';
	}
?>
		</div>
		<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
			<div class="row infoBoxHeading"><h4><?php echo $pane_head_title; ?></h4></div>
<?php
	switch( $action ){
		case 'add_redirect':
			$form = get_redirect_form();
			echo $form;
			break;
		case 'edit_redirect':
			$form = get_redirect_form( $rID );
			echo $form;
			break;
		case 'delete_redirect':
			if( $rID > 0 ){
?>
			<div class="row infoBoxContent"><br /><?php echo CONFIRM_DELETE_REDIRECT; ?></div>
<?php
				$sql = "SELECT `redirection_id`, `original_url`, `redirect_url`, `redirect_type`, `exact_match`
						FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
						WHERE `redirection_id` = '" . $rID . "'";
				$rec = $db->Execute($sql);
				if( !$rec->EOF ){
?>
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_ID . '</b> ' . $rec->fields['redirection_id']; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_ORIGINAL_URL . '</b><br />' . '<span class="chop-text">' . $rec->fields['original_url'] . '</span>'; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_URL . '</b><br />' . '<span class="chop-text">' . $rec->fields['redirect_url'] . '</span>'; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_TYPE . '</b> ' . $rec->fields['redirect_type']; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_EXACT . '</b> ' . ($rec->fields['exact_match']=='1'?'Yes':'No'); ?></div>
<?php
				}
?>
<?php
				echo zen_draw_form('frmDeleteRedirect', FILENAME_ADDON_URL_REDIRECTION_MANAGER, '', 'post');
				echo zen_draw_hidden_field('rID', $rID);
				echo zen_draw_hidden_field('action', 'delete');
				if( isset($_GET['page']) && $_GET['page']!='' ){
					echo zen_draw_hidden_field('page', (int)$_GET['page'] );
				}
				if( $keyword != '' ){
					echo zen_draw_hidden_field('keyword', keyword );
				}
?>
			<div class="row center infoBoxContent">
				<button type="submit" class="btn btn-danger btn-sm" aria-label="Left Align">
					<span class="glyphicon glyphicon glyphicon glyphicon-remove" aria-hidden="true"></span> <?php echo BUTTON_DELETE; ?>
				</button>
				
				<a href="<?php echo zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, ''); ?>">
					<button type="button" class="btn btn-info btn-sm" aria-label="Left Align">
						<span class="glyphicon glyphicon glyphicon glyphicon-floppy-remove" aria-hidden="true"></span> <?php echo BUTTON_CANCEL; ?>
					</button>
				</a>
			</div>
			</form>
<?php
				$pars = '';
			}else{
				$feedback = FEEDBACK_REDIRECT_ID_MISSING;
			}
			break;
		default:
			if( $rID > 0 ){
				$sql = "SELECT `redirection_id`, `original_url`, `redirect_url`, `redirect_type`, `exact_match`, `status`
						FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
						WHERE `redirection_id` = '" . $rID . "'";
				$rec = $db->Execute($sql);
				if( !$rec->EOF ){
					$original_link = add_domain($rec->fields['original_url']);
?>
			<div class="row infoBoxContent"><br /><?php echo '<b>' . LBL_REDIRECT_ID . '</b> ' . $rec->fields['redirection_id']; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_ORIGINAL_URL . '</b> <a href="' . add_domain($rec->fields['original_url']) . '" target="_blank"><span class="chop-text">' . $rec->fields['original_url'] . '</span></a>'; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_URL . '</b> <a href="' . add_domain($rec->fields['redirect_url']) . '" target="_blank"><span class="chop-text">' . $rec->fields['redirect_url'] . '</span></a>'; ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_TYPE . '</b> ' . $rec->fields['redirect_type']; ?></div>
			
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_EXACT . '</b> ' . ($rec->fields['exact_match'] == 1 ? INFO_YES : INFO_NO); ?></div>
			
			<div class="row infoBoxContent"><?php echo '<b>' . LBL_REDIRECT_STATUS . '</b> ' . ($rec->fields['status'] == 1 ? INFO_ACTIVE : INFO_DISABLED); ?></div>
<?php
					#BUTTON PARAMETERS
					$pars = '&rID=' . $rec->fields['redirection_id'];
					if( isset($_GET['page']) and (int)$_GET['page'] > 1 ){
						$pars .= '&page=' . (int)$_GET['page'];
					}
					if( $keyword != '' ){
						$pars .= '&txtSearch=' . urlencode($keyword);
					}
?>
			<div class="row center infoBoxContent">
				<a href="<?php echo zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, 'action=edit_redirect' . $pars); ?>">
					<button type="button" class="btn btn-info btn-sm" aria-label="Left Align">
						<span class="glyphicon glyphicon glyphicon glyphicon-pencil" aria-hidden="true"></span> <?php echo BUTTON_EDIT; ?>
					</button>
				</a>
				<a href="<?php echo zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, 'action=delete_redirect' . $pars); ?>">
					<button type="button" class="btn btn-danger btn-sm" aria-label="Left Align">
						<span class="glyphicon glyphicon glyphicon glyphicon-remove" aria-hidden="true"></span> <?php echo BUTTON_DELETE; ?>
					</button>
				</a>
			</div>
<?php
				}
			} else {
				if( $keyword != '' ){
					echo FEEDBACK_NO_SEARCH_RESULTS;
				}else{
					echo FEEDBACK_NO_REDIRECTS;
				}
			}
			break;
	}
?>
		</div>
	</div>
	<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</div>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
<?php
	function get_enum_values( $table, $field ){
		global $db;
		
		$enum = array();
		$sql = "SELECT SUBSTRING(COLUMN_TYPE,5) AS type
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = '" . DB_DATABASE . "'
				AND TABLE_NAME = '{$table}'
				AND COLUMN_NAME='{$field}'";
		$rec = $db->Execute( $sql );
		if( !$rec->EOF ){
			$options = trim($rec->fields['type'], "()");
			$options = str_replace( "'", "", $options );
			$enum = explode( ",", $options );
		}
		
		return $enum;
	}
	
	function tabs($num){
		$tabs = '';
		for( $i = 0; $i < $num; $i++ ){
			$tabs .= "\t";
		}
		return $tabs;
	}
	
	function get_redirect_form( $rID = '' ){
		global $db, $keyword;
		
		$original_url = '';
		$redirect_url = '';
		$redirect_type = '';
		$exact_match = false;
		$status = true;
		
		$output = zen_draw_form('frmRedirectMgr', FILENAME_ADDON_URL_REDIRECTION_MANAGER, '', 'post');
		if( $rID != '' and (int)$rID > 0 ){
			$output .= zen_draw_hidden_field('action', 'update') . "\n";
			$output .= zen_draw_hidden_field('rID', (int)$rID) . "\n";
			if( isset( $_GET['page'] ) ){
				$output .= zen_draw_hidden_field( 'page', (int)$_GET['page'] ) . "\n";
			}
			$sql = "SELECT `redirection_id`, `original_url`, `redirect_url`, `redirect_type`, `exact_match`, `status`
					FROM `" . TABLE_ADDON_URL_REDIRECTION_MANAGER . "`
					WHERE `redirection_id` = '" . (int)$rID . "'";
			$rec = $db->Execute($sql);
			if( !$rec->EOF ){
				$original_url = $rec->fields['original_url'];
				$redirect_url = $rec->fields['redirect_url'];
				$redirect_type = $rec->fields['redirect_type'];
				$exact_match = $rec->fields['exact_match'];
				$status = ($rec->fields['status'] == 1 ? true : false);
			}
		}else{
			$output .= zen_draw_hidden_field('action', 'insert') . "\n";
		}
		
		#ORIGINAL URL TEXT BOX
		$output .= '<div class="row infoBoxContent"><br />' . zen_draw_label(LBL_ORIGINAL_URL, 'txtOriginalURL') . "\n";
		$output .= zen_draw_input_field('txtOriginalURL', $original_url, zen_set_field_length(TABLE_ADDON_URL_REDIRECTION_MANAGER, 'original_url') . ' class="form-control"') . '</div>' . "\n";
		
		#REDIRECT URL TEXT BOX
		$output .= '<div class="row infoBoxContent">' . zen_draw_label(LBL_REDIRECT_URL, 'txtRedirectURL') . "\n";
		$output .= zen_draw_input_field('txtRedirectURL', $redirect_url, zen_set_field_length(TABLE_ADDON_URL_REDIRECTION_MANAGER, 'redirect_url') . ' class="form-control"') . '</div>' . "\n";
		
		#REDIRECT TYPE SELECTOR
		$redirect_options = array();
		$redirects = get_enum_values( TABLE_ADDON_URL_REDIRECTION_MANAGER, 'redirect_type' );
		foreach($redirects as $redID){
			$redirect_name = '';
			switch($redID){
				case '301':
					$redirect_name = '301 - Permanent (default)';
					break;
				case '302':
					$redirect_name = '302 - Temporary';
					break;
				case '303':
					$redirect_name = '303 - Temporary, POST transled as GET';
					break;
				case '307':
					$redirect_name = '307 - Temporary, No Translation';
					break;
				case '308':
					$redirect_name = '308 - Permanent, No Translation';
					break;
				default:
					break;
			}
			$redirect_options[] = array( 'id' => $redID, 'text' => $redirect_name );
		}
		$output .= '<div class="row infoBoxContent">' . zen_draw_label(LBL_REDIRECT_TYPE, 'selRedirectType') . "\n";
		$output .= zen_draw_pull_down_menu('selRedirectType', $redirect_options, $redirect_type,'class="form-control"') . '</div>' . "\n";
		
		#EXACT MATCH CHECK BOX
		$output .= '<div class="row infoBoxContent">' . zen_draw_checkbox_field('cbxExactMatch', '1', $exact_match, '', 'style="width:20px;height:20px;"') . ' ' . zen_draw_label(LBL_REDIRECT_EXACT, 'cbxExactMatch') . '</div>' . "\n";
		
		$output .= '<div class="row infoBoxContent">' . zen_draw_checkbox_field('cbxActive', '1', $status, '', 'style="width:20px;height:20px;"') . ' ' . zen_draw_label(INFO_ACTIVE, 'cbxActive') . '</div>' . "\n";
		
		#ADD BUTTON
		$output .= '<div class="row infoBoxContent">';
		$output .= '<button type="submit" class="btn btn-success btn-sm" aria-label="Left Align">
					<span class="glyphicon glyphicon glyphicon glyphicon-save" aria-hidden="true"></span> ' . IMAGE_SAVE . '</button>';
		
		#CANCEL BUTTON
		$output .= get_cancel_button( $_GET['page'], $rID, $keyword );
		$output .= '</div>';
		
		$output .= '</form>';
		
		return $output;
	}
	
	function validate_url( $url ){
		$url = cleanup_url($url);
		if( $url != '' ){
			$url = add_domain($url);
			$url = filter_var($url, FILTER_SANITIZE_URL);
			if ( filter_var( $url, FILTER_VALIDATE_URL ) ) { #IF VALID URL
				if( stripos($url, HTTP_CATALOG_SERVER) !== false ){ #IF LOCAL URL
					return true;
				}else if( stripos($url, HTTPS_CATALOG_SERVER) !== false ){
					return true;
				}
			}
		}
		
		return false;
	}
	
	function cleanup_url($url){
		#REMOVE ALL QUOTES
		$url = str_replace( array("'", "%27", '"', "%22","`"), "", $url);
		
		#REMOVE UNSAFE CHARACTERS
		$url = preg_replace( '/[^A-Za-z0-9\%\&\=\-\;\+\.\?\_\/\:]/', '', $url );
		
		#REMOVE UNECESSARY CHARACTERS AT THE END
		$url = trim($url);
		$url = str_replace("&amp;", "&", $url);
		$url = trim($url, '&');
		$url = trim($url, '_');
		$url = trim($url, '/');
		$url = trim($url, '\\');
		
		#REMOVE SESSION ID IF ANY
		if( strpos($url,'zenid') ){
			$url = substr($url, 0, strpos($url, 'zenid') - 1);
		}
		$url = filter_var( $url, FILTER_SANITIZE_URL );
		#ZEN CART INPUT SANITIZE
		$url = zen_db_prepare_input( $url );
		
		return $url;
	}
	
	function prepare_url( $url ){
		$url = cleanup_url($url);
		if( $url != '' ){
			$url = add_domain($url);
			#SANITIZE
			$url = filter_var($url, FILTER_SANITIZE_URL);
			#REMOVE DOMAIN AND CATALOG FOLDER (IF ANY)
			if( stripos($url, HTTP_CATALOG_SERVER) !== false ){ #NO SSL
				$url = substr($url, strlen( trim(HTTP_CATALOG_SERVER . DIR_WS_CATALOG, "/")));
			}else if( stripos($url, HTTPS_CATALOG_SERVER) !== false ){ #WITH SSL
				$url = substr($url, strlen( trim(HTTPS_CATALOG_SERVER . DIR_WS_CATALOG, "/")));
			}
		}
		$url = cleanup_url($url);
		
		return $url;
	}
	
	function add_domain($url){
		if( strtolower(substr(trim($url), 0, 4)) != 'http' ){ #IF NO DOMAIN
			if(ENABLE_SSL_CATALOG){
				$url = HTTPS_CATALOG_SERVER . DIR_WS_CATALOG . trim($url);
			}else{
				$url = HTTP_CATALOG_SERVER . DIR_WS_CATALOG . trim($url);
			}
		}
		return $url;
	}
	
	function get_cancel_button( $page = 1, $rID = 0, $keyword = '' ){ #CANCEL BUTTON
		$pars = '';
		if( (int)$page > 1 ){
			$pars = 'page=' . (int)$page;
		}
		if( $rID != '' and $rID > 0 ){
			if( $pars == '' ){
				$pars = 'rID=' . $rID;
			}else{
				$pars .= '&rID=' . $rID;
			}
		}
		if( $keyword != '' ){
			$pars .= '&txtSearch=' . $keyword;
		}
		$output = '<a href="' . zen_href_link(FILENAME_ADDON_URL_REDIRECTION_MANAGER, $pars) . '">
				<button type="button" class="btn btn-primary btn-sm pull-right" aria-label="Left Align">
					<span class="glyphicon glyphicon glyphicon-floppy-remove" aria-hidden="true"></span> ' . BUTTON_CANCEL . '
				</button>
			</a>';
		return $output;
	}
?>
