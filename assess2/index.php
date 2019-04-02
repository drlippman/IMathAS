<?php
// IMathAS: Main launch page for assess2 assessment player
// (c) 2019 David Lippman

$lastupdate = '20190402';

require('../init.php');

$isdiag = isset($sessiondata['isdiag']);
if ($isdiag) {
  $diagid = $sessiondata['isdiag'];
  $hideAllHeaderNav = true;
}
$isltilimited = (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0 && $sessiondata['ltirole']=='learner');


$placeinhead = '<script type="text/javascript">var APIbase = "'.$GLOBALS['basesiteurl'].'/assess2/";</script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/app.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
$nologo = true;
require('../header.php');

echo "<div class=breadcrumb>";
if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0) {
  echo "$breadcrumbbase ", _('Assessment'), "</div>";
} else {
  echo $breadcrumbbase . ' <a href="../course/course.php?cid='.$cid.'">';
  echo Sanitize::encodeStringForDisplay($coursename);
  echo '</a> &gt; ', _('Assessment'), '</div>';
}
?>
<noscript>
  <strong>We're sorry but <?php echo $installname; ?> doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="app"></div>

<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-vendors.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/app.js?v=<?php echo $lastupdate;?>"></script>
<?php

require('../footer.php');
