<?php
// Conversion module - this contains constants for use with Rate and Ratio conversion questions
// Mike Jenck, Originally developed Jan 29-31, 2021
// licensed under GPL version 2 or later
//

// NOTE: _('word') is a call to gettext() for localization
//
// Watch the fllowing videos for more explaination
//Part 1 of 5: https://youtu.be/363wrIjz9vU
//Part 2 of 5: https://youtu.be/fORDl7Aectk
//Part 3 of 5: https://youtu.be/3Wmu9g7uCME
//
// This video uses Poedit editor to intialize the po files
//
// https://poedit.net/download
//
//Part 4 of 5: https://youtu.be/0GWYdXhj1bI
//Part 5 of 5: https://youtu.be/2UXSdTNPlPA

function conversionVer() {
	// File version
	return 22;
}

global $allowedmacros;

if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "conversionVer", "conversionAbbreviations",  "conversionArea",
    "conversionCapacity", "conversionDisplay", "conversionDisplay2HTML", "conversionDisplay2HTMLwithBorder",
    "conversionFormulaAbbreviations", "conversionFormulaGeometry", "conversionFormulaTemperature",
    "conversionLength", "conversionLiquid", "conversionPrefix", "conversionTime",
    "conversionUnits2ScreenReader1", "conversionUnits2ScreenReader2", "conversionVolume", "conversionWeight",
    "conversion_extract_column_array", "conversionTime2");

// internal only  ----------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------

function verifyCancel($input) {
//    s - skip (default)
//    n - around the number
//    u - around the units
//    b - around
    $Retval = "s";

	if(!is_null($input)) {
        if(strtolower($input)=="n"){
            $Retval = "n";
        } elseif (strtolower($input)=="u"){
            $Retval = "u";
        }elseif (strtolower($input)=="b"){
            $Retval = "b";
        }
	}

	return $Retval;
}

function verifyRounding($input) {
	if(!is_null($input)) {
		$rounding = $input;
		if($rounding<1) {
            $rounding=1;
        } elseif ($rounding>8){
            $rounding=8;
        }
	}
	else { $rounding=2; }

	return $rounding;
}

function verifyFullName($input) {
	if(!is_null($input)) {
		$fullname = $input;
		if($fullname<0) {
            $fullname=0;
        } elseif ($fullname>2){
            $fullname=0;
        }
	}
	else { $fullname=0; }

	return $fullname;
}

function verifyPI($input){
    $retval = " pi ";

    if(!is_null($input)) {
        $input = strtolower($input);
        if($input=="h") {
            $retval = "&#8508;"; // HTML pi symbol
        }
    }
    return $retval;
}

function verifyTickMarks($input) {
    $TickMarks = "";

	if(!is_null($input)) {
        if((strtolower($input)=="y")||($input=="`")) { $TickMarks = "`"; }
	}

	return $TickMarks;
}

function verifyEqualSign($input,$tick) {
    $TickMarks = verifyTickMarks($tick);
	if(!is_null($input)) {
        if($input=="=") {
            $retval = "=";
        } elseif($input=="~") {
            $retval = "$TickMarks~~$TickMarks";
        } else {
            $retval =  "&#8776;"; //&#8776; &#x2248; &thickapprox;
        }
	}
	else { $retval = "="; }

	return $retval;
}

function verifyString($input) {
	if(!is_null($input)) {
		$retval =  $input;
	}
	else { $retval = ""; }

	return $retval;
}

// function conversionAbbreviations(system,type[,tick,Fullname])
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "A" - American
//           "M" - Metric
//           "T" - Time
//
//     type: Length
//           Capacity
//           Weight
//           Area
//           Volume
//
//     tick: add a tick mark around items with exponents
// Fullname: determines the order of the word square/cube in the full name of the words
//           e.g.Inches squared/Square inches
//
// Examples
//
// use conversionAbbreviations("A","Length") returns an array of strings that have american abbreviations of length
function conversionAbbreviations() {


	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	} else {
        $system = strtoupper(substr($args[0], 0, 1));
        if($system!='A' && $system!='M' && $system!='T' ) {
            echo (string)$system." is not a valid type. The system type is American, Metric, or Time";
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $temp = verifyString($args[1]);
        if(strlen($temp)==0 ) {
            $FirstLetter = "L";
        } else {
            $FirstLetter = strtoupper(substr($temp, 0, 1));
        }
    } else {
        $FirstLetter = "L";
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $tick = verifyTickMarks($args[2]);
    } else {
        $tick = "";
    }

    if ( count($args)>3 && !is_null($args[3]) ) {
        $fullname = verifyFullName($args[3]);
    } else {
        $fullname = 0;
    }

	if($FirstLetter=="L") {$type="Length";}
	if($FirstLetter=="C") {$type="Capacity";}
	if($FirstLetter=="W") {$type="Weight";}
	if($FirstLetter=="M") {$type="Mass";}
	if($FirstLetter=="A") {$type="Area";}
	if($FirstLetter=="V") {$type="Volume";}

	if(($type!="Length")&&($type!="Capacity")&&($type!="Weight")&&($type!="Area")&&($type!="Volume")&&($type!="Mass")){
		$type="Length";
    }

    $retval = array();

	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="A"){
		if($type=="Length"){
			$retval[0] = _("Inches")." = "._("in");
			$retval[1] = _("Feet")." = "._("ft");
			$retval[2] = _("Yards")." = "._("yd");
			$retval[3] = _("Miles")." = "._("mi");
        } elseif($type=="Capacity"){
			$retval[0] = _("Fluid ounces")." = "._("fl oz");
			$retval[1] = _("Cups")." = "._("c");
			$retval[2] = _("Pints")." = "._("pt");
			$retval[3] = _("Quarts")." = "._("qt");
			$retval[4] = _("Gallons")." = "._("gal");
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = _("Ounces")." = "._("oz");
			$retval[1] = _("Pounds")." = "._("lbs");
			$retval[2] = _("Tons")." = "._("T");
        } elseif($type=="Area"){
			if($fullname==0) {
                $retval[0] = _("Inches squared")." = ".conversionUnits2ScreenReader1("",_("in"),2,$tick);
                $retval[1] = _("Feet squared")." = ".conversionUnits2ScreenReader1("",_("ft"),2,$tick);
                $retval[2] = _("Yard squared")." = ".conversionUnits2ScreenReader1("",_("yd"),2,$tick);
                $retval[3] = _("Mile squared")." = ".conversionUnits2ScreenReader1("",_("mi"),2,$tick);
            } else {
                $retval[0] = _("Square inches")." = ".conversionUnits2ScreenReader1("",_("in"),2,$tick);
                $retval[1] = _("Square feet")." = ".conversionUnits2ScreenReader1("",_("ft"),2,$tick);
                $retval[2] = _("Square yard")." = ".conversionUnits2ScreenReader1("",_("yd"),2,$tick);
                $retval[3] = _("Square mile")." = ".conversionUnits2ScreenReader1("",_("mi"),2,$tick);
            }

        } elseif($type=="Volume"){
			if($fullname==0) {
                $retval[0] = _("Inches cubed")." = ".conversionUnits2ScreenReader1("",_("in"),3,$tick);
                $retval[1] = _("Feet cubed")." = ".conversionUnits2ScreenReader1("",_("ft"),3,$tick);
                $retval[2] = _("Yard cubed")." = ".conversionUnits2ScreenReader1("",_("yd"),3,$tick);
            } else {
                $retval[0] = _("Cubic inches")." = ".conversionUnits2ScreenReader1("",_("in"),3,$tick);
                $retval[1] = _("Cubic feet")." = ".conversionUnits2ScreenReader1("",_("ft"),3,$tick);
                $retval[2] = _("Cubic yard")." = ".conversionUnits2ScreenReader1("",_("yd"),3,$tick);
            }
        }

	}

	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="M"){
        if($type=="Length"){
			$retval[0] = _("Millimeter")." = "._("mm");
			$retval[1] = _("Centimeter")." = "._("cm");
			$retval[2] = _("Decimeter")." = "._("dm");
			$retval[3] = _("Meter")." = "._("m");
			$retval[4] = _("Dekameter")." = ".conversionUnits2ScreenReader1("",_("dam"),1,"n");
			$retval[5] = _("Hectometer")." = "._("hm");
			$retval[6] = _("Kilometer")." = "._("km");
        } elseif($type=="Capacity"){
			$retval[0] = _("Milliliter")." = "._("mL");
			$retval[1] = _("Centiliter")." = "._("cL");
			$retval[2] = _("Deciliter")." = "._("dL");
			$retval[3] = _("Liter")." = "._("L");
			$retval[4] = _("Dekaliter")." = ".conversionUnits2ScreenReader1("",_("daL"),1,"n");
			$retval[5] = _("Hectoliter")." = "._("hL");
			$retval[6] = _("Kiloliter")." = "._("kL");
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = _("Milligram")." = "._("mg");
			$retval[1] = _("Centigram")." = "._("cg");
			$retval[2] = _("Decigram")." = "._("dg");
			$retval[3] = _("Gram")." = "._("g");
			$retval[4] = _("Dekagram")." = ".conversionUnits2ScreenReader1("",_("dag"),1,"n");
			$retval[5] = _("Hectogram")." = "._("hg");
			$retval[6] = _("Kilogram")." = "._("kg");
			$retval[7] = _("Metric Ton")." = "._("Tonne");
        } elseif($type=="Area"){
			if($fullname==0) {
                $retval[0] = _("Millimeter squared")." = ".conversionUnits2ScreenReader1("",_("mm"),2,$tick);
                $retval[1] = _("Centimeter squared")." = ".conversionUnits2ScreenReader1("",_("cm"),2,$tick);
                $retval[2] = _("Decimeter squared")." = ".conversionUnits2ScreenReader1("",_("dm"),2,$tick);
                $retval[3] = _("Meter squared")." = ".conversionUnits2ScreenReader1("",_("m"),2,$tick);
                $retval[4] = _("Dekameter squared")." = ".conversionUnits2ScreenReader1("",_("dam"),2,$tick);
                $retval[5] = _("Hectometer squared")." = ".conversionUnits2ScreenReader1("",_("hm"),2,$tick);
                $retval[6] = _("Kilometer squared")." = ".conversionUnits2ScreenReader1("",_("km"),2,$tick);
            } else {
                $retval[0] = _("Square millimeter")." = ".conversionUnits2ScreenReader1("",_("mm"),2,$tick);
                $retval[1] = _("Square centimeter")." = ".conversionUnits2ScreenReader1("",_("cm"),2,$tick);
                $retval[2] = _("Square decimeter")." = ".conversionUnits2ScreenReader1("",_("dm"),2,$tick);
                $retval[3] = _("Square meter")." = ".conversionUnits2ScreenReader1("",_("m"),2,$tick);
                $retval[4] = _("Square dekameter")." = ".conversionUnits2ScreenReader1("",_("dam"),2,$tick);
                $retval[5] = _("Square hectometer")." = ".conversionUnits2ScreenReader1("",_("hm"),2,$tick);
                $retval[6] = _("Square kilometer")." = ".conversionUnits2ScreenReader1("",_("km"),2,$tick);
            }
			$retval[7] = _("Ares")." = "._("a");
			$retval[8] = _("Hectares")." = "._("ha");
        } elseif($type=="Volume") {
			if($fullname==0) {
                $retval[0] = _("Millimeter cubed")." = ".conversionUnits2ScreenReader1("",_("mm"),3,$tick);
                $retval[1] = _("Centimeter cubed")." = ".conversionUnits2ScreenReader1("",_("cm"),3,$tick);
                $retval[2] = _("Decimeter cubed")." = ".conversionUnits2ScreenReader1("",_("dm"),3,$tick);
                $retval[3] = _("Meter cubed")." = ".conversionUnits2ScreenReader1("",_("m"),3,$tick);
                $retval[4] = _("Dekameter cubed")." = ".conversionUnits2ScreenReader1("",_("dam"),3,$tick);
                $retval[5] = _("Hectometer cubed")." = ".conversionUnits2ScreenReader1("",_("hm"),3,$tick);
                $retval[6] = _("Kilometer cubed")." = ".conversionUnits2ScreenReader1("",_("km"),3,$tick);
            } else {
                $retval[0] = _("Cubic millimeter")." = ".conversionUnits2ScreenReader1("",_("mm"),3,$tick);
                $retval[1] = _("Cubic centimeter")." = ".conversionUnits2ScreenReader1("",_("cm"),3,$tick);
                $retval[2] = _("Cubic decimeter")." = ".conversionUnits2ScreenReader1("",_("dm"),3,$tick);
                $retval[3] = _("Cubic meter")." = ".conversionUnits2ScreenReader1("",_("m"),3,$tick);
                $retval[4] = _("Cubic dekameter")." = ".conversionUnits2ScreenReader1("",_("dam"),3,$tick);
                $retval[5] = _("Cubic hectometer")." = ".conversionUnits2ScreenReader1("",_("hm"),3,$tick);
                $retval[6] = _("Cubic kilometer")." = ".conversionUnits2ScreenReader1("",_("km"),3,$tick);
            }
        }
	}

    // -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="T"){
        $retval[0] = _("Seconds")." = "._("sec");
		$retval[1] = _("Minutes")." = "._("min");
		$retval[2] = _("Hours")." = "._("hr");
		$retval[3] = _("Days")." = "._("d");
		$retval[4] = _("Years")." = "._("yr");
        $retval[5] = _("Decade")." = "._("dec");
		$retval[6] = _("Centuries")." = "._("c");
    }

	return $retval;
}

// Version 2 functions:
// Inputs - identical to the version 1 functions
//
// Outputs and array of arrays of strings with array format
//
// $retval[] = array([0] version 1 output conversion factor
//                   [1], left hand side number (almost always 1)
//                   [2], left hand side units
//                   [3], right hand side number
//                   [4], right hand side units)

// function conversion_extract_column_array($v2,$columnindex)
// returns an array of strings from the selected column
//
// INPUTS:
//   vs = version 2 array
//
// columnindex: column to be extracted
//
// Examples
//
// conversion_extract_column_array($v2,0) extracts the version 1 conversion strings
//
function conversion_extract_column_array($v2,$columnindex) {
    $retval = array();

    for($i=0;$i<count($v2);$i+=1){
        $retval[] = $v2[$i][$columnindex];
    }

    return $retval;
}

function isnotvalid() {
    return _(" is not a valid type.");
}

function isnotvalidC() {
    return _(" is not a valid type. The system type is Casks.");
}

function isnotvalidAMT() {
    return _(" is not a valid type. The system type is A (American), M (Metric), or T (Time).");
}

// function conversionArea(type [,FullWords,Rounding,tick,Sign])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name (feet squared)
//            2 = use Full name (square feet)
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//     Sign: use an = or html approximately equal symbol
//
// Examples
//
// use conversionArea("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionArea() {

    $args = func_get_args();
    if (count($args)==0) {
        echo _("Nothing to display - no system type supplied.")."<br/>\r\n";
        return "";
    } else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system.isnotvalidAMT();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $rounding = verifyRounding($args[2]);
    } else {
        $rounding = 2;
    }

    if ( count($args)>3 && !is_null($args[3]) ) {
        $tick = $args[3];
    } else {
        $tick = "";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $sign_no = verifyEqualSign($args[4],"n");
        $sign = verifyEqualSign($args[4],$tick);
    } else {
        $sign_no = verifyEqualSign("=","n");
        $sign = verifyEqualSign("=",$tick);
    }

    $retval = array();

    if($system=="A"){
        $acre = _("acre");
        if($fullname==0) {
            // if $tick = "n" then no " on acre
            $retval[0] = conversionUnits2ScreenReader2("1 ",_("ft"),2,"144 ",_("in"),2,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ",_("yd"),2,"9 ",_("ft"),2,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ","\"$acre\"",1,"43,560 ",_("ft"),2,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ",_("mi"),2,"640 ","\"$acre\"",1,"=",$tick);
        } else {
            $retval[0] = "1 "._("feet squared")." = 144 "._("inches squared");
            $retval[1] = "1 "._("yard squared")." = 9 "._("feet squared");
            $retval[2] = "1 $acre  = 43,560 "._("feet squared");
            $retval[3] = "1 "._("mile squared")."  = 640 $acre";
        }
    } elseif($system=="M"){
        if($fullname==0) {
            $aresabbr = _("a");
            $hectaresabbr = _("ha");
            $retval[0] = conversionUnits2ScreenReader2("1 ",_("km"),2,"100 ",_("hm"),2,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ",_("hm"),2,"100 ",_("dam"),2,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ",_("dam"),2,"100 ",_("m"),2,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ",_("m"),2,"100 ",_("dm"),2,"=",$tick);
            $retval[4] = conversionUnits2ScreenReader2("1 ",_("dm"),2,"100 ",_("cm"),2,"=",$tick);
            $retval[5] = conversionUnits2ScreenReader2("1 ",_("cm"),2,"100 ",_("mm"),2,"=",$tick);
            $retval[6] = conversionUnits2ScreenReader2("1 ","\"$aresabbr\"",1,"100 ",_("m"),2,"=",$tick);
            $retval[7] = conversionUnits2ScreenReader2("1 ","\"$hectaresabbr\"",1,"100 ","\"$aresabbr\"",1,"=",$tick);
        } elseif($fullname==1) {
            $retval[0] = "1 "._("Kilometer squared")." = 100 "._("Hectometer squared");
            $retval[1] = "1 "._("Hectometer squared")." = 100 "._("Dekameter squared");
            $retval[2] = "1 "._("Dekameter squared")." = 100 "._("Meter squared");
            $retval[3] = "1 "._("Meter squared")." = 100 "._("Decimeter squared");
            $retval[4] = "1 "._("Decimeter squared")." = 100 "._("Centimeter squared");
            $retval[5] = "1 "._("Centimeter squared")." = 100 "._("Millimeter squared");
            $retval[6] = "1 "._("Ares")." = 100 "._("Meter squared");
            $retval[7] = "1 "._("Hectares")." = 100 "._("Ares");
        } else  {
            $retval[0] = "1 "._("Square kilometer")." = 100 "._("Square hectometer");
            $retval[1] = "1 "._("Square hectometer")." = 100 "._("Square dekameter");
            $retval[2] = "1 "._("Square dekameter")." = 100 "._("Square meter");
            $retval[3] = "1 "._("Square meter")." = 100 "._("Square decimeter");
            $retval[4] = "1 "._("Square decimeter")." = 100 "._("Square centimeter");
            $retval[5] = "1 "._("Square centimeter")." = 100 "._("Square millimeter");
            $retval[6] = "1 "._("Ares")." = 100 "._("Square meter")." ";
            $retval[7] = "1 "._("Hectares")." = 100 "._("Ares");
        }
    } elseif($system=="AM"){
        //6.45160000 cm^2 https://www.wolframalpha.com/input/?i=convert+1+square+inch+to+mm+squared
        $CF = round(6.4516, $rounding);
        if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ",_("in"),2,"$CF ",_("cm"),2,$sign,$tick);
        } elseif($fullname==1) {
            $retval[0] = "1 "._("Inch squared")." $sign $CF "._("Centimeter squared");
        } else {
            $retval[0] = "1 "._("Square inch")." $sign $CF "._("Square centimeter");
        }
    } elseif($system=="MA"){
        // 1.19599005 yd^2 https://www.wolframalpha.com/input/?i=convert+1+square+meter+to+square+feet
        // https://www.wolframalpha.com/input/?i=convert+1+hectares+to+square+feet
        $CF0 = round(1.19599005, $rounding);
        $CF1 = round(2.471, $rounding);
        if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ",_("m"),2,"$CF0 ",_("yd"),2,$sign_no,$tick);
            $retval[1] = "1 "._("ha")." $sign $CF1 "._("acres");
        } elseif($fullname==1) {
            $retval[0] = "1 "._("Meters squared")." $sign $CF0 "._("Yard squared");
            $retval[1] = "1 "._("hectares")." $sign $CF1 "._("acres");
        } else {
            $retval[0] = "1 "._("Square meter")." $sign $CF0 "._("Square yard");
            $retval[1] = "1 "._("hectares")." $sign $CF1 "._("acres");
        }
    } else {
        $retval[0] = "'".(string)$system."' ".isnotvalid();
    }

    return $retval;
}

// function conversionCapacity(type [,FullWords,Rounding,Sign])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name
//
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     Sign: = gives you =
//           ~ gives you ~~
//          "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionCapacity("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionCapacity() {

	$args = func_get_args();
	if (count($args)==0) {
		echo _("Nothing to display - no system type supplied.")."<br/>\r\n";
		return "";
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system.isnotvalidAMT();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $rounding = verifyRounding($args[2]);
    } else {
        $rounding = 2;
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    if ( count($args)>3 && !is_null($args[3]) ) {
        $sign = verifyEqualSign($args[3],$tick);
    } else {
        $sign = verifyEqualSign("=",$tick);
    }

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 "._("c")." = 8 "._("fl oz");
            $retval[1] = "1 "._("pt")." = 2 "._("c");
            $retval[2] = "1 "._("qt")." = 2 "._("pt");
            $retval[3] = "1 "._("gal")." = 4 "._("qt");
        } else {
            $retval[0] = "1 "._("Cup")." = 8 "._("fluid ounces");
            $retval[1] = "1 "._("pint")." = 2 "._("Cups");
            $retval[2] = "1 "._("quart")." = 2 "._("pint");
            $retval[3] = "1 "._("gallon")." = 4 "._("quart");
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 "._("kL")." = 1000 "._("L");
            $retval[1] = "1 "._("hL")." = 100 "._("L");
            $retval[2] = conversionUnits2ScreenReader1("1 ",_("daL"),1,"n")." = 10 "._("L");
            $retval[3] = "1 "._("L")." = 10 "._("dL");
            $retval[4] = "1 "._("L")." = 100 "._("cL");
			$retval[5] = "1 "._("L")." = 1000 "._("mL");
        } else {
            $retval[0] = "1 "._("kiloliter")." = 1000 "._("Liter");
            $retval[1] = "1 "._("hectoliter")." = 100 "._("Liter");
            $retval[2] = "1 "._("dekaliter")." = 10 "._("Liter");
            $retval[3] = "1 "._("Liter")." = 10 "._("deciliter");
            $retval[4] = "1 "._("Liter")." = 100 "._("centiliter");
            $retval[5] = "1 "._("Liter")." = 1000 "._("milliliter");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 "._("fl oz")." $sign ".round(0.0295735296, $rounding)." "._("L");
            $retval[1] = "1 "._("C")." $sign ".round(0.236588236, $rounding)." "._("L");
            $retval[2] = "1 "._("pt")." $sign ".round(0.473176473, $rounding)." "._("L");
            $retval[3] = "1 "._("qt")." $sign ".round(0.946352946, $rounding)." "._("L");
			$retval[4] = "1 "._("gal")." $sign ".round(3.78541178, $rounding)." "._("L");
        } else {
			$retval[0] = "1 "._("fluid ounces")." $sign ".round(0.0295735296, $rounding)." "._("Liter");  // 29.5735296 mL  https://www.wolframalpha.com/input/?i=convert+1+fluid+ounce+to+liters
            $retval[1] = "1 "._("cup")." $sign ".round(0.236588236, $rounding)." "._("Liter");  // 236.588236 mL  https://www.wolframalpha.com/input/?i=convert+1+cup+to+liters
            $retval[2] = "1 "._("pint")." $sign ".round(0.473176473, $rounding)." "._("Liter");  // 473.176473 mL  https://www.wolframalpha.com/input/?i=convert+1+pint+to+liters
            $retval[3] = "1 "._("quart")." $sign ".round(0.946352946, $rounding)." "._("Liter");   // 946.352946 mL https://www.wolframalpha.com/input/?i=convert+1+quart+to+liters
			$retval[4] = "1 "._("gallon")." $sign ".round(3.78541178, $rounding)." "._("Liter");  // 3.78541178 L https://www.wolframalpha.com/input/?i=convert+1+gallon+to+milliliters
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 "._("L")." $sign ".round(33.8140227, $rounding)." "._("fl oz");  // 33.8140227 fl oz (fluid ounces)  https://www.wolframalpha.com/input/?i=convert+1+liter+to+pints
            $retval[1] = "1 "._("L")." $sign ".round(4.22675284, $rounding)." "._("C"); //  2.11337642 pints *2
            $retval[2] = "1 "._("L")." $sign ".round(2.11337642, $rounding)." "._("pt");    // 2.11337642 pints   https://www.wolframalpha.com/input/?i=convert+1+liter+to+fluid+ounces
            $retval[3] = "1 "._("L")." $sign ".round(1.05668821, $rounding)." "._("qt");    // 1.05668821 quarts
			$retval[4] = "1 "._("L")." $sign ".round(0.264172052, $rounding)." "._("gal");  // 0.264172052 gallons
        } else {
			$retval[0] = "1 "._("Liter")." $sign ".round(33.8140227, $rounding)." "._("fluid ounces");
            $retval[1] = "1 "._("Liter")." $sign ".round(4.22675284, $rounding)." "._("Cup");
            $retval[2] = "1 "._("Liter")." $sign ".round(2.11337642, $rounding)." "._("pint");
            $retval[3] = "1 "._("Liter")." $sign ".round(1.05668821, $rounding)." "._("quart");
			$retval[4] = "1 "._("Liter")." $sign ".round(0.264172052, $rounding)." "._("gallon");
        }
	} else {
        $retval[0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// function conversionDisplay(Title1,Factors1,[Title2,Factors2,...])
//
// INPUTS:
//     Title: string like Length, Capacity, etc.
//   Factors: An array of strings that are displayed
//
// RETURNS: an array of HTML code to diplay the results.
//
// Examples
//
// use  ConversionDisplay("Length Conversions",ConversionLength("A")); returns an array of length 1 of the HTML to
//                                                                     display the american length conversion factors
function conversionDisplay() {
	$args = func_get_args();
	$argCount = count($args);
    $retval = array();
    if ($argCount==0) {
		return $retval;
	}

    $index = 0;
    for($h=0;$h<$argCount;$h+=2){
        $j = $h+1;
        $Title = (string)$args[$h];
        $Factors = $args[$j];
        $element = count($Factors);

        if(strlen($Title) > 0) {
            //$retval[$index] = "<ul>\r\n<li>$Title</li>\r\n";
            $retval[$index] = "$Title\r\n";
        } else {
            $retval[$index] = "";
        }

        if($element >0){
            $retval[$index].= "<ul>\r\n";

            for($i=0; $i < $element; $i++){
                $retval[$index].= "<li>".$Factors[$i]."</li>\r\n";
            }
            $retval[$index].= "</ul>\r\n";
        }
        //if(strlen($Title) > 0) { $retval[$index].= "</ul>\r\n";}
        $index+=1;
    }
	return $retval;
}

// function conversionDisplay2HTML(CellValueArray,$cellPadding=4)
//
// INPUTS:
//     CellValueArray: an array of strings that are stored 1 per cell
//
// RETURNS: HTML code to diplay the results.
//
// Examples
//
// use  ConversionDisplay2HTML(array("cell 1","Cell 2"); returns the HTML to display the
function conversionDisplay2HTML($CellValueArray,$cellPadding=4) {
	$HTML = "<table>\r\n<tr valign='top'>";
    $element = count($CellValueArray);

    for($i=0; $i < $element; $i++){
        $HTML.="<td style=\"padding: $cellPadding"."px;\">$CellValueArray[$i]</td>\r\n";
    }
    $HTML.= "</tr>\r\n</table>\r\n";

	return $HTML;
}

// function conversionDisplay2HTMLwithBorder(CellValueArray,cellPadding=7)
//
// INPUTS:
//     CellValueArray: an array of strings that are stored 1 per cell
//        cellPadding: space around the content of the cell default 7px
//
// RETURNS: HTML code to diplay the results.
//
// Examples
//
// use  conversionDisplay2HTMLwithBorder(array("cell 1","Cell 2"); returns the HTML to display with a border
function conversionDisplay2HTMLwithBorder($CellValueArray,$cellPadding=7) {
	$HTML = "<table style=\"border: 1px solid black;border-collapse: collapse;\">\r\n<tr valign='top'>";
    $element = count($CellValueArray);

    for($i=0; $i < $element; $i++){
        $HTML.="<td style=\"border: 1px solid black;padding: $cellPadding"."px;\">$CellValueArray[$i]</td>\r\n";
    }
    $HTML.= "</tr>\r\n</table>\r\n";

	return $HTML;
}

// function conversionFormulaAbbreviations(type)
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "C" = Circle (default)
//           "T" = Triangle
//           "R" = Rectangle
//           "S" = Square
//           "A" = Area
//           "V" = Volume
//           "F" = Temperature
//
function conversionFormulaAbbreviations() {
    $args = func_get_args();
	if (count($args)==0) {
		$firstPart = "C";  // Circle
	} else {
        $type = $args[0];
        $firstPart = strtoupper(substr($type, 0, 1));
    }
    $retval = array();

    if($firstPart=="C") {$type="Circle";}
    if($firstPart=="T") {$type="Triangle";}
    if($firstPart=="R") {$type="Rectangle";}
    if($firstPart=="S") {$type="Square";}
    if($firstPart=="A") {$type="Area";}
    if($firstPart=="V") {$type="Volume";}
    if($firstPart=="F") {$type="Temperature";}

    if($type=="Circle") {
        $retval[0] = "C = "._("Circumference"); // of a circle
        $retval[1] = "A = "._("Area");
        $retval[2] = "r = "._("Radius");
        $retval[3] = "d = "._("Diameter");
    } elseif($type=="Rectangle") {
        $retval[0] = "P = "._("Perimeter");
        $retval[1] = "A = "._("Area");
        $retval[2] = "L = "._("Length");
        $retval[3] = "W = "._("Width");
    } elseif($type=="Square") {
        $retval[0] = "P = "._("Perimeter");
        $retval[1] = "A = "._("Area");
        $retval[2] = "s = "._("side");
    } elseif($type=="Area") {
        $retval[0] = "SA = "._("Surface Area");
        $retval[1] = "L = "._("Length");
        $retval[2] = "W = "._("Width");
        $retval[3] = "H or h = "._("Height");
        $retval[4] = "s = "._("Side");
        $retval[5] = "r = "._("Radius");
    } elseif($type=="Volume") {
        $retval[0] = "V = "._("Volume");
        $retval[1] = "L = "._("Length");
        $retval[2] = "W = "._("Width");
        $retval[3] = "H or h = "._("Height");
        $retval[4] = "s = "._("Side");
        $retval[5] = "r = "._("Radius");
    } elseif($type=="Triangle") {
        $retval[0] = "P = "._("Perimeter");
        $retval[1] = "A = "._("Area");
        $retval[2] = "b = "._("base");
        $retval[3] = "h = "._("Height");
    } elseif($type=="Temperature") {
        $retval[0] = "C = "._("Celsius");
        $retval[1] = "F = "._("Fahrenheit");
        $retval[2] = "K = "._("Kelvin");
    } else {
        $retval[0] = "'".(string)$type."' ".isnotvalid();
    }

	return $retval;
}

// function conversionFormulaGeometry(type,[tick=y,pi])
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "C" = Circle (default)
//           "T" = Triangle
//           "R" = Rectangle
//           "S" = Square
//           "A" = Surface Area
//           "V" = Volume
//
//        pi - blank = the letters pi
//           - h = the html entity for pi
//
function conversionFormulaGeometry() {

    $args = func_get_args();
	if (count($args)==0) {
		$firstPart = "C";  // Circle is the default
	} else {
        $firstPart = strtoupper(substr((string)$args[0], 0, 1));
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $tick = $args[1];
    } else {
        $tick = "";
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $PI = verifyPI($args[2]);
    } else {
        $PI = " pi ";
    }

    $retval = array();

    if($firstPart=="C") {$type="Circle";}
    if($firstPart=="T") {$type="Triangle";}
    if($firstPart=="R") {$type="Rectangle";}
    if($firstPart=="S") {$type="Square";}
    if($firstPart=="A") {$type="SurfaceArea";}
    if($firstPart=="V") {$type="Volume";}

    if($type=="Circle") {
        $retval[0] = "{$tick}C = {$PI}d{$tick}";
        $retval[1] = "{$tick}C = 2{$PI}r{$tick}";
        $retval[2] = conversionUnits2ScreenReader2("","A",1,"$PI","r",2,"=",$tick);
    } elseif($type=="Triangle") {
        $retval[0] = "P = "._("add all sides");
        $retval[1] = "{$tick}A = 1/2bh{$tick}";
    } elseif($type=="Rectangle") {
        $retval[0] = "{$tick}P = 2W+2L{$tick}";
        $retval[1] = "{$tick}A = LW{$tick}";
    } elseif($type=="Square") {
        $retval[0] = "{$tick}P = 4s{$tick}";
        $retval[1] = "{$tick}A = s^2{$tick}";
    } elseif($type=="SurfaceArea") {
        $retval[0] = "{$tick}SA=2LW+2LH+2WH{$tick} "._("(Surface Area of a Rectangular Solid)");
        $retval[1] = conversionUnits2ScreenReader2("","SA",1,"6","s",2,"=",$tick)." "._("(Surface Area of a Cube)");
        $retval[2] = conversionUnits2ScreenReader2("","SA",1,"4{$PI}","r",2,"=",$tick)." "._("(Surface Area of a Sphere)");
        $retval[3] = conversionUnits2ScreenReader2("","SA",1,"2{$PI}rh+4{$PI}","r",2,"=",$tick)." "._("(Surface Area of a Right Circular Cylinder)");
    } elseif($type=="Volume") {
        $retval[0] = "{$tick}V = LWH{$tick} "._("(Volume of a Rectangular Solid)");
        $retval[1] = conversionUnits2ScreenReader2("","V",1,"","s",3,"=",$tick)." "._("(Volume of a Cube)");
        $retval[2] = conversionUnits2ScreenReader2("","V",1,"4/3{$PI}","r",3,"=",$tick)." "._("(Volume of a Sphere)");
        $retval[3] = conversionUnits2ScreenReader2("","V",1,"{$PI}h","r",2,"=",$tick)." "._("(Volume of a Right Circular Cylinder)");
    } else {
        $retval[0] = "'".(string)$type."' ".isnotvalid();
    }

    return $retval;
}

// function conversionFormulaTemperature(type[,tick])
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "C" = to Celsius (default)
//           "F" = to Fahrenheit
//           "K" = to Kelvin
//
// Examples
//
// use ConversionFormulaTemperature("F") returns the formula for F = 9/5C+32
function conversionFormulaTemperature() {

    $args = func_get_args();
	if (count($args)==0) {
		$FirstLetter = "F";  // Fahrenheit
	} else {
        $FirstLetter = strtoupper(substr($args[0], 0, 1));
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $tick = verifyTickMarks($args[1]);
    } else {
        $tick = "";
    }

	if($FirstLetter=="C") {$type="Celsius";}
    if($FirstLetter=="F") {$type="Fahrenheit";}
	if($FirstLetter=="K") {$type="Kelvin";}

    $retval = array();

	if ($type == "Kelvin") {
        $retval[0] = "{$tick}K=C+273.15{$tick}";
        $retval[1] = "{$tick}K=5/9(F-32)+273.15{$tick}";
    } elseif($type == "Fahrenheit") {
        $retval[0] =  "{$tick}F=9/5C+32{$tick}";
        $retval[1] =  "{$tick}F=9/5(K-273.15)+32{$tick}";
    } elseif($type == "Celsius") {
        $retval[0] =  "{$tick}C=(5/9)(F-32){$tick}";
        $retval[1] =  "{$tick}C=K-273.15{$tick}";
    } else {
        $retval[0] = "'".(string)$type."' ".isnotvalid();
    }

    return $retval;
}

// function conversionLength(type [,FullWords,Rounding,sign])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name
//
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     Sign: = gives you =
//           ~ gives you ~~
//          "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionLength("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionLength() {

    $args = func_get_args();
	if (count($args)==0) {
		echo _("Nothing to display - no system type supplied.")."<br/>\r\n";
		return "";
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system.isnotvalidAMT();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $rounding = verifyRounding($args[2]);
    } else {
        $rounding = 2;
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    if ( count($args)>3 && !is_null($args[3]) ) {
        $sign = verifyEqualSign($args[3],$tick);
    } else {
        $sign = verifyEqualSign("=",$tick);
    }

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 "._("ft")." = 12 "._("in");
            $retval[1] = "1 "._("yd")." = 3 "._("ft");
            $retval[2] = "1 "._("yd")." = 36 "._("in");
            $retval[3] = "1 "._("mi")." = 5,280 "._("ft");
        } else {
            $retval[0] = "1 "._("foot")." = 12 "._("inches");
            $retval[1] = "1 "._("yard")." = 3 "._("feet");
            $retval[2] = "1 "._("yard")." = 36 "._("inches");
            $retval[3] = "1 "._("mile")." = 5,280 "._("feet");
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 "._("km")." = 1000 "._("m");
            $retval[1] = "1 "._("hm")." = 100 "._("m");
            $retval[2] = conversionUnits2ScreenReader1("1 ",_("dam"),1,"n")." = 10 m";
            $retval[3] = "1 "._("m")." = 10 "._("dm");
            $retval[4] = "1 "._("m")." = 100 "._("cm");
            $retval[5] = "1 "._("m")." = 1000 "._("mm");
        } else {
            $retval[0] = "1 "._("kilometer")." = 1000 "._("meter");
            $retval[1] = "1 "._("hectometer")." = 100 "._("meter");
            $retval[2] = "1 "._("dekameter")."  = 10 "._("meter");
            $retval[3] = "1 "._("meter")." = 10 "._("decimeter");
            $retval[4] = "1 "._("meter")." = 100 "._("centimeter");
            $retval[5] = "1 "._("meter")." = 1000 "._("millimeter");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 "._("in")." $sign ".round(2.54, $rounding)." "._("cm");       // https://www.wolframalpha.com/input/?i=convert+1+inch+to+mm
            $retval[1] = "1 "._("ft")." $sign ".round(0.3048, $rounding)." "._("m");      // https://www.wolframalpha.com/input/?i=convert+1+foot+to+dm
            $retval[2] = "1 "._("yd")." $sign ".round(0.9144, $rounding)." "._("m");      // https://www.wolframalpha.com/input/?i=convert+1+yard+to+dm
            $retval[3] = "1 "._("mi")." $sign ".round(1.60934400, $rounding)." "._("km"); // 1.60934400 km https://www.wolframalpha.com/input/?i=convert+1+mile+to+m
        } else {
			$retval[0] = "1 "._("inch")." $sign ".round(2.54, $rounding)." "._("centimeter");
            $retval[1] = "1 "._("foot")." $sign ".round(0.3048, $rounding)." "._("meter");
            $retval[2] = "1 "._("yard")." $sign ".round(0.9144, $rounding)." "._("meter");
            $retval[3] = "1 "._("mile")." $sign ".round(1.60934400, $rounding)." "._("kilometer");
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 "._("cm")." $sign ".round(0.393700787, $rounding)." "._("in");    // 393.700787 mils https://www.wolframalpha.com/input/?i=convert+1+centimeter+to+inch
            $retval[1] = "1 "._("m")." $sign ".round(3.28083990, $rounding)." "._("ft"); // 3.28083990 feet https://www.wolframalpha.com/input/?i=convert+1+meter+to+inch
            $retval[2] = "1 "._("m")." $sign ".round(1.0936133, $rounding)." "._("yd");  // 3.28083990 feet divided by 3
            $retval[3] = "1 "._("km")." $sign ".round(0.621371, $rounding)." "._("mi");   // 621371 miles https://www.wolframalpha.com/input/?i=convert+1000000+kilometer+to+miles
        } else {
			$retval[0] = "1 "._("centimeter")." $sign ".round(0.393700787, $rounding)." "._("inch");
            $retval[1] = "1 "._("meter")." $sign ".round(3.28083990, $rounding)." "._("feet");
            $retval[2] = "1 "._("meter")." $sign ".round(1.0936133, $rounding)." "._("yard");
            $retval[3] = "1 "._("kilometer")." $sign ".round(0.621371, $rounding)." "._("mile");
        }
	} else {
        $retval[0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// function conversionLiquid(type[,FullWords,Rounding,tick])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "C" - Casks  (rounding is ignored)
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name
//
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     tick: add a tick mark around items with exponents
//
// Examples
//
// use conversionLiquid("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionLiquid() {

    $args = func_get_args();
	if (count($args)==0) {
		echo _("Nothing to display - no system type supplied.")."<br/>\r\n";
		return "";
	} else {
        $system = strtoupper($args[0]);
        if($system!='C' ) {
            echo "'".(string)$system."' ".isnotvalidC();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    $retval = array();

	if($system=="C"){
        if($fullname==0) {
            $retval[0] = "1 "._("US Barrel")." = 42 "._("gal");
            $retval[1] = "1 "._("British Barrel")." = 43 "._("gal");
            $retval[2] = "1 "._("Hogshead")." = 63 "._("gal");
            $retval[3] = "1 "._("Barrique")." = 63 "._("gal");
            $retval[4] = "1 "._("Puncheon")." = 79 "._("gal");
            $retval[5] = "1 "._("Butt")." = 126 "._("gal");
            $retval[6] = "1 "._("Pipe")." = 145 "._("gal");
            $retval[7] = "1 "._("Tun")." = 252 "._("gal");
        } else {
            $retval[0] = "1 "._("US Barrel")." = 42 "._("gallons");
            $retval[1] = "1 "._("British Barrel")." = 43 "._("gallons");
            $retval[2] = "1 "._("Hogshead")." = 63 "._("gallons");
            $retval[3] = "1 "._("Barrique")." = 63 "._("gallons");
            $retval[4] = "1 "._("Puncheon")." = 79 "._("gallons");
            $retval[5] = "1 "._("Butt")." = 126 "._("gallons");
            $retval[6] = "1 "._("Pipe")." = 145 "._("gallons");
            $retval[7] = "1 "._("Tun")." = 252 "._("gallons");
        }
	} else {
        $retval[0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// function conversionPrefix(type [,Abbreviations])
// Returns the Abbreviations to words
//
// INPUTS:
//           type: "M" - Meter
//                 "L" - Liter
//                 "G" - Gram
//
//   Abbreviations: 0 = no abb
//                  1 = show abb
// Examples
//
// use conversionPrefix("G") returns the prefix with a base of grams
function conversionPrefix() {

    $args = func_get_args();
	if (count($args)==0) {
		$type = "M";
	} else {
        $type = strtoupper($args[0]);
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $ShowAbb = verifyFullName($args[1]);
    } else {
        $ShowAbb = 0;
    }

    $retval = array();
	if($ShowAbb == 0) {
        $retval[0] = _("Kilo");
        $retval[1] = _("Hecto");
        $retval[2] = _("Deka");
        if($type == "G") {
            $retval[3] = _("Gram") ;
        } elseif($type == "L") {
            $retval[3] =  _("Liter");
        } else {
            $retval[3] = _("Meter");
        }

        $retval[4] = _("Deci");
        $retval[5] = _("Centi");
        $retval[6] = _("Milli");
    } else {
        $retval[0] = _("Kilo (k)");
        $retval[1] = _("Hecto (h)");
        $retval[2] = _("Deka")." ".conversionUnits2ScreenReader1("","(da)",1,"","");
        if($type == "G") {
            $retval[3] = _("Gram (g)");
        } elseif($type == "L") {
            $retval[3] = _("Liter (L)");
        } else {
            $retval[3] = _("Meter (m)");
        }

        $retval[4] = _("Deci (d)");
        $retval[5] = _("Centi (c)");
        $retval[6] = _("Milli (m)");
    }

	return $retval;
}

// conversionTime(Fullname)
// conversionTime() use Abbreviations
// conversionTime("y") use full name
function conversionTime2() {
	$args = func_get_args();
    // store translation in variables so that you avoid spelling errors
    //
    $minabbr = _("min");
    $secabbr = _("sec");
    $hrabbr = _("hr");
    $dayabbr = _("d");
    $yearabbr = _("yr");
    $decabbr = _("dec");
    $centuryabbr = _("c");

    $seconds = _("seconds");
    $minute = _("minute");
    $minutes = _("minutes");
    $hour = _("hour");
    $hours = _("hours");
    $day = _("day");
    $days = _("days");
    $year = _("year");
    $years = _("years");
    $decade = _("decade");
    $century = _("century");

    if (count($args)==0) {
        $retval[0] = array("",1,$minabbr, 60, $secabbr);
		$retval[1] = array("",1,$hrabbr, 60, $minabbr);
		$retval[2] = array("",1,$dayabbr, 24, $hrabbr);
		$retval[3] = array("",1,$yearabbr, 365,$dayabbr);
		$retval[4] = array("",1,$decabbr, 10, $yearabbr);
		$retval[5] = array("",1,$centuryabbr, 100, $yearabbr);
    } else {
        $retval[0] = array("",1,$minute, 60, $seconds);
		$retval[1] = array("",1,$hour, 60, $minutes);
		$retval[2] = array("",1,$day, 24, $hours);
		$retval[3] = array("",1,$year, 365, $days);
		$retval[4] = array("",1,$decade, 10, $years);
		$retval[5] = array("",1,$century, 100, $years);
    }

    for($i=0;$i<6;$i+=1){
        //$retval[$i][0] = sprintf("%d %s = %d %s",$retval[$i][1], $retval[$i][2], $retval[$i][3], $retval[$i][4]);
        $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = {$retval[$i][3]} {$retval[$i][4]}";
    }

	return $retval;
}

function conversionTime() {
	$args = func_get_args();
    if (count($args)==0) {
        return conversion_extract_column_array(conversionTime2(),0);
    } else {
        return conversion_extract_column_array(conversionTime2($args),0);
    }
}

//
// INTERNAL ONLY
//
//function conversionUnit2ScreenReaderModification($units){
//  Returns a 2 element array where the 0 element is the word that can have tick marksof the tick mark form of the word and the screen read form of the work
//
function conversionUnit2ScreenReaderModification($units,$tick){
    $testunit = strtolower($units);
    $retval = array();
    if($units=="in") {
        if($tick=="`"||$tick=="y") {
            $retval[0] = "i n";
        } else {
            $retval[0] = "in";
        }
        $retval[1] = "in";
    } elseif($testunit=="dam") {
        $retval[0] = "dam";
        $retval[1] = "d a m";
    } elseif($testunit=="dal") {
        $retval[0] = "daL";
        $retval[1] = "d a L";
    } elseif($testunit=="dag") {
        $retval[0] = "dag";
        $retval[1] = "d a g";
    } elseif($testunit=="(da)") {
        $retval[0] = "(da)";
        $retval[1] = "d a";
    } elseif($testunit=="l") {
        $retval[0] = "L";
        $retval[1] = "L";
    } else {
        $retval[0] = $units;
        $retval[1] = $units;
    }

    return $retval;
}

//function conversionUnits2ScreenReader1(number,units,dimensions=2,tick="y",sign="")
// Returns the Abbreviations to words
//
// INPUTS:  number: the amount of units
//           units: ft - feet
//                  in - inch
//                  m = meter
//                  etc.
//      dimensions: 1   - does not show an exponent
//                  2,3 - add ^dimensions1 to units1
//             tick: y - add tick marks
//                   n - do not add tick marks//
//             sign: add this to the end (default of '')
//
function conversionUnits2ScreenReader1($number,$units,$dimensions=2,$tick="y",$sign=""){

    $tick = verifyTickMarks($tick);
    $exponentWord = _("exponent");
    $retval = conversionUnit2ScreenReaderModification($units,$tick);

    $unitTick = _($retval[0]);
    $unitSR = $retval[1];

    if($dimensions==1) {
        return "<span aria-hidden=true>$tick$number$unitTick$sign$tick</span><span class=\"sr-only\">$number $unitSR $sign</span>";
    } else {
        return "<span aria-hidden=true>$tick$number$unitTick^$dimensions$sign$tick</span><span class=\"sr-only\">$number $unitSR $exponentWord $dimensions $sign</span>";
    }

}

//function conversionUnits2ScreenReader2(number1,units1,dimensions1,number2,units2,dimensions2,sign="=",tick="y")
// Returns the Abbreviations to words
//
// INPUTS:  number1: the amount of units1
//           units1: ft - feet
//                   in - inch
//                   m = meter
//                   etc.
//      dimensions1: 1   - does not show an exponent
//                   2,3 - add ^dimensions1 to units1
//
//          number2: the amount of units2
//           units2: ft - feet
//                   in - inch
//                   m = meter
//                   etc.
//
//       dimensions2: 1   - does not show an exponent
//                    2,3 - add ^dimensions2 to units2
//             sign: add this between the two factors (default of '=')
//             tick: y - add tick marks
//                   n - do not add tick marks
//
//
function conversionUnits2ScreenReader2($number1,$units1,$dimensions1,$number2,$units2,$dimensions2,$sign="=",$tick="y"){

    $tick = verifyTickMarks($tick);
    $exponentWord = _("exponent");
    $retval1 = conversionUnit2ScreenReaderModification($units1,$tick);
    $retval2 = conversionUnit2ScreenReaderModification($units2,$tick);

    $unitTick1 = _($retval1[0]);
    $unitSR1 = $retval1[1];

    $unitTick2 = _($retval2[0]);
    $unitSR2 = $retval2[1];

    if($dimensions1==1) {
        if($dimensions2==1) {
            return "<span aria-hidden=true>$tick$number1$unitTick1$sign$number2$unitTick2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1 $sign $number2 $unitSR2 </span>";
        } else {
            return "<span aria-hidden=true>$tick$number1$unitTick1$sign$number2$unitTick2^$dimensions2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1 $sign $number2 $unitSR2 $exponentWord $dimensions2</span>";
        }
    } else {
        if($dimensions2==1) {
            return "<span aria-hidden=true>$tick$number1$unitTick1^$dimensions1$sign$number2$unitTick2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1  $exponentWord $dimensions1 $sign $number2 $unitSR2</span>";
        }
        else {
            return "<span aria-hidden=true>$tick$number1$unitTick1^$dimensions1$sign$number2$unitTick2^$dimensions2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1  $exponentWord $dimensions1 $sign $number2 $unitSR2 $exponentWord $dimensions2</span>";
        }
    }

}

// function conversionVolume(type [,FullWords,Rounding,tick,Sign])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name
//
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//     Sign: = gives you =
//           ~ gives you ~~
//          "" gives you html approximately equal symbol
//
// Examples
//
// use conversionVolume("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionVolume() {

	$args = func_get_args();
	if (count($args)==0) {
		echo _("Nothing to display - no system type supplied.")."<br/>\r\n";
		return "";
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system.isnotvalidAMT();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $rounding = verifyRounding($args[2]);
    } else {
        $rounding = 2;
    }

    if ( count($args)>3 && !is_null($args[3]) ) {
        $tick = verifyTickMarks($args[3]);
    } else {
        $tick = "";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $sign_no = verifyEqualSign($args[4],"n");
        $sign = verifyEqualSign($args[4],$tick);
    } else {
        $sign_no = verifyEqualSign("=","n");
        $sign = verifyEqualSign("=",$tick);
    }

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ",_("ft"),3,"1,728 ",_("in"),3,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ",_("yd"),3,"27 ",_("ft"),3,"=",$tick);
        } elseif($fullname==1) {
            $retval[0] = "1 "._("feet cubed")." = 1,728 "._("inches cubed");
            $retval[1] = "1 "._("yard cubed")." = 27 "._("feet cubed");
        } elseif($fullname==2) {
            $retval[0] = "1 "._("cubic feet")." = 1,728 "._("cubic inches");
            $retval[1] = "1 "._("cubic yard")." = 27 "._("cubic feet");
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ",_("km"),3,"1000 ",_("hm"),3,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ",_("hm"),3,"1000 ",_("dam"),3,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ",_("dam"),3,"1000 ",_("m"),3,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ",_("m"),3,"1000 ",_("dm"),3,"=",$tick);
            $retval[4] = conversionUnits2ScreenReader2("1 ",_("dm"),3,"1000 ",_("cm"),3,"=",$tick);
			$retval[5] = conversionUnits2ScreenReader2("1 ",_("cm"),3,"1000 ",_("mm"),3,"=",$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 "._("Kilometer cubed")." = 1000 "._("Hectometer cubed");
            $retval[1] = "1 "._("Hectometer cubed")." = 1000 "._("Dekameter cubed");
            $retval[2] = "1 "._("Dekameter cubed")." = 1000 "._("Meter cubed");
            $retval[3] = "1 "._("Meter cubed")." = 1000 "._("Decimeter cubed");
            $retval[4] = "1 "._("Decimeter cubed")." = 1000 "._("Centimeter cubed");
			$retval[5] = "1 "._("Centimeter cubed")." = 1000 "._("Millimeter cubed");
        } else  {
			$retval[0] = "1 "._("Cubic kilometer")." = 1000 "._("Cubic hectometer");
            $retval[1] = "1 "._("Cubic hectometer")." = 1000 "._("Cubic dekameter");
            $retval[2] = "1 "._("Cubic dekameter")." = 1000 "._("Cubic meter");
            $retval[3] = "1 "._("Cubic meter")." = 1000 "._("Cubic decimeter");
            $retval[4] = "1 "._("Cubic decimeter")."  = 1000 "._("Cubic centimeter");
			$retval[5] = "1 "._("Cubic centimeter")." = 1000 "._("Cubic millimeter");
        }
	} elseif($system=="AM"){
        // 0.0163870640 L https://www.wolframalpha.com/input/?i=convert+1+cubic+inch+to+ml
        $CF = round(16.3870640, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","in",3,"$CF ","mL",1,$sign_no,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 "._("Inch cubed")." $sign $CF "._("Milliliter");
        } else {
			$retval[0] = "1 "._("Cubic inch")." $sign $CF "._("Milliliter");
        }
	} elseif($system=="MA"){
        // 61.0237441 in^3  https://www.wolframalpha.com/input/?i=convert+1+liter+to+cubic+feet
        $CF = round(61.0237441, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ",_("L"),1,"$CF ",_("in"),3,$sign_no,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 "._("Liter")." $sign $CF "._("Inches cubed");
        } else {
			$retval[0] = "1 "._("Liter")." $sign $CF "._("Cubic inches");
        }
	} else {
        $retval[0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// function conversionWeight(type [,FullWords,Rounding,Sign])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
//      NOTE: This ignores the fact that the metric system uses mass and the Americam system uses a force for weight.
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name
//
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     Sign: = gives you =
//           ~ gives you ~~
//          "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionWeight("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionWeight() {

	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system.isnotvalidAMT();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $rounding = verifyRounding($args[2]);
    } else {
        $rounding = 2;
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    if ( count($args)>3 && !is_null($args[3]) ) {
        $sign = verifyEqualSign($args[3],$tick);
    } else {
        $sign = verifyEqualSign("=",$tick);
    }

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 "._("lb")." = 16 "._("oz");
            $retval[1] = "1 "._("T")." =2000 "._("lbs");
        } else {
            $retval[0] = "1 "._("pound")." = 16 "._("ounces");
            $retval[1] = "1 "._("Ton")." = 2000 "._("pounds");
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 "._("kg")." = 1000 "._("g");
            $retval[1] = "1 "._("hg")." = 100 "._("g");
            $retval[2] = conversionUnits2ScreenReader1("1 ",_("dag"),1,"n")." = 10 "._("g");
            $retval[3] = "1 "._("g")." = 10 "._("dg");
            $retval[4] = "1 "._("g")." = 100 "._("cg");
			$retval[5] = "1 "._("g")." = 1000 "._("mg");
			$retval[6] = "1 "._("Tonne")." = 1000 "._("kg");
        } else {
            $retval[0] = "1 "._("kilogram")." = 1000 "._("gram");
            $retval[1] = "1 "._("hectogram")." = 100 "._("gram");
            $retval[2] = "1 "._("dekagram")." = 10 "._("gram");
            $retval[3] = "1 "._("gram")." = 10 "._("decigram");
            $retval[4] = "1 "._("gram")." = 100 "._("centigram");
            $retval[5] = "1 "._("gram")." = 1000 "._("milligram");
			$retval[6] = "1 "._("Metric Ton")." = 1000 "._("kilogram");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 "._("oz")." $sign ".round(28.3495231, $rounding)." "._("g");    // 0.0283495231 kg https://www.wolframalpha.com/input/?i=convert+1+ounce+to+gram
            $retval[1] = "1 "._("lbs")." $sign ".round(0.453592370, $rounding)." "._("kg"); // 0.453592370 kg https://www.wolframalpha.com/input/?i=convert+1+pound+to+gram
        } else {
			$retval[0] = "1 "._("ounces")." $sign ".round(28.3495231, $rounding)." "._("gram");
            $retval[1] = "1 "._("pound")." $sign ".round(0.453592370, $rounding)." "._("kilogram");
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 "._("g")." $sign ".round(0.035274, $rounding)." "._("oz");
            $retval[1] = "1 "._("kg")." $sign ".round(2.20462, $rounding)." "._("lbs");
        } else {
			$retval[0] = "1 "._("gram")." $sign ".round(0.035274, $rounding)." "._("ounces");
            $retval[1] = "1 "._("kilogram")." $sign ".round(2.20462, $rounding)." "._("pound");
        }
	} else {
        $retval[0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// 2022-05-16 ver 22 - reworking conversion to add _() to all words in file so gettext can be run for a translation file
// 2022-05-09 ver 21 - Converted to language detection with gettext _('') as a fallback.
// 2022-05-04 ver 20 - Changed all spelling _('') to functions for easier maintance.
// 2021-09-24 ver 19 - fixed tick mark typo and added to functions
// 2021-03-08 ver 18 - fixed typo in surface area of a right circular cylinder
// 2021-02-26 ver 17 - fixed conversionLength, conversionCapacity, and conversionWeight missing spaces
// 2021-02-26 ver 16 - added rectangle and square to conversionFormulaGeometry and conversionFormulaAbbreviations, typo in conversionVolume
// 2021-02-23 ver 15 - updated american length language, update pi symbol to pi, update verify equal sign to =, ~~, or HTML entity, verifypi added
// 2021-02-20 ver 14 - updated conversionFormulaAbbreviations, conversionFormulaGeometry, length conversion
// 2021-02-19 ver 13 - updated american weight conversion
// 2021-02-19 ver 12 - updated conversionUnits2ScreenReader to conversionUnits2ScreenReader1 and conversionUnits2ScreenReader2
// 2021-02-16 ver 11 - updated conversionFormulaTemperature and reordered the file for alphabetical order
// 2021-02-16 ver 10 - updated conversionUnits2ScreenReader to take 4 arguments
// 2021-02-04 ver 9  - added conversionLiquid, find typo in conversionArea
// 2021-02-04 ver 8  - Added cellpadding to conversionDisplay2HTML, updated conversion factors from www.wolframalpha.com
//                     fixed missing screen reader on missed ^ conversion factors.
// 2021-02-02 ver 7  - working on the screen reader added
//                       <span aria-hidden=true></span><span class=\"sr-only\"></span>
// 2021-02-02 ver 6  - forgot to include conversionFormulaTempature in the allowed Macros and some typo fixed.
// 2021-02-02 ver 5  - update spellings and function naming conventions
// 2021-02-02 ver 4  - updated ConversionDisplay to allow for multiple sets of inputs
//                     created ConversionDisplay2HTML
// 2021-01-31 ver 3  - added Cubic inches/Inches cubed option
// 2021-01-31 ver 2  - added ConversionDisplay
// 2021-01-31 ver 1  - initial release

?>