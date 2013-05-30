<?php

//FIX outcomemap on more than one outcome in an assessment

require("../validate.php");

require("outcometable.php");
$canviewall = true;
$catfilter = -1;
$secfilter = -1;
$t = outcometable();

print_r($t);

?>
