<?php
//Unit tests for specific functions
//(c) 2018 IMathAS

require("../init.php");

if ($myrights<100) {
	echo "Unauthorized";
	exit;
}


//each test is [string to look at, requiretimes, expected result]
$reqtimestests = [
	['3x^2+4x', 'x,=2,3,>0', 1],
	['3x+30x^2+4', '3,=2', 1],
	['3x+30x^2+4', '#3,=1', 1],
];

//each test is [string to test, answerformat, expected result]
$ansformattests = [
                ['4/6',  'fraction', true],
                ['4/6', 'reducedfraction', false]
];

//makexxpretty: each test is [input string, expected output]
$xxtests = [
    ['3+0+4','3+4'],
    ['3-0-4','3-4'],
    ['4x+x^0','4x+1'],
    ['x^0','1'],
    ['5(x+3)^0','5'],
    ['4+h_0-2','4+h_0-2'],
    ['3+0x-2+0(x-3)^2-4-(x+4)0-1','3-2-4-1'],
    ['3x=0','3x = 0'],
    ['3x=0x','3x = 0'],
    ['3x=-0','3x = 0'],
    ['0=3x','0 = 3x'],
    ['0x=3x','0 = 3x'],
    ['-0=3x','0 = 3x'],
    ['3x=', '3x ='],
    ['0^3+1x-1x+1/x+1x/y','x-x+1/x+x/y'],
    ['x^0y+x^0*y','y+y'],
    ['3x^1+x/1+x/12','3x+x+x/12'],
    ['(x+3)/1+x^1','(x+3)+x'],
    ['3+0<1x<0x+5','3 < x <  5'],
    ['x+1 leq 3', 'x+1 leq 3']
];

require("../assessment/interpret5.php");
require("../assessment/macros.php");
require("../assess2/questions/answerboxhelpers.php");


foreach ($reqtimestests as $test) {
	echo "Testing ".Sanitize::encodeStringForDisplay($test[0]);
	echo " against ".Sanitize::encodeStringForDisplay($test[1]);
	echo ": ";
	if (checkreqtimes($test[0], $test[1])==$test[2]) {
		echo '<span style="color:green">Pass</span>';
	} else {
		echo '<span style="color:red">Fail</span>';
	}
	echo '<br/>';
}

foreach ($ansformattests as $test) {
	echo "Testing ".Sanitize::encodeStringForDisplay($test[0]);
	echo " with ".Sanitize::encodeStringForDisplay($test[1]);
	echo ": ";
	if (checkanswerformat($test[0], $test[1])==$test[2]) {
		echo '<span style="color:green">Pass</span>';
	} else {
		echo '<span style="color:red">Fail</span>';
	}
	echo '<br/>';
}


foreach ($xxtests as $test) {
    $res = trim(makexxpretty($test[0]));
	echo "Testing ".Sanitize::encodeStringForDisplay($test[0]);
	echo ": ".Sanitize::encodeStringForDisplay($res)." vs ".Sanitize::encodeStringForDisplay($test[1]);
	echo ": ";
	if ($res==$test[1]) {
		echo '<span style="color:green">Pass</span>';
	} else {
		echo '<span style="color:red">Fail</span>';
	}
	echo '<br/>';
}



