<?php
// IMathAS: Header code for assess2
// (c) 2019 David Lippman

$vuecss = glob("vue/css/*.css");
foreach ($vuecss as $css) {
  $placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$GLOBALS['basesiteurl'].'/assess2/'.$css.'" />';
}

$nologo = true;
