<?php
//IMathAS:  utility functions for rubrics
//(c) 2011 David Lippman

//printrubrics
//returns javascript string for rubric data
//input: array of rubrics
//rubricarray = array(id,rubrictype, rubrictext)
function printrubrics($rubricarray) {
	$out = '<script type="text/javascript">';
	$out .= 'var imasrubrics = new Array();';
	foreach ($rubricarray as $info) {
		$out .= "imasrubrics[{$info[0]}] = {'type':{$info[1]},'data':[";
		$data = unserialize($info[2]);
		foreach ($data as $i=>$rubline) {
			if ($i!=0) {
				$out .= ',';
			}
			$out .= '["'.str_replace('"','\\"',$rubline[0]).'",';
			$out .= '"'.str_replace('"','\\"',$rubline[1]).'"';
			$out .= ','.$rubline[2];
			$out .= ']';
		}
		$out .= ']};';
	}
	$out .= '</script>';
	return $out;
}

//printrubriclink(rubricId,points,scoreboxid,feedbackboxid,[qn,width])
function printrubriclink($rubricid,$points,$scorebox,$feedbackbox,$qn='null',$width=600) {
	global $imasroot;

	$rubricid = Sanitize::onlyInt($rubricid);
	$points = Sanitize::onlyInt($points);
	$scorebox = Sanitize::encodeStringForJavascript($scorebox);
	$feedbackbox = Sanitize::encodeStringForJavascript($feedbackbox);
	$qn = Sanitize::encodeStringForJavascript($qn);
	$width = Sanitize::onlyInt($width);

	$out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
	$out .= "<img border=0 src=\"$imasroot/img/assess.png\" alt=\"rubric\"></a>";
	return $out;
}

