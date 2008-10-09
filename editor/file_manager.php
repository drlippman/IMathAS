<?php
require("../validate.php");
require("../includes/filehandler.php");

set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");
//which language file to use
include("file_manager/lang/lang_eng.php");

//which images to use
$delete_image 			= "file_manager/x.png";
$file_small_image 		= "file_manager/file_small.png";

//custom configuration from here on ..
//image browser configuration
$dir_width 		= "96px";
$file_width 	= "96px";
$pics_per_row 	= 2;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo $strings["title"]; ?></title>

<link href="file_manager/styles.css" rel="stylesheet" type="text/css">
<?php
if (isset($_REQUEST["type"])) {
	$type = $_REQUEST["type"];
}
else {
	$type = -2;
}

if (isSet($_REQUEST["action"]))
{
	if ($_REQUEST["action"] == "upload_file")
	{
		storeuploadedfile("uploaded_file","ufiles/$userid/". basename($_FILES["uploaded_file"]["name"]),"public");
	}
	else if ($_REQUEST["action"] == "delete_file")
	{
		deleteuserfile($userid,$_REQUEST["item_name"]);
	}
}
?>
<script>
function fileSelected(filename) {
	//let our opener know what we want
	window.top.opener.my_win.document.getElementById(window.top.opener.my_field).value = filename;
	window.top.opener.my_win.document.getElementById(window.top.opener.my_field).onchange();
	//we close ourself, cause we don't need us anymore ;)
	window.close();
}
function switchDivs() {
	var fieldvalue = document.getElementById("uploaded_file").value;
	if (fieldvalue=='') {
		alert("No file selected");
		return false;
	}
<?php
if ($type=="img") {
?>
	
	extension = ['.png','.gif','.jpg','.jpeg'];
	isok = false;
	var thisext = fieldvalue.substr(fieldvalue.lastIndexOf('.')).toLowerCase();
	for(var i = 0; i < extension.length; i++) {
		if(thisext == extension[i]) { isok = true; }
	}
	if (!isok) {
		alert("File must be an image file: .png, .gif, .jpg");
		return false;
	}
<?php
}
?>

	document.getElementById("upload_div").style.display = "none";
	document.getElementById("uploading_div").style.display = "block";
	return true;
}
</script>
</head>
<body>
<div class="td_close">
<a class="close" href="javascript: window.close();"><?php echo $strings["close"]; ?></a>
</div>
<div class="td_main">
<?php
$files = getuserfiles($userid,$type=="img");
foreach ($files as $k=>$v) {
	echo "<a href='#' onClick='delete_file(\"" . basename($v['name']) . "\")'>";
	echo "<img border=0 src='" . $delete_image . "'></a> ";
	echo "<img src='" . $file_small_image . "'> ";
	echo "<a class='file' href='#' onClick='fileSelected(\"" . getuserfileurl($v['name']) . "\");'>" . basename($v['name']) . "</a><br>\n";

}
?>
</div>
<div class="upload">

	<div id="upload_div" style="display: block; padding: 0px;">
	<form method="post" enctype="multipart/form-data" onSubmit="return switchDivs();">
		<?php echo $strings["upload_file"]; ?>
		<input type="hidden" name="action" value="upload_file">
		<input type="hidden" name="MAX_FILE_SIZE" value="10485760" /> <!-- ~10mb -->
		<input type="file" name="uploaded_file" id="uploaded_file">
		<input type="submit" value="<?php echo $strings["upload_file_submit"]; ?>">
	</form>
	</div>
	<div id="uploading_div" style="display: none; padding: 0px;">
	<?php echo $strings["sending"]; ?>
	</div>
</div>
<div class="notice">
Files uploaded are not secured, and can be accessed by anyone who can guess the address.  Do not
upload files with private information.
</div>
<script>

function delete_file(file_name)
{
	document.getElementById("hidden_action").value = "delete_file";
	document.getElementById("hidden_item_name").value = file_name;
	document.getElementById("hidden_form").submit();
}
</script>
<div style="display: none;">
	<form method="post" id="hidden_form">
	<input type="hidden" name="action" id="hidden_action" value="">
	<input type="hidden" name="item_name" id="hidden_item_name" value="">
	</form>
</div>
</body>
</html>