<?php
// conversion module - this contains constants for use with Rate and Ratio conversion questions
// Mike Jenck, Originally developed Jan 29-31, 2021
// licensed under GPL version 2 or later
//


function conversionVer() {
	// File version
	return 2;
}

global $allowedmacros;

// COMMENT OUT BEFORE UPLOADING
if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "conversionVer", "conversionDisplay", "conversionPrefix", "conversionAbbreviations", "conversionLength",  "conversionCapacity", "conversionWeight",  "conversionArea", "conversionVolume", "conversionFormulaAbbreviations", "conversionTime" );


// -------------------------------------------------------------------------------------------------
// internal only  ----------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
function verifyRounding($input) {
	if(!is_null($input)) {
		$rounding = $input;
		if($rounding<2) {
            $rounding=2;
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
        } elseif ($fullname>1){
            $fullname=0;
        }
	}
	else { $fullname=0; }

	return $fullname;
}

function verifyTickMarks($input) {
	if(!is_null($input)) {
		$TickMarks = "`";
	}
	else { $TickMarks = ""; }

	return $TickMarks;
}

function verifyString($input) {
	if(!is_null($input)) {
		$retval =  $input;
	}
	else { $retval = ""; }

	return $retval;
}

// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// conversion Misc ---------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------

function conversionFormulaAbbreviations() {
    $retval[0] = "A = Area";
    $retval[1] = "SA = Surface Area";
    $retval[2] = "r = Radius";
	$retval[3] = "V = Volume";

	return $retval;
}

// function conversionDisplay(Title,Factors)
//
// INPUTS:
//     Title: string like Length, Capacity, etc.
//   Factors: An array of strings that are displayed
//
// Examples
//
// use conversionDisplay("Length",$americanLength) returns the HTML to display the output
function conversionDisplay($Title,$Factors) {
	$args = func_get_args();
	if (count($args)==0) {
		return "";
	} else
	$Title = (string)$args[0];

	$retval = "<ul>\r\n<li>$Title\r\n";

	$element = count($Factors);

	if($element >0){
		$retval .= "<ul>\r\n";

        for($i=0; $i < $element; $i++){
            $retval .= "<li>".$Factors[$i]."</li>\r\n";
        }
        $retval .= "</ul>\r\n";
    }

	$retval .= "</ul>\r\n";

	return $retval;
}

// function conversionFormulaTempature(type)
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "F" - F = (default)
//           "C" - C =
//
// Examples
//
// use conversionFormulaTempature("F") returns the formula for F = 9/5C+32
function conversionFormulaTempature() {
	$args = func_get_args();
	if (count($args)==0) {
		$type = "F";
	} else {
        $type = $args[0];
    }

	if($type == "F") {
        return "F=9/5C+32";
    } else {
		return "C=(5(F-32))/9";
    }
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

	$ShowAbb = verifyFullName($args[1]);
	if($ShowAbb == 0) {
        $retval[0] = "Kilo";
        $retval[1] = "Hecto";
        $retval[2] = _('Deca');
        if($type == "G") {
            $retval[3] = _('Gram');
        } elseif($type == "L") {
            $retval[3] = _('Liter');
        } else {
            $retval[3] = _('Meter');
        }

        $retval[4] = "Deci";
        $retval[5] = "Centi";
        $retval[6] = "Milli";
    } else {
        $retval[0] = "Kilo (k)";
        $retval[1] = "Hecto (h)";
        $retval[2] = _('Deca')." (da)";
        if($type == "G") {
            $retval[3] = _('Gram')." (g)";
        } elseif($type == "L") {
            $retval[3] = _('Liter')." (L)";
        } else {
            $retval[3] = _('Meter')." (m)";
        }

        $retval[4] = "Deci (d)";
        $retval[5] = "Centi (c)";
        $retval[6] = "Milli (m)";
    }

	return $retval;
}

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

// function conversionAbbreviations(system,type[,tick])
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "A" - American
//           "M" - Metric
//
//     type: Length
//           Capacity
//           Weight
//           Area
//           Volume
//           Formula
//     tick: add a tick mark around items with exponents
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

	if($FirstLetter=="L") {$type="Length";}
	if($FirstLetter=="C") {$type="Capacity";}
	if($FirstLetter=="W") {$type="Weight";}
	if($FirstLetter=="M") {$type="Mass";}
	if($FirstLetter=="A") {$type="Area";}
	if($FirstLetter=="V") {$type="Volume";}

	if(($type!="Length")&&($type!="Capacity")&&($type!="Weight")&&($type!="Area")&&($type!="Volume")&&($type!="Mass")){
		$type="Length";
    }

	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="A"){
		if($type=="Length"){
			$retval[0] = "Inches = in";
			$retval[1] = "Feet (foot) = ft";
			$retval[2] = "Yard = yd";
			$retval[3] = "Mile = mi";
        } elseif($type=="Capacity"){
			$retval[0] = "Fluid ounces = fl oz";
			$retval[1] = "Cup = c";
			$retval[2] = "Pint = pt";
			$retval[3] = "Quart = qt";
			$retval[4] = "Gallon = gal";
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = "Ounces = oz";
			$retval[1] = "Pounds = lbs";
			$retval[2] = "Ton = T";
        } elseif($type=="Area"){
			$retval[0] = "Inches squared = $tick"."in^2$tick";
			$retval[1] = "Feet squared = $tick"."ft^2$tick";
			$retval[2] = "Yard squared = $tick"."yd^2$tick";
			$retval[3] = "Mile squared = $tick"."Mi^2$tick";
			//$retval[4] = "";
        } elseif($type=="Volume"){
			$retval[0] = "Inches cubed = $tick"."in^3$tick";
			$retval[1] = "Feet cubed = $tick"."ft^3$tick";
			$retval[2] = "Yard cubed = $tick"."yd^3$tick";
        }

	}

	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="M"){
		if($type=="Length"){
			$retval[0] = "Milli"._('meter')." = mm";
			$retval[1] = "Centi"._('meter')." = cm";
			$retval[2] = "Deci"._('meter')." = dm";
			$retval[3] = _('Meter')." = m";
			$retval[4] = _('Deca')._('meter')." = "._('da')." m";
			$retval[5] = "Hecto"._('meter')." = hm";
			$retval[6] = "Kilo"._('meter')." = km";
        } elseif($type=="Capacity"){
			$retval[0] = "Milli"._('liter')." = mL";
			$retval[1] = "Centi"._('liter')." = cL";
			$retval[2] = "Deci"._('liter')." = dL";
			$retval[3] = _('Liter')." = L";
			$retval[4] = _('Deca')._('liter')." = "._('da')." L";
			$retval[5] = "Hecto"._('liter')." = hL";
			$retval[6] = "Kilo"._('liter')." = kL";
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = "Milli"._('gram')." = mg";
			$retval[1] = "Centi"._('gram')." = cg";
			$retval[2] = "Deci"._('gram')." = dg";
			$retval[3] = _('Gram')." = g";
			$retval[4] = _('Deca')._('gram')." = "._('da')." g";
			$retval[5] = "Hecto"._('gram')." = hg";
			$retval[6] = "Kilo"._('gram')." = kg";
			$retval[7] = "Metric Ton = Tonne";
        } elseif($type=="Area"){
			$retval[0] = "Milli"._('meter')." squared= $tick"."mm^2$tick";
			$retval[1] = "Centi"._('meter')." squared= $tick"."cm^2$tick";
			$retval[2] = "Deci"._('meter')." squared= $tick"."dm^2$tick";
			$retval[3] = _('Meter')." squared= $tick"."m^2$tick";
			$retval[4] = _('Deca')._('meter')." squared= $tick"._('da')." m^2$tick";
			$retval[5] = "Hecto"._('meter')." squared = $tick"."hm^2$tick";
			$retval[6] = "Kilo"._('meter')." squared = $tick"."km^2$tick";
			$retval[7] = "Ares = a";
			$retval[8] = "Hectares = ha";
        } elseif($type=="Volume"){
			$retval[0] = "Milli"._('meter')." cubed = $tick"."mm^3$tick";
			$retval[1] = "Centi"._('meter')." cubed = $tick"."cm^3$tick";
			$retval[2] = "Deci"._('meter')." cubed = $tick"."dm^3$tick";
			$retval[3] = _('Meter')." cubed = $tick"._('da')." m^3$tick";
			$retval[4] = _('Deca')._('meter')." cubed = $tick"._('da')." m^3$tick";
			$retval[5] = "Hecto"._('meter')." cubed = $tick"."hm^3$tick";
			$retval[6] = "Kilo"._('meter')." cubed = $tick"."km^3$tick";
        }
	}

	return $retval;
}

// function conversionLength(type [,FullWords,Rounding])
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

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "12 in = 1 ft";
            $retval[1] = "3 ft = 1 yd";
            $retval[2] = "36 in = 1 yd";
            $retval[3] = "5,280 ft = 1 mi";
        } else {
            $retval[0] = "12 inches = 1 foot";
            $retval[1] = "3 feet = 1 yard";
            $retval[2] = "36 inches = 1 yard";
            $retval[3] = "5,280 feet = 1 mile";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 km = 1000 m";
            $retval[1] = "1 hm = 100 m";
            $retval[2] = "1 "._('da')." m = 10 m";
            $retval[3] = "1 m = 10 dm";
            $retval[4] = "1 m = 100 cm";
            $retval[5] = "1 m = 1000 mm";
        } else {
            $retval[0] = "1 kilo"._('meter')." = 1000 "._('meter');
            $retval[1] = "1 hecto"._('meter')." = 100 "._('meter');
            $retval[2] = "1 "._('Deca')._('meter')."  = 10 "._('meter');
            $retval[3] = "1 "._('meter')." = 10 deci"._('meter');
            $retval[4] = "1 "._('meter')." = 100 centi"._('meter');
            $retval[5] = "1 "._('meter')." = 1000 milli"._('meter');
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 in = ".round(2.54, $rounding)." cm";
            $retval[1] = "1 ft = ".round(1/3.2808, $rounding)." m";
            $retval[2] = "1 yd = ".round(1/1.0936, $rounding)." m";
            $retval[3] = "1 mi = ".round(1/0.62137, $rounding)." km";
        } else {
			$retval[0] = "1 inch = ".round(2.54, $rounding)." centi"._('meter');
            $retval[1] = "1 foot = ".round(1/3.2808, $rounding)._('meter');
            $retval[2] = "1 yard = ".round(1/1.0936, $rounding)._('meter');
            $retval[3] = "1 mile = ".round(1/0.62137, $rounding)." kilo"._('meter');
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 cm = ".round(1/2.54, $rounding)." in";
            $retval[1] = "1 m = ".round(3.2808339, $rounding)." ft";
            $retval[2] = "1 m = ".round(1.0936, $rounding)." yd";
            $retval[3] = "1 km = ".round(0.62137, $rounding)." mi";
        } else {
			$retval[0] = "1 centi"._('meter')." = ".round(1/2.54, $rounding)." inch";
            $retval[1] = "1 "._('meter')." = ".round(3.2808, $rounding)." feet";
            $retval[2] = "1 "._('meter')." = ".round(1.0936, $rounding)." yard";
            $retval[3] = "1 kilo"._('meter')." = ".round(0.62137, $rounding)." mile";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// function conversionCapacity(type [,FullWords,Rounding])
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

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 C = 8 fl oz";
            $retval[1] = "1 pt = 2 C";
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
            $retval[2] = "1 "._('da')." L = 10 L";
            $retval[3] = "1 L = 10 dL";
            $retval[4] = "1 L = 100 cL";
			$retval[5] = "1 L = 1000 mL";
        } else {
            $retval[0] = "1 kilo"._('liter')." = 1000 "._('Liter');
            $retval[1] = "1 hecto"._('liter')." = 100 "._('Liter');
            $retval[2] = "1 "._('Deca')._('liter')." = 10 "._('Liter');
            $retval[3] = "1 "._('Liter')." = 10 deci"._('liter');
            $retval[4] = "1 "._('Liter')." = 100 centi"._('liter');
            $retval[5] = "1 "._('Liter')." = 1000 milli"._('liter');
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 fl oz = ".round(0.0295735, $rounding)." L";
            $retval[1] = "1 C = ".round(.236588237, $rounding)." L";
            $retval[2] = "1 pt = ".round(0.4731765, $rounding)." L";
            $retval[3] = "1 qt = ".round(0.9463529, $rounding)." L";
			$retval[4] = "1 gal = ".round(3.78541, $rounding)." L";
        } else {
			$retval[0] = "1 fluid ounces = ".round(0.0295735, $rounding)._('Liter');
            $retval[1] = "1 Cup = ".round(.236588237, $rounding)._('Liter');
            $retval[2] = "1 pint = ".round(0.4731765, $rounding)._('Liter');
            $retval[3] = "1 quart = ".round(0.9463529, $rounding)._('Liter');
			$retval[4] = "1 gallon = ".round(3.78541, $rounding)._('Liter');
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 L = ".round(33.814, $rounding)." fl oz";
            $retval[1] = "1 L = ".round(4.166666666, $rounding)." C";
            $retval[2] = "1 L = ".round(2.11338, $rounding)." pt";
            $retval[3] = "1 L = ".round(1.05669, $rounding)." qt";
			$retval[4] = "1 L = ".round(0.264172, $rounding)." gal";
        } else {
			$retval[0] = "1 "._('Liter')." = ".round(33.814, $rounding)." fluid ounces";
            $retval[1] = "1 "._('Liter')." = ".round(4.166666666, $rounding)." Cup";
            $retval[2] = "1 "._('Liter')." = ".round(2.11338, $rounding)." pint";
            $retval[3] = "1 "._('Liter')." = ".round(1.05669, $rounding)." quart";
			$retval[4] = "1 "._('Liter')." = ".round(0.264172, $rounding)." gallon";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// function conversionWeight(type [,FullWords,Rounding])
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

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "16 oz = 1 lbs";
            $retval[1] = "2000 lbs = 1 T";
        } else {
            $retval[0] = "16 ounces = 1 pound";
            $retval[1] = "2000 pounds = 1 Ton";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 kg = 1000 g";
            $retval[1] = "1 hg = 100 g";
            $retval[2] = "1 "._('da')." g = 10 g";
            $retval[3] = "1 g = 10 dg";
            $retval[4] = "1 g = 100 cg";
			$retval[5] = "1 g = 1000 mg";
			$retval[6] = "1 Tonne = 1000 kg";
        } else {
            $retval[0] = "1 kilogram = 1000 gram";
            $retval[1] = "1 hectogram = 100 gram";
            $retval[2] = "1 "._('Deca')."gram = 10 gram";
            $retval[3] = "1 gram = 10 decigram";
            $retval[4] = "1 gram = 100 centigram";
            $retval[5] = "1 gram = 1000 milligram";
			$retval[6] = "1 Metric Ton = 1000 kg";
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 oz = ".round(28.3495, $rounding)." g";
            $retval[1] = "1 lbs = ".round(0.453592, $rounding)." kg";
        } else {
			$retval[0] = "1 ounces = ".round(28.3495, $rounding)." g";
            $retval[1] = "1 pound = ".round(0.453592, $rounding)." kg";
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 g = ".round(0.035274, $rounding)." oz";
            $retval[1] = "1 kg = ".round(2.20462, $rounding)." lbs";
        } else {
			$retval[0] = "1 gram = ".round(0.035274, $rounding)." ounces";
            $retval[1] = "1 kilogram = ".round(2.20462, $rounding)." pound";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// function conversionArea(type [,FullWords,Rounding,tick])
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
//     tick: add a tick mark around items with exponents
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
	$tick = verifyTickMarks($args[3]);

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 $tick"."ft^2$tick = 144 $tick"."in^2$tick";
            $retval[1] = "1 $tick"."yd^2$tick = 9 $tick"."ft^2$tick";
			$retval[2] = "1 acre = 43,560 $tick"."ft^2$tick";
            $retval[3] = "1 $tick"."mi^2$tick = 640 acre";
        } else {
            $retval[0] = "1 feet squared = 144 inches squared";
            $retval[1] = "1 yard squared = 9 feet squared";
			$retval[2] = "1 acre = 43,560 feet squared ";
            $retval[3] = "1 mile squared  = 640 acre";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 $tick"."km^2$tick = 100 $tick"."hm^2$tick";
            $retval[1] = "1 $tick"."hm^2$tick = 100 $tick"._('da')." m^2$tick";
            $retval[2] = "1 $tick"._('da')." m^2$tick = 100 $tick"."m^2$tick";
            $retval[3] = "1 $tick"."m^2$tick = 100 $tick"."dm^2$tick";
            $retval[4] = "1 $tick"."dm^2$tick = 100 $tick"."cm^2$tick";
			$retval[5] = "1 $tick"."cm^2$tick = 100 $tick"."mm^2$tick";
			$retval[6] = "1 a = 100 $tick"."m^2$tick";
			$retval[7] = "1 ha = 100 a";
        } else {
			$retval[0] = "1 Kilo"._('meter')." squared = 100 Hecto"._('meter')." squared";
            $retval[1] = "1 Hecto"._('meter')."  squared = 100 "._('Deca')._('meter')." squared";
            $retval[2] = "1 "._('Deca')._('meter')." squared = 100 "._('meter')." squared";
            $retval[3] = "1 "._('meter')." squared = 100 Deci"._('meter')." squared";
            $retval[4] = "1 Deci"._('meter')." squared = 100 Centi"._('meter')." squared";
			$retval[5] = "1 Centi"._('meter')." squared = 100 Milli"._('meter')." squared";
			$retval[6] = "1 Ares = 100 "._('meter')." squared";
			$retval[7] = "1 Hectares = 100 Ares";
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 $tick"."in^2$tick = ".round(6.4516, $rounding)." $tick"."cm^2$tick";
        } else {
			$retval[0] = "1 inch squared = ".round(6.4516, $rounding)." Centi"._('meter')." squared";
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 $tick"."m^2$tick = ".round(1.19599, $rounding)." $tick"."yd^2$tick";
            $retval[1] = "1 ha = ".round(2.47105, $rounding)." acres";
        } else {
			$retval[0] = "1 "._('meter')." squared = ".round(1.19599, $rounding)." yard squared";
            $retval[1] = "1 ha = ".round(2.47105, $rounding)." acres";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// function conversionVolume(type [,FullWords,Rounding,tick])
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
//     tick: add a tick mark around items with exponents
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

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 $tick"."ft^3$tick = 1,728 $tick"."in^3$tick";
            $retval[1] = "1 $tick"."yd^3$tick = 27 $tick"."ft^3$tick";
        } else {
            $retval[0] = "1 feet cubed = 1,728 inches cubed";
            $retval[1] = "1 yard cubed = 27 feet cubed";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 $tick"."km^3$tick = 1000 $tick"."hm^3$tick";
            $retval[1] = "1 $tick"."hm^3$tick = 1000 $tick"._('da')." m^3$tick";
            $retval[2] = "1 $tick"._('da')." m^3$tick = 1000 $tick"."m^3$tick";
            $retval[3] = "1 $tick"."m^3$tick = 1000 $tick"."dm^3$tick";
            $retval[4] = "1 $tick"."dm^3$tick = 1000 $tick"."cm^3$tick";
			$retval[5] = "1 $tick"."cm^3$tick = 1000 $tick"."mm^3$tick";
        } else {
			$retval[0] = "1 Kilo"._('meter')." cubed = 1000 Hecto"._('meter')."  cubed";
            $retval[1] = "1 Hecto"._('meter')." cubed = 1000 "._('Deca')._('meter')." cubed";
            $retval[2] = "1 "._('Deca')._('meter')." cubed = 1000 "._('Meter')." cubed";
            $retval[3] = "1 "._('Meter')." cubed = 1000 Deci"._('meter')." cubed";
            $retval[4] = "1 Deci"._('meter')." cubed = 1000 Centi"._('meter')." cubed";
			$retval[5] = "1 Centi"._('meter')." cubed = 1000 Milli"._('meter')." cubed";
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 $tick"."in^3$tick = ".round(16.3871, $rounding)." mL";
        } else {
			$retval[0] = "1 inch cubed = ".round(16.3871, $rounding)." Milli"._('liter');
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 L = ".round(61.0237, $rounding)." $tick"."in^3$tick";
        } else {
			$retval[0] = "1 "._('Liter')." = ".round(61.0237, $rounding)." inches cubed";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// 2021-01-31 ver 2 - added conversionDisplay
// 2021-01-31 ver 1 - initial release

?>
