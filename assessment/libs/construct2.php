<?php
//Construct2 integration functions, Version 0.1, Dec 18, 2013


global $allowedmacros;
array_push($allowedmacros,"addConstruct2");

//addConstruct2(url,[width,height,params,qn,part]) 
//place a Construct2 HTML5 applet, using the HTML file specified in URL 
//  the applet must have the imathas postMessage shim added to the scripts
//params should be key=>value array of values to send to Construct2 via the query string, like 
//  array('a'=>3,'name'=>'Fred')
//  these will be readable in Construct2 using Browser.QueryParam("name")
//if you want to read values from Construct2 provide  
//  question number(1-indexed - usually use $thisq) 
//  and if multipart, part number (0-indexed)
//  IMathAS doesn't request values - Construct2 has to send them.
//  Use Browser.QueryParam("imathasqn") to read the imathas question number, then
//  form a returnstring text variable like: 
//      imathasqn&"::"&myvar&","&anothervar
//  to send back, use Browser -> Execute Javascript like this:
//      "parent.postMessage('"&returnstring&"','*')"
//  see http://www.imathas.com/misc/basicimathasex.capx for an example
function addConstruct2($url,$width=400,$height=200,$params=array(),$qn=null,$part=null) {
	$out = '';
	if ($GLOBALS['inquestiondisplay'] == false) {return '';}
	if (!isset($GLOBALS['construct2count'])) {
		$GLOBALS['construct2count'] = 0;
	} else {
		$GLOBALS['construct2count']++;
	}
	if ($qn !== null) {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		$params['imathasqn'] = $qn;
	}
	$qs = http_build_query($params);
	if (strpos($url,'?')===false) {
		$url .= '?'.$qs;	
	} else {
		$url .= '&'.$qs;
	}
	
	$out = '<iframe width="'.$width.'" height="'.$height.'" id="constructiframe'.$GLOBALS['construct2count'].'" src="'.$url.'"></iframe>';
	
	if ($qn !== null) {
		$out .= '<script type="text/javascript">';
		$out .= '$(function() { $(window).on("message", function(e) { 
			var data = e.originalEvent.data.split("::");
			if (data[0] == '.$qn.') {
				$("#qn'.$qn.'").val(data[1]);
			}});});';
		$out .= '</script>';
	}
	return $out;
}
