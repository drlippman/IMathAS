<?php
// IMathAS: Footer code for assess2
// (c) 2019 David Lippman


$vuejs = glob("vue/js/*.js");
foreach ($vuejs as $js) {
  echo '<script type="text/javascript" src="'.$GLOBALS['basesiteurl'].'/assess2/'.$js.'"></script>';
}
