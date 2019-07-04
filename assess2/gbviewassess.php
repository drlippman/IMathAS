<?php
// IMathAS: Assess2 gradebook details page
// (c) 2019 David Lippman

$lastupdate = '20190704';

require('../init.php');
if (empty($_GET['cid']) || empty($_GET['aid'])) {
  echo 'Error - need to specify course ID and assessment ID in URL';
  exit;
}
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$stu = Sanitize::onlyInt($_GET['stu']);

$from = $_GET['from'];
if ($from=='isolate') {
  $exitUrl =  $GLOBALS['basesiteurl'] . "/course/isolateassessgrade.php?stu=$stu&cid=$cid&aid=$aid";
} else if ($from=='gisolate') {
  $exitUrl = $GLOBALS['basesiteurl'] . "/course/isolateassessbygroup.php?stu=$stu&cid=$cid&aid=$aid";
} else if ($from=='stugrp') {
  $exitUrl = $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&aid=$aid";
} else if ($from=='gbtesting') {
  $exitUrl = $GLOBALS['basesiteurl'] . "/course/gb-testing.php?stu=$stu&cid=$cid";
} else {
  $exitUrl = $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu=$stu&cid=$cid";
}

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

$placeinhead = '<script type="text/javascript">var APIbase = "'.$GLOBALS['basesiteurl'].'/assess2/";</script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/index.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/gbviewassess.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/chunk-common.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
$placeinhead .= '<script src="'.$imasroot.'/javascript/drawing_min.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/AMhelpers2_min.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/eqntips_min.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/mathjs_min.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/rubric_min.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/mathquill/AMtoMQ_min.js" type="text/javascript"></script>
  <script src="'.$imasroot.'/mathquill/mathquill.min.js" type="text/javascript"></script>
  <script src="'.$imasroot.'/mathquill/mqeditor_min.js" type="text/javascript"></script>
  <script src="'.$imasroot.'/mathquill/mqedlayout_min.js" type="text/javascript"></script>
  <link rel="stylesheet" type="text/css" href="'.$imasroot.'/mathquill/mathquill-basic.css">
  <link rel="stylesheet" type="text/css" href="'.$imasroot.'/mathquill/mqeditor.css">';
if ($isltilimited || $inTreeReader) {
  $placeinhead .= '<script>var exiturl = "";</script>';
} else {
  $placeinhead .= '<script>var exiturl = "' . $exitUrl . '";</script>';
}
$nologo = true;
$useeditor = 1;
require('../header.php');

if ((!$isltilimited || $sessiondata['ltirole']!='learner') && !$inTreeReader && !$isdiag) {
  echo "<div class=breadcrumb>";
  if ($isltilimited) {
    echo "$breadcrumbbase ";
  } else {
    echo $breadcrumbbase . ' <a href="'.$imasroot.'/course/course.php?cid='.$cid.'">';
    echo Sanitize::encodeStringForDisplay($coursename);
    echo '</a> &gt; ';
  }
  if ($stu>0) {
    echo "<a href=\"$imasroot/course/gradebook.php?stu=0&cid=$cid\">"._('Gradebook')."</a> ";
    echo "&gt; <a href=\"$imasroot/course/gradebook.php?stu=$stu&cid=$cid\">"._('Student Detail')."</a> &gt; ";
  } else if ($from=="isolate") {
    echo " <a href=\"$imasroot/course/gradebook.php?stu=0&cid=$cid\">"._('Gradebook')."</a> ";
    echo "&gt; <a href=\"$imasroot/course/isolateassessgrade.php?cid=$cid&aid=$aid\">"._('View Scores')."</a> &gt; ";
  } else if ($from=="gisolate") {
    echo "<a href=\"$imasroot/course/gradebook.php?stu=0&cid=$cid\">"._('Gradebook')."</a> ";
    echo "&gt; <a href=\"$imasroot/course/isolateassessbygroup.php?cid=$cid&aid=$aid\">"._('View Group Scores')."</a> &gt; ";
  } else if ($from=='stugrp') {
    echo "<a href=\"$imasroot/course/managestugrps.php?cid=$cid&aid=$aid\">"._('Student Groups')."</a> &gt; ";
  } else if ($from=='gbtesting') {
    echo "<a href=\"$imasroot/course/gb-testing.php?stu=0&cid=$cid\">"._('Diagnostic Gradebook')."</a> &gt; ";
  } else {
    echo "<a href=\"$imasroot/course/gradebook.php?stu=0&cid=$cid\">"._('Gradebook')."</a> &gt; ";
  }
  echo _('Assessment Detail');
  echo '</div>';
}
?>
<noscript>
  <strong>We're sorry but <?php echo $installname; ?> doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="app"></div>

<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-vendors.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/gbviewassess.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-common.js?v=<?php echo $lastupdate;?>"></script>

<?php
$placeinfooter = '<div id="ehdd" class="ehdd" style="display:none;">
  <span id="ehddtext"></span>
  <span onclick="showeh(curehdd);" style="cursor:pointer;">'._('[more..]').'</span>
</div>
<div id="eh" class="eh"></div>';
require('../footer.php');
