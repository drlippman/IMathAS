<?php
$text = $_GET['text'];

$dbsetup = true;
require("../init_without_validate.php");

if (isset($CFG['GEN']['badgebase'])) {
	$im = imagecreatefrompng($CFG['GEN']['badgebase']);
} else {
	$im = imagecreatefrompng("badgebase.png");
}
$fontfile = '../filter/graph/FreeSerifItalic.ttf';

$black = imagecolorallocate($im, 0,0,0);

$p = explode(' ', $text);
if (count($p)==1) {
	$bb = imagettfbbox(12,0,$fontfile,$text);
	$bbw = $bb[4]-$bb[0];
	$bbh = -1*($bb[5]-$bb[1]);
	$fontsize = min(12*75/$bbw, 12*30/$bbh);
	$bb = imagettfbbox($fontsize,0,$fontfile,$text);
	$bbw = $bb[4]-$bb[0];
	$bbh = -1*($bb[5]-$bb[1]);
	
	//imagettftext($im,$fontsize,0,45-.5*$bbw,28+.5*$bbh,$black,$fontfile,$text);
	imagettftext($im,$fontsize,0,45-.5*$bbw,44+.5*$bbh,$black,$fontfile,$text);
	
} else {
	$countleft = 0; $countright = strlen($text);
	for ($i=0;$i<count($p);$i++) {
		$clen = strlen($p[$i]);
		if ($countleft + $clen + 1 > $countright - $clen - 1) {
			//should we take it?
			if ($countleft==0) {
				$i++; break;
			} else if ($countright==$clen || $countright/$countleft < ($countleft + $clen)/($countright-$clen)) {
				//current ratio is closer to 1
				break;
			} else {
				$i++;
				break;
			}
		} else {
			$countleft += ($clen+1);
			$countright -= ($clen+1);
		}
	}
	$left = implode(' ', array_slice($p,0,$i));
	$right = implode(' ', array_slice($p,$i));
	
	$bb = imagettfbbox(12,0,$fontfile,$left);
	$bbw = $bb[4]-$bb[0];
	$bbh = -1*($bb[5]-$bb[1]);
	$fontsize = min(12*75/$bbw, 12*15/$bbh);
	$bb = imagettfbbox($fontsize,0,$fontfile,$left);
	$bbw = $bb[4]-$bb[0];
	$bbh = -1*($bb[5]-$bb[1]);
	
	//imagettftext($im,$fontsize,0,45-.5*$bbw,15+.5*$bbh,$black,$fontfile,$left);
	imagettftext($im,$fontsize,0,45-.5*$bbw,42,$black,$fontfile,$left);
	
	$bb = imagettfbbox(12,0,$fontfile,$right);
	$bbw = $bb[4]-$bb[0];
	$bbh = -1*($bb[5]-$bb[1]);
	$fontsize = min(12*75/$bbw, 12*15/$bbh);
	$bb = imagettfbbox($fontsize,0,$fontfile,$right);
	$bbw = $bb[4]-$bb[0];
	$bbh = -1*($bb[5]-$bb[1]);
	
	//imagettftext($im,$fontsize,0,45-.5*$bbw,35+.5*$bbh,$black,$fontfile,$right);
	imagettftext($im,$fontsize,0,45-.5*$bbw,46+$bbh,$black,$fontfile,$right);
	
	
}

header('Content-Type: image/png');
imagepng($im);

?>
