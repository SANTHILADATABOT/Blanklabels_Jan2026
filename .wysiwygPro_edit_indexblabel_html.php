<?php ob_start() ?>
<?php
if ($_GET['randomId'] != "a78XVDhKkQnKKhgEOB6DW6PoOOKabKU9DiOX4fdvf4_8ACv9RWGC2Lg003JDVi0PGJukWBjlCYpmQO4CCtFM9SPjBaWCjtIYL80haN21Qeg76rFU6AHIJguHZSbS2d_kX6vR8KgHxYpwOrGPXQbz8tiphFH_Onrp6Ijv9UJukAdoYvxgm5HHGyEIDVlWiTfRYdprvrQWm0oza4d7psmVpHk_4Vm5fRSX5MyYzuqnBI5c3e2lVdeTavWTjncULfFk") {
    echo "Access Denied";
    exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Editing indexblabel.html</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style type="text/css">body {background-color:threedface; border: 0px 0px; padding: 0px 0px; margin: 0px 0px}</style>
</head>
<body>
<div align="center">
<script language="javascript">
<!--//
// this function updates the code in the textarea and then closes this window
function do_save() {
	var code =  htmlCode.getCode();
	document.open();
	document.write('<html><form METHOD="POST" name=mform action="http://70.85.255.242:2082/frontend/x2/files/savehtmlfile.html"><input type="hidden" name="dir" value="/home/yourshop/public_html/blabel"><input type="hidden" name="file" value="indexblabel.html">Saving&nbsp;....<br /><br ><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><textarea name=page rows=1 cols=1></textarea></form></html>');
	document.close();
	document.mform.page.value = code;
	document.mform.submit();
}
function do_abort() {
	var code =  htmlCode.getCode();
	document.open();
	document.write('<html><form METHOD="POST" name="mform" action="http://70.85.255.242:2082/frontend/x2/files/aborthtmlfile.html"><input type="hidden" name="dir" value="/home/yourshop/public_html/blabel"><input type="hidden" name="file" value="indexblabel.html">Aborting Edit&nbsp;....</form></html>');
	document.close();
	document.mform.submit();
}
//-->
</script>
<?php
// make sure these includes point correctly:
include_once ('/usr/local/cpanel/base/3rdparty/WysiwygPro/editor_files/config.php');
include_once ('/usr/local/cpanel/base/3rdparty/WysiwygPro/editor_files/editor_class.php');

// create a new instance of the wysiwygPro class:
$editor = new wysiwygPro();

// add a custom save button:
$editor->addbutton('Save', 'before:print', 'do_save();', WP_WEB_DIRECTORY.'images/save.gif', 22, 22, 'undo');

// add a custom cancel button:
$editor->addbutton('Cancel', 'before:print', 'do_abort();', WP_WEB_DIRECTORY.'images/cancel.gif', 22, 22, 'undo');

$body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Your Shop - Online</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="refresh" content="1;URL=http://blabel.yourshoponline.net/shop/">
</head>

<body>
<table width="100%" height="100%" border="0" align="center" cellpadding="1" cellspacing="1">
  <tr>
    <td><div align="center">
      <p><font color="#000099">If you are not redirected automatically within 5 seconds please</font> <a href="http://blabel.yourshoponline.net/shop/" target="_self">Click
        Here</a></p>
      <p><img src="images/ysologo200.gif" width="200" height="200" border="0"></p>
    </div></td>
  </tr>
</table>
</body>
</html>
';

$editor->set_code($body);

// add a spacer:
$editor->addspacer('', 'after:cancel');

// print the editor to the browser:
$editor->print_editor('100%',450);

?>
</div>
</body>
</html>
<?php ob_end_flush() ?>
