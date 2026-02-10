<?php

/**

 * Page Template

 *

 * @package templateSystem

 * @copyright Copyright 2003-2005 Zen Cart Development Team

 * @copyright Portions Copyright 2003 osCommerce

 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0

 * @version $Id: tpl_printlabels_default.php 3464 2006-04-19 00:07:26Z ajeh $

 */

?>

<div class="centerColumn" id="printLabels">

<h1 id="printLabelsHeading"><?php echo HEADING_TITLE; ?></h1>



<?php if (DEFINE_PRINTLABELS_STATUS >= 1 and DEFINE_PRINTLABELS_STATUS <= 2) { ?>

<div id="printLabelsMainContent" class="content">

<?php

/**

 * require the html_define for the printLabels page

 */

  require($define_page);

?>

</div>

<?php } ?>



<div class="buttonRow back"><?php echo zen_back_link() . zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) . '</a>'; ?></div>

</div>

