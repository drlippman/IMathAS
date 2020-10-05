<?php
// Units checking
// Contributed by Nick Chura
// 
// Based in part on https://github.com/openwebwork/pg/blob/master/lib/Units.pm, GPL licensed

global $allowedmacros;

array_push($allowedmacros, 'parseunits');

function parseunits($unitsExpression) {
    //A unit expression should be of the form [decimal number]*[unit]^[power]*[unit]^[power]... / [unit]^[power]*[unit]^[power]...
    //Factors can also be numerical, including scientific notation.
    //All factors after division symbol are contained in denominator.
    //Function changes unit expressions into a $numerical factor and a $unitArray of powers of fundamental units.
    $numerical=1; //Initiates numerical value of expression
    $unitArray=[0,0,0,0,0,0,0,0,0,0]; //Initiates array of exponents of the fundamental units
    
    //Fundamental units are: kilograms,meters,seconds,radians,degrees Celsius,degrees Fahrenheit,degrees Kelvin,moles,amperes,candelas)
    $baseunits=['kg','m','sec','rad','degC','degF','degK','mol','amp','cd'];
    
    //Units array: 'unit' => [numerical factor, [array of factors of fundamental units], prefixtype]
    // prefixtype: 0 no prefixes, 1 abbr prefixes, 2 long prefixes, 3 no prefixes allow plural
    $units=[
    //Length
      'm' => [1,array(0,1,0,0,0,0,0,0,0,0), 1],
      'meter' => [1,array(0,1,0,0,0,0,0,0,0,0), 2],
      'micron' => [1E-6,array(0,1,0,0,0,0,0,0,0,0), 3],
      'angstrom' => [1E-10,array(0,1,0,0,0,0,0,0,0,0), 2],
      'fermi' => [1E-15,array(0,1,0,0,0,0,0,0,0,0), 3],
      'in' => [0.0254,array(0,1,0,0,0,0,0,0,0,0), 0],
      'inch' => [0.0254,array(0,1,0,0,0,0,0,0,0,0), 0],
      'inches' => [0.0254,array(0,1,0,0,0,0,0,0,0,0), 0],
      'ft' => [0.3048,array(0,1,0,0,0,0,0,0,0,0), 0],
      'foot' => [0.3048,array(0,1,0,0,0,0,0,0,0,0), 0],
      'feet' => [0.3048,array(0,1,0,0,0,0,0,0,0,0), 0],
      'mi' => [1609.344,array(0,1,0,0,0,0,0,0,0,0), 0],
      'mile' => [1609.344,array(0,1,0,0,0,0,0,0,0,0), 3],
      'furlong' => [201.168,array(0,1,0,0,0,0,0,0,0,0), 3],
      'yd' => [0.9144,array(0,1,0,0,0,0,0,0,0,0), 0],
      'yard' => [0.9144,array(0,1,0,0,0,0,0,0,0,0), 3],
    //Time
      's' => [1,array(0,0,1,0,0,0,0,0,0,0), 1],
      'sec' => [1,array(0,0,1,0,0,0,0,0,0,0), 0],
      'second' => [1,array(0,0,1,0,0,0,0,0,0,0), 2],
      'min' => [60,array(0,0,1,0,0,0,0,0,0,0), 0],
      'minute' => [60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'h' => [60*60,array(0,0,1,0,0,0,0,0,0,0), 0],
      'hr' => [60*60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'hour' => [60*60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'day' => [24*60*60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'week' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'mo' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0), 0], //assumes 30 days
      'month' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'yr' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0), 0],
      'year' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0), 3],
      'fortnight' => [1209600,array(0,0,1,0,0,0,0,0,0,0), 3],
    //Area
      'acre' => [4046.86,array(0,2,0,0,0,0,0,0,0,0), 3],
      'ha' => [1E4,array(0,2,0,0,0,0,0,0,0,0), 0],
      'hectare' => [1E4,array(0,2,0,0,0,0,0,0,0,0), 3],
      'b' => [1E-28,array(0,2,0,0,0,0,0,0,0,0), 1], //barn
      'barn' => [1E-28,array(0,2,0,0,0,0,0,0,0,0), 2],
    //Volume
      'L' => [0.001,array(0,3,0,0,0,0,0,0,0,0), 1],
      'liter' => [0.001,array(0,3,0,0,0,0,0,0,0,0), 2],
      'litre' => [0.001,array(0,3,0,0,0,0,0,0,0,0), 2],
      'cc' => [1E-6,array(0,3,0,0,0,0,0,0,0,0), 0],
      'gal' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0), 0],
      'gallon' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0), 3],
      'cup' => [0.000236588,array(0,3,0,0,0,0,0,0,0,0), 3],
      'pint' => [0.000473176,array(0,3,0,0,0,0,0,0,0,0), 3],
      'quart' => [0.000946353,array(0,3,0,0,0,0,0,0,0,0), 3],
      'tbsp' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0), 0], //U.S. tablespoon
      'tablespoon' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0), 3],
      'tsp' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0), 0], //U.S. teaspoon
      'teaspoon' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0), 3],
    //Angles
      'rad' => [1,array(0,0,0,1,0,0,0,0,0,0), 0],
      'radian' => [1,array(0,0,0,1,0,0,0,0,0,0), 3],
      'deg' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0), 0],
      'degree' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0), 3],
      'gradian' => [0.015708,array(0,0,0,1,0,0,0,0,0,0), 3],
    //Velocity
      'knot' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0), 3],
      'kt' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0), 0],
      'c' => [299792458,array(0,1,-1,0,0,0,0,0,0,0), 0], // Speed of light
      'mph' => [0.44704,array(0,1,-1,0,0,0,0,0,0,0), 0],
      'kph' => [0.277778,array(0,1,-1,0,0,0,0,0,0,0), 0],
    //Mass
      'g' => [0.001,array(1,0,0,0,0,0,0,0,0,0), 1],
      'gram' => [0.001,array(1,0,0,0,0,0,0,0,0,0), 2],
      'tonne' => [1000,array(1,0,0,0,0,0,0,0,0,0), 2],
    //Frequency
      'hz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0), 1],
      'hertz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0), 2],
      'rev' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0), 3],
      'revolution' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0), 3],
      'cycle' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0), 3],
    //Force
      'N' => [1,array(1,1,-2,0,0,0,0,0,0,0), 1],
      'newton' => [1,array(1,1,-2,0,0,0,0,0,0,0), 2],
      'kip' => [4448.22,array(1,1,-2,0,0,0,0,0,0,0), 3],
      'dyne' => [1E-5,array(1,1,-2,0,0,0,0,0,0,0), 2],
      'lb' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0), 3],
      'pound' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0), 3],
      'ton' => [8896.443,array(1,1,-2,0,0,0,0,0,0,0), 3],
    //Energy
      'J' => [1,array(1,2,-2,0,0,0,0,0,0,0), 1],
      'joule' => [1,array(1,2,-2,0,0,0,0,0,0,0), 2],
      'erg' => [1E-7,array(1,2,-2,0,0,0,0,0,0,0), 2],
      'lbf' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0), 0],
      'lbft' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0), 0],
      'ftlb' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0), 0],
      'cal' => [4.184,array(1,2,-2,0,0,0,0,0,0,0), 1],
      'calorie' => [4.184,array(1,2,-2,0,0,0,0,0,0,0), 2],
      'ev' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0), 1],
      'electronvolt' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0), 2],
      'wh' => [3.6E3,array(1,2,-2,0,0,0,0,0,0,0), 1], //kiloWatthour
      'btu' => [1055.06,array(1,2,-2,0,0,0,0,0,0,0), 1], //British thermal units
    //Power
      'W' => [1,array(1,2,-3,0,0,0,0,0,0,0), 1],
      'watt' => [1,array(1,2,-3,0,0,0,0,0,0,0), 2],
      'hp' => [746,array(1,2,-3,0,0,0,0,0,0,0), 0],
      'horsepower' => [746,array(1,2,-3,0,0,0,0,0,0,0), 3],
    //Pressure
      'pa' => [1,array(1,-1,-2,0,0,0,0,0,0,0), 1],
      'pascal' => [1,array(1,-1,-2,0,0,0,0,0,0,0), 2],
      'atm' => [1.01E5,array(1,-1,-2,0,0,0,0,0,0,0), 3],
      'atmosphere' => [1.01E5,array(1,-1,-2,0,0,0,0,0,0,0), 3],
      'bar' => [100000,array(1,-1,-2,0,0,0,0,0,0,0), 1],
      'bars' => [100000,array(1,-1,-2,0,0,0,0,0,0,0), 1],
      'barometer' => [100000,array(1,-1,-2,0,0,0,0,0,0,0), 3],
      'torr' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0), 0],
      'mmHg' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0), 0],
      'cmWater' => [98.0638,array(1,-1,-2,0,0,0,0,0,0,0), 0], //This comes from a cmH2O preg_replace
      'psi' => [98.0638,array(1,-1,-2,0,0,0,0,0,0,0), 0],
    //Electrical Units
      'C' => [1,array(0,0,1,0,0,0,0,0,1,0), 1],
      'coulomb' => [1,array(0,0,1,0,0,0,0,0,1,0), 2],
      'V' => [1,array(1,2,-3,0,0,0,0,0,1,0), 1],
      'volt' => [1,array(1,2,-3,0,0,0,0,0,1,0), 2],
      'farad' => [1,array(-1,-2,4,0,0,0,0,0,2,0), 2],
      'ohm' => [1,array(1,2,-3,0,0,0,0,0,-2,0), 2],
      'A' => [1,array(0,0,0,0,0,0,0,0,1,0), 1],
      'amp' => [1,array(0,0,0,0,0,0,0,0,1,0), 2],
      'ampere' => [1,array(0,0,0,0,0,0,0,0,1,0), 2],
    //Magnetic Units
      'T' => [1,array(1,0,-2,0,0,0,0,0,-1,0), 1],
      'tesla' => [1,array(1,0,-2,0,0,0,0,0,-1,0), 2],
      'G' => [1,array(1,0,-2,0,0,0,0,0,-1,0), 1],
      'gauss' => [1,array(1,0,-2,0,0,0,0,0,-1,0), 2],
      'wb' => [1,array(1,2,-2,0,0,0,0,0,-1,0), 1],
      'weber' => [1,array(1,2,-2,0,0,0,0,0,-1,0), 2],
      'H' => [1,array(1,2,-2,0,0,0,0,0,-2,0), 1],
      'henry' => [1,array(1,2,-2,0,0,0,0,0,-2,0), 2],
    //Luminosity
      'lm' => [1,array(0,0,0,-2,0,0,0,0,0,1), 1],
      'lumen' => [1,array(0,0,0,-2,0,0,0,0,0,1), 2],
      'lx' => [1,array(0,-2,0,-2,0,0,0,0,0,1), 1],
      'lux' => [1,array(0,-2,0,-2,0,0,0,0,0,1), 2],
    //Atomic Units
      'amu' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0), 0], //atomic mass unit
      'dalton' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0), 3],
      'me' => [9.1093826E-31,array(1,0,0,0,0,0,0,0,0,0), 0], //electron resting mass
    //Other science units
      'mol' => [1,array(0,0,0,0,0,0,0,1,0,0), 1],
      'mole' => [1,array(0,0,0,0,0,0,0,1,0,0), 2],
      'Ci' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0), 1], //curie
      'curie' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0), 2],
      'R' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0), 0], //roentgen
      'roentgen' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0), 3],
      'sr' => [1,array(0,0,0,2,0,0,0,0,0,0), 0], //steradian
      'steradian' => [1,array(0,0,0,2,0,0,0,0,0,0), 3],
      'bq' => [1,array(0,0,-1,0,0,0,0,0,0,0), 0], //becquerel
      'bequerel' => [1,array(0,0,-1,0,0,0,0,0,0,0), 3],
    //Astronomy Units
      'ls' => [299792458,array(0,1,0,0,0,0,0,0,0,0), 1],
      'lightsecond' => [299792458,array(0,1,0,0,0,0,0,0,0,0), 2],
      'ly' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0), 1],
      'lightyear' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0), 2],
      'au' => [149597870700,array(0,1,0,0,0,0,0,0,0,0), 1],
      'pc' => [3.08567758149137E16,array(0,1,0,0,0,0,0,0,0,0), 1],
      'parsec' => [3.08567758149137E16,array(0,1,0,0,0,0,0,0,0,0), 2],
      'solarmass' => [1.98892E30,array(1,0,0,0,0,0,0,0,0,0), 0],
      'solarradius' => [6.955E8,array(0,1,0,0,0,0,0,0,0,0), 0],
    //Temperature
      'degF' => [1,array(0,0,0,0,0,1,0,0,0,0), 0],
      'degC' => [1,array(0,0,0,0,1,0,0,0,0,0), 1],
      'degK' => [1,array(0,0,0,0,0,0,1,0,0,0), 1],
    ];
    
    //Standard metric prefixes with associated numerical factors
    $unitPrefix = [
      'yotta' => ['yotta',1E24],
      'zetta' => ['zetta',1E21],
      'exa' => ['exa',1E18],
      'peta' => ['peta',1E15],
      'tera' => ['tera',1E12],
      'giga' => ['giga',1E9],
      'mega' => ['mega',1E6],
      'kilo' => ['kilo',1E3],
      'hecto' => ['hecto',100],
      'deka' => ['deka',10],
      'deci' => ['deci',0.1],
      'centi' => ['centi',0.01],
      'milli' => ['milli',1E-3],
      'micro' => ['micro',1E-6],
      'nano' => ['nano',1E-9],
      'pico' => ['pico',1E-12],
      'fempto' => ['fempto',1E-15],
      'atto' => ['atto',1E-18],
      'zepto' => ['zepto',1E-21],
      'yocto' => ['yocto',1E-24]
    ];
    $unitAbbrPrefix = [
        'Y' => ['yotta',1E24],
        'Z' => ['zetta',1E21],
        'E' => ['exa',1E18],
        'P' => ['peta',1E15],
        'T' => ['tera',1E12],
        'G' => ['giga',1E9],
        'M' => ['mega',1E6],
        'k' => ['kilo',1E3],
        'h' => ['hecto',100],
        'd' => ['deci',0.1],
        'c' => ['centi',0.01],
        'm' => ['milli',1E-3],
        'u' => ['micro',1E-6],
        'n' => ['nano',1E-9],
        'p' => ['pico',1E-12],
        'f' => ['fempto',1E-15],
        'a' => ['atto',1E-18],
        'z' => ['zepto',1E-21],
        'y' => ['yocto',1E-24]
      ];
    $abbrPrefixes = array_keys($unitAbbrPrefix);

    //Search string for metric prefixes.
    $unitPrefixPattern = '/yotta|zetta|exa|peta|tera|giga|mega|kilo|hecto|deka|deci|centi|milli|micro|nano|pico|fempto|atto|zepto|yocto/';
    
    $unitsExpression = trim($unitsExpression);
    
    //Special case of unit 'micron'.
    $unitsExpression = preg_replace('/(microns?)([^a-zA-Z]|$)/','micrometer$2',$unitsExpression);
    
    //Special case of unit "cmH2O".
    $unitsExpression = preg_replace('/cmH2O/','cmWater',$unitsExpression);
    
    $unitsExpression = preg_replace('/\s{2,}/',' ',$unitsExpression); //no double spaces
    $unitsExpression = preg_replace('/(\d+\.?\d*|\.\d+)\s*E\s*([\-]?\d+)/','$1*10^$2',$unitsExpression); //scientific notation
    $unitsExpression = preg_replace('/(\d+\.?\d*|\.\d+)\s*E\s*[\+]?(\d+)/','$1*10^$2',$unitsExpression);
    $unitsExpression = preg_replace('/\s*(\/|\^|\-)\s*/','$1',$unitsExpression); //trims space around division, exponent and dash symbols
    $unitsExpression = preg_replace('/\*\*/','^',$unitsExpression); //interprets double multiplication as exponentiation
    $unitsExpression = preg_replace('/(\d)([a-zA-Z])/','$1*$2',$unitsExpression); //interprets number next to letter as multiplication
    $unitsExpression = preg_replace('/([a-zA-Z])(\d)/','$1*$2',$unitsExpression); //Not sure if this is standard notation.
    $unitsExpression = preg_replace('/([0-9])(\.)([a-zA-Z])/','$1$2*$3',$unitsExpression); //allows numerical factor to end in a decimal point
    $unitsExpression = preg_replace('/(\s*\-\s*)([a-zA-Z])/','*$2',$unitsExpression); //interprets dash as multiplication
    $unitsExpression = preg_replace('/([a-zA-Z])(\s*\-\s*)/','$1*',$unitsExpression); //Not sure if this is standard notation.
    $unitsExpression = preg_replace('/\(\s*(.*?)\s*\)\s*\//', '$1/', $unitsExpression); // strip paren around numerator
    $unitsExpression = preg_replace('/\/\s*\(\s*(.*?)\s*\)/', '/$1', $unitsExpression); // strip paren around denom
    $unitsExpression = preg_replace('/\s*[\*\s]\s*/','*',$unitsExpression); //trims space around multiplication symbol, spaces become *

    // unconvert E so is_numeric will recognize it
    $unitsExpression = preg_replace('/(\d+\.?\d*|\.\d+)\s*\*\s*10\s*\^\s*([\-]?\d+)/','$1E$2',$unitsExpression);

    $unitsFormatMessage='Eek! Units must be given as [decimal number]*[unit]^[power]*[unit]^[power].../[unit]^[power]*[unit]^[power]...';
    $unitsDivisionMessage='Eek! Only one division symbol allowed in the expression.';
    $unitsSymbolMessage='Eek! Improper symbol or operation used. Expressions can only use decimal numbers, letters, multiplication, division and exponents. No parentheses allowed.';
    
    $unitsBadSyntax='/[\(\)]|\^\^|\*\*|\^\*|\*\^|\*\*|\-\-|\-\*|\*\-|\-\^|\d\-\d|\s\.\s|\.\.|\d\.\d\.|\.[\d\.\*]\.|[a-zA-Z]\.|\*\.[a-zA-Z]|\*[\*\.]\*|\d\-\d|\d\+\d/'; //bad combinations of symbols.
    $unitsBadSymbols='/[^a-zA-Z\d\*\/\^\.\-]/'; //expression can only contain letters, numbers, multiplication, division, exponents, decimals and negative symbol
    $unitsStartLike='/^[a-zA-Z0-9\.\-]/'; //expression must start with one of these symbols
    $unitsEndLike='/[a-zA-Z0-9\.]$/'; //expression must end with one of these symbols
    
    if ($unitsExpression==='0' || empty($unitsExpression)) {
      $numerical=0;
      return 0;
    }
    if (preg_match($unitsBadSymbols,$unitsExpression)) {
      echo $unitsSymbolMessage;
      return '';
    }
    if (preg_match($unitsBadSyntax,$unitsExpression)) {
      echo $unitsFormatMessage;
      return '';
    }
    if (preg_match($unitsStartLike,$unitsExpression)==0 || preg_match($unitsEndLike,$unitsExpression)==0) {
      echo $unitsFormatMessage;
      return '';
    }

    $parts=explode('/',$unitsExpression);
    if (count($parts)>2) {
      echo $unitsDivisionMessage;
      return '';
    } elseif (count($parts)==1) {
      $numerator=$parts[0];
      $denominator='';
    } elseif (count($parts)==2) {
      $numerator=$parts[0];
      $denominator=$parts[1];
    }
    
    $numerParts=explode('*',$numerator);
    $denomParts=explode('*',$denominator);
    
    $numerPartsTmp=[];
    $baseNumber = '';
    foreach($numerParts as $k => $part) {
      if (is_numeric($part)) {
          if ($baseNumber == '') {
              $baseNumber = $part;
          } else {
              echo $unitsFormatMessage;
              return '';
          }
      } else {
        array_push($numerPartsTmp,$part);
      }
    }
    $numerParts=$numerPartsTmp; //Has only factors that cannot be computed (like units).
  
    $denomPartsTmp=[];
    foreach($denomParts as $k => $part) {
      if (is_numeric($part)) {
        echo $unitsFormatMessage;
        return '';
      } else if ($part !== ''){
        array_push($denomPartsTmp,$part);
      }
    }
    $denomParts=$denomPartsTmp; //Has only factors that cannot be computed (like units).
    
    $numerExpand=[]; //Initiates the expanded array of units.
    $denomExpand=[];
    
    if (!empty($numerParts)) {
      foreach ($numerParts as $k=>$part) { //Expand all factors from numerator, put in numer or denom array.
        if (preg_match('/\^[^\d\.\-]/',$part)) {
          echo 'Eek! Exponents can only be numbers.';
          return '';
        }
        if (preg_match('/^[a-zA-Z]+$/',$part)) {
          $part = preg_replace('/([a-zA-Z]+)/','$1^1',$part); //If unit has no exponent, make it unit^1.
        }
  
        if (preg_match('/^[a-zA-Z]+\^[\-]{0,1}[0-9\.\-]+$/',$part)) {
          $pow=substr($part,strpos($part,'^')+1);
          if (!ctype_digit($pow)) {
            echo 'Eek! Exponents on units must be integers.';
            return '';
          }

          $prefixCount=-1;
          $partPrefix=[];

          $part = substr($part,0,strpos($part,'^')); //Now $part only has letters.

          $hasfullprefix = false;
          while (preg_match($unitPrefixPattern,$part)) { //Does it have a metric prefix?
            $prefixCount = $prefixCount+1;
            if (preg_match($unitPrefixPattern,$part,$matches[$prefixCount])) { //$matches[0][0] catches the first prefix, $matches[1][0] is the 2nd prefix, etc.
                $partPrefix[$prefixCount] = $matches[$prefixCount][0]; //Here is the prefix. [Could be empty!]
                $prefixLength = strlen($matches[$prefixCount][0]);
                $part = substr($part,$prefixLength); //Now $part is just the unit.
                if ($part == '' || empty($part)) {
                echo 'Eek! The prefix \''.$partPrefix[$prefixCount].'\' must be followed by a unit.';
                    return '';
                }
                $hasfullprefix = true;
            }
          }

          $lower = strtolower($part);
          $lowerpart = strtolower($part);
          $subpart = (strlen($part)>1) ? substr($part,1) : '';
          $subpartlower = strtolower($subpart);
          if (isset($units[$part])) {
            // if as-written is a unit we'll use that and nothing more to do
          } else if (isset($units[rtrim($part,'s')]) && $units[rtrim($part,'s')][2]>1) { 
            // if as-written is plural of unit and plural allowedwe'll use that
            $part = rtrim($part,'s');
          } else if (strlen($part)>1 && in_array($part[0], $abbrPrefixes) && 
            isset($units[$subpart]) && $units[$subpart][2] == 1
          ) { // first char is abbr metric prefix and rest is unit that allows abbr prefix
            $prefixCount = $prefixCount+1;
            $partPrefix[$prefixCount] = $unitAbbrPrefix[$part[0]][0];
            $part = $subpart;
          } else if (isset($units[$lower])) {
            // if lowercase is a unit
            // we'll use that and nothing more to do
            $part = $lower;
          } else if (isset($units[rtrim($lower,'s')]) && $units[rtrim($lower,'s')][2]>1) { 
            // if lowercase is plural of a unit and plural allowed
            // we'll use that and nothing more to do
            $part = rtrim($lower,'s');
          } else if (strlen($part)>1 && in_array($part[0], $abbrPrefixes) && 
            isset($units[strtoupper($subpart)]) && $units[strtoupper($subpart)][2] == 1
          ) { // first char is abbr metric prefix and rest uppercased is unit that allows abbr prefix
            $prefixCount = $prefixCount+1;
            $partPrefix[$prefixCount] = $unitAbbrPrefix[$part[0]][0];
            $part = strtoupper($subpart);
          } else if (strlen($lower)>1 && in_array($part[0], $abbrPrefixes) && 
            isset($units[$subpartlower]) && $units[$subpartlower][2] == 1
          ) { // first char is abbr metric prefix and rest lowercase is unit that allows abbr prefix
            $prefixCount = $prefixCount+1;
            $partPrefix[$prefixCount] = $unitAbbrPrefix[$part[0]][0];
            $part = $subpartlower;
          } else {
              echo "Eek, could not find unit";
              return '';
          }
          if ($hasfullprefix && $units[$part][2] != 2) {
              echo "cannot use long prefixes on unit $part";
              return '';
          }
          print_r($partPrefix);
          echo "Unit: $part<br>";

          if ($pow>0) {
            for ($i=-1; $i<($pow-1); $i++) {
              array_push($numerExpand,$part);
              if (!empty($partPrefix)) { //Only look for the prefix factor if there is a prefix.
                for ($j=-1; $j<$prefixCount; $j++) { //Deal with compounded prefixes, such as megamegafeet^2 = megamegafeet * megamegafeet
                  $numerical = $numerical*$unitPrefix[($partPrefix[($j+1)])][1];
                }
              }
            }
          } elseif ($pow<0) {
            for ($i=$pow; $i<0; $i++) {
              array_push($denomExpand,$part);
              if (!empty($partPrefix)) { //Only look for the prefix factor if there is a prefix.
                for ($j=-1; $j<$prefixCount; $j++) {
                  $numerical = $numerical/$unitPrefix[($partPrefix[($j+1)])][1];
                }
              }
            }
          }
        } else {
        echo 'Eek! Error in the numerator.';
        return '';
        }
      }
    }
    
    //Adapt the previous block for the denominator.
    if (!empty($denomParts)) {
      foreach ($denomParts as $k=>$part) { //Expand all factors from denominator, put in numer or denom array.
        if (preg_match('/\^[^\d\.\-]/',$part)) {
          echo 'Eek! Exponents can only be numbers.';
          return '';
        }
        if (preg_match('/^[a-zA-Z]+$/',$part)) {
          $part = preg_replace('/([a-zA-Z]+)/','$1^1',$part); //If unit has no exponent, make it unit^1.
        }
  
        if (preg_match('/^[a-zA-Z]+\^[\-]{0,1}[0-9\.\-]+$/',$part)) {
          $pow=trim(substr($part,strpos($part,'^')+1));
          if (!ctype_digit($pow)) {
            echo 'Eek! Exponents on units must be integers.';
            return '';
          }
          $part = substr($part,0,strpos($part,'^')); //Now $part is the prefix-and-unit.
          $partlower = strtolower($part);
          
          $prefixCount=-1; //The denominator reuses these variable names, so must reset them to blank.
          $partPrefix=[];

        
          
          while (preg_match($unitPrefixPattern,$part)) { //Does it have a metric prefix?
            $prefixCount = $prefixCount+1;
            preg_match($unitPrefixPattern,$part,$matches[$prefixCount]); //$matches[0][0] catches the first prefix, $matches[1][0] is the 2nd prefix, etc.
            $partPrefix[$prefixCount] = $matches[$prefixCount][0];
            $prefixLength = strlen($matches[$prefixCount][0]);
            $part = substr($part,$prefixLength); //Now $part is just the unit.
            if ($part == '' || empty($part)) {
              echo 'Eek! The prefix \''.$partPrefix[$prefixCount].'\' must be followed by a unit.';
              return '';
            }
          }
          if ($partCount > -1) {
            $partPrefix = []; //If factor didn't have a prefix, empty the prefix array.
          }
          if ($pow>0) {
            for ($i=-1; $i<($pow-1); $i++) {
              array_push($denomExpand,$part);
              if (!empty($partPrefix)) { //Only look for the prefix factor if there is a prefix.
                for ($j=-1; $j<$prefixCount; $j++) { //Deal with compounded prefixes, such as megamegafeet^2 = megamegafeet * megamegafeet
                  $numerical = $numerical / $unitPrefix[($partPrefix[($j+1)])][1];
                }
              }
            }
          } elseif ($pow<0) {
            for ($i=$pow; $i<0; $i++) {
              array_push($numerExpand,$part);
              if (!empty($partPrefix)) { //Only look for the prefix factor if there is a prefix.
                for ($j=-1; $j<$prefixCount; $j++) {
                  $numerical = $numerical * $unitPrefix[($partPrefix[($j+1)])][1];
                }
              }
            }
          }
        } else {
        echo 'Eek! Error in the denominator.';
        return '';
        }
      }
    }
    
    //These arrays count duplicated unit factors. Each looks like ['cm'=>3, 'feet'=>2]
    if (!empty($numerExpand)) {
      $numerUnitFactors = array_count_values($numerExpand);
    }
    if (empty($numerExpand)) {
      $numerUnitFactors = ['m' => 0];
    }
    if (!empty($denomExpand)) {
      $denomUnitFactors = array_count_values($denomExpand);
    }
    if (empty($denomExpand)) {
      $denomUnitFactors = ['m' => 0];
    }
  
  //This simplifies all matching fundamental units in numerator and denominator, and it builds the numerical factor
    foreach ($numerUnitFactors as $k => $factor) {
      if (!isset($units[$k])) {
        echo 'Eek! Unknown units: '.$k;
        return '';
      } elseif (isset($units[$k])) {
        for ($i=0;$i<$factor;$i++) {
          $numerical=$numerical*$units[$k][0];
        }
        for ($i=0; $i<10; $i++) {
          $unitArray[$i]=$unitArray[$i]+$factor*$units[$k][1][$i];
        }
      }
    }
    foreach ($denomUnitFactors as $k => $factor) {
      if (!isset($units[$k])) {
        echo 'Eek! Unknown units: '.$k;
        return '';
      } elseif (isset($units[$k])) {
        for ($i=0;$i<$factor;$i++) {
          $numerical=$numerical/$units[$k][0];
        }
        for ($i=0; $i<10; $i++) {
          $unitArray[$i]=$unitArray[$i]-$factor*$units[$k][1][$i];
        }
      }
    }
    
    //At this point, $numerical is the number and $unitArray is the array of factors of fundamental units: e.g. [0,1,-2,0,0,0,0,0,1,0] would mean meter*amp/sec^2 
    //Code block below converts expression in terms of fundamental metric units.
    $unitsExpressionSimple=$numerical; //Build the equivalent, simplifed answer in mks
    if (max($unitArray)>0) {
      foreach ($unitArray as $k => $factor) {
        if ($factor>0) {
          if ($factor==1) {
            $unitsExpressionSimple = $unitsExpressionSimple." ".$baseunits[$k];
          } elseif ($factor>1) {
            $unitsExpressionSimple = $unitsExpressionSimple." ".$baseunits[$k]."^".$factor;
          }
        }
      }
    }
    if (min($unitArray)<0) {
      $unitsExpressionSimple = $unitsExpressionSimple."/";
      foreach ($unitArray as $k => $factor) {
        if ($factor<0) {
          $factorNeg=$factor*-1;
          if ($factor==-1) {
            $unitsExpressionSimple = $unitsExpressionSimple.$baseunits[$k]." ";
          } elseif ($factor<-1) {
            $unitsExpressionSimple = $unitsExpressionSimple.$baseunits[$k]."^".$factorNeg." ";
          }
        }
      }
    }
  //Uncomment next line to show simplified answer written in terms of fundamental metric units. Not sure how/if this functionality will be used in problems.
  //echo $unitsExpressionSimple." ";
  //return array($numerical,$unitArray);
  return array($baseNumber*$numerical, $unitArray, $baseNumber, $numerical);
}

function checkunitssigfigs($givenunits, $ansunits, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
    $givenans = $givenunits[0];
    $anans = $ansunits[0];
    if ($givenans*$anans < 0) { return false;} //move on if opposite signs
    // base this stuff on the baseNumber
	if ($ansunits[2]!=0) {
		$v = -1*floor(-log10(abs($ansunits[2]))-1e-12) - $reqsigfigs;
	}
	if ($sigfigscoretype[0]=='abs') {
		$sigfigscoretype[1] = max(pow(10,$v)/2, $sigfigscoretype[1]);
	} else if ($sigfigscoretype[1]/100 * $ansunits[2] < pow(10,$v)/2) {
        // relative tolerance, but too small
        $sigfigscoretype = ['abs', pow(10,$v)/2];
    }
    $epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
    //base this on baseNumber
	if (strpos($givenunits[2],'E')!==false) {  //handle computer-style scientific notation
		preg_match('/^-?[1-9]\.?(\d*)E/', $givenunits[2], $matches);
		$gasigfig = 1+strlen($matches[1]);
		if ($exactsigfig) {
			if ($gasigfig != $reqsigfigs) {return false;}
		} else {
			if ($gasigfig < $reqsigfigs) {return false;}
			if ($reqsigfigoffset>0 && $gasigfig-$reqsigfigs>$reqsigfigoffset) {return false;}
		}
	} else {
		if (!$exactsigfig) {
			$gadploc = strpos($givenunits[2],'.');
            if ($gadploc===false) {
                $absgivenans = str_replace('-','',$givenunits[2]);
                $gasigfigs = strlen(rtrim($absgivenans,'0'));
                if ($anans != 0 && $v < 0 && $gasigfigs < 1-$v) { return false; } // not enough
                if ($anans != 0 && $reqsigfigoffset>0 && $gasigfigs > 1-$v+$reqsigfigoffset) { return false;} // too many
            } else {
                if ($anans != 0 && $v < 0 && strlen($givenunits[2]) - $gadploc-1 + $v < 0) { return false; } //not enough decimal places
                if ($anans != 0 && $reqsigfigoffset>0 && strlen($givenunits[2]) - $gadploc-1 + $v>$reqsigfigoffset) {return false;} //too many sigfigs
            }
		} else {
			$absgivenans = str_replace('-','',$givenunits[2]);
			$gadploc = strpos($absgivenans,'.');
            if ($gadploc===false) { //no decimal place
                if (strlen(rtrim($absgivenans,'0')) != $reqsigfigs) { return false;}
			} else {
				if (abs($givenunits[2])<1) {
					if (strlen(ltrim(substr($absgivenans,$gadploc+1),'0')) != $reqsigfigs) { return false;}
				} else {
					if (strlen(ltrim($absgivenans,'0'))-1 != $reqsigfigs) { return false;}
				}
			}
		}
    }
    //checked format, now check values, using values in base units
	if ($sigfigscoretype[0]=='abs') {
        // adjust tolerance given unit conversions
        $sigfigscoretype[1] = $sigfigscoretype[1]*$ansunits[3];
		if (abs($anans-$givenans)< $sigfigscoretype[1]+$epsilon) {return true;}
	} else if ($sigfigscoretype[0]=='rel') {
		if ($anans==0) {
			if (abs($anans - $givenans) < $sigfigscoretype[1]+$epsilon) {return true;}
		} else {
			if (abs($anans - $givenans)/(abs($anans)+$epsilon) < $sigfigscoretype[1]/100+$epsilon) {return true;}
		}
	}
	return false;
}

// regex of all units, for possible JS use later:
// \b(yotta|zetta|exa|peta|tera|giga|mega|kilo|hecto|deka|deci|centi|milli|micro|nano|pico|fempto|atto|zepto|yocto)?(m|meters?|km|cm|mm|um|microns?|nm|[aA]ngstroms?|pm|fm|fermi|in|inch|inches|ft|foot|feet|mi|miles?|furlongs?|yd|yards?|s|sec|seconds?|ms|us|ns|min|minutes?|hr|hours?|days?|weeks?|mo|months?|yr|years?|fortnights?|acres?|ha|hectares?|b|barns?|L|liters?|litres?|dL|ml|mL|cc|gal|gallons?|cups?|pints?|quarts?|tbsp|tablespoons?|tsp|teaspoons?|rad|radians?|deg|degrees?|gradians?|knots?|kt|c|mph|kph|kg|g|grams?|mg|tonnes?|k?[hH]z|[hH]ertz|revs?|revolutions?|cycles?|N|[nN]ewtons?|kips?|dynes?|lbs?|pounds?|tons?|[kK]?J|[jJ]oules?|ergs?|lbf|lbft|ftlb|cal|calories?|kcal|eV|electronvolts?|k[wW]h|btu|BTU|W|[wW]atts?|kW|hp|horsepower|Pa|[pP]ascals?|kPa|MPa|GPa|atms?|atmospheres?|bars?|barometers?|mbars?|[tT]orr|mmHg|cmWater|psi|C|[cC]oulombs?|V|[vV]olts?|mV|MV|[fF]arad|ohms?|ohms|amps?|[aA]mperes?|T|[tT]eslas?|G|Gauss|Wb|Weber|H|Henry|lm|lumens?|lx|lux|amu|[dD]altons?|me|mol|mole|Ci|curies?|R|roentgens?|sr|steradians?|Bq|bequerel|ls|lightsecond|ly|lightyears?|AU|au|parsecs?|kpc|solarmass|solarradius|degF|degC|degK|microns?|cmH2O)\b
