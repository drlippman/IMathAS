<?php
// IMathAS: Main launch page for assess2 assessment player
// (c) 2019 David Lippman

$lastupdate = '20190402';

require('../init.php');
if (empty($_GET['cid']) || empty($_GET['aid'])) {
  echo 'Error - need to specify course ID and assessment ID in URL';
  exit;
}
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);

$isltilimited = (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0 && $sessiondata['ltirole']=='learner');
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
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/app.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
$placeinhead .= '<script src="'.$imasroot.'/javascript/AMhelpers2.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/eqntips.js" type="text/javascript"></script>';
$placeinhead .= '<script src="'.$imasroot.'/javascript/mathjs.js" type="text/javascript"></script>';

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

if (!$isltilimited && !$inTreeReader && !$isdiag) {
  echo "<div class=breadcrumb>";
  if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0) {
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

<div id="ehdd" class="ehdd">
  <span id="ehddtext"></span>
  <span onclick="showeh(curehdd);" style="cursor:pointer;"><?php echo _('[more..]'); ?></span>
</div>
<div id="eh" class="eh"></div>

<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-vendors.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/app.js?v=<?php echo $lastupdate;?>"></script>

<?php

require('../footer.php');
