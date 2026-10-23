<?php

  //check credentials
  $init_csrfp_scope = 'default,question';
  require_once "../init.php";
  require_once "../includes/filehandler.php";

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
    $rmurl = rawurldecode($_POST['remove']);
    if (stripos($rmurl, "/afiles/") !== false) {
        // files uploaded inside assessments are locked, except that they can be removed
        // from within the same assessment they were uploaded in
        [$rmstatus, $rmaid] = editoruploadassessstatus($_POST['ctx'] ?? '');
        if ($rmstatus === 'valid' && preg_match('~ufiles/(\d+)/afiles/(\d+)/([^/?&#]+)$~', $rmurl, $m) &&
            intval($m[1]) === intval($userid) && intval($m[2]) === $rmaid
        ) {
            $res = deleteassessuserfile($userid, $rmaid, $m[3]);
        }
    } else if (strpos($_POST['remove'], "ufiles/$userid/") !== false) {
        $res = deleteuserfile($userid, rawurldecode(basename($_POST['remove'])));
    }
    echo '{"done": '.($res?'true':'false').'}';
    exit;
}



  reset ($_FILES);
  if (count($_FILES)==0) {
    error_log('upload_handler fail: upload empty');
    header("HTTP/1.0 500 " . _("Upload failed"));
    exit;
  }
  $tempkey = key($_FILES);
  $temp = current($_FILES);
  if (Sanitize::isFilenameBlacklisted(str_replace(' ','_',$temp['name']))) {
    header("HTTP/1.0 500 " . _('Invalid file type'));
    exit;
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
        header("HTTP/1.0 500 " . _("Invalid file name"));
        exit;
    }

    // Verify extension
    $extension = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
    if ($extension == '') {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($temp['tmp_name']);
        $allowedTypes = [
            'image/jpeg' => '.jpg',
            'image/png'  => '.png',
            'image/gif'  => '.gif',
            'image/webp' => '.webp'
        ];
        if (array_key_exists($mimeType, $allowedTypes)) {
            $extension = substr($allowedTypes[$mimeType], 1); // remove the dot
            $temp['name'] .= $allowedTypes[$mimeType];
        } 
    }
    if ($extension == 'dat' && $temp['type'] == 'image/svg+xml') {
        $temp['name'] = str_replace('.dat','.svg', $temp['name']);
        $extension = 'svg';
    }
    if (isset($_POST['type']) && $_POST['type'] == 'attach') {
      // already checked for blacklisted earlier
    } else if (!in_array($extension, array("gif", "jpg", "png", "jpeg", "svg"))) {
        header("HTTP/1.0 500 " . _("Invalid extension"));
        exit;
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
    // uploads from within an assessment go in a locked subfolder.  A correctly signed but
    // expired assessment token is refused rather than stored unprotected.
    [$assessstatus, $assessaid] = editoruploadassessstatus($_POST['ctx'] ?? '');
    if ($assessstatus === 'expired') {
      header("HTTP/1.0 403 Forbidden");
      exit;
    }
    $storeprefix = "ufiles/$userid/" . ($assessstatus === 'valid' ? "afiles/$assessaid/" : '');
    $filename0 = $filenamepts[0];
    // in assessments, always uniquify (even mceclip/imagetool names) so nothing is replaced
    while ((!$skipcheck || $assessstatus === 'valid') && doesfileexist('ufiles', $storeprefix.$filename)) {
	$filenamepts[0] = $filename0.'_'.$ncnt;
	$filename = implode('.',$filenamepts);
	$ncnt++;
    }
    // public only for course-page text editors with a valid server-issued token
    // and current teacher rights; everything else is private.
    $ispublicupload = editoruploadispublic($_POST['ctx'] ?? '');
    $storekey = $storeprefix.$filename;
    if (!$ispublicupload) {
      try {
        filesig_getsecrets('pre');
      } catch (RuntimeException $e) {
        error_log('upload_handler: ' . $e->getMessage());
        header("HTTP/1.0 500 File signing is not configured");
        exit;
      }
    }
    if (storeuploadedfile($tempkey,$storekey,$ispublicupload?"public":"private")) {

      // Respond to the successful upload with JSON.
      // Use a location key to specify the path to the saved image resource.
      // { location : '/your/uploaded/image/file'}
      // Private uploads return a pre-signed URL that filter() converts at display time.
      echo json_encode(array('location' => $ispublicupload ? getuserfileurl($storekey) : geteditorfileurl($storekey)));
    } else {
      error_log('upload_handler failed to save: ' . print_r($temp, true));
      header("HTTP/1.0 500 " . _("Unable to Save"));
    }
  } else {
    // Notify editor that the upload failed
    error_log('upload_handler fail: not is_uploaded_file: ' . print_r($temp, true));
    header("HTTP/1.0 500 " . _("Server Error"));
  }
?>
