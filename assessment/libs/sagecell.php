<?php
//Sagecell integration functions, Version 1.0, Jan 2018
//
//BE AWARE: whatever your students type in the sagecell box is NOT saved or
//retained after they submit the question.

global $allowedmacros;
array_push($allowedmacros, "setupsagecell");

//setupsagecell(inital code)
//Generates the code necessary to insert a SageCell into your question
//You can optionally provide code to initalize the cell
function setupsagecell($code = "") {
	global $imasroot;
	$uniqid = uniqid();
	$params = array(
		'frame_id' => $uniqid, 
		'code' => $code
	);
	$frame = '<iframe id="sc'.$uniqid.'" src="'.$imasroot.'/assessment/libs/sagecellframe.html?';
	$frame .= Sanitize::generateQueryStringFromMap($params).'"';
	$frame .= ' style="border:0" width="100%" height="100"></iframe>';
	return $frame;
}

