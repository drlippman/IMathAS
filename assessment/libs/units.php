<?php
// Units checking
// Contributed by Nick Chura
// 
// Based in part on https://github.com/openwebwork/pg/blob/master/lib/Units.pm, GPL licensed

global $allowedmacros;

array_push($allowedmacros, 'parseunits');

function parseunits($unitsExpression) {
    $origExpression = $unitsExpression;
    //A unit expression should be of the form [decimal number]*[unit]^[power]*[unit]^[power]... / [unit]^[power]*[unit]^[power]...
    //Factors can also be numerical, including scientific notation.
    //All factors after division symbol are contained in denominator.
    //Function changes unit expressions into a $numerical factor and a $unitArray of powers of fundamental units.
    $numerical=1; //Initiates numerical value of expression
    $unitArray=[0,0,0,0,0,0,0,0,0,0]; //Initiates array of exponents of the fundamental units
    
    //Fundamental units are: kilograms,meters,seconds,radians,degrees Celsius,degrees Fahrenheit,degrees Kelvin,moles,amperes,candelas)
    $baseunits=['kg','m','sec','rad','degC','degF','degK','mol','amp','cd'];
    
    //Units array: 'unit' => [numerical factor, [array of factors of fundamental units], prefixtype, pluraltype, casetype]
    // prefixtype: 0 => no prefixes allowed, 1 => short prefixes allowed, 2 => long prefixes allowed
    // pluraltype: 0 => cannot add s at the end, 1 => can add s at the end
    // casetype: 0 => cannot change case, 1 => can change case
    
    $units=[
    //Length
      'm' => [1,array(0,1,0,0,0,0,0,0,0,0),1,0,0],
      'meter' => [1,array(0,1,0,0,0,0,0,0,0,0),2,1,1],
      'metre' => [1,array(0,1,0,0,0,0,0,0,0,0),2,1,1],
      'micron' => [1E-6,array(0,1,0,0,0,0,0,0,0,0),0,1,1],
      'angstrom' => [1E-10,array(0,1,0,0,0,0,0,0,0,0),2,1,1],
      'fermi' => [1E-15,array(0,1,0,0,0,0,0,0,0,0),2,0,1],
      'in' => [0.0254,array(0,1,0,0,0,0,0,0,0,0),0,0,0],
      'inch' => [0.0254,array(0,1,0,0,0,0,0,0,0,0),0,0,1],
      'inches' => [0.0254,array(0,1,0,0,0,0,0,0,0,0),0,0,1],
      'ft' => [0.3048,array(0,1,0,0,0,0,0,0,0,0),1,0,0],
      'foot' => [0.3048,array(0,1,0,0,0,0,0,0,0,0),2,0,1],
      'feet' => [0.3048,array(0,1,0,0,0,0,0,0,0,0),2,0,1],
      'mi' => [1609.344,array(0,1,0,0,0,0,0,0,0,0),0,0,0],
      'mile' => [1609.344,array(0,1,0,0,0,0,0,0,0,0),0,1,1],
      'furlong' => [201.168,array(0,1,0,0,0,0,0,0,0,0),0,1,1],
      'yd' => [0.9144,array(0,1,0,0,0,0,0,0,0,0),0,0,0],
      'yard' => [0.9144,array(0,1,0,0,0,0,0,0,0,0),0,1,1],
    //Time
      's' => [1,array(0,0,1,0,0,0,0,0,0,0),1,0,0],
      'sec' => [1,array(0,0,1,0,0,0,0,0,0,0),2,0,1],
      'second' => [1,array(0,0,1,0,0,0,0,0,0,0),2,1,1],
      'min' => [60,array(0,0,1,0,0,0,0,0,0,0),0,0,0],
      'minute' => [60,array(0,0,1,0,0,0,0,0,0,0),0,1,1],
      'h' => [60*60,array(0,0,1,0,0,0,0,0,0,0),0,0,0],
      'hr' => [60*60,array(0,0,1,0,0,0,0,0,0,0),0,0,0],
      'hour' => [60*60,array(0,0,1,0,0,0,0,0,0,0),0,1,1],
      'day' => [24*60*60,array(0,0,1,0,0,0,0,0,0,0),0,1,1],
      'week' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0),0,1,1],
      'mo' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0),0,0,0], //assumes 30 days
      'month' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0),0,1,1],
      'yr' => [365*24*60*60,array(0,0,1,0,0,0,0,0,0,0),0,0,0],
      'year' => [365*24*60*60,array(0,0,1,0,0,0,0,0,0,0),0,1,1], //assumes 365 days
      'fortnight' => [1209600,array(0,0,1,0,0,0,0,0,0,0),0,1,1],
    //Area
      'acre' => [4046.86,array(0,2,0,0,0,0,0,0,0,0),0,1,1],
      'ha' => [1E4,array(0,2,0,0,0,0,0,0,0,0),0,0,0],
      'hectare' => [1E4,array(0,2,0,0,0,0,0,0,0,0),0,1,1],
      'b' => [1E-28,array(0,2,0,0,0,0,0,0,0,0),1,0,0], //barn
      'barn' => [1E-28,array(0,2,0,0,0,0,0,0,0,0),2,1,1],
    //Volume
      'L' => [0.001,array(0,3,0,0,0,0,0,0,0,0),1,0,1],
      'liter' => [0.001,array(0,3,0,0,0,0,0,0,0,0),2,1,1],
      'litre' => [0.001,array(0,3,0,0,0,0,0,0,0,0),2,1,1],
      'cc' => [1E-6,array(0,3,0,0,0,0,0,0,0,0),1,0,0],
      'gal' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0),0,0,0],
      'gallon' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0),0,1,1],
      'cup' => [0.000236588,array(0,3,0,0,0,0,0,0,0,0),0,1,1],
      'pt' => [0.000473176,array(0,3,0,0,0,0,0,0,0,0),0,0,0],
      'pint' => [0.000473176,array(0,3,0,0,0,0,0,0,0,0),0,1,1],
      'qt' => [0.000946353,array(0,3,0,0,0,0,0,0,0,0),0,0,0],
      'quart' => [0.000946353,array(0,3,0,0,0,0,0,0,0,0),0,1,1],
      'tbsp' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0),0,0,0], //U.S. tablespoon
      'tablespoon' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0),0,1,1],
      'tsp' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0),0,0,0], //U.S. teaspoon
      'teaspoon' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0),0,1,1],
    //Angles
      'rad' => [1,array(0,0,0,1,0,0,0,0,0,0),0,0,0],
      'radian' => [1,array(0,0,0,1,0,0,0,0,0,0),0,1,1],
      'deg' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0),0,0,0],
      'degree' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0),0,1,1],
      'arcminute' => [0.000290888,array(0,0,0,1,0,0,0,0,0,0),2,1,1],
      'arcsecond' => [4.84814E-6,array(0,0,0,1,0,0,0,0,0,0),2,1,1],
      'grad' => [0.015708,array(0,0,0,1,0,0,0,0,0,0),0,0,0],
      'gradian' => [0.015708,array(0,0,0,1,0,0,0,0,0,0),0,1,1],
    //Velocity
      'knot' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0),0,1,1],
      'kt' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0),0,0,0],
      'c' => [299792458,array(0,1,-1,0,0,0,0,0,0,0),0,0,0], // Speed of light
      'mph' => [0.44704,array(0,1,-1,0,0,0,0,0,0,0),0,0,0],
      'kph' => [0.277778,array(0,1,-1,0,0,0,0,0,0,0),0,0,0],
    //Mass
      'g' => [0.001,array(1,0,0,0,0,0,0,0,0,0),1,0,0],
      'gram' => [0.001,array(1,0,0,0,0,0,0,0,0,0),2,1,1],
      'gramme' => [0.001,array(1,0,0,0,0,0,0,0,0,0),2,1,1],
      't' => [1000,array(1,0,0,0,0,0,0,0,0,0),1,0,0],
      'tonne' => [1000,array(1,0,0,0,0,0,0,0,0,0),2,1,1],
    //Frequency
      'Hz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0),1,0,1],
      'hertz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0),2,0,1],
      'rev' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0),0,0,0],
      'revolution' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0),0,1,1],
      'cycle' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0),0,1,1],
    //Force
      'N' => [1,array(1,1,-2,0,0,0,0,0,0,0),1,0,0],
      'newton' => [1,array(1,1,-2,0,0,0,0,0,0,0),2,1,1],
      'kip' => [4448.22,array(1,1,-2,0,0,0,0,0,0,0),0,1,1],
      'dyn' => [1E-5,array(1,1,-2,0,0,0,0,0,0,0),1,0,0],
      'dyne' => [1E-5,array(1,1,-2,0,0,0,0,0,0,0),2,1,1],
      'lb' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0),0,1,1], // treated as pound force
      'pound' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0),2,1,1], // treated as pound force
      'lbf' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0),0,0,0], // treated as pound force
      'ton' => [8896.443,array(1,1,-2,0,0,0,0,0,0,0),2,1,1],
    //Energy
      'J' => [1,array(1,2,-2,0,0,0,0,0,0,0),1,0,0],
      'joule' => [1,array(1,2,-2,0,0,0,0,0,0,0),2,1,1],
      'erg' => [1E-7,array(1,2,-2,0,0,0,0,0,0,0),2,1,1],
      'lbft' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0),0,0,1],
      'ftlb' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0),0,0,1],
      'cal' => [4.184,array(1,2,-2,0,0,0,0,0,0,0),1,0,0],
      'calorie' => [4.184,array(1,2,-2,0,0,0,0,0,0,0),2,1,1],
      'eV' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0),1,0,0],
      'electronvolt' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0),2,1,1],
      'Wh' => [3.6E3,array(1,2,-2,0,0,0,0,0,0,0),1,0,0], //Watthour
      'Btu' => [1055.06,array(1,2,-2,0,0,0,0,0,0,0),1,0,1], //British thermal units
      'therm' => [1055.06E5,array(1,2,-2,0,0,0,0,0,0,0),0,1,1],
    //Power
      'W' => [1,array(1,2,-3,0,0,0,0,0,0,0),1,0,0],
      'watt' => [1,array(1,2,-3,0,0,0,0,0,0,0),2,1,1],
      'hp' => [746,array(1,2,-3,0,0,0,0,0,0,0),1,0,0],
      'horsepower' => [746,array(1,2,-3,0,0,0,0,0,0,0),2,0,1],
    //Pressure
      'Pa' => [1,array(1,-1,-2,0,0,0,0,0,0,0),1,0,0],
      'pascal' => [1,array(1,-1,-2,0,0,0,0,0,0,0),2,1,1],
      'atm' => [1.01325E5,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0],
      'atmosphere' => [1.01325E5,array(1,-1,-2,0,0,0,0,0,0,0),0,1,1],
      'bar' => [100000,array(1,-1,-2,0,0,0,0,0,0,0),3,1,1],
      'Torr' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0),2,0,1],
      'mmHg' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0],
      'umHg' => [133.322E-3,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0], // micrometers of mercury
      'cmWater' => [98.0665,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0], //This comes from a cmH2O preg_replace
      'psi' => [6894.76,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0],
      'ksi' => [6894.76E3,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0], // kilopound per square inch
      'Mpsi' => [6894.76E6,array(1,-1,-2,0,0,0,0,0,0,0),0,0,0], // Megapound per square inch
    //Electrical Units
      'C' => [1,array(0,0,1,0,0,0,0,0,1,0),1,0,0],
      'coulomb' => [1,array(0,0,1,0,0,0,0,0,1,0),2,1,1],
      'V' => [1,array(1,2,-3,0,0,0,0,0,-1,0),1,0,0],
      'volt' => [1,array(1,2,-3,0,0,0,0,0,-1,0),2,1,1],
      'farad' => [1,array(-1,-2,4,0,0,0,0,0,2,0),2,1,1],
      'F' => [1,array(-1,-2,4,0,0,0,0,0,2,0),1,0,0],
      'ohm' => [1,array(1,2,-3,0,0,0,0,0,-2,0),2,1,1],
      'amp' => [1,array(0,0,0,0,0,0,0,0,1,0),2,1,1],
      'ampere' => [1,array(0,0,0,0,0,0,0,0,1,0),2,1,1],
      'A' => [1,array(0,0,0,0,0,0,0,0,1,0),1,0,0],
    //Magnetic Units
      'T' => [1,array(1,0,-2,0,0,0,0,0,-1,0),1,0,0],
      'tesla' => [1,array(1,0,-2,0,0,0,0,0,-1,0),2,1,1],
      'G' => [0.0001,array(1,0,-2,0,0,0,0,0,-1,0),1,0,0],
      'gauss' => [0.0001,array(1,0,-2,0,0,0,0,0,-1,0),2,0,1],
      'Wb' => [1,array(1,2,-2,0,0,0,0,0,-1,0),1,0,0],
      'weber' => [1,array(1,2,-2,0,0,0,0,0,-1,0),2,1,1],
      'H' => [1,array(1,2,-2,0,0,0,0,0,-2,0),1,0,0],
      'henry' => [1,array(1,2,-2,0,0,0,0,0,-2,0),2,1,1],
    //Luminosity
      'lm' => [1,array(0,0,0,-2,0,0,0,0,0,1),1,0,0],
      'lumen' => [1,array(0,0,0,-2,0,0,0,0,0,1),2,1,1],
      'lx' => [1,array(0,-2,0,-2,0,0,0,0,0,1),1,0,0],
      'lux' => [1,array(0,-2,0,-2,0,0,0,0,0,1),2,0,1],
    //Atomic Units
      'amu' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0),0,0,0], //atomic mass unit
      'dalton' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0),2,1,1],
      'Da' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0),1,0,0],
      'me' => [9.1093826E-31,array(1,0,0,0,0,0,0,0,0,0),0,0,0], //electron resting mass
    //Other science units
      'mol' => [1,array(0,0,0,0,0,0,0,1,0,0),1,0,0],
      'mole' => [1,array(0,0,0,0,0,0,0,1,0,0),2,1,1],
      'M' => [1000,array(0,-3,0,0,0,0,0,1,0,0),1,0,0], // Molarity
      'Ci' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0),1,0,0], //curie
      'curie' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0),2,1,1],
      'R' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0),0,0,0], //roentgen
      'roentgen' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0),0,1,1],
      'sr' => [1,array(0,0,0,2,0,0,0,0,0,0),1,0,0], //steradian
      'steradian' => [1,array(0,0,0,2,0,0,0,0,0,0),2,1,1],
      'Bq' => [1,array(0,0,-1,0,0,0,0,0,0,0),1,0,0], //becquerel
      'becquerel' => [1,array(0,0,-1,0,0,0,0,0,0,0),2,1,1],
    //Astronomy Units
      'ls' => [299792458,array(0,1,0,0,0,0,0,0,0,0),1,0,0],
      'lightsecond' => [299792458,array(0,1,0,0,0,0,0,0,0,0),2,1,1],
      'ly' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0),1,0,0],
      'lightyear' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0),2,1,1],
      'AU' => [149597870700,array(0,1,0,0,0,0,0,0,0,0),0,0,0], //astronomical unit
      'au' => [149597870700,array(0,1,0,0,0,0,0,0,0,0),0,0,0],
      'parsec' => [3.08567758149137E16,array(0,1,0,0,0,0,0,0,0,0),2,1,1],
      'pc' => [3.08567758149137E16,array(0,1,0,0,0,0,0,0,0,0),1,0,0], //parsec
      'solarmass' => [1.98892E30,array(1,0,0,0,0,0,0,0,0,0),0,0,1],
      'solarradius' => [6.955E8,array(0,1,0,0,0,0,0,0,0,0),0,0,1],
    //Temperature
      'degF' => [1,array(0,0,0,0,0,1,0,0,0,0),0,0,0],
      'degC' => [1,array(0,0,0,0,1,0,0,0,0,0),0,0,0],
      'degK' => [1,array(0,0,0,0,0,0,1,0,0,0),0,0,0],
      'K' => [1,array(0,0,0,0,0,0,1,0,0,0),0,0,0],
    ];
    $unitKeys = array_keys($units);
    $unitKeysLow = array_map('strtolower',$unitKeys);
    $unitReverse = array_combine($unitKeysLow,array_keys($units));
    
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
      'deca' => ['deka',10],
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
    $unitPrefixKeys = array_keys($unitPrefix);
    
    $unitAbbPrefix = [
      'Y' => ['yotta',1E24],
      'Z' => ['zetta',1E21],
      'E' => ['exa',1E18],
      'P' => ['peta',1E15],
      'T' => ['tera',1E12],
      'G' => ['giga',1E9],
      'M' => ['mega',1E6],
      'k' => ['kilo',1E3],
      'h' => ['hecto',100],
      'da' => ['deka',10],
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
    $unitAbbPrefixKeys = array_keys($unitAbbPrefix);
    
    //Search string for metric prefixes.
    $unitPrefixPattern = '/yotta|zetta|exa|peta|tera|giga|mega|kilo|hecto|deka|deca|deci|centi|milli|micro|nano|pico|fempto|atto|zepto|yocto/i';
    
    $unitsExpression = trim($unitsExpression);
    
    //Special case of unit 'micron'.
    $unitsExpression = preg_replace('/(microns?)([^a-zA-Z]|$)/','micrometer$2',$unitsExpression);
    
    //Special case of unit "cmH2O".
    $unitsExpression = preg_replace('/cmH2O/','cmWater',$unitsExpression);
    
    $unitsExpression = preg_replace('/\sper\s/',' / ',$unitsExpression); // interpret "ft per s" as "ft/s"
    // Change "sq ft" to "ft*ft" and "cu yd" to "yd*yd*yd"
    if (preg_match('/(sq\b|square\b|squared|cu\b|cubed|cubic\b)/', $unitsExpression)) {
      // cubic ft squared => cubic ft^2
      $unitsExpression = preg_replace("~(square|sq|cubic|cu)\s+(\s*[a-zA-Z]\w*\s+)(?:squared)~",'$1 $2^2',$unitsExpression);
      // sq ft cubed => sq ft^3
      $unitsExpression = preg_replace("~(square|sq|cubic|cu)\s+(\s*[a-zA-Z]\w*\s+)(?:cubed)~",'$1 $2^3',$unitsExpression);
      // sq ft^3 => ft^3*ft^3
      $unitsExpression = preg_replace("~(?:sq|square)\s+(\s*[a-zA-Z]\w*\s*)(\s*\^\s*[\+|\-]?\s*\d+)?([^a-zA-Z]|$)~",'$1$2*$1$2$3',$unitsExpression);
      // cu ft^2 => ft^2*ft^2*ft^2
      $unitsExpression = preg_replace("~(?:cu|cubic)\s+(\s*[a-zA-Z]\w*\s*)(\s*\^\s*[\+|\-]?\s*\d+)?([^a-zA-Z]|$)~",'$1$2*$1$2*$1$2$3',$unitsExpression);
      // ft squared => ft*ft or ft^3 squared => ft^3*ft^3
      $unitsExpression = preg_replace("~([a-zA-Z]\w*\s*)(\s*\^\s*[\+|\-]?\s*\d+\s*)?(?:squared)([^a-zA-Z]*)~",'$1 $2*$1 $2$3',$unitsExpression);
      // ft cubed => ft*ft*ft or ft^2 cubed => ft^2*ft^2*ft^2
      $unitsExpression = preg_replace("~([a-zA-Z]\w*\s*)(\s*\^\s*[\+|\-]?\s*\d+\s*)?(?:cubed)([^a-zA-Z]*)~",'$1$2*$1$2*$1$2$3',$unitsExpression);
    }
    $unitsExpression = trim($unitsExpression);
    
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
      if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsSymbolMessage; }
      return '';
    }
    if (preg_match($unitsBadSyntax,$unitsExpression)) {
      if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsFormatMessage; }
      return '';
    }
    if (preg_match($unitsStartLike,$unitsExpression)==0 || preg_match($unitsEndLike,$unitsExpression)==0) {
      if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsFormatMessage; }
      return '';
    }

    $parts=explode('/',$unitsExpression);
    if (count($parts)>2) {
      if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsDivisionMessage; }
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
              if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsFormatMessage; }
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
        if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsFormatMessage; }
        return '';
      } else if ($part !== ''){
        array_push($denomPartsTmp,$part);
      }
    }
    $denomParts=$denomPartsTmp; //Has only factors that cannot be computed (like units).
    
    $numerExpand=[]; //Initiates the expanded array of units.
    $denomExpand=[];
    
    $prefixWasAbb = false;
    if (!empty($numerParts)) {
      foreach ($numerParts as $k=>$part) { //Expand all factors from numerator, put in numer or denom array.
        if (preg_match('/\^[^\d\.\-]/',$part)) {
          if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Exponents can only be numbers.'; }
          return '';
        }
        if (preg_match('/^[a-zA-Z]+$/',$part)) {
          $part = preg_replace('/([a-zA-Z]+)/','$1^1',$part); //If unit has no exponent, make it unit^1.
        }
  
        if (preg_match('/^[a-zA-Z]+\^[\-]{0,1}[0-9\.\-]+$/',$part)) {
          $pow=substr($part,strpos($part,'^')+1);
          if (floor(evalMathParser($pow))!=evalMathParser($pow)||isNaN(evalMathParser($pow))) {
            if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Exponents on units must be integers.'; }
            return '';
          }
          $part = substr($part,0,strpos($part,'^')); //Now $part only has letters.
          
          // Only look for prefixes, plural and case if part isn't already a unit
          // Don't want ft to be interpreted as femptotesla
          if (!in_array($part,$unitKeys)) {
            // Does it have (what could be) an abbreviated prefix?
            $hasAbbCheck = false;
            if (substr($part,0,2) === 'da') {
              $abbCheck = 'da';
              $partNoAbb = substr($part,2);
              $hasAbbCheck = true;
            } elseif (in_array($part[0],$unitAbbPrefixKeys)) {
              $abbCheck = $part[0];
              $partNoAbb = substr($part,1);
              $hasAbbCheck = true;
            }
            if ($hasAbbCheck) {
              $partNoAbbLow = strtolower($partNoAbb);
              if (in_array($partNoAbbLow,$unitKeysLow)) {
                $part = $unitAbbPrefix[$abbCheck][0].$partNoAbb;
                $prefixWasAbb = true;
              }
            }
          }
          $prefixCount=-1;
          $partPrefix=[];
          // Does it have a metric prefix? Is that okay?
          while (preg_match($unitPrefixPattern,$part)) {
            $prefixCount ++;
            preg_match($unitPrefixPattern,$part,$matches[$prefixCount]); //$matches[0][0] catches the first prefix, $matches[1][0] is the 2nd prefix, etc.
            $partPrefix[$prefixCount] = strtolower($matches[$prefixCount][0]); //Here is the prefix. [Could be empty!]
            $prefixLength = strlen($partPrefix[$prefixCount]);
            $part = substr($part,$prefixLength); //Now $part is just the unit.

            if ($part == '' || empty($part)) {
              if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! The prefix \''.$partPrefix[$prefixCount].'\' must be followed by a unit.'; }
              return '';
            }
          }
          $partLow = strtolower($part);
          // Is the unit plural? Is that okay?
          if (substr($partLow,-1) === 's' && in_array(rtrim($partLow, 's'),$unitKeysLow)) {
            $partNos = substr($part,0,strlen($part)-1);
            $partLowNos = strtolower($partNos);
            if (isset($units[$unitReverse[$partLowNos]])) {
              if ($units[$unitReverse[$partLowNos]][3] != 1) {
                if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unit \''.$partNos.'\' cannot be pluralized with an \'s\'.'; }
                return '';
              }
            } else {
              if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unknown unit \''.$part.'\'.'; }
              return '';
            }
            $part = $partNos;
            $partLow = $partLowNos;
          }
            
          if ($prefixCount > -1 && $partLow != '') {
            if (!in_array($partLow,$unitKeysLow)) {
              if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unknown unit \''.$part.'\'.'; }
              return '';
            }
          }
          // Was the prefix abbreviated? Is that okay?
          if ($prefixCount > -1) {
            if ($prefixWasAbb) {
              if (($units[$unitReverse[$partLow]][2])%2 == 0) {
                if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unit \''.$part.'\' cannot use prefix \''.$abbCheck.'\'.'; }
                return '';
              }
            } elseif (!$prefixWasAbb) {
              if (($units[$unitReverse[$partLow]][2]) < 2) {
                if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unit \''.$part.'\' cannot use prefix \''.$partPrefix[0].'\'.'; }
                return '';
              }
            }
          }
          // Is the case different? Is that okay?
          if (in_array($partLow,$unitKeysLow)) {
            if ($part !== $unitReverse[$partLow]) {
              if ($units[$unitReverse[$partLow]][4] == 1) {
                $part = $unitReverse[$partLow];
              }
            }
          }
          
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
        if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Error in the numerator.'; }
        return '';
        }
      }
    }
    
    //Adapt the previous block for the denominator.
    $prefixWasAbb = false;
    if (!empty($denomParts)) {
      foreach ($denomParts as $k=>$part) { //Expand all factors from denominator, put in numer or denom array.
        if (preg_match('/\^[^\d\.\-]/',$part)) {
          if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Exponents can only be numbers.'; }
          return '';
        }
        if (preg_match('/^[a-zA-Z]+$/',$part)) {
          $part = preg_replace('/([a-zA-Z]+)/','$1^1',$part); //If unit has no exponent, make it unit^1.
        }
  
        if (preg_match('/^[a-zA-Z]+\^[\-]{0,1}[0-9\.\-]+$/',$part)) {
          $pow=substr($part,strpos($part,'^')+1);
          if (floor(evalMathParser($pow))!=evalMathParser($pow)||isNaN(evalMathParser($pow))) {
            if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Exponents on units must be integers.'; }
            return '';
          }
          $part = substr($part,0,strpos($part,'^')); //Now $part is the prefix-and-unit.
          
          // Only look for prefixes if part isn't already a unit
          // Don't want ft to be interpreted as femptotesla
          if (!in_array($part,$unitKeys)) {
            // Does it have (what could be) an abbreviated prefix?
            $hasAbbCheck = false;
            if (substr($part,0,2) === 'da') {
              $abbCheck = 'da';
              $partNoAbb = substr($part,2);
              $hasAbbCheck = true;
            } elseif (in_array($part[0],$unitAbbPrefixKeys)) {
              $abbCheck = $part[0];
              $partNoAbb = substr($part,1);
              $hasAbbCheck = true;
            }
            if ($hasAbbCheck) {
              $partNoAbbLow = strtolower($partNoAbb);
              if (in_array($partNoAbbLow,$unitKeysLow)) {
                $part = $unitAbbPrefix[$abbCheck][0].$partNoAbb;
                $prefixWasAbb = true;
              }
            }
          }
          $prefixCount=-1;
          $partPrefix=[];
          // Does it have a metric prefix? Is that okay?
          while (preg_match($unitPrefixPattern,$part)) {
            $prefixCount ++;
            preg_match($unitPrefixPattern,$part,$matches[$prefixCount]); //$matches[0][0] catches the first prefix, $matches[1][0] is the 2nd prefix, etc.
            $partPrefix[$prefixCount] = strtolower($matches[$prefixCount][0]); //Here is the prefix. [Could be empty!]
            $prefixLength = strlen($partPrefix[$prefixCount]);
            $part = substr($part,$prefixLength); //Now $part is just the unit.

            if ($part == '' || empty($part)) {
              if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! The prefix \''.$partPrefix[$prefixCount].'\' must be followed by a unit.'; }
              return '';
            }
          }
          $partLow = strtolower($part);
          
          // Is the unit plural? Is that okay?
          if (substr($partLow,-1) === 's' && in_array(rtrim($partLow, 's'),$unitKeysLow)) {
            $partNos = substr($part,0,strlen($part)-1);
            $partLowNos = strtolower($partNos);
            if (isset($units[$unitReverse[$partLowNos]])) {
              if ($units[$unitReverse[$partLowNos]][3] != 1) {
                if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unit \''.$partNos.'\' cannot be pluralized with an \'s\'.'; }
                return '';
              }
            } else {
              if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unknown unit \''.$part.'\'.'; }
              return '';
            }
            $part = $partNos;
            $partLow = $partLowNos;
          }

          if ($prefixCount > -1 && $partLow != '') {
            if (!in_array($partLow,$unitKeysLow)) {
              if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unknown unit \''.$part.'\'.'; }
              return '';
            }
          }
          // Was the prefix abbreviated? Is that okay?
          if ($prefixCount > -1) {
            if ($prefixWasAbb) {
              if (($units[$unitReverse[$partLow]][2])%2 == 0) {
                if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unit \''.$part.'\' cannot use prefix \''.$abbCheck.'\'.'; }
                return '';
              }
            } elseif (!$prefixWasAbb) {
              if (($units[$unitReverse[$partLow]][2]) < 2) {
                if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unit \''.$part.'\' cannot use prefix \''.$partPrefix[0].'\'.'; }
                return '';
              }
            }
          }
          // Is the case different? Is that okay?
          if (in_array($partLow,$unitKeysLow)) {
            if ($part !== $unitReverse[$partLow]) {
              if ($units[$unitReverse[$partLow]][4] == 1) {
                $part = $unitReverse[$partLow];
              }
            }
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
        if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Error in the denominator.'; }
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
        if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unknown units: '.$k; }
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
        if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Unknown units: '.$k; }
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
    if (!is_numeric($baseNumber) || !is_numeric($numerical)) {
        if (!empty($GLOBALS['inQuestionTesting'])) { echo 'Eek! Was unable to parse units'; }
        return '';
    }
    //At this point, $numerical is the number and $unitArray is the array of factors of fundamental units: e.g. [0,1,-2,0,0,0,0,0,1,0] would mean meter*amp/sec^2 
    //Code block below converts expression in terms of fundamental metric units.
    $unitsExpressionSimple=$baseNumber*$numerical; //Build the equivalent, simplifed answer in mks
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
  // if (!empty($GLOBALS['inQuestionTesting'])) { echo $unitsExpressionSimple." "; }
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
	
    $epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
    //base this on baseNumber
  if (strpos($givenunits[2], 'E') !== false) { //handle computer-style scientific notation
      preg_match('/^-?[1-9]\.?(\d*)E/', $givenunits[2], $matches);
      $gasigfig = 1 + strlen($matches[1]);
      if ($exactsigfig) {
          if ($gasigfig != $reqsigfigs) {return false;}
      } else {
          if ($gasigfig < $reqsigfigs) {return false;}
          if ($reqsigfigoffset > 0 && $gasigfig - $reqsigfigs > $reqsigfigoffset) {return false;}
      }
  } else {
    if (!$exactsigfig) {
        $absgivenans = str_replace('-','',$givenunits[2]);
        $gadploc = strpos($absgivenans, '.');
        if ($gadploc === false) {
          if ($anans != 0 && strlen($absgivenans) < $reqsigfigs) { return false; } //not enough digits
          if ($anans != 0 && $reqsigfigoffset>0 && strlen(rtrim($absgivenans,'0')) > $reqsigfigs + $reqsigfigoffset) {return false;} //too many sigfigs
          $gasigfig = max($reqsigfigs, strlen(rtrim($absgivenans,'0')));
        } else {
          if (abs($absgivenans)<1) {
              $gasigfig = strlen(ltrim(substr($absgivenans,$gadploc+1),'0'));
          } else {
              $gasigfig = strlen(ltrim($absgivenans,'0'))-1;
          }
          if ($anans != 0 && $gasigfig < $reqsigfigs ) { return false; } //not enough sigfigs
          if ($anans != 0 && $reqsigfigoffset>0 && $gasigfig > $reqsigfigs + $reqsigfigoffset) {return false;} //too many sigfigs
        }
      } else {
          $absgivenans = str_replace('-', '', $givenunits[2]);
          $gadploc = strpos($absgivenans, '.');
          if ($gadploc === false) { //no decimal place
            if (strlen(rtrim($absgivenans,'0')) > $reqsigfigs || 
                strlen($absgivenans) < $reqsigfigs
            ) { 
                return false;
            }
            $gasigfig = $reqsigfigs;
          } else {
            if (abs($givenunits[2]) < 1) {
                if (strlen(ltrim(substr($absgivenans, $gadploc + 1), '0')) != $reqsigfigs) {return false;}
            } else {
                if (strlen(ltrim($absgivenans, '0')) - 1 != $reqsigfigs) {return false;}
            }
            $gasigfig = $reqsigfigs;
          }
      }
  }
    //checked format, now check values, using values in base units
    if ($sigfigscoretype[0] == 'abs') {
      // adjust tolerance given unit conversions
      $sigfigscoretype[1] = $sigfigscoretype[1] * $ansunits[3];
      if ($givenunits[2] != 0) {
        // may need to adjust abs tolerance, since comparison is being made in base units
        // make it one final sigfig value in givenans units.
        $v = -1 * floor(-log10(abs($givenunits[2])) - 1e-12) - $gasigfig;
        $altabstol = pow(10, $v) * $givenunits[3] * .5;
        $sigfigscoretype[1] = max($sigfigscoretype[1], $altabstol);
      } 
        if (abs($anans - $givenans) < $sigfigscoretype[1] + $epsilon) {return true;}
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
// \b(m|meter|micron|angstrom|fermi|in|inch|inches|ft|foot|feet|mi|mile|furlong|yd|yard|s|sec|second|min|minute|h|hr|hour|day|week|mo|month|yr|year|fortnight|acre|ha|hectare|b|barn|L|liter|litre|cc|gal|gallon|cup|pt|pint|qt|quart|tbsp|tablespoon|tsp|teaspoon|rad|radian|deg|degree|arcminute|arcsecond|grad|gradian|knot|kt|c|mph|kph|g|gram|t|tonne|Hz|hertz|rev|revolution|cycle|N|newton|kip|dyn|dyne|lb|pound|lbf|ton|J|joule|erg|lbft|ftlb|cal|calorie|eV|electronvolt|Wh|Btu|therm|W|watt|hp|horsepower|Pa|pascal|atm|atmosphere|bar|Torr|mmHg|umHg|cmWater|psi|ksi|Mpsi|C|coulomb|V|volt|farad|F|ohm|amp|ampere|A|T|tesla|G|gauss|Wb|weber|H|henry|lm|lumen|lx|lux|amu|dalton|Da|me|mol|mole|M|Ci|curie|R|roentgen|sr|steradian|Bq|becquerel|ls|lightsecond|ly|lightyear|AU|au|parsec|pc|solarmass|solarradius|degF|degC|degK|K)\b
//
