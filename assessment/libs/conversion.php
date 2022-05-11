<?php
// Conversion module - this contains constants for use with Rate and Ratio conversion questions
// Mike Jenck, Originally developed Jan 29-31, 2021
// licensed under GPL version 2 or later
//

// NOTE: _('word') is a call to gettext() for localization
//

function conversion_detectlanguage(){

    $supportedLangs = array('en-gb', 'en-ca', 'en-us', 'en');
    $langstr = preg_replace('/;q=[\d\.]*/','',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $languages = explode(',', strtolower($langstr));

    foreach($languages as $lang)
    {
        if(in_array($lang, $supportedLangs))
        {
            // return the first language
            return $lang;
        }
    }

    return 'en-us';
}

$conversion_browser_lang = conversion_detectlanguage();

function exponent($case) {
    if($case=="E") {
        return _('Exponent');
    } else {
        return _('exponent');
    }
}

function deca($case) {
    global $conversion_browser_lang;
    if ($conversion_browser_lang == 'en-gb' || $conversion_browser_lang == 'en-ca') {
        if($case=="D") {
            return 'Deca';
        } else {
            return 'deca';
        }
    } else {
        if($case=="D") {
            return _('Deka');
        } else {
            return _('deka');
        }
    }
}

function meter($case) {
    global $conversion_browser_lang;
    if ($conversion_browser_lang == 'en-gb' || $conversion_browser_lang == 'en-ca') {
        if($case=="M") {
            return 'Metre';
        } else {
            return 'metre';
        }
    } else {
        if($case=="M") {
            return _('Meter');
        } else {
            return _('meter');
        }
    }
}

function liter($case) {
    global $conversion_browser_lang;
    if ($conversion_browser_lang == 'en-gb' || $conversion_browser_lang == 'en-ca') {
        if($case=="L") {
            return 'Litre';
        } else {
            return 'litre';
        }
    } else {
        if($case=="L") {
            return _('Liter');
        } else {
            return _('liter');
        }
    }
}

function gram($case) {
    global $conversion_browser_lang;
    if ($conversion_browser_lang == 'en-gb') {
        if($case=="G") {
            return _('Gramme');
        } else {
            return _('gramme');
        }
    } else {
        if($case=="G") {
            return _('Gram');
        } else {
            return _('gram');
        }
    }
}


function conversionVer() {
	// File version
	return 21;
}

global $allowedmacros;

// COMMENT OUT BEFORE UPLOADING
if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "conversionVer", "conversionAbbreviations",  "conversionArea",
    "conversionCapacity", "conversionDisplay", "conversionDisplay2HTML", "conversionDisplay2HTMLwithBorder",
     "conversionFormulaAbbreviations", "conversionFormulaGeometry", "conversionFormulaTemperature",
     "conversionLength", "conversionLiquid", "conversionPrefix", "conversionTime",
    "conversionUnits2ScreenReader1", "conversionUnits2ScreenReader2", "conversionVolume", "conversionWeight" );

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
	}

	$system = $args[0];
	$temp = verifyString($args[1]);
	if(strlen($temp)==0 ) {
		$FirstLetter = "L";
    } else {
		$FirstLetter = substr($temp, 0, 1);
    }

	$tick = verifyTickMarks($args[2]);
	$fullname = verifyFullName($args[3]);

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
			$retval[0] = "Inches = in";
			$retval[1] = "Feet = ft";
			$retval[2] = "Yards = yd";
			$retval[3] = "Miles = mi";
        } elseif($type=="Capacity"){
			$retval[0] = "Fluid ounces = fl oz";
			$retval[1] = "Cups = c";
			$retval[2] = "Pints = pt";
			$retval[3] = "Quarts = qt";
			$retval[4] = "Gallons = gal";
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = "Ounces = oz";
			$retval[1] = "Pounds = lbs";
			$retval[2] = "Tons = T";
        } elseif($type=="Area"){
			if($fullname==0) {
                $retval[0] = "Inches squared = ".conversionUnits2ScreenReader1("","in",2,$tick);
                $retval[1] = "Feet squared = ".conversionUnits2ScreenReader1("","ft",2,$tick);
                $retval[2] = "Yard squared = ".conversionUnits2ScreenReader1("","yd",2,$tick);
                $retval[3] = "Mile squared = ".conversionUnits2ScreenReader1("","mi",2,$tick);
            } else {
                $retval[0] = "Square inches = ".conversionUnits2ScreenReader1("","in",2,$tick);
                $retval[1] = "Square feet = ".conversionUnits2ScreenReader1("","ft",2,$tick);
                $retval[2] = "Square yard = ".conversionUnits2ScreenReader1("","yd",2,$tick);
                $retval[3] = "Square mile = ".conversionUnits2ScreenReader1("","mi",2,$tick);
            }

        } elseif($type=="Volume"){
			if($fullname==0) {
                $retval[0] = "Inches cubed = ".conversionUnits2ScreenReader1("","in",3,$tick);
                $retval[1] = "Feet cubed = ".conversionUnits2ScreenReader1("","ft",3,$tick);
                $retval[2] = "Yard cubed = ".conversionUnits2ScreenReader1("","yd",3,$tick);
            } else {
                $retval[0] = "Cubic inches = ".conversionUnits2ScreenReader1("","in",3,$tick);
                $retval[1] = "Cubic feet = ".conversionUnits2ScreenReader1("","ft",3,$tick);
                $retval[2] = "Cubic yard = ".conversionUnits2ScreenReader1("","yd",3,$tick);
            }
        }

	}

	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="M"){
		if($type=="Length"){
			$retval[0] = "Milli".meter("m")." = mm";
			$retval[1] = "Centi".meter("m")." = cm";
			$retval[2] = "Deci".meter("m")." = dm";
			$retval[3] = meter("M")." = m";
			$retval[4] = deca("D").meter("m")." = ".conversionUnits2ScreenReader1("","dam",1,"n");
			$retval[5] = "Hecto".meter("m")." = hm";
			$retval[6] = "Kilo".meter("m")." = km";
        } elseif($type=="Capacity"){
			$retval[0] = "Milli".liter("l")." = mL";
			$retval[1] = "Centi".liter("l")." = cL";
			$retval[2] = "Deci".liter("l")." = dL";
			$retval[3] = liter("L")." = L";
			$retval[4] = deca("D").liter("l")." = ".conversionUnits2ScreenReader1("","daL",1,"n");
			$retval[5] = "Hecto".liter("l")." = hL";
			$retval[6] = "Kilo".liter("l")." = kL";
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = "Milli".gram("g")." = mg";
			$retval[1] = "Centi".gram("g")." = cg";
			$retval[2] = "Deci".gram("g")." = dg";
			$retval[3] = gram("G")." = g";
			$retval[4] = deca("D").gram("g")." = ".conversionUnits2ScreenReader1("","dag",1,"n");
			$retval[5] = "Hecto".gram("g")." = hg";
			$retval[6] = "Kilo".gram("g")." = kg";
			$retval[7] = "Metric Ton = Tonne";
        } elseif($type=="Area"){
			if($fullname==0) {
                $retval[0] = "Milli".meter("m")." squared = ".conversionUnits2ScreenReader1("","mm",2,$tick);
                $retval[1] = "Centi".meter("m")." squared = ".conversionUnits2ScreenReader1("","cm",2,$tick);
                $retval[2] = "Deci".meter("m")." squared = ".conversionUnits2ScreenReader1("","dm",2,$tick);
                $retval[3] = meter("M")." squared = ".conversionUnits2ScreenReader1("","m",2,$tick);
                $retval[4] = deca("D").meter("m")." squared = ".conversionUnits2ScreenReader1("","dam",2,$tick);
                $retval[5] = "Hecto".meter("m")." squared = ".conversionUnits2ScreenReader1("","hm",2,$tick);
                $retval[6] = "Kilo".meter("m")." squared = ".conversionUnits2ScreenReader1("","km",2,$tick);
            } else {
                $retval[0] = "Square milli".meter("m")." = ".conversionUnits2ScreenReader1("","mm",2,$tick);
                $retval[1] = "Square centi".meter("m")." = ".conversionUnits2ScreenReader1("","cm",2,$tick);
                $retval[2] = "Square deci".meter("m")." = ".conversionUnits2ScreenReader1("","dm",2,$tick);
                $retval[3] = "Square ".meter("m")." = ".conversionUnits2ScreenReader1("","m",2,$tick);
                $retval[4] = "Square ".deca("d").meter("m")." = ".conversionUnits2ScreenReader1("","dam",2,$tick);
                $retval[5] = "Square hecto".meter("m")." = ".conversionUnits2ScreenReader1("","hm",2,$tick);
                $retval[6] = "Square kilo".meter("m")." = <".conversionUnits2ScreenReader1("","km",2,$tick);
            }
			$retval[7] = "Ares = a";
			$retval[8] = "Hectares = ha";
        } elseif($type=="Volume") {
			if($fullname==0) {
                $retval[0] = "Milli".meter("m")." cubed = ".conversionUnits2ScreenReader1("","mm",3,$tick);
                $retval[1] = "Centi".meter("m")." cubed = ".conversionUnits2ScreenReader1("","cm",3,$tick);
                $retval[2] = "Deci".meter("m")." cubed = ".conversionUnits2ScreenReader1("","dm",3,$tick);
                $retval[3] = meter("M")." cubed = ".conversionUnits2ScreenReader1("","m",3,$tick);
                $retval[4] = deca("D").meter("m")." cubed = ".conversionUnits2ScreenReader1("","dam",3,$tick);
                $retval[5] = "Hecto".meter("m")." cubed = ".conversionUnits2ScreenReader1("","hm",3,$tick);
                $retval[6] = "Kilo".meter("m")." cubed = ".conversionUnits2ScreenReader1("","km",3,$tick);
            } else {
                $retval[0] = "Cubic milli".meter("m")." = ".conversionUnits2ScreenReader1("","mm",3,$tick);
                $retval[1] = "Cubic centi".meter("m")." = ".conversionUnits2ScreenReader1("","cm",3,$tick);
                $retval[2] = "Cubic deci".meter("m")." = ".conversionUnits2ScreenReader1("","dm",3,$tick);
                $retval[3] = "Cubic ".meter("m")." = ".conversionUnits2ScreenReader1("","m",3,$tick);
                $retval[4] = "Cubic ".deca("d").meter("m")." = ".conversionUnits2ScreenReader1("","dam",3,$tick);
                $retval[5] = "Cubic hecto".meter("m")." = ".conversionUnits2ScreenReader1("","hm",3,$tick);
                $retval[6] = "Cubic kilo".meter("m")." = ".conversionUnits2ScreenReader1("","km",3,$tick);
            }
        }
	}

    // -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="T"){
        $retval[0] = "Seconds = sec";
		$retval[1] = "Minutes = min";
		$retval[2] = "Hours = hr";
		$retval[3] = "Days = d";
		$retval[4] = "Years = yr.";
		$retval[5] = "Centuries = c";
    }


	return $retval;
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
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	}

	$system = $args[0];
	$fullname = verifyFullName($args[1]);
	$rounding = verifyRounding($args[2]);
	$tick = $args[3];
    $sign = verifyEqualSign($args[4],$tick);

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ","ft",2,"144 ","in",2,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ","yd",2,"9 ","ft",2,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ","\"acre\"",1,"43,560 ","ft",2,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ","mi",2,"640 ","\"acre\"",1,"=",$tick);
        } else {
            $retval[0] = "1 feet squared = 144 inches squared";
            $retval[1] = "1 yard squared = 9 feet squared";
            $retval[3] = "1 mile squared  = 640 acre";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ","km",2,"100 ","hm",2,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ","hm",2,"100 ","dam",2,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ","dam",2,"100 ","m",2,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ","m",2,"100 ","dm",2,"=",$tick);
            $retval[4] = conversionUnits2ScreenReader2("1 ","dm",2,"100 ","cm",2,"=",$tick);
			$retval[5] = conversionUnits2ScreenReader2("1 ","cm",2,"100 ","mm",2,"=",$tick);
			$retval[6] = conversionUnits2ScreenReader2("1 ","\"a\"",1,"100 ","m",2,"=",$tick);
			$retval[7] = conversionUnits2ScreenReader2("1 ","\"ha\"",1,"100 ","\"a\"",1,"=",$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Kilo".meter("m")." squared = 100 Hecto".meter("m")." squared";
            $retval[1] = "1 Hecto".meter("m")."  squared = 100 ".deca("D").meter("m")." squared";
            $retval[2] = "1 ".deca("D").meter("m")." squared = 100 ".meter("M")." squared";
            $retval[3] = "1 ".meter("M")." squared = 100 Deci".meter("m")." squared";
            $retval[4] = "1 Deci".meter("m")." squared = 100 Centi".meter("m")." squared";
			$retval[5] = "1 Centi".meter("m")." squared = 100 Milli".meter("m")." squared";
			$retval[6] = "1 Ares = 100 ".meter("m")." squared";
			$retval[7] = "1 Hectares = 100 Ares";
        } else  {
			$retval[0] = "1 Square kilo".meter("m")." = 100 Square hecto".meter("m");
            $retval[1] = "1 Square hecto".meter("m")." = 100 Square ".deca("d").meter("m");
            $retval[2] = "1 Square ".deca("d").meter("m")." = 100 Square ".meter("m");
            $retval[3] = "1 Square ".meter("m")." = 100 Square deci".meter("m");
            $retval[4] = "1 Square deci".meter("m")." = 100 Square centi".meter("m");
			$retval[5] = "1 Square centi".meter("m")." = 100 Square milli".meter("m");
			$retval[6] = "1 Ares = 100 Square ".meter("m")." ";
			$retval[7] = "1 Hectares = 100 Ares";
        }
	} elseif($system=="AM"){
        //6.45160000 cm^2 https://www.wolframalpha.com/input/?i=convert+1+square+inch+to+mm+squared
        $CF = round(6.4516, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","in",2,"$CF ","cm",2,$sign,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Inch squared $sign $CF Centi".meter("m")." squared";
        } else {
			$retval[0] = "1 Square inch $sign $CF Square centi".meter("m");
        }
	} elseif($system=="MA"){
        // 1.19599005 yd^2 https://www.wolframalpha.com/input/?i=convert+1+square+meter+to+square+feet
        // https://www.wolframalpha.com/input/?i=convert+1+hectares+to+square+feet
        $CF0 = round(1.19599005, $rounding);
        $CF1 = round(2.471, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","m",2,"$CF0 ","yd",2,$sign,$tick);
            $retval[1] = "1 ha $sign $CF1 acres";
        } elseif($fullname==1) {
			$retval[0] = "1 ".meter("M")." squared $sign $CF0 Yard squared";
            $retval[1] = "1 hectares $sign $CF1 acres";
        } else {
			$retval[0] = "1 Square".meter("M")." $sign $CF0 Square yard";
            $retval[1] = "1 hectares $sign $CF1 acres";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
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
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	}

	$system = $args[0];
	$fullname = verifyFullName($args[1]);
	$rounding = verifyRounding($args[2]);
    $tick = verifyTickMarks($args[4]);
    $sign = verifyEqualSign($args[3],$tick);

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 c = 8 fl oz";
            $retval[1] = "1 pt = 2 c";
            $retval[2] = "1 qt = 2 pt";
            $retval[3] = "1 gal = 4 qt";
        } else {
            $retval[0] = "1 Cup = 8 fluid ounces";
            $retval[1] = "1 pint = 2 Cups";
            $retval[2] = "1 quart = 2 pint";
            $retval[3] = "1 gallon = 4 quart";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 kL = 1000 L";
            $retval[1] = "1 hL = 100 L";
            $retval[2] = conversionUnits2ScreenReader1("1 ","daL",1,"n")." = 10 L";
            $retval[3] = "1 L = 10 dL";
            $retval[4] = "1 L = 100 cL";
			$retval[5] = "1 L = 1000 mL";
        } else {
            $retval[0] = "1 kilo".liter("l")." = 1000 ".liter("L");
            $retval[1] = "1 hecto".liter("l")." = 100 ".liter("L");
            $retval[2] = "1 ".deca("d").liter("l")." = 10 ".liter("L");
            $retval[3] = "1 ".liter("L")." = 10 deci".liter("l");
            $retval[4] = "1 ".liter("L")." = 100 centi".liter("l");
            $retval[5] = "1 ".liter("L")." = 1000 milli".liter("l");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 fl oz $sign ".round(0.0295735296, $rounding)." L";
            $retval[1] = "1 C $sign ".round(0.236588236, $rounding)." L";
            $retval[2] = "1 pt $sign ".round(0.473176473, $rounding)." L";
            $retval[3] = "1 qt $sign ".round(0.946352946, $rounding)." L";
			$retval[4] = "1 gal $sign ".round(3.78541178, $rounding)." L";
        } else {
			$retval[0] = "1 fluid ounces $sign ".round(0.0295735296, $rounding)." ".liter("L");  // 29.5735296 mL  https://www.wolframalpha.com/input/?i=convert+1+fluid+ounce+to+liters
            $retval[1] = "1 cup $sign ".round(0.236588236, $rounding)." ".liter("L");  // 236.588236 mL  https://www.wolframalpha.com/input/?i=convert+1+cup+to+liters
            $retval[2] = "1 pint $sign ".round(0.473176473, $rounding)." ".liter("L");  // 473.176473 mL  https://www.wolframalpha.com/input/?i=convert+1+pint+to+liters
            $retval[3] = "1 quart $sign ".round(0.946352946, $rounding)." ".liter("L");   // 946.352946 mL https://www.wolframalpha.com/input/?i=convert+1+quart+to+liters
			$retval[4] = "1 gallon $sign ".round(3.78541178, $rounding)." ".liter("L");  // 3.78541178 L https://www.wolframalpha.com/input/?i=convert+1+gallon+to+milliliters
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 L $sign ".round(33.8140227, $rounding)." fl oz";  // 33.8140227 fl oz (fluid ounces)  https://www.wolframalpha.com/input/?i=convert+1+liter+to+pints
            $retval[1] = "1 L $sign ".round(4.22675284, $rounding)." C"; //  2.11337642 pints *2
            $retval[2] = "1 L $sign ".round(2.11337642, $rounding)." pt";    // 2.11337642 pints   https://www.wolframalpha.com/input/?i=convert+1+liter+to+fluid+ounces
            $retval[3] = "1 L $sign ".round(1.05668821, $rounding)." qt";    // 1.05668821 quarts
			$retval[4] = "1 L $sign ".round(0.264172052, $rounding)." gal";  // 0.264172052 gallons
        } else {
			$retval[0] = "1 ".liter("L")." $sign ".round(33.8140227, $rounding)." fluid ounces";
            $retval[1] = "1 ".liter("L")." $sign ".round(4.22675284, $rounding)." Cup";
            $retval[2] = "1 ".liter("L")." $sign ".round(2.11337642, $rounding)." pint";
            $retval[3] = "1 ".liter("L")." $sign ".round(1.05668821, $rounding)." quart";
			$retval[4] = "1 ".liter("L")." $sign ".round(0.264172052, $rounding)." gallon";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
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
        $retval[0] = "C = Circumference"; // of a circle
        $retval[1] = "A = Area";
        $retval[2] = "r = Radius";
        $retval[3] = "d = Diameter";
    } elseif($type=="Rectangle") {
        $retval[0] = "P = Perimeter";
        $retval[1] = "A = Area";
        $retval[2] = "L = Length";
        $retval[3] = "W = Width";
    } elseif($type=="Square") {
        $retval[0] = "P = Perimeter";
        $retval[1] = "A = Area";
        $retval[2] = "s = side";
    } elseif($type=="Area") {
        $retval[0] = "SA = Surface Area";
        $retval[1] = "L = Length";
        $retval[2] = "W = Width";
        $retval[3] = "H or h = Height";
        $retval[4] = "s = Side";
        $retval[5] = "r = Radius";
    } elseif($type=="Volume") {
        $retval[0] = "V = Volume";
        $retval[1] = "L = Length";
        $retval[2] = "W = Width";
        $retval[3] = "H or h = Height";
        $retval[4] = "s = Side";
        $retval[5] = "r = Radius";
    } elseif($type=="Triangle") {
        $retval[0] = "P = Perimeter";
        $retval[1] = "A = Area";
        $retval[2] = "b = base";
        $retval[3] = "h = Height";
    } elseif($type=="Temperature") {
        $retval[0] = "C = Celsius";
        $retval[1] = "F = Fahrenheit";
        $retval[2] = "K = Kelvin";
    } else {
        $retval[0] = "'".(string)$type."' is not a valid type.";
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
		$firstPart = "C";  // Circle
	} else {
        $type = $args[0];
        $firstPart = strtoupper(substr($type, 0, 1));
    }
    $tick = $args[1];
    $PI = verifyPI($args[2]);

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
        $retval[0] = "P = add all sides";
        $retval[1] = "{$tick}A = 1/2bh{$tick}";
    } elseif($type=="Rectangle") {
        $retval[0] = "{$tick}P = 2W+2L{$tick}";
        $retval[1] = "{$tick}A = LW{$tick}";
    } elseif($type=="Square") {
        $retval[0] = "{$tick}P = 4s{$tick}";
        $retval[1] = "{$tick}A = s^2{$tick}";
    } elseif($type=="SurfaceArea") {
        $retval[0] = "{$tick}SA=2LW+2LH+2WH{$tick} (Surface Area of a Rectangular Solid)";
        $retval[1] = conversionUnits2ScreenReader2("","SA",1,"6","s",2,"=",$tick)." (Surface Area of a Cube)";
        $retval[2] = conversionUnits2ScreenReader2("","SA",1,"4{$PI}","r",2,"=",$tick)." (Surface Area of a Sphere)";
        $retval[3] = conversionUnits2ScreenReader2("","SA",1,"2{$PI}rh+2{$PI}","r",2,"=",$tick)." (Surface Area of a Right Circular Cylinder)";
    } elseif($type=="Volume") {
        $retval[0] = "{$tick}V = LWH{$tick} (Volume of a Rectangular Solid)";
        $retval[1] = conversionUnits2ScreenReader2("","V",1,"","s",3,"=",$tick)." (Volume of a Cube)";
        $retval[2] = conversionUnits2ScreenReader2("","V",1,"4/3{$PI}","r",3,"=",$tick)." (Volume of a Sphere)";
        $retval[3] = conversionUnits2ScreenReader2("","V",1,"{$PI}h","r",2,"=",$tick)." (Volume of a Right Circular Cylindar)";
    } else {
        $retval[0] = "'".(string)$type."' is not a valid type.";
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
        $type = $args[0];
        $FirstLetter = strtoupper(substr($type, 0, 1));
    }

    $tick = verifyTickMarks($args[1]);

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
    } else {
        $retval[0] =  "{$tick}C=(5/9)(F-32){$tick}";
        $retval[1] =  "{$tick}C=K-273.15{$tick}";
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
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	}

	$system = $args[0];
	$fullname = verifyFullName($args[1]);
	$rounding = verifyRounding($args[2]);
    $tick = verifyTickMarks($args[4]);
    $sign = verifyEqualSign($args[3],$tick);


    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 ft = 12 in";
            $retval[1] = "1 yd = 3 ft";
            $retval[2] = "1 yd = 36 in";
            $retval[3] = "1 mi = 5,280 ft";
        } else {
            $retval[0] = "1 foot = 12 inches";
            $retval[1] = "1 yard = 3 feet";
            $retval[2] = "1 yard = 36 inches";
            $retval[3] = "1 mile = 5,280 feet";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 km = 1000 m";
            $retval[1] = "1 hm = 100 m";
            $retval[2] = conversionUnits2ScreenReader1("1 ","dam",1,"n")." = 10 m";
            $retval[3] = "1 m = 10 dm";
            $retval[4] = "1 m = 100 cm";
            $retval[5] = "1 m = 1000 mm";
        } else {
            $retval[0] = "1 kilo".meter("m")." = 1000 ".meter("m");
            $retval[1] = "1 hecto".meter("m")." = 100 ".meter("m");
            $retval[2] = "1 ".deca("d").meter("m")."  = 10 ".meter("m");
            $retval[3] = "1 ".meter("m")." = 10 deci".meter("m");
            $retval[4] = "1 ".meter("m")." = 100 centi".meter("m");
            $retval[5] = "1 ".meter("m")." = 1000 milli".meter("m");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 in $sign ".round(2.54, $rounding)." cm";     // https://www.wolframalpha.com/input/?i=convert+1+inch+to+mm
            $retval[1] = "1 ft $sign ".round(0.3048, $rounding)." m";    // https://www.wolframalpha.com/input/?i=convert+1+foot+to+dm
            $retval[2] = "1 yd $sign ".round(0.9144, $rounding)." m";  // https://www.wolframalpha.com/input/?i=convert+1+yard+to+dm
            $retval[3] = "1 mi $sign ".round(1.60934400, $rounding)." km";// 1.60934400 km https://www.wolframalpha.com/input/?i=convert+1+mile+to+m
        } else {
			$retval[0] = "1 inch $sign ".round(2.54, $rounding)." centi".meter("m");
            $retval[1] = "1 foot $sign ".round(0.3048, $rounding)." ".meter("m");
            $retval[2] = "1 yard $sign ".round(0.9144, $rounding)." ".meter("m");
            $retval[3] = "1 mile $sign ".round(1.60934400, $rounding)." kilo".meter("m");
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 cm $sign ".round(0.393700787, $rounding)." in";    // 393.700787 mils https://www.wolframalpha.com/input/?i=convert+1+centimeter+to+inch
            $retval[1] = "1 m $sign ".round(3.28083990, $rounding)." ft"; // 3.28083990 feet https://www.wolframalpha.com/input/?i=convert+1+meter+to+inch
            $retval[2] = "1 m $sign ".round(1.0936133, $rounding)." yd";  // 3.28083990 feet divided by 3
            $retval[3] = "1 km $sign ".round(0.621371, $rounding)." mi";   // 621371 miles https://www.wolframalpha.com/input/?i=convert+1000000+kilometer+to+miles
        } else {
			$retval[0] = "1 centi".meter("m")." $sign ".round(0.393700787, $rounding)." inch";
            $retval[1] = "1 ".meter("m")." $sign ".round(3.28083990, $rounding)." feet";
            $retval[2] = "1 ".meter("m")." $sign ".round(1.0936133, $rounding)." yard";
            $retval[3] = "1 kilo".meter("m")." $sign ".round(0.621371, $rounding)." mile";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
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
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	}

	$system = $args[0];
	$fullname = verifyFullName($args[1]);
	//$rounding = verifyRounding($args[2]);
	//$tick = verifyTickMarks($args[3]);

    $retval = array();

	if($system=="C"){
        if($fullname==0) {
            $retval[0] = "1 US Barrel = 42 gal";
            $retval[1] = "1 British Barrel = 43 gal";
            $retval[2] = "1 Hogshead = 63 gal";
            $retval[3] = "1 Barrique = 63 gal";
            $retval[4] = "1 Puncheon = 79 gal";
            $retval[5] = "1 Butt = 126 gal";
            $retval[6] = "1 Pipe = 145 gal";
            $retval[7] = "1 Tun = 252 gal";
        } else {
            $retval[0] = "1 US Barrel = 42 gallons";
            $retval[1] = "1 British Barrel = 43 gallons";
            $retval[2] = "1 Hogshead = 63 gallons";
            $retval[3] = "1 Barrique = 63 gallons";
            $retval[4] = "1 Puncheon = 79 gallons";
            $retval[5] = "1 Butt = 126 gallons";
            $retval[6] = "1 Pipe = 145 gallons";
            $retval[7] = "1 Tun = 252 gallons";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
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
        $type = $args[0];
    }

    $retval = array();

	$ShowAbb = verifyFullName($args[1]);
	if($ShowAbb == 0) {
        $retval[0] = "Kilo";
        $retval[1] = "Hecto";
        $retval[2] = deca("D");
        if($type == "G") {
            $retval[3] = gram("G") ;
        } elseif($type == "L") {
            $retval[3] =  liter("L");
        } else {
            $retval[3] = meter("M");
        }

        $retval[4] = "Deci";
        $retval[5] = "Centi";
        $retval[6] = "Milli";
    } else {
        $retval[0] = "Kilo (k)";
        $retval[1] = "Hecto (h)";
        $retval[2] = deca("D")." (<span aria-hidden=true>da</span><span class=\"sr-only\">d a</span>)";
        if($type == "G") {
            $retval[3] = gram("G")." (g)";
        } elseif($type == "L") {
            $retval[3] = liter("L")." (L)";
        } else {
            $retval[3] = meter("M")." (m)";
        }

        $retval[4] = "Deci (d)";
        $retval[5] = "Centi (c)";
        $retval[6] = "Milli (m)";
    }

	return $retval;
}

// conversionTime(Fullname)
// conversionTime() use Abbreviations
// conversionTime("y") use full name
function conversionTime() {
	$args = func_get_args();
    if (count($args)==0) {
        $retval[0] = "1 min = 60 sec";
		$retval[1] = "1 hr = 60 min";
		$retval[2] = "1 day = 24 hr";
		$retval[3] = "1 year = 365 days";
		$retval[4] = "1 decade = 10 years";
		$retval[5] = "1 century = 100 years";
    } else {
        $retval[0] = "1 minute = 60 seconds";
		$retval[1] = "1 hour = 60 minutes";
		$retval[2] = "1 day = 24 hours";
		$retval[3] = "1 year = 365 days";
		$retval[4] = "1 decade = 10 years";
		$retval[5] = "1 century = 100 years";
    }

	return $retval;
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
        if($tick=="`") {
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
    $exponentWord = exponent("e");
    $retval = conversionUnit2ScreenReaderModification($units,$tick);

    $unitTick = $retval[0];
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
    $exponentWord = exponent("e");
    $retval1 = conversionUnit2ScreenReaderModification($units1,$tick);
    $retval2 = conversionUnit2ScreenReaderModification($units2,$tick);

    $unitTick1 = $retval1[0];
    $unitSR1 = $retval1[1];

    $unitTick2 = $retval2[0];
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
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	}

	$system = $args[0];
	$fullname = verifyFullName($args[1]);
	$rounding = verifyRounding($args[2]);
	$tick = verifyTickMarks($args[3]);
    $sign = verifyEqualSign($args[4],$tick);

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ","ft",3,"1,728 ","in",3,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ","yd",3,"27 ","ft",3,"=",$tick);
        } elseif($fullname==1) {
            $retval[0] = "1 feet cubed = 1,728 inches cubed";
            $retval[1] = "1 yard cubed = 27 feet cubed";
        } elseif($fullname==2) {
            $retval[0] = "1 cubic feet = 1,728 cubic inches";
            $retval[1] = "1 cubic yard = 27 cubic feet";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = conversionUnits2ScreenReader2("1 ","km",3,"1000 ","hm",3,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ","hm",3,"1000 ","dam",3,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ","dam",3,"1000 ","m",3,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ","m",3,"1000 ","dm",3,"=",$tick);
            $retval[4] = conversionUnits2ScreenReader2("1 ","dm",3,"1000 ","cm",3,"=",$tick);
			$retval[5] = conversionUnits2ScreenReader2("1 ","cm",3,"1000 ","mm",3,"=",$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Kilo".meter("m")." cubed = 1000 Hecto".meter("m")."  cubed";
            $retval[1] = "1 Hecto".meter("m")." cubed = 1000 ".deca("D").meter("m")." cubed";
            $retval[2] = "1 ".deca("D").meter("m")." cubed = 1000 ".meter("M")." cubed";
            $retval[3] = "1 ".meter("M")." cubed = 1000 Deci".meter("m")." cubed";
            $retval[4] = "1 Deci".meter("m")." cubed = 1000 Centi".meter("m")." cubed";
			$retval[5] = "1 Centi".meter("m")." cubed = 1000 Milli".meter("m")." cubed";
        } else  {
			$retval[0] = "1 Cubic kilo".meter("m")." = 1000 Cubic hecto".meter("m");
            $retval[1] = "1 Cubic hecto".meter("m")." cubed = 1000 Cubic ".deca("d").meter("m");
            $retval[2] = "1 Cubic ".deca("d").meter("m")." cubed = 1000 Cubic ".meter("m");
            $retval[3] = "1 Cubic ".meter("m")." cubed = 1000 Cubic deci".meter("m");
            $retval[4] = "1 Cubic deci".meter("m")." cubed = 1000 Cubic centi".meter("m");
			$retval[5] = "1 Cubic centi".meter("m")." cubed = 1000 Cubic milli".meter("m");
        }
	} elseif($system=="AM"){
        // 0.0163870640 L https://www.wolframalpha.com/input/?i=convert+1+cubic+inch+to+ml
        $CF = round(16.3870640, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","in",3,"$CF ","mL",1,$sign,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Inch cubed $sign $CF Milli".liter("l");
        } else {
			$retval[0] = "1 Cubic inch $sign $CF Milli".liter("l");
        }
	} elseif($system=="MA"){
        // 61.0237441 in^3  https://www.wolframalpha.com/input/?i=convert+1+liter+to+cubic+feet
        $CF = round(61.0237441, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","L",1,"$CF ","in",3,$sign,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 ".liter("L")." $sign $CF Inches cubed";
        } else {
			$retval[0] = "1 ".liter("L")." $sign $CF Cubic inches";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
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
	}

	$system = $args[0];
	$fullname = verifyFullName($args[1]);
	$rounding = verifyRounding($args[2]);
    $tick = verifyTickMarks($args[4]);
    $sign = verifyEqualSign($args[3],$tick);

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 lb = 16 oz";
            $retval[1] = "1 T =2000 lbs";
        } else {
            $retval[0] = "1 pound = 16 ounces";
            $retval[1] = "1 Ton= 2000 pounds";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 kg = 1000 g";
            $retval[1] = "1 hg = 100 g";
            $retval[2] = conversionUnits2ScreenReader1("1 ","dag",1,"n")." = 10 g";
            $retval[3] = "1 g = 10 dg";
            $retval[4] = "1 g = 100 cg";
			$retval[5] = "1 g = 1000 mg";
			$retval[6] = "1 Tonne = 1000 kg";
        } else {
            $retval[0] = "1 kilo".gram("g")." = 1000 ".gram("g");
            $retval[1] = "1 hecto".gram("g")." = 100 ".gram("g");
            $retval[2] = "1 ".deca("d").gram("g")." = 10 ".gram("g");
            $retval[3] = "1 ".gram("g")." = 10 deci".gram("g");
            $retval[4] = "1 ".gram("g")." = 100 centi".gram("g");
            $retval[5] = "1 ".gram("g")." = 1000 milli".gram("g");
			$retval[6] = "1 Metric Ton = 1000 kilo".gram("g");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 oz $sign ".round(28.3495231, $rounding)." g";    // 0.0283495231 kg https://www.wolframalpha.com/input/?i=convert+1+ounce+to+gram
            $retval[1] = "1 lbs $sign ".round(0.453592370, $rounding)." kg"; // 0.453592370 kg https://www.wolframalpha.com/input/?i=convert+1+pound+to+gram
        } else {
			$retval[0] = "1 ounces $sign ".round(28.3495231, $rounding)." ".gram("g");
            $retval[1] = "1 pound $sign ".round(0.453592370, $rounding)." kilo".gram("g");;
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 g $sign ".round(0.035274, $rounding)." oz";
            $retval[1] = "1 kg $sign ".round(2.20462, $rounding)." lbs";
        } else {
			$retval[0] = "1 ".gram("g")." $sign ".round(0.035274, $rounding)." ounces";
            $retval[1] = "1 kilo".gram("g")." $sign ".round(2.20462, $rounding)." pound";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// 2021-09-24 ver 20 - Changed all spelling _('') to functions for easier maintance.
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
