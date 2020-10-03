<?php
// Units checking

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
    
    //Units array: 'unit' => [numerical factor, [array of factors of fundamental units]]
    $units=[
    //Length
      'm' => [1,array(0,1,0,0,0,0,0,0,0,0)],
      'meter' => [1,array(0,1,0,0,0,0,0,0,0,0)],
      'meters' => [1,array(0,1,0,0,0,0,0,0,0,0)],
      'km' => [1000,array(0,1,0,0,0,0,0,0,0,0)], //kilometer
      'cm' => [0.01,array(0,1,0,0,0,0,0,0,0,0)], //centimeter
      'mm' => [0.001,array(0,1,0,0,0,0,0,0,0,0)], //millimeter
      'um' => [1E-6,array(0,1,0,0,0,0,0,0,0,0)], //micrometer
      'micron' => [1E-6,array(0,1,0,0,0,0,0,0,0,0)],
      'microns' => [1E-6,array(0,1,0,0,0,0,0,0,0,0)],
      'nm' => [1E-9,array(0,1,0,0,0,0,0,0,0,0)], //nanometer
      'Angstrom' => [1E-10,array(0,1,0,0,0,0,0,0,0,0)],
      'Angstroms' => [1E-10,array(0,1,0,0,0,0,0,0,0,0)],
      'angstrom' => [1E-10,array(0,1,0,0,0,0,0,0,0,0)],
      'angstroms' => [1E-10,array(0,1,0,0,0,0,0,0,0,0)],
      'pm' => [1E-12,array(0,1,0,0,0,0,0,0,0,0)], //picometer
      'fm' => [1E-15,array(0,1,0,0,0,0,0,0,0,0)], //femtometer
      'fermi' => [1E-15,array(0,1,0,0,0,0,0,0,0,0)],
      'in' => [0.0254,array(0,1,0,0,0,0,0,0,0,0)],
      'inch' => [0.0254,array(0,1,0,0,0,0,0,0,0,0)],
      'inches' => [0.0254,array(0,1,0,0,0,0,0,0,0,0)],
      'ft' => [0.3048,array(0,1,0,0,0,0,0,0,0,0)],
      'foot' => [0.3048,array(0,1,0,0,0,0,0,0,0,0)],
      'feet' => [0.3048,array(0,1,0,0,0,0,0,0,0,0)],
      'mi' => [1609.344,array(0,1,0,0,0,0,0,0,0,0)],
      'mile' => [1609.344,array(0,1,0,0,0,0,0,0,0,0)],
      'miles' => [1609.344,array(0,1,0,0,0,0,0,0,0,0)],
      'furlong' => [201.168,array(0,1,0,0,0,0,0,0,0,0)],
      'furlongs' => [201.168,array(0,1,0,0,0,0,0,0,0,0)],
      'yd' => [0.9144,array(0,1,0,0,0,0,0,0,0,0)],
      'yard' => [0.9144,array(0,1,0,0,0,0,0,0,0,0)],
      'yards' => [0.9144,array(0,1,0,0,0,0,0,0,0,0)],
    //Time
      's' => [1,array(0,0,1,0,0,0,0,0,0,0)],
      'sec' => [1,array(0,0,1,0,0,0,0,0,0,0)],
      'second' => [1,array(0,0,1,0,0,0,0,0,0,0)],
      'seconds' => [1,array(0,0,1,0,0,0,0,0,0,0)],
      'ms' => [0.001,array(0,0,1,0,0,0,0,0,0,0)], //milliseconds
      'us' => [1E-6,array(0,0,1,0,0,0,0,0,0,0)], //microseconds
      'ns' => [1E-9,array(0,0,1,0,0,0,0,0,0,0)], //nanoseconds
      'min' => [60,array(0,0,1,0,0,0,0,0,0,0)],
      'minute' => [60,array(0,0,1,0,0,0,0,0,0,0)],
      'minutes' => [60,array(0,0,1,0,0,0,0,0,0,0)],
      'hr' => [60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'hour' => [60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'hours' => [60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'day' => [24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'days' => [24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'week' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'weeks' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'mo' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0)], //assumes 30 days
      'month' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'months' => [30*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'yr' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'year' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'years' => [7*24*60*60,array(0,0,1,0,0,0,0,0,0,0)],
      'fortnight' => [1209600,array(0,0,1,0,0,0,0,0,0,0)],
      'fortnights' => [1209600,array(0,0,1,0,0,0,0,0,0,0)],
    //Area
      'acre' => [4046.86,array(0,2,0,0,0,0,0,0,0,0)],
      'acres' => [4046.86,array(0,2,0,0,0,0,0,0,0,0)],
      'ha' => [1E4,array(0,2,0,0,0,0,0,0,0,0)],
      'hectare' => [1E4,array(0,2,0,0,0,0,0,0,0,0)],
      'hectares' => [1E4,array(0,2,0,0,0,0,0,0,0,0)],
      'b' => [1E-28,array(0,2,0,0,0,0,0,0,0,0)], //barn
      'barn' => [1E-28,array(0,2,0,0,0,0,0,0,0,0)],
      'barns' => [1E-28,array(0,2,0,0,0,0,0,0,0,0)],
    //Volume
      'L' => [0.001,array(0,3,0,0,0,0,0,0,0,0)],
      'liter' => [0.001,array(0,3,0,0,0,0,0,0,0,0)],
      'litre' => [0.001,array(0,3,0,0,0,0,0,0,0,0)],
      'liters' => [0.001,array(0,3,0,0,0,0,0,0,0,0)],
      'litres' => [0.001,array(0,3,0,0,0,0,0,0,0,0)],
      'dL' => [0.0001,array(0,3,0,0,0,0,0,0,0,0)],
      'ml' => [1E-6,array(0,3,0,0,0,0,0,0,0,0)], //milliliter
      'mL' => [1E-6,array(0,3,0,0,0,0,0,0,0,0)],
      'cc' => [1E-6,array(0,3,0,0,0,0,0,0,0,0)],
      'gal' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0)],
      'gallon' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0)],
      'gallons' => [0.00378541,array(0,3,0,0,0,0,0,0,0,0)],
      'cup' => [0.000236588,array(0,3,0,0,0,0,0,0,0,0)],
      'cups' => [0.000236588,array(0,3,0,0,0,0,0,0,0,0)],
      'pint' => [0.000473176,array(0,3,0,0,0,0,0,0,0,0)],
      'pints' => [0.000473176,array(0,3,0,0,0,0,0,0,0,0)],
      'quart' => [0.000946353,array(0,3,0,0,0,0,0,0,0,0)],
      'quarts' => [0.000946353,array(0,3,0,0,0,0,0,0,0,0)],
      'tbsp' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0)], //U.S. tablespoon
      'tablespoon' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0)],
      'tablespoons' => [1.47868E-5,array(0,3,0,0,0,0,0,0,0,0)],
      'tsp' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0)], //U.S. teaspoon
      'teaspoon' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0)],
      'teaspoons' => [4.92892E-6,array(0,3,0,0,0,0,0,0,0,0)],
    //Angles
      'rad' => [1,array(0,0,0,1,0,0,0,0,0,0)],
      'radian' => [1,array(0,0,0,1,0,0,0,0,0,0)],
      'radians' => [1,array(0,0,0,1,0,0,0,0,0,0)],
      'deg' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0)],
      'degree' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0)],
      'degrees' => [0.0174532925,array(0,0,0,1,0,0,0,0,0,0)],
      'gradian' => [0.015708,array(0,0,0,1,0,0,0,0,0,0)],
      'gradians' => [0.015708,array(0,0,0,1,0,0,0,0,0,0)],
    //Velocity
      'knot' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0)],
      'knots' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0)],
      'kt' => [0.5144444444,array(0,1,-1,0,0,0,0,0,0,0)],
      'c' => [299792458,array(0,1,-1,0,0,0,0,0,0,0)], // Speed of light
      'mph' => [0.44704,array(0,1,-1,0,0,0,0,0,0,0)],
      'kph' => [0.277778,array(0,1,-1,0,0,0,0,0,0,0)],
    //Mass
      'kg' => [1,array(1,0,0,0,0,0,0,0,0,0)], //kilogram
      'g' => [0.001,array(1,0,0,0,0,0,0,0,0,0)],
      'gram' => [0.001,array(1,0,0,0,0,0,0,0,0,0)],
      'grams' => [0.001,array(1,0,0,0,0,0,0,0,0,0)],
      'mg' => [0.000001,array(1,0,0,0,0,0,0,0,0,0)], //milligram
      'tonne' => [1000,array(1,0,0,0,0,0,0,0,0,0)],
      'tonnes' => [1000,array(1,0,0,0,0,0,0,0,0,0)],
    //Frequency
      'Hz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0)],
      'hz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0)],
      'Hertz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0)],
      'hertz' => [2*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0)],
      'kHz' => [2000*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0)],
      'khz' => [2000*3.14159265358979,array(0,0,-1,1,0,0,0,0,0,0)],
      'rev' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0)],
      'revs' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0)],
      'revolution' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0)],
      'revolutions' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0)],
      'cycle' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0)],
      'cycles' => [2*3.14159265358979,array(0,0,0,1,0,0,0,0,0,0)],
    //Force
      'N' => [1,array(1,1,-2,0,0,0,0,0,0,0)],
      'Newton' => [1,array(1,1,-2,0,0,0,0,0,0,0)],
      'Newtons' => [1,array(1,1,-2,0,0,0,0,0,0,0)],
      'newton' => [1,array(1,1,-2,0,0,0,0,0,0,0)],
      'newtons' => [1,array(1,1,-2,0,0,0,0,0,0,0)],
      'kip' => [4448.22,array(1,1,-2,0,0,0,0,0,0,0)],
      'kips' => [4448.22,array(1,1,-2,0,0,0,0,0,0,0)],
      'dyne' => [1E-5,array(1,1,-2,0,0,0,0,0,0,0)],
      'dynes' => [1E-5,array(1,1,-2,0,0,0,0,0,0,0)],
      'lb' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0)],
      'lbs' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0)],
      'pound' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0)],
      'pounds' => [4.4482216152605,array(1,1,-2,0,0,0,0,0,0,0)],
      'ton' => [8896.443,array(1,1,-2,0,0,0,0,0,0,0)],
      'tons' => [8896.443,array(1,1,-2,0,0,0,0,0,0,0)],
    //Energy
      'J' => [1,array(1,2,-2,0,0,0,0,0,0,0)],
      'Joule' => [1,array(1,2,-2,0,0,0,0,0,0,0)],
      'Joules' => [1,array(1,2,-2,0,0,0,0,0,0,0)],
      'joule' => [1,array(1,2,-2,0,0,0,0,0,0,0)],
      'joules' => [1,array(1,2,-2,0,0,0,0,0,0,0)],
      'KJ' => [1000,array(1,2,-2,0,0,0,0,0,0,0)], //kiloJoules
      'kJ' => [1000,array(1,2,-2,0,0,0,0,0,0,0)],
      'erg' => [1E-7,array(1,2,-2,0,0,0,0,0,0,0)],
      'ergs' => [1E-7,array(1,2,-2,0,0,0,0,0,0,0)],
      'lbf' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0)],
      'lbft' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0)],
      'ftlb' => [1.35582,array(1,2,-2,0,0,0,0,0,0,0)],
      'cal' => [4.184,array(1,2,-2,0,0,0,0,0,0,0)],
      'calorie' => [4.184,array(1,2,-2,0,0,0,0,0,0,0)],
      'calories' => [4.184,array(1,2,-2,0,0,0,0,0,0,0)],
      'kcal' => [4184,array(1,2,-2,0,0,0,0,0,0,0)], //kilocalorie
      'eV' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0)],
      'electronvolt' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0)],
      'electronvolts' => [1.602176634E-19,array(1,2,-2,0,0,0,0,0,0,0)],
      'kwh' => [3.6E6,array(1,2,-2,0,0,0,0,0,0,0)], //kiloWatthour
      'kWh' => [3.6E6,array(1,2,-2,0,0,0,0,0,0,0)],
      'btu' => [1055.06,array(1,2,-2,0,0,0,0,0,0,0)], //British thermal units
      'BTU' => [1055.06,array(1,2,-2,0,0,0,0,0,0,0)],  
    //Power
      'W' => [1,array(1,2,-3,0,0,0,0,0,0,0)],
      'Watt' => [1,array(1,2,-3,0,0,0,0,0,0,0)],
      'Watts' => [1,array(1,2,-3,0,0,0,0,0,0,0)],
      'watt' => [1,array(1,2,-3,0,0,0,0,0,0,0)],
      'watts' => [1,array(1,2,-3,0,0,0,0,0,0,0)],
      'kW' => [1000,array(1,2,-3,0,0,0,0,0,0,0)], //kiloWatt
      'hp' => [746,array(1,2,-3,0,0,0,0,0,0,0)],
      'horsepower' => [746,array(1,2,-3,0,0,0,0,0,0,0)],
    //Pressure
      'Pa' => [1,array(1,-1,-2,0,0,0,0,0,0,0)],
      'Pascal' => [1,array(1,-1,-2,0,0,0,0,0,0,0)],
      'Pascals' => [1,array(1,-1,-2,0,0,0,0,0,0,0)],
      'pascal' => [1,array(1,-1,-2,0,0,0,0,0,0,0)],
      'pascals' => [1,array(1,-1,-2,0,0,0,0,0,0,0)],
      'kPa' => [1000,array(1,-1,-2,0,0,0,0,0,0,0)], //kilopascal
      'MPa' => [1E6,array(1,-1,-2,0,0,0,0,0,0,0)], //megapascal
      'GPa' => [1E9,array(1,-1,-2,0,0,0,0,0,0,0)], //gigapascal
      'atm' => [1.01E5,array(1,-1,-2,0,0,0,0,0,0,0)],
      'atms' => [1.01E5,array(1,-1,-2,0,0,0,0,0,0,0)],
      'atmosphere' => [1.01E5,array(1,-1,-2,0,0,0,0,0,0,0)],
      'atmospheres' => [1.01E5,array(1,-1,-2,0,0,0,0,0,0,0)],
      'bar' => [100000,array(1,-1,-2,0,0,0,0,0,0,0)],
      'bars' => [100000,array(1,-1,-2,0,0,0,0,0,0,0)],
      'barometer' => [100000,array(1,-1,-2,0,0,0,0,0,0,0)],
      'barometers' => [100000,array(1,-1,-2,0,0,0,0,0,0,0)],
      'mbar' => [100,array(1,-1,-2,0,0,0,0,0,0,0)], //millibar
      'mbars' => [100,array(1,-1,-2,0,0,0,0,0,0,0)],
      'Torr' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0)],
      'torr' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0)],
      'mmHg' => [133.322,array(1,-1,-2,0,0,0,0,0,0,0)],
      'cmWater' => [98.0638,array(1,-1,-2,0,0,0,0,0,0,0)], //This comes from a cmH2O preg_replace
      'psi' => [98.0638,array(1,-1,-2,0,0,0,0,0,0,0)],
    //Electrical Units
      'C' => [1,array(0,0,1,0,0,0,0,0,1,0)],
      'Coulomb' => [1,array(0,0,1,0,0,0,0,0,1,0)],
      'Coulombs' => [1,array(0,0,1,0,0,0,0,0,1,0)],
      'coulomb' => [1,array(0,0,1,0,0,0,0,0,1,0)],
      'coulombs' => [1,array(0,0,1,0,0,0,0,0,1,0)],
      'V' => [1,array(1,2,-3,0,0,0,0,0,1,0)],
      'Volt' => [1,array(1,2,-3,0,0,0,0,0,1,0)],
      'Volts' => [1,array(1,2,-3,0,0,0,0,0,1,0)],
      'volt' => [1,array(1,2,-3,0,0,0,0,0,1,0)],
      'volts' => [1,array(1,2,-3,0,0,0,0,0,1,0)],
      'mV' => [0.001,array(1,2,-3,0,0,0,0,0,1,0)], //millivolt
      'MV' => [1E6,array(1,2,-3,0,0,0,0,0,1,0)], //megavolt
      'Farad' => [1,array(-1,-2,4,0,0,0,0,0,2,0)],
      'farad' => [1,array(-1,-2,4,0,0,0,0,0,2,0)],
      'ohm' => [1,array(1,2,-3,0,0,0,0,0,-2,0)],
      'ohms' => [1,array(1,2,-3,0,0,0,0,0,-2,0)],
      'amp' => [1,array(0,0,0,0,0,0,0,0,1,0)],
      'amps' => [1,array(0,0,0,0,0,0,0,0,1,0)],
      'Ampere' => [1,array(0,0,0,0,0,0,0,0,1,0)],
      'Amperes' => [1,array(0,0,0,0,0,0,0,0,1,0)],
      'ampere' => [1,array(0,0,0,0,0,0,0,0,1,0)],
      'amperes' => [1,array(0,0,0,0,0,0,0,0,1,0)],
    //Magnetic Units
      'T' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'Tesla' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'Teslas' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'tesla' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'teslas' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'G' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'Gauss' => [1,array(1,0,-2,0,0,0,0,0,-1,0)],
      'Wb' => [1,array(1,2,-2,0,0,0,0,0,-1,0)],
      'Weber' => [1,array(1,2,-2,0,0,0,0,0,-1,0)],
      'H' => [1,array(1,2,-2,0,0,0,0,0,-2,0)],
      'Henry' => [1,array(1,2,-2,0,0,0,0,0,-2,0)],
    //Luminosity
      'lm' => [1,array(0,0,0,-2,0,0,0,0,0,1)],
      'lumen' => [1,array(0,0,0,-2,0,0,0,0,0,1)],
      'lumens' => [1,array(0,0,0,-2,0,0,0,0,0,1)],
      'lx' => [1,array(0,-2,0,-2,0,0,0,0,0,1)],
      'lux' => [1,array(0,-2,0,-2,0,0,0,0,0,1)],
    //Atomic Units
      'amu' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0)], //atomic mass unit
      'Dalton' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0)],
      'Daltons' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0)],
      'dalton' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0)],
      'daltons' => [1.660538921E-27,array(1,0,0,0,0,0,0,0,0,0)],
      'me' => [9.1093826E-31,array(1,0,0,0,0,0,0,0,0,0)], //electron resting mass
    //Other science units
      'mol' => [1,array(0,0,0,0,0,0,0,1,0,0)],
      'mole' => [1,array(0,0,0,0,0,0,0,1,0,0)],
      'Ci' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0)], //curie
      'curie' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0)],
      'curies' => [3.7E10,array(0,0,-1,0,0,0,0,0,0,0)],
      'R' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0)], //roentgen
      'roentgen' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0)],
      'roentgens' => [2.58E-4,array(-1,0,1,0,0,0,0,0,1,0)],
      'sr' => [1,array(0,0,0,2,0,0,0,0,0,0)], //steradian
      'steradian' => [1,array(0,0,0,2,0,0,0,0,0,0)],
      'steradians' => [1,array(0,0,0,2,0,0,0,0,0,0)],
      'Bq' => [1,array(0,0,-1,0,0,0,0,0,0,0)], //becquerel
      'bequerel' => [1,array(0,0,-1,0,0,0,0,0,0,0)],
    //Astronomy Units
      'ls' => [299792458,array(0,1,0,0,0,0,0,0,0,0)],
      'lightsecond' => [299792458,array(0,1,0,0,0,0,0,0,0,0)],
      'ly' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0)],
      'lightyear' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0)],
      'lightyears' => [9460730472580800,array(0,1,0,0,0,0,0,0,0,0)],
      'AU' => [149597870700,array(0,1,0,0,0,0,0,0,0,0)], //astronomical unit
      'au' => [149597870700,array(0,1,0,0,0,0,0,0,0,0)],
      'parsec' => [3.08567758149137E16,array(0,1,0,0,0,0,0,0,0,0)],
      'parsecs' => [3.08567758149137E16,array(0,1,0,0,0,0,0,0,0,0)],
      'kpc' => [3.08567758149137E19,array(0,1,0,0,0,0,0,0,0,0)], //kiloparsec
      'solarmass' => [1.98892E30,array(1,0,0,0,0,0,0,0,0,0)],
      'solarradius' => [6.955E8,array(0,1,0,0,0,0,0,0,0,0)],
    //Temperature
      'degF' => [1,array(0,0,0,0,0,1,0,0,0,0)],
      'degC' => [1,array(0,0,0,0,1,0,0,0,0,0)],
      'degK' => [1,array(0,0,0,0,0,0,1,0,0,0)],
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
    $unitsExpression = preg_replace('/\s*[\*\s]\s*/','*',$unitsExpression); //trims space around multiplication symbol, spaces become *
    $unitsExpression = preg_replace('/\((.*?)\)\s*\//', '$1/', $unitsExpression); // strip paren around numerator
    $unitsExpression = preg_replace('/\/\s*\((.*?)\)/', '/$1', $unitsExpression); // strip paren around denom

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
          if (floor(evalMathParser($pow))!=evalMathParser($pow)||isNaN(evalMathParser($pow))) {
            echo 'Eek! Exponents on units must be integers.';
            return '';
          }
          $part = substr($part,0,strpos($part,'^')); //Now $part only has letters.
          
          $prefixCount=-1;
          $partPrefix=[];
          while (preg_match($unitPrefixPattern,$part)) { //Does it have a metric prefix?
            $prefixCount = $prefixCount+1;
            preg_match($unitPrefixPattern,$part,$matches[$prefixCount]); //$matches[0][0] catches the first prefix, $matches[1][0] is the 2nd prefix, etc.
            $partPrefix[$prefixCount] = $matches[$prefixCount][0]; //Here is the prefix. [Could be empty!]
            $prefixLength = strlen($matches[$prefixCount][0]);
            $part = substr($part,$prefixLength); //Now $part is just the unit.
            if ($part == '' || empty($part)) {
              echo 'Eek! The prefix \''.$partPrefix[$prefixCount].'\' must be followed by a unit.';
              return '';
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
          $pow=substr($part,strpos($part,'^')+1);
          if (floor(evalMathParser($pow))!=evalMathParser($pow)||isNaN(evalMathParser($pow))) {
            echo 'Eek! Exponents on units must be integers.';
            return '';
          }
          $part = substr($part,0,strpos($part,'^')); //Now $part is the prefix-and-unit.
          
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
