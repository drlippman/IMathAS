<?php
  //check credentials
  require("../init.php");
  require_once("../includes/filehandler.php");



ini_set("max_input_time", "120");
ini_set("max_execution_time", "120");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

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
    if ($_POST['type'] == 'attach') {
      // already checked for blacklisted earlier
    } else if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png"))) {
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
    storeuploadedfile($tempkey,"ufiles/$userid/".$filename,"public");

    // Respond to the successful upload with JSON.
    // Use a location key to specify the path to the saved image resource.
    // { location : '/your/uploaded/image/file'}
    echo json_encode(array('location' => getuserfileurl("ufiles/$userid/".$filename)));
  } else {
    // Notify editor that the upload failed
    header("HTTP/1.0 500 Server Error");
  }
?>
