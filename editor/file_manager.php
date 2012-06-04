<?php
require("../validate.php");
require("../includes/filehandler.php");

@set_time_limit(0);
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

if (isset($_REQUEST["type"])) {
	$type = $_REQUEST["type"];
}
else {
	$type = -2;
}

if (isset($_REQUEST["action"]))
{
	if ($_REQUEST["action"] == "upload_file")
	{
		//$filename = basename(stripslashes($_POST["uploaded_file_name"]));
		$filename = basename($_FILES['uploaded_file']['name']);
		$filename = str_replace(' ','_',$filename);
		$filename = preg_replace('/[^\w\.\-_]/','',$filename);
		//$filename = urlencode($filename);
		//echo $filename;
		//exit;
		$extension = strtolower(strrchr($filename,"."));
		$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe");
		if (in_array($extension,$badextensions)) {
			unset($_REQUEST["action"]);
		} else if (storeuploadedfile("uploaded_file","ufiles/$userid/".$filename,"public")) {
			
		} else {
			unset($_REQUEST["action"]);
		}
	}
	else if ($_REQUEST["action"] == "delete_file")
	{
		deleteuserfile($userid,$_REQUEST["item_name"]);
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo $strings["title"]; ?></title>


<script language="javascript" type="text/javascript" src="<?php echo $imasroot?>/editor/tiny_mce_popup.js"></script>
<link href="file_manager/styles.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
var FileBrowserDialogue = {
    init : function () {
        // Here goes your code for setting your custom things onLoad.
    },
    mySubmit : function (filename) {
        var win = tinyMCEPopup.getWindowArg("window");

        // insert information now
        win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = filename;

        // for image browsers: update image dimensions
        if (win.ImageDialog) {
		if (win.ImageDialog.getImageData) win.ImageDialog.getImageData();
		if (win.ImageDialog.showPreviewImage) win.ImageDialog.showPreviewImage(URL);
	}
        // close popup window
        tinyMCEPopup.close();
    }
}

tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);

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
} else {
?>
	extension = [".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe"];
	isok = true;
	var thisext = fieldvalue.substr(fieldvalue.lastIndexOf('.')).toLowerCase();
	for(var i = 0; i < extension.length; i++) {
		if(thisext == extension[i]) { isok = false; }
	}
	if (!isok) {
		alert("This filetype is not allowed");
		return false;
	}
<?php
}
?>
	document.getElementById("upload_div").style.display = "none";
	document.getElementById("uploading_div").style.display = "block";
	document.getElementById("uploaded_file_name").value = fieldvalue;
	return true;
}
</script>
</head>
<body>
<div class="td_close">
<a class="close" href="javascript: tinyMCEPopup.close();"><?php echo $strings["close"]; ?></a>
</div>
<div class="td_main">
<?php
$files = getuserfiles($userid,$type=="img");
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "upload_file") {
	echo 'Just uploaded:<br/>';
	echo "<a href='#' onClick='delete_file(\"" . basename("ufiles/$userid/".$filename) . "\")'>";
	echo "<img border=0 src='" . $delete_image . "'></a> ";
	echo "<img src='" . $file_small_image . "'> ";
	echo "<a class='file' href='#' onClick='FileBrowserDialogue.mySubmit(\"" . getuserfileurl("ufiles/$userid/".$filename) . "\");'>" . basename("ufiles/$userid/".$filename) . "</a><br>\n";
	echo '<hr/>';
}
foreach ($files as $k=>$v) {
	echo "<a href='#' onClick='delete_file(\"" . basename($v['name']) . "\")'>";
	echo "<img border=0 src='" . $delete_image . "'></a> ";
	echo "<img src='" . $file_small_image . "'> ";
	echo "<a class='file' href='#' onClick='FileBrowserDialogue.mySubmit(\"" . getuserfileurl($v['name']) . "\");'>" . basename($v['name']) . "</a><br>\n";

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
		<input type="hidden" name="uploaded_file_name" id="uploaded_file_name" />
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
	if (confirm("Are you sure you want to delete this file?  If you delete it, it will no longer be accessible or viewable from anywhere you've used it.")) {
		document.getElementById("hidden_action").value = "delete_file";
		document.getElementById("hidden_item_name").value = file_name;
		document.getElementById("hidden_form").submit();
	}
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