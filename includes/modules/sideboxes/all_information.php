<?php
/**
 *
 * @package templateSystem
 * @copyright Copyright 2003-2007 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

  $zco_notifier->notify('NOTIFY_START_EZPAGES_SIDEBOX');

  // test if sidebox should display
  if (EZPAGES_STATUS_SIDEBOX == '1' or (EZPAGES_STATUS_SIDEBOX== '2' and (strstr(EXCLUDE_ADMIN_IP_FOR_MAINTENANCE, $_SERVER['REMOTE_ADDR'])))) {
    if (isset($var_linksList)) {
      unset($var_linksList);
    }
    $page_query = $db->Execute("select * from " . TABLE_EZPAGES . " where status_sidebox = 1 and sidebox_sort_order > 0 order by sidebox_sort_order, pages_title");
    if ($page_query->RecordCount()>0) {
      $title =  BOX_HEADING_EZPAGES;
      $box_id =  'ezpages';
      $rows = 0;
      while (!$page_query->EOF) {
        $rows++;
        $page_query_list_sidebox[$rows]['id'] = $page_query->fields['pages_id'];
        $page_query_list_sidebox[$rows]['name'] = $page_query->fields['pages_title'];
        $page_query_list_sidebox[$rows]['altURL']  = "";
        switch (true) {
          // external link new window or same window
          case ($page_query->fields['alt_url_external'] != ''):
          $page_query_list_sidebox[$rows]['altURL']  = $page_query->fields['alt_url_external'];
          break;
          // internal link new window
          case ($page_query->fields['alt_url'] != '' and $page_query->fields['page_open_new_window'] == '1'):
          $page_query_list_sidebox[$rows]['altURL']  = (substr($page_query->fields['alt_url'],0,4) == 'http') ?
          $page_query->fields['alt_url'] :
          ($page_query->fields['alt_url']=='' ? '' : zen_href_link($page_query->fields['alt_url'], '', ($page_query->fields['page_is_ssl']=='0' ? 'NONSSL' : 'SSL'), true, true, true));
          break;
          // internal link same window
          case ($page_query->fields['alt_url'] != '' and $page_query->fields['page_open_new_window'] == '0'):
          $page_query_list_sidebox[$rows]['altURL']  = (substr($page_query->fields['alt_url'],0,4) == 'http') ?
          $page_query->fields['alt_url'] :
          ($page_query->fields['alt_url']=='' ? '' : zen_href_link($page_query->fields['alt_url'], '', ($page_query->fields['page_is_ssl']=='0' ? 'NONSSL' : 'SSL'), true, true, true));
          break;
        }

        // if altURL is specified, use it; otherwise, use EZPage ID to create link
        $page_query_list_sidebox[$rows]['link'] = ($page_query_list_sidebox[$rows]['altURL'] =='') ?
        zen_href_link(FILENAME_EZPAGES, 'id=' . $page_query->fields['pages_id'] . ($page_query->fields['toc_chapter'] > 0 ? '&chapter=' . $page_query->fields['toc_chapter'] : ''), ($page_query->fields['page_is_ssl']=='0' ? 'NONSSL' : 'SSL')) :
        $page_query_list_sidebox[$rows]['altURL'];
        $page_query_list_sidebox[$rows]['link'] .= ($page_query->fields['page_open_new_window'] == '1' ? '" target="_blank' : '');
        $page_query->MoveNext();
      }

      $title_link = false;

      $var_linksList = $page_query_list_sidebox;

      require($template->get_template_dir('tpl_all_information.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_all_information.php');

      $zco_notifier->notify('NOTIFY_END_EZPAGES_SIDEBOX');
 //     require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
    }
  } // test for display

  $zco_notifier->notify('NOTIFY_END_EZPAGES_SIDEBOX');









  

// test if box should display

  unset($more_information);

// test if links should display
  if (DEFINE_PAGE_2_STATUS <= 1) {
    $more_information[] = '<a href="' . zen_href_link(FILENAME_PAGE_2) . '">' . BOX_INFORMATION_PAGE_2 . '</a>';
  }
  if (DEFINE_PAGE_3_STATUS <= 1) {
    $more_information[] = '<a href="' . zen_href_link(FILENAME_PAGE_3) . '">' . BOX_INFORMATION_PAGE_3 . '</a>';
  }
  if (DEFINE_PAGE_4_STATUS <= 1) {
    $more_information[] = '<a href="' . zen_href_link(FILENAME_PAGE_4) . '">' . BOX_INFORMATION_PAGE_4 . '</a>';
  }

  if (DEFINE_PRINTLABELS_STATUS <= 1) {
    $more_information[] = '<a href="' . zen_href_link(FILENAME_PRINTLABELS) . '">' . BOX_INFORMATION_PRINTLABELS . '</a>';
  }
  if (DEFINE_LABELTEMPLATES_STATUS <= 1) {
    $more_information[] = '<a href="' . zen_href_link(FILENAME_LABELTEMPLATES) . '">' . BOX_INFORMATION_LABELTEMPLATES . '</a>';
  }

// insert additional links below to add to the more_information box
// Example:
//    $more_information[] = '<a href="' . zen_href_link(FILENAME_DEFAULT) . '">' . 'TESTING' . '</a>';


// only show if links are active
  if (sizeof($more_information) > 0) {
    require($template->get_template_dir('tpl_all_information.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_all_information.php');

//    $title =  BOX_HEADING_MORE_INFORMATION;
//    $title_link = false;
//    require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
  }











  unset($information);

  if (DEFINE_SHIPPINGINFO_STATUS <= 1) {
    $information[] = '<a href="' . zen_href_link(FILENAME_SHIPPING) . '">' . BOX_INFORMATION_SHIPPING . '</a>';
  }
  if (DEFINE_PRIVACY_STATUS <= 1) {
    $information[] = '<a href="' . zen_href_link(FILENAME_PRIVACY) . '">' . BOX_INFORMATION_PRIVACY . '</a>';
  }
  if (DEFINE_CONDITIONS_STATUS <= 1) {
    $information[] = '<a href="' . zen_href_link(FILENAME_CONDITIONS) . '">' . BOX_INFORMATION_CONDITIONS . '</a>';
  }
  if (DEFINE_CONTACT_US_STATUS <= 1) {
    $information[] = '<a href="' . zen_href_link(FILENAME_CONTACT_US, '', 'SSL') . '">' . BOX_INFORMATION_CONTACT . '</a>';
  }

// forum/bb link:
  if (!empty($external_bb_url) && !empty($external_bb_text)) {
    $information[] = '<a href="' . $external_bb_url . '" target="_blank">' . $external_bb_text . '</a>';
  }

  if (DEFINE_SITE_MAP_STATUS <= 1) {
    $information[] = '<a href="' . zen_href_link(FILENAME_SITE_MAP) . '">' . BOX_INFORMATION_SITE_MAP . '</a>';
  }

  // only show GV FAQ when installed
  if (MODULE_ORDER_TOTAL_GV_STATUS == 'true') {
    $information[] = '<a href="' . zen_href_link(FILENAME_GV_FAQ) . '">' . BOX_INFORMATION_GV . '</a>';
  }
  // only show Discount Coupon FAQ when installed
  if (DEFINE_DISCOUNT_COUPON_STATUS <= 1 && MODULE_ORDER_TOTAL_COUPON_STATUS == 'true') {
    $information[] = '<a href="' . zen_href_link(FILENAME_DISCOUNT_COUPON) . '">' . BOX_INFORMATION_DISCOUNT_COUPONS . '</a>';
  }

  if (SHOW_NEWSLETTER_UNSUBSCRIBE_LINK == 'true') {
    $information[] = '<a href="' . zen_href_link(FILENAME_UNSUBSCRIBE) . '">' . BOX_INFORMATION_UNSUBSCRIBE . '</a>';
  }

  require($template->get_template_dir('tpl_all_information.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_all_information.php');

//  $title =  BOX_HEADING_INFORMATION;
//  $title_link = false;

  require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
