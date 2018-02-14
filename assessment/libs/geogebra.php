<?php
//Geogebra integration functions, Version 3.0, Oct 4, 2014


global $allowedmacros;
array_push($allowedmacros,"addGeogebra","addGeogebraJava");

//addGeogebra(url,[width,height,commands,params,callbacks,qn,part]) 
//place a geogebra HTML5 applet.  Either include the base64ggb (grab by pressing
// Ctrl+Shift+B), or include the "Material ID" after uploading to Geogebratube
//if commands array is provided, execute these javascript commands
//  (see http://wiki.geogebra.org/en/Reference:JavaScript
//  for available commands).  For example:
//    array('setValue("a",'.$a.')','setValue("b",'.$b.')')
//params should be key=>value array of parameters, like 
//  array('framePossible'=>'true','showToolBar'=>'true')
//  (see http://wiki.geogebra.org/en/Reference:Applet_Parameters for options)
//if you want to pull values from Geogebra on submit, provide an array of 
//  geogebra commands to callback with.  For example:
//      array('getValue("a")','getValue("b")')
//  if doing callbacks, make sure to provide qn (the question number 
//  (1-indexed) - usually use $thisq) and if multipart, part number (0-indexed)
function addGeogebra($url,$width=400,$height=200,$commands=array(),$params=array(),$callback=null,$qn=null,$part=null) {
	$out = '';
	if ($GLOBALS['inquestiondisplay'] == false) {return '';}
	$ggbid = uniqid();
	if (!isset($GLOBALS['geogebracount'])) {
		$GLOBALS['geogebracount'] = 0;
		$out .= '<script type="text/javascript" src="https://cdn.geogebra.org/apps/deployggb.js"></script>';
	}
	$out .= '<script type="text/javascript">';
	if (strlen($url)>10) {
		$out .= 'var applet'.$ggbid.' = new GGBApplet({"ggbBase64":"'.$url.'",';
	} else {
		$out .= 'var applet'.$ggbid.' = new GGBApplet({"material_id":"'.$url.'",';
	}
	$out .= '"ggbOnInitParam":"ggb'.$ggbid.'","id":"ggb'.$ggbid.'","useBrowserForJS":true';
	foreach ($params as $k=>$v) {
		$out .= ",\"$k\":\"$v\"";
	}
	if ($width != "") {
		$out .= ",height:\"$height\",width:\"$width\"";
	}
	$out .= '});';
	$out .= '$(function() { applet'.$ggbid.'.inject("geogebra_container'.$ggbid.'","preferHTML5");});';
	$out .= '</script>';
	$out .= '<div id="geogebra_container'.$ggbid.'"><span id="ggbloadimg'.$ggbid.'">Loading Geogebra...</span></div>';
	
	//if (count($commands)>0) {
		$out .= '<script type="text/javascript">';
		
		$out .= 'if (typeof ggbOnInit == "undefined") {';
		$out .= '  var ggbInitStack = []; ';
		$out .= '  function ggbOnInit(param) {';
		$out .= '      if (param in ggbInitStack) {ggbInitStack[param]();}';
		$out .= '  } } ;';
		$out .= 'ggbInitStack["ggb'.$ggbid.'"] = function () {';
		$out .= '   $("#ggbloadimg'.$ggbid.'").remove(); ';
		foreach ($commands as $com) {
			$out .= 'ggb'.$ggbid.'.'.$com.';';
		}
		$out .= '};';
		$out .= '</script>';
	//}
	if ($callback!=null & $qn != null) {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		
		$out .= '<script type="text/javascript">';
		$out .= ' callbackstack['.$qn.'] = function () {';
		$out .= '   var ansparts = [];';
		foreach ($callback as $com) {
			$out .= '  ansparts.push(ggb'.$ggbid.'.'.$com.');';
		}
		$out .= '   document.getElementById("qn"+'.$qn.').value = ansparts.join(",");';
		$out .= '};</script>';
	}
	$GLOBALS['geogebracount']++;
	return $out;
}

//addGeogebraJava(url,[width,height,commands,params,callbacks,qn,part]) 
//place a geogebra Java applet, calling .ggb file specified in URL
//if commands array is provided, execute these javascript commands
//(see http://www.geogebra.org/en/wiki/index.php/GeoGebra_JavaScript_Methods
//for available commands)
//params should be key=>value array of parameters, like array('framePossible'=>'true','showToolBar'=>'true')
//(see http://www.geogebra.org/en/wiki/index.php/GeoGebra_Applet_Parameters for options)
//if you want to pull values from Geogebra on submit, provide an array of geogebra commands to callback with
//and provide question number (1-indexed - usually use $thisq) and if multipart, part number (0-indexed)
function addGeogebraJava($url,$width=400,$height=200,$commands=array(),$params=array(),$callback=null,$qn=null,$part=null) {
	if (!isset($GLOBALS['geogebracount'])) {
		$GLOBALS['geogebracount'] = 0;
	}
	if (!isset($params['framePossible'])) {
		$params['framePossible'] = 'false';
	}
	$params['ggbOnInitParam'] = $GLOBALS['geogebracount'];
	$out = ' <applet code="geogebra.GeoGebraApplet" ';
	$out .= 'id="geogebra'.$GLOBALS['geogebracount'].'" name="geogebra'.$GLOBALS['geogebracount'].'" ';
	$out .= 'codebase="http://jars.geogebra.org/webstart/4.2/" ';
	$out .= "archive=\"geogebra.jar\" ";
	$out .= 'width="'.$width.'" height="'.$height.'" mayscript="true">';
	if ($url != '') {
		$out .= '<param name="filename" value="'.$url.'" />';
	}
	foreach ($params as $k=>$v) {
		$out .= "<param name=\"$k\" value=\"$v\" />";
	}
	$out .= 'Please <a href="http://www.java.com">install Java 1.4.2</a> (or later) to use this page.';
	$out .= '</applet>';
	
	if (count($commands)>0) {
		$out .= '<script type="text/javascript">';
		$out .= "function ggbOnInit{$GLOBALS['geogebracount']}() {";
		$out .= "var applet=document.getElementById(\"geogebra{$GLOBALS['geogebracount']}\");";  
		foreach ($commands as $com) {
			$out .= 'applet.'.$com.';';
		}
		$out .= '}';
		$out .= 'if (typeof ggbOnInit == "undefined") {';
		$out .= 'function ggbOnInit(val) {';
		$out .= '  setTimeout(window["ggbOnInit"+val],50);';
		$out .= '} } ;';
		$out .= '</script>';
	}
	if ($callback!=null & $qn != null) {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		
		$out .= '<script type="text/javascript">';
		$out .= ' callbackstack['.$qn.'] = function () {';
		$out .= "   var applet=document.getElementById(\"geogebra{$GLOBALS['geogebracount']}\");";  
		$out .= '   var ansparts = [];';
		foreach ($callback as $com) {
			$out .= '  ansparts.push(applet.'.$com.');';
		}
		$out .= '   document.getElementById("qn"+'.$qn.').value = ansparts.join(",");';
		$out .= '};</script>';
	}
	$GLOBALS['geogebracount']++;
	return $out;
}
?>
