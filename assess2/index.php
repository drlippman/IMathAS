<?php
// IMathAS: Main launch page for assess2 assessment player
// (c) 2019 David Lippman

$lastupdate = '20200305';

require('../init.php');
if (empty($_GET['cid']) || empty($_GET['aid'])) {
  echo 'Error - need to specify course ID and assessment ID in URL';
  exit;
}
if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) { // loaded by a NON-teacher
  echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll.";
  exit;
}
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);

$isltilimited = (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0);
$inTreeReader = (strpos($_SERVER['HTTP_REFERER'],'treereader') !== false);
$isdiag = isset($sessiondata['isdiag']);
if ($isdiag) {
  $diagid = Sanitize::onlyInt($sessiondata['isdiag']);
  $hideAllHeaderNav = true;
}

if ($isltilimited || $inTreeReader) {
  $flexwidth = true;
  $hideAllHeaderNav = true;
}

$placeinhead = '<script type="text/javascript">var APIbase = "'.$GLOBALS['basesiteurl'].'/assess2/";';
$placeinhead .= 'var inTreeReader = ' . ($inTreeReader ? 1 : 0) . ';';
$placeinhead .= '</script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/index.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/chunk-common.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
$placeinhead .= '<script src="'.$imasroot.'/mathquill/mathquill.min.js?v=022720" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/assess2_min.js?v=030120" type="text/javascript"></script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/mathquill/mathquill-basic.css">
  <link rel="stylesheet" type="text/css" href="'.$imasroot.'/mathquill/mqeditor.css">';
if ($isltilimited || $inTreeReader) {
  $placeinhead .= '<script>var exiturl = "";</script>';
} else if ($isdiag) {
  $placeinhead .= '<script>var exiturl = "'.$GLOBALS['basesiteurl'].'/diag/index.php?id='.$diagid.'";</script>';
} else {
  $placeinhead .= '<script>var exiturl = "'.$GLOBALS['basesiteurl'].'/course/course.php?cid='.$cid.'";</script>';
}
$nologo = true;
$useeditor = 1;
require('../header.php');

if ((!$isltilimited || $sessiondata['ltirole']!='learner') && !$inTreeReader && !$isdiag) {
  echo "<div class=breadcrumb>";
  if ($isltilimited) {
    echo "$breadcrumbbase ", _('Assessment'), "</div>";
  } else {
    echo $breadcrumbbase . ' <a href="../course/course.php?cid='.$cid.'">';
    echo Sanitize::encodeStringForDisplay($coursename);
    echo '</a> &gt; ', _('Assessment'), '</div>';
  }
}
?>
<noscript>
  <strong>We're sorry but <?php echo $installname; ?> doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="app"></div>

<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-vendors.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/index.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-common.js?v=<?php echo $lastupdate;?>"></script>

<?php
$placeinfooter = '<div id="ehdd" class="ehdd" style="display:none;">
  <span id="ehddtext"></span>
  <span onclick="showeh(curehdd);" style="cursor:pointer;">'._('[more..]').'</span>
</div>
<div id="eh" class="eh"></div>';
require('../footer.php');
