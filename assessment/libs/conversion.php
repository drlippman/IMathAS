<?php
// Conversion module - this contains constants for use with Rate and Ratio conversion questions
// Mike Jenck, Originally developed Jan 29-31, 2021
// licensed under GPL version 2 or later
//

function conversionVer() {
	// File version
	return 9;
}

global $allowedmacros;

// COMMENT OUT BEFORE UPLOADING
if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "conversionVer", "conversionDisplay", "conversionDisplay2HTML", "conversionDisplay2HTMLwithBorder",
    "conversionPrefix", "conversionAbbreviations", "conversionLength", "conversionLiquid", "conversionUnits2ScreenReader",
    "conversionCapacity", "conversionWeight",  "conversionArea", "conversionVolume",
    "conversionTime", "conversionFormulaAbbreviations", "conversionFormulaTemperature" );

// -------------------------------------------------------------------------------------------------
// internal only  ----------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
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
// Conversion Misc ---------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------

function conversionFormulaAbbreviations() {
    $retval = array();

    $retval[0] = "A = Area";
    $retval[1] = "SA = Surface Area";
    $retval[2] = "r = Radius";
	$retval[3] = "V = Volume";

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
            $retval[$index] .= "<ul>\r\n";

            for($i=0; $i < $element; $i++){
                $retval[$index] .= "<li>".$Factors[$i]."</li>\r\n";
            }
            $retval[$index] .= "</ul>\r\n";
        }
        //if(strlen($Title) > 0) { $retval[$index] .= "</ul>\r\n";}
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
        $HTML .="<td style=\"padding: $cellPadding"."px;\">$CellValueArray[$i]</td>\r\n";
    }
    $HTML .= "</tr>\r\n</table>\r\n";

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
        $HTML .="<td style=\"border: 1px solid black;padding: $cellPadding"."px;\">$CellValueArray[$i]</td>\r\n";
    }
    $HTML .= "</tr>\r\n</table>\r\n";

	return $HTML;
}

// function conversionFormulaTemperature(type)
// Returns the Abbreviations to words
//
// INPUTS:
//   system: "F" - F = (default)
//           "C" - C =
//
// Examples
//
// use ConversionFormulaTemperature("F") returns the formula for F = 9/5C+32
function conversionFormulaTemperature() {
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

    $retval = array();

	$ShowAbb = verifyFullName($args[1]);
	if($ShowAbb == 0) {
        $retval[0] = "Kilo";
        $retval[1] = "Hecto";
        $retval[2] = _('Deca');
        if($type == "G") {
            $retval[3] = _('Gram') ;
        } elseif($type == "L") {
            $retval[3] =  _('Liter');
        } else {
            $retval[3] = _('Meter');
        }

        $retval[4] = "Deci";
        $retval[5] = "Centi";
        $retval[6] = "Milli";
    } else {
        $retval[0] = "Kilo (k)";
        $retval[1] = "Hecto (h)";
        $retval[2] = _('Deca')." (<span aria-hidden=true>da</span><span class=\"sr-only\">d a</span>)";
        if($type == "G") {
            $retval[3] = _('Gram') ." (g)";
        } elseif($type == "L") {
            $retval[3] = _('Liter') ." (L)";
        } else {
            $retval[3] = _('Meter')." (m)";
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

//function conversionUnits2ScreenReader(units,dimensions,tick)
// Returns the Abbreviations to words
//
// INPUTS:
//           units: ft - feet
//                  in - inch
//                  m = meter
//                  ect.
//
//       dimensions: 2,3 (except dam it allows 1,2, or 3)
//             tick: add tick marks
//
function conversionUnits2ScreenReader($units,$dimensions=2,$tick="y"){
    $units = strtolower($units);
    $tick = verifyTickMarks($tick);
    $exponentWord = _('exponent');

    if($dimensions==1) {
        if($tick=="`") {
            if($units=="in") {
                return "<span aria-hidden=true>`i n`</span><span class=\"sr-only\">in</span>";
            } elseif($units=="dam") {
                return "<span aria-hidden=true>`dam`</span><span class=\"sr-only\">d a m</span>";
            } elseif($units=="dal") {
                return "<span aria-hidden=true>`daL`</span><span class=\"sr-only\">d a L</span>";
            } elseif($units=="dag") {
                return "<span aria-hidden=true>`dag`</span><span class=\"sr-only\">d a g</span>";
            } else {
                return "`$units`";
            }
        } else {
            if($units=="in") {
                return "<span aria-hidden=true>in</span><span class=\"sr-only\">in</span>";
            } elseif($units=="dam") {
                return "<span aria-hidden=true>dam</span><span class=\"sr-only\">d a m</span>";
            } elseif($units=="dal") {
                return "<span aria-hidden=true>daL</span><span class=\"sr-only\">d a L</span>";
            } elseif($units=="dag") {
                return "<span aria-hidden=true>dag</span><span class=\"sr-only\">d a g</span>";
            } else {
                return "$units";
            }
        }
    } else {
        if($tick=="`") {
            if($units=="in") {
                return "<span aria-hidden=true>`i n^$dimensions`</span><span class=\"sr-only\">in $exponentWord $dimensions</span>";
            } elseif($units=="dam") {
                return "<span aria-hidden=true>`dam^$dimensions`</span><span class=\"sr-only\">d a m $exponentWord $dimensions</span>";
            } elseif($units=="dal") {
                return "<span aria-hidden=true>`daL^$dimensions`</span><span class=\"sr-only\">d a L $exponentWord $dimensions</span>";
            } elseif($units=="dag") {
                return "<span aria-hidden=true>`dag^$dimensions`</span><span class=\"sr-only\">d a g $exponentWord $dimensions</span>";
            } else {
                return "<span aria-hidden=true>`$units^$dimensions`</span><span class=\"sr-only\">$units $exponentWord $dimensions</span>";
            }
        } else {
            if($units=="in") {
                return "<span aria-hidden=true>in^$dimensions</span><span class=\"sr-only\">in $exponentWord $dimensions</span>";
            } elseif($units=="dam") {
                return "<span aria-hidden=true>dam^$dimensions</span><span class=\"sr-only\">d a m $exponentWord $dimensions</span>";
            } elseif($units=="dal") {
                return "<span aria-hidden=true>daL^$dimensions</span><span class=\"sr-only\">d a L $exponentWord $dimensions</span>";
            } elseif($units=="dag") {
                return "<span aria-hidden=true>dag^$dimensions</span><span class=\"sr-only\">d a g $exponentWord $dimensions</span>";
            } else {
                return "<span aria-hidden=true>$units^$dimensions</span><span class=\"sr-only\">$units $exponentWord $dimensions</span>";
            }
        }
    }

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
//           e.g. Inches squared/Square inches
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
			if($fullname==0) {
                $retval[0] = "Inches squared = ".conversionUnits2ScreenReader("in",2,$tick);
                $retval[1] = "Feet squared = ".conversionUnits2ScreenReader("ft",2,$tick);
                $retval[2] = "Yard squared = ".conversionUnits2ScreenReader("yd",2,$tick);
                $retval[3] = "Mile squared = ".conversionUnits2ScreenReader("mi",2,$tick);
            } else {
                $retval[0] = "Square inches = ".conversionUnits2ScreenReader("in",2,$tick);
                $retval[1] = "Square feet = ".conversionUnits2ScreenReader("ft",2,$tick);
                $retval[2] = "Square yard = ".conversionUnits2ScreenReader("yd",2,$tick);
                $retval[3] = "Square mile = ".conversionUnits2ScreenReader("mi",2,$tick);
            }

        } elseif($type=="Volume"){
			if($fullname==0) {
                $retval[0] = "Inches cubed = ".conversionUnits2ScreenReader("in",3,$tick);
                $retval[1] = "Feet cubed = ".conversionUnits2ScreenReader("ft",3,$tick);
                $retval[2] = "Yard cubed = ".conversionUnits2ScreenReader("yd",3,$tick);
            } else {
                $retval[0] = "Cubic inches = ".conversionUnits2ScreenReader("in",3,$tick);
                $retval[1] = "Cubic feet = ".conversionUnits2ScreenReader("ft",3,$tick);
                $retval[2] = "Cubic yard = ".conversionUnits2ScreenReader("yd",3,$tick);
            }
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
			$retval[4] = _('Deca'). _('meter')." = ".conversionUnits2ScreenReader("dam",1,"n");
			$retval[5] = "Hecto"._('meter')." = hm";
			$retval[6] = "Kilo"._('meter')." = km";
        } elseif($type=="Capacity"){
			$retval[0] = "Milli" . _('liter') . " = mL";
			$retval[1] = "Centi" . _('liter') . " = cL";
			$retval[2] = "Deci" . _('liter') . " = dL";
			$retval[3] = _('Liter') . " = L";
			$retval[4] = _('Deca')._('liter') . " = ".conversionUnits2ScreenReader("daL",1,"n");
			$retval[5] = "Hecto" . _('liter') . " = hL";
			$retval[6] = "Kilo" . _('liter') . " = kL";
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = "Milli"._('gram')." = mg";
			$retval[1] = "Centi"._('gram')." = cg";
			$retval[2] = "Deci"._('gram')." = dg";
			$retval[3] = _('Gram') ." = g";
			$retval[4] = _('Deca')._('gram')." = ".conversionUnits2ScreenReader("dag",1,"n");
			$retval[5] = "Hecto"._('gram')." = hg";
			$retval[6] = "Kilo"._('gram')." = kg";
			$retval[7] = "Metric Ton = Tonne";
        } elseif($type=="Area"){
			if($fullname==0) {
                $retval[0] = "Milli"._('meter')." squared = ".conversionUnits2ScreenReader("mm",2,$tick);
                $retval[1] = "Centi"._('meter')." squared = ".conversionUnits2ScreenReader("cm",2,$tick);
                $retval[2] = "Deci"._('meter')." squared = ".conversionUnits2ScreenReader("dm",2,$tick);
                $retval[3] = _('Meter')." squared = ".conversionUnits2ScreenReader("m",2,$tick);
                $retval[4] = _('Deca')._('meter')." squared = ".conversionUnits2ScreenReader("dam",2,$tick);
                $retval[5] = "Hecto"._('meter')." squared = ".conversionUnits2ScreenReader("hm",2,$tick);
                $retval[6] = "Kilo"._('meter')." squared = ".conversionUnits2ScreenReader("km",2,$tick);
            } else {
                $retval[0] = "Square milli"._('meter')." = ".conversionUnits2ScreenReader("mm",2,$tick);
                $retval[1] = "Square centi"._('meter')." = ".conversionUnits2ScreenReader("cm",2,$tick);
                $retval[2] = "Square deci"._('meter')." = ".conversionUnits2ScreenReader("dm",2,$tick);
                $retval[3] = "Square "._('meter')." = ".conversionUnits2ScreenReader("m",2,$tick);
                $retval[4] = "Square "._('deca')._('meter')." = ".conversionUnits2ScreenReader("dam",2,$tick);
                $retval[5] = "Square hecto"._('meter')." = ".conversionUnits2ScreenReader("hm",2,$tick);
                $retval[6] = "Square kilo"._('meter')." = <".conversionUnits2ScreenReader("km",2,$tick);
            }
			$retval[7] = "Ares = a";
			$retval[8] = "Hectares = ha";
        } elseif($type=="Volume") {
			if($fullname==0) {
                $retval[0] = "Milli"._('meter')." cubed = ".conversionUnits2ScreenReader("mm",3,$tick);
                $retval[1] = "Centi"._('meter')." cubed = ".conversionUnits2ScreenReader("cm",3,$tick);
                $retval[2] = "Deci"._('meter')." cubed = ".conversionUnits2ScreenReader("dm",3,$tick);
                $retval[3] = _('Meter')." cubed = ".conversionUnits2ScreenReader("m",3,$tick);
                $retval[4] = _('Deca')._('meter')." cubed = ".conversionUnits2ScreenReader("dam",3,$tick);
                $retval[5] = "Hecto"._('meter')." cubed = ".conversionUnits2ScreenReader("hm",3,$tick);
                $retval[6] = "Kilo"._('meter')." cubed = ".conversionUnits2ScreenReader("km",3,$tick);
            } else {
                $retval[0] = "Cubic milli"._('meter')." = ".conversionUnits2ScreenReader("mm",3,$tick);
                $retval[1] = "Cubic centi"._('meter')." = ".conversionUnits2ScreenReader("cm",3,$tick);
                $retval[2] = "Cubic deci"._('meter')." = ".conversionUnits2ScreenReader("dm",3,$tick);
                $retval[3] = "Cubic "._('meter')." = ".conversionUnits2ScreenReader("m",3,$tick);
                $retval[4] = "Cubic "._('deca')._('meter')." = ".conversionUnits2ScreenReader("dam",3,$tick);
                $retval[5] = "Cubic hecto"._('meter')." = ".conversionUnits2ScreenReader("hm",3,$tick);
                $retval[6] = "Cubic kilo"._('meter')." = ".conversionUnits2ScreenReader("km",3,$tick);
            }
        }
	}


	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="T"){
        $retval[0] = "Seconds = sec";
		$retval[1] = "Minutes = min";
		$retval[2] = "Hour = hr";
		$retval[3] = "Day = d";
		$retval[4] = "Year = yr.";
		$retval[5] = "Century = c";
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

    $retval = array();

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
            $retval[2] = "1 ".conversionUnits2ScreenReader("dam",1,"n")." = 10 m";
            $retval[3] = "1 m = 10 dm";
            $retval[4] = "1 m = 100 cm";
            $retval[5] = "1 m = 1000 mm";
        } else {
            $retval[0] = "1 kilo"._('meter')." = 1000 "._('meter');
            $retval[1] = "1 hecto"._('meter')." = 100 "._('meter');
            $retval[2] = "1 "._('deca')._('meter')."  = 10 "._('meter');
            $retval[3] = "1 "._('meter')." = 10 deci"._('meter');
            $retval[4] = "1 "._('meter')." = 100 centi"._('meter');
            $retval[5] = "1 "._('meter')." = 1000 milli"._('meter');
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 in = ".round(2.54, $rounding)." cm";     // https://www.wolframalpha.com/input/?i=convert+1+inch+to+mm
            $retval[1] = "1 ft = ".round(0.3048, $rounding)." m";    // https://www.wolframalpha.com/input/?i=convert+1+foot+to+dm
            $retval[2] = "1 yd = ".round(0.9144, $rounding)." m";  // https://www.wolframalpha.com/input/?i=convert+1+yard+to+dm
            $retval[3] = "1 mi = ".round(1.60934400, $rounding)." km";// 1.60934400 km https://www.wolframalpha.com/input/?i=convert+1+mile+to+m
        } else {
			$retval[0] = "1 inch = ".round(2.54, $rounding)." centi"._('meter');
            $retval[1] = "1 foot = ".round(0.3048, $rounding)._('meter');
            $retval[2] = "1 yard = ".round(0.9144, $rounding)._('meter');
            $retval[3] = "1 mile = ".round(1.60934400, $rounding)." kilo"._('meter');
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 cm = ".round(0.393700787, $rounding)." in";    // 393.700787 mils https://www.wolframalpha.com/input/?i=convert+1+centimeter+to+inch
            $retval[1] = "1 m = ".round(3.28083990, $rounding)." ft"; // 3.28083990 feet https://www.wolframalpha.com/input/?i=convert+1+meter+to+inch
            $retval[2] = "1 m = ".round(1.0936133, $rounding)." yd";  // 3.28083990 feet divided by 3
            $retval[3] = "1 km = ".round(0.621371, $rounding)." mi";   // 621371 miles https://www.wolframalpha.com/input/?i=convert+1000000+kilometer+to+miles
        } else {
			$retval[0] = "1 centi"._('meter')." = ".round(0.393700787, $rounding)." inch";
            $retval[1] = "1 "._('meter')." = ".round(3.28083990, $rounding)." feet";
            $retval[2] = "1 "._('meter')." = ".round(1.0936133, $rounding)." yard";
            $retval[3] = "1 kilo"._('meter')." = ".round(0.621371, $rounding)." mile";
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
            $retval[2] = "1 ".conversionUnits2ScreenReader("daL",1,"n")." = 10 L";
            $retval[3] = "1 L = 10 dL";
            $retval[4] = "1 L = 100 cL";
			$retval[5] = "1 L = 1000 mL";
        } else {
            $retval[0] = "1 kilo" . _('liter') . " = 1000 " . _('Liter');
            $retval[1] = "1 hecto" . _('liter') . " = 100 "._('Liter');
            $retval[2] = "1 "._('deca'). _('liter') . " = 10 "._('Liter');
            $retval[3] = "1 " . _('Liter'). " = 10 deci" . _('liter');
            $retval[4] = "1 " . _('Liter')." = 100 centi" . _('liter');
            $retval[5] = "1 " . _('Liter')." = 1000 milli" . _('liter');
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 fl oz = ".round(0.0295735296, $rounding)." L";
            $retval[1] = "1 C = ".round(0.236588236, $rounding)." L";
            $retval[2] = "1 pt = ".round(0.473176473, $rounding)." L";
            $retval[3] = "1 qt = ".round(0.946352946, $rounding)." L";
			$retval[4] = "1 gal = ".round(3.78541178, $rounding)." L";
        } else {
			$retval[0] = "1 fluid ounces = ".round(0.0295735296, $rounding)._('Liter');  // 29.5735296 mL  https://www.wolframalpha.com/input/?i=convert+1+fluid+ounce+to+liters
            $retval[1] = "1 cup = ".round(0.236588236, $rounding)._('Liter');  // 236.588236 mL  https://www.wolframalpha.com/input/?i=convert+1+cup+to+liters
            $retval[2] = "1 pint = ".round(0.473176473, $rounding)._('Liter');  // 473.176473 mL  https://www.wolframalpha.com/input/?i=convert+1+pint+to+liters
            $retval[3] = "1 quart = ".round(0.946352946, $rounding)._('Liter');   // 946.352946 mL https://www.wolframalpha.com/input/?i=convert+1+quart+to+liters
			$retval[4] = "1 gallon = ".round(3.78541178, $rounding)._('Liter');  // 3.78541178 L https://www.wolframalpha.com/input/?i=convert+1+gallon+to+milliliters
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 L = ".round(33.8140227, $rounding)." fl oz";  // 33.8140227 fl oz (fluid ounces)  https://www.wolframalpha.com/input/?i=convert+1+liter+to+pints
            $retval[1] = "1 L = ".round(4.22675284, $rounding)." C"; //  2.11337642 pints *2
            $retval[2] = "1 L = ".round(2.11337642, $rounding)." pt";    // 2.11337642 pints   https://www.wolframalpha.com/input/?i=convert+1+liter+to+fluid+ounces
            $retval[3] = "1 L = ".round(1.05668821, $rounding)." qt";    // 1.05668821 quarts
			$retval[4] = "1 L = ".round(0.264172052, $rounding)." gal";  // 0.264172052 gallons
        } else {
			$retval[0] = "1 "._('Liter')." = ".round(33.8140227, $rounding)." fluid ounces";
            $retval[1] = "1 "._('Liter')." = ".round(4.22675284, $rounding)." Cup";
            $retval[2] = "1 "._('Liter')." = ".round(2.11337642, $rounding)." pint";
            $retval[3] = "1 "._('Liter')." = ".round(1.05668821, $rounding)." quart";
			$retval[4] = "1 "._('Liter')." = ".round(0.264172052, $rounding)." gallon";
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
//      NOTE: This ignores the fact that the metric system uses mass and the Americam system uses a force for weight.
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

    $retval = array();

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
            $retval[2] = "1 ".conversionUnits2ScreenReader("dag",1,"n")." = 10 g";
            $retval[3] = "1 g = 10 dg";
            $retval[4] = "1 g = 100 cg";
			$retval[5] = "1 g = 1000 mg";
			$retval[6] = "1 Tonne = 1000 kg";
        } else {
            $retval[0] = "1 kilo"._('gram')." = 1000 "._('gram');
            $retval[1] = "1 hecto"._('gram')." = 100 "._('gram');
            $retval[2] = "1 "._('deca')._('gram')." = 10 "._('gram');
            $retval[3] = "1 "._('gram')." = 10 deci"._('gram');
            $retval[4] = "1 "._('gram')." = 100 centi"._('gram');
            $retval[5] = "1 "._('gram')." = 1000 milli"._('gram');
			$retval[6] = "1 Metric Ton = 1000 kilo"._('gram');
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 oz = ".round(28.3495231, $rounding)." g";    // 0.0283495231 kg https://www.wolframalpha.com/input/?i=convert+1+ounce+to+gram
            $retval[1] = "1 lbs = ".round(0.453592370, $rounding)." kg"; // 0.453592370 kg https://www.wolframalpha.com/input/?i=convert+1+pound+to+gram
        } else {
			$retval[0] = "1 ounces = ".round(28.3495231, $rounding)._('gram');
            $retval[1] = "1 pound = ".round(0.453592370, $rounding)." kilo"._('gram');;
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 g = ".round(0.035274, $rounding)." oz";
            $retval[1] = "1 kg = ".round(2.20462, $rounding)." lbs";
        } else {
			$retval[0] = "1 "._('gram')." = ".round(0.035274, $rounding)." ounces";
            $retval[1] = "1 kilo"._('gram')." = ".round(2.20462, $rounding)." pound";
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
//            1 = use Full name (feet squared)
//            2 = use Full name (square feet)
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
	$tick = $args[3];

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 ".conversionUnits2ScreenReader("ft",2,$tick)." = 144 ".conversionUnits2ScreenReader("in",2,$tick);
            $retval[1] = "1 ".conversionUnits2ScreenReader("yd",2,$tick)." = 9 ".conversionUnits2ScreenReader("ft",2,$tick);
            $retval[2] = "1 acre = 43,560 ".conversionUnits2ScreenReader("ft",2,$tick);
            $retval[3] = "1 ".conversionUnits2ScreenReader("mi",2,$tick)." = 640 acre";
        } else {
            $retval[0] = "1 feet squared = 144 inches squared";
            $retval[1] = "1 yard squared = 9 feet squared";
			$retval[2] = "1 acre = 43,560 feet squared ";
            $retval[3] = "1 mile squared  = 640 acre";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 ".conversionUnits2ScreenReader("km",2,$tick)." = 100 ".conversionUnits2ScreenReader("hm",2,$tick);
            $retval[1] = "1 ".conversionUnits2ScreenReader("hm",2,$tick)." = 100 ".conversionUnits2ScreenReader("dam",2,$tick);
            $retval[2] = "1 ".conversionUnits2ScreenReader("dam",2,$tick)." = 100 ".conversionUnits2ScreenReader("m",2,$tick);
            $retval[3] = "1 ".conversionUnits2ScreenReader("m",2,$tick)." = 100 ".conversionUnits2ScreenReader("dm",2,$tick);
            $retval[4] = "1 ".conversionUnits2ScreenReader("dm",2,$tick)." = 100 ".conversionUnits2ScreenReader("cm",2,$tick);
			$retval[5] = "1 ".conversionUnits2ScreenReader("cm",2,$tick)." = 100 ".conversionUnits2ScreenReader("mm",2,$tick);
			$retval[6] = "1 a = 100 ".conversionUnits2ScreenReader("m",2,$tick);
			$retval[7] = "1 ha = 100 a";
        } elseif($fullname==1) {
			$retval[0] = "1 Kilo"._('meter')." squared = 100 Hecto"._('meter')." squared";
            $retval[1] = "1 Hecto"._('meter')."  squared = 100 "._('Deca')._('meter')." squared";
            $retval[2] = "1 "._('Deca')._('meter')." squared = 100 "._('Meter')." squared";
            $retval[3] = "1 "._('Meter')." squared = 100 Deci"._('meter')." squared";
            $retval[4] = "1 Deci"._('meter')." squared = 100 Centi"._('meter')." squared";
			$retval[5] = "1 Centi"._('meter')." squared = 100 Milli"._('meter')." squared";
			$retval[6] = "1 Ares = 100 "._('meter')." squared";
			$retval[7] = "1 Hectares = 100 Ares";
        } else  {
			$retval[0] = "1 Square kilo"._('meter')." = 100 Square hecto"._('meter');
            $retval[1] = "1 Square hecto"._('meter')." = 100 Square "._('deca')._('meter');
            $retval[2] = "1 Square "._('deca')._('meter')." = 100 Square "._('meter');
            $retval[3] = "1 Square "._('meter')." = 100 Square deci"._('meter');
            $retval[4] = "1 Square deci"._('meter')." = 100 Square centi"._('meter');
			$retval[5] = "1 Square centi"._('meter')." = 100 Square milli"._('meter');
			$retval[6] = "1 Ares = 100 Square "._('meter')." ";
			$retval[7] = "1 Hectares = 100 Ares";
        }
	} elseif($system=="AM"){
        //6.45160000 cm^2 https://www.wolframalpha.com/input/?i=convert+1+square+inch+to+mm+squared
        $CF = round(6.4516, $rounding);
		if($fullname==0) {
			$retval[0] = "1 ".conversionUnits2ScreenReader("in",2,$tick)." = $CF ".conversionUnits2ScreenReader("cm",2,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Inch squared = $CF Centi"._('meter')." squared";
        } else {
			$retval[0] = "1 Square inch = $CF Square centi"._('meter');
        }
	} elseif($system=="MA"){
        // 1.19599005 yd^2 https://www.wolframalpha.com/input/?i=convert+1+square+meter+to+square+feet
        // https://www.wolframalpha.com/input/?i=convert+1+hectares+to+square+feet
        $CF0 = round(1.19599005, $rounding);
        $CF1 = round(2.471, $rounding);
		if($fullname==0) {
			$retval[0] = "1 ".conversionUnits2ScreenReader("m",2,$tick)." = $CF0 ".conversionUnits2ScreenReader("yd",2,$tick);
            $retval[1] = "1 ha = $CF1 acres";
        } elseif($fullname==1) {
			$retval[0] = "1 "._('Meter')." squared = $CF0 Yard squared";
            $retval[1] = "1 hectares = $CF1 acres";
        } else {
			$retval[0] = "1 Square"._('Meter')." = $CF0 Square yard";
            $retval[1] = "1 hectares = $CF1 acres";
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
	$tick = $args[3];

    $retval = array();

	if($system=="A"){
		if($fullname==0) {
            $retval[0] = "1 ".conversionUnits2ScreenReader("ft",3,$tick)." = 1,728 ".conversionUnits2ScreenReader("in",3,$tick);
            $retval[1] = "1 ".conversionUnits2ScreenReader("yd",3,$tick)." = 27 ".conversionUnits2ScreenReader("ft",3,$tick);
        } elseif($fullname==1) {
            $retval[0] = "1 feet cubed = 1,728 inches cubed";
            $retval[1] = "1 yard cubed = 27 feet cubed";
        } elseif($fullname==2) {
            $retval[0] = "1 cubic feet = 1,728 cubic inches";
            $retval[1] = "1 cubic yard = 27 cubic feet";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 ".conversionUnits2ScreenReader("km",3,$tick)." = 1000 ".conversionUnits2ScreenReader("hm",3,$tick);
            $retval[1] = "1 ".conversionUnits2ScreenReader("hm",3,$tick)." = 1000 ".conversionUnits2ScreenReader("dam",3,$tick);
            $retval[2] = "1 ".conversionUnits2ScreenReader("dam",3,$tick)." = 1000 ".conversionUnits2ScreenReader("m",3,$tick);
            $retval[3] = "1 ".conversionUnits2ScreenReader("m",3,$tick)." = 1000 ".conversionUnits2ScreenReader("dm",3,$tick);
            $retval[4] = "1 ".conversionUnits2ScreenReader("dm",3,$tick)." = 1000 ".conversionUnits2ScreenReader("cm",3,$tick);
			$retval[5] = "1 ".conversionUnits2ScreenReader("cm",3,$tick)." = 1000 ".conversionUnits2ScreenReader("mm",3,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Kilo"._('meter')." cubed = 1000 Hecto"._('meter')."  cubed";
            $retval[1] = "1 Hecto"._('meter')." cubed = 1000 "._('Deca')._('meter')." cubed";
            $retval[2] = "1 "._('Deca')._('meter')." cubed = 1000 "._('Meter')." cubed";
            $retval[3] = "1 "._('Meter')." cubed = 1000 Deci"._('meter')." cubed";
            $retval[4] = "1 Deci"._('meter')." cubed = 1000 Centi"._('meter')." cubed";
			$retval[5] = "1 Centi"._('meter')." cubed = 1000 Milli"._('meter')." cubed";
        } else  {
			$retval[0] = "1 Cubic kilo"._('meter')." = 1000 Cubic hecto"._('meter');
            $retval[1] = "1 Cubic hecto"._('meter')." cubed = 1000 Cubic "._('deca')._('meter');
            $retval[2] = "1 Cubic "._('deca')._('meter')." cubed = 1000 Cubic "._('meter');
            $retval[3] = "1 Cubic "._('meter')." cubed = 1000 Cubic deci"._('meter');
            $retval[4] = "1 Cubic deci"._('meter')." cubed = 1000 Cubic centi"._('meter');
			$retval[5] = "1 Cubic centi"._('meter')." cubed = 1000 Cubic milli"._('meter');
        }
	} elseif($system=="AM"){
        // 0.0163870640 L https://www.wolframalpha.com/input/?i=convert+1+cubic+inch+to+ml
        $CF = round(16.3870640, $rounding);
		if($fullname==0) {
			$retval[0] = "1 ".conversionUnits2ScreenReader("in",3,$tick)." = $CF mL";
        } elseif($fullname==1) {
			$retval[0] = "1 Inch cubed = $CF Milli"._('liter');
        } else {
			$retval[0] = "1 Cubic inch = $CF Milli"._('Liter');
        }
	} elseif($system=="MA"){
        // 61.0237441 in^3  https://www.wolframalpha.com/input/?i=convert+1+liter+to+cubic+feet
        $CF = round(61.0237441, $rounding);
		if($fullname==0) {
			$retval[0] = "1 L = $CF ".conversionUnits2ScreenReader("in",3,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 "._('Liter')." = $CF Inches cubed";
        } else {
			$retval[0] = "1 "._('Liter')." = $CF Cubic inches";
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

// 2021-02-04 ver 9 - added conversionLiquid, find typo in conversionArea
// 2021-02-04 ver 8 - Added cellpadding to conversionDisplay2HTML, updated conversion factors from www.wolframalpha.com
//                    fixed missing screen reader on missed ^ conversion factors.
// 2021-02-02 ver 7 - working on the screen reader added
//                       <span aria-hidden=true></span><span class=\"sr-only\"></span>
// 2021-02-02 ver 6 - forgot to include conversionFormulaTempature in the allowed Macros and some typo fixed.
// 2021-02-02 ver 5 - update spellings and function naming conventions
// 2021-02-02 ver 4 - updated ConversionDisplay to allow for multiple sets of inputs
//                    created ConversionDisplay2HTML
// 2021-01-31 ver 3 - added Cubic inches/Inches cubed option
// 2021-01-31 ver 2 - added ConversionDisplay
// 2021-01-31 ver 1 - initial release

?>