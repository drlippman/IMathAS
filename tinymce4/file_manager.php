<?php
require("../init.php");
require_once("../includes/filehandler.php");



ini_set("max_execution_time", "120");



//which language file to use
include("file_manager/lang/lang_eng.php");

//which images to use
$delete_image 			= "file_manager/x.png";
$update_image			= $staticroot . "/img/updating.gif";
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
		$filename = str_replace(' ','_',$_FILES['uploaded_file']['name']);
		$filename = Sanitize::sanitizeFilenameAndCheckBlacklist(basename(str_replace('\\','/',$filename)));

		//$filename = Sanitize::encodeStringForUrl($filename);
		//echo $filename;
		//exit;
		$extension = strtolower(strrchr($filename,"."));
		$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe");
		if (in_array($extension,$badextensions)) {
			unset($_REQUEST["action"]);
		} else {
			if (!isset($_REQUEST["overwrite"])) { //check if file exists, change filename if needed
				$ncnt = 1;
				$filenamepts = explode('.',$filename);
				$filename0 = $filenamepts[0];
				while (doesfileexist('ufiles', "ufiles/$userid/".$filename)) {
					$filenamepts[0] = $filename0.'_'.$ncnt;
					$filename = implode('.',$filenamepts);
					$ncnt++;
				}
			}
			if (storeuploadedfile("uploaded_file","ufiles/$userid/".$filename,"public")) {

			} else {
				unset($_REQUEST["action"]);
			}
		}
	}
	else if ($_REQUEST["action"] == "delete_file")
	{
		if (deleteuserfile($userid,$_REQUEST["item_name"])) {
			echo 'OK';
		} else {
			echo 'FAIL';
		}
		exit;
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo $strings["title"]; ?></title>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
<script type="text/javascript">
  if (!window.jQuery) {  document.write('<script src="'.$staticroot.'/javascript/jquery.min.js"><\/script>');}
</script>
<link href="file_manager/styles.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
var FileBrowserDialogue = {
    args : null,
    init : function () {
    	    args = parent.tinymce.activeEditor.windowManager.getParams();
        // Here goes your code for setting your custom things onLoad.
<?php
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "upload_file") {
	echo '         FileBrowserDialogue.mySubmit("'. getuserfileurl("ufiles/$userid/".$filename) .'");';
}
?>
    },
    mySubmit : function (filename) {

        // insert information now
        parent.tinymce.activeEditor.windowManager.getParams().oninsert(filename);

        // close popup window
        parent.tinymce.activeEditor.windowManager.close();
    }
}

$(function() {
	FileBrowserDialogue.init();
});

function switchDivs() {
	var fieldvalue = document.getElementById("uploaded_file").value;
	if (fieldvalue=='') {
		alert("No file selected");
		return false;
	}
<?php
if ($type=="img") {
?>

	extension = ['.png','.gif','.jpg','.jpeg','.svg'];
	isok = false;
	var thisext = fieldvalue.substr(fieldvalue.lastIndexOf('.')).toLowerCase();
	for(var i = 0; i < extension.length; i++) {
		if(thisext == extension[i]) { isok = true; }
	}
	if (!isok) {
		alert("File must be an image file: .png, .gif, .jpg, .svg");
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
<?php
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "upload_file") {
	echo '<div class="td_main">';
	echo 'Inserting...';
	echo '</div></body></html>';
	exit;
	/*
	echo 'Just uploaded:<br/>';
	echo "<a href='#' onClick='delete_file(\"" . basename("ufiles/$userid/".$filename) . "\")'>";
	echo "<img border=0 src='" . $delete_image . "'></a> ";
	echo "<img src='" . $file_small_image . "'> ";
	echo "<a class='file' href='#' onClick='FileBrowserDialogue.mySubmit(\"" . getuserfileurl("ufiles/$userid/".$filename) . "\");'>" . basename("ufiles/$userid/".$filename) . "</a><br>\n";
	echo '<hr/>';
	*/
}
if (isset($_REQUEST['showfiles'])) {
	$files = getuserfiles($userid,$type=="img");
	echo '<div class="td_main">';
	foreach ($files as $k=>$v) {
		echo "<div><a href='#' onClick='delete_file(\"" . basename($v['name']) . "\", this)'>";
		echo "<img border=0 src='" . $delete_image . "' alt=\"Delete\"></a> ";
		echo "<img src='" . $file_small_image . "' alt=\"File\"> ";
		echo "<a class='file' href='#' onClick='FileBrowserDialogue.mySubmit(\"" . getuserfileurl($v['name']) . "\");'>" . basename($v['name']) . "</a><br></div>\n";

	}
	if (count($files)==0) {
		echo '<div>No files to show</div>';
	}
} else {
	echo '<div class="upload">';
	echo '<p><a href="file_manager.php?showfiles=true&amp;type=' . Sanitize::encodeUrlParam($type) . '">Show previously uploaded files</a></p>';
}
?>
</div>
<div class="upload">

	<div id="upload_div" style="display: block; padding: 0px;">
	<form method="post" enctype="multipart/form-data" onSubmit="return switchDivs();">
		<?php echo $strings["upload_file"]; ?>
		<input type="hidden" name="action" value="upload_file">
		<input type="hidden" name="MAX_FILE_SIZE" value="10485760" /> <!-- ~10mb -->
		<?php
		if ($type=="img") {
			echo '<input type="file" name="uploaded_file" id="uploaded_file" accept=".gif,.png,.jpg,.jpeg,.svg"><br/>';
			echo $strings["imagetypes"].'<br/>';

		} else {
			echo '<input type="file" name="uploaded_file" id="uploaded_file">';
		}
		?>
		<input type="hidden" name="uploaded_file_name" id="uploaded_file_name" />
		<input type="submit" value="<?php echo $strings["upload_file_submit"]; ?>"><br/>
		<input type="checkbox" name="overwrite"/> <?php echo $strings["upload_overwrite"]; ?>
	</form>
	</div>
	<div id="uploading_div" style="display: none; padding: 0px;">
	<?php echo $strings["sending"]; ?>
	</div>
</div>
<div class="notice">
<?php echo $strings["notice"]; ?>
</div>
<script>

function delete_file(file_name, el)
{
	if (confirm("Are you sure you want to delete this file?  If you delete it, it will no longer be accessible or viewable from anywhere you've used it.")) {
		$(el).children("img").attr("src", "<?php echo $update_image;?>");
		$.ajax({
		  url: "file_manager.php",
		  data: { action: "delete_file", item_name: file_name }
		})
		  .done(function( msg ) {
		    if (msg=='OK') {
		    	    $(el).parent().remove();
		    } else {
		    	    alert("Error deleting file");
		    	    $(el).children("img").attr("src", "<?php echo $delete_image;?>");
		    }
		  });
		//document.getElementById("hidden_action").value = "delete_file";
		//document.getElementById("hidden_item_name").value = file_name;
		//document.getElementById("hidden_form").submit();
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
