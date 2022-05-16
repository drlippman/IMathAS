<?php
// Conversion module - this contains constants for use with Rate and Ratio conversion questions
// Mike Jenck, Originally developed Jan 29-31, 2021
// licensed under GPL version 2 or later
//

function conversionVer() {
	// File version
	return 24;
}

function conversion_detectlanguage(){

    $supportedLangs = array('en-gb', 'en-ca', 'en-us', 'en', 'de', 'de-de');
    $conversion_browser_langstr = preg_replace('/;q=[\d\.]*/','',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $conversion_browser_languages = explode(',', strtolower($conversion_browser_langstr));

    foreach($conversion_browser_languages as $conversion_browser_lang)
    {
        if(in_array($conversion_browser_lang, $supportedLangs))
        {
            if($conversion_browser_lang == 'de' || $conversion_browser_lang == 'de-de') {
                return 'de-de';
            }

            // return the first language
            return $conversion_browser_lang;
        }
    }

    return 'en-us';
}

$conversion_browser_lang = conversion_detectlanguage();

function exponent($case) {
    if($case=="E") {
        return "Exponent";
    } else {
        return "exponent";
    }
}

// American Prefix Functions --------------------------------------------------------------------------------
function inch($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="I") {
            return "Zoll";
        } else {
            return "zoll";
        }
    } else {
        //default
        if($case=="I") {
            return _('Inch');
        } else {
            return _('inch');
        }
    }
}
function inches($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="I") {
            return "Zoll";
        } else {
            return "zoll";
        }
    } else {
        //default
        if($case=="I") {
            return _('Inches');
        } else {
            return _('inches');
        }
    }
}
function foot($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="F") {
            return "Fuß";
        } else {
            return "fuß";
        }
    } else {
        //default
        if($case=="F") {
            return _('Foot');
        } else {
            return _('foot');
        }
    }
}
function feet($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="F") {
            return "Fuß";
        } else {
            return "fuß";
        }
    } else {
        //default
        if($case=="F") {
            return _('Feet');
        } else {
            return _('feet');
        }
    }
}
function yard($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="Y") {
            return "Yard";
        } else {
            return "yard";
        }
    } else {
        //default
        if($case=="Y") {
            return _('Yard');
        } else {
            return _('yard');
        }
    }
}
function yards($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="Y") {
            return "Yards";
        } else {
            return "yards";
        }
    } else {
        //default
        if($case=="Y") {
            return _('Yards');
        } else {
            return _('yards');
        }
    }
}
function mile($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="M") {
            return "Meile";
        } else {
            return "meile";
        }
    } else {
        //default
        if($case=="M") {
            return _('Mile');
        } else {
            return _('mile');
        }
    }
}
function miles($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="M") {
            return "Meilen";
        } else {
            return "meilen";
        }
    } else {
        //default
        if($case=="M") {
            return _('Miles');
        } else {
            return _('miles');
        }
    }
}
function acres($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="A") {
            return "Acres";
        } else {
            return "acres";
        }
    } else {
        //default
        if($case=="A") {
            return _('Acres');
        } else {
            return _('acres');
        }
    }
}
function acresabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="A") {
            return "Acre";
        } else {
            return "acre";
        }
    } else {
        //default
        if($case=="A") {
            return _('Acre');
        } else {
            return _('acre');
        }
    }
}

function fluidounces($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="F") {
            return "Flüssige Unzen";
        } else {
            return "Flüssige Unzen";
        }
    } else {
        //default
        if($case=="F") {
            return _('Fluid ounces');
        } else {
            return _('fluid ounces');
        }
    }
}
function cup($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="C") {
            return "Tasse";
        } else {
            return "tasse";
        }
    } else {
        //default
        if($case=="C") {
            return _('Cup');
        } else {
            return _('cup');
        }
    }
}
function cups($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="C") {
            return "Tassen";
        } else {
            return "tassen";
        }
    } else {
        //default
        if($case=="C") {
            return _('Cups');
        } else {
            return _('cups');
        }
    }
}
function pints($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="P") {
            return "Pints";
        } else {
            return "pints";
        }
    } else {
        //default
        if($case=="P") {
            return _('Pints');
        } else {
            return _('pints');
        }
    }
}
function quarts($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="Q") {
            return "Quart";
        } else {
            return "quart";
        }
    } else {
        //default
        if($case=="Q") {
            return _('Quarts');
        } else {
            return _('quarts');
        }
    }
}
function gallons($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="G") {
            return "Gallonen";
        } else {
            return "gallonen";
        }
    } else {
        //default
        if($case=="G") {
            return _('Gallons');
        } else {
            return _('gallons');
        }
    }
}

function ounces($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="O") {
            return "Unzen";
        } else {
            return "unzen";
        }
    } else {
        //default
        if($case=="O") {
            return _('Ounces');
        } else {
            return _('ounces');
        }
    }
}
function pounds($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="P") {
            return "Pfund";
        } else {
            return "pfund";
        }
    } else {
        //default
        if($case=="P") {
            return _('Pounds');
        } else {
            return _('pounds');
        }
    }
}
function americanton($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="T") {
            return "Tonne";
        } else {
            return "Tonne";
        }
    } else {
        //default
        if($case=="T") {
            return _('Ton');
        } else {
            return _('ton');
        }
    }
}
function americantons($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="T") {
            return "Tonnen";
        } else {
            return "Tonnen";
        }
    } else {
        //default
        if($case=="T") {
            return _('Tons');
        } else {
            return _('tons');
        }
    }
}

// Metric Prefix Functions --------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// NOTE: _('word') is a call to gettext() for localization
//
// Functions for the spelling of metric words
// update the function
//
// 'en-gb' - Great Britian English
// 'en-ca' - Canadian English
// 'en-us' - American English
// 'de-de' - German
//
function kilo($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="K") {
            return "Kilo";
        } else {
            return "kilo";
        }
    } else {
        //default
        if($case=="K") {
            return _('Kilo');
        } else {
            return _('kilo');
        }
    }
}
function kiloabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="K") {
            return "K";
        } else {
            return "k";
        }
    } else {
        //default
        if($case=="K") {
            return _('K');
        } else {
            return _('k');
        }
    }
}

function hecto($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="H") {
            return "Hekto";
        } else {
            return "hekto";
        }
    } else {
        //default
        if($case=="H") {
            return _('Hecto');
        } else {
            return _('hecto');
        }
    }
}
function hectoabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="H") {
            return "H";
        } else {
            return "h";
        }
    } else {
        //default
        if($case=="H") {
            return _('H');
        } else {
            return _('h');
        }
    }
}

function deca($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'en-gb' || $conversion_browser_lang == 'en-ca') {
        if($case=="D") {
            return "Deca";
        } else {
            return "deca";
        }
    }  elseif($conversion_browser_lang == 'de-de') {
        if($case=="D") {
            return "Deka";
        } else {
            return "deka";
        }
    } else {
        //default
        if($case=="D") {
            return _('Deka');
        } else {
            return _('deka');
        }
    }
}
function decaabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="D") {
            return "Da";
        } else {
            return "da";
        }
    } else {
        //default
        if($case=="D") {
            return _('Da');
        } else {
            return _('da');
        }
    }
}
function decaabbrsr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="D") {
            return "D a";
        } else {
            return "d a";
        }
    } else {
        //default
        if($case=="D") {
            return _('D a');
        } else {
            return _('d a');
        }
    }
}

function meter($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'en-gb' || $conversion_browser_lang == 'en-ca') {
        if($case=="M") {
            return "Metre";
        } else {
            return "metre";
        }
    } elseif($conversion_browser_lang == 'de-de') {
        if($case=="M") {
            return "Meter";
        } else {
            return "meter";
        }
    } else {
        //default
        if($case=="M") {
            return _('Meter');
        } else {
            return _('meter');
        }
    }
}
function meterabbr($case) {
    global $conversion_browser_lang;

    if($case=="M") {
        return _('M');
    } else {
        return _('m');
    }
}

function liter($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'en-gb' || $conversion_browser_lang == 'en-ca') {
        if($case=="L") {
            return "Litre";
        } else {
            return "litre";
        }
    } elseif($conversion_browser_lang == 'de-de') {
        if($case=="L") {
            return "Liter";
        } else {
            return "liter";
        }
    } else {
        //default
        if($case=="L") {
            return _('Liter');
        } else {
            return _('liter');
        }
    }
}
function literabbr($case) {
    global $conversion_browser_lang;

    if($case=="L") {
        return _('L');
    } else {
        return _('l');
    }
}

function gram($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'en-gb') {
        if($case=="G") {
            return "Gramme";
        } else {
            return "gramme";
        }
    } elseif($conversion_browser_lang == 'de-de') {
        if($case=="G") {
            return "Gramm";
        } else {
            return "gramm";
        }
    } else {
        //default
        if($case=="G") {
            return _('Gram');
        } else {
            return _('gram');
        }
    }
}
function gramabbr($case) {
    global $conversion_browser_lang;

    if($case=="G") {
        return _('G');
    } else {
        return _('g');
    }
}

function deci($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="D") {
            return "Dezi";
        } else {
            return "dezi";
        }
    } else {
        //default
        if($case=="D") {
            return _('Deci');
        } else {
            return _('deci');
        }
    }
}
function deciabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="D") {
            return "D";
        } else {
            return "d";
        }
    } else {
        //default
        if($case=="D") {
            return _('D');
        } else {
            return _('d');
        }
    }
}

function centi($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="C") {
            return "Zenti";
        } else {
            return "zenti";
        }
    } else {
        //default
        if($case=="C") {
            return _('Centi');
        } else {
            return _('centi');
        }
    }
}
function centiabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="C") {
            return "C";
        } else {
            return "c";
        }
    } else {
        //default
        if($case=="C") {
            return _('C');
        } else {
            return _('c');
        }
    }
}

function milli($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Milli";
        if($case=="M") {
            return "Milli";
        } else {
            return "milli";
        }
    } else {
        //default
        if($case=="M") {
            return _('Milli');
        } else {
            return _('milli');
        }
    }
}
function milliabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="M") {
            return "M";
        } else {
            return "m";
        }
    } else {
        //default
        if($case=="M") {
            return _('M');
        } else {
            return _('m');
        }
    }
}

// Time Functions --------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
//
function century($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jahrhundert";
    } else {
        //default
        if($case=="C") {
            return _('Century');
        } else {
            return _('century');
        }
    }
}
function centuryabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jh";
    } else {
        //default
        if($case=="C") {
            return _('C');
        } else {
            return _('c');
        }
    }
}
function centuries($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jahrhunderte";
    } else {
        //default
        if($case=="C") {
            return _('Centuries');
        } else {
            return _('centuries');
        }
    }
}

function decade($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jahrzehnt";
    } else {
        //default
        if($case=="D") {
            return _('Decade');
        } else {
            return _('decade');
        }
    }
}
function decades($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jahrzehnte";
    } else {
        //default
        if($case=="D") {
            return _('Decades');
        } else {
            return _('decades');
        }
    }
}

function year($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jahr";
    } else {
        //default
        if($case=="Y") {
            return _('Year');
        } else {
            return _('year');
        }
    }
}
function yearabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "j";
    } else {
        //default
        if($case=="Y") {
            return _('Yr');
        } else {
            return _('yr');
        }
    }
}
function years($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Jahre";
    } else {
        //default
        if($case=="Y") {
            return _('Years');
        } else {
            return _('years');
        }
    }
}

function day($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Tag";
    } else {
        //default
        if($case=="D") {
            return _('Day');
        } else {
            return _('day');
        }
    }
}
function dayabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "d";
    } else {
        //default
        if($case=="D") {
            return _('D');
        } else {
            return _('d');
        }
    }
}
function days($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Tage";
    } else {
        //default
        if($case=="D") {
            return _('Days');
        } else {
            return _('days');
        }
    }
}

function hour($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Stunde";
    } else {
        //default
        if($case=="H") {
            return _('Hour');
        } else {
            return _('hour');
        }
    }
}
function hourabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "h";
    } else {
        //default
        if($case=="H") {
            return _('Hr');
        } else {
            return _('hr');
        }
    }
}
function hours($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Stunden";
    } else {
        //default
        if($case=="H") {
            return _('Hours');
        } else {
            return _('hours');
        }
    }
}

function minute($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Minute";
    } else {
        //default
        if($case=="M") {
            return _('Minute');
        } else {
            return _('minute');
        }
    }
}
function minuteabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "m";
    } else {
        //default
        if($case=="D") {
            return _('Min');
        } else {
            return _('min');
        }
    }
}
function minutes($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Minuten";
    } else {
        //default
        if($case=="M") {
            return _('Minutes');
        } else {
            return _('minutes');
        }
    }
}

function second($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Sekunde";
    } else {
        //default
        if($case=="S") {
            return _('Second');
        } else {
            return _('second');
        }
    }
}
function secondabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "s";
    } else {
        //default
        if($case=="S") {
            return _('Sec');
        } else {
            return _('sec');
        }
    }
}
function seconds($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Sekunden";
    } else {
        //default
        if($case=="S") {
            return _('Seconds');
        } else {
            return _('seconds');
        }
    }
}

//
function hectares($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Hektar";
    } else {
        //default
        if($case=="H") {
            return _('Hectares');
        } else {
            return _('hectares');
        }
    }
}
function hectaresabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "ha";
    } else {
        //default
        if($case=="H") {
            return _('Ha');
        } else {
            return _('ha');
        }
    }
}

function ares($case) {
    global $conversion_browser_lang;

    if($case=="A") {
        return _('Ares');
    } else {
        return _('ares');
    }

}
function aresabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="A") {
            return "Ar";
        } else {
            return "ar";
        }
    } else {
        //default
        if($case=="A") {
            return _('A');
        } else {
            return _('a');
        }
    }
}


global $allowedmacros;

// USED FOR LOCAL TESTING
if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "conversionVer", "conversionAbbreviations",  "conversionArea",
    "conversionCapacity", "conversionDisplay", "conversionDisplay2HTML", "conversionDisplay2HTMLwithBorder",
    "conversionFormulaAbbreviations", "conversionFormulaGeometry", "conversionFormulaTemperature",
    "conversionLength", "conversionLiquid", "conversionPrefix", "conversionTime",
    "conversionUnits2ScreenReader1", "conversionUnits2ScreenReader2", "conversionVolume", "conversionWeight",
    "conversion_detectlanguage", "kilo", "kiloabbr", "hecto", "hectoabbr", "meter", "meterabbr", "liter", "literabbr",
    "gram", "gramabbr", "deci", "deciabbr", "centi", "centiabbr", "milli", "milliabbr", "century", "centuryabbr", "centuries",
    "decade", "decades", "year", "yearabbr", "years", "day", "dayabbr", "days", "hour", "hourabbr", "hours", "minute", "minuteabbr",
    "minutes", "second", "secondabbr", "seconds", "metricton", "metrictonabbr", "metrictons", "area", "base", "circumference",
    "diameter", "height", "length", "perimeter", "radius", "width", "volume", "inch", "inches", "foot", "feet", "yard", "yards", 
    "mile", "acres", "acresabbr", "fluidounces", "cup", "cups", "pints", "quarts", "gallons", "ounces", "pounds", "americanton", 
    "americantons");

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

// INTERNAL ONLY DISPLAY Routines
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
    global $conversion_browser_lang;

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
			$retval[0] = inches("I")." = in";
			$retval[1] = feet("F")." = ft";
			$retval[2] = yards("Y")." = yd";
			$retval[3] = miles("M")." = mi";
        } elseif($type=="Capacity"){
			$retval[0] = fluidounces("F")." = fl oz";
			$retval[1] = cups("C")." = c";
			$retval[2] = pints("P")." = pt";
			$retval[3] = quarts("Q")." = qt";
			$retval[4] = gallons("G")." = gal";
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = ounces("O")." = oz";
			$retval[1] = pounds("P")." = lbs";
			$retval[2] = americanton("T")." = T";
        } elseif($type=="Area"){
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = squared("S").inch("i")." = ".conversionUnits2ScreenReader1("","in",2,$tick);
                $retval[1] = squared("S").feet("f")." = ".conversionUnits2ScreenReader1("","ft",2,$tick);
                $retval[2] = squared("S").yard("y")." = ".conversionUnits2ScreenReader1("","yd",2,$tick);
                $retval[3] = squared("S").mile("m")." = ".conversionUnits2ScreenReader1("","mi",2,$tick);
            } else {
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
            }

        } elseif($type=="Volume"){
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = cubic("C").inch("i")." = ".conversionUnits2ScreenReader1("","in",3,$tick);
                $retval[1] = cubic("C").feet("f")." = ".conversionUnits2ScreenReader1("","ft",3,$tick);
                $retval[2] = cubic("C").yard("y")." = ".conversionUnits2ScreenReader1("","yd",3,$tick);
            } else {
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

	}

	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="M"){
		if($type=="Length"){
			$retval[0] = milli("M").meter("m")." = ".milliabbr("m").meterabbr("m");
			$retval[1] = centi("C").meter("m")." = ".centiabbr("c").meterabbr("m");
			$retval[2] = deci("D").meter("m")." = ".deciabbr("d").meterabbr("m");
			$retval[3] = meter("M")." = ".meterabbr("m");
			$retval[4] = deca("D").meter("m")." = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),1,"n");
			$retval[5] = hecto("H").meter("m")." = ".hectoabbr("h").meterabbr("m");
			$retval[6] = kilo("K").meter("m")." = ".kiloabbr("k").meterabbr("m");
        } elseif($type=="Capacity"){
			$retval[0] = milli("M").liter("l")." = ".milliabbr("m").literabbr("L");
			$retval[1] = centi("C").liter("l")." = ".centiabbr("c").literabbr("L");
			$retval[2] = deci("D").liter("l")." = ".deciabbr("d").literabbr("L");
			$retval[3] = liter("L")." = ".literabbr("L");
			$retval[4] = deca("D").liter("l")." = ".conversionUnits2ScreenReader1("",decaabbr("d").literabbr("L"),1,"n");
			$retval[5] = hecto("H").liter("l")." = ".hectoabbr("h").literabbr("L");
			$retval[6] = kilo("K").liter("l")." = ".kiloabbr("k").literabbr("L");
        } elseif(($type=="Weight")||($type=="Mass")){
			$retval[0] = milli("M").gram("g")." = ".milliabbr("m").gramabbr("g");
			$retval[1] = centi("C").gram("g")." = ".centiabbr("c").gramabbr("g");
			$retval[2] = deci("D").gram("g")." = ".deciabbr("d").gramabbr("g");
			$retval[3] = gram("G")." = ".gramabbr("g");
			$retval[4] = deca("D").gram("g")." = ".conversionUnits2ScreenReader1("",decaabbr("d").gramabbr("g"),1,"n");
			$retval[5] = hecto("H").gram("g")." = ".hectoabbr("h").gramabbr("g");
			$retval[6] = kilo("K").gram("g")." = ".kiloabbr("k").gramabbr("g");
			$retval[7] = "Metric ".metricton("T")." = ".metrictonabbr("t");
        } elseif($type=="Area"){
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = squared("S").milli("m").meter("m")." = ".conversionUnits2ScreenReader1("",milliabbr("m").meterabbr("m"),2,$tick);
                $retval[1] = squared("S").centi("c").meter("m")." = ".conversionUnits2ScreenReader1("",centiabbr("c").meterabbr("m"),2,$tick);
                $retval[2] = squared("S").deci("d").meter("m")." = ".conversionUnits2ScreenReader1("",deciabbr("d").meterabbr("m"),2,$tick);
                $retval[3] = squared("S").meter("m")." = ".conversionUnits2ScreenReader1("",meterabbr("m"),2,$tick);
                $retval[4] = squared("S").deca("d").meter("m")." = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),2,$tick);
                $retval[5] = squared("S").hecto("h").meter("m")." = ".conversionUnits2ScreenReader1("",hectoabbr("h").meterabbr("m"),2,$tick);
                $retval[6] = squared("S").kilo("k").meter("m")." = <".conversionUnits2ScreenReader1("",kiloabbr("k").meterabbr("m"),2,$tick);
                $retval[7] = ares("A")." = ".aresabbr("A");
                $retval[8] = centi("C")."ar = ".centiabbr("c").aresabbr("a");
                $retval[9] = deca("D")."r = ".decaabbr("d")."a";
                $retval[10] = hectares("H")." = ".hectaresabbr("h");
            } else {
                if($fullname==0) {
                    $retval[0] = milli("M").meter("m")." squared = ".conversionUnits2ScreenReader1("",milliabbr("m").meterabbr("m"),2,$tick);
                    $retval[1] = centi("C").meter("m")." squared = ".conversionUnits2ScreenReader1("",centiabbr("c").meterabbr("m"),2,$tick);
                    $retval[2] = deci("D").meter("m")." squared = ".conversionUnits2ScreenReader1("",deciabbr("d").meterabbr("m"),2,$tick);
                    $retval[3] = meter("M")." squared = ".conversionUnits2ScreenReader1("",meterabbr("m"),2,$tick);
                    $retval[4] = deca("D").meter("m")." squared = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),2,$tick);
                    $retval[5] = hecto("H").meter("m")." squared = ".conversionUnits2ScreenReader1("",hectoabbr("h").meterabbr("m"),2,$tick);
                    $retval[6] = kilo("K").meter("m")." squared = ".conversionUnits2ScreenReader1("",kiloabbr("k").meterabbr("m"),2,$tick);
                } else {
                    $retval[0] = squared("S")." ".milli("m").meter("m")." = ".conversionUnits2ScreenReader1("",milliabbr("m").meterabbr("m"),2,$tick);
                    $retval[1] = squared("S")." ".centi("c").meter("m")." = ".conversionUnits2ScreenReader1("",centiabbr("c").meterabbr("m"),2,$tick);
                    $retval[2] = squared("S")." ".deci("d").meter("m")." = ".conversionUnits2ScreenReader1("",deciabbr("d").meterabbr("m"),2,$tick);
                    $retval[3] = squared("S")." ".meter("m")." = ".conversionUnits2ScreenReader1("",meterabbr("m"),2,$tick);
                    $retval[4] = squared("S")." ".deca("d").meter("m")." = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),2,$tick);
                    $retval[5] = squared("S")." ".hecto("h").meter("m")." = ".conversionUnits2ScreenReader1("",hectoabbr("h").meterabbr("m"),2,$tick);
                    $retval[6] = squared("S")." ".kilo("k").meter("m")." = <".conversionUnits2ScreenReader1("",kiloabbr("k").meterabbr("m"),2,$tick);
                    $retval[7] = ares("A")." = ".aresabbr("a");
                    $retval[8] = hectares("H")." = ".hectaresabbr("h");
                }
            }
        } elseif($type=="Volume") {
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = cubic("C").milli("m").meter("m")." = ".conversionUnits2ScreenReader1("",milliabbr("m").meterabbr("m"),3,$tick);
                $retval[1] = cubic("C").centi("C").meter("m")." = ".conversionUnits2ScreenReader1("",centiabbr("c").meterabbr("m"),3,$tick);
                $retval[2] = cubic("C").deci("d").meter("m")." = ".conversionUnits2ScreenReader1("",deciabbr("d").meterabbr("m"),3,$tick);
                $retval[3] = cubic("C").meter("m")." = ".conversionUnits2ScreenReader1("",meterabbr("m"),3,$tick);
                $retval[4] = cubic("C").deca("d").meter("m")." = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),3,$tick);
                $retval[5] = cubic("C").hecto("h").meter("m")." = ".conversionUnits2ScreenReader1("",hectoabbr("h").meterabbr("m"),3,$tick);
                $retval[6] = cubic("C").kilo("k").meter("m")." = ".conversionUnits2ScreenReader1("",kiloabbr("k").meterabbr("m"),3,$tick);
            } else {
                if($fullname==0) {
                    $retval[0] = milli("M").meter("m")." cubed = ".conversionUnits2ScreenReader1("",milliabbr("m").meterabbr("m"),3,$tick);
                    $retval[1] = centi("C").meter("m")." cubed = ".conversionUnits2ScreenReader1("",centiabbr("c").meterabbr("m"),3,$tick);
                    $retval[2] = deci("D").meter("m")." cubed = ".conversionUnits2ScreenReader1("",deciabbr("d").meterabbr("m"),3,$tick);
                    $retval[3] = meter("M")." cubed = ".conversionUnits2ScreenReader1("",meterabbr("m"),3,$tick);
                    $retval[4] = deca("D").meter("m")." cubed = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),3,$tick);
                    $retval[5] = hecto("H").meter("m")." cubed = ".conversionUnits2ScreenReader1("",hectoabbr("h").meterabbr("m"),3,$tick);
                    $retval[6] = kilo("K").meter("m")." cubed = ".conversionUnits2ScreenReader1("",kiloabbr("k").meterabbr("m"),3,$tick);
                } else {
                    $retval[0] = cubic("C")." ".milli("m").meter("m")." = ".conversionUnits2ScreenReader1("",milliabbr("m").meterabbr("m"),3,$tick);
                    $retval[1] = cubic("C")." ".centi("C").meter("m")." = ".conversionUnits2ScreenReader1("",centiabbr("c").meterabbr("m"),3,$tick);
                    $retval[2] = cubic("C")." ".deci("d").meter("m")." = ".conversionUnits2ScreenReader1("",deciabbr("d").meterabbr("m"),3,$tick);
                    $retval[3] = cubic("C")." ".meter("m")." = ".conversionUnits2ScreenReader1("",meterabbr("m"),3,$tick);
                    $retval[4] = cubic("C")." ".deca("d").meter("m")." = ".conversionUnits2ScreenReader1("",decaabbr("d").meterabbr("m"),3,$tick);
                    $retval[5] = cubic("C")." ".hecto("h").meter("m")." = ".conversionUnits2ScreenReader1("",hectoabbr("h").meterabbr("m"),3,$tick);
                    $retval[6] = cubic("C")." ".kilo("k").meter("m")." = ".conversionUnits2ScreenReader1("",kiloabbr("k").meterabbr("m"),3,$tick);
                }
            }
        }
	}

    // -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	// -------------------------------------------------------------------------------------------------
	if($system=="T"){
        $retval[0] = seconds("S")." = ".secondabbr("s");
		$retval[1] = minutes("M")." = ".minuteabbr("m");
		$retval[2] = hours("H")." = ".hourabbr("h");
		$retval[3] = days("D")." = ".dayabbr("d");
		$retval[4] = years("Y")." = ".yearabbr("y");
		$retval[5] = century("C")." = ".centuryabbr("c");
    }

	return $retval;
}

// conversionTime(Fullname)
// conversionTime() use Abbreviations
// conversionTime("y") use full name
function conversionTime() {
	$args = func_get_args();
    if (count($args)==0) {
        $retval[0] = "1 ".minute("m")." = 60 ".secondabbr("s");
		$retval[1] = "1 ".hour("h")." = 60 ".minuteabbr("m");
		$retval[2] = "1 ".day("d")." = 24 ".hourabbr("h");
		$retval[3] = "1 ".year("y")." = 365 ".dayabbr("d");
		$retval[4] = "1 ".decade("d")." = 10 ".yearabbr("y");
		$retval[5] = "1 ".century("c")." = 100 ".yearabbr("y");
    } else {
        $retval[0] = "1 ".minute("m")." = 60 ".seconds("s");
		$retval[1] = "1 ".hour("h")." = 60 ".minutes("m");
		$retval[2] = "1 ".day("d")." = 24 ".hours("h");
		$retval[3] = "1 ".year("y")." = 365 ".days("d");
		$retval[4] = "1 ".decade("d")." = 10 ".years("y");
		$retval[5] = "1 ".century("c")." = 100 ".years("y");
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
    global $conversion_browser_lang;

	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no system type supplied.<br/>\r\n";
		return "";
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system." is not a valid type. The system type is American, Metric, or Time";
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
            $retval[0] = conversionUnits2ScreenReader2("1 ",kiloabbr("k").meterabbr("m"),2,"100 ",hectoabbr("h").meterabbr("m"),2,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ",hectoabbr("h").meterabbr("m"),2,"100 ",decaabbr("d").meterabbr("m"),2,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ",decaabbr("d").meterabbr("m"),2,"100 ",meterabbr("m"),2,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ",meterabbr("m"),2,"100 ",deciabbr("d").meterabbr("m"),2,"=",$tick);
            $retval[4] = conversionUnits2ScreenReader2("1 ",deciabbr("d").meterabbr("m"),2,"100 ",centiabbr("c").meterabbr("m"),2,"=",$tick);
            $retval[5] = conversionUnits2ScreenReader2("1 ",centiabbr("c").meterabbr("m"),2,"100 ",milliabbr("m").meterabbr("m"),2,"=",$tick);
            $retval[6] = conversionUnits2ScreenReader2("1 ",aresabbr("a"),1,"100 ",meterabbr("m"),2,"=",$tick);
            $retval[7] = conversionUnits2ScreenReader2("1 ",hectaresabbr("h"),1,"100 ",aresabbr("a"),1,"=",$tick);
        } else {
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = "1 ".squared("S").kilo("k").meter("m")." = 100 ".squared("S").hecto("H").meter("m");
                $retval[1] = "1 ".squared("S").hecto("h").meter("m")." squared = 100 ".squared("S").deca("D").meter("m");
                $retval[2] = "1 ".squared("S").deca("d").meter("m")." squared = 100 ".squared("S").meter("M");
                $retval[3] = "1 ".squared("S").meter("m")." squared = 100 ".squared("S").deci("D").meter("m");
                $retval[4] = "1 ".squared("S").deci("d").meter("m")." squared = 100 ".squared("S").centi("C").meter("m");
                $retval[5] = "1 ".squared("S").centi("c").meter("m")." squared = 100 ".squared("S").milli("M").meter("m");
                $retval[6] = "1 ".ares("A")." = 100 ".squared("S").meter("m");
                $retval[7] = "1 Zentiar = 0.01 ".ares("A");
                $retval[8] = "1 Dekar = 10 ".ares("A");
                $retval[9] = "1 ".hectares("H")." = 100 ".ares("A");
            } else {
                if($fullname==1) {
                    $retval[0] = "1 ".kilo("K").meter("m")." squared = 100 ".hecto("H").meter("m")." squared";
                    $retval[1] = "1 ".hecto("H").meter("m")." squared = 100 ".deca("D").meter("m")." squared";
                    $retval[2] = "1 ".deca("D").meter("m")." squared = 100 ".meter("M")." squared";
                    $retval[3] = "1 ".meter("M")." squared = 100 ".deci("D").meter("m")." squared";
                    $retval[4] = "1 ".deci("D").meter("m")." squared = 100 ".centi("C").meter("m")." squared";
                    $retval[5] = "1 ".centi("C").meter("m")." squared = 100 ".milli("M").meter("m")." squared";
                    $retval[6] = "1 ".ares("A")." = 100 ".meter("m")." squared";
                    $retval[7] = "1 ".hectares("H")." = 100 ".ares("A");
                } else  {
                    $retval[0] = "1 Square ".kilo("k").meter("m")." = 100 Square ".hecto("h").meter("m");
                    $retval[1] = "1 Square ".hecto("h").meter("m")." = 100 Square ".deca("d").meter("m");
                    $retval[2] = "1 Square ".deca("d").meter("m")." = 100 Square ".meter("m");
                    $retval[3] = "1 Square ".meter("m")." = 100 Square ".deci("d").meter("m");
                    $retval[4] = "1 Square ".deci("d").meter("m")." = 100 Square ".centi("c").meter("m");
                    $retval[5] = "1 Square ".centi("c").meter("m")." = 100 Square ".milli("m").meter("m");
                    $retval[6] = "1 ".ares("A")." = 100 Square ".meter("m")." ";
                    $retval[7] = "1 ".hectares("H")." = 100 ".ares("A");
                }
            }
        }
	} elseif($system=="AM"){
        //6.45160000 cm^2 https://www.wolframalpha.com/input/?i=convert+1+square+inch+to+mm+squared
        $CF = round(6.4516, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","in",2,"$CF ","cm",2,$sign_no,$tick);
        } else {
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = "1 ".squared("S").inch("i")." $sign $CF ".squared("S").centi("c").meter("m");
            } else {
                if($fullname==1) {
                    $retval[0] = "1 Inch squared $sign $CF ".centi("C").meter("m")." squared";
                } else {
                    $retval[0] = "1 Square inch $sign $CF Square ".centi("c").meter("m");
                }
            }
        }
	} elseif($system=="MA"){
        // 1.19599005 yd^2 https://www.wolframalpha.com/input/?i=convert+1+square+meter+to+square+feet
        // https://www.wolframalpha.com/input/?i=convert+1+hectares+to+square+feet
        $CF0 = round(1.19599005, $rounding);
        $CF1 = round(2.471, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","m",2,"$CF0 ","yd",2,$sign_no,$tick);
            $retval[1] = "1 ".hectaresabbr("h")." $sign $CF1 ".acresabbr("a");
        } else {
            if($conversion_browser_lang == 'de-de') {
                $retval[0] = "1 ".squared("S").meter("m")." $sign $CF0 ".squared("S").yard("y");
                $retval[1] = "1 ".hectares("H")." $sign $CF1 ".acres("a");
            } else {
                if($fullname==1) {
                    $retval[0] = "1 ".meter("M")." squared $sign $CF0 Yard squared";
                    $retval[1] = "1 ".hectares("H")." $sign $CF1 ".acres("a");
                } else {
                    $retval[0] = "1 ".squared("S")." ".meter("m")." $sign $CF0 ".squared("S")." ".yard("y");
                    $retval[1] = "1 ".hectares("H")." $sign $CF1 ".acres("a");
                }
            }
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
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system." is not a valid type. The system type is American, Metric, or Time";
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
            $retval[0] = "1 ".kiloabbr("k").literabbr("L")." = 1000 ".literabbr("L");
            $retval[1] = "1 ".hectoabbr("h").literabbr("L")." = 100 ".literabbr("L");
            $retval[2] = conversionUnits2ScreenReader1("1 ",decaabbr("d").literabbr("L"),1,"n")." = 10 ".literabbr("L");
            $retval[3] = "1 ".literabbr("L")." = 10 ".deciabbr("d").literabbr("L");
            $retval[4] = "1 ".literabbr("L")." = 100 ".centiabbr("c").literabbr("L");
			$retval[5] = "1 ".literabbr("L")." = 1000 ".milliabbr("m").literabbr("L");
        } else {
            $retval[0] = "1 ".kilo("k").liter("l")." = 1000 ".liter("L");
            $retval[1] = "1 ".hecto("h").liter("l")." = 100 ".liter("L");
            $retval[2] = "1 ".deca("d").liter("l")." = 10 ".liter("L");
            $retval[3] = "1 ".liter("L")." = 10 ".deci("d").liter("l");
            $retval[4] = "1 ".liter("L")." = 100 ".centi("c").liter("l");
            $retval[5] = "1 ".liter("L")." = 1000 ".milli("m").liter("l");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 fl oz $sign ".round(0.0295735296, $rounding)." ".literabbr("L");
            $retval[1] = "1 c $sign ".round(0.236588236, $rounding)." ".literabbr("L");
            $retval[2] = "1 pt $sign ".round(0.473176473, $rounding)." ".literabbr("L");
            $retval[3] = "1 qt $sign ".round(0.946352946, $rounding)." ".literabbr("L");
			$retval[4] = "1 gal $sign ".round(3.78541178, $rounding)." ".literabbr("L");
        } else {
			$retval[0] = "1 fluid ounces $sign ".round(0.0295735296, $rounding)." ".liter("L");  // 29.5735296 mL  https://www.wolframalpha.com/input/?i=convert+1+fluid+ounce+to+liters
            $retval[1] = "1 cup $sign ".round(0.236588236, $rounding)." ".liter("L");  // 236.588236 mL  https://www.wolframalpha.com/input/?i=convert+1+cup+to+liters
            $retval[2] = "1 pint $sign ".round(0.473176473, $rounding)." ".liter("L");  // 473.176473 mL  https://www.wolframalpha.com/input/?i=convert+1+pint+to+liters
            $retval[3] = "1 quart $sign ".round(0.946352946, $rounding)." ".liter("L");   // 946.352946 mL https://www.wolframalpha.com/input/?i=convert+1+quart+to+liters
			$retval[4] = "1 gallon $sign ".round(3.78541178, $rounding)." ".liter("L");  // 3.78541178 L https://www.wolframalpha.com/input/?i=convert+1+gallon+to+milliliters
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 ".literabbr("L")." $sign ".round(33.8140227, $rounding)." fl oz";  // 33.8140227 fl oz (fluid ounces)  https://www.wolframalpha.com/input/?i=convert+1+liter+to+pints
            $retval[1] = "1 ".literabbr("L")." $sign ".round(4.22675284, $rounding)." c"; //  2.11337642 pints *2
            $retval[2] = "1 ".literabbr("L")." $sign ".round(2.11337642, $rounding)." pt";    // 2.11337642 pints   https://www.wolframalpha.com/input/?i=convert+1+liter+to+fluid+ounces
            $retval[3] = "1 ".literabbr("L")." $sign ".round(1.05668821, $rounding)." qt";    // 1.05668821 quarts
			$retval[4] = "1 ".literabbr("L")." $sign ".round(0.264172052, $rounding)." gal";  // 0.264172052 gallons
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

// Geometery Functions --------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
//

function area($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Fläche";
    } else {
        //default
        if($case=="A") {
            return _('Area');
        } else {
            return _('area');
        }
    }
}
function base($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Basis";
    } else {
        //default
        if($case=="B") {
            return _('Base');
        } else {
            return _('base');
        }
    }
}
function circumference($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Umfang";
    } else {
        //default
        if($case=="C") {
            return _('Circumference');
        } else {
            return _('circumference');
        }
    }
}
function cubic($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="C") {
            return "Kubik";
        } else {
            return "kubik";
        }
    } else {
        //default
        if($case=="C") {
            return _('Cubic');
        } else {
            return _('cubic');
        }
    }
}
function diameter($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Durchmesser";
    } else {
        //default
        if($case=="D") {
            return _('Diameter');
        } else {
            return _('diameter');
        }
    }
}
function height($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return htmlentities("Höhe",ENT_QUOTES | ENT_IGNORE | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401 | ENT_XML1 | ENT_XHTML | ENT_HTML5, "ISO-8859-1");
    } else {
        //default
        if($case=="H") {
            return _('Height');
        } else {
            return _('height');
        }
    }
}
function length($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return htmlentities("Länge",ENT_QUOTES | ENT_IGNORE | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401 | ENT_XML1 | ENT_XHTML | ENT_HTML5, "ISO-8859-1");
    } else {
        //default
        if($case=="L") {
            return _('Length');
        } else {
            return _('length');
        }
    }
}
function perimeter($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Durchmesser";
    } else {
        //default
        if($case=="P") {
            return _('Perimeter');
        } else {
            return _('perimeter');
        }
    }
}
function radius($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Radius";
    } else {
        //default
        if($case=="R") {
            return _('Radius');
        } else {
            return _('radius');
        }
    }
}
function side($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Seite";
    } else {
        //default
        if($case=="S") {
            return _('Side');
        } else {
            return _('side');
        }
    }
}
function surfacearea($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return htmlentities("Oberfläche",ENT_QUOTES | ENT_IGNORE | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401 | ENT_XML1 | ENT_XHTML | ENT_HTML5, "ISO-8859-1");
    } else {
        //default
        if($case=="S") {
            return _('Surface Area');
        } else {
            return _('surface area');
        }
    }
}
function squared($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="S") {
            return "Quadrat";
        } else {
            return "quadrat";
        }
    } else {
        //default
        if($case=="S") {
            return _('Surface Area');
        } else {
            return _('surface area');
        }
    }
}
function width($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Breite";
    } else {
        //default
        if($case=="W") {
            return _('Width');
        } else {
            return _('width');
        }
    }
}
function volume($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return "Volumen";
    } else {
        //default
        if($case=="V") {
            return _('Volume');
        } else {
            return _('volume');
        }
    }
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
		$firstPart = "C";  // Circle is the default
	} else {
        $firstPart = strtoupper(substr((string)$args[0], 0, 1));
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
        $retval[0] = "C = ".circumference("C"); // of a circle
        $retval[1] = "A = ".area("A");
        $retval[2] = "r = ".radius("R");
        $retval[3] = "d = ".diameter("D");
    } elseif($type=="Rectangle") {
        $retval[0] = "P = ".perimeter("P");
        $retval[1] = "A = ".area("A");
        $retval[2] = "L = ".length("L");
        $retval[3] = "W = ".width("W");
    } elseif($type=="Square") {
        $retval[0] = "P = ".perimeter("P");
        $retval[1] = "A = ".area("A");
        $retval[2] = "s = ".side("s");
    } elseif($type=="Area") {
        $retval[0] = "SA = ".surfacearea("S");
        $retval[1] = "L = ".length("L");
        $retval[2] = "W = ".width("W");
        $retval[3] = "H or h = ".height("H");
        $retval[4] = "s = ".side("s");
        $retval[5] = "r = ".radius("R");
    } elseif($type=="Volume") {
        $retval[0] = "V = ".volume("V");
        $retval[1] = "L = ".length("L");
        $retval[2] = "W = ".width("W");
        $retval[3] = "H or h = ".height("H");
        $retval[4] = "s = ".side("s");
        $retval[5] = "r = ".radius("R");
    } elseif($type=="Triangle") {
        $retval[0] = "P = ".perimeter("P");
        $retval[1] = "A = ".area("A");
        $retval[2] = "b = ".base("B");
        $retval[3] = "h = ".height("H");
    } elseif($type=="Temperature") {
        $retval[0] = "C = Celsius";
        $retval[1] = "F = Fahrenheit";
        $retval[2] = "K = Kelvin";
    } else {
        $retval[0] = "'".(string)$type."' is not a valid type.";
    }

	return $retval;
}

// Geometery of a Functions --------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
//
function ofacube() {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return htmlentities(" eines Würfels",ENT_QUOTES | ENT_IGNORE | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401 | ENT_XML1 | ENT_XHTML | ENT_HTML5, "ISO-8859-1");
    } else {
        //default
        return _(' of a Cube');
    }
}
function ofarectangularsolid() {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return htmlentities(" eines rechteckigen Festkörpers",ENT_QUOTES | ENT_IGNORE | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401 | ENT_XML1 | ENT_XHTML | ENT_HTML5, "ISO-8859-1");
    } else {
        //default
        return _(' of a Rectangular Solid');
    }
}
function ofasphere() {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return " einer Kugel";
    } else {
        //default
        return _(' of a Sphere');
    }
}
function ofarightcircularcylinder() {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        return " eines geschlossenen Zylinders";
    } else {
        //default
        return _(' of a Right Circular Cylinder');
    }
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
        $retval[0] = "P = add all sides";
        $retval[1] = "{$tick}A = 1/2bh{$tick}";
    } elseif($type=="Rectangle") {
        $retval[0] = "{$tick}P = 2W+2L{$tick}";
        $retval[1] = "{$tick}A = LW{$tick}";
    } elseif($type=="Square") {
        $retval[0] = "{$tick}P = 4s{$tick}";
        $retval[1] = "{$tick}A = s^2{$tick}";
    } elseif($type=="SurfaceArea") {
        $retval[0] = "{$tick}SA=2LW+2LH+2WH{$tick} (".surfacearea("S").ofarectangularsolid().")";
        $retval[1] = conversionUnits2ScreenReader2("","SA",1,"6","s",2,"=",$tick)." (".surfacearea("S")." ".ofacube().")";
        $retval[2] = conversionUnits2ScreenReader2("","SA",1,"4{$PI}","r",2,"=",$tick)." (".surfacearea("S").ofasphere().")";
        $retval[3] = conversionUnits2ScreenReader2("","SA",1,"2{$PI}rh+2{$PI}","r",2,"=",$tick)." (".surfacearea("S").ofarightcircularcylinder().")";
    } elseif($type=="Volume") {
        $retval[0] = "{$tick}V = LWH{$tick} (".volume("V").ofarectangularsolid().")";
        $retval[1] = conversionUnits2ScreenReader2("","V",1,"","s",3,"=",$tick)." (".volume("V")." ".ofacube().")";
        $retval[2] = conversionUnits2ScreenReader2("","V",1,"4/3{$PI}","r",3,"=",$tick)." (".volume("V").ofasphere().")";
        $retval[3] = conversionUnits2ScreenReader2("","V",1,"{$PI}h","r",2,"=",$tick)." (".volume("V").ofarightcircularcylinder().")";
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
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system." is not a valid type. The system type is American, Metric, or Time";
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
            $retval[0] = "1 ".kiloabbr("k").meterabbr("m")." = 1000 ".meterabbr("m");
            $retval[1] = "1 ".hectoabbr("h").meterabbr("m")." = 100 m";
            $retval[2] = conversionUnits2ScreenReader1("1 ",decaabbr("d").meterabbr("m"),1,"n")." = 10 ".meterabbr("m");
            $retval[3] = "1 ".meterabbr("m")." = 10 ".deciabbr("d").meterabbr("m");
            $retval[4] = "1 ".meterabbr("m")." = 100 ".centiabbr("c").meterabbr("m");
            $retval[5] = "1 ".meterabbr("m")." = 1000 ".milliabbr("m").meterabbr("m");
        } else {
            $retval[0] = "1 ".kilo("k").meter("m")." = 1000 ".meter("m");
            $retval[1] = "1 ".hecto("h").meter("m")." = 100 ".meter("m");
            $retval[2] = "1 ".deca("d").meter("m")."  = 10 ".meter("m");
            $retval[3] = "1 ".meter("m")." = 10 ".deci("d").meter("m");
            $retval[4] = "1 ".meter("m")." = 100 ".centi("c").meter("m");
            $retval[5] = "1 ".meter("m")." = 1000 ".milli("m").meter("m");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 in $sign ".round(2.54, $rounding)." ".centi("c").meter("m");;     // https://www.wolframalpha.com/input/?i=convert+1+inch+to+mm
            $retval[1] = "1 ft $sign ".round(0.3048, $rounding)." ".meter("m");               // https://www.wolframalpha.com/input/?i=convert+1+foot+to+dm
            $retval[2] = "1 yd $sign ".round(0.9144, $rounding)." ".meter("m");               // https://www.wolframalpha.com/input/?i=convert+1+yard+to+dm
            $retval[3] = "1 mi $sign ".round(1.60934400, $rounding)." ".kilo("k").meter("m"); // 1.60934400 km https://www.wolframalpha.com/input/?i=convert+1+mile+to+m
        } else {
			$retval[0] = "1 inch $sign ".round(2.54, $rounding)." ".centi("c").meter("m");
            $retval[1] = "1 foot $sign ".round(0.3048, $rounding)." ".meter("m");
            $retval[2] = "1 yard $sign ".round(0.9144, $rounding)." ".meter("m");
            $retval[3] = "1 mile $sign ".round(1.60934400, $rounding)." ".kilo("k").meter("m");
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 ".centiabbr("c").meterabbr("m")." $sign ".round(0.393700787, $rounding)." in";    // 393.700787 mils https://www.wolframalpha.com/input/?i=convert+1+centimeter+to+inch
            $retval[1] = "1 ".meterabbr("m")." $sign ".round(3.28083990, $rounding)." ft"; // 3.28083990 feet https://www.wolframalpha.com/input/?i=convert+1+meter+to+inch
            $retval[2] = "1 ".meterabbr("m")." $sign ".round(1.0936133, $rounding)." yd";  // 3.28083990 feet divided by 3
            $retval[3] = "1 ".kiloabbr("k").meterabbr("m")." $sign ".round(0.621371, $rounding)." mi";   // 621371 miles https://www.wolframalpha.com/input/?i=convert+1000000+kilometer+to+miles
        } else {
			$retval[0] = "1 ".centi("c").meter("m")." $sign ".round(0.393700787, $rounding)." inch";
            $retval[1] = "1 ".meter("m")." $sign ".round(3.28083990, $rounding)." feet";
            $retval[2] = "1 ".meter("m")." $sign ".round(1.0936133, $rounding)." yard";
            $retval[3] = "1 ".kilo("k").meter("m")." $sign ".round(0.621371, $rounding)." mile";
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
	} else {
        $system = strtoupper($args[0]);
        if($system!='C' ) {
            echo (string)$system." is not a valid type. The system type is Casks";
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
        $type = strtoupper($args[0]);
    }


    if ( count($args)>1 && !is_null($args[1]) ) {
        $ShowAbb = verifyFullName($args[1]);
    } else {
        $ShowAbb = 0;
    }

    $retval = array();

	if($ShowAbb == 0) {
        $retval[0] = kilo("K");
        $retval[1] = hecto("H");
        $retval[2] = deca("D");
        if($type == "G") {
            $retval[3] = gram("G") ;
        } elseif($type == "L") {
            $retval[3] =  liter("L");
        } else {
            $retval[3] = meter("M");
        }

        $retval[4] = deci("D");
        $retval[5] = centi("C");
        $retval[6] = milli("M");
    } else {
        $retval[0] = kilo("K")." (".kiloabbr("k").")";
        $retval[1] = hecto("H")." (".hectoabbr("h").")";
        $retval[2] = deca("D")." (<span aria-hidden=true>".decaabbr("d")."</span><span class=\"sr-only\">".decaabbrsr("d")."</span>)";
        if($type == "G") {
            $retval[3] = gram("G")." (".gramabbr("g").")";
        } elseif($type == "L") {
            $retval[3] = liter("L")." (".literabbr("l").")";
        } else {
            $retval[3] = meter("M")." (".meterabbr("m").")";
        }

        $retval[4] = deci("D")." (".deciabbr("d").")";
        $retval[5] = centi("C")." (".centiabbr("c").")";
        $retval[6] = milli("M")." (".milliabbr("m").")";
    }

	return $retval;
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
	} else {
        $system = strtoupper($args[0]);
        if($system!='A' && $system!='M' && $system!='AM' && $system!='MA' ) {
            echo (string)$system." is not a valid type. The system type is American, Metric, or Time";
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
            $retval[0] = conversionUnits2ScreenReader2("1 ",kiloabbr("k").meterabbr("m"),3,"1000 ",hectoabbr("h").meterabbr("m"),3,"=",$tick);
            $retval[1] = conversionUnits2ScreenReader2("1 ",hectoabbr("h").meterabbr("m"),3,"1000 ",decaabbr("d").meterabbr("m"),3,"=",$tick);
            $retval[2] = conversionUnits2ScreenReader2("1 ",decaabbr("d").meterabbr("m"),3,"1000 ",meterabbr("m"),3,"=",$tick);
            $retval[3] = conversionUnits2ScreenReader2("1 ",meterabbr("m"),3,"1000 ",deciabbr("d").meterabbr("m"),3,"=",$tick);
            $retval[4] = conversionUnits2ScreenReader2("1 ",deciabbr("d").meterabbr("m"),3,"1000 ",centiabbr("c").meterabbr("m"),3,"=",$tick);
			$retval[5] = conversionUnits2ScreenReader2("1 ",centiabbr("c").meterabbr("m"),3,"1000 ",milliabbr("m").meterabbr("m"),3,"=",$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 ".kilo("K").meter("m")." cubed = 1000 ".hecto("H").meter("m")."  cubed";
            $retval[1] = "1 ".hecto("H").meter("m")." cubed = 1000 ".deca("D").meter("m")." cubed";
            $retval[2] = "1 ".deca("D").meter("m")." cubed = 1000 ".meter("M")." cubed";
            $retval[3] = "1 ".meter("M")." cubed = 1000 ".deci("D").meter("m")." cubed";
            $retval[4] = "1 ".deci("D").meter("m")." cubed = 1000 ".centi("C").meter("m")." cubed";
			$retval[5] = "1 ".centi("C").meter("m")." cubed = 1000 ".milli("M").meter("m")." cubed";
        } else  {
			$retval[0] = "1 Cubic ".kilo("k").meter("m")." = 1000 Cubic ".hecto("h").meter("m");
            $retval[1] = "1 Cubic ".hecto("h").meter("m")." cubed = 1000 Cubic ".deca("d").meter("m");
            $retval[2] = "1 Cubic ".deca("d").meter("m")." cubed = 1000 Cubic ".meter("m");
            $retval[3] = "1 Cubic ".meter("m")." cubed = 1000 Cubic ".deci("d").meter("m");
            $retval[4] = "1 Cubic ".deci("d").meter("m")." cubed = 1000 Cubic ".centi("c").meter("m");
			$retval[5] = "1 Cubic ".centi("c").meter("m")." cubed = 1000 Cubic ".milli("m").meter("m");
        }
	} elseif($system=="AM"){
        // 0.0163870640 L https://www.wolframalpha.com/input/?i=convert+1+cubic+inch+to+ml
        $CF = round(16.3870640, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ","in",3,"$CF ",milliabbr("m").literabbr("L"),1,$sign_no ,$tick);
        } elseif($fullname==1) {
			$retval[0] = "1 Inch cubed $sign $CF ".milli("M").liter("l");
        } else {
			$retval[0] = "1 Cubic inch $sign $CF ".milli("M").liter("l");
        }
	} elseif($system=="MA"){
        // 61.0237441 in^3  https://www.wolframalpha.com/input/?i=convert+1+liter+to+cubic+feet
        $CF = round(61.0237441, $rounding);
		if($fullname==0) {
			$retval[0] = conversionUnits2ScreenReader2("1 ",literabbr("L"),1,"$CF ","in",3,$sign_no ,$tick);
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

// Metric Weight Functions --------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------
//

function metricton($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="T") {
            return "Tonne";
        } else {
            return "tonne";
        }
    } else {
        //default
        if($case=="T") {
            return _('Tonne');
        } else {
            return _('tonne');
        }
    }
}
function metrictonabbr($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="T") {
            return "T";
        } else {
            return "t";
        }
    } else {
        //default
        if($case=="T") {
            return _('T');
        } else {
            return _('t');
        }
    }
}
function metrictons($case) {
    global $conversion_browser_lang;

    if($conversion_browser_lang == 'de-de') {
        if($case=="T") {
            return "Tonnen";
        } else {
            return "tonnen";
        }
    } else {
        //default
        if($case=="T") {
            return _('Tonnes');
        } else {
            return _('tonnes');
        }
    }
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
            echo (string)$system." is not a valid type. The system type is American, Metric, or Time";
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
            $retval[0] = "1 lb = 16 oz";
            $retval[1] = "1 T = 2000 lbs";
        } else {
            $retval[0] = "1 pound = 16 ounces";
            $retval[1] = "1 Ton = 2000 pounds";
        }
	} elseif($system=="M"){
		if($fullname==0) {
            $retval[0] = "1 ".kiloabbr("k").gramabbr("g")." = 1000 g";
            $retval[1] = "1 ".hectoabbr("h").gramabbr("g")." = 100 g";
            $retval[2] = conversionUnits2ScreenReader1("1 ","dag",1,"n")." = 10 g";
            $retval[3] = "1 ".gramabbr("g")." = 10 ".deciabbr("d").gramabbr("g");
            $retval[4] = "1 ".gramabbr("g")." = 100 ".centiabbr("c").gramabbr("g");
			$retval[5] = "1 ".gramabbr("g")." = 1000 ".milliabbr("m").gramabbr("g");
			$retval[6] = "1 ".metricton("T")." = 1000 ".kiloabbr("k").gramabbr("g");
        } else {
            $retval[0] = "1 ".kilo("k").gram("g")." = 1000 ".gram("g");
            $retval[1] = "1 ".hecto("h").gram("g")." = 100 ".gram("g");
            $retval[2] = "1 ".deca("d").gram("g")." = 10 ".gram("g");
            $retval[3] = "1 ".gram("g")." = 10 ".deci("d").gram("g");
            $retval[4] = "1 ".gram("g")." = 100 ".centi("c").gram("g");
            $retval[5] = "1 ".gram("g")." = 1000 ".milli("m").gram("g");
			$retval[6] = "1 Metric ".metricton("T")." = 1000".kilo("k").gram("g");
        }
	} elseif($system=="AM"){
		if($fullname==0) {
			$retval[0] = "1 oz $sign ".round(28.3495231, $rounding)." ".gramabbr("g");    // 0.0283495231 kg https://www.wolframalpha.com/input/?i=convert+1+ounce+to+gram
            $retval[1] = "1 lbs $sign ".round(0.453592370, $rounding)." ".kiloabbr("k").gramabbr("g"); // 0.453592370 kg https://www.wolframalpha.com/input/?i=convert+1+pound+to+gram
        } else {
			$retval[0] = "1 ounces $sign ".round(28.3495231, $rounding)." ".gram("g");
            $retval[1] = "1 pound $sign ".round(0.453592370, $rounding)." ".kilo("k").gram("g");
        }
	} elseif($system=="MA"){
		if($fullname==0) {
			$retval[0] = "1 ".gram("g")." $sign ".round(0.035274, $rounding)." oz";
            $retval[1] = "1 ".kiloabbr("k").gramabbr("g")." $sign ".round(2.20462, $rounding)." lbs";
        } else {
			$retval[0] = "1 ".gram("g")." $sign ".round(0.035274, $rounding)." ounces";
            $retval[1] = "1 ".kilo("k").gram("g")." $sign ".round(2.20462, $rounding)." pound";
        }
	} else {
        $retval[0] = "'".(string)$system."' is not a valid type.";
    }

	return $retval;
}

// 2022-05-15 ver 24 - Bug fixes.
// 2022-05-15 ver 23 - Bug fixes for offset error when checking for function augments. Added abbreviation in functions that were missed.
// 2022-05-11 ver 22 - Added German words to the file.
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