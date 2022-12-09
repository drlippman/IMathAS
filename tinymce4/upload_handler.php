<?php

  //check credentials
  require("../init.php");
  require_once("../includes/filehandler.php");

  if ($_SERVER['HTTP_HOST'] == 'localhost') {
    //to help with development, while vue runs on 8080
    if (!empty($CFG['assess2-use-vue-dev'])) {
      header('Access-Control-Allow-Origin: '. $CFG['assess2-use-vue-dev-address']);
    }
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Origin");
}


ini_set("max_execution_time", "120");

if (isset($_POST['remove'])) {
    $res = false;
    if (strpos($_POST['remove'], "ufiles/$userid/") !== false) {
        $res = deleteuserfile($userid, basename($_POST['remove']));
    }
    echo '{"done": '.($res?'true':'false').'}';
    exit;
}



  reset ($_FILES);
  $tempkey = key($_FILES);
  $temp = current($_FILES);
  if (Sanitize::isFilenameBlacklisted(str_replace(' ','_',$temp['name']))) {
    header("HTTP/1.0 500 Invalid file name.");
    return;
  }
  $temp['name'] = Sanitize::sanitizeFilenameAndCheckBlacklist(str_replace(' ','_',$temp['name']));
  if (is_uploaded_file($temp['tmp_name'])){

    /*
      If your script needs to receive cookies, set images_upload_credentials : true in
      the configuration and enable the following two headers.
    */
    header('Access-Control-Allow-Credentials: true');
    header('P3P: CP="There is no P3P policy."');

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.0 500 Invalid file name.");
        return;
    }

    // Verify extension
    $extension = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
    if ($extension == 'dat' && $temp['type'] == 'image/svg+xml') {
        $temp['name'] = str_replace('.dat','.svg', $temp['name']);
        $extension = 'svg';
    }
    if (isset($_POST['type']) && $_POST['type'] == 'attach') {
      // already checked for blacklisted earlier
    } else if (!in_array($extension, array("gif", "jpg", "png", "jpeg", "svg"))) {
        header("HTTP/1.0 500 Invalid extension.");
        return;
    }

    // Accept upload if there was no origin, or if it is an accepted origin
    $filename = basename(str_replace('\\','/',$temp['name']));
    $ncnt = 1;
    $filenamepts = explode('.',$filename);
    $skipcheck = false;
    if (strpos($filename,'mceclip')!==false || strpos($filename,'imagetool')!==false) {
    	    $milliseconds = round(microtime(true) * 1000);
    	    $filenamepts[0] .= '-'.$milliseconds;
    	    $filename = implode('.',$filenamepts);
    	    $skipcheck = true;
    }
    $filename0 = $filenamepts[0];
    while (!$skipcheck && doesfileexist('ufiles', "ufiles/$userid/".$filename)) {
	$filenamepts[0] = $filename0.'_'.$ncnt;
	$filename = implode('.',$filenamepts);
	$ncnt++;
    }
    if (storeuploadedfile($tempkey,"ufiles/$userid/".$filename,"public")) {

      // Respond to the successful upload with JSON.
      // Use a location key to specify the path to the saved image resource.
      // { location : '/your/uploaded/image/file'}
      echo json_encode(array('location' => getuserfileurl("ufiles/$userid/".$filename)));
    } else {
      header("HTTP/1.0 500 Unable to Save");
    }
  } else {
    // Notify editor that the upload failed
    header("HTTP/1.0 500 Server Error");
  }
?>
