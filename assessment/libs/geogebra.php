<?php
//Geogebra integration functions, Version 2.0, Dec 20, 2012


global $allowedmacros;
array_push($allowedmacros,"addGeogebra","addGeogebraJava");

//addGeogebra(url,[width,height,commands,params,callbacks,qn,part]) 
//place a geogebra HTML5 applet, calling .ggb file specified in URL 
//  ggb must be on the same server, or ggbbase64 data as a string
//if commands array is provided, execute these javascript commands
//  (see http://www.geogebra.org/en/wiki/index.php/GeoGebra_JavaScript_Methods
//  for available commands)
//params should be key=>value array of parameters, like 
//  array('framePossible'=>'true','showToolBar'=>'true')
//  (see http://www.geogebra.org/en/wiki/index.php/GeoGebra_Applet_Parameters for options)
//if you want to pull values from Geogebra on submit, provide an array of 
//  geogebra commands to callback with and provide question number 
//  (1-indexed - usually use $thisq) and if multipart, part number (0-indexed)
function addGeogebra($url,$width=400,$height=200,$commands=array(),$params=array(),$callback=null,$qn=null,$part=null) {
	$out = '';
	if ($GLOBALS['inquestiondisplay'] == false) {return '';}
	if (!isset($GLOBALS['geogebracount'])) {
		$GLOBALS['geogebracount'] = 0;
		$out .= '<script type="text/javascript" src="//www.geogebra.org/web/4.2/web/web.nocache.js"></script>';
	}
	$out .= ' <article class="geogebraweb" id="geogebra'.$GLOBALS['geogebracount'].'" ';
	$out .= 'data-param-ggbOnInitParam="'.$GLOBALS['geogebracount'].'" data-param-id="ggbApplet'.$GLOBALS['geogebracount'].'" '; 
	if (substr($url,0,4)=='http') {
		$out .= 'data-param-filename="'.$url.'" ';
	} else {
		$out .= 'data-param-ggbbase64="'.$url.'" ';
	}
	foreach ($params as $k=>$v) {
		$out .= "data-param-$k=\"$v\" ";
	}
	$out .= "data-param-height=\"$height\" data-param-width=\"$width\" ";
	$out .= '><span style="position:absolute;">Please wait while Geogebra loads...</span></article>';
	
	if (count($commands)>0) {
		$out .= '<script type="text/javascript">';
		
		$out .= 'if (typeof gbbOnInit == "undefined") {';
		$out .= '  var ggbInitStack = []; ';
		$out .= '  function ggbOnInit() {';
		$out .= '      for (i in ggbInitStack) {setTimeout(ggbInitStack[i],50);}';
		$out .= '  } } ;';
		$out .= 'ggbInitStack.push(function () {';
		$out .= "   var applet=document.ggbApplet{$GLOBALS['geogebracount']};";
		foreach ($commands as $com) {
			$out .= 'applet.'.$com.';';
		}
		$out .= '});';
		$out .= '</script>';
	}
	if ($callback!=null & $qn != null) {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		
		$out .= '<script type="text/javascript">';
		$out .= ' callbackstack['.$qn.'] = function () {';
		$out .= "   var applet=document.ggbApplet{$GLOBALS['geogebracount']};";  
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
		$out .= 'if (typeof gbbOnInit == "undefined") {';
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
