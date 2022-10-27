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
	return 26.3;
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
    "conversion_extract_column_array", "conversionArea2", "conversionCapacity2", "conversionLength2",
    "conversionLiquid2", "conversionTime2", "conversionVolume2", "conversionWeight2");

// internal only  ----------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------

//function verifyCancel($input) {
////    s - skip (default)
////    n - around the number
////    u - around the units
////    b - around
//    $Retval = "s";

//    if(!is_null($input)) {
//        if(strtolower($input)=="n"){
//            $Retval = "n";
//        } elseif (strtolower($input)=="u"){
//            $Retval = "u";
//        }elseif (strtolower($input)=="b"){
//            $Retval = "b";
//        }
//    }

//    return $Retval;
//}

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
    $fullname=0;

	if(!is_null($input)) {
		$fullname = $input;
		if ($fullname<0) { return 0; }
        if ($fullname>4) { return 4; }
	}

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

    if(!is_null($input)) {
        if((strtolower($input)=="y")||($input=="`")) { return "`"; }
	}

	return "";
}

function verifyEqualSign($input) {
	if(!is_null($input)) {
        if($input=="=" || $input=="") {
            return "=";
        }
        if($input=="~" || $input=="~~") {
            return "~~";
        }
        return  "&#8776;"; // HTML approximately equal
	}
	return "=";
}

function verifyString($input) {
	if(!is_null($input)) {
		$retval =  $input;
	}
	else { $retval = ""; }

	return $retval;
}

// Length ------------------------------------------------------------------------------------------
function get_unit_lengths() {
    $unit = array();

    $unit["Inches"] = _("Inches");
    $unit["Inch"] = _("Inch");
    $unit["inches"] = _("inches");
    $unit["inch"] = _("inch");

    $unit["Feet"] = _("Feet");
    $unit["Foot"] = _("Foot");
    $unit["feet"] = _("feet");
    $unit["foot"] = _("foot");

    $unit["Yards"] = _("Yards");
    $unit["Yard"] = _("Yard");
    $unit["yards"] = _("yards");
    $unit["yard"] = _("yard");

    $unit["Miles"] = _("Miles");
    $unit["Mile"] = _("Mile");
    $unit["miles"] = _("miles");
    $unit["mile"] = _("mile");

    //metric
    $unit["Kilometer"] = _("Kilometer");
    $unit["Hectometer"] = _("Hectometer");
    $unit["Dekameter"] = _("Dekameter");
    $unit["Meter"] = _("Meter");
    $unit["Decimeter"] = _("Decimeter");
    $unit["Centimeter"] = _("Centimeter");
    $unit["Millimeter"] = _("Millimeter");


    return $unit;
}
function get_unit_length_abbreviations() {
    $unitabbr = array();

    $unitabbr["Inches"] = _("in");
    $unitabbr["Inch"] = _("in");
    $unitabbr["inches"] = _("in");
    $unitabbr["inch"] = _("in");

    $unitabbr["Feet"] = _("ft");
    $unitabbr["Foot"] = _("ft");
    $unitabbr["feet"] = _("ft");
    $unitabbr["foot"] = _("ft");

    $unitabbr["Yards"] = _("yds");
    $unitabbr["Yard"] = _("yd");
    $unitabbr["yards"] = _("yds");
    $unitabbr["yard"] = _("yd");

    $unitabbr["Miles"] = _("mi");
    $unitabbr["Mile"] = _("mi");
    $unitabbr["miles"] = _("mi");
    $unitabbr["mile"] = _("mi");

    //metric
    $unitabbr["Kilometer"] = _("km");
    $unitabbr["Kilometers"] = _("km");
    $unitabbr["Hectometer"] = _("hm");
    $unitabbr["Hectometers"] = _("hm");
    $unitabbr["Dekameter"] = _("dam");
    $unitabbr["Dekameters"] = _("dam");
    $unitabbr["Meter"] = _("m");
    $unitabbr["Meters"] = _("m");
    $unitabbr["Decimeter"] = _("dm");
    $unitabbr["Decimeters"] = _("dm");
    $unitabbr["Centimeter"] = _("cm");
    $unitabbr["Centimeters"] = _("cm");
    $unitabbr["Millimeter"] = _("mm");
    $unitabbr["Millimeters"] = _("mm");

    $unitabbr["Ares"] = _("a");
    $unitabbr["ares"] = _("a");
    $unitabbr["Centiar"] = _("ca");
    $unitabbr["centiar"] = _("ca");
    $unitabbr["Decare"] = _("da");
    $unitabbr["decare"] = _("da");
    $unitabbr["Hectare"] = _("ha");
    $unitabbr["hectare"] = _("ha");


    return $unitabbr;
}

// Capacity
function get_unit_capacities() {
    $unit = array();

    $unit["Fluid ounces"] = _("Fluid ounces");
    $unit["fluid ounces"] = _("fluid ounces");
    $unit["Fluid ounce"] = _("Fluid ounce");
    $unit["fluid ounce"] = _("fluid ounce");

    $unit["Cups"] = _("Cups");
    $unit["cups"] = _("cups");
    $unit["Cup"] = _("Cup");
    $unit["cup"] = _("cup");

    $unit["Pints"] = _("Pints");
    $unit["pints"] = _("pints");
    $unit["Pint"] = _("Pint");
    $unit["pint"] = _("pint");

    $unit["Quarts"] = _("Quarts");
    $unit["quarts"] = _("quarts");
    $unit["Quart"] = _("Quart");
    $unit["quart"] = _("quart");

    $unit["Gallons"] = _("Gallons");
    $unit["gallons"] = _("gallons");
    $unit["Gallon"] = _("Gallon");
    $unit["gallon"] = _("gallon");

    //metric
    $unit["Kiloliter"] = _("Kiloliter");
    $unit["Hectoliter"] = _("Hectoliter");
    $unit["Dekaliter"] = _("Dekaliter");
    $unit["Liter"] = _("Liter");
    $unit["Deciliter"] = _("Deciliter");
    $unit["Centiliter"] = _("Centiliter");
    $unit["Milliliter"] = _("Milliliter");

    return $unit;
}
function get_unit_capacity_abbreviations() {
    $unitabbr = array();

    $unitabbr["Fluid ounces"] = _("fl. oz");
    $unitabbr["fluid ounces"] = _("fl. oz");
    $unitabbr["Fluid ounce"] = _("fl. oz");
    $unitabbr["fluid ounce"] = _("fl. oz");

    $unitabbr["Cups"] = _("c");
    $unitabbr["cups"] = _("c");
    $unitabbr["Cup"] = _("c");
    $unitabbr["cup"] = _("c");

    $unitabbr["Pints"] = _("pt");
    $unitabbr["pints"] = _("pt");
    $unitabbr["Pint"] = _("pt");
    $unitabbr["pint"] = _("pt");

    $unitabbr["Quarts"] = _("qt");
    $unitabbr["quarts"] = _("qt");
    $unitabbr["Quart"] = _("qt");
    $unitabbr["quart"] = _("qt");

    $unitabbr["Gallons"] = _("gal");
    $unitabbr["gallons"] = _("gal");
    $unitabbr["Gallon"] = _("gal");
    $unitabbr["gallon"] = _("gal");

    //metric
    $unitabbr["Kiloliter"] = _("kL");
    $unitabbr["Hectoliter"] = _("hL");
    $unitabbr["Dekaliter"] = _("daL");
    $unitabbr["Liter"] = _("L");
    $unitabbr["Deciliter"] = _("dL");
    $unitabbr["Centiliter"] = _("cL");
    $unitabbr["Milliliter"] = _("mL");

    return $unitabbr;
}

function get_unit_liquids() {
    $unit = array();

    $unit["British Barrel"] = _("British Barrel");
    $unit["Hogshead"] = _("Hogshead");
    $unit["Barrique"] = _("Barrique");
    $unit["Puncheon"] = _("Puncheon");
    $unit["Butt"] = _("Butt");
    $unit["Pipe"] = _("Pipe");
    $unit["Tun"] = _("Tun");
    $unit["Gallons"] = _("Gallons");
    $unit["gallons"] = _("gallons");
    $unit["Gallon"] = _("Gallon");
    $unit["gallon"] = _("gallon");

    return $unit;
}
function get_unit_liquid_abbreviations() {
    $unitabbr = array();

    $unitabbr["Gallons"] = _("Gallons");
    $unitabbr["gallons"] = _("gallons");
    $unitabbr["Gallon"] = _("Gallon");
    $unitabbr["gallon"] = _("gallon");

    return $unitabbr;
}

// Formula
function get_unit_formulanames() {
    $unit = array();

    $unit["Area"] = _("Area");
    $unit["base"] = _("base");
    $unit["Circumference"] = _("Circumference");
    $unit["Diameter"] = _("Diameter");
    $unit["Height"] = _("Height");
    $unit["Length"] = _("Length");
    $unit["Perimeter"] = _("Perimeter");
    $unit["Radius"] = _("Radius");
    $unit["Surface Area"] = _("Surface Area");
    $unit["Side"] = _("Side");
    $unit["side"] = _("side");
    $unit["Volume"] = _("Volume");
    $unit["Width"] = _("Width");

    // Temperature
    $unit["Celsius"] = _("Celsius");
    $unit["Fahrenheit"] = _("Fahrenheit");
    $unit["Kelvin"] = _("Kelvin");

    return $unit;
}

// Weight
function get_unit_weights() {
    $unit = array();

    $unit["Ounces"] = _("Ounces");
    $unit["ounces"] = _("ounces");
    $unit["Ounce"] = _("Ounce");
    $unit["ounce"] = _("ounce");

    $unit["Pounds"] = _("Pounds");
    $unit["pounds"] = _("pounds");
    $unit["Pound"] = _("Pound");
    $unit["pound"] = _("pound");

    $unit["Tons"] = _("Tons");
    $unit["Ton"] = _("Ton");
    $unit["tons"] = _("tons");
    $unit["ton"] = _("ton");

    // Metric
    $unit["Kilogram"] = _("Kilogram");
    $unit["Hectogram"] = _("Hectogram");
    $unit["Dekagram"] = _("Dekagram");
    $unit["Gram"] = _("Gram");
    $unit["Decigram"] = _("Decigram");
    $unit["Centigram"] = _("Centigram");
    $unit["Milligram"] = _("Milligram");
    //$unit["Metric Ton"] = _("Metric Ton");
    $unit["Metric Ton"] = _("Tonne");

    return $unit;
}
function get_unit_weight_abbreviations() {
    $unitabbr = array();

    $unitabbr["Ounces"] = _("oz");
    $unitabbr["ounces"] = _("oz");

    $unitabbr["Pounds"] = _("lbs");
    $unitabbr["pounds"] = _("lbs");
    $unitabbr["Pound"] = _("lb");
    $unitabbr["pound"] = _("lb");

    $unitabbr["Tons"] = _("T");
    $unitabbr["Ton"] = _("T");
    $unitabbr["tons"] = _("T");
    $unitabbr["ton"] = _("T");

    // Metric
    $unitabbr["Kilogram"] = _("kg");
    $unitabbr["Hectogram"] = _("hg");
    $unitabbr["Dekagram"] = _("dag");
    $unitabbr["Gram"] = _("g");
    $unitabbr["Decigram"] = _("dg");
    $unitabbr["Centigram"] = _("cg");
    $unitabbr["Milligram"] = _("mg");
    //$unitabbr["Metric Ton"] = _("t");
    $unitabbr["Metric Ton"] = _("Tonne");

    return $unitabbr;
}

// Area  https://gotthisnow.com/is-feet-squared-the-same-as-square-feet
function get_unit_areas() {
    $unit = array();

    $unit["Square inches"] = _("Square inches");
    $unit["square inches"] = _("square inches");
    $unit["Square inch"] = _("Square inch");
    $unit["square inch"] = _("square inch");

    $unit["Inches squared"] = _("Square inches");
    $unit["inches squared"] = _("square inches");
    $unit["Inch squared"] = _("Square inch");
    $unit["inch squared"] = _("square inch");

    $unit["Square feet"] = _("Square feet");
    $unit["square feet"] = _("square feet");
    $unit["Feet squared"] = _("Square feet");
    $unit["feet squared"] = _("square feet");

    $unit["Square yard"] = _("Square yard");
    $unit["square yard"] = _("square yard");
    $unit["Yard squared"] = _("Square yard");
    $unit["yard squared"] = _("square yard");

    $unit["acre"] = _("acre");

    $unit["Square mile"] = _("Square mile");
    $unit["square mile"] = _("square mile");
    $unit["Mile squared"] = _("Square mile");
    $unit["mile squared"] = _("square mile");

    // metric
    $unit["Kilometer squared"] = _("Kilometer squared");
    $unit["Square kilometer"] = _("Square kilometer");

    $unit["Hectometer squared"] = _("Hectometer squared");
    $unit["Square hectometer"] = _("Square hectometer");

    $unit["Dekameter squared"] = _("Dekameter squared");
    $unit["Square dekameter"] = _("Square dekameter");

    $unit["Meter squared"] = _("Meter squared");
    $unit["Square meter"] = _("Square meter");

    $unit["Decimeter squared"] = _("Decimeter squared");
    $unit["Square decimeter"] = _("Square decimeter");

    $unit["Centimeter squared"] = _("Centimeter squared");
    $unit["Square centimeter"] = _("Square centimeter");

    $unit["Millimeter squared"] = _("Millimeter squared");
    $unit["Square millimeter"] = _("Square millimeter");

    $unit["Centiares"] = _("Centiares");
    $unit["centiares"] = _("centiares");
    $unit["Centiare"] = _("Centiare");
    $unit["centiare"] = _("centiare");

    $unit["Ares"] = _("Ares");
    $unit["ares"] = _("ares");
    $unit["Are"] = _("Are");
    $unit["are"] = _("are");

    $unit["Decares"] = _("Decares");
    $unit["decares"] = _("decares");
    $unit["Decare"] = _("Decare");
    $unit["decare"] = _("decare");

    $unit["Hectares"] = _("Hectares");
    $unit["hectares"] = _("hectares");
    $unit["Hectare"] = _("Hectare");
    $unit["hectare"] = _("hectare");

    return $unit;
}
function get_unit_area_abbreviations() {
    $unitabbr = array();


    $unitabbr["Square inches"] = _("in");
    $unitabbr["square inches"] = _("in");
    $unitabbr["Inches squared"] = _("in");
    $unitabbr["inches squared"] = _("in");

    $unitabbr["Square feet"] = _("ft");
    $unitabbr["square feet"] = _("ft");
    $unitabbr["Feet squared"] = _("ft");
    $unitabbr["feet squared"] = _("ft");

    $unitabbr["Square yard"] = _("yd");
    $unitabbr["square yard"] = _("yd");
    $unitabbr["Yard squared"] = _("yd");
    $unitabbr["yard squared"] = _("yd");

    $unitabbr["acre"] = _("acre");

    $unitabbr["Square mile"] = _("mi");
    $unitabbr["square mile"] = _("mi");
    $unitabbr["Mile squared"] = _("mi");
    $unitabbr["mile squared"] = _("mi");

    // metric

    //$unitabbr["acre"] = _("ac");  //https://en.wikipedia.org/wiki/Acre
    //$unitabbr["acres"] = _("ac");
    $unitabbr["acre"] = _("acre");
    $unitabbr["acres"] = _("acres");

    //metric

    $unitabbr["Centiares"] = _("ca");
    $unitabbr["centiares"] = _("ca");
    $unitabbr["Centiare"] = _("ca");
    $unitabbr["centiare"] = _("ca");

    $unitabbr["Ares"] = _("a");
    $unitabbr["ares"] = _("a");
    $unitabbr["Are"] = _("a");
    $unitabbr["are"] = _("a");

    $unitabbr["Decares"] = _("da");
    $unitabbr["decares"] = _("da");
    $unitabbr["Decare"] = _("da");
    $unitabbr["decare"] = _("da");

    $unitabbr["Hectares"] = _("ha");
    $unitabbr["hectares"] = _("ha");
    $unitabbr["Hectare"] = _("ha");
    $unitabbr["hectare"] = _("ha");


    return $unitabbr;
}

// Volume
function get_unit_volumes() {
    $unit = array();

    $unit["Cubic inches"] = _("Cubic inches");
    $unit["cubic inches"] = _("Cubic inches");
    $unit["Inches cubed"]  = _("Inches cubed");
    $unit["inches cubed"]  = _("inches cubed");

    $unit["Cubic feet"]  = _("Cubic feet");
    $unit["cubic feet"]  = _("Cubic feet");
    $unit["Cubic foot"]  = _("Cubic foot");
    $unit["cubic foot"]  = _("Cubic foot");

    $unit["Feet cubed"] = _("Feet cubed");
    $unit["feet cubed"] = _("feet cubed");
    $unit["Foot cubed"] = _("Foot cubed");
    $unit["foot cubed"] = _("foot cubed");


    $unit["Cubic yard"]  = _("Cubic yard");
    $unit["cubic yard"]  = _("Cubic yard");
    $unit["Yard cubed"]  = _("Yard cubed");
    $unit["yard cubed"]  = _("yard cubed");

    // Metric
    $unit["Liter"] = _("Liter");

    $unit["Millimeter cubed"] = _("Millimeter cubed");
    $unit["Cubic millimeter"] = _("Cubic millimeter");

    $unit["Centimeter cubed"] = _("Centimeter cubed");
    $unit["Cubic centimeter"] = _("Cubic centimeter");

    $unit["Decimeter cubed"] = _("Decimeter cubed");
    $unit["Cubic decimeter"] = _("Cubic decimeter");

    $unit["Meter cubed"] = _("Meter cubed");
    $unit["Cubic meter"] = _("Cubic meter");

    $unit["Dekameter cubed"] = _("Dekameter cubed");
    $unit["Cubic dekameter"] = _("Cubic dekameter");

    $unit["Hectometer cubed"] = _("Hectometer cubed");
    $unit["Cubic hectometer"] = _("Cubic hectometer");

    $unit["Kilometer cubed"] = _("Kilometer cubed");
    $unit["Cubic kilometer"] = _("Cubic kilometer");

    return $unit;
}

// Time units
function get_unit_times() {
    $unit = array();

    $unit["Seconds"] = _("Seconds");
    $unit["Second"] = _("Second");
    $unit["seconds"] = _("seconds");
    $unit["second"] = _("second");

    $unit["Minutes"] = _("Minutes");
    $unit["Minute"] = _("Minute");
    $unit["minutes"] = _("minutes");
    $unit["minute"] = _("minute");

    $unit["Hours"] = _("Hours");
    $unit["Hour"] = _("Hour");
    $unit["hours"] = _("hours");
    $unit["hour"] = _("hour");

    $unit["Days"] = _("Days");
    $unit["Day"] = _("Day");
    $unit["days"] = _("days");
    $unit["day"] = _("day");

    $unit["Years"] = _("Years");
    $unit["Year"] = _("Year");
    $unit["years"] = _("years");
    $unit["year"] = _("year");

    $unit["Decade"] = _("Decade");
    $unit["decade"] = _("decade");

    $unit["Centuries"] = _("Centuries");
    $unit["Century"] = _("Century");
    $unit["century"] = _("century");

    return $unit;
}
function get_unit_time_abbreviations() {
    $unitabbr = array();

    $unitabbr["Seconds"] = _("sec");
    $unitabbr["Second"] = _("sec");
    $unitabbr["seconds"] = _("sec");
    $unitabbr["second"] = _("sec");

    $unitabbr["Minutes"] = _("min");
    $unitabbr["Minute"] = _("min");
    $unitabbr["minutes"] = _("min");
    $unitabbr["minute"] = _("min");

    $unitabbr["Hours"] = _("hrs");
    $unitabbr["Hour"] = _("hr");
    $unitabbr["hours"] = _("hrs");
    $unitabbr["hour"] = _("hr");

    $unitabbr["Days"] = _("d");
    $unitabbr["Day"] = _("d");
    $unitabbr["days"] = _("d");
    $unitabbr["day"] = _("d");

    $unitabbr["Years"] = _("yrs");
    $unitabbr["Year"] = _("yr");
    $unitabbr["years"] = _("yrs");
    $unitabbr["year"] = _("yr");

    $unitabbr["Decade"] = _("dec");
    $unitabbr["decade"] = _("dec");

    $unitabbr["Centuries"] = _("c");
    $unitabbr["Century"] = _("c");
    $unitabbr["century"] = _("c");

    return $unitabbr;
}

// Metric Prefix
function get_metric_prefixs() {
    $unit = array();

    // Metric - larger
    $unit["Kilo"] = _("Kilo");
    $unit["Hecto"] = _("Hecto");
    $unit["Deka"] = _("Deka");

    //Base units
    $unit["Gram"] = _("Gram");
    $unit["Liter"] = _("Liter");
    $unit["Meter"] = _("Meter");

    // Metric - smaller
    $unit["Deci"] = _("Deci");
    $unit["Centi"] = _("Centi");
    $unit["Milli"] = _("Milli");


    return $unit;
}
function get_metric_prefix_abbreviations() {
    $unitabbr = array();

    // Metric - larger
    $unitabbr["Kilo"] = _("k");
    $unitabbr["Hecto"] = _("h");
    $unitabbr["Deka"] = _("da");

    //Base units
    $unitabbr["Gram"] = _("g");
    $unitabbr["Liter"] = _("L");
    $unitabbr["Meter"] = _("m");

    // Metric - smaller
    $unitabbr["Deci"] = _("d");
    $unitabbr["Centi"] = _("c");
    $unitabbr["Milli"] = _("m");


    return $unitabbr;
}



// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------

function nothingtodisplay() {
    return _("Nothing to display - no system type supplied")."<br/>\r\n";
}
function isnotvalid() {
    return _(" is not a valid type.");
}
function isnotvalidC() {
    return _(" is not a valid type. The system type is C (Casks).");
}
function isnotvalidAMT() {
    return _(" is not a valid type. The system type is A (American), M (Metric), or T (Time).");
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
//
//           0 = Kilometer squared/Kilometer cube
//           1 = Square kilometer/Cubic kilometer
//
//           3 = add extra conversion factors to metric area Kilometer squared
//           4 = add extra conversion factors to metric area Square kilometer
//
// Fullname: determines the order of the word square/cube in the full name of the words
//           e.g.Inches squared/Square inches
//
// Examples
//
// use conversionAbbreviations("A","Length") returns an array of strings that have american abbreviations of length
function conversionAbbreviations() {

	$args = func_get_args();

    #region Argument verification

	if (count($args)==0) {
		echo nothingtodisplay();
		return "";
	} else {
        $system = strtoupper(substr($args[0], 0, 1));
        if($system!='A' && $system!='M' && $system!='T' ) {
            echo (string)$system.isnotvalidAMT();
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

	#endregion

	if($FirstLetter=="L") {$type="Length";}
	if($FirstLetter=="C") {$type="Capacity";}
	if($FirstLetter=="W") {$type="Weight";}
	if($FirstLetter=="M") {$type="Mass";}
	if($FirstLetter=="A") {$type="Area";}
	if($FirstLetter=="V") {$type="Volume";}

    // make
    if(!in_array($type, array("Length","Capacity","Weight","Area","Volume","Mass"))) {
        $type="Length";
    }

    $retval = array();

	#region American Conversion
    // -------------------------------------------------------------------------------------------------
    if($system=="A"){
        if($type=="Length"){
            $unit = get_unit_lengths();
            $unitabbr = get_unit_length_abbreviations();

            $retval[0] = $unit["Inches"]." = ".$unitabbr["Inches"];
            $retval[1] = $unit["Feet"]." = ".$unitabbr["Feet"];
            $retval[2] = $unit["Yards"]." = ".$unitabbr["Yards"];
            $retval[3] = $unit["Miles"]." = ".$unitabbr["Miles"];
        } elseif($type=="Capacity"){
            $unit = get_unit_capacities();
            $unitabbr = get_unit_capacity_abbreviations();

            $retval[0] = $unit["Fluid ounces"]." = ".$unitabbr["Fluid ounces"];
            $retval[1] = $unit["Cups"]." = ".$unitabbr["Cups"];
            $retval[2] = $unit["Pints"]." = ".$unitabbr["Pints"];
            $retval[3] = $unit["Quarts"]." = ".$unitabbr["Quarts"];
            $retval[4] = $unit["Gallons"]." = ".$unitabbr["Gallons"];
        } elseif(($type=="Weight")||($type=="Mass")){
            $unit = get_unit_weights();
            $unitabbr = get_unit_weight_abbreviations();

            $retval[0] = $unit["Ounces"]." = ".$unitabbr["Ounces"];
            $retval[1] = $unit["Pounds"]." = ".$unitabbr["Pounds"];
            $retval[2] = $unit["Tons"]." = ".$unitabbr["Tons"];
        } elseif($type=="Area"){
            $unit = get_unit_areas();
            $unitabbr = get_unit_length_abbreviations();

            if($fullname==0) {
                $retval[0] = $unit["Inches squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["inch"],2,$tick,"y");
                $retval[1] = $unit["Feet squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["feet"],2,$tick);
                $retval[2] = $unit["Yard squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["yard"],2,$tick);
                $retval[3] = $unit["Mile squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["mile"],2,$tick);
            } else {
                $retval[0] = $unit["Square inches"]." = ".conversionUnits2ScreenReader1("",$unitabbr["inch"],2,$tick,"y");
                $retval[1] = $unit["Square feet"]." = ".conversionUnits2ScreenReader1("",$unitabbr["feet"],2,$tick);
                $retval[2] = $unit["Square yard"]." = ".conversionUnits2ScreenReader1("",$unitabbr["yard"],2,$tick);
                $retval[3] = $unit["Square mile"]." = ".conversionUnits2ScreenReader1("",$unitabbr["mile"],2,$tick);
            }

        } elseif($type=="Volume"){
            $unit = get_unit_volumes();
            $unitabbr = get_unit_length_abbreviations();

            if($fullname==0) {
                $retval[0] = $unit["Inches cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["inch"],3,$tick,"y");
                $retval[1] = $unit["Feet cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["feet"],3,$tick);
                $retval[2] = $unit["Yard cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["yard"],3,$tick);
            } else {
                $retval[0] = $unit["Cubic inches"]." = ".conversionUnits2ScreenReader1("",$unitabbr["inch"],3,$tick,"y");
                $retval[1] = $unit["Cubic feet"]." = ".conversionUnits2ScreenReader1("",$unitabbr["feet"],3,$tick);
                $retval[2] = $unit["Cubic yard"]." = ".conversionUnits2ScreenReader1("",$unitabbr["yard"],3,$tick);
            }
        }

    }
    #endregion

	#region Metric Conversion
    // -------------------------------------------------------------------------------------------------
    if($system=="M"){
        if($type=="Length"){
            $unit = get_unit_lengths();
            $unitabbr = get_unit_length_abbreviations();

            $retval[0] = $unit["Millimeter"]." = ".$unitabbr["Millimeter"];
            $retval[1] = $unit["Centimeter"]." = ".$unitabbr["Centimeter"];
            $retval[2] = $unit["Decimeter"]." = ".$unitabbr["Decimeter"];
            $retval[3] = $unit["Meter"]." = ".$unitabbr["Meter"];
            $retval[4] = $unit["Dekameter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekameter"],1,"n");
            $retval[5] = $unit["Hectometer"]." = ".$unitabbr["Hectometer"];
            $retval[6] = $unit["Kilometer"]." = ".$unitabbr["Kilometer"];
        } elseif($type=="Capacity"){
            $unit = get_unit_capacities();
            $unitabbr = get_unit_capacity_abbreviations();

            $retval[0] = $unit["Milliliter"]." = ".$unitabbr["Milliliter"];
            $retval[1] = $unit["Centiliter"]." = ".$unitabbr["Centiliter"];
            $retval[2] = $unit["Deciliter"]." = ".$unitabbr["Deciliter"];
            $retval[3] = $unit["Liter"]." = ".$unitabbr["Liter"];
            $retval[4] = $unit["Dekaliter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekaliter"],1,"n");
            $retval[5] = $unit["Hectoliter"]." = ".$unitabbr["Hectoliter"];
            $retval[6] = $unit["Kiloliter"]." = ".$unitabbr["Kiloliter"];
        } elseif(($type=="Weight")||($type=="Mass")){
            $unit = get_unit_weights();
            $unitabbr = get_unit_weight_abbreviations();

            $retval[0] = $unit["Milligram"]." = ".$unitabbr["Milligram"];
            $retval[1] = $unit["Centigram"]." = ".$unitabbr["Centigram"];
            $retval[2] = $unit["Decigram"]." = ".$unitabbr["Decigram"];
            $retval[3] = $unit["Gram"]." = ".$unitabbr["Gram"];
            $retval[4] = $unit["Dekagram"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekagram"],1,"n");
            $retval[5] = $unit["Hectogram"]." = ".$unitabbr["Hectogram"];
            $retval[6] = $unit["Kilogram"]." = ".$unitabbr["Kilogram"];
            $retval[7] = $unit["Metric Ton"]." = ".$unitabbr["Metric Ton"];
        } elseif($type=="Area"){
            $unit=get_unit_areas();
            $unitabbr = array_merge(get_unit_length_abbreviations(),get_unit_area_abbreviations());

            if($fullname==0 || $fullname==3) {
                $retval[0] = $unit["Millimeter squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Millimeter"],2,$tick,"y");
                $retval[1] = $unit["Centimeter squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Centimeter"],2,$tick);
                $retval[2] = $unit["Decimeter squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Decimeter"],2,$tick);
                $retval[3] = $unit["Meter squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Meter"],2,$tick);
                $retval[4] = $unit["Dekameter squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekameter"],2,$tick);
                $retval[5] = $unit["Hectometer squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Hectometer"],2,$tick);
                $retval[6] = $unit["Kilometer squared"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Kilometer"],2,$tick);
            } else {
                $retval[0] = $unit["Square millimeter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Millimeter"],2,$tick,"y");
                $retval[1] = $unit["Square centimeter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Centimeter"],2,$tick);
                $retval[2] = $unit["Square decimeter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Decimeter"],2,$tick);
                $retval[3] = $unit["Square meter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Meter"],2,$tick);
                $retval[4] = $unit["Square dekameter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekameter"],2,$tick);
                $retval[5] = $unit["Square hectometer"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Hectometer"],2,$tick);
                $retval[6] = $unit["Square kilometer"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Kilometer"],2,$tick);
            }
            $retval[7] = $unit["Ares"]." = ".$unitabbr["Ares"];
            if($fullname==3 || $fullname==4) {
                $retval[8] = $unit["Centiare"]." = ".$unitabbr["Centiare"];
                $retval[9] = $unit["Decare"]." = ".$unitabbr["Decare"];
                $retval[10] = $unit["Hectare"]." = ".$unitabbr["Hectare"];
            } else {
                $retval[8] = $unit["Hectare"]." = ".$unitabbr["Hectare"];
            }
        } elseif($type=="Volume") {
            $unit = get_unit_volumes();
            $unitabbr = get_unit_length_abbreviations();

            if($fullname==0  || $fullname==3) {
                $retval[0] = $unit["Millimeter cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Millimeter"],3,$tick,"y");
                $retval[1] = $unit["Centimeter cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Centimeter"],3,$tick);
                $retval[2] = $unit["Decimeter cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Decimeter"],3,$tick);
                $retval[3] = $unit["Meter cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Meter"],3,$tick);
                $retval[4] = $unit["Dekameter cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekameter"],3,$tick);
                $retval[5] = $unit["Hectometer cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Hectometer"],3,$tick);
                $retval[6] = $unit["Kilometer cubed"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Kilometer"],3,$tick);
                // For expansion
                //if($fullname==3) {
                //}
            } else {
                $retval[0] = $unit["Cubic millimeter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Millimeter"],3,$tick,"y");
                $retval[1] = $unit["Cubic centimeter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Centimeter"],3,$tick);
                $retval[2] = $unit["Cubic decimeter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Decimeter"],3,$tick);
                $retval[3] = $unit["Cubic meter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Meter"],3,$tick);
                $retval[4] = $unit["Cubic dekameter"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Dekameter"],3,$tick);
                $retval[5] = $unit["Cubic hectometer"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Hectometer"],3,$tick);
                $retval[6] = $unit["Cubic kilometer"]." = ".conversionUnits2ScreenReader1("",$unitabbr["Kilometer"],3,$tick);
                // For expansion
                //if($fullname==4) {
                //} else {
                //}
            }

        }
    }
    #endregion

    #region Time
    // -------------------------------------------------------------------------------------------------
    if($system=="T"){
        $unit = get_unit_times();
        $unitabbr = get_unit_time_abbreviations();

        $retval[0] = $unit["Seconds"]." = ".$unitabbr["Seconds"];
        $retval[1] = $unit["Minutes"]." = ".$unitabbr["Minutes"];
        $retval[2] = $unit["Hours"]." = ".$unitabbr["Hours"];
        $retval[3] = $unit["Days"]." = ".$unitabbr["Days"];
        $retval[4] = $unit["Years"]." = ".$unitabbr["Years"];
        $retval[5] = $unit["Decade"]." = ".$unitabbr["Decade"];
        $retval[6] = $unit["Century"]." = ".$unitabbr["Century"];
    }

    #endregion

    return $retval;
}

// Version 2 functions:
// Inputs - identical silimar to version 1 with the exception that all functions have the same order
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

    $rows = count($v2);

    for($i=0;$i<$rows;$i+=1){
        $retval[] = $v2[$i][$columnindex];
    }

    return $retval;
}

// function conversionArea2(type [,FullWords,Rounding,Sign,tick])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name (feet squared) size <- ELIMINATED replaced with square feet
//            2 = use Full name (square feet) Area
//            3 = add extra conversion factors to version 1 setting of 0
//            4 = add extra conversion factors to version 1 setting of 2
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     Sign: use an = or html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionArea2("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionArea2() {

    $args = func_get_args();

    #region Argument verification

    if (count($args)==0) {
        $retval[0][0] = nothingtodisplay();
        echo $retval[0][0];
        return $retval;
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
        $input = $args[3];
        $sign = verifyEqualSign($input);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    $sign_tick = "$tick$sign$tick";

	#endregion

    // set quote and connector sign
    // if $tick = "n" then no quotes (") on acre, area, hectares
    // this is to prevent the name to be interpreted as variables
    // in the screen display
    if((strtolower($tick)=="y")||($tick=="`")) {
        $quote = '"';
    } else {
        $quote = '';
    }

    $retval = array();

    // Get array values
    $unit=get_unit_areas();
    $unitabbr = array_merge(get_unit_length_abbreviations(),get_unit_area_abbreviations());

    if($system=="A"){

        #region American Conversion

        if($fullname==0 || $fullname==3) {
            $retval[0] = array("",1,$unitabbr["feet"], 144, $unitabbr["inches"]);
            $retval[1] = array("",1,$unitabbr["yard"], 9, $unitabbr["feet"]);
            $retval[2] = array("",1,$quote.$unitabbr["acre"].$quote, 43560, $unitabbr["feet"]);
            $retval[3] = array("",1,$unitabbr["mile"],640,$quote.$unitabbr["acre"].$quote);
            $html = "y";
            for($i=0;$i<2;$i+=1){
                if($i>0) {$html = "n";}
                $retval[$i][0] = conversionUnits2ScreenReader2($retval[$i][1],$retval[$i][2],2,number_format($retval[$i][3]),$retval[$i][4],2,"=",$tick,$html);
            }
            $retval[2][0] = conversionUnits2ScreenReader2($retval[2][1],$retval[2][2],1,number_format($retval[2][3]),$retval[2][4],2,"=",$tick,$html);
            $retval[3][0] = conversionUnits2ScreenReader2($retval[3][1],$retval[3][2],2,number_format($retval[3][3]),$retval[3][4],1,"=",$tick,$html);

        } else {
            $retval[0] = array("",1,$unit["feet squared"], 144, $unit["inches squared"]);
            $retval[1] = array("",1,$unit["yard squared"], 9, $unit["feet squared"]);
            $retval[2] = array("",1,$unit["acre"], 43560, $unit["feet squared"]);
            $retval[3] = array("",1,$unit["mile squared"],640,$unit["acre"]);
            for($i=0;$i<4;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        }


        #endregion

    } elseif($system=="M"){

        #region Metric Conversion

        if($fullname==0 || $fullname==3) {
            $retval[0] = array("",1,$unitabbr["Kilometer"],100,$unitabbr["Hectometer"]);
            $retval[1] = array("",1,$unitabbr["Hectometer"],100,$unitabbr["Dekameter"]);
            $retval[2] = array("",1,$unitabbr["Dekameter"],100,$unitabbr["Meter"]);
            $retval[3] = array("",1,$unitabbr["Meter"],100,$unitabbr["Decimeter"]);
            $retval[4] = array("",1,$unitabbr["Decimeter"],100,$unitabbr["Centimeter"]);
            $retval[5] = array("",1,$unitabbr["Centimeter"],100,$unitabbr["Millimeter"]);
            $retval[6] = array("",1,$unitabbr["Ares"],100,$unitabbr["Meter"]);
            $retval[7] = array("",1,$unitabbr["Hectares"],100,$unitabbr["Ares"]);
            $html = "y";
            for($i=0;$i<6;$i+=1){
                if($i>0) {$html = "n";}
                $retval[$i][0] = conversionUnits2ScreenReader2($retval[$i][1],$retval[$i][2],2,number_format($retval[$i][3]),$retval[$i][4],2,"=",$tick,$html);
            }
            $retval[6][0] = conversionUnits2ScreenReader2($retval[6][1],$retval[6][2],1,number_format($retval[6][3]),$retval[6][4],2,"=",$tick,$html);
            $retval[7][0] = "{$retval[7][1]} {$retval[7][2]} = {$retval[7][3]} {$retval[7][4]}";

        } elseif($fullname==1) {
            $retval[0] = array("",1,$unit["Kilometer squared"],100,$unit["Hectometer squared"]);
            $retval[1] = array("",1,$unit["Hectometer squared"],100,$unit["Dekameter squared"]);
            $retval[2] = array("",1,$unit["Dekameter squared"],100,$unit["Meter squared"]);
            $retval[3] = array("",1,$unit["Meter squared"],100,$unit["Decimeter squared"]);
            $retval[4] = array("",1,$unit["Decimeter squared"],100,$unit["Centimeter squared"]);
            $retval[5] = array("",1,$unit["Centimeter squared"],100,$unit["Millimeter squared"]);
            $retval[6] = array("",1,$unit["Ares"],100,$unit["Meter squared"]);
            $retval[7] = array("",1,$unit["Hectares"],100,$unit["Ares"]);
            for($i=0;$i<8;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        } else  {
            $retval[0] = array("",1,$unit["Square kilometer"],100,$unit["Square hectometer"]);
            $retval[1] = array("",1,$unit["Square hectometer"],100,$unit["Square dekameter"]);
            $retval[2] = array("",1,$unit["Square dekameter"],100,$unit["Square meter"]);
            $retval[3] = array("",1,$unit["Square meter"],100,$unit["Square decimeter"]);
            $retval[4] = array("",1,$unit["Square decimeter"],100,$unit["Square centimeter"]);
            $retval[5] = array("",1,$unit["Square centimeter"],100,$unit["Square millimeter"]);
            $retval[6] = array("",1,$unit["Ares"],100,$unit["Meter squared"]);
            $retval[7] = array("",1,$unit["Hectares"],100,$unit["Ares"]);
            for($i=0;$i<8;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        }

        #endregion

    } elseif($system=="AM"){

        #region American to Metric Conversion

        //6.45160000 cm^2 https://www.wolframalpha.com/input/?i=convert+1+square+inch+to+mm+squared
        $CF = round(6.4516, $rounding);
        if($fullname==0 || $fullname==3) {
            $retval[0] = array("",1,$unitabbr["Inch"],$CF,$unitabbr["Centimeter"]);
            $retval[0][0] = conversionUnits2ScreenReader2($retval[0][1],$retval[0][2],2,$retval[0][3],$retval[0][4],2,$sign,$tick,"y");
        } elseif($fullname==1) {
            $retval[0] = array("",1,$unit["Inch squared"],$CF,$unit["Centimeter squared"]);
            $retval[0][0] = "{$retval[0][1]} {$retval[0][2]} $sign_tick {$retval[0][3]} {$retval[0][4]}";
        } else {
            $retval[0] = array("",1,$unit["Square inch"],$CF,$unit["Square centimeter"]);
            $retval[0][0] = $retval[0][0] = "{$retval[0][1]} {$retval[0][2]} $sign_tick {$retval[0][3]} {$retval[0][4]}";
        }

        #endregion

    } elseif($system=="MA"){

        #region Metric to American Conversion

        // 1.19599005 yd^2 https://www.wolframalpha.com/input/?i=convert+1+square+meter+to+square+feet
        // https://www.wolframalpha.com/input/?i=convert+1+hectares+to+square+feet
        $CF0 = round(1.19599005, $rounding);
        $CF1 = round(2.471, $rounding);
        if($fullname==0 || $fullname==3) {
            $retval[0] = array("",1,$unitabbr["Meter"],$CF0,$unitabbr["Yards"]);
            $retval[0][0] = conversionUnits2ScreenReader2($retval[0][1],$retval[0][2],2,$retval[0][3],$retval[0][4],2,$sign,$tick,"y");
            $retval[1] = array("",1,$unitabbr["hectares"],$CF1,$unitabbr["acres"]);
            $retval[1][0] = "{$retval[1][1]} {$retval[1][2]} $sign_tick {$retval[1][3]} {$retval[1][4]}";
        } elseif($fullname==1) {
            $retval[0] = array("",1,$unit["Meter squared"],$CF0,$unit["Yard squared"]);
            $retval[1] = array("",1,$unit["hectares"],$CF1,$unit["acres"]);
            for($i=0;$i<2;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
            }
        } else {
            $retval[0] = array("",1,$unit["Square meter"],$CF0,$unit["Square yard"]);
            $retval[1] = array("",1,$unit["hectares"],$CF1,$unit["acres"]);
            for($i=0;$i<2;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
            }
        }

        #endregion

    } else {
        $retval[0][0] = "'".(string)$system."' ".isnotvalid();
    }

    return $retval;
}

// function conversionCapacity2(type [,FullWords,Rounding,Sign,tick])
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
// use conversionCapacity2("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionCapacity2() {

	$args = func_get_args();

    #region Argument verification

    if (count($args)==0) {
		$retval[0][0] = nothingtodisplay();
        echo $retval[0][0];
        return $retval;
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
        $input = $args[3];
        $sign = verifyEqualSign($input);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    $sign_tick = "$tick$sign$tick";

	#endregion

    $retval = array();

    // Get array values
    $unit=get_unit_capacities();
    $unitabbr = get_unit_capacity_abbreviations();

	if($system=="A"){

        #region American Conversion

        // don't allow an approximately equal symbol or ans ~
        $sign = "=";

		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["cup"],8,$unitabbr["fluid ounces"]);
            $retval[1] = array("",1,$unitabbr["pint"],2,$unitabbr["cups"]);
            $retval[2] = array("",1,$unitabbr["quart"],2,$unitabbr["pints"]);
            $retval[3] = array("",1,$unitabbr["gallon"],4,$unitabbr["quarts"]);
        } else {
            $retval[0] = array("",1,$unit["Cup"],8,$unit["Fluid ounces"]);
            $retval[1] = array("",1,$unit["Pint"],2,$unit["Cups"]);
            $retval[2] = array("",1,$unit["Quart"],2,$unit["Pints"]);
            $retval[3] = array("",1,$unit["Gallon"],4,$unit["Quarts"]);
        }
        for($i=0;$i<4;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="M"){

        #region Metric Conversion

        // don't allow an approximately equal symbol or ans ~
        $sign = "=";

		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["Kiloliter"],1000,$unitabbr["Liter"]);
            $retval[1] = array("",1,$unitabbr["Hectoliter"],100,$unitabbr["Liter"]);
            $retval[2] = array("",1,conversionUnits2ScreenReader1("",$unitabbr["Dekaliter"],1,"n"),10,$unitabbr["Liter"]);
            $retval[3] = array("",1,$unitabbr["Liter"],10,$unitabbr["Deciliter"]);
            $retval[4] = array("",1,$unitabbr["Liter"],100,$unitabbr["Centiliter"]);
            $retval[5] = array("",1,$unitabbr["Liter"],1000,$unitabbr["Milliliter"]);
        } else {
            $retval[0] = array("",1,$unit["Kiloliter"],1000,$unit["Liter"]);
            $retval[1] = array("",1,$unit["Hectoliter"],100,$unit["Liter"]);
            $retval[2] = array("",1,$unit["Dekaliter"],10,$unit["Liter"]);
            $retval[3] = array("",1,$unit["Liter"],10,$unit["Deciliter"]);
            $retval[4] = array("",1,$unit["Liter"],100,$unit["Centiliter"]);
            $retval[5] = array("",1,$unit["Liter"],1000,$unit["Milliliter"]);
        }
        for($i=0;$i<6;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="AM"){

        #region American to Metric Conversion

		if($fullname==0) {
			$retval[0] = array("",1,$unitabbr["fluid ounces"],round(0.0295735296, $rounding),$unitabbr["Liter"]);
            $retval[1] = array("",1,$unitabbr["cup"],round(0.236588236, $rounding),$unitabbr["Liter"]);
            $retval[2] = array("",1,$unitabbr["pint"],round(0.473176473, $rounding),$unitabbr["Liter"]);
            $retval[3] = array("",1,$unitabbr["quart"],round(0.946352946, $rounding),$unitabbr["Liter"]);
            $retval[4] = array("",1,$unitabbr["gallon"],round(3.78541178, $rounding),$unitabbr["Liter"]);
        } else {
			$retval[0] = array("",1,$unit["fluid ounces"],round(0.0295735296, $rounding),$unit["Liter"]);
            $retval[1] = array("",1,$unit["cup"],round(0.236588236, $rounding),$unit["Liter"]);
            $retval[2] = array("",1,$unit["pint"],round(0.473176473, $rounding),$unit["Liter"]);
            $retval[3] = array("",1,$unit["quart"],round(0.946352946, $rounding),$unit["Liter"]);
            $retval[4] = array("",1,$unit["gallon"],round(3.78541178, $rounding),$unit["Liter"]);
        }
        for($i=0;$i<5;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="MA"){

        #region Metric to American Conversion

		if($fullname==0) {
			$retval[0] = array("",1,$unitabbr["Liter"],round(33.8140227, $rounding),$unitabbr["fluid ounces"]);
            $retval[1] = array("",1,$unitabbr["Liter"],round(4.22675284, $rounding),$unitabbr["cups"]);
            $retval[2] = array("",1,$unitabbr["Liter"],round(2.11337642, $rounding),$unitabbr["pints"]);
            $retval[3] = array("",1,$unitabbr["Liter"],round(1.05668821, $rounding),$unitabbr["quarts"]);
            $retval[4] = array("",1,$unitabbr["Liter"],round(0.264172052, $rounding),$unitabbr["gallon"]);
        } else {
			$retval[0] = array("",1,$unit["Liter"],round(33.8140227, $rounding),$unit["fluid ounces"]);
            $retval[1] = array("",1,$unit["Liter"],round(4.22675284, $rounding),$unit["cups"]);
            $retval[2] = array("",1,$unit["Liter"],round(2.11337642, $rounding),$unit["pints"]);
            $retval[3] = array("",1,$unit["Liter"],round(1.05668821, $rounding),$unit["quarts"]);
            $retval[4] = array("",1,$unit["Liter"],round(0.264172052, $rounding),$unit["gallon"]);
        }
        for($i=0;$i<5;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} else {
        $retval[0][0] = "'".(string)$system."' ".isnotvalid();
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

    $unit=get_unit_formulanames();

    if($type=="Circle") {
        $retval[0] = "C = ".$unit["Circumference"]; // of a circle
        $retval[1] = "A = ".$unit["Area"];
        $retval[2] = "r = ".$unit["Radius"];
        $retval[3] = "d = ".$unit["Diameter"];
    } elseif($type=="Rectangle") {
        $retval[0] = "P = ".$unit["Perimeter"];
        $retval[1] = "A = ".$unit["Area"];
        $retval[2] = "L = ".$unit["Length"];
        $retval[3] = "W = ".$unit["Width"];
    } elseif($type=="Square") {
        $retval[0] = "P = ".$unit["Perimeter"];
        $retval[1] = "A = ".$unit["Area"];
        $retval[2] = "s = ".$unit["side"];
    } elseif($type=="Area") {
        $retval[0] = "SA = ".$unit["Surface Area"];
        $retval[1] = "L = ".$unit["Length"];
        $retval[2] = "W = ".$unit["Width"];
        $retval[3] = "H or h = ".$unit["Height"];
        $retval[4] = "s = ".$unit["Side"];
        $retval[5] = "r = ".$unit["Radius"];
    } elseif($type=="Volume") {
        $retval[0] = "V = ".$unit["Volume"];
        $retval[1] = "L = ".$unit["Length"];
        $retval[2] = "W = ".$unit["Width"];
        $retval[3] = "H or h = ".$unit["Height"];
        $retval[4] = "s = ".$unit["Side"];
        $retval[5] = "r = ".$unit["Radius"];
    } elseif($type=="Triangle") {
        $retval[0] = "P = ".$unit["Perimeter"];
        $retval[1] = "A = ".$unit["Area"];
        $retval[2] = "b = ".$unit["base"];
        $retval[3] = "h = ".$unit["Height"];
    } elseif($type=="Temperature") {
        $retval[0] = "C = ".$unit["Celsius"];
        $retval[1] = "F = ".$unit["Fahrenheit"];
        $retval[2] = "K = ".$unit["Kelvin"];
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

    #region Argument verification

	if (count($args)==0) {
		$firstPart = "C";  // Circle is the default
	} else {
        $firstPart = strtoupper(substr((string)$args[0], 0, 1));
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $tick = verifyTickMarks($args[1]);
    } else {
        $tick = "";
    }

    if ( count($args)>2 && !is_null($args[2]) ) {
        $PI = verifyPI($args[2]);
    } else {
        $PI = " pi ";
    }

	#endregion

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
        $retval[2] = "{$tick}A=$PI r^2{$tick}";
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
        $retval[1] = "{$tick}SA=6 s^2{$tick} "._("(Surface Area of a Cube)");
        $retval[2] = "{$tick}SA=4{$PI}r^2{$tick} "._("(Surface Area of a Sphere)");
        $retval[3] = "{$tick}SA=2{$PI}rh+2{$PI}r^2{$tick} "._("(Surface Area of a Right Circular Cylinder)");
    } elseif($type=="Volume") {
        $retval[0] = "{$tick}V = LWH{$tick} "._("(Volume of a Rectangular Solid)");
        $retval[1] = "{$tick}V = s^3{$tick} "._("(Volume of a Cube)");
        $retval[2] = "{$tick}V = 4/3{$PI}r^3{$tick} "._("(Volume of a Sphere)");
        $retval[3] = "{$tick}V = {$PI}hr^2{$tick} "._("(Volume of a Right Circular Cylinder)");
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

    #region Argument verification

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

    #endregion

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

// function conversionLength2(type [,FullWords,Rounding,Sign,tick])
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
function conversionLength2() {

    $args = func_get_args();

    #region Argument verification

    if (count($args)==0) {
		$retval[0][0] = nothingtodisplay();
        echo $retval[0][0];
        return $retval;
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
        $input = $args[3];
        $sign = verifyEqualSign($input);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    $sign_tick = "$tick$sign$tick";

	#endregion

    $retval = array();

    // Get array values
    $unit = get_unit_lengths();
    $unitabbr = get_unit_length_abbreviations();

	if($system=="A"){

        #region American Conversion

		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["foot"],12,$unitabbr["inches"]);
            $retval[1] = array("",1,$unitabbr["yard"],3,$unitabbr["feet"]);
            $retval[2] = array("",1,$unitabbr["yard"],36,$unitabbr["inches"]);
            $retval[3] = array("",1,$unitabbr["mile"],5280,$unitabbr["feet"]);
        } else {
            $retval[0] = array("",1,$unit["foot"],12,$unit["inches"]);
            $retval[1] = array("",1,$unit["yard"],3,$unit["feet"]);
            $retval[2] = array("",1,$unit["yard"],36,$unit["inches"]);
            $retval[3] = array("",1,$unit["mile"],5280,$unit["feet"]);
        }
        for($i=0;$i<4;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="M"){

        #region Metric Conversion

        if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["Kilometer"],1000,$unitabbr["Meter"]);
            $retval[1] = array("",1,$unitabbr["Hectometer"],100,$unitabbr["Meter"]);
            $retval[2] = array("",1,conversionUnits2ScreenReader1("",$unitabbr["Dekameter"],1,"n"),10,$unitabbr["Meter"]);
            $retval[3] = array("",1,$unitabbr["Meter"],10,$unitabbr["Decimeter"]);
            $retval[4] = array("",1,$unitabbr["Meter"],100,$unitabbr["Centimeter"]);
            $retval[5] = array("",1,$unitabbr["Meter"],1000,$unitabbr["Millimeter"]);
        } else {
            $retval[0] = array("",1,$unit["Kilometer"],1000,$unit["Meter"]);
            $retval[1] = array("",1,$unit["Hectometer"],100,$unit["Meter"]);
            $retval[2] = array("",1,$unit["Dekameter"],10,$unit["Meter"]);
            $retval[3] = array("",1,$unit["Meter"],10,$unit["Decimeter"]);
            $retval[4] = array("",1,$unit["Meter"],100,$unit["Centimeter"]);
            $retval[5] = array("",1,$unit["Meter"],1000,$unit["Millimeter"]);
        }
        for($i=0;$i<6;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="AM"){

        #region American to Metric Conversion

		if($fullname==0) {
			$retval[0] = array("",1,$unitabbr["inch"],round(2.54, $rounding),$unitabbr["Centimeter"]);      // https://www.wolframalpha.com/input/?i=convert+1+inch+to+mm
            $retval[1] = array("",1,$unitabbr["foot"],round(0.3048, $rounding),$unitabbr["Meter"]);         // https://www.wolframalpha.com/input/?i=convert+1+foot+to+dm
            $retval[2] = array("",1,$unitabbr["yard"],round(0.9144, $rounding),$unitabbr["Meter"]);         // https://www.wolframalpha.com/input/?i=convert+1+yard+to+dm
            $retval[3] = array("",1,$unitabbr["mile"],round(1.60934400, $rounding),$unitabbr["Kilometer"]); // 1.60934400 km https://www.wolframalpha.com/input/?i=convert+1+mile+to+m
        } else {
			$retval[0] = array("",1,$unit["inch"],round(2.54, $rounding),$unit["Centimeter"]);
            $retval[1] = array("",1,$unit["foot"],round(0.3048, $rounding),$unit["Meter"]);
            $retval[2] = array("",1,$unit["yard"],round(0.9144, $rounding),$unit["Meter"]);
            $retval[3] = array("",1,$unit["mile"],round(1.60934400, $rounding),$unit["Kilometer"]);
        }
        for($i=0;$i<4;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="MA"){

        #region Metric to American Conversion

		if($fullname==0) {
			$retval[0] = array("",1,$unitabbr["Centimeter"],round(0.393700787, $rounding),$unitabbr["inch"]); // 393.700787 mils https://www.wolframalpha.com/input/?i=convert+1+centimeter+to+inch
            $retval[1] = array("",1,$unitabbr["Meter"],round(3.28083990, $rounding),$unitabbr["feet"]);       // 3.28083990 feet https://www.wolframalpha.com/input/?i=convert+1+meter+to+inch
            $retval[2] = array("",1,$unitabbr["Meter"],round(1.0936133, $rounding),$unitabbr["yard"]);        // 3.28083990 feet divided by 3
            $retval[3] = array("",1,$unitabbr["Kilometer"],round(0.621371, $rounding),$unitabbr["mile"]);     // 621371 miles https://www.wolframalpha.com/input/?i=convert+1000000+kilometer+to+miles
        } else {
			$retval[0] = array("",1,$unit["Centimeter"],round(0.393700787, $rounding),$unit["inch"]);
            $retval[1] = array("",1,$unit["Meter"],round(3.28083990, $rounding),$unit["feet"]);
            $retval[2] = array("",1,$unit["Meter"],round(1.0936133, $rounding),$unit["yard"]);
            $retval[3] = array("",1,$unit["Kilometer"],round(0.621371, $rounding),$unit["mile"]);
        }
        for($i=0;$i<4;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} else {
        $retval[0][0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// function conversionLiquid2(type [,FullWords,Rounding,Sign,tick])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "C" - Casks  (rounding is ignored)
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name
//
// The following are currently not used (2022-05-31)
//
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     Sign: = gives you =
//           ~ gives you ~~
//          "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add//
// Examples
//
// use conversionLiquid2("C") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionLiquid2() {

    $args = func_get_args();

    #region Argument verification

    if (count($args)==0) {
		$retval[0][0] = nothingtodisplay();
        echo $retval[0][0];
        return $retval;
	} else {
        $system = strtoupper($args[0]);
        if($system!='C') {
            echo (string)$system.isnotvalidC();
            return "";
        }
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    //if ( count($args)>2 && !is_null($args[2]) ) {
    //    $rounding = verifyRounding($args[2]);
    //} else {
    //    $rounding = 2;
    //}

    //if ( count($args)>3 && !is_null($args[3]) ) {
    //    $input = $args[3];
    //    $sign = verifyEqualSign($input);
    //} else {
    //    $sign = "=";
    //}

    //if ( count($args)>4 && !is_null($args[4]) ) {
    //    $tick = verifyTickMarks($args[4]);
    //} else {
    //    $tick = "";
    //}

    //$sign_tick = "$tick$sign$tick";

	#endregion

    $retval = array();

    // Get array values
    $unit = get_unit_liquids();
    $unitabbr = get_unit_liquid_abbreviations();

	if($system=="C"){

        #region Casks

        if($fullname==0) {
            $retval[0] = array("",1,$unit["US Barrel"],42,$unitabbr["gallons"]);
            $retval[1] = array("",1,$unit["British Barrel"],43,$unitabbr["gallons"]);
            $retval[2] = array("",1,$unit["Hogshead"],63,$unitabbr["gallons"]);
            $retval[3] = array("",1,$unit["Barrique"],63,$unitabbr["gallons"]);
            $retval[4] = array("",1,$unit["Puncheon"],79,$unitabbr["gallons"]);
            $retval[5] = array("",1,$unit["Butt"],126,$unitabbr["gallons"]);
            $retval[6] = array("",1,$unit["Pipe"],145,$unitabbr["gallons"]);
            $retval[7] = array("",1,$unit["Tun"],252,$unitabbr["gallons"]);
        } else {
            $retval[0] = array("",1,$unit["US Barrel"],42,$unit["gallons"]);
            $retval[1] = array("",1,$unit["British Barrel"],43,$unit["gallons"]);
            $retval[2] = array("",1,$unit["Hogshead"],63,$unit["gallons"]);
            $retval[3] = array("",1,$unit["Barrique"],63,$unit["gallons"]);
            $retval[4] = array("",1,$unit["Puncheon"],79,$unit["gallons"]);
            $retval[5] = array("",1,$unit["Butt"],126,$unit["gallons"]);
            $retval[6] = array("",1,$unit["Pipe"],145,$unit["gallons"]);
            $retval[7] = array("",1,$unit["Tun"],252,$unit["gallons"]);
        }
        for($i=0;$i<8;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} else {
        $retval[0][0] = "'".(string)$system."' ".isnotvalid();
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

    $unit = get_metric_prefixs();
    $unitabbr = get_metric_prefix_abbreviations();

	if($ShowAbb == 0) {
        $retval[0] = $unit["Kilo"];
        $retval[1] = $unit["Hecto"];
        $retval[2] = $unit["Deka"];
        if($type == "G") {
            $retval[3] = $unit["Gram"];
        } elseif($type == "L") {
            $retval[3] =  $unit["Liter"];
        } else {
            $retval[3] = $unit["Meter"];
        }

        $retval[4] = $unit["Deci"];
        $retval[5] = $unit["Centi"];
        $retval[6] = $unit["Milli"];
    } else {
        $retval[0] = $unit["Kilo"]." (".$unitabbr["Kilo"].")";
        $retval[1] = $unit["Hecto"]." (".$unitabbr["Hecto"].")";
        $retval[2] = $unit["Deka"]." (".conversionUnits2ScreenReader1("",$unitabbr["Deka"],1,"","").")";
        if($type == "G") {
            $retval[3] = $unit["Gram"]." (".$unitabbr["Gram"].")";
        } elseif($type == "L") {
            $retval[3] = $unit["Liter"]." (".$unitabbr["Liter"].")";
        } else {
            $retval[3] = $unit["Meter"]." (".$unitabbr["Meter"].")";
        }

        $retval[4] = $unit["Deci"]." (".$unitabbr["Deci"].")";
        $retval[5] = $unit["Centi"]." (".$unitabbr["Centi"].")";
        $retval[6] = $unit["Milli"]." (".$unitabbr["Milli"].")";
    }

	return $retval;
}

// conversionTime() no
// conversionTime() use Abbreviations
// conversionTime("y") use full name
function conversionTime2() {

    // Get array values
    $unit = get_unit_times();
    $unitabbr = get_unit_time_abbreviations();

	$args = func_get_args();
    if (count($args)==0) {
        $retval[0] = array("",1,$unitabbr["minute"], 60, $unitabbr["seconds"]);
		$retval[1] = array("",1,$unitabbr["hour"], 60, $unitabbr["minutes"]);
		$retval[2] = array("",1,$unitabbr["day"], 24, $unitabbr["hours"]);
		$retval[3] = array("",1,$unitabbr["year"], 365, $unitabbr["days"]);
		$retval[4] = array("",1,$unitabbr["decade"], 10, $unitabbr["years"]);
		$retval[5] = array("",1,$unitabbr["century"], 100, $unitabbr["years"]);
    } else {
        $retval[0] = array("",1,$unit["minute"], 60, $unit["seconds"]);
		$retval[1] = array("",1,$unit["hour"], 60, $unit["minutes"]);
		$retval[2] = array("",1,$unit["day"], 24, $unit["hours"]);
		$retval[3] = array("",1,$unit["year"], 365, $unit["days"]);
		$retval[4] = array("",1,$unit["decade"], 10, $unit["years"]);
		$retval[5] = array("",1,$unit["century"], 100, $unit["years"]);
    }

    for($i=0;$i<6;$i+=1){
        //$retval[$i][0] = sprintf("%d %s = %d %s",$retval[$i][1], $retval[$i][2], $retval[$i][3], $retval[$i][4]);
        $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = {$retval[$i][3]} {$retval[$i][4]}";
    }

	return $retval;
}

//function conversionUnit2ScreenReaderModification($units) - INTERNAL ONLY
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

function htmlwarning($html) {
    if($html=="y") {
        return "<! "._("The 'aria-hidden' attribute hides the span from a screen reader. While the class 'sr-only' hides the span from the display")." -->";
    } else {
        return "";
    }
}

//function conversionUnits2ScreenReader1(number,units,dimensions=2,tick="y",html="n") - INTERNAL ONLY
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
function conversionUnits2ScreenReader1($number1,$units,$dimensions=2,$tick="y",$html="n"){

    $tick = verifyTickMarks($tick);
    $exponentWord = _("exponent");
    $retval = conversionUnit2ScreenReaderModification($units,$tick);

    $unit1 = _($retval[0]);
    $unitSR1 = $retval[1]; // does this need a gettext option for foreign language screen readers

    if($dimensions==1) {
        return htmlwarning($html)."
                <span aria-hidden=true>$tick$number1 $unit1 $tick</span>
                <span class=\"sr-only\">$number1 $unitSR1 </span>";
    } else {
        return htmlwarning($html)."
                <span aria-hidden=true>$tick$number1 $unit1^$dimensions $tick</span>
                <span class=\"sr-only\">$number1 $unitSR1 $exponentWord $dimensions</span>";
    }
}

//function conversionUnits2ScreenReader2(number1,units1,dimensions1,number2,units2,dimensions2,sign="=",tick="y",html="n")
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
function conversionUnits2ScreenReader2($number1,$units1,$dimensions1,$number2,$units2,$dimensions2,$sign="=",$tick="y",$html="n"){

    $tick = verifyTickMarks($tick);
    $exponentWord = _("exponent");
    $retval1 = conversionUnit2ScreenReaderModification($units1,$tick);
    $retval2 = conversionUnit2ScreenReaderModification($units2,$tick);

    $unit1 = _($retval1[0]);
    $unitSR1 = $retval1[1]; // does this need a gettext option for foreign language screen readers

    $unit2 = _($retval2[0]);
    $unitSR2 = $retval2[1]; // does this need a gettext option for foreign language screen readers

    if($dimensions1==1) {
        if($dimensions2==1) {
            return htmlwarning($html)."
                    <span aria-hidden=true>$tick$number1 $unit1 $sign $number2 $unit2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1 $sign $number2 $unitSR2 </span>";
        } else {
            return htmlwarning($html)."
                    <span aria-hidden=true>$tick$number1 $unit1 $sign $number2 $unit2^$dimensions2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1 $sign $number2 $unitSR2 $exponentWord $dimensions2</span>";
        }
    } else {
        if($dimensions2==1) {
            return htmlwarning($html)."
                    <span aria-hidden=true>$tick$number1 $unit1^$dimensions1 $sign $number2 $unit2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1  $exponentWord $dimensions1 $sign $number2 $unitSR2</span>";
        }
        else {
            return htmlwarning($html)."
                    <span aria-hidden=true>$tick$number1 $unit1^$dimensions1 $sign $number2 $unit2^$dimensions2$tick</span>
                    <span class=\"sr-only\">$number1 $unitSR1  $exponentWord $dimensions1 $sign $number2 $unitSR2 $exponentWord $dimensions2</span>";
        }
    }
}

// function conversionVolume(type [,FullWords,Rounding,Sign,tick])
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
//           "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionVolume("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionVolume2() {

    $args = func_get_args();

    #region Argument verification

    if (count($args)==0) {
		$retval[0][0] = nothingtodisplay();
        echo $retval[0][0];
        return $retval;
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
        $input = $args[3];
        $sign = verifyEqualSign($input);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    $sign_tick = "$tick$sign$tick";

	#endregion

    $retval = array();

    // Get array values
    $unit = array_merge(get_unit_volumes(),get_unit_lengths());
    $unitabbr = array_merge(get_unit_capacity_abbreviations(),get_unit_length_abbreviations());

	if($system=="A"){

        #region American Conversion

		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["foot"],1728,$unitabbr["inches"]);
            $retval[1] = array("",1,$unitabbr["yard"],27,$unitabbr["feet"]);
            $html = "y";
            for($i=0;$i<2;$i+=1){
                if($i>0) {$html = "n";}
                $retval[$i][0] = conversionUnits2ScreenReader2($retval[$i][1],$retval[$i][2],3,number_format($retval[$i][3]),$retval[$i][4],3,"=",$tick,$html);
            }
        } elseif($fullname==1) {
            $retval[0] = array("",1,$unit["foot cubed"],1728,$unit["inches cubed"]);
            $retval[1] = array("",1,$unit["yard cubed"],27,$unit["feet cubed"]);
            for($i=0;$i<2;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        } elseif($fullname==2) {
            $retval[0] = array("",1,$unit["cubic foot"],1728,$unit["cubic inches"]);
            $retval[1] = array("",1,$unit["cubic yard"],27,$unit["cubic feet"]);
            for($i=0;$i<2;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        }

        #endregion

	} elseif($system=="M"){

        #region Metric Conversion

		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["Kilometer"],1000,$unitabbr["Hectometer"]);
            $retval[1] = array("",1,$unitabbr["Hectometer"],1000,$unitabbr["Dekameter"]);
            $retval[2] = array("",1,$unitabbr["Dekameter"],1000,$unitabbr["Meter"]);
            $retval[3] = array("",1,$unitabbr["Meter"],1000,$unitabbr["Decimeter"]);
            $retval[4] = array("",1,$unitabbr["Decimeter"],1000,$unitabbr["Centimeter"]);
            $retval[5] = array("",1,$unitabbr["Centimeter"],1000,$unitabbr["Millimeter"]);
            for($i=0;$i<6;$i+=1){
                $retval[$i][0] = "{$tick}{$retval[$i][1]} {$retval[$i][2]}^3 = ".number_format($retval[$i][3])." {$retval[$i][4]}^3{$tick}";
            }
        } elseif($fullname==1) {
            $retval[0] = array("",1,$unit["Kilometer cubed"],1000,$unit["Hectometer cubed"]);
            $retval[1] = array("",1,$unit["Hectometer cubed"],1000,$unit["Dekameter cubed"]);
            $retval[2] = array("",1,$unit["Dekameter cubed"],1000,$unit["Meter cubed"]);
            $retval[3] = array("",1,$unit["Meter cubed"],1000,$unit["Decimeter cubed"]);
            $retval[4] = array("",1,$unit["Decimeter cubed"],1000,$unit["Centimeter cubed"]);
            $retval[5] = array("",1,$unit["Centimeter cubed"],1000,$unit["Millimeter cubed"]);
            for($i=0;$i<6;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        } else  {
            $retval[0] = array("",1,$unit["Cubic kilometer"],1000,$unit["Cubic hectometer"]);
            $retval[1] = array("",1,$unit["Cubic hectometer"],1000,$unit["Cubic dekameter"]);
            $retval[2] = array("",1,$unit["Cubic dekameter"],1000,$unit["Cubic meter"]);
            $retval[3] = array("",1,$unit["Cubic meter"],1000,$unit["Cubic decimeter"]);
            $retval[4] = array("",1,$unit["Cubic decimeter"],1000,$unit["Cubic centimeter"]);
            $retval[5] = array("",1,$unit["Cubic centimeter"],1000,$unit["Cubic millimeter"]);
            for($i=0;$i<6;$i+=1){
                $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
            }
        }

        #endregion

	} elseif($system=="AM"){

        #region American to Metric Conversion

        // 0.0163870640 L https://www.wolframalpha.com/input/?i=convert+1+cubic+inch+to+ml
        $CF = round(16.3870640, $rounding);
		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["Inch"],$CF,$unitabbr["Millimeter"]);
			$retval[0][0] = conversionUnits2ScreenReader2($retval[0][1],$retval[0][2],3,$retval[0][3],$retval[0][4],1,$sign,$tick);
        } elseif($fullname==1) {
			$retval[0] = array("",1,$unit["Inches cubed"],$CF,$unit["Millimeter"]);
            $retval[0][0] = "{$retval[0][1]} {$retval[0][2]} $sign_tick {$retval[0][3]} {$retval[0][4]}";
        } else {
            $retval[0] = array("",1,$unit["Cubic inches"],$CF,$unit["Millimeter"]);
            $retval[0][0] = "{$retval[0][1]} {$retval[0][2]} $sign_tick {$retval[0][3]} {$retval[0][4]}";
        }

        #endregion

	} elseif($system=="MA"){

        #region Metric to American Conversion

        // 61.0237441 in^3  https://www.wolframalpha.com/input/?i=convert+1+liter+to+cubic+feet
        $CF = round(61.0237441, $rounding);
		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["Liter"],$CF,$unitabbr["Inch"]);
			$retval[0][0] = conversionUnits2ScreenReader2($retval[0][1],$retval[0][2],1,$retval[0][3],$retval[0][4],3,$sign,$tick);
        } elseif($fullname==1) {
            $retval[0] = array("",1,$unit["Liter"],$CF,$unit["Inches cubed"]);
            $retval[0][0] = "{$retval[0][1]} {$retval[0][2]} $sign_tick {$retval[0][3]} {$retval[0][4]}";
        } else {
            $retval[0] = array("",1,$unit["Liter"],$CF,$unit["Cubic inches"]);
            $retval[0][0] = "{$retval[0][1]} {$retval[0][2]} $sign_tick {$retval[0][3]} {$retval[0][4]}";
        }

        #endregion

	} else {
        $retval[0][0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}

// function conversionWeight(type [,FullWords,Rounding,Sign,tick])
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
//           "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionWeight("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionWeight2() {

	$args = func_get_args();

    #region Argument verification

    if (count($args)==0) {
		$retval[0][0] = nothingtodisplay();
        echo $retval[0][0];
        return $retval;
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
        $input = $args[3];
        $sign = verifyEqualSign($input);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

    $sign_tick = "$tick$sign$tick";

	#endregion

    $retval = array();

    // Get array values
    $unit = get_unit_weights();
    $unitabbr = get_unit_weight_abbreviations();

	if($system=="A"){

        #region American Conversion

		if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["pound"],16,$unitabbr["ounces"]);
            $retval[1] = array("",1,$unitabbr["Ton"],2000,$unitabbr["pounds"]);
        } else {
            $retval[0] = array("",1,$unit["pound"],16,$unit["ounces"]);
            $retval[1] = array("",1,$unit["Ton"],2000,$unit["pounds"]);
        }
        for($i=0;$i<2;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="M"){

        #region Metric Conversion

        if($fullname==0) {
            $retval[0] = array("",1,$unitabbr["Kilogram"],1000,$unitabbr["Gram"]);
            $retval[1] = array("",1,$unitabbr["Hectogram"],100,$unitabbr["Gram"]);
            $retval[2] = array("",1,conversionUnits2ScreenReader1("",$unitabbr["Dekagram"],1,"n"),10,$unitabbr["Gram"]);
            $retval[3] = array("",1,$unitabbr["Gram"],10,$unitabbr["Decigram"]);
            $retval[4] = array("",1,$unitabbr["Gram"],100,$unitabbr["Centigram"]);
            $retval[5] = array("",1,$unitabbr["Gram"],1000,$unitabbr["Milligram"]);
            $retval[6] = array("",1,$unitabbr["Metric Ton"],1000,$unitabbr["Kilogram"]);
        } else {
            $retval[0] = array("",1,$unit["Kilogram"],1000,$unit["Gram"]);
            $retval[1] = array("",1,$unit["Hectogram"],100,$unit["Gram"]);
            $retval[2] = array("",1,$unit["Dekagram"],10,$unit["Gram"]);
            $retval[3] = array("",1,$unit["Gram"],10,$unit["Decigram"]);
            $retval[4] = array("",1,$unit["Gram"],100,$unit["Centigram"]);
            $retval[5] = array("",1,$unit["Gram"],1000,$unit["Milligram"]);
            $retval[6] = array("",1,$unit["Metric Ton"],1000,$unit["Kilogram"]);
        }
        for($i=0;$i<7;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} = ".number_format($retval[$i][3])." {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="AM"){

        #region American to Metric Conversion

        if($fullname==0) {
			$retval[0] = array("",1,$unitabbr["ounce"],round(28.3495231, $rounding),$unitabbr["Gram"]);    // 0.0283495231 kg https://www.wolframalpha.com/input/?i=convert+1+ounce+to+gram
            $retval[1] = array("",1,$unitabbr["pound"],round(0.453592370, $rounding),$unitabbr["Kilogram"]); // 0.453592370 kg https://www.wolframalpha.com/input/?i=convert+1+pound+to+gram
        } else {
			$retval[0] = array("",1,$unit["ounce"],round(28.3495231, $rounding),$unit["Gram"]);
            $retval[1] = array("",1,$unit["pound"],round(0.453592370, $rounding),$unit["Kilogram"]);
        }
        for($i=0;$i<2;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} elseif($system=="MA"){

        #region Metric to American Conversion

		if($fullname==0) {
			$retval[0] = array("",1,$unitabbr["Gram"],round(0.035274, $rounding),$unitabbr["ounces"]);
            $retval[1] = array("",1,$unitabbr["Kilogram"],round(2.20462, $rounding),$unitabbr["pound"]);
        } else {
			$retval[0] = array("",1,$unit["Gram"],round(0.035274, $rounding),$unit["ounces"]);
            $retval[1] = array("",1,$unit["Kilogram"],round(2.20462, $rounding),$unit["pound"]);
        }
        for($i=0;$i<2;$i+=1){
            $retval[$i][0] = "{$retval[$i][1]} {$retval[$i][2]} $sign_tick {$retval[$i][3]} {$retval[$i][4]}";
        }

        #endregion

	} else {
        $retval[0][0] = "'".(string)$system."' ".isnotvalid();
    }

	return $retval;
}




// Version 1 functions
function conversionTime() {
	$args = func_get_args();
    if (count($args)==0) {
        return conversion_extract_column_array(conversionTime2(),0);
    } else {
        return conversion_extract_column_array(conversionTime2($args),0);
    }
}

// function conversionArea(type [,FullWords,Rounding,Sign,tick])
// returns an array of strings with the conversion factors
//
// INPUTS:
//   system: "A" - American (rounding is ignored)
//           "M" - Metric   (rounding is ignored)
//           "AM - Americian to Metric
//           "MA - Metric to Americian
//
// FullWords: 0 = use Abbreviations
//            1 = use Full name (feet squared) size <- ELIMINATED replaced with square feet
//            2 = use Full name (square feet) Area
// Rounding: a integer number of digits to round to that is between 2 and 8 and defaults to 2
//     Sign: = gives you =
//           ~ gives you ~~
//           "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Examples
//
// use conversionArea("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionArea() {

    $args = func_get_args();

    #region Verify/set defaults for function arguments

    if (count($args)==0) {
        echo nothingtodisplay();
        return "";
    } else {
        $system = strtoupper($args[0]);
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

    // in the original version the sign and tick argument
    // were reversed
    if ( count($args)>3 && !is_null($args[3]) ) {
        $args3input =  $args[3];
        if ( count($args)>4 && !is_null($args[4]) ) {
          $args4input =  $args[4];
        } else {
            $args4input = "";
        }

        // was args3input the tick argument and args4input the sign - if so switch
        // should not be needed - but this wll prevent anyone that used this before
        // the release from displaying about = signs (the default if the 3rd argument is not a = or ~)
        if($args3input=="y" || $args3input=="n" || $args4input = "=" || $args4input = "~") {
            $signinput = $args4input;
            $tickinput = $args3input;
        } else {
            $signinput = $args3input;
            $tickinput = $args4input;
        }
        $sign = verifyEqualSign($signinput);
        $tick = verifyTickMarks($tickinput);
    } else {
        $sign = "=";
        $tick = "";
    }

	#endregion

    return conversion_extract_column_array(conversionArea2($system,$fullname,$rounding,$sign,$tick),0);
}

// function conversionCapacity(type [,FullWords,Rounding,Sign,tick])
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
//           "" gives you html approximately equal symbol
//     tick: y = add a tick mark around items with exponents
//           n = don't add
//
// Example
//
// use conversionCapacity("A") returns an array of strings that have Abbreviations for the units that can be used for display
function conversionCapacity() {

    $args = func_get_args();

    #region Verify/set defaults for function arguments

    if (count($args)==0) {
        echo nothingtodisplay();
        return "";
    } else {
        $system = strtoupper($args[0]);
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
        $sign = verifyEqualSign($args[3]);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

	#endregion

    return conversion_extract_column_array(conversionCapacity2($system,$fullname,$rounding,$sign,$tick),0);
}

// function conversionLength(type [,FullWords,Rounding,Sign,tick])
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

    #region Verify/set defaults for function arguments

    if (count($args)==0) {
        echo nothingtodisplay();
        return "";
    } else {
        $system = strtoupper($args[0]);
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
        $sign = verifyEqualSign($args[3]);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

	#endregion

    return conversion_extract_column_array(conversionLength2($system,$fullname,$rounding,$sign,$tick),0);
}

// function conversionLiquid(type [,FullWords])
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

    #region Verify/set defaults for function arguments

    if (count($args)==0) {
        echo nothingtodisplay();
        return "";
    } else {
        $system = strtoupper($args[0]);
    }

    if ( count($args)>1 && !is_null($args[1]) ) {
        $fullname = verifyFullName($args[1]);
    } else {
        $fullname = 0;
    }

    //if ( count($args)>2 && !is_null($args[2]) ) {
    //    $rounding = verifyRounding($args[2]);
    //} else {
    //    $rounding = 2;
    //}

    //if ( count($args)>3 && !is_null($args[3]) ) {
    //    $sign = verifyEqualSign($args[3]);
    //} else {
    //    $sign = "=";
    //}

    //if ( count($args)>4 && !is_null($args[4]) ) {
    //    $tick = verifyTickMarks($args[4]);
    //} else {
    //    $tick = "";
    //}

	#endregion

    return conversion_extract_column_array(conversionLiquid2($system,$fullname,"","",""),0);
}

// function conversionVolume(type [,FullWords,Rounding,Sign,tick])
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

    #region Verify/set defaults for function arguments

    if (count($args)==0) {
        echo nothingtodisplay();
        return "";
    } else {
        $system = strtoupper($args[0]);
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

    // in the original version the sign and tick argument
    // were reversed
    if ( count($args)>3 && !is_null($args[3]) ) {
        $args3input =  $args[3];
        if ( count($args)>4 && !is_null($args[4]) ) {
            $args4input =  $args[4];
        } else {
            $args4input = "";
        }

        // was args3input the tick argument and args4input the sign - if so switch
        // should not be needed - but this wll prevent anyone that used this before
        // the release from displaying about = signs (the default if the 3rd argument is not a = or ~)
        if($args3input=="y" || $args3input=="n" || $args4input = "=" || $args4input = "~") {
            $signinput = $args4input;
            $tickinput = $args3input;
        } else {
            $signinput = $args3input;
            $tickinput = $args4input;
        }
        $sign = verifyEqualSign($signinput);
        $tick = verifyTickMarks($tickinput);
    } else {
        $sign = "=";
        $tick = "";
    }

	#endregion

    return conversion_extract_column_array(conversionVolume2($system,$fullname,$rounding,$sign,$tick),0);
}

// function conversionWeight(type [,FullWords,Rounding,Sign,tick])
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

    #region Verify/set defaults for function arguments

    if (count($args)==0) {
        echo nothingtodisplay();
        return "";
    } else {
        $system = strtoupper($args[0]);
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
        $sign = verifyEqualSign($args[3]);
    } else {
        $sign = "=";
    }

    if ( count($args)>4 && !is_null($args[4]) ) {
        $tick = verifyTickMarks($args[4]);
    } else {
        $tick = "";
    }

	#endregion

    return conversion_extract_column_array(conversionWeight2($system,$fullname,$rounding,$sign,$tick),0);

}

//  WAMAP Question ID: 201697

// 2022-xx-xx ver 27 - TODO: add a make fraction converion function
//
// 2022-10-10 ver26.3- Fixed Typo on  1 acre = 43,560 ft^2 and 1 mi = 640 acre
//  through            100 mg, mL
// 2022-10-12          decigram abbriavation
//
// 2022-08-17 ver 25 - Fixed Typos
//
// 2022-05-24 ver 24 - changed all string references to two arrays $unit and $unitabbr. Converted conversion functions to return
//                     an array of values with the first value an array of conversion strings (equivalent to the original function)
//  through            added checks in conversionAreaand conversionVolume for switched sign and tick arguments and corrected them (should not be needed)
// 2022-05-17
// 2022-05-16 ver 23 - has conversion_detectlanguage functions that wer eliminated as they interferred with gettext function
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
