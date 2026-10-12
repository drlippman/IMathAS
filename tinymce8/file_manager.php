<?php
require_once "../init.php";
require_once "../includes/filehandler.php";

ini_set("max_execution_time", "120");

if (isset($_REQUEST["type"])) {
	$type = $_REQUEST["type"];
} else {
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

// file listing, loaded via ajax into the Existing Files tab
if (isset($_REQUEST['showfiles'])) {
	$files = getuserfiles($userid,$type=="img");
	$imgext = array('gif','jpg','jpeg','png','svg','webp');
	$rows = array();
	foreach ($files as $v) {
		$name = basename($v['name']);
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		$isimg = in_array($ext, $imgext);
		$rows[] = array(
			'name' => $name,
			'url' => getuserfileurl($v['name']),
			'ext' => $ext,
			'time' => intval($v['time'] ?? 0),
			'size' => intval($v['size'] ?? 0),
			'isimg' => $isimg,
			// clipboard pastes / image tool output; names are auto-generated
			'pasted' => $isimg && preg_match('/^(mceclip|imagetool|pastedimage)/i', $name) === 1,
		);
	}
	echo '<div class="fm_files">';
	if (count($rows)==0) {
		echo '<div class="td_main">' . _('No files to show') . '</div>';
	} else {
?>
<div class="listtools">
 <label><?php echo _("Sort:"); ?> <select id="sortsel">
  <option value="date"><?php echo _("Newest first"); ?></option>
  <option value="name"><?php echo _("Name"); ?></option>
  <option value="ext"><?php echo _("Extension"); ?></option>
<?php if ($type=="img") { ?>
  <option value="thumbs"><?php echo _("Thumbnails"); ?></option>
<?php } ?>
 </select></label>
 <label><?php echo _("Filter:"); ?> <input type="text" id="filterbox" size="15" aria-label="<?php echo _("Filter files by name"); ?>"></label>
</div>
<div class="td_main">
<div id="filelist"></div>
<div id="filegridbox" style="display:none">
 <div id="filegrid" class="thumbgrid"></div>
 <button type="button" id="filemore" style="display:none"><?php echo _("More"); ?></button>
</div>
<details id="pastedbox" style="display:none">
 <summary><?php echo _("Pasted images"); ?> (<span id="pastedcnt"></span>)</summary>
 <div id="pastedgrid" class="thumbgrid"></div>
 <button type="button" id="pastedmore" style="display:none"><?php echo _("More"); ?></button>
</details>
</div>
<script>
fmInitFileList(<?php echo json_encode($rows, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>);
</script>
<?php
	}
	echo '</div>';
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo _('File Manager'); ?></title>

<script src="<?php echo $staticroot;?>/javascript/jquery.min.js"></script>
<link href="file_manager/styles.css?v=2" rel="stylesheet" type="text/css">
<?php
if (isset($CFG['locale'])) {
	$lang = substr($CFG['locale'],0,2);
	if (file_exists(rtrim(dirname(__DIR__), '/\\').'/i18n/locale/'.$lang.'/messages.js')) {
		echo '<script src="'.$staticroot.'/i18n/locale/'.$lang.'/messages.js"></script>';
	}
}
?>
<script src="file_manager/file_manager.js?v=3"></script>
</head>
<body data-type="<?php echo Sanitize::encodeStringForDisplay($type); ?>" data-files-url="file_manager.php?showfiles=true&amp;type=<?php echo Sanitize::encodeUrlParam($type); ?>"<?php if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "upload_file") { echo ' data-inserted="' . Sanitize::encodeStringForDisplay(getuserfileurl("ufiles/$userid/".$filename)) . '"'; } ?>>
<?php
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "upload_file") {
	echo '<div class="td_main">';
	echo _('Inserting...');
	echo '</div></body></html>';
	exit;
}
?>
<div role="tablist" class="tabs">
 <button type="button" role="tab" id="tab_upload" aria-controls="panel_upload" aria-selected="true"><?php echo _("Upload"); ?></button>
 <button type="button" role="tab" id="tab_files" aria-controls="panel_files" aria-selected="false" tabindex="-1"><?php echo _("Existing Files"); ?></button>
</div>
<div role="tabpanel" id="panel_upload" aria-labelledby="tab_upload">
<div class="upload">
	<div id="upload_div">
	<form method="post" enctype="multipart/form-data" onSubmit="return switchDivs();">
		<input type="hidden" name="action" value="upload_file">
		<input type="hidden" name="MAX_FILE_SIZE" value="10485760" /> <!-- ~10mb -->
		<label class="fieldlabel" for="uploaded_file"><?php echo _('Upload file:'); ?></label>
		<?php
		if ($type=="img") {
			echo '<input type="file" name="uploaded_file" id="uploaded_file" accept=".gif,.png,.jpg,.jpeg,.svg">';
			echo '<div class="hint">'._('Valid image types: GIF, PNG, JPG/JPEG, SVG').'</div>';
		} else {
			echo '<input type="file" name="uploaded_file" id="uploaded_file">';
		}
		?>
		<input type="hidden" name="uploaded_file_name" id="uploaded_file_name" />
		<label class="checkrow"><input type="checkbox" name="overwrite"/> <?php echo _('Overwrite an existing file if it has the same name?'); ?></label>
		<button type="submit" class="primary"><?php echo _('Upload'); ?></button>
	</form>
	</div>
	<div id="uploading_div" style="display: none;">
	<?php echo _('Uploading ...'); ?>
	</div>
</div>
<div class="notice">
<?php echo _('Files uploaded are not secured, and can be accessed by anyone who can guess the address.  Do not upload files with private information.'); ?>
</div>
</div>
<div role="tabpanel" id="panel_files" aria-labelledby="tab_files" hidden></div>
</body>
</html>
