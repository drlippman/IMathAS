<?php
// IMathAS: Main launch page for assess2 assessment player
// (c) 2019 David Lippman

$lastupdate = '20190401';

require('../init.php');

$placeinhead = '<script type="text/javascript">var APIbase = "'.$GLOBALS['basesiteurl'].'/assess2/";</script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$imasroot.'/assess2/vue/css/app.css?v='.$lastupdate.'" />';
$nologo = true;
require('../header.php');

?>
<noscript>
  <strong>We're sorry but <?php echo $installname; ?> doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="app"></div>

<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/chunk-vendors.js?v=<?php echo $lastupdate;?>"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/assess2/vue/js/app.js?v=<?php echo $lastupdate;?>"></script>
<?php

require('../footer.php');
