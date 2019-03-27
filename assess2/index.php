<?php
// IMathAS: Main launch page for assess2 assessment player
// (c) 2019 David Lippman

require('../init.php');

$placeinhead = '<script type="text/javascript">var APIbase = "'.$GLOBALS['basesiteurl'].'/assess2/";</script>';
require("./assessheader.php");
require('../header.php');


?>
<noscript>
  <strong>We're sorry but <?php echo $installname; ?> doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="app"></div>

<?php
require("./assessfooter.php");
require('../footer.php');
