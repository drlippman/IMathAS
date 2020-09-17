<?php
//IMathAS:  utility functions for rubrics
//(c) 2011 David Lippman

require_once(__DIR__ . "/sanitize.php");

//printrubrics
//returns javascript string for rubric data
//input: array of rubrics
//rubricarray = array(id,rubrictype, rubrictext)
function printrubrics($rubricarray) {
	$out = '<script type="text/javascript">';
	$out .= 'var imasrubrics = new Array();';
	foreach ($rubricarray as $info) {
		$out .= "imasrubrics[".Sanitize::encodeStringForJavascript($info[0])."] = {'type':".Sanitize::encodeStringForJavascript($info[1]).",'data':[";
		$data = unserialize($info[2]);
		foreach ($data as $i=>$rubline) {
			if ($i!=0) {
				$out .= ',';
			}
			$out .= '["'.Sanitize::encodeStringForJavascript($rubline[0]).'",';
			$out .= '"'.Sanitize::encodeStringForJavascript($rubline[1]).'"';
			$out .= ','.Sanitize::onlyFloat($rubline[2]);
			$out .= ']';
		}
		$out .= ']};';
	}
	$out .= '</script>';
	return $out;
}

//printrubriclink(rubricId,points,scoreboxid,feedbackboxid,[qn,width])
function printrubriclink($rubricid,$points,$scorebox,$feedbackbox,$qn='null',$width=600) {
	global $imasroot,$staticroot;

	$rubricid = Sanitize::onlyInt($rubricid);
	$points = Sanitize::onlyFloat($points);
	$scorebox = Sanitize::encodeStringForJavascript($scorebox);
	$feedbackbox = Sanitize::encodeStringForJavascript($feedbackbox);
	$qn = Sanitize::encodeStringForJavascript($qn);
	$width = Sanitize::onlyInt($width);

	$out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
	$out .= "<img border=0 src=\"$staticroot/img/assess.png\" alt=\"rubric\"></a>";
	$out .= "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\" style=\"display:none\" class=\"rubriclink\" id=\"rublink-$scorebox\">";
	$out .= "<img border=0 src=\"$staticroot/img/assess.png\" alt=\"rubric\"></a>";
	return $out;
}
