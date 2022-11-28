<?php
//Geogebra integration functions, Version 3.0, Oct 4, 2014


global $allowedmacros;
array_push($allowedmacros,"addGeogebra","addGeogebraJava","ggb_axes","ggb_addobject","ggb_addslider","ggb_getparams");

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
	if (isset($GLOBALS['assessUIver']) && $GLOBALS['assessUIver'] > 1) {
		$out .= '<script type="text/javascript" src="https://cdn.geogebra.org/apps/deployggb.js"></script>';
        if (!isset($GLOBALS['geogebracount'])) {
            $GLOBALS['geogebracount'] = 0;
        }
    } else if (!isset($GLOBALS['geogebracount'])) {
		$GLOBALS['geogebracount'] = 0;
		$out .= '<script type="text/javascript" src="https://cdn.geogebra.org/apps/deployggb.js"></script>';
	}
	$out .= '<script type="text/javascript">';
	if ($url == '') {
		$url = 'classic';
	}
	if ($url == 'graphing' || $url == 'geometry' || $url == '3d') {
		$out .= 'var applet'.$ggbid.' = new GGBApplet({"appName":"'.$url.'",';
	} else if ($url == 'spreadsheet' || $url == 'classic' || $url == 'probability') {
		$out .= 'var applet'.$ggbid.' = new GGBApplet({';
		if ($url == 'spreadsheet') {
			$p = ggb_getparams('spreadsheet');
		} else if ($url == 'probability') {
			$p = ggb_getparams('probability');
		} else {
			$p = array("showAlgebraInput"=>true);
		}
		foreach ($p as $k=>$pv) {
			if (!isset($params[$k])) {
				$params[$k] = $pv;
			}
		}
	} else if (strlen($url)>10) {
		$out .= 'var applet'.$ggbid.' = new GGBApplet({"ggbBase64":"'.$url.'",';
	} else {
		$out .= 'var applet'.$ggbid.' = new GGBApplet({"material_id":"'.$url.'",';
	}
	//$out .= '"ggbOnInitParam":"ggb'.$ggbid.'","id":"ggb'.$ggbid.'","useBrowserForJS":true';
	$out .= '"id":"ggb'.$ggbid.'"';
	foreach ($params as $k=>$v) {
		$out .= ",\"$k\":";
		if ($v === true || $v === 'true') {
			$out .= 'true';
		} else if ($v === false || $v === 'false') {
			$out .= 'false';
		} else {
			$out .= '"' . $v . '"';
		}
	}
	if ($width != "") {
		$out .= ",height:\"$height\",width:\"$width\"";
	}
	$out .= ',appletOnLoad:function(api) {';
	$out .= '   $("#ggbloadimg'.$ggbid.'").remove(); ';
	$out .= '  $("#geogebra_container'.$ggbid.'").on("keydown", function(e) {if (e.keyCode==13) { console.log("1");e.preventDefault(); return false;}});';
	$out .= '  $(document).on("keydown", function(e) {if (e.keyCode==13) {window.lastEnterKeyPressed =  e.timeStamp;}});';
	$out .= '  window.lastEnterKeyPressed = 0;
	  $("form").on("submit", function(e) { if (e.originalEvent.submitter.className.match(/gwt-/)) {e.preventDefault(); return false;}});
	  $("input[type=submit]").on("click", function(e) {
		if (e.detail==0 && (e.timeStamp - window.lastEnterKeyPressed)>20) {
			e.preventDefault(); return false;
		}
	});';
	foreach ($commands as $com) {
		$out .= 'api.'.$com.';';
	}
	$out .= '}';
	$out .= '}, "5.0", "geogebra_container'.$ggbid.'");';
	$out .= '$(function() { applet'.$ggbid.'.inject("geogebra_container'.$ggbid.'");});';
	$out .= '</script>';
	$out .= '<div id="geogebra_container'.$ggbid.'"><span id="ggbloadimg'.$ggbid.'">Loading Geogebra...</span></div>';
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

function ggb_axes($commands,$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$xscl=1,$yscl=1) {
	if (!is_array($commands)) {
		$commands = array();
	}
	$commands[] = 'setCoordSystem('.floatval($xmin).','.floatval($xmax).','.floatval($ymin).','.floatval($ymax).')';
	$commands[] = 'setAxisSteps(1,'.floatval($xscl).','.floatval($yscl).')';
	return $commands;
}

function ggb_addobject($commands,$object,$label=null, $labelvis = true,$fixed = false,$aux = false) {
	if ($label === null) {
		$label = chr(65 + count($commands));
	}
	$commands[] = 'evalCommand("'.$label .':'.$object.'")';
	if ($labelvis === false) {
		$commands[] = 'setLabelVisible("'.$label.'",false)';
	}
	if ($fixed === true) {
		$commands[] = 'setFixed("'.$label.'",true)';
	}
	if ($aux === true) {
		$commands[] = 'setAuxiliary("'.$label.'",true)';
	}
	return $commands;
}

function ggb_addslider($commands,$label,$min=-5,$max=5,$step=0.1,$vis=false) {
	$commands[] = 'evalCommand("'.$label .'=Slider('.floatval($min).','.floatval($max).','.floatval($step).')")';
	if ($vis === false) {
		$commands[] = 'setVisible("'.$label.'",false)';
	}
	return $commands;
}

function ggb_getparams($type) {
	if ($type == 'graphing_input') {
		return ["showAlgebraInput" => true, "showMenuBar" => true];
	} else if ($type == 'spreadsheet') {
		return ["perspective"=>"S", "showAlgebraInput" => true, "showMenuBar" => true, "showToolBar"=>true, "showToolBarHelp"=>false];
	} else if ($type == 'probability') {
		return ["perspective"=>"B", "showAlgebraInput" => true, "showMenuBar" =>false, "showToolBar"=>false, "showToolBarHelp"=>false];
	}
}


?>
