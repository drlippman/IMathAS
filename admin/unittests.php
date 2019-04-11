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

require("../assessment/displayq2.php");

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



